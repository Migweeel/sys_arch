<?php
require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';

function passwordResetTableExists($conn) {
    if (!$conn) {
        return false;
    }

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'password_resets'");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row && (int) $row['table_count'] > 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/forgotpass.php");
    exit();
}

$token = trim((string) ($_POST['token'] ?? ''));
$redirect = "../pages/resetPassword.php?token=" . urlencode($token);

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
    $_SESSION['errors'] = ["Security token mismatch. Please try again."];
    header("Location: " . $redirect);
    exit();
}

$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$errors = [];

if ($token === '') {
    $errors[] = "Reset link is missing.";
}

if ($password === '' || $confirmPassword === '') {
    $errors[] = "Please fill in all password fields.";
} elseif ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
} elseif (strlen($password) < 8) {
    $errors[] = "New password must be at least 8 characters.";
}

if ($conn === null) {
    $errors[] = "Database connection failed. Please try again later.";
} elseif (!passwordResetTableExists($conn)) {
    $errors[] = "Password reset is not fully set up yet. Please run the required database SQL first.";
}

if (empty($errors)) {
    $tokenHash = hash('sha256', $token);
    $stmt = mysqli_prepare(
        $conn,
        "SELECT reset_id, user_id
         FROM password_resets
         WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
         LIMIT 1"
    );

    if (!$stmt) {
        $errors[] = "Unable to validate reset link. Please request a new one.";
    } else {
        mysqli_stmt_bind_param($stmt, "s", $tokenHash);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reset = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$reset) {
            $errors[] = "Reset link is invalid or expired.";
        } else {
            $userId = (int) $reset['user_id'];
            $resetId = (int) $reset['reset_id'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ?, updated_at = NOW() WHERE userId = ?");
            if (!$updateStmt) {
                $errors[] = "Unable to update password. Please try again.";
            } else {
                mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $userId);
                $updated = mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);

                if ($updated) {
                    $usedStmt = mysqli_prepare($conn, "UPDATE password_resets SET used_at = NOW() WHERE reset_id = ?");
                    if ($usedStmt) {
                        mysqli_stmt_bind_param($usedStmt, "i", $resetId);
                        mysqli_stmt_execute($usedStmt);
                        mysqli_stmt_close($usedStmt);
                    }

                    refreshToken();
                    $_SESSION['success'] = "Password updated successfully. You can now login.";

                    if ($conn) {
                        mysqli_close($conn);
                    }

                    header("Location: ../pages/loginPage.php");
                    exit();
                }

                $errors[] = "Unable to update password. Please try again.";
            }
        }
    }
}

$_SESSION['errors'] = $errors;

if ($conn) {
    mysqli_close($conn);
}

header("Location: " . $redirect);
exit();
