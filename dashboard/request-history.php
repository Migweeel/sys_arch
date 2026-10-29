<?php
require_once '../auth/session.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ["Please login to access the dashboard."];
    header("Location: ../pages/loginPage.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'resident') {
    header("Location: ../pages/loginPage.php");
    exit();
}

if ($conn === null) {
    die("Database connection failed. Please check your database configuration and ensure MySQL is running.");
}

$firstName = $_SESSION['first_name'] ?? 'Resident';
$userId = (int) $_SESSION['user_id'];
$highlightId = isset($_GET['highlight']) ? (int) $_GET['highlight'] : 0;

$sql = "SELECT request_id, request_number, document_type, purpose, status, admin_remarks, generated_file_path, date_submitted, date_updated, reviewed_at
        FROM document_requests
        WHERE user_id = ?
        ORDER BY date_submitted DESC, request_id DESC";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Error preparing statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $userId);

if (!mysqli_stmt_execute($stmt)) {
    die("Error executing statement: " . mysqli_error($conn));
}

$result = mysqli_stmt_get_result($stmt);
$requests = [];

while ($row = mysqli_fetch_assoc($result)) {
    $status = strtolower($row['status'] ?? 'pending');
    $statusMessages = [
        'pending' => 'Your request is waiting for admin review.',
        'approved' => 'Your document request has been approved and is now being prepared for printing.',
        'printed' => 'Your document has been printed and is ready to claim at the barangay office during office hours.',
        'released' => 'Released. Your document has already been claimed or issued.',
        'rejected' => 'Rejected. Please review the reason and submit a new request if needed.',
    ];
    $message = trim((string) ($row['admin_remarks'] ?? ''));
    if ($status === 'rejected' && $message !== '') {
        $message = 'Rejected: ' . $message;
    }

    $requests[] = [
        'request_id' => (int) $row['request_id'],
        'request_number' => $row['request_number'],
        'doc_type' => $row['document_type'],
        'date_submitted' => date('Y-m-d', strtotime($row['date_submitted'])),
        'date_requested' => date('Y-m-d', strtotime($row['date_submitted'])),
        'date_updated' => !empty($row['date_updated']) ? date('Y-m-d h:i A', strtotime($row['date_updated'])) : 'N/A',
        'reviewed_at' => !empty($row['reviewed_at']) ? date('Y-m-d h:i A', strtotime($row['reviewed_at'])) : '',
        'status' => $status,
        'message' => $message !== '' ? $message : ($statusMessages[$status] ?? 'No message.'),
        'purpose' => $row['purpose'],
        'admin_remarks' => trim((string) ($row['admin_remarks'] ?? '')),
        'generated_file_path' => trim((string) ($row['generated_file_path'] ?? '')),
        'files' => []
    ];
}

mysqli_stmt_close($stmt);

if (!empty($requests)) {
    $requestIds = array_map(function ($request) {
        return (int) $request['request_id'];
    }, $requests);
    $requestIds = array_values(array_unique(array_filter($requestIds)));

    if (!empty($requestIds)) {
        $filesByRequest = [];
        $idList = implode(',', $requestIds);
        $fileSql = "SELECT request_id, file_name, file_path FROM document_request_files WHERE request_id IN ($idList) ORDER BY request_id ASC, file_id ASC";
        $fileResult = mysqli_query($conn, $fileSql);
        if ($fileResult) {
            while ($fileRow = mysqli_fetch_assoc($fileResult)) {
                $fileRequestId = (int) ($fileRow['request_id'] ?? 0);
                $filesByRequest[$fileRequestId][] = [
                    'name' => $fileRow['file_name'] ?? 'Uploaded file',
                    'path' => $fileRow['file_path'] ?? '',
                ];
            }
        }

        foreach ($requests as &$request) {
            $request['files'] = $filesByRequest[(int) $request['request_id']] ?? [];
        }
        unset($request);
    }
}

$itemsPerPage = 5;
$totalRequests = count($requests);
$totalPages = max(1, (int) ceil($totalRequests / $itemsPerPage));
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($highlightId > 0) {
    foreach ($requests as $index => $request) {
        if ((int) $request['request_id'] === $highlightId) {
            $currentPage = (int) floor($index / $itemsPerPage) + 1;
            break;
        }
    }
}

$currentPage = max(1, min($currentPage, $totalPages));
$startIndex = ($currentPage - 1) * $itemsPerPage;
$pageRequests = array_slice($requests, $startIndex, $itemsPerPage, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History</title>
    <link rel="stylesheet" href="../css/request-history.css?v=20261012-mobile-shell-5" type="text/css">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css" integrity="sha512-t7Few9xlddEmgd3oKZQahkNI4dS6l80+eGEzFQiqtyVYdvcSG2D3Iub77R20BdotfRPA9caaRkg1tyaJiPmO0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <?php require_once 'nav.php'; ?>
  <?php displayFlashMessages(); ?>

  <section id="section-request-history">
  <div class="container">
    <div class="card">
      <div class="card-header">
        <h2>My Request History</h2>
      </div>

      <div class="table-responsive">
        <div class="data-table">
          <div class="thead">
            <div class="tr">
              <div class="th">Document Type</div>
              <div class="th date-submitted">Date Submitted</div>
              <div class="th date-requested">Date Requested</div>
              <div class="th">Status</div>
              <div class="th message-actions-heading">
                <span>Message</span>
                <span>Actions</span>
              </div>
            </div>
          </div>

          <div class="tbody">
            <?php if (!empty($pageRequests)): ?>
              <?php foreach ($pageRequests as $request): ?>
                <?php
                  $statusClass = htmlspecialchars($request['status']);
                  $statusText = ucfirst($request['status']);
                  $dateRequested = htmlspecialchars($request['date_requested'] ?? $request['date_submitted']);
                  $isHighlighted = $highlightId > 0 && (int) $request['request_id'] === $highlightId;
                  $requestModalId = 'residentRequestDetailsModal' . (int) $request['request_id'];
                ?>
                <div class="tr request-row<?php echo $isHighlighted ? ' request-highlight' : ''; ?>" id="request-<?php echo (int) $request['request_id']; ?>" data-request-id="<?php echo (int) $request['request_id']; ?>">
                  <div class="td doc-type">
                    <strong><?php echo htmlspecialchars($request['request_number'] ?: 'Request #' . $request['request_id']); ?></strong>
                    <span><?php echo htmlspecialchars($request['doc_type']); ?></span>
                  </div>
                  <div class="td date-submitted"><?php echo htmlspecialchars($request['date_submitted']); ?></div>
                  <div class="td date-requested"><?php echo $dateRequested; ?></div>
                  <div class="td"><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span></div>
                  <div class="td message-cell">
                    <div class="message-action-row">
                      <span><?php echo htmlspecialchars($request['message']); ?></span>
                      <button class="btn btn-outline-red open-request-modal" type="button" data-modal-target="<?php echo htmlspecialchars($requestModalId); ?>">View Details</button>
                    </div>
                  </div>
                </div>
                <div class="request-modal-backdrop" id="<?php echo htmlspecialchars($requestModalId); ?>" aria-hidden="true" hidden>
                  <div class="request-modal-panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($requestModalId); ?>Title">
                    <div class="request-modal-header">
                      <div>
                        <h3 id="<?php echo htmlspecialchars($requestModalId); ?>Title"><?php echo htmlspecialchars($request['request_number'] ?: 'Request #' . $request['request_id']); ?></h3>
                        <p><?php echo htmlspecialchars($request['doc_type']); ?></p>
                      </div>
                      <button type="button" class="request-modal-close" data-modal-close aria-label="Close request details"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="request-modal-body">
                      <div class="request-detail-grid">
                        <div>
                          <span>Status</span>
                          <strong><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span></strong>
                        </div>
                        <div>
                          <span>Date Submitted</span>
                          <strong><?php echo htmlspecialchars($request['date_submitted']); ?></strong>
                        </div>
                        <div>
                          <span>Last Updated</span>
                          <strong><?php echo htmlspecialchars($request['reviewed_at'] ?: $request['date_updated']); ?></strong>
                        </div>
                        <div class="wide">
                          <span>Purpose</span>
                          <strong><?php echo htmlspecialchars($request['purpose'] ?: 'Not specified'); ?></strong>
                        </div>
                        <div class="wide">
                          <span>Message</span>
                          <strong><?php echo htmlspecialchars($request['message']); ?></strong>
                        </div>
                      </div>

                      <div class="request-file-section">
                        <h4>Uploaded Requirements</h4>
                        <?php if (empty($request['files'])): ?>
                          <p>No uploaded requirement files recorded.</p>
                        <?php else: ?>
                          <div class="request-file-list">
                            <?php foreach ($request['files'] as $file): ?>
                              <?php
                                $filePath = trim((string) ($file['path'] ?? ''));
                                $fileName = trim((string) ($file['name'] ?? '')) ?: basename($filePath);
                                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                $previewType = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
                                    ? 'image'
                                    : ($fileExtension === 'pdf' ? 'pdf' : 'download');
                                $fileUrl = '../actions/download.php?path=' . rawurlencode(ltrim($filePath, '/\\'));
                              ?>
                              <?php if ($filePath !== ''): ?>
                                <button type="button" class="request-file-preview-button" data-request-file-preview
                                  data-file-url="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                  data-file-name="<?php echo htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8'); ?>"
                                  data-file-type="<?php echo $previewType; ?>" aria-haspopup="dialog">
                                  <i class="bi bi-paperclip"></i>
                                  <span><?php echo htmlspecialchars($fileName); ?></span>
                                </button>
                              <?php endif; ?>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>

                      <?php if ($request['generated_file_path'] !== ''): ?>
                        <div class="request-file-section">
                          <h4>Generated Document</h4>
                          <?php
                            $generatedFilePath = trim((string) $request['generated_file_path']);
                            $generatedFileName = basename($generatedFilePath);
                            $generatedExtension = strtolower(pathinfo($generatedFileName, PATHINFO_EXTENSION));
                            $generatedPreviewType = in_array($generatedExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
                                ? 'image'
                                : ($generatedExtension === 'pdf' ? 'pdf' : 'download');
                            $generatedFileUrl = '../actions/download.php?path=' . rawurlencode(ltrim($generatedFilePath, '/\\'));
                          ?>
                          <button type="button" class="generated-file-link request-file-preview-button" data-request-file-preview
                            data-file-url="<?php echo htmlspecialchars($generatedFileUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            data-file-name="<?php echo htmlspecialchars($generatedFileName, ENT_QUOTES, 'UTF-8'); ?>"
                            data-file-type="<?php echo $generatedPreviewType; ?>" aria-haspopup="dialog">
                            <i class="bi bi-file-earmark-pdf"></i>
                            <span><?php echo htmlspecialchars($generatedFileName); ?></span>
                          </button>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="tr"><div class="td empty-row">No requests found.</div></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="pagination">
        <?php
        $prevPage = $currentPage > 1 ? $currentPage - 1 : 1;
        $nextPage = $currentPage < $totalPages ? $currentPage + 1 : $totalPages;
        $prevDisabled = $currentPage === 1 ? 'style="opacity: 0.5; cursor: not-allowed;"' : '';
        $nextDisabled = $currentPage === $totalPages ? 'style="opacity: 0.5; cursor: not-allowed;"' : '';

        echo '<a href="?page=' . $prevPage . '" class="page-nav prev" aria-label="Previous page" ' . $prevDisabled . '>';
        echo '<i class="bi bi-arrow-left-circle"></i>';
        echo '</a>';

        for ($i = 1; $i <= $totalPages; $i++) {
            $activeClass = $i === $currentPage ? 'active' : '';
            echo '<a href="?page=' . $i . '" class="page-num ' . $activeClass . '">' . $i . '</a>';
        }

        echo '<a href="?page=' . $nextPage . '" class="page-nav next" aria-label="Next page" ' . $nextDisabled . '>';
        echo '<i class="bi bi-arrow-right-circle"></i>';
        echo '</a>';
        ?>
      </div>
    </div>
  </div>
</section>

<div class="request-file-preview-backdrop" data-request-file-preview-dialog hidden aria-hidden="true">
  <section class="request-file-preview-panel" role="dialog" aria-modal="true" aria-labelledby="requestFilePreviewTitle">
    <div class="request-modal-header">
      <div>
        <h3 id="requestFilePreviewTitle">Uploaded requirement</h3>
        <p data-request-file-preview-name></p>
      </div>
      <button type="button" class="request-modal-close" data-close-file-preview aria-label="Close file preview"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="request-file-preview-content" data-request-file-preview-content></div>
  </section>
</div>

<?php if ($highlightId > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const highlighted = document.getElementById('request-<?php echo (int) $highlightId; ?>');
  if (highlighted) {
    highlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const openModal = function (modal) {
    if (!modal) return;
    modal.hidden = false;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    const firstButton = modal.querySelector('button, a');
    if (firstButton) firstButton.focus();
  };

  const closeModal = function (modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    modal.hidden = true;
  };

  document.querySelectorAll('.open-request-modal').forEach(function (button) {
    button.addEventListener('click', function () {
      openModal(document.getElementById(button.dataset.modalTarget));
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(function (button) {
    button.addEventListener('click', function () {
      closeModal(button.closest('.request-modal-backdrop'));
    });
  });

  document.querySelectorAll('.request-modal-backdrop').forEach(function (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        closeModal(modal);
      }
    });
  });

  const filePreview = document.querySelector('[data-request-file-preview-dialog]');
  const filePreviewContent = document.querySelector('[data-request-file-preview-content]');
  const filePreviewName = document.querySelector('[data-request-file-preview-name]');
  let filePreviewTrigger = null;

  const closeFilePreview = function () {
    if (!filePreview) return;
    filePreview.classList.remove('is-open');
    filePreview.setAttribute('aria-hidden', 'true');
    filePreview.hidden = true;
    filePreviewContent.replaceChildren();
    document.body.classList.remove('request-file-preview-open');
    if (filePreviewTrigger) filePreviewTrigger.focus();
    filePreviewTrigger = null;
  };

  document.querySelectorAll('[data-request-file-preview]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!filePreview || !filePreviewContent || !filePreviewName) return;
      filePreviewTrigger = button;
      filePreviewName.textContent = button.dataset.fileName || 'Uploaded requirement';
      const fileUrl = button.dataset.fileUrl;
      const fileType = button.dataset.fileType;
      let viewer;

      if (fileType === 'image') {
        viewer = document.createElement('img');
        viewer.className = 'request-file-preview-image';
        viewer.alt = button.dataset.fileName || 'Uploaded requirement';
      } else if (fileType === 'pdf') {
        viewer = document.createElement('iframe');
        viewer.className = 'request-file-preview-pdf';
        viewer.title = button.dataset.fileName || 'Uploaded PDF requirement';
      } else {
        viewer = document.createElement('div');
        viewer.className = 'request-file-preview-fallback';
        const message = document.createElement('p');
        message.textContent = 'This file type cannot be previewed here.';
        const download = document.createElement('a');
        download.className = 'generated-file-link';
        download.href = fileUrl;
        download.download = button.dataset.fileName || '';
        download.textContent = 'Download file';
        viewer.append(message, download);
      }

      if (fileType === 'image' || fileType === 'pdf') {
        viewer.src = fileUrl;
      }
      filePreviewContent.replaceChildren(viewer);
      filePreview.hidden = false;
      filePreview.setAttribute('aria-hidden', 'false');
      filePreview.classList.add('is-open');
      document.body.classList.add('request-file-preview-open');
      filePreview.querySelector('[data-close-file-preview]').focus();
    });
  });

  document.querySelectorAll('[data-close-file-preview]').forEach(function (button) {
    button.addEventListener('click', closeFilePreview);
  });

  if (filePreview) {
    filePreview.addEventListener('click', function (event) {
      if (event.target === filePreview) closeFilePreview();
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      if (filePreview && !filePreview.hidden) {
        closeFilePreview();
        event.stopPropagation();
        return;
      }
      document.querySelectorAll('.request-modal-backdrop.is-open').forEach(closeModal);
    }
  });
});
</script>
</body>
</html>
