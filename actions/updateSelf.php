<?php
require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ['Please login first.'];
    header('Location: ../pages/loginPage.php');
    exit();
}

$defaultRedirect = '../dashboard/admin.php';
if (($_SESSION['role'] ?? '') === 'staff') {
    $defaultRedirect = '../dashboard/staff.php';
} elseif (($_SESSION['role'] ?? '') === 'resident') {
    $defaultRedirect = '../dashboard/changePassword.php';
}

$redirectTo = isset($_POST['redirect_to']) ? trim($_POST['redirect_to']) : $defaultRedirect;
$allowedRedirects = [
    '../dashboard/admin.php',
    '../dashboard/staff.php',
    '../dashboard/resident-login.php',
    '../dashboard/changePassword.php',
];
if (!in_array($redirectTo, $allowedRedirects, true)) {
    $redirectTo = $defaultRedirect;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectTo);
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['errors'] = ['Invalid security session.'];
    header('Location: ' . $redirectTo);
    exit();
}

if ($conn === null) {
    $_SESSION['errors'] = ['Database connection failed. Please try again later.'];
    header('Location: ' . $redirectTo);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if ($username === '') {
    $_SESSION['errors'] = ['Username is required.'];
    header('Location: ' . $redirectTo);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT userId FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
if (!$stmt) {
    $_SESSION['errors'] = ['Database error.'];
    header('Location: ' . $redirectTo);
    exit();
}
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if ($row && (int) $row['userId'] !== $userId) {
    $_SESSION['errors'] = ['Username is already taken.'];
    header('Location: ' . $redirectTo);
    exit();
}

if ($newPassword !== '') {
    if ($currentPassword === '') {
        $_SESSION['errors'] = ['Current password is required to change password.'];
        header('Location: ' . $redirectTo);
        exit();
    }

    $curStmt = mysqli_prepare($conn, "SELECT password FROM users WHERE userId = ? LIMIT 1");
    if (!$curStmt) {
        $_SESSION['errors'] = ['Database error.'];
        header('Location: ' . $redirectTo);
        exit();
    }
    mysqli_stmt_bind_param($curStmt, 'i', $userId);
    mysqli_stmt_execute($curStmt);
    $curRes = mysqli_stmt_get_result($curStmt);
    $curRow = mysqli_fetch_assoc($curRes);
    mysqli_stmt_close($curStmt);

    if (!$curRow || !password_verify($currentPassword, $curRow['password'])) {
        $_SESSION['errors'] = ['Current password is incorrect.'];
        header('Location: ' . $redirectTo);
        exit();
    }

    if (strlen($newPassword) < 8) {
        $_SESSION['errors'] = ['New password must be at least 8 characters.'];
        header('Location: ' . $redirectTo);
        exit();
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
}

$fields = ['username = ?'];
$types = 's';
$values = [$username];
if (isset($newHash)) {
    $fields[] = 'password = ?';
    $types .= 's';
    $values[] = $newHash;
}

$values[] = $userId;
$types .= 'i';
$sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE userId = ?';

mysqli_begin_transaction($conn);
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    mysqli_rollback($conn);
    $_SESSION['errors'] = ['Database error.'];
    header('Location: ' . $redirectTo);
    exit();
}

mysqli_stmt_bind_param($stmt, $types, ...$values);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    mysqli_commit($conn);
    $profileUserLabel = userAccountLogLabel($conn, $userId);
    $profileDescription = 'User updated their account settings: ' . $profileUserLabel . '.';
    if (isset($newHash)) {
        $profileDescription .= ' Password was changed.';
    }
    logActivity($conn, $userId, $username, $_SESSION['role'] ?? 'resident', 'profile_updated', $profileDescription);
    $_SESSION['success'] = isset($newHash) ? 'Password updated successfully.' : 'Account settings updated.';
    $_SESSION['username'] = $username;
} else {
    mysqli_rollback($conn);
    $_SESSION['errors'] = ['Failed to update account settings.'];
}

header('Location: ' . $redirectTo);
exit();
