<?php
/**
 * Application configuration helpers.
 * Sessions are started via includes/session.php (not here).
 */

declare(strict_types=1);

define('APP_NAME', 'Student Registration System');
define('APP_ROOT', dirname(__DIR__));

// Optional host-specific overrides (no secrets). Example: define('APP_ENV', 'production');
$appLocal = __DIR__ . '/app.local.php';
if (is_readable($appLocal)) {
    require $appLocal;
}

// Production hosts should set APP_ENV=production (env or local bootstrap) to hide PHP errors.
if (!defined('APP_ENV')) {
    $env = getenv('APP_ENV');
    define('APP_ENV', is_string($env) && $env !== '' ? $env : 'local');
}
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
}

/**
 * Resolve a URL path relative to the application root.
 * Works when the project is hosted in an Apache subdirectory
 * (including when the folder is a junction/symlink outside DOCUMENT_ROOT).
 */
function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $base = '';

        // Prefer the request script directory (reliable under /ab_hasan/).
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName !== '' && PHP_SAPI !== 'cli') {
            $dir = dirname($scriptName);
            if ($dir !== '/' && $dir !== '\\' && $dir !== '.') {
                $base = rtrim($dir, '/');
            }
        } else {
            // Fallback for CLI or unusual SAPIs.
            $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
            $appRoot = realpath(APP_ROOT) ?: APP_ROOT;
            $documentRoot = str_replace('\\', '/', $documentRoot);
            $appRoot = str_replace('\\', '/', $appRoot);

            if ($documentRoot !== '' && str_starts_with($appRoot, $documentRoot)) {
                $base = rtrim(substr($appRoot, strlen($documentRoot)), '/');
            }
        }
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

/**
 * Escape output for safe HTML rendering.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
