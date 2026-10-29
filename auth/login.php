<?php



require_once 'session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';
require_once 'security.php';



if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        http_response_code(204);
        exit();
    }
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errors'] = ["Security token mismatch. Please try again."];

        header("Location: ../pages/loginPage.php");
        exit();
    }

    $_SESSION['old'] = $_POST;
    unset($_SESSION['old']['password'], $_SESSION['old']['csrf_token']);
    $errors = [];

    $username = clean($_POST['username']);
    $password = $_POST['password'];
    $role = clean($_POST['role'] ?? '');
    $rateKey = securityClientKey($username);

    // VALIDATION
    if (empty($username) || empty($password) || empty($role)) {
        $errors[] = "Please fill in all fields.";
    }

    if (empty($errors) && !securityRateLimit('login', $rateKey, 10, 900)) {
        $errors[] = "Too many login attempts. Please try again later.";
    }

    if (empty($errors)) {
        $query = "SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND LOWER(role) = LOWER(?) LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);

        mysqli_stmt_bind_param($stmt, "ss", $username, $role);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                unset($_SESSION['old']);

                $resData = null;
                if (!empty($user['residentId'])) {
                    $resQuery = "SELECT firstName FROM residents WHERE residentId = ? LIMIT 1";
                    $resStmt = mysqli_prepare($conn, $resQuery);

                    if ($resStmt) {
                        mysqli_stmt_bind_param($resStmt, "i", $user['residentId']);
                        mysqli_stmt_execute($resStmt);

                        $resResult = mysqli_stmt_get_result($resStmt);
                        $resData = mysqli_fetch_assoc($resResult);
                        mysqli_stmt_close($resStmt);
                    }
                }

                $_SESSION['user_id'] = $user['userId'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = strtolower($user['role']);
                $_SESSION['account_status'] = strtolower($user['account_status'] ?? 'verified');
                $_SESSION['avatar_path'] = $user['avatar_path'] ?? '';

                if ($_SESSION['role'] === 'resident') {
                    $_SESSION['first_name'] = $resData['firstName'] ?? 'Resident';
                } else {
                    $_SESSION['first_name'] = trim((string) ($user['first_name'] ?? '')) ?: $user['username'];
                }

                $remember = isset($_POST['remember']);
                $rememberDuration = time() + 30 * 24 * 60 * 60;

                if ($remember && hasCookieCategoryConsent('functional')) {
                    setcookie('remember_username', $username, $rememberDuration, '/');
                    setcookie('remember_role', $role, $rememberDuration, '/');
                } else {
                    setcookie('remember_username', '', time() - 3600, '/');
                    setcookie('remember_role', '', time() - 3600, '/');
                }
                setcookie('remember_password', '', time() - 3600, '/', '', false, true);

                refreshToken();
                securityRateLimitReset('login', $rateKey);

                logActivity($conn, (int) $user['userId'], $user['username'], $user['role'], 'login', 'User logged in to the system');
                
                $role = $_SESSION['role'];

                if ($role === 'admin') {
                    header("Location: ../dashboard/admin.php");
                } else if ($role === 'resident') {
                    header("Location: ../dashboard/resident.php");
                } else if ($role === 'staff') {
                    header("Location: ../dashboard/staff.php");
                } else {
                    header("Location: ../pages/loginPage.php");
                }

                exit();

            } else {
                $errors[] = "Invalid username, password, or role.";
            }
        } else {
            $errors[] = "Invalid username, password, or role.";
        }

        mysqli_stmt_close($stmt);
    }

    $_SESSION['errors'] = $errors;

    header("Location: ../pages/loginPage.php");
    exit();
    
} else {
    header("Location: ../pages/loginPage.php");
    exit();
}
