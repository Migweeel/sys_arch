<?php

date_default_timezone_set('Asia/Singapore');


$appEnvironment = trim((string) (getenv('APP_ENV') ?: 'local'));
$host = trim((string) (getenv('DB_HOST') ?: '127.0.0.1'));
$port = (int) (getenv('DB_PORT') ?: 3306);
$username = trim((string) (getenv('DB_USERNAME') ?: 'root'));
$password = (string) (getenv('DB_PASSWORD') ?: '');
$database = trim((string) (getenv('DB_NAME') ?: 'barangay_krus_na-ligas'));

if ($host === '' || $username === '' || $database === '') {
    $conn = null;
    return;
}

function ensureUserTableColumns($conn, $database) {
    $safeDatabase = mysqli_real_escape_string($conn, $database);
    $columnChecks = [
        'account_status' => "ALTER TABLE `users` ADD COLUMN `account_status` VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER `residentId`",
        'verification_proof' => "ALTER TABLE `users` ADD COLUMN `verification_proof` VARCHAR(255) DEFAULT NULL AFTER `account_status`",
        'verification_rejection_reason' => "ALTER TABLE `users` ADD COLUMN `verification_rejection_reason` TEXT DEFAULT NULL AFTER `verification_proof`",
        'updated_at' => "ALTER TABLE `users` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`"
    ];

    foreach ($columnChecks as $columnName => $alterSql) {
        $checkSql = "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$safeDatabase' AND TABLE_NAME = 'users' AND COLUMN_NAME = '$columnName'";
        $checkResult = mysqli_query($conn, $checkSql);

        if ($checkResult && $row = mysqli_fetch_assoc($checkResult)) {
            if ((int) $row['column_count'] === 0) {
                mysqli_query($conn, $alterSql);
            }
        }
    }
}

function ensureActivityLogsTable($conn, $database) {
    $safeDatabase = mysqli_real_escape_string($conn, $database);
    $checkSql = "SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$safeDatabase' AND TABLE_NAME = 'activity_logs'";
    $checkResult = mysqli_query($conn, $checkSql);

    if ($checkResult && $row = mysqli_fetch_assoc($checkResult)) {
        if ((int) $row['table_count'] === 0) {
            $createSql = "CREATE TABLE activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 0,
                username VARCHAR(100) NOT NULL DEFAULT '',
                role VARCHAR(50) NOT NULL DEFAULT 'resident',
                action VARCHAR(50) NOT NULL DEFAULT 'activity',
                description TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_activity_logs_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            mysqli_query($conn, $createSql);
        }
    }
}

function ensureDocumentTemplatesTable($conn, $database) {
    $safeDatabase = mysqli_real_escape_string($conn, $database);
    $checkSql = "SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$safeDatabase' AND TABLE_NAME = 'document_templates'";
    $checkResult = mysqli_query($conn, $checkSql);

    if ($checkResult && $row = mysqli_fetch_assoc($checkResult)) {
        if ((int) $row['table_count'] === 0) {
            $createSql = "CREATE TABLE document_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                category VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                usage_count INT NOT NULL DEFAULT 0,
                file_name VARCHAR(255) DEFAULT NULL,
                file_path VARCHAR(255) DEFAULT NULL,
                template_content TEXT DEFAULT NULL,
                requirements TEXT DEFAULT NULL,
                fill_up_fields TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            mysqli_query($conn, $createSql);
        }
    }
}

function ensureDocumentTemplateRequirementsColumn($conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'document_templates'
           AND COLUMN_NAME = 'requirements'
         LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException('Unable to check the document_templates requirements column: ' . mysqli_error($conn));
    }

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Unable to check the document_templates requirements column: ' . $error);
    }

    $result = mysqli_stmt_get_result($stmt);
    $columnExists = $result && mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$columnExists && !mysqli_query(
        $conn,
        "ALTER TABLE document_templates ADD COLUMN requirements TEXT DEFAULT NULL AFTER template_content"
    )) {
        throw new RuntimeException('Unable to add the document_templates requirements column: ' . mysqli_error($conn));
    }
}

function ensureDocumentTemplateFillUpFieldsColumn($conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'document_templates'
           AND COLUMN_NAME = 'fill_up_fields'
         LIMIT 1"
    );

    if (!$stmt || !mysqli_stmt_execute($stmt)) {
        $error = $stmt ? mysqli_stmt_error($stmt) : mysqli_error($conn);
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
        throw new RuntimeException('Unable to check the document_templates fill_up_fields column: ' . $error);
    }

    $result = mysqli_stmt_get_result($stmt);
    $columnExists = $result && mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$columnExists && !mysqli_query(
        $conn,
        "ALTER TABLE document_templates ADD COLUMN fill_up_fields TEXT DEFAULT NULL AFTER requirements"
    )) {
        throw new RuntimeException('Unable to add the document_templates fill_up_fields column: ' . mysqli_error($conn));
    }
}

function ensureDocumentRequestGeneratedFileColumn($conn, $database) {
    $safeDatabase = mysqli_real_escape_string($conn, $database);
    $checkSql = "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$safeDatabase' AND TABLE_NAME = 'document_requests' AND COLUMN_NAME = 'generated_file_path'";
    $checkResult = mysqli_query($conn, $checkSql);

    if ($checkResult && $row = mysqli_fetch_assoc($checkResult)) {
        if ((int) $row['column_count'] === 0) {
            mysqli_query($conn, "ALTER TABLE document_requests ADD COLUMN generated_file_path VARCHAR(255) DEFAULT NULL AFTER status");
        }
    }
}

function ensureDocumentRequestFillUpDataColumn($conn, $database) {
    $safeDatabase = mysqli_real_escape_string($conn, $database);
    $checkSql = "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$safeDatabase' AND TABLE_NAME = 'document_requests' AND COLUMN_NAME = 'fill_up_data'";
    $checkResult = mysqli_query($conn, $checkSql);

    if (!$checkResult) {
        throw new RuntimeException('Unable to check the document_requests fill_up_data column: ' . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($checkResult);
    if ($row && (int) $row['column_count'] === 0 && !mysqli_query(
        $conn,
        "ALTER TABLE document_requests ADD COLUMN fill_up_data LONGTEXT DEFAULT NULL AFTER purpose"
    )) {
        throw new RuntimeException('Unable to add the document_requests fill_up_data column: ' . mysqli_error($conn));
    }
}

function ensureDocumentRequestTemplateIdColumn($conn, $database) {
    $safeDatabase = mysqli_real_escape_string($conn, $database);
    $checkSql = "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$safeDatabase' AND TABLE_NAME = 'document_requests' AND COLUMN_NAME = 'template_id'";
    $checkResult = mysqli_query($conn, $checkSql);

    if (!$checkResult) {
        throw new RuntimeException('Unable to check the document_requests template_id column: ' . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($checkResult);
    if ($row && (int) $row['column_count'] === 0) {
        if (!mysqli_query($conn, 'ALTER TABLE document_requests ADD COLUMN template_id INT DEFAULT NULL')) {
            throw new RuntimeException('Unable to add the document_requests template_id column: ' . mysqli_error($conn));
        }

        if (!mysqli_query(
            $conn,
            "UPDATE document_requests AS r
             INNER JOIN document_templates AS t
               ON LOWER(TRIM(r.document_type)) = LOWER(TRIM(t.name))
             SET r.template_id = t.id
             WHERE r.template_id IS NULL"
        )) {
            throw new RuntimeException('Unable to link existing requests to document templates: ' . mysqli_error($conn));
        }
    }
}



// Try to connect to the database
$conn = mysqli_connect($host, $username, $password, $database, $port);

// If database doesn't exist, create it
if (!$conn) {
    if (getenv('DB_ALLOW_CREATE') !== '1') {
        $conn = null;
        return;
    }

    // Connect without specifying database
    $conn = mysqli_connect($host, $username, $password, '', $port);

    if (!$conn) {
        // Instead of dying, set conn to null and let the application handle it
        $conn = null;
    } else {
        // Create the database
        $sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (mysqli_query($conn, $sql)) {
            // Now connect to the newly created database
            mysqli_select_db($conn, $database);
            ensureUserTableColumns($conn, $database);
            ensureActivityLogsTable($conn, $database);
            ensureDocumentTemplatesTable($conn, $database);
            ensureDocumentTemplateRequirementsColumn($conn);
            ensureDocumentTemplateFillUpFieldsColumn($conn);
            ensureDocumentRequestTemplateIdColumn($conn, $database);
            ensureDocumentRequestGeneratedFileColumn($conn, $database);
            ensureDocumentRequestFillUpDataColumn($conn, $database);
        } else {
            // If database creation fails, set conn to null
            mysqli_close($conn);
            $conn = null;
        }
    }
} else {
    mysqli_set_charset($conn, 'utf8mb4');
    ensureUserTableColumns($conn, $database);
    ensureActivityLogsTable($conn, $database);
    ensureDocumentTemplatesTable($conn, $database);
    ensureDocumentTemplateRequirementsColumn($conn);
    ensureDocumentTemplateFillUpFieldsColumn($conn);
    ensureDocumentRequestTemplateIdColumn($conn, $database);
    ensureDocumentRequestGeneratedFileColumn($conn, $database);
    ensureDocumentRequestFillUpDataColumn($conn, $database);
}

if ($conn instanceof mysqli) {
    mysqli_query($conn, "SET time_zone = '+08:00'");
}
