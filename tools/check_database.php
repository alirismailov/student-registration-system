<?php
/**
 * CLI-only database health check (Phase 2).
 *
 * Usage (from project root):
 *   C:\xampp\php\php.exe tools\check_database.php
 *
 * Exit code: 0 = all checks passed, 1 = failure.
 * Does not print passwords or DSN credentials.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Forbidden: this script may only be run from the command line.\n";
    exit(1);
}

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config/database.php';

$failed = 0;

/**
 * @param bool $ok
 */
function report(string $label, bool $ok, string $detail = ''): void
{
    global $failed;
    $status = $ok ? 'PASS' : 'FAIL';
    if (!$ok) {
        $failed++;
    }
    $line = sprintf('[%s] %s', $status, $label);
    if ($detail !== '') {
        $line .= ' — ' . $detail;
    }
    echo $line . PHP_EOL;
}

echo 'Student Registration System — database health check' . PHP_EOL;
echo str_repeat('-', 56) . PHP_EOL;

$cfg = getDatabaseConfig();
report(
    'Configuration loaded',
    isset($cfg['host'], $cfg['dbname'], $cfg['username'], $cfg['charset']),
    'host=' . $cfg['host'] . '; db=' . $cfg['dbname'] . '; user=' . $cfg['username']
);

if (!extension_loaded('pdo')) {
    report('PDO extension', false, 'pdo extension is not loaded');
    echo PHP_EOL . 'Result: FAIL' . PHP_EOL;
    exit(1);
}
report('PDO extension', true);

if (!extension_loaded('pdo_mysql')) {
    report('pdo_mysql extension', false, 'enable extension=pdo_mysql in php.ini');
    echo PHP_EOL . 'Result: FAIL' . PHP_EOL;
    exit(1);
}
report('pdo_mysql extension', true);

$pdo = null;
try {
    $pdo = getPDO();
    report('MySQL connection', true, 'reachable and database selected');
} catch (Throwable $e) {
    report('MySQL connection', false, 'could not connect (see PHP error log for details)');
    echo PHP_EOL . 'Result: FAIL' . PHP_EOL;
    exit(1);
}

$requiredTables = [
    'users' => [
        'id',
        'username',
        'email',
        'password',
        'created_at',
    ],
    'students' => [
        'id',
        'name',
        'email',
        'phone',
        'address',
        'profile_picture',
        'created_at',
    ],
];

foreach ($requiredTables as $table => $columns) {
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS c
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table_name'
        );
        $stmt->execute(['table_name' => $table]);
        $exists = (int) $stmt->fetchColumn() > 0;
        report("Table `{$table}` exists", $exists);

        if (!$exists) {
            continue;
        }

        $colStmt = $pdo->prepare(
            'SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table_name'
        );
        $colStmt->execute(['table_name' => $table]);
        $found = array_map('strtolower', $colStmt->fetchAll(PDO::FETCH_COLUMN));

        foreach ($columns as $column) {
            $ok = in_array(strtolower($column), $found, true);
            report("Column `{$table}.{$column}`", $ok);
        }
    } catch (Throwable $e) {
        report("Schema check for `{$table}`", false, 'query failed (details in error log)');
        error_log('check_database.php schema error: ' . $e->getMessage());
    }
}

echo str_repeat('-', 56) . PHP_EOL;
if ($failed > 0) {
    echo "Result: FAIL ({$failed} check(s) failed)" . PHP_EOL;
    exit(1);
}

echo 'Result: PASS' . PHP_EOL;
exit(0);
