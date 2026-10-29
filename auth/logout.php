<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookieParams = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $cookieParams['path'],
        $cookieParams['domain'],
        (bool) $cookieParams['secure'],
        (bool) $cookieParams['httponly']
    );
}

session_destroy();

header('Location: /updated/pages/loginPage.php', true, 302);
exit;
