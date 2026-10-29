<?php
require_once '../auth/session.php';
require_once __DIR__ . '/../auth/myprofile.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link rel="stylesheet" href="../css/resident-login.css?v=20261012-mobile-shell-5">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css" integrity="sha512-t7Few9xlddEmgd3oKZQahkNI4dS6l80+eGEzFQiqtyVYdvcSG2D3Iub77R20BdotfRPA9caaRkg1tyaJiPmO0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <?php require_once 'nav.php'; ?>

    <section id="section-main">
        <div class="main-container">
            <?php displayFlashMessages(); ?>

            <div class="profile-card">
                <div class="card-header">
                    <div class="header-text">
                        <h2>Account Settings</h2>
                        <p>Change your password or update your login username</p>
                    </div>
                    <a class="btn btn-outline-red" href="resident-login.php">Back to Profile</a>
                </div>

                <div class="card-content settings-content">
                    <div class="settings-form-card">
                        <div class="password-modal-header inline-settings-header">
                            <div>
                                <h3 id="changePasswordTitle">Change Password</h3>
                                <p>Use your current password and choose a new one.</p>
                            </div>
                        </div>

                        <form method="POST" action="../actions/updateSelf.php" class="password-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="redirect_to" value="../dashboard/changePassword.php">

                            <div class="password-field">
                                <label for="username">Username</label>
                                <input id="username" type="text" name="username" value="<?= htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
                            </div>

                            <div class="password-field">
                                <label for="current_password">Current Password</label>
                                <input id="current_password" type="password" name="current_password" placeholder="Enter your current password">
                            </div>

                            <div class="password-field">
                                <label for="new_password">New Password</label>
                                <input id="new_password" type="password" name="new_password" placeholder="Minimum 8 characters">
                            </div>

                            <div class="password-actions">
                                <a class="btn btn-outline-red" href="resident-login.php">Cancel</a>
                                <button type="submit" class="btn btn-red">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
