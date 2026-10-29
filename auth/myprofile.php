<?php
require_once '../auth/session.php';
require_once '../config/db.php';

// Get the logged-in user ID
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: ../pages/loginPage.php");
    exit();
}

// Fetch resident info joined with users table
$query = "SELECT r.firstName, r.lastName, r.middleName, r.suffix, r.dateOfBirth, r.fullAddress, r.phoneNumber, r.emailAddress, r.civilStatus, r.sex, u.role, u.account_status, u.verification_proof, u.verification_rejection_reason, u.avatar_path
          FROM residents r
          JOIN users u ON r.residentId = u.residentId
          WHERE u.userId = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Fallback values
function normalizeProfileValue($value) {
    $value = trim((string) ($value ?? ''));
    return ($value === '' || strtoupper($value) === 'N/A') ? '' : $value;
}

$firstName = normalizeProfileValue($userData['firstName'] ?? 'Resident');
$lastName = normalizeProfileValue($userData['lastName'] ?? '');
$middleName = normalizeProfileValue($userData['middleName'] ?? '');
$suffix = normalizeProfileValue($userData['suffix'] ?? '');
$fullName = trim("$firstName $middleName $lastName $suffix");
$email = normalizeProfileValue($userData['emailAddress'] ?? '');
$phone = normalizeProfileValue($userData['phoneNumber'] ?? '');
$rawDob = normalizeProfileValue($userData['dateOfBirth'] ?? '');
$dob = '';
if ($rawDob !== '') {
    $timestamp = strtotime($rawDob);
    if ($timestamp !== false) {
        $dob = date("F d, Y", $timestamp);
    }
}
$address = normalizeProfileValue($userData['fullAddress'] ?? '');
$civilStatus = normalizeProfileValue($userData['civilStatus'] ?? '');
$sex = normalizeProfileValue($userData['sex'] ?? ''); // New field
$role = ucfirst($userData['role'] ?? '');
$accountStatus = strtolower($userData['account_status'] ?? 'pending');
$accountStatusLabel = [
    'pending' => 'For Verification',
    'verified' => 'Verified',
    'rejected' => 'Rejected',
][$accountStatus] ?? ucfirst($accountStatus);
$verificationProof = normalizeProfileValue($userData['verification_proof'] ?? '');
$verificationRejectionReason = normalizeProfileValue($userData['verification_rejection_reason'] ?? '');
$avatarPath = normalizeProfileValue($userData['avatar_path'] ?? '');

function shouldShowProfileValue($value) {
    return isset($value) && trim($value) !== '';
}
if ($avatarPath === '' && !empty($_SESSION['avatar_path'])) {
    $avatarPath = normalizeProfileValue($_SESSION['avatar_path']);
}
