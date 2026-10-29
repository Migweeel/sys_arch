


<div class="sticky-header">
    <div class="navbar-top">
        <div class="top-content">
            <div class="flag-container">
                <img class="flag-img" src="<?= $base ?>assets/ph-flag.jpg" alt="Philippine flag" decoding="async">
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
                    <a href="<?= $base ?>index.php">
                        <img class="logo-img" src="<?= $base ?>assets/logo.png" alt="Barangay logo">
                    </a>
                </div>
                <button type="button" class="mobile-toggle" id="hamburger" aria-label="Open navigation menu" aria-controls="mobile-menu" aria-expanded="false">
                    <i class="ri-menu-line" aria-hidden="true"></i>
                </button>
                <div class="nav-menu-desktop">
                    <ul class="nav-links">
                        <li>
                            <a href="<?= $base ?>index.php">HOME</a>
                        </li>
                        <li>
                            <a href="<?= $base ?>pages/about.php">ABOUT US</a>
                        </li>
                        <li>
                            <a href="<?= $base ?>pages/complaint.php">COMPLAINT</a>
                        </li>
                        <li>
                            <a href="<?= $base ?>index.php#contact">CONTACT</a>
                        </li>
                        <li>
                            <a href="<?= $base ?>pages/hotline.php">HOTLINES</a>
                        </li>
                        <li>
                            <a href="<?= $base ?>pages/service.php">SERVICES</a>
                        </li>
                        <li>
                            <a href="<?= $base ?>pages/loginPage.php">LOGIN</a>
                        </li>
                        <li>
                            <a href="<?= $base ?>pages/registerPage.php">REGISTER</a>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="search-section-desktop">
                <form class="search-group" action="<?= $base ?>pages/search.php" method="GET" role="search">
                    <label class="visually-hidden" for="site-search-desktop">Search</label>
                    <input class="search-input" id="site-search-desktop" type="search" name="search" placeholder="Search" required>
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
                    <a href="<?= $base ?>index.php">HOME</a>
                </li>
                <li>
                    <a href="<?= $base ?>pages/about.php">ABOUT US</a>
                </li>
                <li>
                    <a href="<?= $base ?>pages/complaint.php">COMPLAINT</a>
                </li>
                <li>
                    <a href="<?= $base ?>index.php#contact">CONTACT</a>
                </li>
                <li>
                    <a href="<?= $base ?>pages/hotline.php">HOTLINES</a>
                </li>
                <li>
                    <a href="<?= $base ?>pages/service.php">SERVICES</a>
                </li>
                <li>
                    <a href="<?= $base ?>pages/loginPage.php">LOGIN</a>
                </li>
                <li>
                    <a href="<?= $base ?>pages/registerPage.php">REGISTER</a>
                </li>
            </ul>
            <form class="search-group mobile-search" action="<?= $base ?>pages/search.php" method="GET" role="search">
                <label class="visually-hidden" for="site-search-mobile">Search</label>
                <input class="search-input" id="site-search-mobile" type="search" name="search" placeholder="Search" required>
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>
    </div>
</div>