<?php
require_once '../auth/session.php';
require_once '../helpers/functions.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ["Please login to continue."];
    header("Location: ../pages/loginPage.php");
    exit();
}

if (!in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header("Location: ../dashboard/" . ($_SESSION['role'] ?? 'resident') . ".php");
    exit();
}

$dashboardPath = '../dashboard/admin.php';
if (($role = $_SESSION['role'] ?? 'admin') === 'staff') {
    $dashboardPath = '../dashboard/staff.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $dashboardPath);
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
    $_SESSION['errors'] = ["Security token mismatch. Please try again."];
    header("Location: " . $dashboardPath);
    exit();
}

if ($conn === null) {
    $_SESSION['errors'] = ["Database connection failed. Please try again later."];
    header("Location: " . $dashboardPath);
    exit();
}

$requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
$status = strtolower(trim($_POST['status'] ?? ''));
$adminRemarks = trim($_POST['admin_remarks'] ?? '');
$role = $_SESSION['role'] ?? 'admin';
$validStatuses = ['approved', 'printed', 'released', 'rejected'];
$redirectQuery = trim($_POST['redirect_query'] ?? '');
$redirectData = [];
parse_str($redirectQuery, $redirectData);
$safeRedirect = [];
if (isset($redirectData['status'])) {
    $safeRedirect['status'] = preg_replace('/[^a-z_]/', '', (string) $redirectData['status']);
}
if (isset($redirectData['search'])) {
    $safeRedirect['search'] = substr((string) $redirectData['search'], 0, 120);
}
if (isset($redirectData['request_id'])) {
    $safeRedirect['request_id'] = (int) $redirectData['request_id'];
}
if (isset($redirectData['section']) && in_array($redirectData['section'], ['pending', 'requests', 'approved', 'users', 'archive', 'reports', 'backup'], true)) {
    $safeRedirect['section'] = $redirectData['section'];
}
if (isset($redirectData['view']) && in_array($redirectData['view'], ['pending', 'approved', 'activity', 'templates', 'complaints'], true)) {
    $safeRedirect['view'] = $redirectData['view'];
}
$redirectUrl = $dashboardPath . (!empty($safeRedirect) ? '?' . http_build_query($safeRedirect) : '');

if ($requestId < 0 || !in_array($status, $validStatuses, true)) {
    $_SESSION['errors'] = ["Invalid request update."];
    header("Location: " . $redirectUrl);
    exit();
}

if ($status === 'rejected' && $adminRemarks === '') {
    $_SESSION['errors'] = ["Please enter a reason before rejecting the request."];
    header("Location: " . $redirectUrl);
    exit();
}

if ($status !== 'rejected') {
    $adminRemarks = null;
}

$adminId = (int) $_SESSION['user_id'];
$requestInfo = [
    'request_number' => '',
    'status' => '',
    'document_type' => '',
    'template_id' => 0,
    'username' => '',
    'firstName' => '',
    'lastName' => '',
];
$requestFound = false;
$infoStmt = mysqli_prepare(
    $conn,
    "SELECT
        r.request_number,
        r.status,
        r.document_type,
        r.template_id,
        u.username,
        res.firstName,
        res.lastName
     FROM document_requests r
     LEFT JOIN users u ON r.user_id = u.userId
     LEFT JOIN residents res ON u.residentId = res.residentId
     WHERE r.request_id = ?
     LIMIT 1"
);
if ($infoStmt) {
    mysqli_stmt_bind_param($infoStmt, "i", $requestId);
    mysqli_stmt_execute($infoStmt);
    $infoResult = mysqli_stmt_get_result($infoStmt);
    if ($infoRow = mysqli_fetch_assoc($infoResult)) {
        $requestInfo = $infoRow;
        $requestFound = true;
    }
    mysqli_stmt_close($infoStmt);
}

if (!$requestFound) {
    $_SESSION['errors'] = ["Request not found. Please refresh the page and try again."];
    header("Location: " . $redirectUrl);
    exit();
}

$currentStatus = strtolower((string) ($requestInfo['status'] ?? ''));
if (in_array($status, ['approved', 'rejected'], true) && $currentStatus !== 'pending') {
    $_SESSION['errors'] = ["Only pending requests can be approved or rejected."];
    header("Location: " . $redirectUrl);
    exit();
}

if ($status === 'printed' && $currentStatus !== 'approved') {
    $_SESSION['errors'] = ["Only approved requests can be marked as printed."];
    header("Location: " . $redirectUrl);
    exit();
}

if ($status === 'released' && !in_array($currentStatus, ['approved', 'printed'], true)) {
    $_SESSION['errors'] = ["Only approved or printed requests can be marked as released."];
    header("Location: " . $redirectUrl);
    exit();
}

if ($role === 'staff') {
    if (in_array($status, ['approved', 'rejected'], true) && $currentStatus !== 'pending') {
        $_SESSION['errors'] = ["Staff can only approve or reject pending requests."];
        header("Location: " . $redirectUrl);
        exit();
    }
    if ($status === 'printed' && $currentStatus !== 'approved') {
        $_SESSION['errors'] = ["Staff can only mark approved requests as printed."];
        header("Location: " . $redirectUrl);
        exit();
    }
    if ($status === 'released' && !in_array($currentStatus, ['approved', 'printed'], true)) {
        $_SESSION['errors'] = ["Staff can only release approved or printed requests."];
        header("Location: " . $redirectUrl);
        exit();
    }
}

$stmt = mysqli_prepare($conn, "UPDATE document_requests SET status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ?");
if (!$stmt) {
    $_SESSION['errors'] = ["Failed to prepare request update."];
    header("Location: " . $redirectUrl);
    exit();
}

mysqli_stmt_bind_param($stmt, "ssii", $status, $adminRemarks, $adminId, $requestId);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0) {
    $requestNumber = trim((string) ($requestInfo['request_number'] ?? ''));
    $requestLabel = $requestNumber !== '' ? $requestNumber : ('Request #' . $requestId);
    $documentType = trim((string) ($requestInfo['document_type'] ?? ''));
    $residentName = trim((string) ($requestInfo['firstName'] ?? '') . ' ' . (string) ($requestInfo['lastName'] ?? ''));
    $requestUsername = trim((string) ($requestInfo['username'] ?? ''));
    $requestDetails = $requestLabel;
    if ($documentType !== '') {
        $requestDetails .= ' (' . $documentType . ')';
    }
    if ($residentName !== '') {
        $requestDetails .= ' for resident ' . $residentName;
    }
    if ($requestUsername !== '') {
        $requestDetails .= ' (username: ' . $requestUsername . ')';
    }
    $actorRole = ucfirst((string) $role);
    logActivity($conn, $adminId, $_SESSION['username'] ?? 'Admin', $role, 'request_' . $status, $actorRole . ' ' . $status . ' document request: ' . $requestDetails . '.');
    if ($status === 'approved') {
        generateDocumentForRequest($conn, $requestId, __DIR__ . '/../uploads/generated_documents');
    }
    $successMessages = [
        'approved' => $requestLabel . " approved successfully. It is now listed in Approved Documents.",
        'printed' => $requestLabel . " marked as printed successfully. The resident can now claim it during office hours.",
        'rejected' => $requestLabel . " rejected successfully. It was removed from Pending Requests.",
        'released' => $requestLabel . " marked as released successfully.",
    ];
    $_SESSION['success'] = $successMessages[$status] ?? "Request status updated successfully.";
} else {
    $_SESSION['errors'] = ["Failed to update request status."];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

header("Location: " . $redirectUrl);
exit();
