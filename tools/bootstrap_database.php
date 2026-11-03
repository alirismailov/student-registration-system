<?php
/**
 * CLI helper: import schema + write private local PDO config (Phase 2.1).
 *
 * SAFETY
 * - CLI only (refuses HTTP).
 * - Does not print passwords.
 * - Does not drop existing tables or delete rows.
 * - Does not reset MySQL root.
 *
 * Usage (PowerShell) — set the root password for THIS shell session only:
 *
 *   $env:BWP501_MYSQL_ROOT_PASSWORD = 'your-mysql-root-password'
 *   C:\xampp\php\php.exe tools\bootstrap_database.php
 *
 * Optional overrides:
 *   BWP501_MYSQL_HOST (default 127.0.0.1)
 *   BWP501_MYSQL_PORT (default 3306)
 *   BWP501_MYSQL_ROOT_USER (default root)
 *
 * Creates/uses app user `bwp501_app` with privileges only on
 * `student_registration_system`, and writes config/database.local.php.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Forbidden: CLI only.\n";
    exit(1);
}

$projectRoot = dirname(__DIR__);
$sqlFile = $projectRoot . '/database/student_registration_system.sql';
$localConfigPath = $projectRoot . '/config/database.local.php';

$host = getenv('BWP501_MYSQL_HOST') ?: '127.0.0.1';
$port = (int) (getenv('BWP501_MYSQL_PORT') ?: '3306');
$rootUser = getenv('BWP501_MYSQL_ROOT_USER') ?: 'root';
$rootPassword = getenv('BWP501_MYSQL_ROOT_PASSWORD');

if ($rootPassword === false || $rootPassword === '') {
    fwrite(STDERR, "Missing BWP501_MYSQL_ROOT_PASSWORD in the environment.\n");
    fwrite(STDERR, "Set it for this PowerShell session, then re-run this script.\n");
    fwrite(STDERR, "Example:\n");
    fwrite(STDERR, "  \$env:BWP501_MYSQL_ROOT_PASSWORD = 'your-password'\n");
    fwrite(STDERR, "  C:\\xampp\\php\\php.exe tools\\bootstrap_database.php\n");
    exit(1);
}

if (!is_readable($sqlFile)) {
    fwrite(STDERR, "SQL file not readable: database/student_registration_system.sql\n");
    exit(1);
}

$dbname = 'student_registration_system';
$appUser = 'bwp501_app';
$appPassword = bin2hex(random_bytes(16));

function out(string $msg): void
{
    echo $msg . PHP_EOL;
}

try {
    $serverPdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
        $rootUser,
        $rootPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $version = (string) $serverPdo->query('SELECT VERSION()')->fetchColumn();
    out('[PASS] Connected to MySQL server (version ' . $version . ')');

    $serverPdo->exec(
        "CREATE DATABASE IF NOT EXISTS `{$dbname}`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    out('[PASS] Database ensured: ' . $dbname);

    $serverPdo->exec("USE `{$dbname}`");

    foreach (['users', 'students'] as $table) {
        $stmt = $serverPdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = :db AND table_name = :table_name'
        );
        $stmt->execute(['db' => $dbname, 'table_name' => $table]);
        $exists = (int) $stmt->fetchColumn() > 0;
        if ($exists) {
            $countStmt = $serverPdo->query('SELECT COUNT(*) FROM `' . $table . '`');
            $rows = (int) $countStmt->fetchColumn();
            out("[INFO] Table `{$table}` already exists ({$rows} row(s)) — will not drop");
        }
    }

    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new RuntimeException('Unable to read SQL file.');
    }

    // Remove CREATE DATABASE / USE so we stay on the selected DB.
    $sql = preg_replace('/^CREATE DATABASE[\s\S]*?;\s*/mi', '', $sql) ?? $sql;
    $sql = preg_replace('/^USE\s+`?student_registration_system`?\s*;\s*/mi', '', $sql) ?? $sql;

    $serverPdo->exec($sql);
    out('[PASS] Schema statements executed (CREATE TABLE IF NOT EXISTS)');

    // Create dedicated app user (MySQL 8+/9 syntax). Ignore if already exists then grant.
    $createUserSql = sprintf(
        "CREATE USER IF NOT EXISTS '%s'@'localhost' IDENTIFIED BY '%s'",
        $appUser,
        str_replace(["\\", "'"], ["\\\\", "\\'"], $appPassword)
    );
    $serverPdo->exec($createUserSql);

    // Also allow 127.0.0.1 host variant used by PDO.
    $createUserSqlIp = sprintf(
        "CREATE USER IF NOT EXISTS '%s'@'127.0.0.1' IDENTIFIED BY '%s'",
        $appUser,
        str_replace(["\\", "'"], ["\\\\", "\\'"], $appPassword)
    );
    $serverPdo->exec($createUserSqlIp);

    // App privileges only — no global admin rights.
    $serverPdo->exec(
        "GRANT SELECT, INSERT, UPDATE, DELETE
         ON `{$dbname}`.* TO '{$appUser}'@'localhost'"
    );
    $serverPdo->exec(
        "GRANT SELECT, INSERT, UPDATE, DELETE
         ON `{$dbname}`.* TO '{$appUser}'@'127.0.0.1'"
    );

    // Ensure password matches this run even if the user already existed.
    $escapedPass = str_replace(["\\", "'"], ["\\\\", "\\'"], $appPassword);
    $serverPdo->exec("ALTER USER '{$appUser}'@'localhost' IDENTIFIED BY '{$escapedPass}'");
    $serverPdo->exec("ALTER USER '{$appUser}'@'127.0.0.1' IDENTIFIED BY '{$escapedPass}'");
    $serverPdo->exec('FLUSH PRIVILEGES');
    out('[PASS] Dedicated app user granted (database-scoped privileges only)');

    $localPhp = <<<'PHP'
<?php
/**
 * Private local database credentials — DO NOT COMMIT.
 * Generated by tools/bootstrap_database.php
 */

declare(strict_types=1);

return [
    'host' => %s,
    'port' => %d,
    'dbname' => %s,
    'username' => %s,
    'password' => %s,
    'charset' => 'utf8mb4',
];

PHP;

    $contents = sprintf(
        $localPhp,
        var_export($host, true),
        $port,
        var_export($dbname, true),
        var_export($appUser, true),
        var_export($appPassword, true)
    );

    if (file_put_contents($localConfigPath, $contents) === false) {
        throw new RuntimeException('Failed to write config/database.local.php');
    }
    out('[PASS] Wrote config/database.local.php (gitignored)');

    // Verify with app credentials
    $appPdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbname),
        $appUser,
        $appPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    foreach (['users', 'students'] as $table) {
        $appPdo->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
        out('[PASS] App user can read table `' . $table . '`');
    }

    out('--------------------------------------------------------');
    out('Bootstrap complete.');
    out('Next: C:\\xampp\\php\\php.exe tools\\check_database.php');
    out('Clear the root password from your shell when finished:');
    out('  Remove-Item Env:BWP501_MYSQL_ROOT_PASSWORD');
    exit(0);
} catch (Throwable $e) {
    error_log('bootstrap_database.php: ' . $e->getMessage());
    fwrite(STDERR, "[FAIL] " . $e->getMessage() . "\n");
    fwrite(STDERR, "No password was printed. Check that the root password is correct.\n");
    exit(1);
}

