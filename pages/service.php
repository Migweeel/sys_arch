<?php $base = '../'; ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/service.css">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <!--- NAVBAR --->
    <?php include $base . 'includes/navbar.php'; ?>
    
    <!-- SERVICE -->
    <main id="main-content">
    <div class="services-section">
        <div class="services-bg-container">
            <img src="../assets/krus-na-ligas.png" alt="" class="services-bg-img">
            <div class="services-overlay"></div>
        </div>

        <div class="services-content">
            <div class="services-header">
                <h1>OUR SERVICES</h1>
                <p>We offer a wide range of services to meet the needs of our community members</p>
            </div>

            <div class="services-grid">
                <a href="loginPage.php" class="service-card">
                    <div class="service-icon"><i class="ri-file-text-line"></i></div>
                    <h3>Barangay Clearance</h3>
                    <p>Get your barangay clearance for various purposes</p>
                    <span class="learn-more">Learn more <i class="ri-arrow-right-line"></i></span>
                </a>

                <a href="loginPage.php" class="service-card">
                    <div class="service-icon"><i class="ri-contacts-book-line"></i></div>
                    <h3>Certificate of Residency</h3>
                    <p>Proof of residency certification</p>
                    <span class="learn-more">Learn more <i class="ri-arrow-right-line"></i></span>
                </a>

                <a href="loginPage.php" class="service-card">
                    <div class="service-icon"><i class="ri-home-4-line"></i></div>
                    <h3>Business Permit</h3>
                    <p>Apply for barangay business permits</p>
                    <span class="learn-more">Learn more <i class="ri-arrow-right-line"></i></span>
                </a>

                <a href="loginPage.php" class="service-card">
                    <div class="service-icon"><i class="ri-shield-cross-line"></i></div>
                    <h3>Indigency Certificate</h3>
                    <p>For medical and educational assistance</p>
                    <span class="learn-more">Learn more <i class="ri-arrow-right-line"></i></span>
                </a>

                <a href="loginPage.php" class="service-card">
                    <div class="service-icon"><i class="ri-briefcase-line"></i></div>
                    <h3>Community Tax Certificate</h3>
                    <p>Cedula for residents</p>
                    <span class="learn-more">Learn more <i class="ri-arrow-right-line"></i></span>
                </a>

                <a href="complaint.php" class="service-card">
                    <div class="service-icon"><i class="ri-heart-line"></i></div>
                    <h3>Blotter Report</h3>
                    <p>File incident and complaint reports</p>
                    <span class="learn-more">Learn more <i class="ri-arrow-right-line"></i></span>
                </a>
            </div>
        </div>
    </div>

    </main>
    <?php include $base . 'includes/footer.php'; ?>
    <script src="../script.js"></script>
</body>
</html>