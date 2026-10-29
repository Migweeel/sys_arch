<?php
require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../config/app.php';
require_once '../helpers/functions.php';
require_once '../auth/security.php';

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

function buildResetUrl($token) {
    return appBaseUrl() . '/pages/resetPassword.php?token=' . urlencode($token);
}

function sendPasswordResetEmail($email, $name, $resetUrl) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $subject = "Barangay Krus na Ligas Password Reset";
    $message = "Hello {$name},\n\n";
    $message .= "We received a request to reset the password for your Barangay Krus na Ligas account.\n\n";
    $message .= "Instructions:\n";
    $message .= "1. Open the reset link below.\n";
    $message .= "2. Enter your new password.\n";
    $message .= "3. Confirm the new password and submit the form.\n";
    $message .= "4. Login again using your new password.\n\n";
    $message .= "Reset link:\n{$resetUrl}\n\n";
    $message .= "This link will expire in 1 hour. If you did not request this, you can ignore this email.\n\n";
    $message .= "Thank you.";
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    $smtpHost = appSmtpSetting('HOST');
    if ($smtpHost !== '' && is_file($autoload)) {
        require_once $autoload;

        try {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $smtpHost;
            $mailer->Port = (int) appSmtpSetting('PORT', '587');
            $mailer->SMTPAuth = appSmtpSetting('USERNAME') !== '';
            $mailer->Username = appSmtpSetting('USERNAME');
            $mailer->Password = appSmtpSetting('PASSWORD');
            if (strtolower(appSmtpSetting('ENCRYPTION', 'tls')) === 'ssl') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom(appMailFromAddress(), appMailFromName());
            $mailer->addAddress($email, $name);
            $mailer->Subject = $subject;
            $mailer->Body = $message;

            return $mailer->send();
        } catch (\PHPMailer\PHPMailer\Exception $exception) {
            error_log('Password reset email failed: ' . $exception->getMessage());
            return false;
        }
    }

    $headers = 'From: ' . appMailFromName() . ' <' . appMailFromAddress() . ">\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($email, $subject, $message, $headers);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/forgotpass.php");
    exit();
}

if (trim((string) ($_POST['website'] ?? '')) !== '') {
    http_response_code(204);
    exit();
}

$resetRateKey = securityClientKey($_POST['email'] ?? '');
if (!securityRateLimit('password-reset', $resetRateKey, 5, 900)) {
    $_SESSION['errors'] = ['Too many reset requests. Please try again later.'];
    header("Location: ../pages/forgotpass.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
    $_SESSION['errors'] = ["Security token mismatch. Please try again."];
    header("Location: ../pages/forgotpass.php");
    exit();
}

$_SESSION['old'] = $_POST;
$email = clean($_POST['email'] ?? '');
$errors = [];

if ($email === '') {
    $errors[] = "Please enter your email address.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if ($conn === null) {
    $errors[] = "Database connection failed. Please try again later.";
} elseif (!passwordResetTableExists($conn)) {
    $errors[] = "Password reset is not fully set up yet. Please run the required database SQL first.";
}

if (empty($errors)) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            u.userId,
            u.username,
            u.role,
            COALESCE(NULLIF(TRIM(r.emailAddress), ''), NULLIF(TRIM(u.contact_email), '')) AS account_email,
            COALESCE(NULLIF(TRIM(CONCAT(COALESCE(r.firstName, ''), ' ', COALESCE(r.lastName, ''))), ''), NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''), u.username) AS full_name
         FROM users u
         LEFT JOIN residents r ON u.residentId = r.residentId
         WHERE LOWER(r.emailAddress) = LOWER(?) OR LOWER(u.contact_email) = LOWER(?)
         LIMIT 1"
    );

    if (!$stmt) {
        $errors[] = "Unable to process reset request. Please try again.";
    } else {
        mysqli_stmt_bind_param($stmt, "ss", $email, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$user) {
            $errors[] = "Email address is not registered in the system.";
        } else {
            $userId = (int) $user['userId'];
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $clearStmt = mysqli_prepare($conn, "UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
            if ($clearStmt) {
                mysqli_stmt_bind_param($clearStmt, "i", $userId);
                mysqli_stmt_execute($clearStmt);
                mysqli_stmt_close($clearStmt);
            }

            $insertStmt = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())");
            if (!$insertStmt) {
                $errors[] = "Unable to create reset instructions. Please try again.";
            } else {
                mysqli_stmt_bind_param($insertStmt, "iss", $userId, $tokenHash, $expiresAt);
                if (mysqli_stmt_execute($insertStmt)) {
                    unset($_SESSION['old']);
                    refreshToken();

                    $resetUrl = buildResetUrl($token);
                    $emailSent = sendPasswordResetEmail($user['account_email'], $user['full_name'] ?: $user['username'], $resetUrl);

                    if ($emailSent) {
                        $_SESSION['success'] = "Password reset instructions were sent to the email address you entered.";
                    } else {
                        $_SESSION['errors'] = ["Reset link was created, but the email was not sent. Please check the mail server settings."];
                    }
                } else {
                    $errors[] = "Unable to create reset instructions. Please try again.";
                }
                mysqli_stmt_close($insertStmt);
            }
        }
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
}

if ($conn) {
    mysqli_close($conn);
}

header("Location: ../pages/forgotpass.php");
exit();
