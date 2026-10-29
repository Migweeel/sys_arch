<?php
require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ["Please login to continue."];
    header("Location: ../pages/loginPage.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'resident') {
    header("Location: ../dashboard/" . $_SESSION['role'] . ".php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard/resident-login.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['errors'] = ["Security token mismatch. Please try again."];
    header("Location: ../dashboard/resident-login.php");
    exit();
}

if ($conn === null) {
    $_SESSION['errors'] = ["Database connection failed. Please try again later."];
    header("Location: ../dashboard/resident-login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$proofFile = $_FILES['verification_proof'] ?? null;
$allowedProofTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/pdf' => 'pdf',
];
$errors = [];
$mimeType = '';

if (!$proofFile || ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $errors[] = "Please upload a verification proof.";
} elseif (($proofFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    $errors[] = "Verification proof upload failed. Please try again.";
} else {
    $maxProofSize = 5 * 1024 * 1024;
    if (($proofFile['size'] ?? 0) > $maxProofSize) {
        $errors[] = "Verification proof must not exceed 5MB.";
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($proofFile['tmp_name']);
    if (!isset($allowedProofTypes[$mimeType])) {
        $errors[] = "Verification proof must be a JPG, PNG, or PDF file.";
    }
}

$currentStmt = mysqli_prepare($conn, "SELECT account_status, verification_proof FROM users WHERE userId = ? LIMIT 1");
if (!$currentStmt) {
    $_SESSION['errors'] = ["Failed to prepare verification resubmission."];
    header("Location: ../dashboard/resident-login.php");
    exit();
}

mysqli_stmt_bind_param($currentStmt, "i", $userId);
mysqli_stmt_execute($currentStmt);
$currentResult = mysqli_stmt_get_result($currentStmt);
$currentUser = mysqli_fetch_assoc($currentResult);
mysqli_stmt_close($currentStmt);

if (!$currentUser) {
    $errors[] = "User account was not found.";
} elseif (strtolower($currentUser['account_status'] ?? '') !== 'rejected') {
    $errors[] = "You can upload a new proof only after your verification is rejected.";
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../dashboard/resident-login.php");
    exit();
}

$proofPath = null;

try {
    $uploadDir = __DIR__ . '/../uploads/verification_proofs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $extension = $allowedProofTypes[$mimeType];
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($proofFile['name'], PATHINFO_FILENAME));
    $fileName = uniqid('proof_', true) . '_' . ($safeName ?: 'verification') . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($proofFile['tmp_name'], $targetPath)) {
        throw new Exception("Failed to save verification proof.");
    }

    $proofPath = 'uploads/verification_proofs/' . $fileName;

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET account_status = 'pending',
             verification_proof = ?,
             verification_rejection_reason = NULL,
             updated_at = NOW()
         WHERE userId = ?"
    );

    if (!$stmt) {
        throw new Exception("Failed to prepare verification update.");
    }

    mysqli_stmt_bind_param($stmt, "si", $proofPath, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update verification proof.");
    }

    mysqli_stmt_close($stmt);

    $oldProof = trim((string) ($currentUser['verification_proof'] ?? ''));
    if ($oldProof !== '' && $oldProof !== $proofPath) {
        $oldPath = realpath(__DIR__ . '/../' . ltrim($oldProof, '/\\'));
        $proofRoot = realpath($uploadDir);
        $proofRootPrefix = $proofRoot ? rtrim($proofRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
        if ($oldPath && $proofRootPrefix !== '' && str_starts_with($oldPath, $proofRootPrefix) && is_file($oldPath)) {
            unlink($oldPath);
        }
    }

    $_SESSION['account_status'] = 'pending';
    $resubmittedUserLabel = userAccountLogLabel($conn, $userId);
    logActivity($conn, $userId, $_SESSION['username'] ?? 'Resident', $_SESSION['role'] ?? 'resident', 'verification_resubmitted', 'Resident resubmitted verification proof for account: ' . $resubmittedUserLabel . '.');
    $_SESSION['success'] = "Verification proof uploaded. Your account is now back for admin review.";
} catch (Exception $e) {
    if ($proofPath && file_exists(__DIR__ . '/../' . $proofPath)) {
        unlink(__DIR__ . '/../' . $proofPath);
    }

    $_SESSION['errors'] = ["Verification resubmission failed. Please try again."];
}

mysqli_close($conn);

header("Location: ../dashboard/resident-login.php");
exit();
