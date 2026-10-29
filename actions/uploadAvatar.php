<?php
ini_set('display_errors', '0');
require_once "../auth/session.php";
require_once "../config/db.php"; // make sure this path is correct
header('Content-Type: application/json');

// Check DB connection
if(!$conn){
    echo json_encode(['success'=>false,'error'=>'DB connection failed']);
    exit;
}

// Check login
if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) ($_POST['csrf_token'] ?? ''))) {
    echo json_encode(['success' => false, 'error' => 'Security token mismatch. Refresh the page and try again.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Check file
if(!isset($_FILES['avatar'])){
    echo json_encode(['success'=>false,'error'=>'No file uploaded']);
    exit;
}

$file = $_FILES['avatar'];

// Check upload error
if($file['error'] !== UPLOAD_ERR_OK){
    echo json_encode(['success'=>false,'error'=>'Upload error']);
    exit;
}

// Optional: validate file type
$allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if(!isset($allowedTypes[$mime]) || @getimagesize($file['tmp_name']) === false){
    echo json_encode(['success'=>false,'error'=>'Invalid file type']);
    exit;
}

// Optional: validate file size (max 25MB)
if($file['size'] > 25*1024*1024){
    echo json_encode(['success'=>false,'error'=>'File too large']);
    exit;
}

// Upload directory
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Save file
$ext = $allowedTypes[$mime];
$filename = "avatar_{$userId}_" . time() . "." . $ext;
$filepath = $uploadDir . $filename;

if(!move_uploaded_file($file['tmp_name'], $filepath)){
    echo json_encode(['success'=>false,'error'=>'Failed to save file']);
    exit;
}

$relativePath = 'uploads/avatars/' . $filename;
$stmt = $conn->prepare("UPDATE users SET avatar_path=? WHERE userId=?");
if(!$stmt){
    echo json_encode(['success'=>false,'error'=>'DB prepare failed: '.$conn->error]);
    exit;
}

$stmt->bind_param("si", $relativePath, $userId);
if(!$stmt->execute()){
    echo json_encode(['success'=>false,'error'=>'DB execute failed: '.$stmt->error]);
    exit;
}

$stmt->close();

// Return a path relative to the dashboard page. The client adds cache-busting.
$downloadBase = '../actions/download.php?path=' . rawurlencode($relativePath);
echo json_encode(['success'=>true,'avatarPath'=>$downloadBase]);