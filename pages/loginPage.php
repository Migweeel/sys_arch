<?php 



$base = '../'; 

require_once '../auth/session.php';

$hasFunctionalConsent = hasCookieCategoryConsent('functional');
$rememberUsername = $hasFunctionalConsent ? ($_COOKIE['remember_username'] ?? '') : '';
$rememberRole = $hasFunctionalConsent ? ($_COOKIE['remember_role'] ?? '') : '';
$rememberChecked = !empty($rememberUsername);
$selectedRole = old('role') ?: ($rememberRole ?: 'resident');

ifLoggedIn();

if (isset($_COOKIE['remember_password'])) {
    setcookie('remember_password', '', time() - 3600, '/', '', false, true);
}

if (!$hasFunctionalConsent) {
    foreach (['remember_username', 'remember_role'] as $cookieName) {
        if (isset($_COOKIE[$cookieName])) {
            setcookie($cookieName, '', time() - 3600, '/', '', false, true);
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <!--- NAVIGATION --->
    <?php include $base . 'includes/navbar.php'; ?>

    <main class="login-wrapper" id="main-content">
        <img src="../assets/bg.png" alt="" class="login-bg-img">
        <div class="login-overlay"></div>

        <!-- Login card -->
        <div class="login-container">
            <div class="login-card">
                <img src="../assets/logo.png" class="card-logo" alt="Logo">
                <h2>Barangay Krus na Ligas</h2>

                <?php displayFlashMessages(); ?>

                <form action="../auth/login.php" method="POST">
                    <div class="visually-hidden" aria-hidden="true">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <fieldset class="role-selection">
                        <legend>Role</legend>
                        <div class="role-options">
                            <label for="login-role-resident">
                                <input id="login-role-resident" type="radio" name="role" value="resident" <?= $selectedRole === 'resident' ? 'checked' : '' ?> required>
                                <span>Resident</span>
                            </label>
                            <label for="login-role-staff">
                                <input id="login-role-staff" type="radio" name="role" value="staff" <?= $selectedRole === 'staff' ? 'checked' : '' ?>>
                                <span>Staff</span>
                            </label>
                            <label for="login-role-admin">
                                <input id="login-role-admin" type="radio" name="role" value="admin" <?= $selectedRole === 'admin' ? 'checked' : '' ?>>
                                <span>Admin</span>
                            </label>
                        </div>
                    </fieldset>

                    <label for="login-username">Username</label>
                    <input id="login-username" type="text" name="username" placeholder="Enter your username" value="<?= htmlspecialchars($rememberUsername ?: old('username')) ?>" autocomplete="username">

                    <label for="login-password">Password</label>
                    <input id="login-password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password">

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="options">
                        <label>
                            <input type="checkbox" name="remember" class="remember" <?= $rememberChecked ? 'checked' : '' ?>> Remember username and role (functional cookies)
                        </label>
                        <a href="forgotpass.php">Forgot password?</a>
                    </div>

                    <button class="login-btn">Login</button>
                    <div class="footer-links">
                        <p>Dont have an account? <a href="registerPage.php">Register here</a></p>
                   </div>
                </form>

                <?php clearOldInput(); ?>

            </div>
        </div>
    </main>

    <?php include $base . 'includes/footer.php'; ?>
    <script src="../script.js"></script>
</body>
</html>