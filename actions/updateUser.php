<?php
require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['errors'] = ['Unauthorized'];
    header('Location: ../pages/loginPage.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['errors'] = ['Invalid security session.'];
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

function actionUserColumnExists($conn, $columnName) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 's', $columnName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row && (int) $row['column_count'] > 0;
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$firstName = clean($_POST['first_name'] ?? '');
$lastName = clean($_POST['last_name'] ?? '');
$contactEmail = clean($_POST['contact_email'] ?? '');
$contactPhone = clean($_POST['contact_phone'] ?? '');
$password = $_POST['password'] ?? '';
$role = in_array($_POST['role'] ?? '', ['resident','staff','admin'], true) ? $_POST['role'] : 'resident';

if ($userId <= 0 || $username === '') {
    $_SESSION['errors'] = ['Invalid input.'];
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

$profileColumns = ['first_name', 'last_name', 'contact_email', 'contact_phone'];
$profileColumnsReady = true;
foreach ($profileColumns as $profileColumn) {
    if (!actionUserColumnExists($conn, $profileColumn)) {
        $profileColumnsReady = false;
        break;
    }
}

if (in_array($role, ['admin', 'staff'], true)) {
    if ($firstName === '' || $lastName === '' || $contactEmail === '' || $contactPhone === '') {
        $_SESSION['errors'] = ['Please fill in name, email, and phone for admin or staff accounts.'];
        header('Location: ../dashboard/admin.php?section=users');
        exit();
    }

    if (!$profileColumnsReady) {
        $_SESSION['errors'] = ['Please add the admin/staff profile columns to the users table before updating accounts.'];
        header('Location: ../dashboard/admin.php?section=users');
        exit();
    }
}

if ($firstName !== '' && $lastName !== '' && invalidNamePattern($firstName, $lastName)) {
    $_SESSION['errors'] = ['Name contains invalid characters.'];
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['errors'] = ['Please enter a valid email address.'];
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

if ($contactPhone !== '' && invalidPhoneNo($contactPhone)) {
    $_SESSION['errors'] = ['Invalid phone number format.'];
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

// Fetch current user to allow same username and update the right profile table.
$curStmt = mysqli_prepare($conn, "SELECT username, role, residentId FROM users WHERE userId = ? LIMIT 1");
mysqli_stmt_bind_param($curStmt, 'i', $userId);
mysqli_stmt_execute($curStmt);
$curRes = mysqli_stmt_get_result($curStmt);
$curRow = mysqli_fetch_assoc($curRes);
mysqli_stmt_close($curStmt);

if (!$curRow) {
    $_SESSION['errors'] = ['User not found.'];
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

$residentId = (int) ($curRow['residentId'] ?? 0);
$updatesResidentProfile = $residentId > 0;

if (strtolower($curRow['username']) !== strtolower($username) && usernameTaken($conn, $username)) {
    $_SESSION['errors'] = ['Username is already taken.'];
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

if ($updatesResidentProfile && $contactEmail !== '') {
    $residentEmailStmt = mysqli_prepare($conn, "SELECT residentId FROM residents WHERE LOWER(emailAddress) = LOWER(?) AND residentId <> ? LIMIT 1");
    if ($residentEmailStmt) {
        mysqli_stmt_bind_param($residentEmailStmt, 'si', $contactEmail, $residentId);
        mysqli_stmt_execute($residentEmailStmt);
        $residentEmailResult = mysqli_stmt_get_result($residentEmailStmt);
        if (mysqli_fetch_assoc($residentEmailResult)) {
            $_SESSION['errors'] = ['Email address is already used by another resident profile.'];
            mysqli_stmt_close($residentEmailStmt);
            header('Location: ../dashboard/admin.php?section=users');
            exit();
        }
        mysqli_stmt_close($residentEmailStmt);
    }
}

if ($updatesResidentProfile && $contactPhone !== '') {
    $residentPhoneStmt = mysqli_prepare($conn, "SELECT residentId FROM residents WHERE phoneNumber = ? AND residentId <> ? LIMIT 1");
    if ($residentPhoneStmt) {
        mysqli_stmt_bind_param($residentPhoneStmt, 'si', $contactPhone, $residentId);
        mysqli_stmt_execute($residentPhoneStmt);
        $residentPhoneResult = mysqli_stmt_get_result($residentPhoneStmt);
        if (mysqli_fetch_assoc($residentPhoneResult)) {
            $_SESSION['errors'] = ['Phone number is already used by another resident profile.'];
            mysqli_stmt_close($residentPhoneStmt);
            header('Location: ../dashboard/admin.php?section=users');
            exit();
        }
        mysqli_stmt_close($residentPhoneStmt);
    }
}

if (!$updatesResidentProfile && $profileColumnsReady && $contactEmail !== '') {
    $emailStmt = mysqli_prepare($conn, "SELECT userId FROM users WHERE LOWER(contact_email) = LOWER(?) AND userId <> ? LIMIT 1");
    if ($emailStmt) {
        mysqli_stmt_bind_param($emailStmt, 'si', $contactEmail, $userId);
        mysqli_stmt_execute($emailStmt);
        $emailResult = mysqli_stmt_get_result($emailStmt);
        if (mysqli_fetch_assoc($emailResult)) {
            $_SESSION['errors'] = ['Email address is already used by another admin or staff account.'];
            mysqli_stmt_close($emailStmt);
            header('Location: ../dashboard/admin.php?section=users');
            exit();
        }
        mysqli_stmt_close($emailStmt);
    }
}

$fields = [];
$types = '';
$values = [];

$fields[] = 'username = ?'; $types .= 's'; $values[] = $username;
$fields[] = 'role = ?'; $types .= 's'; $values[] = $role;

if (!$updatesResidentProfile && $profileColumnsReady) {
    $fields[] = 'first_name = ?'; $types .= 's'; $values[] = $firstName !== '' ? $firstName : null;
    $fields[] = 'last_name = ?'; $types .= 's'; $values[] = $lastName !== '' ? $lastName : null;
    $fields[] = 'contact_email = ?'; $types .= 's'; $values[] = $contactEmail !== '' ? $contactEmail : null;
    $fields[] = 'contact_phone = ?'; $types .= 's'; $values[] = $contactPhone !== '' ? $contactPhone : null;
} elseif ($updatesResidentProfile && $profileColumnsReady) {
    $fields[] = 'first_name = NULL';
    $fields[] = 'last_name = NULL';
    $fields[] = 'contact_email = NULL';
    $fields[] = 'contact_phone = NULL';
}

if (!empty($password)) {
    if (strlen($password) < 8) {
        $_SESSION['errors'] = ['Password must be at least 8 characters.'];
        header('Location: ../dashboard/admin.php?section=users');
        exit();
    }
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $fields[] = 'password = ?'; $types .= 's'; $values[] = $hashed;
}

$values[] = $userId; $types .= 'i';
 $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE userId = ?';
 mysqli_begin_transaction($conn);
 $stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    $_SESSION['errors'] = ['Database error preparing update.'];
    header('Location: ../dashboard/admin.php?section=users');
    exit();
}

mysqli_stmt_bind_param($stmt, $types, ...$values);
if (mysqli_stmt_execute($stmt)) {
    if ($updatesResidentProfile && ($firstName !== '' || $lastName !== '' || $contactEmail !== '' || $contactPhone !== '')) {
        $residentFields = [];
        $residentTypes = '';
        $residentValues = [];

        if ($firstName !== '') {
            $residentFields[] = 'firstName = ?';
            $residentTypes .= 's';
            $residentValues[] = $firstName;
        }
        if ($lastName !== '') {
            $residentFields[] = 'lastName = ?';
            $residentTypes .= 's';
            $residentValues[] = $lastName;
        }
        if ($contactEmail !== '') {
            $residentFields[] = 'emailAddress = ?';
            $residentTypes .= 's';
            $residentValues[] = $contactEmail;
        }
        if ($contactPhone !== '') {
            $residentFields[] = 'phoneNumber = ?';
            $residentTypes .= 's';
            $residentValues[] = $contactPhone;
        }

        if ($residentFields) {
            $residentValues[] = $residentId;
            $residentTypes .= 'i';
            $residentSql = 'UPDATE residents SET ' . implode(', ', $residentFields) . ' WHERE residentId = ?';
            $residentStmt = mysqli_prepare($conn, $residentSql);
            if (!$residentStmt) {
                mysqli_rollback($conn);
                $_SESSION['errors'] = ['Database error preparing resident profile update.'];
                mysqli_stmt_close($stmt);
                header('Location: ../dashboard/admin.php?section=users');
                exit();
            }

            mysqli_stmt_bind_param($residentStmt, $residentTypes, ...$residentValues);
            if (!mysqli_stmt_execute($residentStmt)) {
                mysqli_rollback($conn);
                $_SESSION['errors'] = ['Failed to update resident profile details.'];
                mysqli_stmt_close($residentStmt);
                mysqli_stmt_close($stmt);
                header('Location: ../dashboard/admin.php?section=users');
                exit();
            }
            mysqli_stmt_close($residentStmt);
        }
    }

    mysqli_commit($conn);
    $updatedUserLabel = userAccountLogLabel($conn, $userId);
    logActivity($conn, (int) $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin', $_SESSION['role'] ?? 'admin', 'user_updated', 'Admin updated user account: ' . $updatedUserLabel . '.');
    $_SESSION['success'] = 'User updated.';
} else {
    mysqli_rollback($conn);
    $_SESSION['errors'] = ['Failed to update user.'];
}
mysqli_stmt_close($stmt);

header('Location: ../dashboard/admin.php?section=users');
exit();
