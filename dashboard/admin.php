<?php
require_once '../auth/session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ["Please login to access the admin panel."];
    header("Location: ../pages/loginPage.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard/" . $_SESSION['role'] . ".php");
    exit();
}

if ($conn === null) {
    die("Database connection failed. Please check your database configuration and ensure MySQL is running.");
}

$displayName = $_SESSION['username'] ?? 'Admin User';
$activeSection = strtolower($_GET['section'] ?? 'requests');
if (!in_array($activeSection, ['overview', 'pending', 'requests', 'users', 'templates', 'approved', 'archive', 'reports', 'backup', 'activity'], true)) {
    $activeSection = 'requests';
}

$allowedStatuses = ['all', 'approved', 'printed', 'rejected', 'released'];
$statusFilter = strtolower($_GET['status'] ?? 'all');
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}
$archiveCutoffMonths = 3;
$archiveCutoffLabel = $archiveCutoffMonths . ' months';
$archiveCondition = "r.status IN ('printed', 'released', 'rejected') AND COALESCE(r.reviewed_at, r.date_updated, r.date_submitted) < DATE_SUB(NOW(), INTERVAL $archiveCutoffMonths MONTH)";

$search = trim($_GET['search'] ?? '');
$userSearch = trim($_GET['user_search'] ?? '');
$roleFilter = strtolower($_GET['role'] ?? 'all');
if (!in_array($roleFilter, ['all', 'admin', 'staff', 'resident'], true)) {
    $roleFilter = 'all';
}
$accountStatusFilter = strtolower($_GET['account_status'] ?? 'all');
if (!in_array($accountStatusFilter, ['all', 'pending', 'verified', 'rejected'], true)) {
    $accountStatusFilter = 'all';
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

$activitySearch = trim($_GET['activity_search'] ?? '');
$highlightUserId = isset($_GET['highlight_user']) ? (int) $_GET['highlight_user'] : 0;
$highlightRequestId = isset($_GET['highlight_request']) ? (int) $_GET['highlight_request'] : 0;

function adminRequestNumber($id, $dateSubmitted) {
    $year = $dateSubmitted ? date('Y', strtotime($dateSubmitted)) : date('Y');
    return 'REQ-' . $year . '-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

function adminTemplatePreview($name, $content, $isLive = false) {
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
            <strong>RENIEL JOHN C. PERDIDO</strong>
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

function adminStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'printed' => 'Printed',
        'rejected' => 'Rejected',
        'released' => 'Released',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function adminStatusClass($status) {
    return $status;
}

function adminStatusMessage($status) {
    $messages = [
        'pending' => 'This request is waiting for review.',
        'approved' => 'This document request has been approved and is ready for printing.',
        'printed' => 'This document has been printed and can now be claimed by the resident during office hours.',
        'released' => 'This document has already been released to the resident.',
        'rejected' => 'This request was rejected. Review the remarks for the reason.',
    ];

    return $messages[strtolower((string) $status)] ?? 'No status message available.';
}

function adminValidDateInput($value) {
    $value = trim((string) $value);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return '';
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
}

function adminReportDateRange($period, $startInput, $endInput) {
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
        $startDate = adminValidDateInput($startInput);
        $endDate = adminValidDateInput($endInput);
        if ($startDate !== '' && $endDate !== '' && strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
    }

    return [$startDate, $endDate];
}

function adminReportRangeLabel($period, $startDate, $endDate, $periodOptions) {
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

function accountStatusLabel($status) {
    $labels = [
        'pending' => 'For Verification',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function userDisplayName($user) {
    $residentName = trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''));
    $accountName = trim(($user['accountFirstName'] ?? '') . ' ' . ($user['accountLastName'] ?? ''));
    return $residentName ?: ($accountName ?: ($user['username'] ?? 'Unknown User'));
}

function userTableColumnExists($conn, $columnName) {
    static $cache = [];

    if (isset($cache[$columnName])) {
        return $cache[$columnName];
    }

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    if (!$stmt) {
        $cache[$columnName] = false;
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $columnName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    $cache[$columnName] = $row && (int) $row['column_count'] > 0;
    return $cache[$columnName];
}

function userColumnSelect($conn, $columnName, $alias) {
    return userTableColumnExists($conn, $columnName)
        ? "u.`$columnName` AS `$alias`"
        : "NULL AS `$alias`";
}

function fetchRequestFiles($conn, $requestId) {
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

if (isset($_SESSION['user_id']) && userTableColumnExists($conn, 'first_name')) {
    $currentNameStmt = mysqli_prepare($conn, "SELECT first_name FROM users WHERE userId = ? LIMIT 1");
    if ($currentNameStmt) {
        $currentUserId = (int) $_SESSION['user_id'];
        mysqli_stmt_bind_param($currentNameStmt, "i", $currentUserId);
        mysqli_stmt_execute($currentNameStmt);
        $currentNameResult = mysqli_stmt_get_result($currentNameStmt);
        if ($currentNameRow = mysqli_fetch_assoc($currentNameResult)) {
            $accountFirstName = trim((string) ($currentNameRow['first_name'] ?? ''));
            if ($accountFirstName !== '') {
                $displayName = $accountFirstName;
                $_SESSION['first_name'] = $accountFirstName;
            }
        }
        mysqli_stmt_close($currentNameStmt);
    }
}

$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'printed' => 0,
    'released' => 0,
    'rejected' => 0,
    'archive' => 0,
];

$statsSql = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
    SUM(CASE WHEN status = 'printed' THEN 1 ELSE 0 END) AS printed_count,
    SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) AS released_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
    SUM(CASE WHEN status IN ('printed', 'released', 'rejected') AND COALESCE(reviewed_at, date_updated, date_submitted) < DATE_SUB(NOW(), INTERVAL $archiveCutoffMonths MONTH) THEN 1 ELSE 0 END) AS archive_count
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
        'archive' => (int) $row['archive_count'],
    ];
}

$populationStats = [
    'total' => 0,
    'male' => 0,
    'female' => 0,
    'adults' => 0,
    'elders' => 0,
    'teenagers' => 0,
];

$populationSql = "SELECT r.sex, r.dateOfBirth
    FROM users u
    LEFT JOIN residents r ON r.residentId = u.residentId
    WHERE u.role = 'resident' AND u.residentId IS NOT NULL";
$populationResult = mysqli_query($conn, $populationSql);
if ($populationResult) {
    while ($populationRow = mysqli_fetch_assoc($populationResult)) {
        $populationStats['total']++;

        $sex = strtolower(trim((string) ($populationRow['sex'] ?? '')));
        if ($sex === 'male') {
            $populationStats['male']++;
        } elseif ($sex === 'female') {
            $populationStats['female']++;
        }

        $birthdate = trim((string) ($populationRow['dateOfBirth'] ?? ''));
        if ($birthdate !== '') {
            $birthDateTime = DateTimeImmutable::createFromFormat('Y-m-d', $birthdate);
            if ($birthDateTime instanceof DateTimeImmutable) {
                $today = new DateTimeImmutable('today');
                $age = (int) $today->diff($birthDateTime)->y;
                if ($age >= 60) {
                    $populationStats['elders']++;
                }
                if ($age >= 20 && $age <= 59) {
                    $populationStats['adults']++;
                }
                if ($age >= 13 && $age <= 19) {
                    $populationStats['teenagers']++;
                }
            }
        }
    }
}
$historyCount = max(0, $stats['total'] - $stats['pending']);

$where = [];
$params = [];
$types = '';

if ($activeSection === 'pending') {
    $where[] = "r.status = 'pending'";
} elseif ($activeSection === 'archive') {
    $where[] = $archiveCondition;
} elseif ($statusFilter !== 'all') {
    $where[] = "r.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
    $where[] = "NOT ($archiveCondition)";
} else {
    $where[] = "r.status <> 'pending'";
    $where[] = "NOT ($archiveCondition)";
}

if ($search !== '') {
    $where[] = "(r.request_number LIKE ? OR r.document_type LIKE ? OR r.purpose LIKE ? OR u.username LIKE ? OR res.firstName LIKE ? OR res.lastName LIKE ? OR res.emailAddress LIKE ?)";
    $searchTerm = '%' . $search . '%';
    for ($i = 0; $i < 7; $i++) {
        $params[] = $searchTerm;
        $types .= 's';
    }
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$requestsSql = "SELECT
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
    $whereSql
    ORDER BY COALESCE(r.reviewed_at, r.date_updated, r.date_submitted) DESC, r.request_id DESC";

$stmt = mysqli_prepare($conn, $requestsSql);
if ($stmt && $types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

$requests = [];
if ($stmt && mysqli_stmt_execute($stmt)) {
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$approvedWhere = ["r.status IN ('approved', 'printed')"];
$approvedParams = [];
$approvedTypes = '';
if ($search !== '') {
    $approvedWhere[] = "(r.request_number LIKE ? OR r.document_type LIKE ? OR r.purpose LIKE ? OR u.username LIKE ? OR res.firstName LIKE ? OR res.lastName LIKE ? OR res.emailAddress LIKE ?)";
    $searchTerm = '%' . $search . '%';
    for ($i = 0; $i < 7; $i++) {
        $approvedParams[] = $searchTerm;
        $approvedTypes .= 's';
    }
}

$approvedWhereSql = 'WHERE ' . implode(' AND ', $approvedWhere);
$approvedSql = "SELECT
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
    $approvedWhereSql
    ORDER BY COALESCE(r.reviewed_at, r.date_updated) DESC, r.request_id DESC";

$approvedStmt = mysqli_prepare($conn, $approvedSql);
if ($approvedStmt && $approvedTypes !== '') {
    mysqli_stmt_bind_param($approvedStmt, $approvedTypes, ...$approvedParams);
}

$approvedRequests = [];
if ($approvedStmt && mysqli_stmt_execute($approvedStmt)) {
    $approvedResult = mysqli_stmt_get_result($approvedStmt);
    while ($row = mysqli_fetch_assoc($approvedResult)) {
        $approvedRequests[] = $row;
    }
    mysqli_stmt_close($approvedStmt);
}

$statusOptions = [
    'all' => 'All History',
    'approved' => 'Approved',
    'printed' => 'Printed',
    'released' => 'Released',
    'rejected' => 'Rejected',
];

$roleOptions = [
    'all' => 'All Roles',
    'admin' => 'Admin',
    'staff' => 'Staff',
    'resident' => 'Resident',
];

$accountStatusOptions = [
    'all' => 'All Accounts',
    'pending' => 'For Verification',
    'verified' => 'Verified',
    'rejected' => 'Rejected',
];

$userStats = [
    'total' => 0,
    'admin' => 0,
    'staff' => 0,
    'resident' => 0,
];

$userStatsSql = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admin_count,
    SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) AS staff_count,
    SUM(CASE WHEN role = 'resident' THEN 1 ELSE 0 END) AS resident_count
    FROM users";
$userStatsResult = mysqli_query($conn, $userStatsSql);
if ($userStatsResult && $row = mysqli_fetch_assoc($userStatsResult)) {
    $userStats = [
        'total' => (int) $row['total'],
        'admin' => (int) $row['admin_count'],
        'staff' => (int) $row['staff_count'],
        'resident' => (int) $row['resident_count'],
    ];
}

$userWhere = [];
$userParams = [];
$userTypes = '';

if ($roleFilter !== 'all') {
    $userWhere[] = "u.role = ?";
    $userParams[] = $roleFilter;
    $userTypes .= 's';
}

if ($accountStatusFilter !== 'all') {
    $userWhere[] = "u.account_status = ?";
    $userParams[] = $accountStatusFilter;
    $userTypes .= 's';
}

if ($userSearch !== '') {
    $userSearchTerm = '%' . strtolower($userSearch) . '%';
    $userSearchParts = [
        "LOWER(CONCAT(COALESCE(res.firstName, ''), ' ', COALESCE(res.lastName, ''))) LIKE ?",
        "LOWER(u.username) LIKE ?",
        "LOWER(COALESCE(res.emailAddress, '')) LIKE ?",
        "LOWER(COALESCE(res.phoneNumber, '')) LIKE ?",
    ];

    if (userTableColumnExists($conn, 'first_name')) {
        $userSearchParts[] = "LOWER(COALESCE(u.first_name, '')) LIKE ?";
    }
    if (userTableColumnExists($conn, 'last_name')) {
        $userSearchParts[] = "LOWER(COALESCE(u.last_name, '')) LIKE ?";
    }
    if (userTableColumnExists($conn, 'contact_email')) {
        $userSearchParts[] = "LOWER(COALESCE(u.contact_email, '')) LIKE ?";
    }
    if (userTableColumnExists($conn, 'contact_phone')) {
        $userSearchParts[] = "LOWER(COALESCE(u.contact_phone, '')) LIKE ?";
    }

    $userWhere[] = '(' . implode(' OR ', $userSearchParts) . ')';
    $searchPartCount = count($userSearchParts);
    for ($i = 0; $i < $searchPartCount; $i++) {
        $userParams[] = $userSearchTerm;
        $userTypes .= 's';
    }
}

$userWhereSql = $userWhere ? 'WHERE ' . implode(' AND ', $userWhere) : '';
$userProfileSelectSql = implode(",\n        ", [
    userColumnSelect($conn, 'first_name', 'accountFirstName'),
    userColumnSelect($conn, 'last_name', 'accountLastName'),
    userColumnSelect($conn, 'contact_email', 'contactEmail'),
    userColumnSelect($conn, 'contact_phone', 'contactPhone'),
]);
$usersSql = "SELECT
        u.userId,
        u.username,
        u.role,
        u.account_status,
        u.verification_proof,
        u.verification_rejection_reason,
        u.created_at,
        u.updated_at,
        $userProfileSelectSql,
        res.firstName,
        res.lastName,
        res.emailAddress,
        res.phoneNumber
    FROM users u
    LEFT JOIN residents res ON u.residentId = res.residentId
    $userWhereSql
    ORDER BY
        CASE WHEN u.account_status = 'pending' THEN 0 WHEN u.account_status = 'verified' THEN 1 ELSE 2 END,
        COALESCE(u.updated_at, u.created_at) DESC,
        u.userId DESC";

$userStmt = mysqli_prepare($conn, $usersSql);
if ($userStmt && $userTypes !== '') {
    mysqli_stmt_bind_param($userStmt, $userTypes, ...$userParams);
}

$users = [];
if ($userStmt && mysqli_stmt_execute($userStmt)) {
    $userResult = mysqli_stmt_get_result($userStmt);
    while ($row = mysqli_fetch_assoc($userResult)) {
        $users[] = $row;
    }
    mysqli_stmt_close($userStmt);
}

$adminNotifications = [];
$pendingVerificationSql = "SELECT
        u.userId,
        u.username,
        u.role,
        u.account_status,
        u.verification_proof,
        u.updated_at,
        $userProfileSelectSql,
        res.firstName,
        res.lastName,
        res.emailAddress,
        res.phoneNumber
    FROM users u
    LEFT JOIN residents res ON u.residentId = res.residentId
    WHERE u.role = 'resident'
      AND u.account_status = 'pending'
      AND COALESCE(u.verification_proof, '') <> ''
    ORDER BY COALESCE(u.updated_at, u.created_at) DESC, u.userId DESC
    LIMIT 8";
$pendingVerificationResult = mysqli_query($conn, $pendingVerificationSql);
if ($pendingVerificationResult) {
    while ($pendingUser = mysqli_fetch_assoc($pendingVerificationResult)) {
        $pendingUserId = (int) ($pendingUser['userId'] ?? 0);
        $adminNotifications[] = [
            'key' => 'admin-user-verification:' . $pendingUserId . ':' . (string) ($pendingUser['updated_at'] ?? ''),
            'href' => 'admin.php?section=users&account_status=pending&highlight_user=' . $pendingUserId,
            'message' => userDisplayName($pendingUser) . ' submitted account verification.',
            'date' => (string) ($pendingUser['updated_at'] ?? ''),
        ];
    }
}

$pendingRequestNotificationSql = "SELECT
        r.request_id,
        r.request_number,
        r.document_type,
        r.date_submitted,
        u.username,
        res.firstName,
        res.lastName
    FROM document_requests r
    LEFT JOIN users u ON r.user_id = u.userId
    LEFT JOIN residents res ON u.residentId = res.residentId
    WHERE r.status = 'pending'
    ORDER BY r.date_submitted DESC, r.request_id DESC
    LIMIT 8";
$pendingRequestNotificationResult = mysqli_query($conn, $pendingRequestNotificationSql);
if ($pendingRequestNotificationResult) {
    while ($pendingRequest = mysqli_fetch_assoc($pendingRequestNotificationResult)) {
        $pendingRequestId = (int) ($pendingRequest['request_id'] ?? 0);
        $pendingRequestNumber = $pendingRequest['request_number'] ?: adminRequestNumber($pendingRequestId, $pendingRequest['date_submitted'] ?? '');
        $pendingResidentName = trim(($pendingRequest['firstName'] ?? '') . ' ' . ($pendingRequest['lastName'] ?? ''));
        $pendingResidentName = $pendingResidentName ?: ($pendingRequest['username'] ?? 'Resident');
        $adminNotifications[] = [
            'key' => 'admin-pending-request:' . $pendingRequestId . ':' . (string) ($pendingRequest['date_submitted'] ?? ''),
            'href' => 'admin.php?section=pending&highlight_request=' . $pendingRequestId,
            'message' => $pendingRequestNumber . ' from ' . $pendingResidentName . ' is waiting for review.',
            'date' => (string) ($pendingRequest['date_submitted'] ?? ''),
        ];
    }
}

usort($adminNotifications, function ($left, $right) {
    return strtotime($right['date'] ?? '') <=> strtotime($left['date'] ?? '');
});
$adminNotifications = array_slice($adminNotifications, 0, 10);

$reportDocumentTypeOptions = [];
$reportDocumentTypeSql = "SELECT DISTINCT document_type FROM document_requests WHERE TRIM(document_type) <> '' ORDER BY document_type ASC";
$reportDocumentTypeResult = mysqli_query($conn, $reportDocumentTypeSql);
if ($reportDocumentTypeResult) {
    while ($row = mysqli_fetch_assoc($reportDocumentTypeResult)) {
        $reportDocumentTypeOptions[] = $row['document_type'];
    }
}

$reportStartInput = adminValidDateInput($_GET['report_start'] ?? '');
$reportEndInput = adminValidDateInput($_GET['report_end'] ?? '');
[$reportStartDate, $reportEndDate] = adminReportDateRange($reportPeriod, $reportStartInput, $reportEndInput);
$reportRangeLabel = adminReportRangeLabel($reportPeriod, $reportStartDate, $reportEndDate, $reportPeriodOptions);
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

$backupDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups';
$recentBackups = [];
if (is_dir($backupDirectory)) {
    $backupFiles = array_merge(
        glob($backupDirectory . DIRECTORY_SEPARATOR . '*.enc') ?: [],
        glob($backupDirectory . DIRECTORY_SEPARATOR . '*.sql') ?: [],
        glob($backupDirectory . DIRECTORY_SEPARATOR . '*.zip') ?: []
    );
    usort($backupFiles, function ($left, $right) {
        return filemtime($right) <=> filemtime($left);
    });
    foreach (array_slice($backupFiles, 0, 8) as $backupFile) {
        $recentBackups[] = [
            'name' => basename($backupFile),
            'size' => filesize($backupFile),
            'date' => filemtime($backupFile),
            'type' => match (strtolower(pathinfo($backupFile, PATHINFO_EXTENSION))) {
                'enc' => 'Encrypted SQL',
                'zip' => 'Legacy ZIP',
                default => 'Legacy SQL',
            },
        ];
    }
}

$templates = getDocumentTemplates($conn);

$recentActivities = [];
$activitySql = "SELECT user_id, username, role, action, description, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 8";
$activityResult = mysqli_query($conn, $activitySql);
if ($activityResult) {
    while ($row = mysqli_fetch_assoc($activityResult)) {
        $recentActivities[] = $row;
    }
}

$activityLogs = [];
$activityWhere = [];
$activityParams = [];
$activityTypes = '';

if ($activitySearch !== '') {
    $activityWhere[] = "(LOWER(username) LIKE ? OR LOWER(action) LIKE ? OR LOWER(description) LIKE ?)";
    $activitySearchTerm = '%' . strtolower($activitySearch) . '%';
    $activityParams[] = $activitySearchTerm;
    $activityParams[] = $activitySearchTerm;
    $activityParams[] = $activitySearchTerm;
    $activityTypes .= 'sss';
}

$activityWhereSql = $activityWhere ? 'WHERE ' . implode(' AND ', $activityWhere) : '';
$activityLogsSql = "SELECT user_id, username, role, action, description, created_at FROM activity_logs $activityWhereSql ORDER BY created_at DESC";
$activityLogsStmt = mysqli_prepare($conn, $activityLogsSql);
if ($activityLogsStmt && $activityTypes !== '') {
    mysqli_stmt_bind_param($activityLogsStmt, $activityTypes, ...$activityParams);
}

if ($activityLogsStmt && mysqli_stmt_execute($activityLogsStmt)) {
    $activityLogsResult = mysqli_stmt_get_result($activityLogsStmt);
    while ($row = mysqli_fetch_assoc($activityLogsResult)) {
        $activityLogs[] = $row;
    }
    mysqli_stmt_close($activityLogsStmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin.css?v=20261009-mobile-controls-1">
    <link rel="stylesheet" href="../css/release-confirm.css?v=20261007-1">
    <link rel="stylesheet" href="../css/file-preview.css?v=20261007-file-preview-1">
    <link rel="stylesheet" href="../css/responsive.css?v=20261006-responsive-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css" integrity="sha512-t7Few9xlddEmgd3oKZQahkNI4dS6l80+eGEzFQiqtyVYdvcSG2D3Iub77R20BdotfRPA9caaRkg1tyaJiPmO0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<header class="admin-header">
    <div class="container header-container">
        <div>
            <h1>Admin Dashboard</h1>
            <p class="admin-welcome">Welcome, <?php echo htmlspecialchars($displayName); ?></p>
        </div>
        <div class="header-actions">
            <div class="notification-wrap">
                <button class="icon-button notification-toggle" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="adminNotificationMenu">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge" data-notification-badge hidden>0</span>
                </button>
                <div class="notification-menu" id="adminNotificationMenu" hidden>
                    <div class="notification-menu-header">
                        <strong>Notifications</strong>
                    </div>
                    <div class="notification-list" data-notification-list>
                        <?php if (empty($adminNotifications)): ?>
                            <div class="notification-empty">No new verification or request updates.</div>
                        <?php else: ?>
                            <?php foreach ($adminNotifications as $notification): ?>
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
            <button class="icon-button" aria-label="Account" data-modal-target="editSelfModal"><i class="bi bi-person-circle"></i></button>
            <button class="mobile-menu-toggle admin-mobile-menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="adminSectionsMenu">
                <i class="bi bi-list"></i>
            </button>
            <a href="../auth/logout.php" class="logout-button"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
        </div>
    </div>
</header>

<main>
    <?php displayFlashMessages(); ?>

    <section class="stats-section">
        <div class="container stats-grid">
            <a href="admin.php?section=requests" class="stat-card" aria-label="View request history">
                <span>Request History</span>
                <strong><?php echo $historyCount; ?></strong>
                <i class="bi bi-file-earmark-text"></i>
            </a>
            <a href="admin.php?section=pending" class="stat-card warning" aria-label="View pending requests">
                <span>Pending</span>
                <strong><?php echo $stats['pending']; ?></strong>
                <i class="bi bi-hourglass-split"></i>
            </a>
            <a href="admin.php?section=requests&status=approved" class="stat-card info" aria-label="View approved requests">
                <span>Approved</span>
                <strong><?php echo $stats['approved']; ?></strong>
                <i class="bi bi-arrow-repeat"></i>
            </a>
            <a href="admin.php?section=requests&status=printed" class="stat-card success" aria-label="View printed requests">
                <span>Printed</span>
                <strong><?php echo $stats['printed']; ?></strong>
                <i class="bi bi-printer"></i>
            </a>
            <a href="admin.php?section=requests&status=released" class="stat-card primary" aria-label="View released requests">
                <span>Released</span>
                <strong><?php echo $stats['released']; ?></strong>
                <i class="bi bi-send-check"></i>
            </a>
            <a href="admin.php?section=requests&status=rejected" class="stat-card danger" aria-label="View rejected requests">
                <span>Rejected</span>
                <strong><?php echo $stats['rejected']; ?></strong>
                <i class="bi bi-x-circle"></i>
            </a>
            <a href="admin.php?section=archive" class="stat-card" aria-label="View archived request history">
                <span>Archived</span>
                <strong><?php echo $stats['archive']; ?></strong>
                <i class="bi bi-archive"></i>
            </a>
        </div>
    </section>

    <section class="nav-section">
        <div class="container">
            <div class="admin-nav-controls">
                <button class="admin-nav-arrow" type="button" data-admin-nav-scroll="left" aria-label="Scroll admin sections left" aria-controls="adminSectionsMenu" hidden>
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <nav class="admin-nav" id="adminSectionsMenu" aria-label="Admin sections">
                    <a href="admin.php?section=overview" class="nav-link <?php echo $activeSection === 'overview' ? 'active' : ''; ?>"><i class="bi bi-grid"></i><span>Overview</span></a>
                    <a href="admin.php?section=users" class="nav-link <?php echo $activeSection === 'users' ? 'active' : ''; ?>"><i class="bi bi-people"></i><span>User Management</span></a>
                    <a href="admin.php?section=pending" class="nav-link <?php echo $activeSection === 'pending' ? 'active' : ''; ?>"><i class="bi bi-hourglass-split"></i><span>Pending Requests</span></a>
                    <a href="admin.php?section=approved" class="nav-link <?php echo $activeSection === 'approved' ? 'active' : ''; ?>"><i class="bi bi-file-earmark-check"></i><span>Approved Documents</span></a>
                    <a href="admin.php?section=requests" class="nav-link <?php echo $activeSection === 'requests' ? 'active' : ''; ?>"><i class="bi bi-file-earmark-text"></i><span>Request History</span></a>
                    <a href="admin.php?section=archive" class="nav-link <?php echo $activeSection === 'archive' ? 'active' : ''; ?>"><i class="bi bi-archive"></i><span>Archive</span></a>
                    <a href="admin.php?section=reports" class="nav-link <?php echo $activeSection === 'reports' ? 'active' : ''; ?>"><i class="bi bi-printer"></i><span>Print Reports</span></a>
                    <a href="admin.php?section=templates" class="nav-link <?php echo $activeSection === 'templates' ? 'active' : ''; ?>"><i class="bi bi-gear"></i><span>Document Templates</span></a>
                    <a href="admin.php?section=activity" class="nav-link <?php echo $activeSection === 'activity' ? 'active' : ''; ?>"><i class="bi bi-activity"></i><span>Activity Log</span></a>
                    <a href="admin.php?section=backup" class="nav-link <?php echo $activeSection === 'backup' ? 'active' : ''; ?>"><i class="bi bi-database-down"></i><span>System Backup</span></a>
                    <a href="../auth/logout.php" class="nav-link mobile-logout-nav"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
                </nav>
                <button class="admin-nav-arrow" type="button" data-admin-nav-scroll="right" aria-label="Scroll admin sections right" aria-controls="adminSectionsMenu" hidden>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </section>

    <section class="requests-section">
        <div class="container">
            <?php if ($activeSection === 'users'): ?>
            <div class="requests-card user-management-card">
                <div class="card-heading">
                    <div class="title-row">
                        <i class="bi bi-people"></i>
                        <div>
                            <h2>User Management</h2>
                            <p>Manage system users and resident verification</p>
                        </div>
                    </div>
                    <button type="button" class="add-user-button open-modal-button" data-modal-target="addUserModal">
                        <i class="bi bi-plus-lg"></i>Add User
                    </button>
                </div>

                <div class="user-toolbar">
                    <div class="user-counts">
                        <span>Total Users: <?php echo $userStats['total']; ?></span>
                        <span>Admin: <?php echo $userStats['admin']; ?></span>
                        <span>Staff: <?php echo $userStats['staff']; ?></span>
                        <span>Resident: <?php echo $userStats['resident']; ?></span>
                    </div>
                    <form class="filter-form" method="GET">
                        <input type="hidden" name="section" value="users">
                        <label class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="search" name="user_search" placeholder="Search users by name or contact..." value="<?php echo htmlspecialchars($userSearch); ?>">
                        </label>
                        <button type="submit" class="mini-action filter-search-button">Search</button>
                        <select name="role" aria-label="Filter by role" onchange="this.form.submit()">
                            <?php foreach ($roleOptions as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $roleFilter === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="account_status" aria-label="Filter by account status" onchange="this.form.submit()">
                            <?php foreach ($accountStatusOptions as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $accountStatusFilter === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="table-wrap">
                    <table class="requests-table users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="empty-cell">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $displayUserName = userDisplayName($user);
                                $email = trim((string) ($user['emailAddress'] ?? '')) ?: (trim((string) ($user['contactEmail'] ?? '')) ?: 'Not provided');
                                $phone = trim((string) ($user['phoneNumber'] ?? '')) ?: (trim((string) ($user['contactPhone'] ?? '')) ?: 'Not provided');
                                $accountFirstName = trim((string) ($user['firstName'] ?? '')) ?: trim((string) ($user['accountFirstName'] ?? ''));
                                $accountLastName = trim((string) ($user['lastName'] ?? '')) ?: trim((string) ($user['accountLastName'] ?? ''));
                                $accountEmail = trim((string) ($user['emailAddress'] ?? '')) ?: trim((string) ($user['contactEmail'] ?? ''));
                                $accountPhone = trim((string) ($user['phoneNumber'] ?? '')) ?: trim((string) ($user['contactPhone'] ?? ''));
                                $status = strtolower($user['account_status'] ?? 'pending');
                                $proofPath = trim((string) ($user['verification_proof'] ?? ''));
                                $rejectReason = trim((string) ($user['verification_rejection_reason'] ?? ''));
                                $proofUrl = $proofPath !== '' ? '../actions/download.php?path=' . rawurlencode(ltrim($proofPath, '/\\')) : '';
                                $canReview = $user['role'] === 'resident' && $proofPath !== '' && $status === 'pending';
                                $reviewModalId = 'verifyUserModal' . (int) $user['userId'];
                                $rejectModalId = 'rejectUserModal' . (int) $user['userId'];
                                ?>
                                <tr id="admin-user-<?php echo (int) $user['userId']; ?>" class="<?php echo (int) $user['userId'] === $highlightUserId ? 'request-highlight' : ''; ?>">
                                    <td><?php echo htmlspecialchars($displayUserName); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($email); ?></td>
                                    <td><?php echo htmlspecialchars($phone); ?></td>
                                    <td><span class="role-pill <?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></td>
                                    <td><span class="status-pill <?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(accountStatusLabel($status)); ?></span></td>
                                    <td><?php echo htmlspecialchars(date('m/d/Y', strtotime($user['created_at']))); ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <?php if ($canReview): ?>
                                                <button
                                                    type="button"
                                                    class="mini-action verify open-modal-button"
                                                    data-modal-target="<?php echo htmlspecialchars($reviewModalId); ?>">
                                                    View &amp; Verify
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="mini-action verify disabled" disabled>View &amp; Verify</button>
                                            <?php endif; ?>
                                            <?php if ((int) $user['userId'] !== (int) $_SESSION['user_id']): ?>
                                                <form method="POST" action="../actions/deleteUser.php" onsubmit="return confirm('Delete this user account?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['userId']; ?>">
                                                    <button type="submit" class="mini-action delete">Delete</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted-text">Current user</span>
                                            <?php endif; ?>
                                            <?php if ((int) $user['userId'] !== (int) $_SESSION['user_id']): ?>
                                                <button type="button" class="mini-action edit open-modal-button" data-modal-target="editUserModal<?php echo (int) $user['userId']; ?>">Edit</button>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($canReview): ?>
                                            <div class="modal-backdrop" id="<?php echo htmlspecialchars($reviewModalId); ?>" aria-hidden="true" hidden data-allow-backdrop="false">
                                                <div class="modal-panel review-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($reviewModalId); ?>Title">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h3 id="<?php echo htmlspecialchars($reviewModalId); ?>Title">View &amp; Verify</h3>
                                                            <p><?php echo htmlspecialchars($displayUserName); ?> - <?php echo htmlspecialchars(accountStatusLabel($status)); ?></p>
                                                        </div>
                                                        <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                                                    </div>
                                                    <div class="modal-form review-content">
                                                        <div class="review-details">
                                                            <span>Username: <strong><?php echo htmlspecialchars($user['username']); ?></strong></span>
                                                            <span>Email: <strong><?php echo htmlspecialchars($email); ?></strong></span>
                                                            <span>Phone: <strong><?php echo htmlspecialchars($phone); ?></strong></span>
                                                        </div>

                                                        <?php if ($rejectReason !== ''): ?>
                                                            <div class="reason-note">
                                                                <strong>Previous reason</strong>
                                                                <p><?php echo htmlspecialchars($rejectReason); ?></p>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="proof-preview-box">
                                                            <div class="proof-preview-header">
                                                                <strong>Submitted proof</strong>
                                                                <a href="<?php echo htmlspecialchars($proofUrl); ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i>Open file</a>
                                                            </div>
                                                            <iframe src="<?php echo htmlspecialchars($proofUrl); ?>" title="Verification proof for <?php echo htmlspecialchars($displayUserName); ?>"></iframe>
                                                        </div>

                                                        <div class="review-actions">
                                                            <form method="POST" action="../actions/updateUserVerification.php">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <input type="hidden" name="user_id" value="<?php echo (int) $user['userId']; ?>">
                                                                <input type="hidden" name="status" value="verified">
                                                                <button type="submit" class="add-user-button approve-submit"><i class="bi bi-check-circle"></i>Verify</button>
                                                            </form>
                                                            <button
                                                                type="button"
                                                                class="add-user-button reject-submit open-modal-button"
                                                                data-modal-target="<?php echo htmlspecialchars($rejectModalId); ?>">
                                                                <i class="bi bi-x-circle"></i>Reject
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-backdrop" id="<?php echo htmlspecialchars($rejectModalId); ?>" aria-hidden="true" hidden>
                                                <div class="modal-panel reject-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($rejectModalId); ?>Title">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h3 id="<?php echo htmlspecialchars($rejectModalId); ?>Title">Reject Verification</h3>
                                                            <p><?php echo htmlspecialchars($displayUserName); ?></p>
                                                        </div>
                                                        <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                                                    </div>
                                                    <form method="POST" action="../actions/updateUserVerification.php" class="modal-form reject-review-form">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['userId']; ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <label for="rejectReason<?php echo (int) $user['userId']; ?>">Reason</label>
                                                        <textarea id="rejectReason<?php echo (int) $user['userId']; ?>" name="rejection_reason" rows="4" placeholder="Example: Uploaded proof is blurry or does not match the registered details." required></textarea>
                                                        <div class="reject-modal-actions">
                                                            <button type="button" class="modal-secondary-button" data-modal-close>Cancel</button>
                                                            <button type="submit" class="add-user-button reject-submit"><i class="bi bi-x-circle"></i>Submit</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ((int) $user['userId'] !== (int) $_SESSION['user_id']): ?>
                                            <div class="modal-backdrop" id="editUserModal<?php echo (int) $user['userId']; ?>" aria-hidden="true" hidden>
                                                <div class="modal-panel edit-user-modal" role="dialog" aria-modal="true" aria-labelledby="editUserModal<?php echo (int) $user['userId']; ?>Title">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h3 id="editUserModal<?php echo (int) $user['userId']; ?>Title">Edit User</h3>
                                                            <p><?php echo htmlspecialchars($displayUserName); ?></p>
                                                        </div>
                                                        <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                                                    </div>
                                                    <form method="POST" action="../actions/updateUser.php" class="modal-form">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['userId']; ?>">
                                                        <div>
                                                            <label>First Name</label>
                                                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($accountFirstName); ?>" placeholder="First name">
                                                        </div>
                                                        <div>
                                                            <label>Surname</label>
                                                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($accountLastName); ?>" placeholder="Surname">
                                                        </div>
                                                        <div>
                                                            <label>Phone Number</label>
                                                            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($accountPhone); ?>" placeholder="09XXXXXXXXX">
                                                        </div>
                                                        <div>
                                                            <label>Email Address</label>
                                                            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($accountEmail); ?>" placeholder="Email address">
                                                        </div>
                                                        <div>
                                                            <label>Username</label>
                                                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                        </div>
                                                        <div>
                                                            <label>Role</label>
                                                            <select name="role" required>
                                                                <option value="resident" <?php echo $user['role'] === 'resident' ? 'selected' : ''; ?>>Resident</option>
                                                                <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label>New Password (leave blank to keep current)</label>
                                                            <input type="password" name="password" placeholder="New password">
                                                        </div>
                                                        <div class="form-actions">
                                                            <button type="submit" class="add-user-button">Save</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-backdrop" id="addUserModal" aria-hidden="true" hidden>
                <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle">
                    <div class="modal-header">
                        <div>
                            <h3 id="addUserModalTitle">Add User</h3>
                            <p>Create a staff or admin account.</p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <form class="add-user-form modal-form" method="POST" action="../actions/addUser.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div>
                            <label>First Name</label>
                            <input type="text" name="first_name" placeholder="Enter first name" required>
                        </div>
                        <div>
                            <label>Surname</label>
                            <input type="text" name="last_name" placeholder="Enter surname" required>
                        </div>
                        <div>
                            <label>Phone Number</label>
                            <input type="tel" name="contact_phone" placeholder="09XXXXXXXXX" required>
                        </div>
                        <div>
                            <label>Email Address</label>
                            <input type="email" name="contact_email" placeholder="Enter email address" required>
                        </div>
                        <div>
                            <label>Username</label>
                            <input type="text" name="username" placeholder="Enter username" required>
                        </div>
                        <div>
                            <label>Role</label>
                            <select name="role" required>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Minimum 8 characters" required>
                        </div>
                        <div>
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm password" required>
                        </div>
                        <button type="submit" class="add-user-button"><i class="bi bi-plus-lg"></i>Add User</button>
                    </form>
                </div>
            </div>

            <?php elseif ($activeSection === 'reports'): ?>
            <div class="requests-card report-card" id="printableReport">
                <div class="card-heading">
                    <div class="title-row">
                        <i class="bi bi-printer"></i>
                        <div>
                            <h2>Printable Request Report</h2>
                            <p><?php echo htmlspecialchars($reportRangeLabel); ?> request summary generated on <?php echo htmlspecialchars($reportGeneratedAt); ?></p>
                        </div>
                    </div>
                    <button type="button" class="add-user-button report-print-button" data-print-report>
                        <i class="bi bi-printer"></i>Print Report
                    </button>
                </div>

                <form class="report-filter-panel" method="GET">
                    <input type="hidden" name="section" value="reports">
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
                                    $reportRequestNumber = $reportRow['request_number'] ?: adminRequestNumber($reportRequestId, $reportRow['date_submitted'] ?? '');
                                    $reportResidentName = trim(($reportRow['firstName'] ?? '') . ' ' . ($reportRow['lastName'] ?? ''));
                                    $reportResidentName = $reportResidentName ?: ($reportRow['username'] ?? 'Unknown User');
                                    $reportStatus = strtolower((string) ($reportRow['status'] ?? 'pending'));
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reportRequestNumber); ?></td>
                                        <td><?php echo htmlspecialchars($reportResidentName); ?></td>
                                        <td><?php echo htmlspecialchars($reportRow['document_type'] ?? 'Document'); ?></td>
                                        <td><span class="status-pill <?php echo htmlspecialchars(adminStatusClass($reportStatus)); ?>"><?php echo htmlspecialchars(adminStatusLabel($reportStatus)); ?></span></td>
                                        <td><?php echo htmlspecialchars(!empty($reportRow['date_submitted']) ? date('m/d/Y', strtotime($reportRow['date_submitted'])) : 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php elseif ($activeSection === 'backup'): ?>
            <div class="requests-card backup-card">
                <div class="card-heading">
                    <div class="title-row">
                        <i class="bi bi-database-down"></i>
                        <div>
                            <h2>Database Backup</h2>
                            <p>Create an encrypted backup of the database only</p>
                        </div>
                    </div>
                    <button type="button" class="add-user-button open-modal-button" data-modal-target="createBackupModal">
                        <i class="bi bi-download"></i>Create Backup
                    </button>
                </div>

                <div class="backup-note">
                    New backups are password-encrypted and include database tables and data only. Keep the password securely; it is required to restore the encrypted backup. Existing SQL and ZIP backups remain restorable.
                </div>

                <div class="table-wrap">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Recent Backup</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Date Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentBackups)): ?>
                            <tr><td colspan="5" class="empty-cell">No local backups created yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentBackups as $backup): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                    <td><?php echo htmlspecialchars($backup['type']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format(((int) $backup['size']) / 1024, 1)); ?> KB</td>
                                    <td><?php echo htmlspecialchars(date('m/d/Y h:i A', (int) $backup['date'])); ?></td>
                                    <td>
                                        <button type="button" class="add-user-button reject-submit open-modal-button" data-modal-target="restoreBackupModal" data-backup-name="<?php echo htmlspecialchars($backup['name']); ?>">
                                            <i class="bi bi-arrow-counterclockwise"></i>Restore
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="modal-backdrop" id="restoreBackupModal" aria-hidden="true" hidden>
                    <div class="modal-panel reject-modal" role="dialog" aria-modal="true" aria-labelledby="restoreBackupModalTitle">
                        <div class="modal-header">
                            <div>
                                <h3 id="restoreBackupModalTitle">Restore Database Backup</h3>
                                <p id="restoreBackupFilename">Selected backup</p>
                            </div>
                            <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <form method="POST" action="../actions/restoreDatabaseBackup.php" class="modal-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="backup_file" value="" data-backup-file>
                            <p>This replaces the current database tables and data. System files will not be changed.</p>
                            <label for="restoreBackupPassword">Encryption Password</label>
                            <input type="password" id="restoreBackupPassword" name="restore_password" autocomplete="current-password" placeholder="Required for encrypted backups">
                            <p class="field-note">Leave blank only when restoring an older SQL or ZIP backup.</p>
                            <label for="confirmRestoreBackup">
                                <input type="checkbox" id="confirmRestoreBackup" name="confirm_restore" value="1" required>
                                I understand that the current database data will be replaced.
                            </label>
                            <div class="reject-modal-actions">
                                <button type="button" class="modal-secondary-button" data-modal-close>Cancel</button>
                                <button type="submit" class="add-user-button reject-submit"><i class="bi bi-arrow-counterclockwise"></i>Restore Database</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal-backdrop" id="createBackupModal" aria-hidden="true" hidden>
                    <div class="modal-panel reject-modal" role="dialog" aria-modal="true" aria-labelledby="createBackupModalTitle">
                        <div class="modal-header">
                            <div>
                                <h3 id="createBackupModalTitle">Encrypt Database Backup</h3>
                                <p>Set a password for this backup file.</p>
                            </div>
                            <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <form method="POST" action="../actions/createDatabaseBackup.php" class="modal-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <label for="backupPassword">Backup Password</label>
                            <input type="password" id="backupPassword" name="backup_password" minlength="12" autocomplete="new-password" required>
                            <label for="confirmBackupPassword">Confirm Backup Password</label>
                            <input type="password" id="confirmBackupPassword" name="confirm_backup_password" minlength="12" autocomplete="new-password" required>
                            <p class="field-note">Use at least 12 characters. This password is not stored and cannot be recovered by the system.</p>
                            <div class="reject-modal-actions">
                                <button type="button" class="modal-secondary-button" data-modal-close>Cancel</button>
                                <button type="submit" class="add-user-button"><i class="bi bi-shield-lock"></i>Create Encrypted Backup</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php elseif ($activeSection === 'templates'): ?>
            <div class="requests-card document-template-shell">
                <section id="section-document-templates">
                    <div class="section-header">
                        <div class="section-title-area">
                            <h2>Document Templates</h2>
                            <p>Manage barangay document templates and requirements</p>
                        </div>
                        <button type="button" class="btn-primary open-template-modal" data-template-modal="createTemplateModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Template</span>
                        </button>
                    </div>

                    <div class="cards-grid">
                        <?php if (empty($templates)): ?>
                            <div class="template-card empty-template-card">
                                <p>No templates yet. Create your first one to get started.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                                <?php
                                $templateId = (int) $template['id'];
                                $fee = number_format((float) ($template['fee'] ?? 0), 2);
                                $lastModified = $template['last_modified'] ?: $template['created_at'];
                                $isDefaultTemplate = !empty($template['is_default']);
                                $templateModalId = 'editTemplateModal' . $templateId;
                                $deleteModalId = 'deleteTemplateModal' . $templateId;
                                $previewModalId = ($isDefaultTemplate ? 'viewDefaultTemplateModal' : 'viewTemplateModal') . abs($templateId);
                                $templateName = htmlspecialchars($template['name'] ?? 'Untitled');
                                ?>
                                <div class="template-card">
                                    <div class="card-header">
                                        <div class="card-icon">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <div class="card-title-group">
                                            <h3><?php echo $templateName; ?></h3>
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
                                            <span class="text-green">₱<?php echo htmlspecialchars($fee); ?></span>
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
                                                <span>Download</span>
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
                                </div>

                                <div class="modal-backdrop" id="<?php echo htmlspecialchars($previewModalId); ?>" aria-hidden="true" hidden>
                                    <div class="modal-panel template-modal template-preview-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($previewModalId); ?>Title">
                                        <div class="modal-header">
                                            <div>
                                                <h3 id="<?php echo htmlspecialchars($previewModalId); ?>Title"><?php echo $templateName; ?></h3>
                                                <p><?php echo $isDefaultTemplate ? 'Built-in barangay template' : 'Saved template content'; ?></p>
                                            </div>
                                            <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                        <div class="template-preview-frame">
                                            <?php adminTemplatePreview($template['name'] ?? '', $template['template_content'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-backdrop" id="<?php echo htmlspecialchars($templateModalId); ?>" aria-hidden="true" hidden>
                                    <div class="modal-panel template-modal template-editor-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($templateModalId); ?>Title">
                                        <div class="modal-header">
                                            <div>
                                                <h3 id="<?php echo htmlspecialchars($templateModalId); ?>Title"><?php echo $isDefaultTemplate ? 'Edit Built-in Template' : 'Edit Template'; ?></h3>
                                                <p><?php echo $templateName; ?></p>
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
                                                    <?php adminTemplatePreview($template['name'] ?? '', $template['template_content'] ?? '', true); ?>
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
                                                <p><?php echo $templateName; ?></p>
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
                        <?php endif; ?>
                    </div>
                </section>
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
                                <?php adminTemplatePreview('', '', true); ?>
                            </aside>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($activeSection === 'approved'): ?>
            <div class="requests-card">
                <div class="card-heading">
                    <div class="title-row">
                        <i class="bi bi-file-earmark-check"></i>
                        <div>
                            <h2>Approved Documents</h2>
                            <p>Approved and printed requests ready for the next step</p>
                        </div>
                    </div>

                    <form class="filter-form" method="GET">
                        <input type="hidden" name="section" value="approved">
                        <label class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="search" name="search" placeholder="Search approved..." value="<?php echo htmlspecialchars($search); ?>">
                        </label>
                    </form>
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
                                $requestId = (int) $request['request_id'];
                                $requestNumber = $request['request_number'] ?: adminRequestNumber($requestId, $request['date_submitted']);
                                $residentName = trim(($request['firstName'] ?? '') . ' ' . ($request['lastName'] ?? ''));
                                $userName = $residentName ?: ($request['emailAddress'] ?: ($request['username'] ?? 'Unknown User'));
                                $approvedDate = $request['reviewed_at'] ?: $request['date_updated'];
                                $requestStatus = strtolower((string) ($request['status'] ?? 'approved'));
                                $approvedModalId = 'approvedDocModal' . $requestId;
                                $requestFiles = fetchRequestFiles($conn, $requestId);
                                $approvedRedirect = http_build_query(['section' => 'approved', 'search' => $search]);
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
                                $certificatePreviewId = 'adminCertificatePreview' . $requestId;
                                ?>
                                <tr id="admin-request-<?php echo $requestId; ?>" class="<?php echo $requestId === $highlightRequestId ? 'request-highlight' : ''; ?>">
                                    <td><?php echo htmlspecialchars($requestNumber); ?></td>
                                    <td><span class="user-link"><?php echo htmlspecialchars($userName); ?></span></td>
                                    <td><?php echo htmlspecialchars($request['document_type']); ?></td>
                                    <td><?php echo htmlspecialchars($request['purpose'] ?: 'Not specified'); ?></td>
                                    <td><?php echo htmlspecialchars(date('m/d/Y', strtotime($approvedDate))); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo htmlspecialchars(adminStatusClass($requestStatus)); ?>">
                                            <?php echo htmlspecialchars(adminStatusLabel($requestStatus)); ?>
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
                                                        <p><?php echo htmlspecialchars($userName); ?> - <?php echo htmlspecialchars($request['document_type']); ?></p>
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

            <?php elseif ($activeSection === 'overview'): ?>
            <div class="overview-shell">
                <section class="overview-content-grid">
                    <div class="overview-card">
                        <h3>Recent Activity</h3>
                        <div class="overview-activity-list">
                            <?php if (empty($recentActivities)): ?>
                                <div class="overview-activity-item">
                                    <div class="overview-activity-left">
                                        <i class="bi bi-info-circle overview-activity-icon"></i>
                                        <div>
                                            <div class="overview-activity-row">
                                                <span class="overview-activity-user">System</span>
                                                <span class="overview-badge overview-badge-default">Info</span>
                                            </div>
                                            <span class="overview-activity-desc">No recent system activity yet.</span>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <?php
                                    $activityAction = strtolower((string) ($activity['action'] ?? 'activity'));
                                    $activityIcon = 'bi-activity';
                                    $activityBadgeClass = 'overview-badge-default';
                                    $activityBadgeText = 'Activity';

                                    if ($activityAction === 'login') {
                                        $activityIcon = 'bi-box-arrow-in-right';
                                        $activityBadgeClass = 'overview-badge-login';
                                        $activityBadgeText = 'Login';
                                    } elseif ($activityAction === 'logout') {
                                        $activityIcon = 'bi-box-arrow-right';
                                        $activityBadgeClass = 'overview-badge-logout';
                                        $activityBadgeText = 'Logout';
                                    } elseif (strpos($activityAction, 'request_') === 0) {
                                        $activityIcon = 'bi-file-earmark-check';
                                        $activityBadgeClass = 'overview-badge-approved';
                                        $activityBadgeText = 'Request';
                                    } elseif (strpos($activityAction, 'verification_') === 0) {
                                        $activityIcon = 'bi-person-check';
                                        $activityBadgeClass = 'overview-badge-approved';
                                        $activityBadgeText = 'Verification';
                                    }
                                    ?>
                                    <div class="overview-activity-item">
                                        <div class="overview-activity-left">
                                            <i class="bi <?php echo htmlspecialchars($activityIcon); ?> overview-activity-icon"></i>
                                            <div>
                                                <div class="overview-activity-row">
                                                    <span class="overview-activity-user"><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></span>
                                                    <span class="overview-badge <?php echo htmlspecialchars($activityBadgeClass); ?>"><?php echo htmlspecialchars($activityBadgeText); ?></span>
                                                </div>
                                                <span class="overview-activity-desc"><?php echo htmlspecialchars(formatActivityLogDescription($conn, $activity['description'] ?? 'System activity')); ?></span>
                                            </div>
                                        </div>
                                        <div class="overview-activity-right">
                                            <span class="overview-activity-date"><?php echo htmlspecialchars(date('M d', strtotime($activity['created_at']))); ?></span>
                                            <span class="overview-activity-time"><?php echo htmlspecialchars(date('h:i A', strtotime($activity['created_at']))); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="overview-card">
                        <h3>Population Overview</h3>
                        <div class="overview-status-list">
                            <div class="overview-status-item overview-status-operational">
                                <span class="overview-status-label">Total Population</span>
                                <span class="overview-status-value text-success"><?php echo (int) $populationStats['total']; ?></span>
                            </div>
                            <div class="overview-status-item overview-status-connected">
                                <span class="overview-status-label">Male</span>
                                <span class="overview-status-value text-info"><?php echo (int) $populationStats['male']; ?></span>
                            </div>
                            <div class="overview-status-item overview-status-notset">
                                <span class="overview-status-label">Female</span>
                                <span class="overview-status-value text-warning"><?php echo (int) $populationStats['female']; ?></span>
                            </div>
                            <div class="overview-status-item overview-status-connected">
                                <span class="overview-status-label">Adults</span>
                                <span class="overview-status-value text-info"><?php echo (int) $populationStats['adults']; ?></span>
                            </div>
                            <div class="overview-status-item overview-status-operational">
                                <span class="overview-status-label">Elders</span>
                                <span class="overview-status-value text-success"><?php echo (int) $populationStats['elders']; ?></span>
                            </div>
                            <div class="overview-status-item overview-status-connected">
                                <span class="overview-status-label">Teenagers</span>
                                <span class="overview-status-value text-info"><?php echo (int) $populationStats['teenagers']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="overview-card">
                        <h3>System Status</h3>
                        <div class="overview-status-list">
                            <div class="overview-status-item overview-status-operational">
                                <span class="overview-status-label">System Status</span>
                                <span class="overview-status-value text-success">Operational</span>
                            </div>
                            <div class="overview-status-item overview-status-connected">
                                <span class="overview-status-label">Database</span>
                                <span class="overview-status-value text-info">Connected</span>
                            </div>
                            <div class="overview-status-item overview-status-notset">
                                <span class="overview-status-label">Last Backup</span>
                                <span class="overview-status-value text-warning">
                                    <?php echo !empty($recentBackups) ? htmlspecialchars(date('M d, h:i A', (int) $recentBackups[0]['date'])) : 'Not Set'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php elseif ($activeSection === 'activity'): ?>
            <div class="activity-log-shell">
                <section class="activity-log-card">
                    <div class="card-heading activity-log-heading">
                        <div class="title-row">
                            <i class="bi bi-activity"></i>
                            <div>
                                <h2>Activity Log</h2>
                                <p>Track system activity and barangay operations</p>
                            </div>
                        </div>
                    </div>

                    <form class="filters-bar activity-log-filters" method="GET">
                        <input type="hidden" name="section" value="activity">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="search" name="activity_search" class="search-input" placeholder="Search by user, action, or details..." value="<?php echo htmlspecialchars($activitySearch); ?>" />
                        </div>
                        <button type="submit" class="dropdown-btn">Search <i class="bi bi-search"></i></button>
                    </form>

                    <div class="activity-list">
                        <?php if (empty($activityLogs)): ?>
                            <div class="activity-empty">No activity recorded yet.</div>
                        <?php else: ?>
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
                                }
                                ?>
                                <article class="activity-card">
                                    <div class="card-main">
                                        <div class="card-icon">
                                            <i class="bi <?php echo htmlspecialchars($activityIcon); ?>"></i>
                                        </div>
                                        <div class="card-info">
                                            <div class="card-meta">
                                                <span class="user-name"><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></span>
                                                <span class="separator">•</span>
                                                <span class="action-type"><?php echo htmlspecialchars($activityBadgeText); ?></span>
                                            </div>
                                            <p class="action-desc"><?php echo htmlspecialchars(formatActivityLogDescription($conn, $activity['description'] ?? 'System activity')); ?></p>
                                            <div class="action-time">
                                                <i class="bi bi-clock"></i>
                                                <time><?php echo htmlspecialchars(date('M d, Y, h:i A', strtotime($activity['created_at']))); ?></time>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-status">
                                        <span class="badge <?php echo htmlspecialchars($activityBadgeClass); ?>"><?php echo htmlspecialchars($activityStatusText); ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
            <?php else: ?>
            <div class="requests-card">
                <div class="card-heading">
                    <div class="title-row">
                        <i class="bi bi-file-earmark-text"></i>
                        <div>
                            <h2><?php echo $activeSection === 'pending' ? 'Pending Requests' : ($activeSection === 'archive' ? 'Archived Request History' : 'Request History'); ?></h2>
                            <p><?php echo $activeSection === 'pending' ? 'Newest pending document requests first' : ($activeSection === 'archive' ? 'Completed, printed, or rejected requests older than ' . $archiveCutoffLabel : 'Approved, printed, released, and rejected request history'); ?></p>
                        </div>
                    </div>

                    <form class="filter-form" method="GET">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($activeSection); ?>">
                        <?php if ($activeSection === 'requests'): ?>
                            <select name="status" aria-label="Filter by status" onchange="this.form.submit()">
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <label class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="search" name="search" placeholder="Search request..." value="<?php echo htmlspecialchars($search); ?>">
                        </label>
                    </form>
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
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="empty-cell"><?php echo $activeSection === 'pending' ? 'No pending document requests found.' : ($activeSection === 'archive' ? 'No archived request history found.' : 'No request history found.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                $requestId = (int) $request['request_id'];
                                $requestNumber = $request['request_number'] ?: adminRequestNumber($requestId, $request['date_submitted']);
                                $residentName = trim(($request['firstName'] ?? '') . ' ' . ($request['lastName'] ?? ''));
                                $userName = $residentName ?: ($request['emailAddress'] ?: ($request['username'] ?? 'Unknown User'));
                                $dateRequested = $request['date_submitted'];
                                $requestModalId = 'requestReviewModal' . $requestId;
                                $requestRejectModalId = 'rejectRequestModal' . $requestId;
                                $requestFiles = fetchRequestFiles($conn, $requestId);
                                $requestRedirectData = ['section' => $activeSection, 'search' => $search];
                                if ($activeSection === 'requests') {
                                    $requestRedirectData['status'] = $statusFilter;
                                }
                                $requestRedirect = http_build_query($requestRedirectData);
                                $requestStatus = strtolower((string) ($request['status'] ?? 'pending'));
                                $canUpdateRequest = $requestStatus === 'pending';
                                ?>
                                <tr id="admin-request-<?php echo $requestId; ?>" class="<?php echo $requestId === $highlightRequestId ? 'request-highlight' : ''; ?>">
                                    <td><?php echo htmlspecialchars($requestNumber); ?></td>
                                    <td><span class="user-link"><?php echo htmlspecialchars($userName); ?></span></td>
                                    <td><?php echo htmlspecialchars($request['document_type']); ?></td>
                                    <td><?php echo htmlspecialchars($request['purpose'] ?: 'Not specified'); ?></td>
                                    <td><?php echo htmlspecialchars(date('m/d/Y', strtotime($dateRequested))); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo htmlspecialchars(adminStatusClass($request['status'])); ?>">
                                            <?php echo htmlspecialchars(adminStatusLabel($request['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="verify-button open-modal-button" data-modal-target="<?php echo htmlspecialchars($requestModalId); ?>">
                                                <i class="bi bi-eye"></i>
                                                <span><?php echo $activeSection === 'pending' ? 'View &amp; Verify' : 'View Details'; ?></span>
                                            </button>
                                        </div>
                                        <div class="modal-backdrop" id="<?php echo htmlspecialchars($requestModalId); ?>" aria-hidden="true" hidden data-allow-backdrop="false">
                                            <div class="modal-panel request-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($requestModalId); ?>Title">
                                                <div class="modal-header">
                                                    <div>
                                                        <h3 id="<?php echo htmlspecialchars($requestModalId); ?>Title"><?php echo htmlspecialchars($requestNumber); ?></h3>
                                                        <p><?php echo htmlspecialchars($userName); ?> - <?php echo htmlspecialchars(adminStatusLabel($request['status'])); ?></p>
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
                                                            <strong><?php echo htmlspecialchars(adminStatusLabel($request['status'])); ?></strong>
                                                        </div>
                                                        <div>
                                                            <span>Document Type</span>
                                                            <strong><?php echo htmlspecialchars($request['document_type']); ?></strong>
                                                        </div>
                                                        <div>
                                                            <span>Date Requested</span>
                                                            <strong><?php echo htmlspecialchars(date('m/d/Y', strtotime($dateRequested))); ?></strong>
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

                                                    <div class="reason-note">
                                                        <strong>Status Message</strong>
                                                        <p><?php echo htmlspecialchars(adminStatusMessage($requestStatus)); ?></p>
                                                    </div>

                                                    <?php if (trim((string) ($request['admin_remarks'] ?? '')) !== ''): ?>
                                                        <div class="reason-note">
                                                            <strong>Message</strong>
                                                            <p><?php echo htmlspecialchars($request['admin_remarks']); ?></p>
                                                        </div>
                                                    <?php endif; ?>

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

                                                    <?php if ($canUpdateRequest): ?>
                                                        <div class="review-actions">
                                                            <form method="POST" action="../actions/updateRequestStatus.php">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                                                                <input type="hidden" name="redirect_query" value="<?php echo htmlspecialchars($requestRedirect); ?>">
                                                                <button type="submit" name="status" value="approved" class="add-user-button approve-submit"><i class="bi bi-check-circle"></i>Approve</button>
                                                            </form>
                                                            <button type="button" class="add-user-button reject-submit open-modal-button" data-modal-target="<?php echo htmlspecialchars($requestRejectModalId); ?>">
                                                                <i class="bi bi-x-circle"></i>Reject
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($canUpdateRequest): ?>
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
                                                        <input type="hidden" name="redirect_query" value="<?php echo htmlspecialchars($requestRedirect); ?>">
                                                        <label for="requestRejectReason<?php echo $requestId; ?>">Reason</label>
                                                        <textarea id="requestRejectReason<?php echo $requestId; ?>" name="admin_remarks" rows="4" placeholder="Example: Missing or invalid uploaded requirement." required></textarea>
                                                        <div class="reject-modal-actions">
                                                            <button type="button" class="modal-secondary-button" data-modal-close>Cancel</button>
                                                            <button type="submit" name="status" value="rejected" class="add-user-button reject-submit"><i class="bi bi-x-circle"></i>Submit</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Edit current admin account modal -->
<div class="modal-backdrop" id="editSelfModal" aria-hidden="true" hidden>
    <div class="modal-panel edit-user-modal" role="dialog" aria-modal="true" aria-labelledby="editSelfModalTitle">
        <div class="modal-header">
            <div>
                <h3 id="editSelfModalTitle">Account Settings</h3>
                <p>Update your username or password</p>
            </div>
            <button type="button" class="modal-close" data-modal-close aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form method="POST" action="../actions/updateSelf.php" class="modal-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
    const highlightedUserId = params.get('highlight_user');
    const highlightedRequestId = params.get('highlight_request');

    const openModal = function (modal) {
        if (!modal) return;
        modal.hidden = false;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea, button');
        if (firstInput) firstInput.focus();
    };

    const closeModal = function (modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.hidden = true;
    };

    document.querySelectorAll('[data-modal-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = document.getElementById(button.dataset.modalTarget);
            if (modal && button.dataset.backupName) {
                const backupFileInput = modal.querySelector('[data-backup-file]');
                const backupFilename = modal.querySelector('#restoreBackupFilename');
                if (backupFileInput) backupFileInput.value = button.dataset.backupName;
                if (backupFilename) backupFilename.textContent = button.dataset.backupName;
            }
            openModal(modal);
        });
    });

    const highlightedRow = highlightedUserId
        ? document.getElementById('admin-user-' + highlightedUserId)
        : (highlightedRequestId ? document.getElementById('admin-request-' + highlightedRequestId) : null);
    if (highlightedRow) {
        highlightedRow.classList.add('request-highlight');
        window.setTimeout(function () {
            highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 120);
    }

    const adminMobileMenuToggle = document.querySelector('.admin-mobile-menu-toggle');
    const adminSectionsMenu = document.getElementById('adminSectionsMenu');
    const closeAdminMobileMenu = function () {
        if (!adminMobileMenuToggle || !adminSectionsMenu) return;
        adminSectionsMenu.classList.remove('is-open');
        adminMobileMenuToggle.classList.remove('is-open');
        adminMobileMenuToggle.setAttribute('aria-expanded', 'false');
    };

    if (adminMobileMenuToggle && adminSectionsMenu) {
        adminMobileMenuToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            const willOpen = !adminSectionsMenu.classList.contains('is-open');
            adminSectionsMenu.classList.toggle('is-open', willOpen);
            adminMobileMenuToggle.classList.toggle('is-open', willOpen);
            adminMobileMenuToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        adminSectionsMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeAdminMobileMenu);
        });

        document.addEventListener('click', function (event) {
            if (adminSectionsMenu.classList.contains('is-open') && !adminSectionsMenu.contains(event.target) && !adminMobileMenuToggle.contains(event.target)) {
                closeAdminMobileMenu();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                closeAdminMobileMenu();
            }
        });
    }

    const adminNavScrollButtons = Array.from(document.querySelectorAll('[data-admin-nav-scroll]'));
    if (adminSectionsMenu && adminNavScrollButtons.length === 2) {
        const updateAdminNavScrollButtons = function () {
            const maxScrollLeft = adminSectionsMenu.scrollWidth - adminSectionsMenu.clientWidth;
            const hasOverflow = maxScrollLeft > 1;

            adminNavScrollButtons.forEach(function (button) {
                button.hidden = !hasOverflow;
            });
            adminNavScrollButtons[0].disabled = adminSectionsMenu.scrollLeft <= 1;
            adminNavScrollButtons[1].disabled = adminSectionsMenu.scrollLeft >= maxScrollLeft - 1;
        };

        adminNavScrollButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const direction = button.dataset.adminNavScroll === 'left' ? -1 : 1;
                adminSectionsMenu.scrollBy({
                    left: direction * Math.max(180, Math.floor(adminSectionsMenu.clientWidth * 0.75)),
                    behavior: 'smooth'
                });
            });
        });

        adminSectionsMenu.addEventListener('scroll', updateAdminNavScrollButtons, { passive: true });
        window.addEventListener('resize', updateAdminNavScrollButtons);
        const activeAdminSection = adminSectionsMenu.querySelector('.nav-link.active');
        if (activeAdminSection) {
            activeAdminSection.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
        updateAdminNavScrollButtons();
    }

    const notificationToggle = document.querySelector('.notification-toggle');
    const notificationMenu = document.getElementById('adminNotificationMenu');
    const notificationBadge = document.querySelector('[data-notification-badge]');
    const notificationItems = Array.from(document.querySelectorAll('[data-notification-key]'));
    const notificationStorageKey = 'adminReadNotifications';
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
        button.addEventListener('click', function () {
            const certificate = document.getElementById(button.dataset.printTarget);
            if (!certificate) return;

            const markPrintedPromise = (button.dataset.currentStatus === 'approved' && button.dataset.requestId && button.dataset.csrfToken)
                ? (function () {
                    const statusData = new FormData();
                    statusData.append('request_id', button.dataset.requestId);
                    statusData.append('csrf_token', button.dataset.csrfToken);
                    statusData.append('status', 'printed');
                    statusData.append('redirect_query', button.dataset.redirectQuery || 'section=approved');

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
        button.addEventListener('click', function () {
            openModal(document.getElementById(button.dataset.templateModal));
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

    document.querySelectorAll('[data-modal-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(button.closest('.modal-backdrop'));
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                if (modal.dataset.allowBackdrop === 'false') return;
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.is-open').forEach(function (modal) {
                if (modal.dataset.allowBackdrop === 'false') return;
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
