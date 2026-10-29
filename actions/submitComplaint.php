<?php
require_once '../auth/session.php';
require_once '../helpers/functions.php';
require_once '../config/db.php';

function redirectComplaint() {
    header('Location: ../pages/complaint.php');
    exit();
}

function complaintTableExists($conn) {
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectComplaint();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['complaint_errors'] = ['Security token mismatch. Please try again.'];
    redirectComplaint();
}

if ($conn === null) {
    $_SESSION['complaint_errors'] = ['Database connection failed. Please try again later.'];
    redirectComplaint();
}

if (!complaintTableExists($conn)) {
    $_SESSION['complaint_errors'] = ['Complaints table is not installed yet. Please run the complaint SQL script first.'];
    redirectComplaint();
}

$allowedComplaints = [
    'Waste Management / Garbage',
    'Roads & Infrastructure',
    'Draining / Flooding',
    'Water Supply',
    'Pollution (Noise / Smoke / Odor)',
    'Stray Animals / Public Safety',
    'Permits & Licensing',
    'Health & Sanitation',
    'Traffic & Parking',
    'Social Services',
    'Rude Employee',
    'Others',
];

$selectedComplaints = [];
foreach (($_POST['complaints'] ?? []) as $complaint) {
    $complaint = trim((string) $complaint);
    if (in_array($complaint, $allowedComplaints, true) && !in_array($complaint, $selectedComplaints, true)) {
        $selectedComplaints[] = $complaint;
    }
}

$location = trim(strip_tags((string) ($_POST['location'] ?? '')));
$description = trim(strip_tags((string) ($_POST['description'] ?? '')));
$errors = [];

if (empty($selectedComplaints)) {
    $errors[] = 'Please select at least one complaint type.';
}

if ($location === '') {
    $errors[] = 'Location is required.';
}

$attachmentName = null;
$attachmentPath = null;
$uploadedPath = null;

if (isset($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['attachment'];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed. Please try again.';
    } elseif (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors[] = 'Attachment must not exceed 5MB.';
    } else {
        $originalName = basename((string) ($file['name'] ?? 'attachment'));
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($file['tmp_name']);
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'Only JPG, PNG, PDF, DOC, and DOCX files are allowed.';
        } else {
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'complaints';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
                $errors[] = 'Unable to prepare upload folder.';
            } else {
                $storedName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $attachmentName = $originalName;
                    $attachmentPath = 'uploads/complaints/' . $storedName;
                    $uploadedPath = $targetPath;
                } else {
                    $errors[] = 'Failed to save uploaded file.';
                }
            }
        }
    }
}

if (!empty($errors)) {
    $_SESSION['complaint_errors'] = $errors;
    redirectComplaint();
}

$categories = implode(', ', $selectedComplaints);
$descriptionValue = $description !== '' ? $description : null;

$stmt = mysqli_prepare($conn, "INSERT INTO complaints (categories, location, description, attachment_name, attachment_path, status) VALUES (?, ?, ?, ?, ?, 'pending')");
if (!$stmt) {
    if ($uploadedPath && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }
    $_SESSION['complaint_errors'] = ['Failed to prepare complaint submission.'];
    redirectComplaint();
}

mysqli_stmt_bind_param($stmt, 'sssss', $categories, $location, $descriptionValue, $attachmentName, $attachmentPath);

if (mysqli_stmt_execute($stmt)) {
    $complaintId = mysqli_insert_id($conn);
    logActivity($conn, 0, 'Guest', 'resident', 'complaint_submitted', 'Complaint submitted: CMP-' . str_pad((string) $complaintId, 4, '0', STR_PAD_LEFT) . '.');
    $_SESSION['complaint_success'] = 'Complaint submitted successfully. Your complaint is now pending staff review.';
} else {
    if ($uploadedPath && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }
    $_SESSION['complaint_errors'] = ['Failed to submit complaint. Please try again.'];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

redirectComplaint();
