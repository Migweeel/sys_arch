<?php
require_once '../auth/session.php';
require_once '../helpers/functions.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ["Please login to access the dashboard."];
    header("Location: ../pages/loginPage.php");
    exit();
}

if ($_SESSION['role'] !== 'resident') {
    header("Location: ../pages/loginPage.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$accountStatus = 'pending';
$residentProfile = [
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'suffix' => '',
    'birthdate' => '',
    'sex' => '',
    'civil_status' => '',
    'address' => '',
];
if ($conn !== null) {
    $statusStmt = mysqli_prepare(
        $conn,
        "SELECT u.account_status, r.firstName, r.middleName, r.lastName, r.suffix,
                r.dateOfBirth, r.sex, r.civilStatus, r.fullAddress
         FROM users u
         LEFT JOIN residents r ON r.residentId = u.residentId
         WHERE u.userId = ?
         LIMIT 1"
    );
    if ($statusStmt) {
        mysqli_stmt_bind_param($statusStmt, "i", $userId);
        mysqli_stmt_execute($statusStmt);
        $statusResult = mysqli_stmt_get_result($statusStmt);
        if ($statusRow = mysqli_fetch_assoc($statusResult)) {
            $accountStatus = strtolower($statusRow['account_status'] ?? 'pending');
            $_SESSION['account_status'] = $accountStatus;
            $residentProfile = [
                'first_name' => $statusRow['firstName'] ?? '',
                'middle_name' => $statusRow['middleName'] ?? '',
                'last_name' => $statusRow['lastName'] ?? '',
                'suffix' => $statusRow['suffix'] ?? '',
                'birthdate' => $statusRow['dateOfBirth'] ?? '',
                'sex' => $statusRow['sex'] ?? '',
                'civil_status' => $statusRow['civilStatus'] ?? '',
                'address' => $statusRow['fullAddress'] ?? '',
            ];
        }
        mysqli_stmt_close($statusStmt);
    }
}

$isVerified = $accountStatus === 'verified';
$documentTemplates = getDocumentTemplates($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
        $_SESSION['errors'][] = "Security token mismatch. Please try again.";
        header("Location: resident.php");
        exit();
    }
    if (!$isVerified) {
        $_SESSION['errors'][] = "Your account is still for verification. You can request documents once an admin verifies your account.";
        header("Location: resident.php");
        exit();
    }

    // Check database connection
    if ($conn === null) {
        $_SESSION['errors'][] = "Database connection failed. Please try again later.";
        header("Location: request-history.php");
        exit();
    }

    // At this point, $conn is guaranteed to be valid
    /** @var mysqli $conn */

    // Ensure $conn is not null before proceeding (for static analysis)
    if ($conn === null) {
        $_SESSION['errors'][] = "Unexpected database connection error.";
        header("Location: request-history.php");
        exit();
    }

    $templateId = isset($_POST['document_type']) ? (int) $_POST['document_type'] : 0;
    $selectedTemplate = getDocumentTemplateById($conn, $templateId);
    if (!$selectedTemplate) {
        $_SESSION['errors'][] = "Please select a valid document template.";
        header("Location: resident.php");
        exit();
    }

    $docType = trim((string) ($selectedTemplate['name'] ?? ''));
    if ($docType === '') {
        $_SESSION['errors'][] = "Selected document template is missing a document name.";
        header("Location: resident.php");
        exit();
    }

    $purpose = trim($_POST['purpose']);
    if ($purpose === '') {
        $_SESSION['errors'][] = "Please enter the purpose of your request.";
        header("Location: resident.php");
        exit();
    }

    $onBehalf = ($_POST['request_mode'] ?? 'self') === 'on_behalf';
    $fillUpFields = parseDocumentTemplateFillUpFields($selectedTemplate['fill_up_fields'] ?? '');
    $submittedFillUp = is_array($_POST['fill_up'] ?? null) ? $_POST['fill_up'] : [];
    $fillUpData = [];
    foreach ($fillUpFields as $index => $fieldLabel) {
        $profileValue = $onBehalf ? '' : residentProfileValueForTemplateField($fieldLabel, $residentProfile);
        $value = $profileValue !== ''
            ? $profileValue
            : trim((string) ($submittedFillUp[$index] ?? ''));
        if ($value === '') {
            $_SESSION['errors'][] = "Please complete the \"$fieldLabel\" field.";
            header("Location: resident.php");
            exit();
        }
        $fillUpData[$fieldLabel] = $value;
    }
    if ($onBehalf) {
        $fillUpData['Requesting on behalf of someone else'] = 'Yes';
    }
    $fillUpDataJson = json_encode($fillUpData, JSON_UNESCAPED_UNICODE);
    if ($fillUpDataJson === false) {
        $_SESSION['errors'][] = "Unable to process the additional request details.";
        header("Location: resident.php");
        exit();
    }

    /* ================= FILE UPLOAD ================= */

    $uploadDir = __DIR__ . '/../uploads/requirements/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadedFiles = [];

    if (!empty($_FILES['requirements'])) {
        foreach ($_FILES['requirements']['name'] as $key => $name) {

            if ($_FILES['requirements']['error'][$key] === UPLOAD_ERR_OK) {

                $tmpName = $_FILES['requirements']['tmp_name'][$key];
                $size = $_FILES['requirements']['size'][$key];

                if ($size > 25 * 1024 * 1024) {
                    $_SESSION['errors'][] = "File {$name} exceeds 25MB limit.";
                    continue;
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpName);
                $allowedRequirementTypes = [
                    'application/pdf' => 'pdf',
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                ];
                if (!isset($allowedRequirementTypes[$mime])) {
                    $_SESSION['errors'][] = "File {$name} has an unsupported file type.";
                    continue;
                }
                $safeName = bin2hex(random_bytes(16)) . '.' . $allowedRequirementTypes[$mime];
                $destination = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $uploadedFiles[] = [
                        'file_name' => $name,
                        'file_path' => 'uploads/requirements/' . $safeName
                    ];
                }
            }
        }
    }

    /* ================= SAVE REQUEST TO DATABASE ================= */

    // Double-check database connection before database operations
    if ($conn === null) {
        $_SESSION['errors'][] = "Database connection lost. Please try again later.";
        header("Location: request-history.php");
        exit();
    }

    // At this point, $conn is guaranteed to be valid
    /** @var mysqli $conn */

    mysqli_begin_transaction($conn);

    $sql = "INSERT INTO document_requests (request_number, user_id, template_id, document_type, purpose, fill_up_data, status) VALUES (NULL, ?, ?, ?, ?, ?, 'pending')";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        $_SESSION['errors'][] = "Database error: " . mysqli_error($conn);
        mysqli_rollback($conn);
        header("Location: request-history.php");
        exit();
    }

    mysqli_stmt_bind_param($stmt, "iisss", $userId, $templateId, $docType, $purpose, $fillUpDataJson);

    if (mysqli_stmt_execute($stmt)) {
        $requestId = mysqli_insert_id($conn);
        $requestNumber = 'REQ-' . date('Y') . '-' . str_pad((string) $requestId, 3, '0', STR_PAD_LEFT);

        $numberStmt = mysqli_prepare($conn, "UPDATE document_requests SET request_number = ? WHERE request_id = ?");
        if (!$numberStmt) {
            $_SESSION['errors'][] = "Database error: " . mysqli_error($conn);
            mysqli_rollback($conn);
            header("Location: request-history.php");
            exit();
        }

        mysqli_stmt_bind_param($numberStmt, "si", $requestNumber, $requestId);
        if (!mysqli_stmt_execute($numberStmt)) {
            $_SESSION['errors'][] = "Failed to assign request number.";
            mysqli_rollback($conn);
            header("Location: request-history.php");
            exit();
        }
        mysqli_stmt_close($numberStmt);

        $fileStmt = mysqli_prepare($conn, "INSERT INTO document_request_files (request_id, file_name, file_path) VALUES (?, ?, ?)");
        if (!$fileStmt) {
            $_SESSION['errors'][] = "Database error: " . mysqli_error($conn);
            mysqli_rollback($conn);
            header("Location: request-history.php");
            exit();
        }

        foreach ($uploadedFiles as $uploadedFile) {
            mysqli_stmt_bind_param($fileStmt, "iss", $requestId, $uploadedFile['file_name'], $uploadedFile['file_path']);
            if (!mysqli_stmt_execute($fileStmt)) {
                $_SESSION['errors'][] = "Failed to save uploaded requirement details.";
                mysqli_rollback($conn);
                header("Location: request-history.php");
                exit();
            }
        }

        mysqli_stmt_close($fileStmt);

        $usageStmt = mysqli_prepare($conn, "UPDATE document_templates SET usage_count = usage_count + 1 WHERE id = ?");
        if ($usageStmt) {
            mysqli_stmt_bind_param($usageStmt, "i", $templateId);
            mysqli_stmt_execute($usageStmt);
            mysqli_stmt_close($usageStmt);
        }

        mysqli_commit($conn);
        $requestingUserLabel = userAccountLogLabel($conn, $userId);
        logActivity($conn, $userId, $_SESSION['username'] ?? 'Resident', $_SESSION['role'] ?? 'resident', 'document_requested', 'Resident submitted document request: ' . $requestNumber . ' (' . $docType . ') for account: ' . $requestingUserLabel . '.');
        $_SESSION['success'] = "Document request submitted successfully. Track its status in Request History.";
    } else {
        $_SESSION['errors'][] = "Failed to save request: " . mysqli_error($conn);
        mysqli_rollback($conn);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    header("Location: request-history.php");
    exit();
}

$firstName = $_SESSION['first_name'] ?? 'Resident';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Document</title>
<link rel="stylesheet" href="../css/resident.css?v=20261012-mobile-shell-5">
<link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

<?php require_once __DIR__ . '/nav.php'; ?>
<?php displayFlashMessages(); ?>

<section id="section-main-content">
<div class="container">
<div class="card">

<div class="card-header">
  <div class="card-title-group">
    <i class="bi bi-file-richtext"></i>
    <div>
      <h2 class="card-title">Request a Document</h2>
      <p class="card-subtitle">Fill out the form to request barangay documents</p>
    </div>
  </div>
</div>

<?php if (!$isVerified): ?>
<div class="verification-alert <?php echo htmlspecialchars($accountStatus); ?>">
  <i class="bi bi-shield-exclamation"></i>
  <div>
    <strong>Account Status: <?php echo htmlspecialchars(ucfirst($accountStatus)); ?></strong>
    <p>
      <?php if ($accountStatus === 'rejected'): ?>
        Your account verification was rejected. Try to verify again of please contact the barangay office for assistance.
      <?php else: ?>
        Your account is for admin verification. You can login and view your profile, but document requests are locked until verified.
      <?php endif; ?>
    </p>
  </div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="request-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

<fieldset class="request-mode-picker">
    <legend>Request for</legend>
    <div class="request-mode-options">
        <label>
            <input type="radio" name="request_mode" value="self" checked>
            <span>Myself</span>
        </label>
        <label>
            <input type="radio" name="request_mode" value="on_behalf">
            <span>On behalf</span>
        </label>
    </div>
    <div class="request-mode-description">
        <strong>Request on Behalf</strong>
        <p>Submit a request for a family member or another person you are authorized to represent.</p>
    </div>
</fieldset>

<div class="form-group">
<label class="form-label" for="doc-type">Document Type *</label>
<div class="select-wrap">
<select id="doc-type" name="document_type" class="form-select" data-custom-select required <?php echo $isVerified ? '' : 'disabled'; ?>>
<option value="" disabled selected>Select document type</option>
<?php foreach ($documentTemplates as $template): ?>
<?php
  $templateId = (int) ($template['id'] ?? 0);
  $templateName = trim((string) ($template['name'] ?? ''));
  if ($templateId <= 0 || $templateName === '') {
      continue;
  }
  $templateKind = normalizeBarangayDocumentType($templateName);
  $requirements = parseDocumentTemplateRequirements($template['requirements'] ?? '');
  $requirementsJson = htmlspecialchars(json_encode($requirements), ENT_QUOTES, 'UTF-8');
    $fillUpFields = parseDocumentTemplateFillUpFields($template['fill_up_fields'] ?? '');
    $profileFillUpValues = [];
    foreach ($fillUpFields as $fillUpField) {
            $profileFillUpValues[] = residentProfileValueForTemplateField($fillUpField, $residentProfile);
    }
    $fillUpFieldsJson = htmlspecialchars(json_encode($fillUpFields), ENT_QUOTES, 'UTF-8');
    $profileFillUpValuesJson = htmlspecialchars(json_encode($profileFillUpValues), ENT_QUOTES, 'UTF-8');
?>
<option value="<?php echo $templateId; ?>" data-template-kind="<?php echo htmlspecialchars($templateKind); ?>" data-requirements="<?php echo $requirementsJson; ?>" data-fill-up-fields="<?php echo $fillUpFieldsJson; ?>" data-profile-fill-up-values="<?php echo $profileFillUpValuesJson; ?>">
  <?php echo htmlspecialchars($templateName); ?>
</option>
<?php endforeach; ?>
<?php if (empty($documentTemplates)): ?>
<option value="" disabled>No document templates available</option>
<?php endif; ?>
</select>
</div>
</div>

<div class="form-group">
<label class="form-label" for="request-purpose">Purpose *</label>
<textarea id="request-purpose" name="purpose" class="form-textarea" required></textarea>
</div>

<div id="requirementsContainer"></div>

<div id="fillUpContainer"></div>

<button type="submit" class="submit-btn" data-verified="<?php echo $isVerified ? '1' : '0'; ?>" disabled>
<i class="bi bi-send"></i> Submit Request
</button>

</form>
</div>
</div>
</section>

<script src="../script.js"></script>
<script src="../requirments.js"></script>
</body>
</html>
