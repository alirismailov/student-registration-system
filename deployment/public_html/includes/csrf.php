<?php
/**
 * Lightweight CSRF protection for POST forms.
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';

/**
 * Return the current CSRF token (create if missing).
 */
function csrf_token(): string
{
    init_session();

    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

/**
 * HTML hidden input for forms.
 */
function csrf_field(): string
{
    require_once dirname(__DIR__) . '/config/app.php';

    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Validate a submitted CSRF token.
 */
function csrf_verify(?string $token): bool
{
    init_session();

    if (!is_string($token) || $token === '' || empty($_SESSION['_csrf'])) {
        return false;
    }

    return hash_equals((string) $_SESSION['_csrf'], $token);
}

/**
 * Reject the request when the CSRF token is missing or invalid.
 */
function require_valid_csrf(): void
{
    $token = isset($_POST['_csrf']) ? (string) $_POST['_csrf'] : null;

    if (!csrf_verify($token)) {
        flash_set('danger', 'Your session token is invalid or expired. Please try again.');
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (is_string($referer) && $referer !== '') {
            header('Location: ' . $referer);
            exit;
        }
        redirect_to('index.php');
    }
}
