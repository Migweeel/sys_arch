<?php
require_once '../auth/session.php';
require_once __DIR__ . '/../auth/myprofile.php';

$highlightVerification = ($_GET['highlight'] ?? '') === 'verification';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
          <h2>My Profile</h2>
          <p>Manage your personal information</p>
        </div>
        <button class="btn btn-outline-red" type="button" data-profile-modal-open>
          <?= $accountStatus === 'verified' ? 'Edit &amp; Re-verify' : 'Edit Profile' ?>
        </button>
      </div>

      <div class="card-content">
        <div class="profile-details">
          <div class="user-info-header">
            <div class="avatar-circle <?= $avatarPath ? 'has-avatar' : '' ?>" id="avatarTrigger">
              <?php if (!empty($avatarPath)): ?>
                <img id="avatarPreview" src="../actions/download.php?path=<?= rawurlencode(ltrim($avatarPath, '/\\')) ?>" alt="Profile avatar">
              <?php else: ?>
                <i class="bi bi-person-fill-down avatar-upload-icon"></i>
              <?php endif; ?>
              <span class="avatar-upload-text">Upload</span>
            </div>

            <div class="user-text">
              <h3><?= htmlspecialchars($fullName) ?></h3>
              <span><?= htmlspecialchars($role) ?></span>
              <span class="account-status <?= htmlspecialchars($accountStatus) ?>"><?= htmlspecialchars($accountStatusLabel) ?></span>
            </div>

            <input type="hidden" id="avatarCsrfToken" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="file" id="avatarInput" name="avatar" accept="image/*" hidden>
          </div>

          <div class="avatar-crop-backdrop" id="avatarCropDialog" hidden>
            <section class="avatar-crop-panel" role="dialog" aria-modal="true" aria-labelledby="avatarCropTitle">
              <div class="avatar-crop-header">
                <h2 id="avatarCropTitle">Adjust profile photo</h2>
                <button type="button" id="avatarCropCancel" aria-label="Cancel photo crop">&times;</button>
              </div>
              <div class="avatar-crop-canvas-wrap">
                <canvas id="avatarCropCanvas" width="320" height="320"></canvas>
              </div>
              <label class="avatar-zoom-label" for="avatarCropZoom">Zoom</label>
              <input id="avatarCropZoom" type="range" min="1" max="3" step="0.01" value="1">
              <div class="avatar-crop-actions">
                <button type="button" class="btn btn-outline-red" id="avatarCropCancelButton">Cancel</button>
                <button type="button" class="btn btn-red" id="avatarCropSave">Use photo</button>
              </div>
            </section>
          </div>

          <div class="info-grid">
            <div class="info-field">
              <div class="field-left">
                <i class="bi bi-person-circle"></i>
                <span class="field-label">Full Name</span>
              </div>
              <div class="value-box"><?= htmlspecialchars($fullName) ?></div>
            </div>

            <div class="info-field">
              <div class="field-left">
                <i class="bi bi-shield-check"></i>
                <span class="field-label">Account Status</span>
              </div>
              <span class="account-status <?= htmlspecialchars($accountStatus) ?>"><?= htmlspecialchars($accountStatusLabel) ?></span>
            </div>

            <div class="info-field">
              <div class="field-left">
                <i class="bi bi-shield-lock"></i>
                <span class="field-label">Password</span>
              </div>
              <span style="margin-right: 10px;">********</span>
              <a class="btn btn-outline-red btn-sm" href="changePassword.php">CHANGE</a>
            </div>

            <?php if (shouldShowProfileValue($phone)): ?>
            <div class="info-field">
              <div class="field-left">
                <i class="bi bi-telephone"></i>
                <span class="field-label">Phone Number</span>
              </div>
              <span class="field-value"><?= htmlspecialchars($phone) ?></span>
            </div>
            <?php endif; ?>

            <?php if (shouldShowProfileValue($dob)): ?>
            <div class="info-field">
              <div class="field-left">
                <i class="bi bi-calendar"></i>
                <span class="field-label">Date of Birth</span>
              </div>
              <span class="field-value"><?= htmlspecialchars($dob) ?></span>
            </div>
            <?php endif; ?>

            <?php if (shouldShowProfileValue($civilStatus)): ?>
            <div class="info-field">
              <div class="field-left">
                <i class="bi bi-person-hearts"></i>
                <span class="field-label">Civil Status</span>
              </div>
              <span class="field-value"><?= htmlspecialchars($civilStatus) ?></span>
            </div>
            <?php endif; ?>

            <?php if (shouldShowProfileValue($sex)): ?>
            <div class="info-field">
              <div class="field-left">
                <i class="bi bi-person-fill"></i>
                <span class="field-label">Sex</span>
              </div>
              <span class="field-value"><?= htmlspecialchars($sex) ?></span>
            </div>
            <?php endif; ?>

            <?php if (shouldShowProfileValue($address)): ?>
            <div class="info-field full-width">
              <div class="field-left">
                <i class="bi bi-geo-alt"></i>
                <span class="field-label">Address</span>
              </div>
              <span class="field-value"><?= htmlspecialchars($address) ?></span>
            </div>
            <?php endif; ?>

            <?php if (shouldShowProfileValue($email)): ?>
            <div class="info-field full-width">
              <div class="field-left">
                <i class="bi bi-envelope-open"></i>
                <span class="field-label">Email Address</span>
              </div>
              <span class="field-value"><?= htmlspecialchars($email) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="sidebar">
          <h4 class="sidebar-title">ACCOUNT VERIFICATION</h4>

          <div class="verification-summary <?= htmlspecialchars($accountStatus) ?><?= $highlightVerification ? ' verification-highlight' : '' ?>" id="account-verification">
            <strong><?= htmlspecialchars($accountStatusLabel) ?></strong>
            <p>
              <?php if ($accountStatus === 'verified'): ?>
                Your account is verified. You can submit barangay document requests.
              <?php elseif ($accountStatus === 'rejected'): ?>
                Your verification was rejected. Please upload a clearer or updated proof for another review.
              <?php else: ?>
                Your submitted proof is waiting for admin review. Requests unlock after verification.
              <?php endif; ?>
            </p>
          </div>

          <?php if ($accountStatus === 'rejected' && $verificationRejectionReason !== ''): ?>
          <div class="rejection-reason-box">
            <strong>Reason</strong>
            <p><?= htmlspecialchars($verificationRejectionReason) ?></p>
          </div>
          <?php endif; ?>

          <div class="sidebar-section sidebar-copy-section">
            <h5>REQUIREMENTS:</h5>
            <ul class="sidebar-list sidebar-copy-list">
              <li>
                <div class="list-content">
                  <strong>Barangay ID</strong>
                  <p>An official ID or supporting document to confirm your identity.</p>
                </div>
              </li>
            </ul>
          </div>

          <div class="sidebar-section sidebar-copy-section">
            <h5>PERKS:</h5>
            <ul class="sidebar-list sidebar-copy-list">
              <li><div class="list-content"><p>Convenient online access to Barangay document requests</p></div></li>
              <li><div class="list-content"><p>File complaints conveniently via our online portal.</p></div></li>
            </ul>
          </div>

          <?php if ($accountStatus === 'rejected'): ?>
          <form class="verification-upload-form" action="../actions/resubmitVerification.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <label for="verificationProofUpload">Upload new proof</label>
            <input id="verificationProofUpload" type="file" name="verification_proof" accept=".jpg,.jpeg,.png,.pdf" required>
            <p>Accepted files: JPG, PNG, or PDF up to 5MB.</p>
            <button type="submit" class="btn btn-red full-width-btn">
              <i class="bi bi-cloud-arrow-up"></i> VERIFY ACCOUNT
            </button>
          </form>
          <?php elseif ($accountStatus === 'pending'): ?>
            <?php if ($verificationProof !== ''): ?>
            <?php $proofExtension = strtolower(pathinfo($verificationProof, PATHINFO_EXTENSION)); ?>
            <button class="proof-view-link" type="button" data-proof-preview-open
              data-proof-url="<?= htmlspecialchars('../actions/download.php?path=' . rawurlencode(ltrim($verificationProof, '/\\')), ENT_QUOTES, 'UTF-8') ?>"
              data-proof-type="<?= in_array($proofExtension, ['jpg', 'jpeg', 'png'], true) ? 'image' : 'pdf' ?>"
              aria-haspopup="dialog" aria-controls="submitted-proof-preview">
              <i class="bi bi-paperclip"></i> View submitted proof
            </button>
            <?php endif; ?>
            <button class="btn btn-red full-width-btn" type="button" disabled>
              <i class="bi bi-hourglass-split"></i> FOR REVIEW
            </button>
          <?php else: ?>
            <button class="btn btn-red full-width-btn" type="button" disabled>
              <i class="bi bi-check-circle"></i> VERIFIED
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="proof-preview-backdrop" data-proof-preview hidden>
  <section class="proof-preview-panel" id="submitted-proof-preview" role="dialog" aria-modal="true" aria-labelledby="proofPreviewTitle">
    <div class="proof-preview-header">
      <div>
        <h2 id="proofPreviewTitle">Submitted proof</h2>
        <p>Verification document</p>
      </div>
      <button class="proof-preview-close" type="button" data-proof-preview-close aria-label="Close submitted proof preview">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
      </button>
    </div>
    <div class="proof-preview-content" data-proof-preview-content></div>
  </section>
</div>

<div class="profile-modal-backdrop" data-profile-modal hidden>
  <div class="profile-modal-panel" role="dialog" aria-modal="true" aria-labelledby="editProfileTitle">
    <div class="profile-modal-header">
      <div>
        <h3 id="editProfileTitle">Edit Profile</h3>
        <p>Update your resident information.</p>
      </div>
      <button type="button" class="profile-modal-close" data-profile-modal-close aria-label="Close edit profile form">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <form class="profile-edit-form" method="POST" action="../actions/updateResidentProfile.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

      <?php if ($accountStatus === 'verified'): ?>
      <div class="profile-reverification-notice">
        <strong>Re-verification required</strong>
        <p>Updating your profile will place your account back under review. Upload new proof to continue.</p>
        <label>New verification proof
          <input type="file" name="verification_proof" accept=".jpg,.jpeg,.png,.pdf" required>
        </label>
        <small>Accepted files: JPG, PNG, or PDF up to 5MB.</small>
      </div>
      <?php endif; ?>

      <div class="profile-form-grid">
        <label>First Name
          <input type="text" name="first_name" value="<?= htmlspecialchars($firstName); ?>" required>
        </label>
        <label>Last Name
          <input type="text" name="last_name" value="<?= htmlspecialchars($lastName); ?>" required>
        </label>
        <label>Middle Name
          <input type="text" name="middle_name" value="<?= htmlspecialchars($middleName); ?>">
        </label>
        <label>Suffix
          <input type="text" name="suffix" value="<?= htmlspecialchars($suffix); ?>" placeholder="Jr., Sr., III">
        </label>
        <label>Date of Birth
          <input type="date" name="date_of_birth" value="<?= htmlspecialchars($rawDob); ?>" required>
        </label>
        <label>Sex
          <select name="sex" required>
            <option value="">Select sex</option>
            <option value="Male" <?= $sex === 'Male' ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?= $sex === 'Female' ? 'selected' : ''; ?>>Female</option>
          </select>
        </label>
        <label>Civil Status
          <select name="civil_status" required>
            <option value="">Select civil status</option>
            <?php foreach (['Single', 'Married', 'Widowed', 'Separated'] as $statusOption): ?>
              <option value="<?= htmlspecialchars($statusOption); ?>" <?= $civilStatus === $statusOption ? 'selected' : ''; ?>><?= htmlspecialchars($statusOption); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Phone Number
          <input type="tel" name="phone_number" value="<?= htmlspecialchars($phone); ?>" placeholder="09XXXXXXXXX">
        </label>
        <label class="full-width">Email Address
          <input type="email" name="email_address" value="<?= htmlspecialchars($email); ?>">
        </label>
        <label class="full-width">Address
          <textarea name="full_address" rows="3" required><?= htmlspecialchars($address); ?></textarea>
        </label>
      </div>

      <div class="profile-modal-actions">
        <button type="button" class="btn btn-outline-red" data-profile-modal-close>Cancel</button>
        <button type="submit" class="btn btn-red">Save Profile</button>
      </div>
    </form>
  </div>
</div>

<script src="../upload-photo.js?v=20261009-avatar-2"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.querySelector('[data-profile-modal]');
  const openButton = document.querySelector('[data-profile-modal-open]');
  const closeButtons = document.querySelectorAll('[data-profile-modal-close]');

  const openModal = function () {
    if (!modal) return;
    modal.hidden = false;
    modal.classList.add('is-open');
    const firstInput = modal.querySelector('input, select, textarea, button');
    if (firstInput) firstInput.focus();
  };

  const closeModal = function () {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.hidden = true;
  };

  if (openButton) openButton.addEventListener('click', openModal);
  closeButtons.forEach(function (button) {
    button.addEventListener('click', closeModal);
  });
  if (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeModal();
    });
  }

  const proofPreview = document.querySelector('[data-proof-preview]');
  const proofPreviewButton = document.querySelector('[data-proof-preview-open]');
  const proofPreviewContent = document.querySelector('[data-proof-preview-content]');
  const closeProofPreviewButtons = document.querySelectorAll('[data-proof-preview-close]');

  const closeProofPreview = function () {
    if (!proofPreview) return;
    proofPreview.classList.remove('is-open');
    proofPreview.hidden = true;
    proofPreviewContent.replaceChildren();
    document.body.classList.remove('proof-preview-open');
    if (proofPreviewButton) proofPreviewButton.focus();
  };

  const openProofPreview = function () {
    if (!proofPreview || !proofPreviewButton || !proofPreviewContent) return;
    const proofUrl = proofPreviewButton.dataset.proofUrl;
    const previewType = proofPreviewButton.dataset.proofType;
    let viewer;

    if (previewType === 'image') {
      viewer = document.createElement('img');
      viewer.className = 'proof-preview-image';
      viewer.alt = 'Submitted verification document';
    } else {
      viewer = document.createElement('iframe');
      viewer.className = 'proof-preview-pdf';
      viewer.title = 'Submitted verification document';
    }

    viewer.src = proofUrl;
    proofPreviewContent.replaceChildren(viewer);
    proofPreview.hidden = false;
    proofPreview.classList.add('is-open');
    document.body.classList.add('proof-preview-open');
    proofPreview.querySelector('[data-proof-preview-close]').focus();
  };

  if (proofPreviewButton) proofPreviewButton.addEventListener('click', openProofPreview);
  closeProofPreviewButtons.forEach(function (button) {
    button.addEventListener('click', closeProofPreview);
  });
  if (proofPreview) {
    proofPreview.addEventListener('click', function (event) {
      if (event.target === proofPreview) closeProofPreview();
    });
  }
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && proofPreview && !proofPreview.hidden) {
      closeProofPreview();
    }
  });

  const verificationHighlight = document.querySelector('.verification-highlight');
  if (verificationHighlight) {
    verificationHighlight.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>
</body>
</html>
