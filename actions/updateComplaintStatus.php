<?php
require_once '../auth/session.php';
require_once '../helpers/functions.php';
require_once '../config/db.php';

$dashboardPath = '../dashboard/staff.php';

function redirectStaffComplaints($dashboardPath, $redirectQuery = '') {
    $redirectData = [];
    parse_str($redirectQuery, $redirectData);
    $safeRedirect = [];

    if (isset($redirectData['view']) && $redirectData['view'] === 'complaints') {
        $safeRedirect['view'] = 'complaints';
    }

    header('Location: ' . $dashboardPath . (!empty($safeRedirect) ? '?' . http_build_query($safeRedirect) : '?view=complaints'));
    exit();
}

function updateComplaintTableExists($conn) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'complaints'");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row && (int) $row['table_count'] > 0;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['errors'] = ['Please login to continue.'];
    header('Location: ../pages/loginPage.php');
    exit();
}

if (($_SESSION['role'] ?? '') !== 'staff') {
    header('Location: ../dashboard/' . ($_SESSION['role'] ?? 'resident') . '.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectStaffComplaints($dashboardPath);
}

$redirectQuery = trim($_POST['redirect_query'] ?? '');

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['errors'] = ['Security token mismatch. Please try again.'];
    redirectStaffComplaints($dashboardPath, $redirectQuery);
}

if ($conn === null) {
    $_SESSION['errors'] = ['Database connection failed. Please try again later.'];
    redirectStaffComplaints($dashboardPath, $redirectQuery);
}

if (!updateComplaintTableExists($conn)) {
    $_SESSION['errors'] = ['Complaints table is not installed yet. Please run the complaint SQL script first.'];
    redirectStaffComplaints($dashboardPath, $redirectQuery);
}

$complaintId = isset($_POST['complaint_id']) ? (int) $_POST['complaint_id'] : 0;
$status = strtolower(trim((string) ($_POST['status'] ?? '')));
$validStatuses = ['pending', 'done'];

if ($complaintId <= 0 || !in_array($status, $validStatuses, true)) {
    $_SESSION['errors'] = ['Invalid complaint update.'];
    redirectStaffComplaints($dashboardPath, $redirectQuery);
}

$infoStmt = mysqli_prepare($conn, "SELECT complaint_id, status FROM complaints WHERE complaint_id = ? LIMIT 1");
if (!$infoStmt) {
    $_SESSION['errors'] = ['Failed to prepare complaint lookup.'];
    redirectStaffComplaints($dashboardPath, $redirectQuery);
}

mysqli_stmt_bind_param($infoStmt, 'i', $complaintId);
mysqli_stmt_execute($infoStmt);
$infoResult = mysqli_stmt_get_result($infoStmt);
$complaint = $infoResult ? mysqli_fetch_assoc($infoResult) : null;
mysqli_stmt_close($infoStmt);

if (!$complaint) {
    $_SESSION['errors'] = ['Complaint not found. Please refresh the page and try again.'];
    redirectStaffComplaints($dashboardPath, $redirectQuery);
}

$staffId = (int) $_SESSION['user_id'];
if ($status === 'done') {
    $stmt = mysqli_prepare($conn, "UPDATE complaints SET status = 'done', completed_by = ?, completed_at = NOW() WHERE complaint_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $staffId, $complaintId);
    }
} else {
    $stmt = mysqli_prepare($conn, "UPDATE complaints SET status = 'pending', completed_by = NULL, completed_at = NULL WHERE complaint_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $complaintId);
    }
}

if (!$stmt) {
    $_SESSION['errors'] = ['Failed to prepare complaint update.'];
    redirectStaffComplaints($dashboardPath, $redirectQuery);
}

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0) {
    $complaintLabel = 'CMP-' . str_pad((string) $complaintId, 4, '0', STR_PAD_LEFT);
    logActivity($conn, $staffId, $_SESSION['username'] ?? 'Staff', 'staff', 'complaint_' . $status, 'Staff marked complaint ' . $complaintLabel . ' as ' . $status . '.');
    $_SESSION['success'] = 'Complaint ' . $complaintLabel . ' marked as ' . $status . '.';
} else {
    $_SESSION['errors'] = ['Failed to update complaint status.'];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

redirectStaffComplaints($dashboardPath, $redirectQuery);
