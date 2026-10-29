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

if (!class_exists('ZipArchive')) {
    $_SESSION['errors'] = ["ZIP support is not enabled in PHP. Enable the zip extension in XAMPP to create system backups."];
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
    $dump = "-- Krus na Ligas system backup\n";
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

function addProjectFilesToZip($zip, $projectRoot, $backupRoot) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $normalizedBackupRoot = rtrim(str_replace('\\', '/', realpath($backupRoot) ?: $backupRoot), '/');

    foreach ($iterator as $fileInfo) {
        $path = $fileInfo->getPathname();
        $normalizedPath = str_replace('\\', '/', $path);
        if ($normalizedBackupRoot !== '' && str_starts_with($normalizedPath, $normalizedBackupRoot . '/')) {
            continue;
        }

        $relativePath = substr($path, strlen($projectRoot) + 1);
        $zipPath = 'project/' . str_replace('\\', '/', $relativePath);

        if ($fileInfo->isDir()) {
            $zip->addEmptyDir($zipPath);
        } elseif ($fileInfo->isFile()) {
            $zip->addFile($path, $zipPath);
        }
    }
}

$projectRoot = realpath(dirname(__DIR__));
$backupRoot = $projectRoot . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupRoot) && !mkdir($backupRoot, 0775, true)) {
    $_SESSION['errors'] = ["Unable to create the backups folder."];
    header("Location: ../dashboard/admin.php?section=backup");
    exit();
}

$timestamp = date('Ymd_His');
$backupFilename = 'system_backup_' . $timestamp . '.zip';
$backupPath = $backupRoot . DIRECTORY_SEPARATOR . $backupFilename;
$zip = new ZipArchive();

try {
    if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create backup ZIP file.');
    }

    $databaseName = $database ?? 'krus_na_ligas_db';
    $zip->addFromString('database/' . $databaseName . '_' . $timestamp . '.sql', buildDatabaseDump($conn, $databaseName));
    addProjectFilesToZip($zip, $projectRoot, $backupRoot);
    $zip->close();

    logActivity(
        $conn,
        (int) $_SESSION['user_id'],
        $_SESSION['username'] ?? 'Admin',
        'admin',
        'system_backup_created',
        'Admin created system backup: ' . $backupFilename . '.'
    );
} catch (Throwable $error) {
    if ($zip instanceof ZipArchive) {
        $zip->close();
    }
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

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $backupFilename . '"');
header('Content-Length: ' . filesize($backupPath));
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($backupPath);
exit();
