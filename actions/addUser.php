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

$username = clean($_POST['username'] ?? '');
$firstName = clean($_POST['first_name'] ?? '');
$lastName = clean($_POST['last_name'] ?? '');
$contactEmail = clean($_POST['contact_email'] ?? '');
$contactPhone = clean($_POST['contact_phone'] ?? '');
$role = strtolower(clean($_POST['role'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$errors = [];

if ($username === '' || $firstName === '' || $lastName === '' || $contactEmail === '' || $contactPhone === '' || $role === '' || $password === '' || $confirmPassword === '') {
    $errors[] = "Please fill in all required user fields.";
}

if ($firstName !== '' && $lastName !== '' && invalidNamePattern($firstName, $lastName)) {
    $errors[] = "Name contains invalid characters.";
}

if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if ($contactPhone !== '' && invalidPhoneNo($contactPhone)) {
    $errors[] = "Invalid phone number format.";
}

if (!in_array($role, ['admin', 'staff'], true)) {
    $errors[] = "Only admin and staff accounts can be added here.";
}

if (passwordNotMatch($password, $confirmPassword)) {
    $errors[] = "Passwords do not match.";
}

if (passwordLength($password)) {
    $errors[] = "Password must be at least 8 characters.";
}

if ($username !== '' && usernameTaken($conn, $username)) {
    $errors[] = "Username is already taken.";
}

$requiredColumns = ['first_name', 'last_name', 'contact_email', 'contact_phone'];
foreach ($requiredColumns as $requiredColumn) {
    $columnStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    if (!$columnStmt) {
        $errors[] = "Failed to check user profile columns.";
        break;
    }

    mysqli_stmt_bind_param($columnStmt, "s", $requiredColumn);
    mysqli_stmt_execute($columnStmt);
    $columnResult = mysqli_stmt_get_result($columnStmt);
    $columnRow = $columnResult ? mysqli_fetch_assoc($columnResult) : null;
    mysqli_stmt_close($columnStmt);

    if (!$columnRow || (int) $columnRow['column_count'] === 0) {
        $errors[] = "Please add the admin/staff profile columns to the users table before creating accounts.";
        break;
    }
}

if (empty($errors) && $contactEmail !== '') {
    $emailStmt = mysqli_prepare($conn, "SELECT userId FROM users WHERE LOWER(contact_email) = LOWER(?) LIMIT 1");
    if ($emailStmt) {
        mysqli_stmt_bind_param($emailStmt, "s", $contactEmail);
        mysqli_stmt_execute($emailStmt);
        $emailResult = mysqli_stmt_get_result($emailStmt);
        if (mysqli_fetch_assoc($emailResult)) {
            $errors[] = "Email address is already used by another admin or staff account.";
        }
        mysqli_stmt_close($emailStmt);
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$status = 'verified';
$stmt = mysqli_prepare($conn, "INSERT INTO users (username, first_name, last_name, contact_email, contact_phone, password, role, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    $_SESSION['errors'] = ["Failed to prepare user creation."];
    header("Location: ../dashboard/admin.php?section=users");
    exit();
}

mysqli_stmt_bind_param($stmt, "ssssssss", $username, $firstName, $lastName, $contactEmail, $contactPhone, $hashedPassword, $role, $status);

if (mysqli_stmt_execute($stmt)) {
    $createdUserId = (int) mysqli_insert_id($conn);
    $createdUserLabel = $createdUserId > 0
        ? userAccountLogLabel($conn, $createdUserId)
        : trim($firstName . ' ' . $lastName) . ' (username: ' . $username . ', role: ' . ucfirst($role) . ')';
    logActivity($conn, (int) $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin', $_SESSION['role'] ?? 'admin', 'user_created', 'Admin created user account: ' . $createdUserLabel . '.');
    $_SESSION['success'] = ucfirst($role) . " account created successfully.";
} else {
    $_SESSION['errors'] = ["Failed to create user account."];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

header("Location: ../dashboard/admin.php?section=users");
exit();
