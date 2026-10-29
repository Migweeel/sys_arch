<?php
require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ['Please login first.'];
    header('Location: ../pages/loginPage.php');
    exit();
}

if (($_SESSION['role'] ?? '') !== 'resident') {
    header('Location: ../dashboard/' . ($_SESSION['role'] ?? 'resident') . '.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard/resident-login.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['errors'] = ['Invalid security session.'];
    header('Location: ../dashboard/resident-login.php');
    exit();
}

if ($conn === null) {
    $_SESSION['errors'] = ['Database connection failed. Please try again later.'];
    header('Location: ../dashboard/resident-login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$currentUserStmt = mysqli_prepare($conn, "SELECT account_status FROM users WHERE userId = ? AND role = 'resident' LIMIT 1");
if (!$currentUserStmt) {
    $_SESSION['errors'] = ['Failed to verify account status.'];
    header('Location: ../dashboard/resident-login.php');
    exit();
}
mysqli_stmt_bind_param($currentUserStmt, 'i', $userId);
mysqli_stmt_execute($currentUserStmt);
$currentUserResult = mysqli_stmt_get_result($currentUserStmt);
$currentUser = mysqli_fetch_assoc($currentUserResult);
mysqli_stmt_close($currentUserStmt);

$currentAccountStatus = strtolower((string) ($currentUser['account_status'] ?? ''));
$requiresReverification = $currentAccountStatus === 'verified';
$verificationProofFile = $_FILES['verification_proof'] ?? null;
$firstName = trim(strip_tags((string) ($_POST['first_name'] ?? '')));
$lastName = trim(strip_tags((string) ($_POST['last_name'] ?? '')));
$middleName = trim(strip_tags((string) ($_POST['middle_name'] ?? '')));
$suffix = trim(strip_tags((string) ($_POST['suffix'] ?? '')));
$dateOfBirth = trim((string) ($_POST['date_of_birth'] ?? ''));
$sex = trim((string) ($_POST['sex'] ?? ''));
$civilStatus = trim((string) ($_POST['civil_status'] ?? ''));
$fullAddress = trim(strip_tags((string) ($_POST['full_address'] ?? '')));
$phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
$emailAddress = trim((string) ($_POST['email_address'] ?? ''));

$errors = [];

if ($firstName === '' || $lastName === '' || $dateOfBirth === '' || $sex === '' || $civilStatus === '' || $fullAddress === '') {
    $errors[] = 'Please fill in all required profile fields.';
}

if (invalidNamePattern($firstName, $lastName)) {
    $errors[] = 'Name contains invalid characters.';
}

if ($middleName !== '' && !preg_match('/^[a-zA-Z\s.]+$/', $middleName)) {
    $errors[] = 'Middle name contains invalid characters.';
}

if ($dateOfBirth !== '' && invalidBirthdate($dateOfBirth)) {
    $errors[] = 'Please enter a valid birthdate.';
}

if (invalidSex($sex)) {
    $errors[] = 'Please select a valid sex.';
}

if (invalidCivilStatus($civilStatus)) {
    $errors[] = 'Please select a valid civil status.';
}

if ($phoneNumber !== '' && invalidPhoneNo($phoneNumber)) {
    $errors[] = 'Phone number must follow the 09XXXXXXXXX format.';
}

if ($emailAddress !== '' && !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (!$currentUser) {
    $errors[] = 'Resident account was not found.';
} elseif ($requiresReverification) {
    $allowedProofTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    if (!$verificationProofFile || ($verificationProofFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Upload new verification proof before updating a verified profile.';
    } elseif (($verificationProofFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'Verification proof upload failed. Please try again.';
    } else {
        if (($verificationProofFile['size'] ?? 0) > 5 * 1024 * 1024) {
            $errors[] = 'Verification proof must not exceed 5MB.';
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $verificationMimeType = $fileInfo->file($verificationProofFile['tmp_name']);
        if (!isset($allowedProofTypes[$verificationMimeType])) {
            $errors[] = 'Verification proof must be a JPG, PNG, or PDF file.';
        }
    }
}

$residentStmt = mysqli_prepare($conn, "SELECT residentId FROM users WHERE userId = ? AND role = 'resident' LIMIT 1");
if (!$residentStmt) {
    $_SESSION['errors'] = ['Database error.'];
    header('Location: ../dashboard/resident-login.php');
    exit();
}
mysqli_stmt_bind_param($residentStmt, 'i', $userId);
mysqli_stmt_execute($residentStmt);
$residentResult = mysqli_stmt_get_result($residentStmt);
$residentRow = mysqli_fetch_assoc($residentResult);
mysqli_stmt_close($residentStmt);

$residentId = (int) ($residentRow['residentId'] ?? 0);
if ($residentId <= 0) {
    $errors[] = 'Resident profile was not found.';
}

if ($residentId > 0 && $emailAddress !== '') {
    $emailStmt = mysqli_prepare($conn, "SELECT residentId FROM residents WHERE LOWER(emailAddress) = LOWER(?) AND residentId <> ? LIMIT 1");
    if ($emailStmt) {
        mysqli_stmt_bind_param($emailStmt, 'si', $emailAddress, $residentId);
        mysqli_stmt_execute($emailStmt);
        $emailResult = mysqli_stmt_get_result($emailStmt);
        if (mysqli_fetch_assoc($emailResult)) {
            $errors[] = 'Email address is already used by another resident.';
        }
        mysqli_stmt_close($emailStmt);
    }
}

if ($residentId > 0 && $phoneNumber !== '') {
    $phoneStmt = mysqli_prepare($conn, "SELECT residentId FROM residents WHERE phoneNumber = ? AND residentId <> ? LIMIT 1");
    if ($phoneStmt) {
        mysqli_stmt_bind_param($phoneStmt, 'si', $phoneNumber, $residentId);
        mysqli_stmt_execute($phoneStmt);
        $phoneResult = mysqli_stmt_get_result($phoneStmt);
        if (mysqli_fetch_assoc($phoneResult)) {
            $errors[] = 'Phone number is already used by another resident.';
        }
        mysqli_stmt_close($phoneStmt);
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: ../dashboard/resident-login.php');
    exit();
}

$middleNameValue = $middleName !== '' ? $middleName : null;
$suffixValue = $suffix !== '' ? $suffix : null;
$phoneValue = $phoneNumber !== '' ? $phoneNumber : null;
$emailValue = $emailAddress !== '' ? $emailAddress : null;
$verificationProofPath = null;

if ($requiresReverification) {
    $uploadDir = __DIR__ . '/../uploads/verification_proofs/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        $_SESSION['errors'] = ['Unable to prepare verification upload.'];
        header('Location: ../dashboard/resident-login.php');
        exit();
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($verificationProofFile['name'], PATHINFO_FILENAME));
    $verificationProofPath = 'uploads/verification_proofs/' . uniqid('proof_', true) . '_' . ($safeName ?: 'verification') . '.' . $allowedProofTypes[$verificationMimeType];
    if (!move_uploaded_file($verificationProofFile['tmp_name'], __DIR__ . '/../' . $verificationProofPath)) {
        $_SESSION['errors'] = ['Failed to save verification proof.'];
        header('Location: ../dashboard/resident-login.php');
        exit();
    }
}

$stmt = mysqli_prepare(
    $conn,
    "UPDATE residents
     SET firstName = ?, lastName = ?, middleName = ?, suffix = ?, dateOfBirth = ?, sex = ?, civilStatus = ?, fullAddress = ?, phoneNumber = ?, emailAddress = ?
     WHERE residentId = ?"
);
if (!$stmt) {
    $_SESSION['errors'] = ['Failed to prepare profile update.'];
    header('Location: ../dashboard/resident-login.php');
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    'ssssssssssi',
    $firstName,
    $lastName,
    $middleNameValue,
    $suffixValue,
    $dateOfBirth,
    $sex,
    $civilStatus,
    $fullAddress,
    $phoneValue,
    $emailValue,
    $residentId
);

mysqli_begin_transaction($conn);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['first_name'] = $firstName;
    if ($requiresReverification) {
        $verificationStmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET account_status = 'pending',
                 verification_proof = ?,
                 verification_rejection_reason = NULL,
                 updated_at = NOW()
             WHERE userId = ?"
        );

        if (!$verificationStmt) {
            mysqli_rollback($conn);
            if ($verificationProofPath && is_file(__DIR__ . '/../' . $verificationProofPath)) {
                unlink(__DIR__ . '/../' . $verificationProofPath);
            }
            $_SESSION['errors'] = ['Failed to prepare re-verification update.'];
        } else {
            mysqli_stmt_bind_param($verificationStmt, 'si', $verificationProofPath, $userId);
            if (mysqli_stmt_execute($verificationStmt)) {
                mysqli_commit($conn);
                $_SESSION['account_status'] = 'pending';
                logActivity($conn, $userId, $_SESSION['username'] ?? 'Resident', 'resident', 'profile_updated_reverification', 'Resident updated their profile and resubmitted verification.');
                $_SESSION['success'] = 'Profile updated and submitted for re-verification.';
            } else {
                mysqli_rollback($conn);
                if ($verificationProofPath && is_file(__DIR__ . '/../' . $verificationProofPath)) {
                    unlink(__DIR__ . '/../' . $verificationProofPath);
                }
                $_SESSION['errors'] = ['Failed to submit profile for re-verification.'];
            }
            mysqli_stmt_close($verificationStmt);
        }
    } else {
        mysqli_commit($conn);
        logActivity($conn, $userId, $_SESSION['username'] ?? 'Resident', 'resident', 'profile_updated', 'Resident updated their profile information.');
        $_SESSION['success'] = 'Profile updated successfully.';
    }
} else {
    mysqli_rollback($conn);
    if ($verificationProofPath && is_file(__DIR__ . '/../' . $verificationProofPath)) {
        unlink(__DIR__ . '/../' . $verificationProofPath);
    }
    $_SESSION['errors'] = ['Failed to update profile.'];
}

mysqli_stmt_close($stmt);
header('Location: ../dashboard/resident-login.php');
exit();
