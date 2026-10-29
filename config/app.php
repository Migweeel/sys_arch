<?php

function appEnvironmentValue($key, $default = '') {
    $value = getenv($key);
    return $value === false ? $default : trim((string) $value);
}

function appBaseUrl() {
    $configuredUrl = rtrim(appEnvironmentValue('APP_URL'), '/');
    if ($configuredUrl !== '') {
        if (appEnvironmentValue('APP_ENV') === 'production' && stripos($configuredUrl, 'https://') !== 0) {
            throw new RuntimeException('APP_URL must use HTTPS in production.');
        }
        return $configuredUrl;
    }

    if (appEnvironmentValue('APP_ENV') === 'production') {
        throw new RuntimeException('APP_URL must be configured in production.');
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $projectPath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');

    return $scheme . '://' . $host . $projectPath;
}

function appMailFromAddress() {
    return appEnvironmentValue('MAIL_FROM_ADDRESS', 'noreply@localhost');
}

function appMailFromName() {
    return appEnvironmentValue('MAIL_FROM_NAME', 'Barangay Krus na Ligas');
}

function appSmtpSetting($key, $default = '') {
    return appEnvironmentValue('SMTP_' . $key, $default);
}
