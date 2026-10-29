<?php

function securityClientKey($username = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', strtolower(trim((string) $username)) . '|' . $ip);
}

function securityRequestKey() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userId = $_SESSION['user_id'] ?? 'guest';
    return hash('sha256', $ip . '|' . $userId);
}

function securityRequireRole(array $allowedRoles, $redirect = '../pages/loginPage.php') {
    $role = strtolower((string) ($_SESSION['role'] ?? ''));
    if (!isset($_SESSION['user_id']) || !in_array($role, $allowedRoles, true)) {
        header('Location: ' . $redirect);
        exit();
    }
}

function securityRateLimitRequest() {
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (strpos($script, '/actions/') === false && strpos($script, '/api/') === false) {
        return;
    }

    if (!securityRateLimit('request', securityRequestKey(), 120, 60)) {
        http_response_code(429);
        header('Retry-After: 60');
        if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
        } else {
            echo 'Too many requests. Please try again later.';
        }
        exit();
    }
}

function securityRateLimit($bucket, $key, $limit, $windowSeconds) {
    $directory = dirname(__DIR__) . '/storage/rate-limit';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        return false;
    }

    $path = $directory . '/' . hash('sha256', $bucket . '|' . $key) . '.json';
    $handle = fopen($path, 'c+');
    if (!$handle) {
        return false;
    }

    flock($handle, LOCK_EX);
    $contents = stream_get_contents($handle);
    $record = json_decode($contents ?: '{}', true);
    $now = time();
    $attempts = array_values(array_filter($record['attempts'] ?? [], function ($timestamp) use ($now, $windowSeconds) {
        return is_int($timestamp) && $timestamp > $now - $windowSeconds;
    }));
    $allowed = count($attempts) < $limit;
    if ($allowed) {
        $attempts[] = $now;
    }

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode(['attempts' => $attempts]));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $allowed;
}

function securityRateLimitReset($bucket, $key) {
    $path = dirname(__DIR__) . '/storage/rate-limit/' . hash('sha256', $bucket . '|' . $key) . '.json';
    if (is_file($path)) {
        unlink($path);
    }
}
