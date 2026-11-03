<?php
/**
 * Authentication helpers (Phase 3).
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once dirname(__DIR__) . '/config/database.php';

/**
 * Authenticated user ID from the session, or null.
 */
function currentUserId(): ?int
{
    init_session();

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $id = $_SESSION['user_id'];

    if (is_int($id)) {
        return $id > 0 ? $id : null;
    }

    if (is_string($id) && ctype_digit($id)) {
        $id = (int) $id;
        return $id > 0 ? $id : null;
    }

    return null;
}

/**
 * Username stored in the session (display only).
 */
function currentUsername(): ?string
{
    init_session();

    if (!isset($_SESSION['username']) || !is_string($_SESSION['username'])) {
        return null;
    }

    $name = trim($_SESSION['username']);

    return $name !== '' ? $name : null;
}

/**
 * Whether the current request has a valid authenticated session.
 */
function isAuthenticated(): bool
{
    return currentUserId() !== null;
}

/**
 * Establish an authenticated session after successful login.
 */
function login_user(int $userId, string $username): void
{
    init_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
}

/**
 * Clear authentication and destroy the session.
 */
function logout_user(): void
{
    init_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

/**
 * Require a logged-in user whose account still exists.
 */
function requireAuth(): void
{
    init_session();

    $userId = currentUserId();
    if ($userId === null) {
        flash_set('warning', 'Please log in to continue.');
        redirect_to('login.php');
    }

    try {
        $stmt = getPDO()->prepare('SELECT id, username FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('requireAuth lookup failed: ' . $e->getMessage());
        flash_set('danger', 'Unable to verify your session. Please log in again.');
        logout_user();
        redirect_to('login.php');
    }

    if (!$user) {
        logout_user();
        init_session();
        flash_set('warning', 'Your account is no longer available. Please log in again.');
        redirect_to('login.php');
    }

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = (string) $user['username'];
}

/**
 * Send authenticated users away from guest-only pages (login/register).
 */
function redirectIfAuthenticated(string $path = 'dashboard.php'): void
{
    if (isAuthenticated()) {
        redirect_to($path);
    }
}

