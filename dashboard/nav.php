<?php
if (!isset($firstName) || empty($firstName)) {
    $firstName = $_SESSION['first_name'] ?? 'Resident';
}

$residentNotifications = [];
if (isset($conn, $_SESSION['user_id']) && $conn instanceof mysqli) {
    $notificationUserId = (int) $_SESSION['user_id'];
    $accountNotificationStmt = mysqli_prepare(
        $conn,
        "SELECT account_status, verification_rejection_reason, updated_at
         FROM users
         WHERE userId = ?
         LIMIT 1"
    );
    if ($accountNotificationStmt) {
        mysqli_stmt_bind_param($accountNotificationStmt, "i", $notificationUserId);
        mysqli_stmt_execute($accountNotificationStmt);
        $accountNotificationResult = mysqli_stmt_get_result($accountNotificationStmt);
        if ($accountNotification = mysqli_fetch_assoc($accountNotificationResult)) {
            $notificationAccountStatus = strtolower((string) ($accountNotification['account_status'] ?? 'pending'));
            if (in_array($notificationAccountStatus, ['verified', 'rejected'], true)) {
                $residentNotifications[] = [
                    'key' => 'account-verification:' . $notificationAccountStatus . ':' . (string) ($accountNotification['updated_at'] ?? ''),
                    'href' => 'resident-login.php?highlight=verification',
                    'message' => $notificationAccountStatus === 'verified'
                        ? 'Your account verification has been approved.'
                        : 'Your account verification has been rejected.',
                    'status_date' => (string) ($accountNotification['updated_at'] ?? ''),
                ];
            }
        }
        mysqli_stmt_close($accountNotificationStmt);
    }

    $notificationStmt = mysqli_prepare(
        $conn,
        "SELECT request_id, request_number, document_type, status, COALESCE(reviewed_at, date_updated) AS status_date
         FROM document_requests
         WHERE user_id = ? AND status IN ('approved', 'printed', 'released', 'rejected')
         ORDER BY COALESCE(reviewed_at, date_updated) DESC, request_id DESC
         LIMIT 8"
    );
    if ($notificationStmt) {
        mysqli_stmt_bind_param($notificationStmt, "i", $notificationUserId);
        mysqli_stmt_execute($notificationStmt);
        $notificationResult = mysqli_stmt_get_result($notificationStmt);
        while ($notificationRow = mysqli_fetch_assoc($notificationResult)) {
            $notificationRequestId = (int) ($notificationRow['request_id'] ?? 0);
            $residentNotifications[] = [
                'key' => $notificationRequestId . ':' . (string) ($notificationRow['status_date'] ?? ''),
                'href' => 'request-history.php?highlight=' . $notificationRequestId,
                'message' => residentNotificationMessage($notificationRow),
                'status_date' => (string) ($notificationRow['status_date'] ?? ''),
            ];
        }
        mysqli_stmt_close($notificationStmt);
    }

    usort($residentNotifications, function ($left, $right) {
        return strtotime($right['status_date'] ?? '') <=> strtotime($left['status_date'] ?? '');
    });
    $residentNotifications = array_slice($residentNotifications, 0, 8);
}

function residentNotificationMessage($notification) {
    $requestNumber = trim((string) ($notification['request_number'] ?? 'Request'));
    $documentType = trim((string) ($notification['document_type'] ?? 'Document request'));
    $status = strtolower((string) ($notification['status'] ?? ''));
    $statusText = [
        'approved' => 'has been approved',
        'printed' => 'has been printed and is ready to claim',
        'released' => 'has been released',
        'rejected' => 'has been rejected',
    ][$status] ?? ('is now ' . $status);

    return $requestNumber . ' - ' . $documentType . ' ' . $statusText . '.';
}
?>
<section id="section-header">
  <header class="site-header">
    <div class="container header-content">
      <div class="header-left">
        <h1 class="brand-title">Resident Portal</h1>
        <p class="user-welcome">Welcome, <strong><?php echo htmlspecialchars($firstName); ?></strong></p>
      </div>

      <div class="header-right">
        <div class="notification-wrap">
          <button class="icon-btn notification-toggle" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="residentNotificationMenu">
            <i class="bi bi-bell"></i>
            <span class="notification-badge" data-notification-badge hidden>0</span>
          </button>
          <div class="notification-menu" id="residentNotificationMenu" hidden>
            <div class="notification-menu-header">
              <strong>Notifications</strong>
            </div>
            <div class="notification-list" data-notification-list>
              <?php if (empty($residentNotifications)): ?>
                <div class="notification-empty">No account or request updates yet.</div>
              <?php else: ?>
                <?php foreach ($residentNotifications as $notification): ?>
                  <a
                    class="notification-item"
                    href="<?php echo htmlspecialchars($notification['href']); ?>"
                    data-notification-key="<?php echo htmlspecialchars($notification['key']); ?>"
                  >
                    <span class="notification-unread-dot" aria-hidden="true"></span>
                    <span><?php echo htmlspecialchars($notification['message']); ?></span>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <button class="mobile-menu-toggle resident-mobile-menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="residentSubNav">
          <i class="bi bi-list"></i>
        </button>

        <a href="../auth/logout.php" class="logout-btn">
          <i class="bi bi-box-arrow-right"></i>
          <p>Logout</p>
        </a>
      </div>
    </div>
  </header>
</section>

<?php $residentCurrentPage = basename($_SERVER['PHP_SELF']); ?>
<section id="section-subnav">
  <div class="container">
    <nav class="sub-nav" id="residentSubNav" aria-label="Resident navigation">
      <a href="resident.php" class="nav-item<?php echo $residentCurrentPage === 'resident.php' ? ' active active1' : ''; ?>">
        <i class="bi bi-file-text-fill"></i>
        <span>Request Document</span>
      </a>
      <a href="request-history.php" class="nav-item<?php echo $residentCurrentPage === 'request-history.php' ? ' active active2' : ''; ?>">
        <i class="bi bi-clock-history"></i>
        <span>Request History</span>
      </a>
      <a href="resident-login.php" class="nav-item<?php echo $residentCurrentPage === 'resident-login.php' ? ' active active3' : ''; ?>">
        <i class="bi bi-person"></i>
        <span>My Profile</span>
      </a>
      <a href="changePassword.php" class="nav-item<?php echo $residentCurrentPage === 'changePassword.php' ? ' active active3' : ''; ?>">
        <i class="bi bi-gear"></i>
        <span>Account Settings</span>
      </a>
      <a href="../auth/logout.php" class="nav-item mobile-logout-nav">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </a>
    </nav>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const idleLimitMs = 15 * 60 * 1000;
  let idleTimer;
  const resetIdleTimer = function () {
    window.clearTimeout(idleTimer);
    idleTimer = window.setTimeout(function () {
      window.location.href = '../auth/logout.php?timeout=1';
    }, idleLimitMs);
  };

  ['click', 'mousemove', 'keydown', 'scroll', 'touchstart'].forEach(function (eventName) {
    document.addEventListener(eventName, resetIdleTimer, { passive: true });
  });
  resetIdleTimer();

  const toggle = document.querySelector('.notification-toggle');
  const menu = document.getElementById('residentNotificationMenu');
  const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
  const mobileMenu = document.getElementById('residentSubNav');
  const badge = document.querySelector('[data-notification-badge]');
  const items = Array.from(document.querySelectorAll('[data-notification-key]'));
  const storageKey = 'residentReadRequestNotifications';
  let readKeys = [];

  try {
    readKeys = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
  } catch (error) {
    readKeys = [];
  }

  const saveReadKeys = function () {
    window.localStorage.setItem(storageKey, JSON.stringify(readKeys));
  };

  const refreshNotifications = function () {
    const unreadItems = items.filter(function (item) {
      return !readKeys.includes(item.dataset.notificationKey);
    });

    items.forEach(function (item) {
      item.classList.toggle('is-read', readKeys.includes(item.dataset.notificationKey));
    });

    if (badge) {
      if (unreadItems.length > 0) {
        badge.hidden = false;
        badge.textContent = unreadItems.length > 9 ? '9+' : String(unreadItems.length);
      } else {
        badge.hidden = true;
      }
    }
  };

  if (toggle && menu) {
    toggle.addEventListener('click', function (event) {
      event.stopPropagation();
      const willOpen = menu.hidden;
      menu.hidden = !willOpen;
      toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
      if (!menu.hidden && !menu.contains(event.target) && !toggle.contains(event.target)) {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  if (mobileMenuToggle && mobileMenu) {
    mobileMenuToggle.addEventListener('click', function (event) {
      event.stopPropagation();
      const willOpen = !mobileMenu.classList.contains('is-open');
      mobileMenu.classList.toggle('is-open', willOpen);
      mobileMenuToggle.classList.toggle('is-open', willOpen);
      mobileMenuToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
      if (mobileMenu.classList.contains('is-open') && !mobileMenu.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
        mobileMenu.classList.remove('is-open');
        mobileMenuToggle.classList.remove('is-open');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
      }
    });

    mobileMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mobileMenu.classList.remove('is-open');
        mobileMenuToggle.classList.remove('is-open');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
      });
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 768) {
        mobileMenu.classList.remove('is-open');
        mobileMenuToggle.classList.remove('is-open');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  items.forEach(function (item) {
    item.addEventListener('click', function () {
      const key = item.dataset.notificationKey;
      if (key && !readKeys.includes(key)) {
        readKeys.push(key);
        saveReadKeys();
      }
    });
  });

  refreshNotifications();
});
</script>
