<?php
require_once '../auth/session.php';
require_once '../helpers/functions.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ['Please login to continue.'];
    header('Location: ../pages/loginPage.php');
    exit();
}

if (!in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    $fallbackDashboard = '../dashboard/' . ($_SESSION['role'] ?? 'login') . '.php';
    header('Location: ' . $fallbackDashboard);
    exit();
}

$isStaff = ($_SESSION['role'] ?? '') === 'staff';
$dashboardPage = $isStaff ? '../dashboard/staff.php' : '../dashboard/admin.php';
$templateRedirect = $dashboardPage . ($isStaff ? '?view=templates' : '?section=templates');

if ($conn === null) {
    $_SESSION['errors'] = ['Database connection is unavailable. Please try again later.'];
    header('Location: ' . $templateRedirect);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $dashboardPage);
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
    $_SESSION['errors'] = ['Security token mismatch. Please try again.'];
    header('Location: ' . $templateRedirect);
    exit();
}

$action = strtolower(trim($_POST['action'] ?? 'create'));
$templateId = isset($_POST['template_id']) ? (int) $_POST['template_id'] : 0;
$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$fee = (float) ($_POST['fee'] ?? 0);
$templateContent = sanitizeTemplateHtml($_POST['template_content'] ?? '');
$requirements = trim($_POST['requirements'] ?? '');
$submittedFillUpFields = $_POST['fill_up_fields'] ?? '';
if (!is_array($submittedFillUpFields)) {
    $submittedFillUpFields = preg_split('/\r\n|\r|\n/', (string) $submittedFillUpFields);
}
$submittedFillUpFields = array_map(static function ($field) {
    return is_scalar($field) ? trim((string) $field) : '';
}, $submittedFillUpFields);
$fillUpFields = implode("\n", array_values(array_unique(array_filter($submittedFillUpFields, static function ($field) {
    return $field !== '';
}))));
$supportsTemplateRequirements = databaseColumnExists($conn, 'document_templates', 'requirements');
$supportsTemplateFillUpFields = databaseColumnExists($conn, 'document_templates', 'fill_up_fields');

if ($action !== 'delete' && ($name === '' || $category === '')) {
    $_SESSION['errors'] = ['Template name and category are required.'];
    header('Location: ' . $templateRedirect);
    exit();
}

$uploadPath = '';
$fileName = '';
if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/templates/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $tmpName = $_FILES['template_file']['tmp_name'];
    $originalName = basename($_FILES['template_file']['name']);
    $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExts = ['doc', 'docx', 'pdf', 'txt'];
    if (!in_array($fileExt, $allowedExts, true)) {
        $_SESSION['errors'] = ['Only DOC, DOCX, PDF, and TXT files are allowed.'];
        header('Location: ' . $templateRedirect);
        exit();
    }

    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $fileExt;
    $uploadPath = 'uploads/templates/' . $fileName;
    $destination = $uploadDir . $fileName;
    if (!move_uploaded_file($tmpName, $destination)) {
        $_SESSION['errors'] = ['Failed to upload template file.'];
        header('Location: ' . $templateRedirect);
        exit();
    }
}

if ($action === 'delete' && $templateId > 0) {
    $templateLabel = 'document template #' . $templateId;
    $selectStmt = mysqli_prepare($conn, 'SELECT name, category, file_name, file_path FROM document_templates WHERE id = ? LIMIT 1');
    if ($selectStmt) {
        mysqli_stmt_bind_param($selectStmt, 'i', $templateId);
        mysqli_stmt_execute($selectStmt);
        $selectResult = mysqli_stmt_get_result($selectStmt);
        if ($row = mysqli_fetch_assoc($selectResult)) {
            $templateName = trim((string) ($row['name'] ?? ''));
            $templateCategory = trim((string) ($row['category'] ?? ''));
            if ($templateName !== '') {
                $templateLabel = $templateName;
                if ($templateCategory !== '') {
                    $templateLabel .= ' (' . $templateCategory . ')';
                }
            }

            $candidatePaths = [];
            if (!empty($row['file_path'])) {
                $candidatePaths[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $row['file_path']), DIRECTORY_SEPARATOR);
                $candidatePaths[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . basename($row['file_path']);
            }
            if (!empty($row['file_name'])) {
                $candidatePaths[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $row['file_name'];
            }

            $seenPaths = [];
            foreach ($candidatePaths as $candidatePath) {
                $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $candidatePath);
                if ($normalizedPath === '' || in_array($normalizedPath, $seenPaths, true)) {
                    continue;
                }
                $seenPaths[] = $normalizedPath;
                if (is_file($normalizedPath)) {
                    unlink($normalizedPath);
                }
            }
        }
        mysqli_stmt_close($selectStmt);
    }

    $deleteStmt = mysqli_prepare($conn, 'DELETE FROM document_templates WHERE id = ?');
    if ($deleteStmt) {
        mysqli_stmt_bind_param($deleteStmt, 'i', $templateId);
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);
    }

    $actorLabel = ($_SESSION['role'] === 'staff') ? 'Staff' : 'Admin';
    logActivity($conn, (int) $_SESSION['user_id'], $_SESSION['username'] ?? $actorLabel, $_SESSION['role'] ?? 'admin', 'template_deleted', $actorLabel . ' deleted document template: ' . $templateLabel . '.');
    $_SESSION['success'] = 'Template deleted successfully.';
    header('Location: ' . $templateRedirect);
    exit();
}

if ($action === 'edit' && $templateId > 0) {
    $fields = [];
    $params = [];
    $types = '';

    $fields[] = 'name = ?';
    $params[] = $name;
    $types .= 's';

    $fields[] = 'category = ?';
    $params[] = $category;
    $types .= 's';

    $fields[] = 'description = ?';
    $params[] = $description;
    $types .= 's';

    $fields[] = 'fee = ?';
    $params[] = number_format($fee, 2, '.', '');
    $types .= 's';

    $fields[] = 'template_content = ?';
    $params[] = $templateContent;
    $types .= 's';

    if ($supportsTemplateRequirements) {
        $fields[] = 'requirements = ?';
        $params[] = $requirements;
        $types .= 's';
    }

    if ($supportsTemplateFillUpFields) {
        $fields[] = 'fill_up_fields = ?';
        $params[] = $fillUpFields;
        $types .= 's';
    }

    if ($uploadPath !== '') {
        $fields[] = 'file_name = ?';
        $params[] = $fileName;
        $types .= 's';

        $fields[] = 'file_path = ?';
        $params[] = $uploadPath;
        $types .= 's';
    }

    $params[] = $templateId;
    $types .= 'i';

    $sql = 'UPDATE document_templates SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $requestNameStmt = mysqli_prepare($conn, 'UPDATE document_requests SET document_type = ? WHERE template_id = ?');
        if ($requestNameStmt) {
            mysqli_stmt_bind_param($requestNameStmt, 'si', $name, $templateId);
            mysqli_stmt_execute($requestNameStmt);
            mysqli_stmt_close($requestNameStmt);
        }
        regenerateGeneratedDocumentsForTemplate($conn, $templateId, dirname(__DIR__) . '/uploads/generated_documents');
        $actorLabel = ($_SESSION['role'] === 'staff') ? 'Staff' : 'Admin';
        logActivity($conn, (int) $_SESSION['user_id'], $_SESSION['username'] ?? $actorLabel, $_SESSION['role'] ?? 'admin', 'template_updated', $actorLabel . ' updated document template: ' . $name . '.');
        $_SESSION['success'] = 'Template updated successfully.';
    } else {
        $_SESSION['errors'] = ['Failed to update template.'];
    }
} else {
    $columns = ['name', 'category', 'description', 'fee', 'file_name', 'file_path', 'template_content'];
    $placeholders = ['?', '?', '?', '?', '?', '?', '?'];
    $params = [$name, $category, $description, $fee, $fileName, $uploadPath, $templateContent];
    $types = 'sssssss';

    if ($supportsTemplateRequirements) {
        $columns[] = 'requirements';
        $placeholders[] = '?';
        $params[] = $requirements;
        $types .= 's';
    }

    if ($supportsTemplateFillUpFields) {
        $columns[] = 'fill_up_fields';
        $placeholders[] = '?';
        $params[] = $fillUpFields;
        $types .= 's';
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO document_templates (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $actorLabel = ($_SESSION['role'] === 'staff') ? 'Staff' : 'Admin';
        logActivity($conn, (int) $_SESSION['user_id'], $_SESSION['username'] ?? $actorLabel, $_SESSION['role'] ?? 'admin', 'template_created', $actorLabel . ' created document template: ' . $name . '.');
        $_SESSION['success'] = 'Template created successfully.';
    } else {
        $_SESSION['errors'] = ['Failed to create template.'];
    }
}

header('Location: ' . $templateRedirect);
exit();
