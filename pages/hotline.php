<?php $base = '../'; ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotlines</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/hotline.css">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <!--- NAVBAR ---> 
    <?php include $base . 'includes/navbar.php'; ?>

    <!-- HOTLINE -->
    <main id="main-content">
    <div class="hotline-hero">
        <img src="../assets/krus-na-ligas.png" alt="" class="hero-bg-img">

        <div class="hero-overlay"></div>

        <div class="hero-content">
            <i class="ri-ambulance-line emergency-icon"></i>
            <h1 class="hero-title">EMERGENCY HOTLINES</h1>
            <p class="hero-subtitle">Quick access to all emergency and essential services in Quezon City. 
                Save these numbers for immediate assistance during emergencies.
            </p>

            <div class="hotline-cards-container">
                <div class="hotline-card">
                    <span class="card-label">FOR ALL EMERGENCIES</span>
                    <span class="card-number">122</span>
                </div>
                <div class="hotline-card">
                    <span class="card-label">BARANGAY MOBILE</span>
                    <span class="card-number">0962-396-2951</span>
                </div>
            </div>
        </div>
    </div>

    <div class="important-banner">
        <p>
            <strong>Important:</strong> 
            Dial 911 for immediate life-threatening emergencies. For non-emergency concerns, 
            please use the specific hotlines listed below.
        </p>
    </div>

    <!-- HOTLINE CARDS -->
    <div class="hotline-directory">
        <div class="directory-container">
            <div class="directory-card">
                <div class="card-header">
                    <i class="ri-shield-flash-line"></i>
                    <h3>Emergency Response</h3>
                </div>
                <ul class="contact-list">
                    <li>
                        <span>Emergency Hotline 911</span> 
                        <span class="num">911</span>
                    </li>
                    <li>
                        <span>QC Emergency Operations Center</span> 
                        <span class="num">122</span>
                    </li>
                    <li>
                        <span>QC Rescue Hotline</span> 
                        <span class="num">8988-4242</span>
                    </li>
                    <li>
                        <span>QC Emergency Medical Services</span> 
                        <span class="num">161</span>
                    </li>
                </ul>
            </div>

            <div class="directory-card">
                <div class="card-header">
                    <i class="ri-police-car-line"></i>
                    <h3>Police Stations</h3>
                </div>
                <ul class="contact-list">
                    <li>
                        <span>QCPD Emergency Hotline</span> 
                        <span class="num">8806-4701 to 06</span>
                    </li>
                    <li>
                        <span>Police Station 9 (Anonas)</span> 
                        <span class="num">0915-258-1066</span>
                    </li>
                </ul>
            </div>

            <div class="directory-card">
                <div class="card-header">
                    <i class="ri-fire-line"></i>
                    <h3>Fire Stations</h3>
                </div>
                <ul class="contact-list">
                    <li>
                        <span>Bureau of Fire Protection QC</span> 
                        <span class="num">8426-0246</span>
                    </li>
                    <li>
                        <span>BFP QC Fire Emergency</span> 
                        <span class="num">8426-1340</span>
                    </li>
                    <li>
                        <span>Novaliches Fire Station</span> 
                        <span class="num">8931-5161</span>
                    </li>
                </ul>
            </div>

            <div class="directory-card">
                <div class="card-header">
                    <i class="ri-hospital-line"></i>
                    <h3>Hospitals & Medical</h3>
                </div>
                <ul class="contact-list">
                    <li>
                        <span>Quezon City General Hospital</span> 
                        <span class="num">8372-2011 to 20</span>
                    </li>
                    <li>
                        <span>East Avenue Medical Center</span> 
                        <span class="num">8928-0611 to 30</span>
                    </li>
                    <li>
                        <span>Veterans Memorial Medical Center</span> 
                        <span class="num">8927-0383</span>
                    </li>
                </ul>
            </div>

            <div class="directory-card">
                <div class="card-header">
                    <i class="ri-government-line"></i>
                    <h3>Government Services</h3>
                </div>
                <ul class="contact-list">
                    <li>
                        <span>QC Action Center</span> 
                        <span class="num">8988-4242</span>
                    </li>
                    <li>
                        <span>QC Health Department</span> 
                        <span class="num">8921-2746</span>
                    </li>
                    <li>
                        <span>QC Disaster Risk Reduction</span> 
                        <span class="num">0967-541-7503</span>
                    </li>
                </ul>
            </div>

            <div class="directory-card">
                <div class="card-header">
                    <i class="ri-lightbulb-line"></i>
                    <h3>Utilities & Services</h3>
                </div>
                <ul class="contact-list">
                    <li>
                        <span>Meralco Hotline</span> 
                        <span class="num">16211</span>
                    </li>
                    <li>
                        <span>Manila Water</span> 
                        <span class="num">1627</span>
                    </li>
                    <li>
                        <span>Maynilad Water</span> 
                        <span class="num">1626</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="additional-resources">
        <h2 class="resource-main-title">Additional Resources</h2>
        <div class="resources-grid">
            <div class="res-card">
                <div class="res-icon-wrapper">
                    <i class="ri-map-pin-2-fill"></i>
                </div>
                <h3>QC Action Center</h3>
                <p>For complaints, inquiries, and requests</p>
                <span class="res-phone">8988-4242</span>
            </div>

            <div class="res-card">
                <div class="res-icon-wrapper">
                    <i class="ri-truck-fill"></i>
                </div>
                <h3>Emergency Rescue</h3>
                <p>24/7 emergency rescue services</p>
                <span class="res-phone">122</span>
            </div>

            <div class="res-card">
                <div class="res-icon-wrapper">
                    <i class="ri-shield-cross-fill"></i>
                </div>
                <h3>Disaster Response</h3>
                <p>Disaster risk reduction management</p>
                <span class="res-phone">0967-541-7503</span>
            </div>
        </div>
    </div>

    <!--- FOOTER --->
    </main>
    <?php include '../includes/footer.php'; ?>


    <script src="../script.js"></script>
</body>
</html>