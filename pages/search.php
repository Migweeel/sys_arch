<?php
$base = '../';
require_once '../auth/session.php';
require_once '../helpers/functions.php';

$searchQuery = trim((string) ($_GET['search'] ?? ''));
$searchQuery = substr($searchQuery, 0, 120);
$searchTerms = preg_split('/\s+/', $searchQuery, -1, PREG_SPLIT_NO_EMPTY);

$searchPages = [
    [
        'title' => 'Home',
        'url' => '../index.php',
        'description' => 'Barangay Krus na Ligas portal, officials, contact details, and office hours.',
        'keywords' => 'home barangay officials address telephone email office hours contact',
    ],
    [
        'title' => 'About Us',
        'url' => 'about.php',
        'description' => 'Learn about Barangay Krus na Ligas and its community.',
        'keywords' => 'about barangay community information',
    ],
    [
        'title' => 'Barangay Services and Documents',
        'url' => 'service.php',
        'description' => 'Find information about barangay services and document requests, including certificates, clearance, residency, and indigency.',
        'keywords' => 'services document request certificate barangay certificate clearance residency indigency requirements',
    ],
    [
        'title' => 'File a Complaint',
        'url' => 'complaint.php',
        'description' => 'Submit a complaint or report an incident to the barangay.',
        'keywords' => 'complaint complaints report incident submit concern',
    ],
    [
        'title' => 'Hotlines',
        'url' => 'hotline.php',
        'description' => 'View emergency and barangay contact numbers.',
        'keywords' => 'hotline emergency contact phone telephone',
    ],
    [
        'title' => 'Login',
        'url' => 'loginPage.php',
        'description' => 'Sign in to your resident, staff, or admin account.',
        'keywords' => 'login sign in account resident staff admin',
    ],
    [
        'title' => 'Register',
        'url' => 'registerPage.php',
        'description' => 'Create a resident account and submit proof for verification.',
        'keywords' => 'register registration account resident verification proof id',
    ],
    [
        'title' => 'Forgot Password',
        'url' => 'forgotpass.php',
        'description' => 'Request instructions to reset your account password.',
        'keywords' => 'forgot password reset recover account',
    ],
    [
        'title' => 'Privacy Policy',
        'url' => '../privacy-policy/',
        'description' => 'Learn how the website collects, uses, and protects personal information.',
        'keywords' => 'privacy policy data personal information collection security',
    ],
    [
        'title' => 'Terms and Conditions',
        'url' => '../terms/',
        'description' => 'Review the terms for using this website and its online services.',
        'keywords' => 'terms conditions rules use website',
    ],
    [
        'title' => 'Disclaimer',
        'url' => '../disclaimer/',
        'description' => 'Read the website information and service disclaimer.',
        'keywords' => 'disclaimer information service accuracy',
    ],
    [
        'title' => 'Accessibility',
        'url' => '../accessibility/',
        'description' => 'Read the accessibility statement and find ways to request assistance.',
        'keywords' => 'accessibility assistance accessible support',
    ],
];

$searchResults = [];
if ($searchTerms) {
    foreach ($searchPages as $page) {
        $searchableText = strtolower($page['title'] . ' ' . $page['description'] . ' ' . $page['keywords']);
        $score = 0;
        foreach ($searchTerms as $term) {
            $term = strtolower($term);
            if (strpos($searchableText, $term) === false) {
                $score = 0;
                break;
            }

            $score += stripos($page['title'], $term) !== false ? 5 : 1;
        }

        if ($score > 0) {
            $page['score'] = $score;
            $searchResults[] = $page;
        }
    }

    usort($searchResults, static function ($left, $right) {
        return $right['score'] <=> $left['score'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search the Website</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link rel="stylesheet" href="../css/search.css?v=20261007-search-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <?php include '../includes/navbar.php'; ?>

    <main id="main-content" class="site-search-page">
        <div class="site-search-container">
            <header class="site-search-heading">
                <p class="site-search-eyebrow">BARANGAY KRUS NA LIGAS</p>
                <h1>Search</h1>
                <?php if ($searchQuery !== ''): ?>
                    <p>Results for <strong><?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?></strong></p>
                <?php else: ?>
                    <p>Enter a search term to find information and services.</p>
                <?php endif; ?>
            </header>

            <?php if (!$searchTerms): ?>
                <p class="site-search-empty">Use the search field above to look for services, documents, contacts, or website information.</p>
            <?php elseif (!$searchResults): ?>
                <p class="site-search-empty">No matching pages found. Try a broader search term.</p>
            <?php else: ?>
                <p class="site-search-count"><?= count($searchResults) ?> <?= count($searchResults) === 1 ? 'page' : 'pages' ?> found</p>
                <ol class="site-search-results">
                    <?php foreach ($searchResults as $result): ?>
                        <li>
                            <a href="<?= htmlspecialchars($result['url'], ENT_QUOTES, 'UTF-8') ?>">
                                <span><?= htmlspecialchars($result['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                <i class="ri-arrow-right-up-line" aria-hidden="true"></i>
                            </a>
                            <p><?= htmlspecialchars($result['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../script.js"></script>
</body>
</html>