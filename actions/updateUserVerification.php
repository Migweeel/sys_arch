<?php
require_once '../auth/session.php';
require_once '../helpers/functions.php';
require_once '../config/db.php';

function sendVerificationEmail($email, $name, $status, $reason = '') {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $statusText = $status === 'verified' ? 'approved and verified' : 'rejected';
    $subject = "Barangay Krus na Ligas Account Verification";
    $message = "Hello {$name},\n\nYour Barangay Krus na Ligas account verification has been {$statusText}.\n\n";
    if ($status === 'verified') {
        $message .= "You may now submit barangay document requests through your resident dashboard.\n\n";
    } else {
        if ($reason !== '') {
            $message .= "Reason: {$reason}\n\n";
        }
        $message .= "Please open your profile and upload a new verification proof for review.\n\n";
    }
    $message .= "Thank you.";
    $headers = "From: Barangay Krus na Ligas <no-reply@krusnaligas.local>\r\n";

    return @mail($email, $subject, $message, $headers);
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ["Please login to continue."];
    header("Location: ../pages/loginPage.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard/" . $_SESSION['role'] . ".php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['errors'] = ["Security token mismatch. Please try again."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

if ($conn === null) {
    $_SESSION['errors'] = ["Database connection failed. Please try again later."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$status = strtolower(trim($_POST['status'] ?? ''));
$rejectionReason = trim($_POST['rejection_reason'] ?? '');
$validStatuses = ['pending', 'verified', 'rejected'];

if ($userId <= 0 || !in_array($status, $validStatuses, true)) {
    $_SESSION['errors'] = ["Invalid user verification update."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

if ($status === 'rejected' && $rejectionReason === '') {
    $_SESSION['errors'] = ["Please enter a reason before rejecting verification."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

if ($status !== 'rejected') {
    $rejectionReason = null;
}

$stmt = mysqli_prepare(
    $conn,
    "UPDATE users
     SET account_status = ?,
         verification_rejection_reason = ?,
         updated_at = NOW()
     WHERE userId = ?"
);

if (!$stmt) {
    $_SESSION['errors'] = ["Failed to prepare verification update."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

mysqli_stmt_bind_param($stmt, "ssi", $status, $rejectionReason, $userId);

if (mysqli_stmt_execute($stmt)) {
    $verifiedUserLabel = userAccountLogLabel($conn, $userId);
    $verificationDescription = 'Admin set verification status to ' . $status . ' for user account: ' . $verifiedUserLabel . '.';
    if ($status === 'rejected' && $rejectionReason !== null && $rejectionReason !== '') {
        $verificationDescription .= ' Reason: ' . $rejectionReason;
    }
    logActivity($conn, (int) $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin', $_SESSION['role'] ?? 'admin', 'verification_' . $status, $verificationDescription);

    $infoStmt = mysqli_prepare($conn, "SELECT u.username, res.firstName, res.lastName, res.emailAddress FROM users u LEFT JOIN residents res ON u.residentId = res.residentId WHERE u.userId = ? LIMIT 1");
    $emailSent = false;
    if ($infoStmt) {
        mysqli_stmt_bind_param($infoStmt, "i", $userId);
        mysqli_stmt_execute($infoStmt);
        $infoResult = mysqli_stmt_get_result($infoStmt);
        if ($info = mysqli_fetch_assoc($infoResult)) {
            $name = trim(($info['firstName'] ?? '') . ' ' . ($info['lastName'] ?? '')) ?: ($info['username'] ?? 'User');
            $email = $info['emailAddress'] ?? '';
            $emailSent = sendVerificationEmail($email, $name, $status, (string) $rejectionReason);
        }
        mysqli_stmt_close($infoStmt);
    }

    $_SESSION['success'] = "User verification status updated to " . ($status === 'verified' ? 'verified' : $status) . ".";
    if ($status !== 'pending') {
        $_SESSION['success'] .= $emailSent ? " Email notification sent." : " Email notification was not sent. Please check mail server settings.";
    }
} else {
    $_SESSION['errors'] = ["Failed to update verification status."];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

header("Location: ../dashboard/admin.php?section=users");
exit();
