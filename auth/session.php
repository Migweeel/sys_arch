<?php

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/security.php';
    ini_set('session.use_strict_mode', '1');
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (isset($_SESSION['role']) && !in_array(strtolower((string) $_SESSION['role']), ['admin', 'staff', 'resident'], true)) {
    $_SESSION = [];
    session_destroy();
    http_response_code(403);
    exit('Invalid session role.');
}

if (function_exists('securityRateLimitRequest')) {
    securityRateLimitRequest();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
if (appEnvironmentValue('APP_ENV') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $canonical = parse_url(appBaseUrl());
        $target = ($canonical['scheme'] ?? 'https') . '://' . ($canonical['host'] ?? '');
        if (isset($canonical['port'])) {
            $target .= ':' . $canonical['port'];
        }
        $target .= $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . $target, true, 301);
        exit();
    }
}

if (empty($_SESSION['csrf_token'])) {
    refreshToken();
}

function refreshToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function hasCookieCategoryConsent($category) {
    $allowedCategories = ['analytics', 'advertising', 'functional'];
    if (!in_array($category, $allowedCategories, true)) {
        return false;
    }

    $record = json_decode($_COOKIE['knl-cookie-preferences'] ?? '', true);
    if (
        !is_array($record) ||
        ($record['version'] ?? '') !== '2026-10-06' ||
        !is_array($record['choices'] ?? null) ||
        !is_string($record['timestamp'] ?? null) ||
        strtotime($record['timestamp']) === false
    ) {
        return false;
    }

    foreach ($allowedCategories as $knownCategory) {
        if (!is_bool($record['choices'][$knownCategory] ?? null)) {
            return false;
        }
    }

    return $record['choices'][$category];
}

function displayFlashMessages() {
    if (isset($_SESSION['errors']) || isset($_SESSION['success'])) {
        $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
        $useInlineMessages = in_array($currentPage, ['loginPage.php', 'registerPage.php'], true);

        if ($useInlineMessages) {
            echo '<div class="flash-messages-container" aria-live="polite">';
            if (isset($_SESSION['errors'])) {
                foreach ($_SESSION['errors'] as $error) {
                    echo '<p class="error-msg" role="alert">&bull; ' . htmlspecialchars($error) . '</p>';
                }
                unset($_SESSION['errors']);
            }
            if (isset($_SESSION['success'])) {
                $successMessages = is_array($_SESSION['success']) ? $_SESSION['success'] : [$_SESSION['success']];
                foreach ($successMessages as $success) {
                    echo '<p class="success-msg" role="status">' . htmlspecialchars($success) . '</p>';
                }
                unset($_SESSION['success']);
            }
            echo '</div>';
            return;
        }

        echo '<style>
            .app-toast-stack {
                position: fixed;
                top: 18px;
                right: 18px;
                z-index: 9999;
                display: grid;
                gap: 10px;
                width: min(380px, calc(100vw - 32px));
                pointer-events: none;
            }
            .app-toast {
                pointer-events: auto;
                display: grid;
                grid-template-columns: 22px 1fr auto;
                gap: 10px;
                align-items: start;
                border: 1px solid #e5e7eb;
                border-left-width: 5px;
                border-radius: 10px;
                background: #ffffff;
                color: #111827;
                box-shadow: 0 16px 36px rgba(15, 23, 42, 0.16);
                padding: 13px 14px;
                font-family: inherit;
                animation: appToastIn 0.24s ease-out;
            }
            .app-toast strong {
                display: block;
                margin: 0 0 2px;
                font-size: 13px;
                line-height: 1.25;
            }
            .app-toast p {
                margin: 0;
                color: #4b5563;
                font-size: 13px;
                line-height: 1.45;
            }
            .app-toast-icon {
                display: inline-grid;
                place-items: center;
                width: 22px;
                height: 22px;
                border-radius: 999px;
                font-size: 13px;
                font-weight: 800;
                line-height: 1;
            }
            .app-toast-close {
                border: 0;
                background: transparent;
                color: #6b7280;
                cursor: pointer;
                font: inherit;
                font-size: 18px;
                line-height: 1;
                padding: 0 2px;
            }
            .app-toast.success {
                border-left-color: #16a34a;
            }
            .app-toast.success .app-toast-icon {
                background: #dcfce7;
                color: #166534;
            }
            .app-toast.error {
                border-left-color: #dc2626;
            }
            .app-toast.error .app-toast-icon {
                background: #fee2e2;
                color: #991b1b;
            }
            @keyframes appToastIn {
                from { opacity: 0; transform: translateY(-8px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @media (max-width: 640px) {
                .app-toast-stack {
                    top: 12px;
                    right: 12px;
                    left: 12px;
                    width: auto;
                }
            }
        </style>';
        echo '<div class="app-toast-stack" aria-live="polite" aria-atomic="true">';
        if (isset($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $error) {
                echo '<div class="app-toast error" role="alert">';
                echo '<span class="app-toast-icon">!</span>';
                echo '<div><strong>Action needed</strong><p>' . htmlspecialchars($error) . '</p></div>';
                echo '<button type="button" class="app-toast-close" aria-label="Dismiss notification">&times;</button>';
                echo '</div>';
            }
            unset($_SESSION['errors']);
        }
        if (isset($_SESSION['success'])) {
            $successMessages = is_array($_SESSION['success']) ? $_SESSION['success'] : [$_SESSION['success']];
            foreach ($successMessages as $success) {
                echo '<div class="app-toast success" role="status">';
                echo '<span class="app-toast-icon">&#10003;</span>';
                echo '<div><strong>Success</strong><p>' . htmlspecialchars($success) . '</p></div>';
                echo '<button type="button" class="app-toast-close" aria-label="Dismiss notification">&times;</button>';
                echo '</div>';
            }
            unset($_SESSION['success']);
        }
        echo '</div>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll(".app-toast").forEach(function (toast) {
                    const closeButton = toast.querySelector(".app-toast-close");
                    const removeToast = function () {
                        toast.style.opacity = "0";
                        toast.style.transform = "translateY(-8px)";
                        setTimeout(function () { toast.remove(); }, 180);
                    };
                    if (closeButton) {
                        closeButton.addEventListener("click", removeToast);
                    }
                    setTimeout(removeToast, 4500);
                });
            });
        </script>';
    }
}

function ifLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        $role = strtolower($_SESSION['role']);
        header("Location: ../dashboard/{$role}.php");
        exit();
    }
}

function old($field) {
    return $_SESSION['old'][$field] ?? '';
}

function clearOldInput() {
    unset($_SESSION['old']);
}
