<?php
require_once '../auth/session.php';
require_once '../helpers/functions.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['errors'] = ["Only admins can create system backups."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['errors'] = ["Security token mismatch. Please try again."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

if ($conn === null) {
    $_SESSION['errors'] = ["Database connection failed. Please try again later."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

$backupPassword = $_POST['backup_password'] ?? '';
$confirmBackupPassword = $_POST['confirm_backup_password'] ?? '';
if (!is_string($backupPassword) || strlen($backupPassword) < 12) {
    $_SESSION['errors'] = ["Backup password must contain at least 12 characters."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

if (!is_string($confirmBackupPassword) || !hash_equals($backupPassword, $confirmBackupPassword)) {
    $_SESSION['errors'] = ["Backup passwords do not match."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

if (!extension_loaded('openssl')) {
    $_SESSION['errors'] = ["OpenSSL is not enabled in PHP. Enable it in XAMPP before creating encrypted backups."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

function backupSqlValue($conn, $value) {
    if ($value === null) {
        return 'NULL';
    }

    return "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
}

function buildDatabaseDump($conn, $databaseName) {
    $safeDatabase = str_replace('`', '``', $databaseName);
    $dump = "-- Krus na Ligas database backup\n";
    $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $dump .= "START TRANSACTION;\n";
    $dump .= "SET time_zone = \"+00:00\";\n\n";
    $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    $dump .= "CREATE DATABASE IF NOT EXISTS `$safeDatabase` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $dump .= "USE `$safeDatabase`;\n\n";

    $tablesResult = mysqli_query($conn, "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if (!$tablesResult) {
        throw new RuntimeException('Unable to list database tables.');
    }

    while ($tableRow = mysqli_fetch_row($tablesResult)) {
        $tableName = (string) $tableRow[0];
        $safeTable = str_replace('`', '``', $tableName);

        $createResult = mysqli_query($conn, "SHOW CREATE TABLE `$safeTable`");
        if (!$createResult || !($createRow = mysqli_fetch_assoc($createResult))) {
            continue;
        }

        $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';
        $dump .= "DROP TABLE IF EXISTS `$safeTable`;\n";
        $dump .= $createSql . ";\n\n";

        $rowsResult = mysqli_query($conn, "SELECT * FROM `$safeTable`");
        if (!$rowsResult) {
            continue;
        }

        $fields = [];
        while ($field = mysqli_fetch_field($rowsResult)) {
            $fields[] = '`' . str_replace('`', '``', $field->name) . '`';
        }

        while ($row = mysqli_fetch_assoc($rowsResult)) {
            $values = [];
            foreach ($row as $value) {
                $values[] = backupSqlValue($conn, $value);
            }
            $dump .= "INSERT INTO `$safeTable` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ");\n";
        }

        $dump .= "\n";
    }

    $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $dump .= "COMMIT;\n";
    return $dump;
}

function encryptDatabaseBackup($databaseDump, $password) {
    $cipher = 'aes-256-gcm';
    $salt = random_bytes(16);
    $iv = random_bytes(openssl_cipher_iv_length($cipher));
    $key = hash_pbkdf2('sha256', $password, $salt, 310000, 32, true);
    $tag = '';
    $ciphertext = openssl_encrypt($databaseDump, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

    if ($ciphertext === false) {
        throw new RuntimeException('Unable to encrypt the database backup.');
    }

    $payload = [
        'format' => 'krus-na-ligas-encrypted-database-backup',
        'version' => 1,
        'cipher' => $cipher,
        'kdf' => 'pbkdf2-sha256',
        'iterations' => 310000,
        'salt' => base64_encode($salt),
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
        'ciphertext' => base64_encode($ciphertext),
    ];

    return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}

$projectRoot = realpath(dirname(__DIR__));
$backupRoot = $projectRoot . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupRoot) && !mkdir($backupRoot, 0775, true)) {
    $_SESSION['errors'] = ["Unable to create the backups folder."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

$timestamp = date('Ymd_His');
$backupFilename = 'database_backup_' . $timestamp . '.enc';
$backupPath = $backupRoot . DIRECTORY_SEPARATOR . $backupFilename;

try {
    $databaseName = $database ?? 'krus_na_ligas_db';
    $databaseDump = buildDatabaseDump($conn, $databaseName);
    $encryptedBackup = encryptDatabaseBackup($databaseDump, $backupPassword);
    if (file_put_contents($backupPath, $encryptedBackup, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save the database backup file.');
    }

    logActivity(
        $conn,
        (int) $_SESSION['user_id'],
        $_SESSION['username'] ?? 'Admin',
        'admin',
        'database_backup_created',
        'Admin created encrypted database backup: ' . $backupFilename . '.'
    );
} catch (Throwable $error) {
    if (is_file($backupPath)) {
        unlink($backupPath);
    }
    $_SESSION['errors'] = ["Backup failed: " . $error->getMessage()];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

if (!is_file($backupPath)) {
    $_SESSION['errors'] = ["Backup file was not created."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $backupFilename . '"');
header('Content-Length: ' . filesize($backupPath));
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($backupPath);
exit();
