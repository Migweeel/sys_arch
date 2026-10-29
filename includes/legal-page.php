<?php
$base = '../';
$legalPages = require __DIR__ . '/legal-content.php';
$legalPage = $legalPages[$legalSlug] ?? null;

if ($legalPage === null) {
    http_response_code(404);
    exit('Page not found.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($legalPage['title']) ?> | Barangay Krus Na Ligas</title>
    <link rel="stylesheet" href="<?= $base ?>css/index.css">
    <link rel="stylesheet" href="<?= $base ?>css/responsive.css?v=20261006-responsive-1">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <?php include $base . 'includes/navbar.php'; ?>
    <main class="legal-page" id="main-content">
        <div class="legal-page-inner">
            <header class="legal-page-header">
                <p class="legal-eyebrow">Barangay Krus Na Ligas</p>
                <h1><?= htmlspecialchars($legalPage['title']) ?></h1>
                <p class="legal-updated">Last updated: October 6, 2026</p>
                <p class="legal-intro"><?= htmlspecialchars($legalPage['intro']) ?></p>
                <p class="legal-intro">This Privacy Policy explains what personal information may be collected, how it may be used, how it is protected, when it may be shared, and the choices and rights available to you.</p>
                <p class="legal-intro">By using this website and its services, you acknowledge that you have read this Privacy Policy. Where consent is required by applicable law, the website will request consent through the appropriate means.</p>
            </header>
            <nav class="legal-toc" aria-label="On this page">
                <h2>On this page</h2>
                <ol>
                    <?php foreach ($legalPage['sections'] as $id => $section): ?>
                        <li><a href="#<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($section['heading']) ?></a></li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <div class="legal-sections">
                <?php foreach ($legalPage['sections'] as $id => $section): ?>
                    <section id="<?= htmlspecialchars($id) ?>" class="legal-section">
                        <h2><?= htmlspecialchars($section['heading']) ?></h2>
                        <?= $section['body'] ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <?php include $base . 'includes/footer.php'; ?>
    <script src="<?= $base ?>script.js" defer></script>
</body>
</html>
