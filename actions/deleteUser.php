<?php
require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';

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

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
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

if ($userId <= 0) {
    $_SESSION['errors'] = ["Invalid user account."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

if ($userId === (int) $_SESSION['user_id']) {
    $_SESSION['errors'] = ["You cannot delete your own admin account."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

$deletedUserLabel = userAccountLogLabel($conn, $userId);

$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE userId = ?");
if (!$stmt) {
    $_SESSION['errors'] = ["Failed to prepare user deletion."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $userId);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    logActivity($conn, (int) $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin', $_SESSION['role'] ?? 'admin', 'user_deleted', 'Admin deleted user account: ' . $deletedUserLabel . '.');
    $_SESSION['success'] = "User account deleted successfully.";
} else {
    $_SESSION['errors'] = ["Failed to delete user account."];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

header("Location: ../dashboard/admin.php?section=users");
exit();
