<?php
require_once '../auth/session.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Authentication required.');
}

$requestedPath = trim(str_replace('\\', '/', (string) ($_GET['path'] ?? '')));
$requestedPath = ltrim($requestedPath, '/');
if ($requestedPath === '' || strpos($requestedPath, '..') !== false || strpos($requestedPath, 'uploads/') !== 0) {
    http_response_code(404);
    exit('File not found.');
}

$userId = (int) $_SESSION['user_id'];
$role = strtolower((string) ($_SESSION['role'] ?? ''));
$authorized = in_array($role, ['admin', 'staff'], true);

if (!$authorized && $role === 'resident' && $conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT 1
         FROM users u
         LEFT JOIN document_requests r ON r.user_id = u.userId
         LEFT JOIN document_request_files f ON f.request_id = r.request_id
         WHERE u.userId = ?
           AND (
               u.avatar_path = ?
               OR u.verification_proof = ?
               OR f.file_path = ?
               OR r.generated_file_path = ?
           )
         LIMIT 1"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'issss', $userId, $requestedPath, $requestedPath, $requestedPath, $requestedPath);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $authorized = (bool) ($result && mysqli_fetch_assoc($result));
        mysqli_stmt_close($stmt);
    }
}

if (!$authorized) {
    http_response_code(403);
    exit('Access denied.');
}

$root = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
$file = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $requestedPath));
if (!$root || !$file || !is_file($file) || strncmp($file, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) !== 0) {
    http_response_code(404);
    exit('File not found.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
$downloadName = basename($file);
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($file));
header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"');
header('X-Content-Type-Options: nosniff');
readfile($file);
