<?php
/**
 * Secure PHP session bootstrap.
 */

declare(strict_types=1);

/**
 * Start the session once with secure cookie parameters.
 */
function init_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('SRSSESSID');
    session_start();
}

/**
 * Store a one-time flash message for the next request.
 */
function flash_set(string $type, string $message): void
{
    init_session();
    $_SESSION['_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Retrieve and clear the flash message.
 *
 * @return array{type: string, message: string}|null
 */
function flash_get(): ?array
{
    init_session();
    if (empty($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
        return null;
    }

    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

    $type = (string) ($flash['type'] ?? 'info');
    $message = (string) ($flash['message'] ?? '');

    if ($message === '') {
        return null;
    }

    return ['type' => $type, 'message' => $message];
}

/**
 * Redirect and stop execution.
 */
function redirect_to(string $path): never
{
    require_once dirname(__DIR__) . '/config/app.php';
    header('Location: ' . base_url($path));
    exit;
}
