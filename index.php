<?php
if (!isset($base) || $base === '') {
    $base = '/updated/';
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Krus Na Ligas</title>
    <link rel="stylesheet" href="<?= $base ?>css/index.css">
    <link rel="stylesheet" href="<?= $base ?>css/home.css">
    <link rel="stylesheet" href="<?= $base ?>css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <!--- NAVBAR --->
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    
    <!--- HERO SECTIONS ---> 
    <main id="main-content">
    <div class="hero-section">
        <img class="hero-bg" src="<?= $base ?>assets/bg.png" alt="" fetchpriority="high" decoding="async">
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <div class="hero-text-container">
                <p class="hero-title">WELCOME TO</p>
                <h1 class="hero-title title-main">
                    BARANGAY KRUS <br class="br-desktop"> NA LIGAS
                </h1>
                <a class="hero-btn" href="<?= $base ?>pages/service.php">
                    WHAT ARE YOU LOOKING FOR?
                </a>
            </div>
        </div>
    </div>

    <div class="request-section">
        <img class="request-bg" src="<?= $base ?>assets/section2.png" alt="">
        
        <div class="request-content">
            <div class="request-container">
                <h2 class="request-title">
                    REQUEST YOUR DOCUMENTS FROM BARANGAY <br class="br-desktop">
                    KRUS NA LIGAS QUICKLY AND EASILY <br class="br-desktop">
                    ANYWHERE, ANYTIME.
                </h2>
                <a href="<?= $base ?>pages/service.php" class="request-btn">
                    REQUEST BARANGAY DOCUMENTS
                </a>
            </div>
        </div>
    </div>

    <!--- OFFICIALS --->
    <?php include __DIR__ . '/includes/officials.php'; ?>

    <!--- CONTACTS --->
        <div class="contact-section" id="contact">
        <div class="contact-container">
            <div class="contact-info">
                <h2 class="contact-heading">Get in Touch</h2>
                <p class="contact-subtext">
                    Have questions or need assistance? We're here to help. Visit us or reach out through any of our contact channels.
                </p>

                <div class="contact-item">
                    <i class="ri-map-pin-line contact-icon"></i>
                    <div>
                        <h3 class="contact-label">Address</h3>
                        <p class="contact-detail">Plaza Sta. Inez, Krus na Iglas, Quezon City</p>
                    </div>
                </div>

                <div class="contact-item">
                    <i class="ri-phone-line contact-icon"></i>
                    <div>
                        <h3 class="contact-label">Phone</h3>
                        <p class="contact-detail">86600801</p>
                        <p class="contact-detail">09623962951</p>
                    </div>
                </div>

                <div class="contact-item">
                    <i class="ri-mail-line contact-icon"></i>
                    <div>
                        <h3 class="contact-label">Email</h3>
                        <p class="contact-detail">info@barangaykrusnaligas.gov.ph</p>
                        <p class="contact-detail">contact@barangaykrusnaligas.gov.ph</p>
                    </div>
                </div>

                <div class="contact-item">
                    <i class="ri-time-line contact-icon"></i>
                    <div>
                        <h3 class="contact-label">Office Hours</h3>
                        <p class="contact-detail">Monday - Friday: 8:00 AM - 5:00 PM</p>
                        <p class="contact-detail">Saturday: 8:00 AM - 12:00 PM</p>
                        <p class="contact-detail">Sunday: Closed for all business transactions</p>
                    </div>
                </div>
            </div>

            <div class="contact-form-card">
                <h3 class="form-title">Send us a Message</h3>
                <form action="#" method="POST" class="message-form">
                    <div class="form-group">
                            <label class="input-label" for="contact-name">Full Name</label>
                            <input id="contact-name" type="text" class="form-input">
                    </div>
                    <div class="form-group">
                            <label class="input-label" for="contact-email">Email</label>
                            <input id="contact-email" type="email" class="form-input">
                    </div>
                    <div class="form-group">
                            <label class="input-label" for="contact-subject">Subject</label>
                            <input id="contact-subject" type="text" class="form-input">
                    </div>
                    <div class="form-group">
                            <label class="input-label" for="contact-message">Message</label>
                            <textarea id="contact-message" rows="4" placeholder="Your message here..." class="form-input"></textarea>
                    </div>
                        <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>
    </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?= $base ?>script.js"></script>
</body>
</html>
