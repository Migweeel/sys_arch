<?php
require_once '../auth/session.php';
require_once '../helpers/functions.php';
require_once '../config/db.php';

function redirectToBackupPage() {
    header('Location: ../dashboard/admin.php?section=backup');
    exit();
}

function decryptDatabaseBackup($encryptedBackup, $password) {
    if (!extension_loaded('openssl')) {
        throw new RuntimeException('OpenSSL is not enabled in PHP.');
    }
    if (!is_string($password) || $password === '') {
        throw new RuntimeException('Enter the encryption password for this backup.');
    }

    try {
        $payload = json_decode($encryptedBackup, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        throw new RuntimeException('The encrypted backup file is invalid.');
    }

    if (!is_array($payload)
        || ($payload['format'] ?? '') !== 'krus-na-ligas-encrypted-database-backup'
        || ($payload['version'] ?? 0) !== 1
        || ($payload['cipher'] ?? '') !== 'aes-256-gcm'
        || ($payload['kdf'] ?? '') !== 'pbkdf2-sha256') {
        throw new RuntimeException('The selected file is not a supported encrypted database backup.');
    }

    $iterations = $payload['iterations'] ?? 0;
    if (!is_int($iterations) || $iterations < 100000 || $iterations > 1000000) {
        throw new RuntimeException('The encrypted backup file has invalid security settings.');
    }

    $salt = base64_decode((string) ($payload['salt'] ?? ''), true);
    $iv = base64_decode((string) ($payload['iv'] ?? ''), true);
    $tag = base64_decode((string) ($payload['tag'] ?? ''), true);
    $ciphertext = base64_decode((string) ($payload['ciphertext'] ?? ''), true);
    if ($salt === false || $iv === false || $tag === false || $ciphertext === false || strlen($salt) !== 16 || strlen($iv) !== 12 || strlen($tag) !== 16) {
        throw new RuntimeException('The encrypted backup file is incomplete or corrupted.');
    }

    $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);
    $databaseDump = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($databaseDump === false) {
        throw new RuntimeException('The encryption password is incorrect or the backup file was altered.');
    }

    return $databaseDump;
}

function readBackupSql($backupPath, $password) {
    $extension = strtolower(pathinfo($backupPath, PATHINFO_EXTENSION));

    if ($extension === 'enc') {
        $encryptedBackup = file_get_contents($backupPath);
        if ($encryptedBackup === false) {
            throw new RuntimeException('Unable to read the encrypted backup file.');
        }
        return decryptDatabaseBackup($encryptedBackup, $password);
    }

    if ($extension === 'sql') {
        $sql = file_get_contents($backupPath);
        if ($sql === false) {
            throw new RuntimeException('Unable to read the SQL backup file.');
        }
        return $sql;
    }

    if ($extension !== 'zip' || !class_exists('ZipArchive')) {
        throw new RuntimeException('This backup format cannot be restored on this server.');
    }

    $zip = new ZipArchive();
    if ($zip->open($backupPath) !== true) {
        throw new RuntimeException('Unable to open the legacy ZIP backup.');
    }

    $sqlEntry = null;
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entry = $zip->statIndex($index);
        $entryName = $entry['name'] ?? '';
        if (preg_match('#^database/[^/]+\.sql$#i', $entryName)) {
            $sqlEntry = $entryName;
            break;
        }
    }

    if ($sqlEntry === null) {
        $zip->close();
        throw new RuntimeException('The ZIP file does not contain a database backup.');
    }

    $sql = $zip->getFromName($sqlEntry);
    $zip->close();
    if ($sql === false) {
        throw new RuntimeException('Unable to read the database backup inside the ZIP file.');
    }

    return $sql;
}

function validateDatabaseDump($sql) {
    if (strlen($sql) === 0 || strlen($sql) > 50 * 1024 * 1024) {
        throw new RuntimeException('The backup file is empty or too large to restore.');
    }

    if (!preg_match('/\bCREATE\s+TABLE\b/i', $sql)) {
        throw new RuntimeException('The selected file is not a valid database backup.');
    }

    if (preg_match('/\b(DROP\s+DATABASE|CREATE\s+USER|GRANT\s+|REVOKE\s+|LOAD\s+DATA|INTO\s+OUTFILE|INSTALL\s+PLUGIN|SHUTDOWN)\b/i', $sql)) {
        throw new RuntimeException('The backup contains commands that are not allowed for restore.');
    }
}

function executeDatabaseDump($conn, $sql) {
    if (!mysqli_multi_query($conn, $sql)) {
        throw new RuntimeException('Database restore failed: ' . mysqli_error($conn));
    }

    do {
        $result = mysqli_store_result($conn);
        if ($result instanceof mysqli_result) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));

    if (mysqli_error($conn) !== '') {
        throw new RuntimeException('Database restore failed: ' . mysqli_error($conn));
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['errors'] = ['Only admins can restore database backups.'];
    redirectToBackupPage();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToBackupPage();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['errors'] = ['Security token mismatch. Please try again.'];
    redirectToBackupPage();
}

if (($_POST['confirm_restore'] ?? '') !== '1') {
    $_SESSION['errors'] = ['Confirm the restore before continuing.'];
    redirectToBackupPage();
}

if ($conn === null) {
    $_SESSION['errors'] = ['Database connection failed. Please try again later.'];
    redirectToBackupPage();
}

$backupName = basename((string) ($_POST['backup_file'] ?? ''));
if ($backupName === '' || !preg_match('/\.(enc|sql|zip)$/i', $backupName)) {
    $_SESSION['errors'] = ['Please select a valid local backup file.'];
    redirectToBackupPage();
}

$backupDirectory = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups');
$backupPath = $backupDirectory ? realpath($backupDirectory . DIRECTORY_SEPARATOR . $backupName) : false;
if ($backupPath === false || !is_file($backupPath) || dirname($backupPath) !== $backupDirectory) {
    $_SESSION['errors'] = ['The selected backup file was not found.'];
    redirectToBackupPage();
}

try {
    $databaseDump = readBackupSql($backupPath, $_POST['restore_password'] ?? '');
    validateDatabaseDump($databaseDump);
    executeDatabaseDump($conn, $databaseDump);

    logActivity(
        $conn,
        (int) $_SESSION['user_id'],
        $_SESSION['username'] ?? 'Admin',
        'admin',
        'database_restored',
        'Admin restored database backup: ' . $backupName . '.'
    );
    $_SESSION['success'] = 'Database restored successfully from ' . $backupName . '.';
} catch (Throwable $error) {
    $_SESSION['errors'] = ['Database restore failed: ' . $error->getMessage()];
}

redirectToBackupPage();
