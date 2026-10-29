<?php 



$base = '../'; 

require_once '../auth/session.php';

ifLoggedIn();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../css/index.css"> 
    <link rel="stylesheet" href="../css/register.css">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <!--- NAVIGATION --->
    <?php include $base . 'includes/navbar.php'; ?>

    <main class="register-section" id="main-content">
        <div class="register-bg-container">
            <img src="../assets/bg.png" alt="" class="register-bg-img">
            <div class="register-overlay"></div>
        </div>

        <div class="container">
            <div class="form-header">
                <img src="../assets/logo.png" alt="Barangay Logo" class="logo">
                <h3>Barangay Krus na Ligas</h3>
            </div>

            <?php displayFlashMessages(); ?>

            <form action="../auth/register.php" method="POST" enctype="multipart/form-data">
                <div class="section-header">Account Information</div>
                <div class="form-grid">
                    <div class="full-width">
                        <label for="register-username">Username</label>
                        <input id="register-username" type="text" name="username" value="<?= htmlspecialchars(old('username')) ?>" placeholder="Choose a username" autocomplete="username">
                    </div>
                    <div>
                        <label for="register-password">Password</label>
                        <input id="register-password" type="password" name="password" placeholder="Enter a password" autocomplete="new-password">
                    </div>
                    <div>
                        <label for="register-confirm-password">Confirm Password</label>
                        <input id="register-confirm-password" type="password" name="confirm_password" placeholder="Confirm your password" autocomplete="new-password">
                    </div>
                </div>

                <div class="section-header">Personal Information</div>
                <div class="form-grid">
                    <div>
                        <label for="register-first-name">First Name</label>
                        <input id="register-first-name" type="text" name="first_name" value="<?= htmlspecialchars(old('first_name')) ?>" placeholder="First Name" autocomplete="given-name">
                    </div>
                    <div>
                        <label for="register-last-name">Last Name</label>
                        <input id="register-last-name" type="text" name="last_name" value="<?= htmlspecialchars(old('last_name')) ?>" placeholder="Last Name" autocomplete="family-name">
                    </div>
                    <div>
                        <label for="register-middle-name">Middle Name</label>
                        <input id="register-middle-name" type="text" name="middle_name" value="<?= htmlspecialchars(old('middle_name')) ?>" placeholder="Proceed if N/A">
                    </div>
                    <div>
                        <label for="register-suffix">Suffix</label>
                        <input id="register-suffix" type="text" name="suffix" value="<?= htmlspecialchars(old('suffix')) ?>" placeholder="e.g. Jr., Sr. (Proceed if N/A)">
                    </div>
                    <div>
                        <label for="register-birthdate">Date of Birth</label>
                        <input id="register-birthdate" type="date" name="birthdate" value="<?= htmlspecialchars(old('birthdate')) ?>" max="<?= date('Y-m-d') ?>" autocomplete="bday">
                    </div>
                    <div>
                        <label for="register-sex">Sex</label>
                        <div class="select-wrap">
                            <select id="register-sex" name="sex" data-custom-select>
                                <option value="" disabled <?= old('sex') == '' ? 'selected' : '' ?>>
                                    Select Sex
                                </option>
                                <option value="Male" <?= old('sex') == 'Male' ? 'selected' : '' ?>>
                                    Male
                                </option>
                                <option value="Female" <?= old('sex') == 'Female' ? 'selected' : '' ?>>
                                    Female
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="full-width">
                        <label for="register-address">Address</label>
                        <input id="register-address" type="text" name="address" value="<?= htmlspecialchars(old('address')) ?>" placeholder="House No., Street Name, Phase/Blk" autocomplete="street-address">
                    </div>
                    <div>
                        <label for="register-civil-status">Civil Status</label>
                        <div class="select-wrap">
                            <select id="register-civil-status" name="civil_status" data-custom-select>
                                <option value="" disabled <?= old('civil_status') == '' ? 'selected' : '' ?>>
                                    Select Status
                                </option>
                                <option value="Single" <?= old('civil_status') == 'Single' ? 'selected' : '' ?>>
                                    Single
                                </option>
                                <option value="Married" <?= old('civil_status') == 'Married' ? 'selected' : '' ?>>
                                    Married
                                </option>
                                <option value="Widowed" <?= old('civil_status') == 'Widowed' ? 'selected' : '' ?>>
                                    Widowed
                                </option>
                                <option value="Separated" <?= old('civil_status') == 'Separated' ? 'selected' : '' ?>>
                                    Separated
                                </option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="register-phone">Phone Number</label>
                        <input id="register-phone" type="tel" name="phone_no" value="<?= htmlspecialchars(old('phone_no')) ?>" placeholder="09xxxxxxxxx" autocomplete="tel">
                    </div>
                    <div class="full-width">
                        <label for="register-email">Email Address</label>
                        <input id="register-email" type="email" name="email" value="<?= htmlspecialchars(old('email')) ?>" placeholder="example@email.com" autocomplete="email">
                    </div>
                </div>

                <div class="section-header">Verification</div>
                <div class="form-grid">
                    <div class="full-width">
                        <label for="verification-proof">Proof of Residency / Valid ID</label>
                        <input id="verification-proof" type="file" name="verification_proof" accept=".jpg,.jpeg,.png,.pdf">
                        <?php if (!empty($_SESSION['registration_proof_path'])): ?>
                            <p class="field-note">Previously uploaded proof retained: <?= htmlspecialchars(basename($_SESSION['registration_proof_path']), ENT_QUOTES, 'UTF-8') ?>. Choose another file only if you want to replace it.</p>
                        <?php else: ?>
                            <p class="field-note">Upload a clear photo or PDF for admin verification before your account can be used.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="privacy-review">
                    <div class="section-header">Privacy Policy</div>

                    <div class="privacy-scroll" tabindex="0" aria-label="Privacy policy content">
                        <h4>Barangay Krus na Ligas Privacy Policy</h4>
                        <p>Barangay Krus na Ligas respects your privacy and is committed to protecting the personal information you provide when using this website and its online services.</p>

                        <h5>Information We Collect</h5>
                        <p>We may collect the information you voluntarily provide when creating an account, submitting requests, or filing complaints. This may include your name, username, date of birth, sex, civil status, address, phone number, email address, and identity or residency documents.</p>

                        <h5>How We Use Personal Information</h5>
                        <p>Personal information is used to create and manage resident accounts, verify identity, process barangay services, assess complaints, maintain records, and communicate with you about your requests and account-related matters.</p>

                        <h5>Storage and Security</h5>
                        <p>The information you share may be stored in the systems used to operate the website. We aim to protect your data through appropriate technical and administrative safeguards and limit access to authorized personnel who need it to perform official duties.</p>

                        <h5>Retention and Deletion</h5>
                        <p>We retain personal information only as long as necessary for the service, legal requirements, or legitimate administrative needs. Records that are no longer required may be deleted or disposed of securely according to applicable rules.</p>

                        <h5>Sharing and Third-Party Services</h5>
                        <p>We may share information only with authorized personnel, service providers that support website operations, or third parties when legally required or necessary for the barangay service being requested.</p>

                        <h5>Your Rights</h5>
                        <p>You may request access, correction, or deletion of your personal data where permitted by law, subject to verification and the limits provided by applicable legal requirements.</p>

                        <p>Your information will only be used for legitimate barangay services and account administration.</p>
                    </div>

                    <div class="privacy-consent">
                        <label class="privacy-consent-label" for="privacy_consent">
                            <input type="checkbox" id="privacy_consent" name="privacy_consent" value="1" <?= old('privacy_consent') === '1' ? 'checked' : '' ?> required>
                            <span>
                                I have read and understood the Privacy Notice and Privacy Policy and acknowledge the collection, use, storage, and processing of my personal information by Barangay Krus na Ligas for the purposes stated therein, in accordance with Republic Act No. 10173 (Data Privacy Act of 2012), its implementing rules and regulations, and other applicable laws.
                            </span>
                        </label>
                        <p class="field-note">Select the checkbox above to enable Proceed.</p>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <button type="submit" class="register-btn" <?= old('privacy_consent') === '1' ? '' : 'disabled' ?>>Proceed</button>
                <div class="footer-links">
                    <p>Already have an account? <a href="loginPage.php">Login here</a></p>
                </div>
            </form>

            <?php clearOldInput(); ?>

        </div>
    </main>

    <?php include $base . 'includes/footer.php'; ?>
    <script src="../script.js"></script>
</body>
</html>
