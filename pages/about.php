<?php $base = '../'; ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About us</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/about.css">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <!--- NAVBAR ---> 
    <?php include $base . 'includes/navbar.php'; ?>

    <!--- ABOUT --->
    <main id="main-content">
    <div class="about-bar">
        <h1 class="about-title">ABOUT US</h1>
    </div>

    <div class="about-visual-section">
        <div class="about-image-container">
            <img src="<?= $base ?>assets/krus-na-ligas.png" alt="Barangay Krus na Ligas Hall" class="about-hero-img">
        </div>
    </div>

    <div class="about-info-section">
        <div class="info-container">
            <div class="top-accent-line"></div>
                
            <h2 class="info-heading">About Krus na Ligas</h2>
                
            <p class="info-paragraph">
                Barangay Krus na Ligas is one of the historic and vibrant communities in Quezon City, 
                known for its rich cultural heritage and close-knit residents. Located near major 
                educational institutions and commercial areas, the barangay continues to grow while 
                preserving its traditions and community values. Guided by responsible leadership and 
                active citizen participation, Krus na Ligas is committed in promoting peace, 
                development and quality public service for all its constituents.
            </p>
        </div>
    </div>

    <div class="about-info-section">
        <div class="info-container">
            <div class="top-accent-line"></div>
                
            <h2 class="info-heading">Barangay History</h2>
                
            <p class="info-paragraph">
                Ang pangalang Krus na Ligas, na dating tinawag na Gulod at bahagi ng Marikina ay mula 
                sa punong Ligas na naghugis ng Krus na nakita sa pook na ito. Naging mahalagang muog 
                ang Krus na Ligas para kina Andres Bonifacio at ng Katipunan noong Himagsikang Pilipino 1896. 
                Noong Agosto 26, 1896 matapos ang sagupaan sa Pasong Tamo, dito umurong sina Bonifacio 
                at ang mga Katipunero upang mamahinga at magpulong. Sa patuloy na himagsikan, sa pamumuno 
                ni Bonifacio at nang lumaon ni Aguinaldo naging mahalagang muog ang pook na saklaw ng 
                Balara at Krus na Ligas.
            </p>
            <p class="info-paragraph" style="margin-top: 24px;">
                Sa bisa ng isang kasunduan sa pagitan ng Unibersidad ng Pilipinas at Pambansang Sanggunian sa 
                Pananaliksik sa Pilipinas, ang lugar na ito ay ipinahahayag na makasaysayang lugar nitong araw ng 
                ika-7 ng Disyembre 1998.
            </p>
        </div>
    </div>

    <!--- MISSION VISION --->
    <div class="about-info-section gray-bg">
        <div class="info-container">
            <div class="top-accent-line"></div>
            <h2 class="info-heading">Our Mission</h2>
            <p class="info-paragraph">
                To provide quality service to the residents of Krus na Ligas and establish an ideal community where 
                people can live and do business in a hospitable, peaceful manner under a comfortable, safe environment 
                that leads to a progressive and responsive society.
            </p>
        </div>
    </div>

    <div class="about-info-section gray-bg">
        <div class="info-container">
            <div class="top-accent-line"></div>
            <h2 class="info-heading">Our Vision</h2>
            <p class="info-paragraph">
                To be an independent and progressive barangay equipped with guiding principles and practices and be a 
                model of effective governance with responsible leaders.
            </p>
        </div>
    </div>

    <!--- OFFICIALS --->
    <?php include $base . 'includes/officials.php'; ?>
    </main>
    <?php include $base . 'includes/footer.php'; ?>
    <script src="../script.js"></script>
</body>
</html>