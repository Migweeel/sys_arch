<?php



require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';



if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ["Please login to access the staff portal."];

    header("Location: ../pages/loginPage.php");
    exit();
}

if ($_SESSION['role'] !== 'staff') {
    header("Location: ../dashboard/" . $_SESSION['role'] . ".php");
    exit();
}

$displayName = $_SESSION['first_name'] ?? ($_SESSION['username'] ?? 'Staff User');

function staffUserColumnExists($conn, $columnName) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $columnName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row && (int) $row['column_count'] > 0;
}

if ($conn && isset($_SESSION['user_id']) && staffUserColumnExists($conn, 'first_name')) {
    $staffNameStmt = mysqli_prepare($conn, "SELECT first_name FROM users WHERE userId = ? LIMIT 1");
    if ($staffNameStmt) {
        $staffUserId = (int) $_SESSION['user_id'];
        mysqli_stmt_bind_param($staffNameStmt, "i", $staffUserId);
        mysqli_stmt_execute($staffNameStmt);
        $staffNameResult = mysqli_stmt_get_result($staffNameStmt);
        if ($staffNameRow = mysqli_fetch_assoc($staffNameResult)) {
            $staffFirstName = trim((string) ($staffNameRow['first_name'] ?? ''));
            if ($staffFirstName !== '') {
                $displayName = $staffFirstName;
                $_SESSION['first_name'] = $staffFirstName;
            }
        }
        mysqli_stmt_close($staffNameStmt);
    }
}

function staffRequestNumber($id, $dateSubmitted) {
    $year = $dateSubmitted ? date('Y', strtotime($dateSubmitted)) : date('Y');
    return 'REQ-' . $year . '-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

function staffTemplatePreview($name, $content, $isLive = false) {
    $name = trim((string) $name) ?: 'Certificate of Indigency';
    $content = sanitizeTemplateHtml($content);
    $titleAttribute = $isLive ? ' data-template-preview-title' : '';
    $contentAttribute = $isLive ? ' data-template-preview-content' : '';
    ?>
    <div class="template-preview-paper<?php echo $isLive ? ' is-live-preview' : ''; ?>">
        <div class="template-preview-top-rule"></div>
        <header class="template-preview-letterhead">
            <img class="template-preview-logo template-preview-logo-knl" src="../assets/template-logos/knl-logo.jpg" alt="Barangay Krus na Ligas seal">
            <div class="template-preview-heading">
                <p>Republic of the Philippines</p>
                <p>Quezon City, Metro Manila</p>
                <h4>Barangay Krus na Ligas</h4>
            </div>
            <img class="template-preview-logo template-preview-logo-qc" src="../assets/template-logos/lungsod.jpg" alt="Lungsod Quezon logo">
        </header>
        <main class="template-preview-main">
            <h5<?php echo $titleAttribute; ?>><?php echo htmlspecialchars($name); ?></h5>
            <div class="template-preview-copy"<?php echo $contentAttribute; ?>><?php echo $content; ?></div>
        </main>
        <div class="template-preview-signature">
            <strong>RENIE JOHN C. PERDIDO</strong>
            <span>Punong Barangay</span>
        </div>
        <footer class="template-preview-footer">
            <span><i class="bi bi-telephone"></i> 0962-3962951 / 0287110367</span>
            <span><i class="bi bi-envelope"></i> brgy.krusnaligas@quezoncity.gov.ph</span>
            <span><i class="bi bi-geo-alt"></i> 20 Plaza Sta. Ines Street</span>
            <img src="../assets/template-logos/bagong-pilipinas.webp" alt="Bagong Pilipinas">
        </footer>
    </div>
    <?php
}

function staffStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'printed' => 'Printed',
        'rejected' => 'Rejected',
        'released' => 'Released',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function fetchStaffRequestFiles($conn, $requestId) {
    $files = [];
    $fileStmt = mysqli_prepare($conn, "SELECT file_name, file_path FROM document_request_files WHERE request_id = ? ORDER BY file_id ASC");
    if ($fileStmt) {
        mysqli_stmt_bind_param($fileStmt, "i", $requestId);
        mysqli_stmt_execute($fileStmt);
        $fileResult = mysqli_stmt_get_result($fileStmt);
        while ($fileRow = mysqli_fetch_assoc($fileResult)) {
            $files[] = $fileRow;
        }
        mysqli_stmt_close($fileStmt);
    }

    return $files;
}

function staffTableExists($conn, $tableName) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $tableName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row && (int) $row['table_count'] > 0;
}

function staffComplaintStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'done' => 'Done',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function staffValidDateInput($value) {
    $value = trim((string) $value);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return '';
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
}

function staffReportDateRange($period, $startInput, $endInput) {
    $today = new DateTimeImmutable('today');
    $startDate = '';
    $endDate = '';

    if ($period === 'week') {
        $start = $today->modify('-' . ((int) $today->format('N') - 1) . ' days');
        $end = $start->modify('+6 days');
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');
    } elseif ($period === 'month') {
        $startDate = $today->modify('first day of this month')->format('Y-m-d');
        $endDate = $today->modify('last day of this month')->format('Y-m-d');
    } elseif ($period === 'year') {
        $startDate = $today->setDate((int) $today->format('Y'), 1, 1)->format('Y-m-d');
        $endDate = $today->setDate((int) $today->format('Y'), 12, 31)->format('Y-m-d');
    } elseif ($period === 'custom') {
        $startDate = staffValidDateInput($startInput);
        $endDate = staffValidDateInput($endInput);
        if ($startDate !== '' && $endDate !== '' && strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
    }

    return [$startDate, $endDate];
}

function staffReportRangeLabel($period, $startDate, $endDate, $periodOptions) {
    if ($period === 'all') {
        return 'All dates';
    }

    if ($startDate !== '' && $endDate !== '') {
        return date('M d, Y', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate));
    }

    if ($startDate !== '') {
        return 'From ' . date('M d, Y', strtotime($startDate));
    }

    if ($endDate !== '') {
        return 'Until ' . date('M d, Y', strtotime($endDate));
    }

    return $periodOptions[$period] ?? 'Selected period';
}

$reportPeriodOptions = [
    'week' => 'This Week',
    'month' => 'This Month',
    'year' => 'This Year',
    'custom' => 'Custom Range',
    'all' => 'All Dates',
];
$reportPeriod = strtolower($_GET['report_period'] ?? 'month');
if (!array_key_exists($reportPeriod, $reportPeriodOptions)) {
    $reportPeriod = 'month';
}

$reportStatusOptions = [
    'all' => 'All Statuses',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'printed' => 'Printed',
    'released' => 'Released',
    'rejected' => 'Rejected',
];
$reportStatusFilter = strtolower($_GET['report_status'] ?? 'all');
if (!array_key_exists($reportStatusFilter, $reportStatusOptions)) {
    $reportStatusFilter = 'all';
}

$reportDocumentTypeFilter = trim((string) ($_GET['report_document_type'] ?? 'all'));
if ($reportDocumentTypeFilter === '') {
    $reportDocumentTypeFilter = 'all';
}

$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'printed' => 0,
    'released' => 0,
    'rejected' => 0,
];

$statsSql = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
    SUM(CASE WHEN status = 'printed' THEN 1 ELSE 0 END) AS printed_count,
    SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) AS released_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
    FROM document_requests";
$statsResult = mysqli_query($conn, $statsSql);
if ($statsResult && $row = mysqli_fetch_assoc($statsResult)) {
    $stats = [
        'total' => (int) $row['total'],
        'pending' => (int) $row['pending_count'],
        'approved' => (int) $row['approved_count'],
        'printed' => (int) $row['printed_count'],
        'released' => (int) $row['released_count'],
        'rejected' => (int) $row['rejected_count'],
    ];
}

$pendingRequests = [];
$pendingRequestsSql = "SELECT
        r.*,
        u.username,
        res.firstName,
        res.lastName,
        res.emailAddress,
        res.phoneNumber,
        res.fullAddress
    FROM document_requests r
    LEFT JOIN users u ON r.user_id = u.userId
    LEFT JOIN residents res ON u.residentId = res.residentId
    WHERE r.status = 'pending'
    ORDER BY r.date_submitted DESC, r.request_id DESC";
$pendingRequestsResult = mysqli_query($conn, $pendingRequestsSql);
if ($pendingRequestsResult) {
    while ($row = mysqli_fetch_assoc($pendingRequestsResult)) {
        $pendingRequests[] = $row;
    }
}

$staffNotifications = [];
foreach (array_slice($pendingRequests, 0, 8) as $notificationRequest) {
    $notificationRequestId = (int) ($notificationRequest['request_id'] ?? 0);
    $notificationRequestNumber = $notificationRequest['request_number'] ?: staffRequestNumber($notificationRequestId, $notificationRequest['date_submitted'] ?? '');
    $notificationResidentName = trim(($notificationRequest['firstName'] ?? '') . ' ' . ($notificationRequest['lastName'] ?? ''));
    $notificationResidentName = $notificationResidentName ?: ($notificationRequest['username'] ?? 'Resident');
    $notificationDate = (string) ($notificationRequest['date_submitted'] ?? '');
    $staffNotifications[] = [
        'key' => 'staff-request:' . $notificationRequestId . ':' . $notificationDate,
        'href' => 'staff.php?view=pending&highlight_request=' . $notificationRequestId,
        'message' => $notificationRequestNumber . ' from ' . $notificationResidentName . ' is waiting for review.',
        'date' => $notificationDate,
    ];
}

$approvedRequests = [];
$approvedRequestsSql = "SELECT
        r.*,
        u.username,
        res.firstName,
        res.middleName,
        res.lastName,
        res.suffix,
        res.dateOfBirth,
        res.sex,
        res.civilStatus,
        res.emailAddress,
        res.phoneNumber,
        res.fullAddress
    FROM document_requests r
    LEFT JOIN users u ON r.user_id = u.userId
    LEFT JOIN residents res ON u.residentId = res.residentId
    WHERE r.status IN ('approved', 'printed')
    ORDER BY COALESCE(r.reviewed_at, r.date_updated) DESC, r.request_id DESC";
$approvedRequestsResult = mysqli_query($conn, $approvedRequestsSql);
if ($approvedRequestsResult) {
    while ($row = mysqli_fetch_assoc($approvedRequestsResult)) {
        $approvedRequests[] = $row;
    }
}

$reportDocumentTypeOptions = [];
$reportDocumentTypeSql = "SELECT DISTINCT document_type FROM document_requests WHERE TRIM(document_type) <> '' ORDER BY document_type ASC";
$reportDocumentTypeResult = mysqli_query($conn, $reportDocumentTypeSql);
if ($reportDocumentTypeResult) {
    while ($row = mysqli_fetch_assoc($reportDocumentTypeResult)) {
        $reportDocumentTypeOptions[] = $row['document_type'];
    }
}

$reportStartInput = staffValidDateInput($_GET['report_start'] ?? '');
$reportEndInput = staffValidDateInput($_GET['report_end'] ?? '');
[$reportStartDate, $reportEndDate] = staffReportDateRange($reportPeriod, $reportStartInput, $reportEndInput);
$reportRangeLabel = staffReportRangeLabel($reportPeriod, $reportStartDate, $reportEndDate, $reportPeriodOptions);
$reportGeneratedAt = date('F d, Y h:i A');

$reportWhere = [];
$reportParams = [];
$reportTypes = '';
if ($reportStartDate !== '') {
    $reportWhere[] = "r.date_submitted >= ?";
    $reportParams[] = $reportStartDate . ' 00:00:00';
    $reportTypes .= 's';
}
if ($reportEndDate !== '') {
    $reportWhere[] = "r.date_submitted <= ?";
    $reportParams[] = $reportEndDate . ' 23:59:59';
    $reportTypes .= 's';
}
if ($reportStatusFilter !== 'all') {
    $reportWhere[] = "r.status = ?";
    $reportParams[] = $reportStatusFilter;
    $reportTypes .= 's';
}
if ($reportDocumentTypeFilter !== 'all') {
    $reportWhere[] = "r.document_type = ?";
    $reportParams[] = $reportDocumentTypeFilter;
    $reportTypes .= 's';
}
$reportWhereSql = $reportWhere ? 'WHERE ' . implode(' AND ', $reportWhere) : '';

$reportStats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'printed' => 0,
    'released' => 0,
    'rejected' => 0,
];
$reportStatsSql = "SELECT
        COUNT(*) AS total_count,
        SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN r.status = 'printed' THEN 1 ELSE 0 END) AS printed_count,
        SUM(CASE WHEN r.status = 'released' THEN 1 ELSE 0 END) AS released_count,
        SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
    FROM document_requests r
    $reportWhereSql";
$reportStatsStmt = mysqli_prepare($conn, $reportStatsSql);
if ($reportStatsStmt && $reportTypes !== '') {
    mysqli_stmt_bind_param($reportStatsStmt, $reportTypes, ...$reportParams);
}
if ($reportStatsStmt && mysqli_stmt_execute($reportStatsStmt)) {
    $reportStatsResult = mysqli_stmt_get_result($reportStatsStmt);
    if ($row = mysqli_fetch_assoc($reportStatsResult)) {
        $reportStats = [
            'total' => (int) $row['total_count'],
            'pending' => (int) $row['pending_count'],
            'approved' => (int) $row['approved_count'],
            'printed' => (int) $row['printed_count'],
            'released' => (int) $row['released_count'],
            'rejected' => (int) $row['rejected_count'],
        ];
    }
    mysqli_stmt_close($reportStatsStmt);
}

$reportRows = [];
$reportSql = "SELECT
        r.request_id,
        r.request_number,
        r.document_type,
        r.purpose,
        r.status,
        r.date_submitted,
        r.reviewed_at,
        u.username,
        res.firstName,
        res.lastName
    FROM document_requests r
    LEFT JOIN users u ON r.user_id = u.userId
    LEFT JOIN residents res ON u.residentId = res.residentId
    $reportWhereSql
    ORDER BY r.date_submitted DESC, r.request_id DESC";
$reportStmt = mysqli_prepare($conn, $reportSql);
if ($reportStmt && $reportTypes !== '') {
    mysqli_stmt_bind_param($reportStmt, $reportTypes, ...$reportParams);
}
if ($reportStmt && mysqli_stmt_execute($reportStmt)) {
    $reportResult = mysqli_stmt_get_result($reportStmt);
    while ($row = mysqli_fetch_assoc($reportResult)) {
        $reportRows[] = $row;
    }
    mysqli_stmt_close($reportStmt);
}

$monthlyReportRows = [];
$monthlyReportSql = "SELECT
        DATE_FORMAT(r.date_submitted, '%Y-%m') AS report_month,
        COUNT(*) AS total_count,
        SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN r.status = 'printed' THEN 1 ELSE 0 END) AS printed_count,
        SUM(CASE WHEN r.status = 'released' THEN 1 ELSE 0 END) AS released_count,
        SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
    FROM document_requests r
    $reportWhereSql
    GROUP BY DATE_FORMAT(r.date_submitted, '%Y-%m')
    ORDER BY report_month DESC";
$monthlyReportStmt = mysqli_prepare($conn, $monthlyReportSql);
if ($monthlyReportStmt && $reportTypes !== '') {
    mysqli_stmt_bind_param($monthlyReportStmt, $reportTypes, ...$reportParams);
}
if ($monthlyReportStmt && mysqli_stmt_execute($monthlyReportStmt)) {
    $monthlyReportResult = mysqli_stmt_get_result($monthlyReportStmt);
    while ($row = mysqli_fetch_assoc($monthlyReportResult)) {
        $monthlyReportRows[] = $row;
    }
    mysqli_stmt_close($monthlyReportStmt);
}

$templates = getDocumentTemplates($conn);

$activityLogs = [];
$activityStmt = mysqli_prepare($conn, "SELECT user_id, username, role, action, description, created_at FROM activity_logs WHERE user_id = ? AND role = 'staff' ORDER BY created_at DESC LIMIT 12");
if ($activityStmt) {
    $staffUserId = (int) $_SESSION['user_id'];
    mysqli_stmt_bind_param($activityStmt, "i", $staffUserId);
    mysqli_stmt_execute($activityStmt);
    $activityResult = mysqli_stmt_get_result($activityStmt);
    while ($row = mysqli_fetch_assoc($activityResult)) {
        $activityLogs[] = $row;
    }
    mysqli_stmt_close($activityStmt);
}

$complaints = [];
$complaintsTableReady = $conn ? staffTableExists($conn, 'complaints') : false;
if ($complaintsTableReady) {
    $complaintsSql = "SELECT
            complaint_id,
            categories,
            location,
            description,
            attachment_name,
            attachment_path,
            status,
            created_at,
            updated_at,
            completed_at
        FROM complaints
        ORDER BY
            CASE WHEN status = 'pending' THEN 0 ELSE 1 END,
            created_at DESC,
            complaint_id DESC";
    $complaintsResult = mysqli_query($conn, $complaintsSql);
    if ($complaintsResult) {
        while ($row = mysqli_fetch_assoc($complaintsResult)) {
            $complaints[] = $row;
        }
    }
}

foreach (array_slice($complaints, 0, 8) as $notificationComplaint) {
    $notificationComplaintStatus = strtolower((string) ($notificationComplaint['status'] ?? 'pending'));
    if ($notificationComplaintStatus !== 'pending') {
        continue;
    }

    $notificationComplaintId = (int) ($notificationComplaint['complaint_id'] ?? 0);
    $notificationComplaintNo = 'CMP-' . str_pad((string) $notificationComplaintId, 4, '0', STR_PAD_LEFT);
    $notificationComplaintCategory = trim((string) ($notificationComplaint['categories'] ?? ''));
    $notificationComplaintDate = (string) ($notificationComplaint['created_at'] ?? '');
    $staffNotifications[] = [
        'key' => 'staff-complaint:' . $notificationComplaintId . ':' . $notificationComplaintDate,
        'href' => 'staff.php?view=complaints&highlight_complaint=' . $notificationComplaintId,
        'message' => $notificationComplaintNo . ' - ' . ($notificationComplaintCategory !== '' ? $notificationComplaintCategory : 'General Complaint') . ' is waiting for review.',
        'date' => $notificationComplaintDate,
    ];
}

usort($staffNotifications, function ($left, $right) {
    return strtotime($right['date'] ?? '') <=> strtotime($left['date'] ?? '');
});
$staffNotifications = array_slice($staffNotifications, 0, 10);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="../css/staff.css?v=20261009-admin-nav-3">
    <link rel="stylesheet" href="../css/release-confirm.css?v=20261007-1">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css" integrity="sha512-t7Few9xlddEmgd3oKZQahkNI4dS6l80+eGEzFQiqtyVYdvcSG2D3Iub77R20BdotfRPA9caaRkg1tyaJiPmO0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <?php displayFlashMessages(); ?>

    <section id="section-header">
      <header class="site-header">
        <div class="container header-container">
          <div class="header-left">
            <h1 class="site-title">Staff Portal</h1>
            <p class="site-subtitle">Welcome, <?php echo htmlspecialchars($displayName); ?></p>
          </div>
          <div class="header-right">
            <div class="notification-wrap">
              <button class="icon-btn notification-toggle" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="staffNotificationMenu">
                <i class="bi bi-bell"></i>
                <span class="notification-badge" data-notification-badge hidden>0</span>
              </button>
              <div class="notification-menu" id="staffNotificationMenu" hidden>
                <div class="notification-menu-header">
                  <strong>Notifications</strong>
                </div>
                <div class="notification-list" data-notification-list>
                  <?php if (empty($staffNotifications)): ?>
                    <div class="notification-empty">No new pending requests or complaints.</div>
                  <?php else: ?>
                    <?php foreach ($staffNotifications as $notification): ?>
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
            <button class="icon-btn" type="button" aria-label="Account" title="Account settings" data-modal-target="editSelfModal">
              <i class="bi bi-person-circle"></i>
            </button>
            <a class="logout-btn" href="../auth/logout.php" aria-label="Logout" title="Logout">
              <i class="bi bi-box-arrow-right"></i>
              <span>Logout</span>
            </a>
          </div>
        </div>
      </header>
    </section>

    <section id="section-stats">
      <div class="container">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-info">
              <span class="stat-label">Total Requests</span>
              <span class="stat-value" style="color: rgba(28, 57, 142, 0.4);"><?php echo (int) $stats['total']; ?></span>
            </div>
            <div class="stat-icon">
              <i class="bi bi-file-earmark-text"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-info">
              <span class="stat-label">Pending</span>
              <span class="stat-value" style="color: rgba(126, 42, 12, 0.4);"><?php echo (int) $stats['pending']; ?></span>
            </div>
            <div class="stat-icon">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-info">
              <span class="stat-label">Approved</span>
              <span class="stat-value" style="color: #0d542b;"><?php echo (int) $stats['approved']; ?></span>
            </div>
            <div class="stat-icon">
              <i class="bi bi-check-circle"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-info">
              <span class="stat-label">Printed</span>
              <span class="stat-value" style="color: #7c3aed;"><?php echo (int) $stats['printed']; ?></span>
            </div>
            <div class="stat-icon">
              <i class="bi bi-printer"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-info">
              <span class="stat-label">Released</span>
              <span class="stat-value" style="color: #1c398e;"><?php echo (int) $stats['released']; ?></span>
            </div>
            <div class="stat-icon">
              <i class="bi bi-send-check"></i>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="section-tabs">
      <div class="container">
        <div class="admin-nav-controls">
          <button class="admin-nav-arrow" type="button" data-staff-nav-scroll="left" aria-label="Scroll staff sections left" aria-controls="staffTabsMenu" hidden>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
          <nav class="admin-nav" id="staffTabsMenu" aria-label="Staff sections">
            <button type="button" class="nav-link active" data-panel="pending">
              <i class="bi bi-clock-history"></i>
              <span>Pending Request</span>
            </button>
            <button type="button" class="nav-link" data-panel="approved">
              <i class="bi bi-file-earmark-check"></i>
              <span>Approved Documents</span>
            </button>
            <button type="button" class="nav-link" data-panel="reports">
              <i class="bi bi-printer"></i>
              <span>Print Reports</span>
            </button>
            <button type="button" class="nav-link" data-panel="activity">
              <i class="bi bi-activity"></i>
              <span>Activity Log</span>
            </button>
            <button type="button" class="nav-link" data-panel="templates">
              <i class="bi bi-file-earmark-text"></i>
              <span>Document Templates</span>
            </button>
            <button type="button" class="nav-link" data-panel="complaints">
              <div class="merged-icon-wrapper">
                <i class="bi bi-exclamation-circle icon-base"></i>
                <i class="bi bi-chat-dots icon-overlay"></i>
              </div>
              <span>Complaints</span>
            </button>
            <a class="nav-link mobile-logout-nav" href="../auth/logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Logout</span>
            </a>
          </nav>
          <button class="admin-nav-arrow" type="button" data-staff-nav-scroll="right" aria-label="Scroll staff sections right" aria-controls="staffTabsMenu" hidden>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </section>

    <section id="section-list">
      <div class="container">
        <div class="staff-panel" id="staff-panel-pending">
          <div class="staff-card">
            <div class="section-card-heading">
              <div>
                <h2>Pending Requests</h2>
                <p>Review pending document requests from residents.</p>
              </div>
            </div>
            <div class="table-wrap">
              <table class="requests-table">
                <thead>
                  <tr>
                    <th>Request Number</th>
                    <th>Resident Name</th>
                    <th>Document Type</th>
                    <th>Purpose</th>
                    <th>Date Requested</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($pendingRequests)): ?>
                    <tr>
                      <td colspan="7" class="empty-cell">No pending requests at the moment.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($pendingRequests as $request): ?>
                      <?php
                        $requestId = (int) ($request['request_id'] ?? 0);
                        $requestNumber = $request['request_number'] ?: staffRequestNumber($requestId, $request['date_submitted']);
                        $residentName = trim(($request['firstName'] ?? '') . ' ' . ($request['lastName'] ?? ''));
                        $userName = $residentName ?: ($request['emailAddress'] ?: ($request['username'] ?? 'Unknown User'));
                        $dateRequested = $request['date_submitted'];
                        $requestModalId = 'staffRequestReviewModal' . $requestId;
                        $requestRejectModalId = 'staffRejectRequestModal' . $requestId;
                        $requestFiles = fetchStaffRequestFiles($conn, $requestId);
                        $approveRedirect = http_build_query(['view' => 'approved']);
                        $pendingRedirect = http_build_query(['view' => 'pending']);
                      ?>
                      <tr id="staff-request-<?php echo $requestId; ?>" data-request-id="<?php echo $requestId; ?>">
                        <td><?php echo htmlspecialchars($requestNumber); ?></td>
                        <td><span class="user-link"><?php echo htmlspecialchars($userName); ?></span></td>
                        <td><?php echo htmlspecialchars($request['document_type'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($request['purpose'] ?: 'Not specified'); ?></td>
                        <td><?php echo htmlspecialchars($dateRequested ? date('m/d/Y', strtotime($dateRequested)) : 'N/A'); ?></td>
                        <td>
                          <span class="status-pill pending"><?php echo htmlspecialchars(staffStatusLabel($request['status'])); ?></span>
                        </td>
                        <td>
                          <button type="button" class="verify-button open-modal-button" data-modal-target="<?php echo htmlspecialchars($requestModalId); ?>">
                            <i class="bi bi-eye"></i>
                            <span>View &amp; Verify</span>
                          </button>
                          <div class="modal-backdrop" id="<?php echo htmlspecialchars($requestModalId); ?>" aria-hidden="true" hidden data-allow-backdrop="false">
                            <div class="modal-panel request-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($requestModalId); ?>Title">
                              <div class="modal-header">
                                <div>
                                  <h3 id="<?php echo htmlspecialchars($requestModalId); ?>Title"><?php echo htmlspecialchars($requestNumber); ?></h3>
                                  <p><?php echo htmlspecialchars($userName); ?> - <?php echo htmlspecialchars(staffStatusLabel($request['status'])); ?></p>
                                </div>
                                <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                              </div>
                              <div class="modal-form request-modal-content">
                                <div class="details-grid">
                                  <div>
                                    <span>Resident</span>
                                    <strong><?php echo htmlspecialchars($userName); ?></strong>
                                  </div>
                                  <div>
                                    <span>Email</span>
                                    <strong><?php echo htmlspecialchars($request['emailAddress'] ?: 'Not provided'); ?></strong>
                                  </div>
                                  <div>
                                    <span>Phone</span>
                                    <strong><?php echo htmlspecialchars($request['phoneNumber'] ?: 'Not provided'); ?></strong>
                                  </div>
                                  <div>
                                    <span>Status</span>
                                    <strong><?php echo htmlspecialchars(staffStatusLabel($request['status'])); ?></strong>
                                  </div>
                                  <div>
                                    <span>Document Type</span>
                                    <strong><?php echo htmlspecialchars($request['document_type'] ?? 'N/A'); ?></strong>
                                  </div>
                                  <div>
                                    <span>Date Requested</span>
                                    <strong><?php echo htmlspecialchars($dateRequested ? date('m/d/Y', strtotime($dateRequested)) : 'N/A'); ?></strong>
                                  </div>
                                  <div class="wide">
                                    <span>Address</span>
                                    <strong><?php echo htmlspecialchars($request['fullAddress'] ?: 'Not provided'); ?></strong>
                                  </div>
                                  <div class="wide">
                                    <span>Purpose</span>
                                    <strong><?php echo htmlspecialchars($request['purpose'] ?: 'Not specified'); ?></strong>
                                  </div>
                                </div>

                                <?php echo renderDocumentRequestFillUpData($request['fill_up_data'] ?? ''); ?>

                                <div class="requirements-box">
                                  <span>Uploaded Requirements</span>
                                  <?php if (empty($requestFiles)): ?>
                                    <p>No uploaded files attached to this request.</p>
                                  <?php else: ?>
                                    <div class="file-list">
                                      <?php foreach ($requestFiles as $file): ?>
                                        <?php
                                          $fileName = $file['file_name'] ?: basename((string) $file['file_path']);
                                          $filePath = '../actions/download.php?path=' . rawurlencode(ltrim((string) $file['file_path'], '/\\'));
                                        ?>
                                        <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank" rel="noopener">
                                          <i class="bi bi-paperclip"></i>
                                          <?php echo htmlspecialchars($fileName); ?>
                                        </a>
                                      <?php endforeach; ?>
                                    </div>
                                  <?php endif; ?>
                                </div>

                                <div class="review-actions">
                                  <form method="POST" action="../actions/updateRequestStatus.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                                    <input type="hidden" name="redirect_query" value="<?php echo htmlspecialchars($approveRedirect); ?>">
                                    <button type="submit" name="status" value="approved" class="add-user-button approve-submit"><i class="bi bi-check-circle"></i>Approve</button>
                                  </form>
                                  <button type="button" class="add-user-button reject-submit open-modal-button" data-modal-target="<?php echo htmlspecialchars($requestRejectModalId); ?>">
                                    <i class="bi bi-x-circle"></i>Reject
                                  </button>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="modal-backdrop" id="<?php echo htmlspecialchars($requestRejectModalId); ?>" aria-hidden="true" hidden>
                            <div class="modal-panel reject-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($requestRejectModalId); ?>Title">
                              <div class="modal-header">
                                <div>
                                  <h3 id="<?php echo htmlspecialchars($requestRejectModalId); ?>Title">Reject Request</h3>
                                  <p><?php echo htmlspecialchars($requestNumber); ?></p>
                                </div>
                                <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                              </div>
                              <form method="POST" action="../actions/updateRequestStatus.php" class="modal-form reject-review-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                                <input type="hidden" name="redirect_query" value="<?php echo htmlspecialchars($pendingRedirect); ?>">
                                <label for="staffRequestRejectReason<?php echo $requestId; ?>">Reason</label>
                                <textarea id="staffRequestRejectReason<?php echo $requestId; ?>" name="admin_remarks" rows="4" placeholder="Example: Missing or invalid uploaded requirement." required></textarea>
                                <div class="reject-modal-actions">
                                  <button type="button" class="modal-secondary-button" data-modal-close>Cancel</button>
                                  <button type="submit" name="status" value="rejected" class="add-user-button reject-submit"><i class="bi bi-x-circle"></i>Submit</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="staff-panel" id="staff-panel-approved" hidden>
          <div class="staff-card">
            <div class="section-card-heading">
              <div>
                <h2>Approved Documents</h2>
                <p>Approved and printed document requests ready for the next step.</p>
              </div>
            </div>
            <div class="table-wrap">
              <table class="requests-table">
                <thead>
                  <tr>
                    <th>Request Number</th>
                    <th>Resident Name</th>
                    <th>Document Type</th>
                    <th>Purpose</th>
                    <th>Processed Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($approvedRequests)): ?>
                    <tr>
                      <td colspan="7" class="empty-cell">No approved or printed documents found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($approvedRequests as $request): ?>
                      <?php
                        $requestId = (int) ($request['request_id'] ?? 0);
                        $requestNumber = $request['request_number'] ?: staffRequestNumber($requestId, $request['date_submitted']);
                        $residentName = trim(($request['firstName'] ?? '') . ' ' . ($request['lastName'] ?? ''));
                        $userName = $residentName ?: ($request['emailAddress'] ?: ($request['username'] ?? 'Unknown User'));
                        $approvedDate = $request['reviewed_at'] ?: $request['date_updated'];
                        $requestStatus = strtolower((string) ($request['status'] ?? 'approved'));
                        $approvedModalId = 'staffApprovedDocModal' . $requestId;
                        $requestFiles = fetchStaffRequestFiles($conn, $requestId);
                        $approvedRedirect = http_build_query(['view' => 'approved']);
                        $certificateAddress = trim((string) ($request['fullAddress'] ?? 'Barangay Krus na Ligas, Quezon City'));
                        $certificateDate = $approvedDate ? date('F d, Y', strtotime($approvedDate)) : date('F d, Y');
                        $certificateData = buildResidentDocumentData([
                          'full_name' => $userName,
                          'first_name' => $request['firstName'] ?? '',
                          'middle_name' => $request['middleName'] ?? '',
                          'last_name' => $request['lastName'] ?? '',
                          'suffix' => $request['suffix'] ?? '',
                          'birthdate' => $request['dateOfBirth'] ?? '',
                          'sex' => $request['sex'] ?? '',
                          'civil_status' => $request['civilStatus'] ?? '',
                          'address' => $certificateAddress,
                        ], [
                          'document_type' => $request['document_type'] ?? 'Barangay Certificate',
                          'purpose' => $request['purpose'] ?? 'official purposes',
                          'fill_up_data' => $request['fill_up_data'] ?? '',
                          'request_number' => $requestNumber,
                          'date' => $certificateDate,
                        ]);
                        $requestTemplate = getDocumentTemplateForRequest($conn, $request);
                        $templateContent = trim((string) ($requestTemplate['template_content'] ?? ''));
                        $certificate = $templateContent !== ''
                          ? buildBarangayDocumentCertificateFromTemplate($templateContent, $certificateData)
                          : buildBarangayDocumentCertificate($certificateData);
                        $certificateTitle = $certificate['title'];
                        $certificatePreviewId = 'staffCertificatePreview' . $requestId;
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars($requestNumber); ?></td>
                        <td><span class="user-link"><?php echo htmlspecialchars($userName); ?></span></td>
                        <td><?php echo htmlspecialchars($request['document_type'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($request['purpose'] ?: 'Not specified'); ?></td>
                        <td><?php echo htmlspecialchars($approvedDate ? date('m/d/Y', strtotime($approvedDate)) : 'N/A'); ?></td>
                        <td>
                          <span class="status-pill <?php echo htmlspecialchars($requestStatus); ?>">
                            <?php echo htmlspecialchars(staffStatusLabel($requestStatus)); ?>
                          </span>
                        </td>
                        <td>
                          <div class="table-actions">
                            <button type="button" class="verify-button open-modal-button" data-modal-target="<?php echo htmlspecialchars($approvedModalId); ?>">
                              <i class="bi bi-eye"></i>
                              <span>View</span>
                            </button>
                            <?php if (in_array($requestStatus, ['approved', 'printed'], true)): ?>
                              <form method="POST" action="../actions/updateRequestStatus.php" data-confirm-release>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                                <input type="hidden" name="redirect_query" value="<?php echo htmlspecialchars($approvedRedirect); ?>">
                                <button type="submit" name="status" value="released" class="verify-button release-inline">
                                  <i class="bi bi-send-check"></i>
                                  <span>Mark Released</span>
                                </button>
                              </form>
                            <?php endif; ?>
                          </div>
                          <div class="modal-backdrop" id="<?php echo htmlspecialchars($approvedModalId); ?>" aria-hidden="true" hidden data-allow-backdrop="false">
                            <div class="modal-panel request-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($approvedModalId); ?>Title">
                              <div class="modal-header">
                                <div>
                                  <h3 id="<?php echo htmlspecialchars($approvedModalId); ?>Title"><?php echo htmlspecialchars($requestNumber); ?></h3>
                                  <p><?php echo htmlspecialchars($userName); ?> - <?php echo htmlspecialchars($request['document_type'] ?? 'Document'); ?></p>
                                </div>
                                <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                              </div>
                              <div class="modal-form request-modal-content">
                                <div class="certificate-preview-shell">
                                  <div class="certificate-toolbar">
                                    <span>Generated printable preview</span>
                                    <button
                                      type="button"
                                      class="verify-button print-certificate-button"
                                      data-print-target="<?php echo htmlspecialchars($certificatePreviewId); ?>"
                                      data-request-id="<?php echo $requestId; ?>"
                                      data-current-status="<?php echo htmlspecialchars($requestStatus); ?>"
                                      data-csrf-token="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                      data-redirect-query="<?php echo htmlspecialchars($approvedRedirect); ?>">
                                      <i class="bi bi-printer"></i>
                                      <span>Print</span>
                                    </button>
                                  </div>
                                  <div class="certificate-sheet approved-certificate" id="<?php echo htmlspecialchars($certificatePreviewId); ?>">
                                    <div class="certificate-accent-bar"></div>
                                    <header class="certificate-letterhead">
                                      <img class="certificate-logo certificate-logo-knl" src="../assets/template-logos/knl-logo.jpg" alt="Barangay Krus na Ligas seal">
                                      <div class="certificate-heading">
                                        <p>Republic of the Philippines</p>
                                        <p>Quezon City, Metro Manila</p>
                                        <h3>Barangay Krus na Ligas</h3>
                                      </div>
                                      <img class="certificate-logo certificate-logo-qc" src="../assets/template-logos/lungsod.jpg" alt="Lungsod Quezon logo">
                                    </header>
                                    <div class="certificate-document-title">
                                      <h2><?php echo htmlspecialchars($certificateTitle); ?></h2>
                                    </div>
                                    <div class="certificate-body">
                                      <?php echo sanitizeTemplateHtml($certificate['body_html'] ?? ''); ?>
                                    </div>
                                    <div class="certificate-signature">
                                      <strong>RENIEL JOHN C. PERDIDO</strong>
                                      <span>Punong Barangay</span>
                                    </div>
                                    <footer class="certificate-footer">
                                      <span><i class="bi bi-telephone"></i> 0962-3962951 / 0287110367</span>
                                      <span><i class="bi bi-envelope"></i> brgy.krusnaligas@quezoncity.gov.ph</span>
                                      <span><i class="bi bi-geo-alt"></i> 20 Plaza Sta. Ines Street</span>
                                      <img src="../assets/template-logos/bagong-pilipinas.webp" alt="Bagong Pilipinas">
                                    </footer>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="staff-panel" id="staff-panel-reports" hidden>
          <div class="staff-card report-card" id="printableStaffReport">
            <div class="section-card-heading report-heading-row">
              <div>
                <h2>Printable Request Report</h2>
                <p><?php echo htmlspecialchars($reportRangeLabel); ?> request summary generated on <?php echo htmlspecialchars($reportGeneratedAt); ?></p>
              </div>
              <button type="button" class="add-user-button report-print-button" data-print-report>
                <i class="bi bi-printer"></i>Print Report
              </button>
            </div>

            <form class="report-filter-panel" method="GET">
              <input type="hidden" name="view" value="reports">
              <div>
                <label>Period</label>
                <select name="report_period" onchange="this.form.submit()">
                  <?php foreach ($reportPeriodOptions as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $reportPeriod === $value ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($label); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>From</label>
                <input type="date" name="report_start" value="<?php echo htmlspecialchars($reportStartDate); ?>">
              </div>
              <div>
                <label>To</label>
                <input type="date" name="report_end" value="<?php echo htmlspecialchars($reportEndDate); ?>">
              </div>
              <div>
                <label>Status</label>
                <select name="report_status">
                  <?php foreach ($reportStatusOptions as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $reportStatusFilter === $value ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($label); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Document</label>
                <select name="report_document_type">
                  <option value="all" <?php echo $reportDocumentTypeFilter === 'all' ? 'selected' : ''; ?>>All Documents</option>
                  <?php foreach ($reportDocumentTypeOptions as $documentTypeOption): ?>
                    <option value="<?php echo htmlspecialchars($documentTypeOption); ?>" <?php echo $reportDocumentTypeFilter === $documentTypeOption ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($documentTypeOption); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" class="add-user-button"><i class="bi bi-funnel"></i>Apply</button>
            </form>

            <div class="report-print-meta">
              <span><strong>Coverage:</strong> <?php echo htmlspecialchars($reportRangeLabel); ?></span>
              <span><strong>Status:</strong> <?php echo htmlspecialchars($reportStatusOptions[$reportStatusFilter] ?? 'All Statuses'); ?></span>
              <span><strong>Document:</strong> <?php echo htmlspecialchars($reportDocumentTypeFilter === 'all' ? 'All Documents' : $reportDocumentTypeFilter); ?></span>
            </div>

            <div class="report-summary-grid">
              <div><span>Total Requests</span><strong><?php echo $reportStats['total']; ?></strong></div>
              <div><span>Pending</span><strong><?php echo $reportStats['pending']; ?></strong></div>
              <div><span>Approved</span><strong><?php echo $reportStats['approved']; ?></strong></div>
              <div><span>Printed</span><strong><?php echo $reportStats['printed']; ?></strong></div>
              <div><span>Released</span><strong><?php echo $reportStats['released']; ?></strong></div>
              <div><span>Rejected</span><strong><?php echo $reportStats['rejected']; ?></strong></div>
            </div>

            <div class="report-subsection">
              <h3>Monthly Summary</h3>
              <div class="table-wrap">
                <table class="requests-table monthly-report-table">
                  <thead>
                    <tr>
                      <th>Month</th>
                      <th>Total</th>
                      <th>Pending</th>
                      <th>Approved</th>
                      <th>Printed</th>
                      <th>Released</th>
                      <th>Rejected</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($monthlyReportRows)): ?>
                      <tr><td colspan="7" class="empty-cell">No report data available.</td></tr>
                    <?php else: ?>
                      <?php foreach ($monthlyReportRows as $monthRow): ?>
                        <tr>
                          <td><?php echo htmlspecialchars(date('F Y', strtotime($monthRow['report_month'] . '-01'))); ?></td>
                          <td><?php echo (int) $monthRow['total_count']; ?></td>
                          <td><?php echo (int) $monthRow['pending_count']; ?></td>
                          <td><?php echo (int) $monthRow['approved_count']; ?></td>
                          <td><?php echo (int) $monthRow['printed_count']; ?></td>
                          <td><?php echo (int) $monthRow['released_count']; ?></td>
                          <td><?php echo (int) $monthRow['rejected_count']; ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="report-subsection">
              <h3>Request List</h3>
              <div class="table-wrap">
                <table class="requests-table report-request-table">
                  <thead>
                    <tr>
                      <th>Request Number</th>
                      <th>Resident</th>
                      <th>Document Type</th>
                      <th>Status</th>
                      <th>Date Submitted</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($reportRows)): ?>
                      <tr><td colspan="5" class="empty-cell">No requests found.</td></tr>
                    <?php else: ?>
                      <?php foreach ($reportRows as $reportRow): ?>
                        <?php
                          $reportRequestId = (int) ($reportRow['request_id'] ?? 0);
                          $reportRequestNumber = $reportRow['request_number'] ?: staffRequestNumber($reportRequestId, $reportRow['date_submitted'] ?? '');
                          $reportResidentName = trim(($reportRow['firstName'] ?? '') . ' ' . ($reportRow['lastName'] ?? ''));
                          $reportResidentName = $reportResidentName ?: ($reportRow['username'] ?? 'Unknown User');
                          $reportStatus = strtolower((string) ($reportRow['status'] ?? 'pending'));
                        ?>
                        <tr>
                          <td><?php echo htmlspecialchars($reportRequestNumber); ?></td>
                          <td><?php echo htmlspecialchars($reportResidentName); ?></td>
                          <td><?php echo htmlspecialchars($reportRow['document_type'] ?? 'Document'); ?></td>
                          <td><span class="status-pill <?php echo htmlspecialchars($reportStatus); ?>"><?php echo htmlspecialchars(staffStatusLabel($reportStatus)); ?></span></td>
                          <td><?php echo htmlspecialchars(!empty($reportRow['date_submitted']) ? date('m/d/Y', strtotime($reportRow['date_submitted'])) : 'N/A'); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="staff-panel" id="staff-panel-activity" hidden>
          <div class="staff-card">
            <div class="section-card-heading">
              <div>
                <h2>Activity Log</h2>
                <p>Track recent system activity and staff actions.</p>
              </div>
            </div>
            <div class="activity-list">
              <?php if (!empty($activityLogs)): ?>
                <?php foreach ($activityLogs as $activity): ?>
                  <?php
                    $activityAction = strtolower((string) ($activity['action'] ?? 'activity'));
                    $activityIcon = 'bi-activity';
                    $activityBadgeClass = 'badge-info';
                    $activityBadgeText = 'Activity';
                    $activityStatusText = 'Info';

                    if ($activityAction === 'login') {
                      $activityIcon = 'bi-box-arrow-in-right';
                      $activityBadgeClass = 'badge-success';
                      $activityBadgeText = 'Login';
                      $activityStatusText = 'Success';
                    } elseif ($activityAction === 'logout') {
                      $activityIcon = 'bi-box-arrow-right';
                      $activityBadgeClass = 'badge-info';
                      $activityBadgeText = 'Logout';
                      $activityStatusText = 'Info';
                    } elseif (strpos($activityAction, 'request_') === 0) {
                      $activityIcon = 'bi-file-earmark-check';
                      $activityBadgeClass = 'badge-success';
                      $activityBadgeText = 'Request';
                      $activityStatusText = 'Success';
                    } elseif (strpos($activityAction, 'verification_') === 0) {
                      $activityIcon = 'bi-person-check';
                      $activityBadgeClass = 'badge-success';
                      $activityBadgeText = 'Verification';
                      $activityStatusText = 'Success';
                    } elseif (strpos($activityAction, 'complaint_') === 0) {
                      $activityIcon = 'bi-chat-dots';
                      $activityBadgeClass = 'badge-info';
                      $activityBadgeText = 'Complaint';
                      $activityStatusText = 'Info';
                    }
                  ?>
                  <article class="activity-card">
                    <div class="activity-card-main">
                      <div class="activity-icon">
                        <i class="bi <?php echo htmlspecialchars($activityIcon); ?>"></i>
                      </div>
                      <div class="activity-info">
                        <div class="activity-meta">
                          <span class="activity-user"><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></span>
                          <span class="separator">•</span>
                          <span class="activity-type"><?php echo htmlspecialchars($activityBadgeText); ?></span>
                        </div>
                        <p class="activity-desc"><?php echo htmlspecialchars(formatActivityLogDescription($conn, $activity['description'] ?? 'System activity')); ?></p>
                        <div class="activity-time">
                          <i class="bi bi-clock"></i>
                          <time><?php echo htmlspecialchars(date('M d, Y, h:i A', strtotime($activity['created_at']))); ?></time>
                        </div>
                      </div>
                    </div>
                    <div class="activity-status">
                      <span class="badge <?php echo htmlspecialchars($activityBadgeClass); ?>"><?php echo htmlspecialchars($activityStatusText); ?></span>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="activity-empty">No activity recorded yet.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="staff-panel" id="staff-panel-templates" hidden>
          <div class="staff-card">
            <div class="section-card-heading">
              <div>
                <h2>Document Templates</h2>
                <p>Manage barangay document templates and requirements</p>
              </div>
              <button type="button" class="btn-primary open-template-modal" data-template-modal="createTemplateModal">
                <i class="bi bi-plus-lg"></i>
                <span>Create Template</span>
              </button>
            </div>
            <div class="cards-grid">
              <?php if (!empty($templates)): ?>
                <?php foreach ($templates as $template): ?>
                  <?php
                    $templateId = (int) ($template['id'] ?? 0);
                    $fee = number_format((float) ($template['fee'] ?? 0), 2);
                    $lastModified = $template['last_modified'] ?: $template['created_at'];
                    $isFree = (float) ($template['fee'] ?? 0) === 0.0;
                    $isDefaultTemplate = !empty($template['is_default']);
                    $templateModalId = 'editTemplateModal' . $templateId;
                    $deleteModalId = 'deleteTemplateModal' . $templateId;
                    $previewModalId = ($isDefaultTemplate ? 'viewDefaultTemplateModal' : 'viewTemplateModal') . abs($templateId);
                  ?>
                  <article class="template-card">
                    <div class="card-header">
                      <div class="card-icon">
                        <i class="bi bi-file-earmark-text"></i>
                      </div>
                      <div class="card-title-group">
                        <h3><?php echo htmlspecialchars($template['name'] ?? 'Untitled'); ?></h3>
                        <span class="badge"><?php echo htmlspecialchars($template['category'] ?? 'General'); ?></span>
                      </div>
                    </div>
                    <p class="card-desc"><?php echo htmlspecialchars($template['description'] ?: 'Template description coming soon.'); ?></p>
                    <div class="card-details">
                      <div class="detail-labels">
                        <span>Fee:</span>
                        <span>Usage:</span>
                        <span>Last Modified:</span>
                      </div>
                      <div class="detail-values">
                        <span class="<?php echo $isFree ? 'text-blue' : 'text-green'; ?>"><?php echo $isFree ? 'Free' : '₱' . htmlspecialchars($fee); ?></span>
                        <span><?php echo (int) ($template['usage_count'] ?? 0); ?> times</span>
                        <span class="text-dark-gray"><?php echo htmlspecialchars(date('M d, Y', strtotime($lastModified))); ?></span>
                      </div>
                    </div>
                    <div class="card-actions">
                      <?php if ($isDefaultTemplate): ?>
                        <button type="button" class="btn-secondary btn-view open-template-modal" data-template-modal="<?php echo htmlspecialchars($previewModalId); ?>">
                          <i class="bi bi-eye"></i>
                          <span>View Content</span>
                        </button>
                        <button type="button" class="btn-icon open-template-modal" data-template-modal="<?php echo htmlspecialchars($templateModalId); ?>" aria-label="Edit">
                          <i class="bi bi-pencil"></i>
                        </button>
                      <?php elseif (!empty($template['file_path'])): ?>
                        <a class="btn-secondary btn-view" href="../actions/download.php?path=<?php echo rawurlencode(ltrim((string) $template['file_path'], '/\\')); ?>" target="_blank" rel="noopener">
                          <i class="bi bi-download"></i>
                          <span>View</span>
                        </a>
                        <button type="button" class="btn-icon open-template-modal" data-template-modal="<?php echo htmlspecialchars($templateModalId); ?>" aria-label="Edit">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn-icon open-template-modal" data-template-modal="<?php echo htmlspecialchars($deleteModalId); ?>" aria-label="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      <?php else: ?>
                        <button type="button" class="btn-secondary btn-view open-template-modal" data-template-modal="<?php echo htmlspecialchars($previewModalId); ?>">
                          <i class="bi bi-eye"></i>
                          <span>View Content</span>
                        </button>
                        <button type="button" class="btn-icon open-template-modal" data-template-modal="<?php echo htmlspecialchars($templateModalId); ?>" aria-label="Edit">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn-icon open-template-modal" data-template-modal="<?php echo htmlspecialchars($deleteModalId); ?>" aria-label="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </article>

                  <div class="modal-backdrop" id="<?php echo htmlspecialchars($previewModalId); ?>" aria-hidden="true" hidden>
                    <div class="modal-panel template-modal template-preview-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($previewModalId); ?>Title">
                      <div class="modal-header">
                        <div>
                          <h3 id="<?php echo htmlspecialchars($previewModalId); ?>Title"><?php echo htmlspecialchars($template['name'] ?? 'Untitled'); ?></h3>
                          <p><?php echo $isDefaultTemplate ? 'Built-in barangay template' : 'Saved template content'; ?></p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                      </div>
                      <div class="template-preview-frame">
                        <?php staffTemplatePreview($template['name'] ?? '', $template['template_content'] ?? ''); ?>
                      </div>
                    </div>
                  </div>

                  <div class="modal-backdrop" id="<?php echo htmlspecialchars($templateModalId); ?>" aria-hidden="true" hidden>
                    <div class="modal-panel template-modal template-editor-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($templateModalId); ?>Title">
                      <div class="modal-header">
                        <div>
                          <h3 id="<?php echo htmlspecialchars($templateModalId); ?>Title"><?php echo $isDefaultTemplate ? 'Edit Built-in Template' : 'Edit Template'; ?></h3>
                          <p><?php echo htmlspecialchars($template['name'] ?? 'Untitled'); ?></p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                      </div>
                      <form class="modal-form template-form" method="POST" action="../actions/manageDocumentTemplate.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="<?php echo $isDefaultTemplate ? 'create' : 'edit'; ?>">
                        <?php if (!$isDefaultTemplate): ?>
                          <input type="hidden" name="template_id" value="<?php echo $templateId; ?>">
                        <?php endif; ?>
                        <div class="template-editor-layout">
                          <div class="template-editor-fields">
                            <label>Template Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($template['name'] ?? ''); ?>" required>
                            <label>Category</label>
                            <input type="text" name="category" value="<?php echo htmlspecialchars($template['category'] ?? ''); ?>" required>
                            <label>Description</label>
                            <textarea name="description" rows="3"><?php echo htmlspecialchars($template['description'] ?? ''); ?></textarea>
                            <label>Fee (₱)</label>
                            <input type="number" step="0.01" min="0" name="fee" value="<?php echo htmlspecialchars((string) ($template['fee'] ?? 0)); ?>">
                            <label>Required Documents</label>
                            <textarea name="requirements" rows="3" placeholder="One requirement per line, e.g. Government ID"><?php echo htmlspecialchars($template['requirements'] ?? ''); ?></textarea>
                            <label>Fill Up Fields</label>
                            <?php echo renderTemplateFillUpFieldsEditor($template['fill_up_fields'] ?? ''); ?>
                            <label>Template Body</label>
                            <?php echo renderTemplateBodyEditor($template['template_content'] ?? ''); ?>
                            <button type="submit" class="add-user-button"><i class="bi bi-check-circle"></i><?php echo $isDefaultTemplate ? 'Save as Template' : 'Save Changes'; ?></button>
                          </div>
                          <aside class="template-live-preview" data-template-live-preview>
                            <span class="template-live-preview-label"><i class="bi bi-eye"></i> Live template preview</span>
                            <?php staffTemplatePreview($template['name'] ?? '', $template['template_content'] ?? '', true); ?>
                          </aside>
                        </div>
                      </form>
                    </div>
                  </div>

                  <?php if (!$isDefaultTemplate): ?>
                  <div class="modal-backdrop" id="<?php echo htmlspecialchars($deleteModalId); ?>" aria-hidden="true" hidden>
                    <div class="modal-panel template-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($deleteModalId); ?>Title">
                      <div class="modal-header">
                        <div>
                          <h3 id="<?php echo htmlspecialchars($deleteModalId); ?>Title">Delete Template</h3>
                          <p><?php echo htmlspecialchars($template['name'] ?? 'Untitled'); ?></p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                      </div>
                      <form class="modal-form template-form" method="POST" action="../actions/manageDocumentTemplate.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="template_id" value="<?php echo $templateId; ?>">
                        <p class="delete-template-message">Are you sure you want to delete this template?</p>
                        <button type="submit" class="add-user-button reject-submit"><i class="bi bi-trash"></i>Delete Template</button>
                      </form>
                    </div>
                  </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="template-card empty-template-card">
                  <p>No templates available yet.</p>
                </div>
              <?php endif; ?>
            </div>

            <div class="modal-backdrop" id="createTemplateModal" aria-hidden="true" hidden>
              <div class="modal-panel template-modal template-editor-modal" role="dialog" aria-modal="true" aria-labelledby="createTemplateModalTitle">
                <div class="modal-header">
                  <div>
                    <h3 id="createTemplateModalTitle">Create Template</h3>
                    <p>Add document wording that can be reused for generated barangay documents.</p>
                  </div>
                  <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <form class="modal-form template-form" method="POST" action="../actions/manageDocumentTemplate.php">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="action" value="create">
                  <div class="template-editor-layout">
                    <div class="template-editor-fields">
                      <label>Template Name</label>
                      <input type="text" name="name" placeholder="e.g. Barangay Clearance" required>
                      <label>Category</label>
                      <input type="text" name="category" placeholder="e.g. Certificates" required>
                      <label>Description</label>
                      <textarea name="description" rows="3" placeholder="Short description of the template"></textarea>
                      <label>Fee (₱)</label>
                      <input type="number" step="0.01" min="0" name="fee" value="0">
                      <label>Required Documents</label>
                      <textarea name="requirements" rows="3" placeholder="One requirement per line, e.g. Government ID"></textarea>
                      <label>Fill Up Fields</label>
                      <?php echo renderTemplateFillUpFieldsEditor(''); ?>
                      <label>Template Body</label>
                      <?php echo renderTemplateBodyEditor(''); ?>
                      <button type="submit" class="add-user-button"><i class="bi bi-plus-lg"></i>Create Template</button>
                    </div>
                    <aside class="template-live-preview" data-template-live-preview>
                      <span class="template-live-preview-label"><i class="bi bi-eye"></i> Live template preview</span>
                      <?php staffTemplatePreview('', '', true); ?>
                    </aside>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="staff-panel" id="staff-panel-complaints" hidden>
          <div class="staff-card">
            <div class="section-card-heading">
              <div>
                <h2>Complaints</h2>
                <p>Pending complaints are shown first, then the newest completed complaints.</p>
              </div>
            </div>
            <?php if (!$complaintsTableReady): ?>
              <div class="activity-empty">Complaints table is not installed yet. Run the SQL script first to start receiving complaints.</div>
            <?php else: ?>
              <div class="table-wrap">
                <table class="requests-table complaints-table">
                  <thead>
                    <tr>
                      <th>Complaint No.</th>
                      <th>Complaint</th>
                      <th>Location</th>
                      <th>Date Submitted</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($complaints)): ?>
                      <tr>
                        <td colspan="6" class="empty-cell">No complaints recorded yet.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($complaints as $complaint): ?>
                        <?php
                          $complaintId = (int) ($complaint['complaint_id'] ?? 0);
                          $complaintNo = 'CMP-' . str_pad((string) $complaintId, 4, '0', STR_PAD_LEFT);
                          $complaintStatus = strtolower((string) ($complaint['status'] ?? 'pending'));
                          $complaintModalId = 'staffComplaintModal' . $complaintId;
                          $complaintRedirect = http_build_query(['view' => 'complaints']);
                          $complaintDate = $complaint['created_at'] ?? '';
                          $attachmentPath = trim((string) ($complaint['attachment_path'] ?? ''));
                          $attachmentName = trim((string) ($complaint['attachment_name'] ?? '')) ?: basename($attachmentPath);
                          $attachmentUrl = $attachmentPath !== '' ? '../actions/download.php?path=' . rawurlencode(ltrim($attachmentPath, '/\\')) : '';
                          $categories = trim((string) ($complaint['categories'] ?? ''));
                        ?>
                        <tr id="staff-complaint-<?php echo $complaintId; ?>" data-complaint-id="<?php echo $complaintId; ?>">
                          <td><?php echo htmlspecialchars($complaintNo); ?></td>
                          <td><?php echo htmlspecialchars($categories !== '' ? $categories : 'General Complaint'); ?></td>
                          <td><?php echo htmlspecialchars($complaint['location'] ?: 'Not provided'); ?></td>
                          <td><?php echo htmlspecialchars($complaintDate ? date('m/d/Y h:i A', strtotime($complaintDate)) : 'N/A'); ?></td>
                          <td>
                            <span class="status-pill <?php echo htmlspecialchars($complaintStatus); ?>"><?php echo htmlspecialchars(staffComplaintStatusLabel($complaintStatus)); ?></span>
                          </td>
                          <td>
                            <div class="table-actions">
                              <button type="button" class="verify-button open-modal-button" data-modal-target="<?php echo htmlspecialchars($complaintModalId); ?>">
                                <i class="bi bi-eye"></i>
                                <span>View</span>
                              </button>
                              <form method="POST" action="../actions/updateComplaintStatus.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="complaint_id" value="<?php echo $complaintId; ?>">
                                <input type="hidden" name="redirect_query" value="<?php echo htmlspecialchars($complaintRedirect); ?>">
                                <?php if ($complaintStatus === 'pending'): ?>
                                  <button type="submit" name="status" value="done" class="verify-button done-inline">
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Mark Done</span>
                                  </button>
                                <?php else: ?>
                                  <button type="submit" name="status" value="pending" class="verify-button pending-inline">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    <span>Reopen</span>
                                  </button>
                                <?php endif; ?>
                              </form>
                            </div>

                            <div class="modal-backdrop" id="<?php echo htmlspecialchars($complaintModalId); ?>" aria-hidden="true" hidden data-allow-backdrop="false">
                              <div class="modal-panel request-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($complaintModalId); ?>Title">
                                <div class="modal-header">
                                  <div>
                                    <h3 id="<?php echo htmlspecialchars($complaintModalId); ?>Title"><?php echo htmlspecialchars($complaintNo); ?></h3>
                                    <p><?php echo htmlspecialchars(staffComplaintStatusLabel($complaintStatus)); ?> complaint</p>
                                  </div>
                                  <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-form request-modal-content">
                                  <div class="details-grid">
                                    <div>
                                      <span>Status</span>
                                      <strong><?php echo htmlspecialchars(staffComplaintStatusLabel($complaintStatus)); ?></strong>
                                    </div>
                                    <div>
                                      <span>Date Submitted</span>
                                      <strong><?php echo htmlspecialchars($complaintDate ? date('M d, Y h:i A', strtotime($complaintDate)) : 'N/A'); ?></strong>
                                    </div>
                                    <div>
                                      <span>Last Updated</span>
                                      <strong><?php echo htmlspecialchars(!empty($complaint['updated_at']) ? date('M d, Y h:i A', strtotime($complaint['updated_at'])) : 'N/A'); ?></strong>
                                    </div>
                                    <div>
                                      <span>Completed</span>
                                      <strong><?php echo htmlspecialchars(!empty($complaint['completed_at']) ? date('M d, Y h:i A', strtotime($complaint['completed_at'])) : 'Not yet'); ?></strong>
                                    </div>
                                    <div class="wide">
                                      <span>Complaint</span>
                                      <strong><?php echo htmlspecialchars($categories !== '' ? $categories : 'General Complaint'); ?></strong>
                                    </div>
                                    <div class="wide">
                                      <span>Location</span>
                                      <strong><?php echo htmlspecialchars($complaint['location'] ?: 'Not provided'); ?></strong>
                                    </div>
                                    <div class="wide">
                                      <span>Details</span>
                                      <strong><?php echo nl2br(htmlspecialchars($complaint['description'] ?: 'No additional details provided.')); ?></strong>
                                    </div>
                                  </div>

                                  <div class="requirements-box">
                                    <span>Attachment</span>
                                    <?php if ($attachmentPath === ''): ?>
                                      <p>No file uploaded.</p>
                                    <?php else: ?>
                                      <div class="file-list">
                                        <a href="<?php echo htmlspecialchars($attachmentUrl); ?>" target="_blank" rel="noopener">
                                          <i class="bi bi-paperclip"></i>
                                          <?php echo htmlspecialchars($attachmentName); ?>
                                        </a>
                                      </div>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <div class="modal-backdrop" id="editSelfModal" aria-hidden="true" hidden>
      <div class="modal-panel edit-user-modal" role="dialog" aria-modal="true" aria-labelledby="editSelfModalTitle">
        <div class="modal-header">
          <div>
            <h3 id="editSelfModalTitle">Account Settings</h3>
            <p>Update your username or password</p>
          </div>
          <button type="button" class="modal-close" data-modal-close aria-label="Close modal">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <form method="POST" action="../actions/updateSelf.php" class="modal-form">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <input type="hidden" name="redirect_to" value="../dashboard/staff.php">
          <div>
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
          </div>
          <div>
            <label>Current Password (required to change password)</label>
            <input type="password" name="current_password" placeholder="Current password">
          </div>
          <div>
            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="new_password" placeholder="New password">
          </div>
          <div style="margin-top:10px;">
            <button type="submit" class="add-user-button">Save</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const requestedPanel = params.get('view') || params.get('section') || 'pending';
        const highlightedRequestId = params.get('highlight_request');
        const highlightedComplaintId = params.get('highlight_complaint');
        const normalizedPanel = requestedPanel === 'approved' || requestedPanel === 'reports' || requestedPanel === 'templates' || requestedPanel === 'activity' || requestedPanel === 'complaints'
          ? requestedPanel
          : 'pending';
        const initialTab = document.querySelector('.nav-link[data-panel="' + normalizedPanel + '"]');

        const openModal = function (modal) {
          if (!modal) return;
          modal.classList.add('is-open');
          modal.removeAttribute('hidden');
          modal.setAttribute('aria-hidden', 'false');
          const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea, button');
          if (firstInput) {
            firstInput.focus();
          }
        };

        const closeModal = function (modal) {
          if (!modal) return;
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          modal.setAttribute('hidden', '');
        };

        document.querySelectorAll('.modal-backdrop').forEach(function (modal) {
          closeModal(modal);
        });

        const activatePanel = function (panelId) {
        document.querySelectorAll('.nav-link[data-panel]').forEach(function (tab) {
            tab.classList.toggle('active', tab.getAttribute('data-panel') === panelId);
          });

          document.querySelectorAll('.staff-panel').forEach(function (panel) {
            panel.hidden = true;
          });

          if (panelId === 'pending') {
            document.getElementById('staff-panel-pending').hidden = false;
          } else if (panelId === 'approved') {
            document.getElementById('staff-panel-approved').hidden = false;
          } else if (panelId === 'reports') {
            document.getElementById('staff-panel-reports').hidden = false;
          } else if (panelId === 'activity') {
            document.getElementById('staff-panel-activity').hidden = false;
          } else if (panelId === 'templates') {
            document.getElementById('staff-panel-templates').hidden = false;
          } else if (panelId === 'complaints') {
            document.getElementById('staff-panel-complaints').hidden = false;
          }

          const url = new URL(window.location.href);
          url.searchParams.set('view', panelId);
          window.history.replaceState({}, '', url.toString());
        };

        if (initialTab) {
          activatePanel(normalizedPanel);
        } else {
          activatePanel('pending');
        }

        const staffTabsMenu = document.getElementById('staffTabsMenu');

        document.querySelectorAll('.nav-link[data-panel]').forEach(function (button) {
          button.addEventListener('click', function () {
            const panelId = this.getAttribute('data-panel');
            activatePanel(panelId);
          });
        });

        const staffNavScrollButtons = Array.from(document.querySelectorAll('[data-staff-nav-scroll]'));
        if (staffTabsMenu && staffNavScrollButtons.length === 2) {
          const updateStaffNavScrollButtons = function () {
            const maxScrollLeft = staffTabsMenu.scrollWidth - staffTabsMenu.clientWidth;
            const hasOverflow = maxScrollLeft > 1;

            staffNavScrollButtons.forEach(function (button) {
              button.hidden = !hasOverflow;
            });
            staffNavScrollButtons[0].disabled = staffTabsMenu.scrollLeft <= 1;
            staffNavScrollButtons[1].disabled = staffTabsMenu.scrollLeft >= maxScrollLeft - 1;
          };

          staffNavScrollButtons.forEach(function (button) {
            button.addEventListener('click', function () {
              const direction = button.dataset.staffNavScroll === 'left' ? -1 : 1;
              staffTabsMenu.scrollBy({
                left: direction * Math.max(180, Math.floor(staffTabsMenu.clientWidth * 0.75)),
                behavior: 'smooth'
              });
            });
          });

          staffTabsMenu.addEventListener('scroll', updateStaffNavScrollButtons, { passive: true });
          window.addEventListener('resize', updateStaffNavScrollButtons);
          const activeStaffSection = staffTabsMenu.querySelector('.nav-link.active');
          if (activeStaffSection) {
            activeStaffSection.scrollIntoView({ block: 'nearest', inline: 'nearest' });
          }
          updateStaffNavScrollButtons();
        }

        const highlightedRow = highlightedRequestId
          ? document.getElementById('staff-request-' + highlightedRequestId)
          : (highlightedComplaintId ? document.getElementById('staff-complaint-' + highlightedComplaintId) : null);
        if (highlightedRow) {
          highlightedRow.classList.add('request-highlight');
          window.setTimeout(function () {
            highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }, 120);
        }

        const notificationToggle = document.querySelector('.notification-toggle');
        const notificationMenu = document.getElementById('staffNotificationMenu');
        const notificationBadge = document.querySelector('[data-notification-badge]');
        const notificationItems = Array.from(document.querySelectorAll('[data-notification-key]'));
        const notificationStorageKey = 'staffReadRequestNotifications';
        let notificationReadKeys = [];

        try {
          notificationReadKeys = JSON.parse(window.localStorage.getItem(notificationStorageKey) || '[]');
        } catch (error) {
          notificationReadKeys = [];
        }

        const saveNotificationReadKeys = function () {
          window.localStorage.setItem(notificationStorageKey, JSON.stringify(notificationReadKeys));
        };

        const refreshNotificationBadge = function () {
          const unreadItems = notificationItems.filter(function (item) {
            return !notificationReadKeys.includes(item.dataset.notificationKey);
          });

          notificationItems.forEach(function (item) {
            item.classList.toggle('is-read', notificationReadKeys.includes(item.dataset.notificationKey));
          });

          if (notificationBadge) {
            if (unreadItems.length > 0) {
              notificationBadge.hidden = false;
              notificationBadge.textContent = unreadItems.length > 9 ? '9+' : String(unreadItems.length);
            } else {
              notificationBadge.hidden = true;
            }
          }
        };

        if (notificationToggle && notificationMenu) {
          notificationToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            const willOpen = notificationMenu.hidden;
            notificationMenu.hidden = !willOpen;
            notificationToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
          });

          document.addEventListener('click', function (event) {
            if (!notificationMenu.hidden && !notificationMenu.contains(event.target) && !notificationToggle.contains(event.target)) {
              notificationMenu.hidden = true;
              notificationToggle.setAttribute('aria-expanded', 'false');
            }
          });
        }

        notificationItems.forEach(function (item) {
          item.addEventListener('click', function () {
            const key = item.dataset.notificationKey;
            if (key && !notificationReadKeys.includes(key)) {
              notificationReadKeys.push(key);
              saveNotificationReadKeys();
            }
          });
        });

        refreshNotificationBadge();

        const reportPrintButton = document.querySelector('[data-print-report]');
        if (reportPrintButton) {
          reportPrintButton.addEventListener('click', function () {
            window.print();
          });
        }

        document.querySelectorAll('.print-certificate-button').forEach(function (button) {
          button.addEventListener('click', function (event) {
            event.stopPropagation();
            const certificate = document.getElementById(button.dataset.printTarget);
            if (!certificate) return;

            const markPrintedPromise = (button.dataset.currentStatus === 'approved' && button.dataset.requestId && button.dataset.csrfToken)
              ? (function () {
                const statusData = new FormData();
                statusData.append('request_id', button.dataset.requestId);
                statusData.append('csrf_token', button.dataset.csrfToken);
                statusData.append('status', 'printed');
                statusData.append('redirect_query', button.dataset.redirectQuery || 'view=approved');

                return window.fetch('../actions/updateRequestStatus.php', {
                  method: 'POST',
                  body: statusData,
                  credentials: 'same-origin'
                }).then(function (response) {
                  if (response.ok) {
                    button.dataset.currentStatus = 'printed';
                    return true;
                  }
                  return false;
                }).catch(function () {
                  return false;
                });
              })()
              : Promise.resolve(false);

            const printWindow = window.open('', '_blank', 'width=900,height=1100');
            if (!printWindow) return;

            let refreshedAfterPrint = false;
            const refreshIfMarkedPrinted = function () {
              if (refreshedAfterPrint) return;
              refreshedAfterPrint = true;
              markPrintedPromise.then(function (shouldRefresh) {
                if (shouldRefresh) {
                  window.setTimeout(function () {
                    window.location.reload();
                  }, 500);
                }
              });
            };

            printWindow.document.write('<!doctype html><html><head><base href="' + window.location.href + '"><title>Print Certificate</title>');
            printWindow.document.write('<style>@page{size:A4 portrait;margin:0}html,body{width:210mm;min-height:297mm}body{margin:0;padding:0;background:#fff;font-family:Georgia,serif;-webkit-print-color-adjust:exact;print-color-adjust:exact}.certificate-sheet{box-sizing:border-box;width:210mm;min-height:297mm;margin:0 auto;background:#fff;color:#1b1b1b}.approved-certificate{display:flex;flex-direction:column;padding:0;overflow:hidden}.certificate-accent-bar{height:7mm;margin:3mm 3mm 0;background:#9b4144}.certificate-letterhead{display:grid;grid-template-columns:24mm 1fr 24mm;gap:4mm;align-items:center;padding:3mm 10mm 2mm}.certificate-logo{display:block;width:22mm;height:22mm;object-fit:contain}.certificate-logo-qc{justify-self:end;clip-path:polygon(50% 0,100% 100%,0 100%)}.certificate-heading{text-align:center;line-height:1.1}.certificate-heading p{margin:0;font-family:Arial,sans-serif;font-size:10pt;font-weight:800;letter-spacing:.14em;text-transform:uppercase}.certificate-heading h3{margin:1mm 0 0;color:#a64146;font-family:Impact,Arial,sans-serif;font-size:27pt;font-weight:400;line-height:.98;text-transform:uppercase}.certificate-document-title h2{margin:7mm 0 0;font-size:20pt;text-align:center;text-decoration:underline;text-transform:uppercase}.certificate-body{margin:8mm 0 0;padding:0 18mm;font-size:12pt;line-height:1.8;text-align:justify}.certificate-greeting{font-weight:800;text-align:left}.certificate-signature{align-self:flex-end;min-width:55mm;margin:auto 18mm 10mm 0;text-align:center}.certificate-signature strong,.certificate-signature span{display:block}.certificate-signature strong{font-size:11pt}.certificate-signature span{margin-top:1mm;font-size:10pt;font-style:italic}.certificate-footer{display:flex;align-items:center;gap:3mm;min-height:13mm;margin:0 3mm 3mm;padding:1.5mm 3.5mm;background:#9b4144;color:#fff;font-family:Arial,sans-serif;font-size:7pt}.certificate-footer span{display:inline-flex;align-items:center;gap:1mm}.certificate-footer img{width:13mm;height:10mm;margin-left:auto;object-fit:contain}@media print{body{background:#fff}.certificate-sheet{box-shadow:none}}</style>');
            printWindow.document.write('</head><body>' + certificate.outerHTML + '</body></html>');
            printWindow.document.close();
            printWindow.addEventListener('afterprint', refreshIfMarkedPrinted);
            printWindow.focus();
            printWindow.setTimeout(function () {
              printWindow.print();
              printWindow.setTimeout(refreshIfMarkedPrinted, 2500);
            }, 250);
          });
        });

        document.querySelectorAll('.open-template-modal').forEach(function (button) {
          button.addEventListener('click', function (event) {
            event.stopPropagation();
            openModal(document.getElementById(this.getAttribute('data-template-modal')));
          });
        });

        document.querySelectorAll('[data-template-rich-editor]').forEach(function (editor) {
            const content = editor.querySelector('[data-template-editor-content]');
            const value = editor.querySelector('[data-template-editor-value]');
            if (!content || !value) return;
            let savedRange = null;
            const saveSelection = function () {
                const selection = window.getSelection();
                if (selection.rangeCount && content.contains(selection.anchorNode)) {
                    savedRange = selection.getRangeAt(0).cloneRange();
                }
            };
            content.addEventListener('mouseup', saveSelection);
            content.addEventListener('keyup', saveSelection);
            editor.querySelectorAll('[data-template-command]').forEach(function (button) {
                button.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    content.focus();
                    if (savedRange) {
                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(savedRange);
                    }
                    document.execCommand(button.dataset.templateCommand, false, null);
                    saveSelection();
                });
            });
            const fontSize = editor.querySelector('[data-template-font-size]');
            if (fontSize) {
                fontSize.addEventListener('mousedown', saveSelection);
                fontSize.addEventListener('change', function () {
                    const size = Number(fontSize.value);
                    if (!Number.isFinite(size) || size < 6 || size > 72 || !savedRange) {
                        return;
                    }
                    content.focus();
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(savedRange);
                    const range = selection.getRangeAt(0);
                    const span = document.createElement('span');
                    span.style.fontSize = size + 'pt';
                    if (range.collapsed) {
                        const marker = document.createTextNode('\u200B');
                        span.appendChild(marker);
                        range.insertNode(span);
                        range.setStart(marker, 1);
                        range.setEnd(marker, 1);
                    } else {
                        span.appendChild(range.extractContents());
                        range.insertNode(span);
                        range.selectNodeContents(span);
                        range.collapse(false);
                    }
                    selection.removeAllRanges();
                    selection.addRange(range);
                    saveSelection();
                });
            }
            const form = editor.closest('form');
            if (form) {
                form.addEventListener('submit', function () {
                    value.value = content.innerHTML.replace(/\u200B/g, '');
                });
            }
        });

        document.querySelectorAll('[data-template-live-preview]').forEach(function (preview) {
          const form = preview.closest('form');
          const nameInput = form ? form.querySelector('[name="name"]') : null;
          const contentInput = form ? form.querySelector('[name="template_content"]') : null;
          const title = preview.querySelector('[data-template-preview-title]');
          const content = preview.querySelector('[data-template-preview-content]');
          const defaultTitle = 'Certificate of Indigency';
          const defaultContent = 'Enter the template body to preview the additional certificate paragraph here.';

          const refreshTemplatePreview = function () {
            if (title) title.textContent = nameInput && nameInput.value.trim() ? nameInput.value.trim() : defaultTitle;
              if (content) {
                  const editorContent = form && form.querySelector('[data-template-editor-content]');
                  content.innerHTML = editorContent && editorContent.innerHTML.trim() ? editorContent.innerHTML : '<p>' + defaultContent + '</p>';
              }
          };

          [nameInput, contentInput, form && form.querySelector('[data-template-editor-content]')].filter(Boolean).forEach(function (input) {
              input.addEventListener('input', refreshTemplatePreview);
          });
          refreshTemplatePreview();
        });

        document.querySelectorAll('[data-modal-target]').forEach(function (button) {
          button.addEventListener('click', function (event) {
            event.stopPropagation();
            openModal(document.getElementById(this.getAttribute('data-modal-target')));
          });
        });

        document.querySelectorAll('[data-modal-close]').forEach(function (button) {
          button.addEventListener('click', function (event) {
            event.stopPropagation();
            const modal = event.currentTarget.closest('.modal-backdrop');
            closeModal(modal);
          });
        });

        document.querySelectorAll('.modal-backdrop').forEach(function (modal) {
          modal.addEventListener('click', function (event) {
            if (event.target === modal) {
              closeModal(modal);
            }
          });
        });

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.is-open').forEach(function (modal) {
              closeModal(modal);
            });
          }
        });
      });
    </script>
<script src="../template-fields.js" defer></script>
<script src="../release-confirm.js?v=20261007-confirm-2" defer></script>
</body>
</html>
