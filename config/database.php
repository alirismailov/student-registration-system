<?php
/**
 * Secure PDO database connection (Phase 2).
 *
 * Configuration precedence:
 * 1. config/database.local.php  (private local overrides — preferred for secrets)
 * 2. Built-in XAMPP development defaults (localhost / root / empty password)
 *
 * Copy database.local.php.example → database.local.php for local or hosting credentials.
 * Never put passwords in JavaScript, HTML, or public pages.
 */

declare(strict_types=1);

/**
 * Load database settings (no connection is opened here).
 *
 * @return array{
 *   host: string,
 *   port: int,
 *   dbname: string,
 *   username: string,
 *   password: string,
 *   charset: string
 * }
 */
function getDatabaseConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    // Safe local development defaults (XAMPP). Not for production.
    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'student_registration_system',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ];

    $localFile = __DIR__ . '/database.local.php';
    if (is_readable($localFile)) {
        $local = require $localFile;
        if (is_array($local)) {
            $config = array_merge($config, $local);
        }
    }

    $config['port'] = (int) $config['port'];

    return $config;
}

/**
 * Return a shared PDO connection (lazy; one instance per request).
 *
 * Does not connect until this function is called.
 * Failures are logged server-side; visitors never see credentials or stack traces.
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = getDatabaseConfig();

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['dbname'],
        $cfg['charset']
    );

    try {
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new RuntimeException(
            'Unable to connect to the database. Please try again later.',
            0,
            $e
        );
    }

    return $pdo;
}
