<?php
$base = '../';
require_once $base . 'auth/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/complaint.css?v=20260731-complaints">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <?php include $base . 'includes/navbar.php'; ?>

    <main class="complaint-section" id="main-content">
        <img src="../assets/bg.png" alt="" class="bg-image">
        <div class="bg-overlay"></div>

        <div class="complaint-container">
            <div class="complaint-card">
                <h1 class="title-main">COMPLAINT</h1>
                <h3 class="title-sub">Submit a Complaint</h3>

                <?php if (!empty($_SESSION['complaint_success'])): ?>
                    <div class="form-alert success" role="status"><?php echo htmlspecialchars($_SESSION['complaint_success']); unset($_SESSION['complaint_success']); ?></div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['complaint_errors'])): ?>
                    <div class="form-alert error" role="alert" aria-live="assertive">
                        <?php foreach ($_SESSION['complaint_errors'] as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; unset($_SESSION['complaint_errors']); ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/submitComplaint.php" method="POST" enctype="multipart/form-data" class="form-layout">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <div class="form-group">
                        <span class="label-main" id="complaint-types-label">List of Complaints (Check all that apply) <span class="required-text">required</span></span>
                        <div class="checkbox-grid" role="group" aria-labelledby="complaint-types-label">
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Waste Management / Garbage"> Waste Management / Garbage</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Roads & Infrastructure"> Roads & Infrastructure</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Draining / Flooding"> Draining / Flooding</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Water Supply"> Water Supply</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Pollution (Noise / Smoke / Odor)"> Pollution (Noise / Smoke / Odor)</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Stray Animals / Public Safety"> Stray Animals / Public Safety</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Permits & Licensing"> Permits & Licensing</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Health & Sanitation"> Health & Sanitation</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Traffic & Parking"> Traffic & Parking</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Social Services"> Social Services</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Rude Employee"> Rude Employee</label>
                            <label class="checkbox-item"><input type="checkbox" name="complaints[]" value="Others"> Others</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label-main" for="complaint-location">Location <span class="required-text">required</span></label>
                        <input id="complaint-location" type="text" name="location" class="text-input" placeholder="Example: Pook Amorsolo, near basketball court" required>
                    </div>

                    <div class="form-group">
                        <label class="label-main" for="complaint-description">Details / Description <span class="optional-text">optional</span></label>
                        <textarea id="complaint-description" rows="4" name="description" placeholder="Provide more details if needed..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="label-main" for="complaint-attachment">Proof, Evidence, or Attachments <span class="optional-text">optional</span></label>
                        <input id="complaint-attachment" type="file" name="attachment" class="file-input" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                    </div>

                    <div class="form-footer">
                        <button type="reset" class="btn-clear">Clear</button>
                        <button type="submit" class="btn-submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include $base . 'includes/footer.php'; ?>
    <script src="../script.js"></script>
</body>
</html>
