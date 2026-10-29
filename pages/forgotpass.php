
<?php
$base = '../';

require_once '../auth/session.php';

ifLoggedIn();

function displayForgotPasswordFlashMessages() {
    if (!isset($_SESSION['errors']) && !isset($_SESSION['success'])) {
        return;
    }

    echo '<div class="flash-messages-container" aria-live="polite">';
    if (isset($_SESSION['errors'])) {
        foreach ($_SESSION['errors'] as $error) {
            echo '<p class="error-msg" role="alert">&bull; ' . htmlspecialchars($error) . '</p>';
        }
        unset($_SESSION['errors']);
    }
    if (isset($_SESSION['success'])) {
        $successMessages = is_array($_SESSION['success']) ? $_SESSION['success'] : [$_SESSION['success']];
        foreach ($successMessages as $success) {
            echo '<p class="success-msg" role="status">' . htmlspecialchars($success) . '</p>';
        }
        unset($_SESSION['success']);
    }
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
                        <li>
                            <a href="../index.php">HOME</a>
                        </li>
                        <li>
                            <a href="registerPage.php">REGISTER</a>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="search-section-desktop">
                <form class="search-group" action="search.php" method="GET" role="search">
                    <label class="visually-hidden" for="forgot-search-desktop">Search</label>
                    <input class="search-input" id="forgot-search-desktop" type="search" name="search" placeholder="Search" required>
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </div>
    </div>

    <!-- MOBILE MENU DROPDOWN -->
    <div id="mobile-menu" class="mobile-menu-dropdown hidden">
        <div class="mobile-menu-content">
            <ul class="mobile-links">
                <li>
                            <a href="../index.php">HOME</a>
                        </li>
                        <li>
                            <a href="registerPage.php">REGISTER</a>
                        </li>
            </ul>
            <form class="search-group mobile-search" action="search.php" method="GET" role="search">
                <label class="visually-hidden" for="forgot-search-mobile">Search</label>
                <input class="search-input" id="forgot-search-mobile" type="search" name="search" placeholder="Search" required>
                <button type="submit" class="search-btn">Search</button>
            </form>
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
        <h2>Forgot Password?</h2>
        <p>Enter your email address and we'll send you instructions to reset your password.</p>

        <?php displayForgotPasswordFlashMessages(); ?>

        <form action="../actions/requestPasswordReset.php" method="POST">
            <div class="visually-hidden" aria-hidden="true">
                <label for="website">Leave this field empty</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div class="request-form">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars(old('email')); ?>" placeholder="Enter your email" required>
                    <i class="fa-solid fa-message input-icon"></i>
                </div>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn-primary">Reset Password</button>
        </form>
        <a href="loginPage.php" class="back-link"><i class="fas fa-long-arrow-alt-left"></i> Back to Login</a>

        <?php clearOldInput(); ?>
    </div>
  </div>

    </main>
    <?php include '../includes/footer.php'; ?>

    <script src="../script.js"></script>
</body>
</html>
