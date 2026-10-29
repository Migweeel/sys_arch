<?php
$base = '../';

require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';

ifLoggedIn();

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

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenHash = $token !== '' ? hash('sha256', $token) : '';
$resetUser = null;

if ($conn !== null && $tokenHash !== '' && passwordResetTableExists($conn)) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT pr.reset_id, pr.user_id, pr.expires_at, u.username
         FROM password_resets pr
         INNER JOIN users u ON pr.user_id = u.userId
         WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()
         LIMIT 1"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $tokenHash);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $resetUser = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/forgotpass.css">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="sticky-header">
    <div class="navbar-top">
        <div class="top-content">
            <div class="flag-container">
                <img class="flag-img" src="../assets/ph-flag.jpg" alt="Philippine flag">
            </div>
            <div>
                <p class="portal-title">THE OFFICIAL WEB PORTAL OF BARANGAY KRUS NA LIGAS</p>
            </div>
        </div>
    </div>

    <div class="navbar-main">
        <div class="nav-container">
            <nav class="nav-wrapper" id="nav-menu">
                <div class="logo-section">
                    <a href="../index.php">
                        <img class="logo-img" src="../assets/logo.png" alt="Barangay logo">
                    </a>
                </div>
                <button type="button" class="mobile-toggle" id="hamburger" aria-label="Open navigation menu" aria-controls="mobile-menu" aria-expanded="false">
                    <i class="ri-menu-line" aria-hidden="true"></i>
                </button>
                <div class="nav-menu-desktop">
                    <ul class="nav-links">
                        <li><a href="../index.php">HOME</a></li>
                        <li><a href="loginPage.php">LOGIN</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>

    <div id="mobile-menu" class="mobile-menu-dropdown hidden">
        <div class="mobile-menu-content">
            <ul class="mobile-links">
                <li><a href="../index.php">HOME</a></li>
                <li><a href="loginPage.php">LOGIN</a></li>
            </ul>
        </div>
    </div>
</div>

<main id="main-content">
<div class="login-wrapper">
    <img src="../assets/bg.png" alt="" class="login-bg-img">
    <div class="login-overlay"></div>

    <div class="login-card">
        <img src="../assets/logo.png" alt="Barangay Logo">
        <h1>Barangay Krus na Ligas</h1>
        <h2>Reset Password</h2>
        <p>Create a new password for your account.</p>

        <?php displayFlashMessages(); ?>

        <?php if ($resetUser): ?>
            <form action="../actions/resetPassword.php" method="POST">
                <div class="request-form">
                    <label for="password" class="form-label">New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Minimum 8 characters" required>
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                </div>
                <div class="request-form">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                </div>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" class="btn-primary">Save New Password</button>
            </form>
        <?php else: ?>
            <div class="flash-messages-container">
                <p class="error-msg">&bull; This reset link is invalid or expired.</p>
            </div>
        <?php endif; ?>

        <a href="loginPage.php" class="back-link"><i class="fas fa-long-arrow-alt-left"></i> Back to Login</a>
    </div>
</div>

</main>
<?php include '../includes/footer.php'; ?>
<script src="../script.js"></script>
</body>
</html>
