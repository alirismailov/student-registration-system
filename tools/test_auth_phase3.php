<?php
/**
 * CLI integration tests for Phase 3 authentication.
 * Creates and deletes only temporary test accounts.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/config/database.php';

$base = getenv('BWP501_BASE_URL') ?: 'http://127.0.0.1:8080/ab_hasan';
$cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bwp501_auth_cookies.txt';
@unlink($cookie);

$failed = 0;
$passed = 0;

function ok(string $label, bool $cond, string $detail = ''): void
{
    global $failed, $passed;
    if ($cond) {
        $passed++;
        echo "[PASS] {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    } else {
        $failed++;
        echo "[FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    }
}

/**
 * @return array{code:int, body:string, headers:string}
 */
function http_request(string $method, string $url, ?string $cookieFile, array $post = [], bool $follow = false): array
{
    $ch = curl_init($url);
    $headers = [];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'BWP501-AuthTest/1.0',
    ]);

    if ($cookieFile !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['code' => 0, 'body' => '', 'headers' => $err];
    }

    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'code' => $code,
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize),
    ];
}

function extract_csrf(string $html): ?string
{
    if (preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return null;
}

function location_header(string $headers): ?string
{
    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
        return trim($m[1]);
    }
    return null;
}

echo "Phase 3 auth integration tests\n";
echo str_repeat('-', 56) . PHP_EOL;

$suffix = bin2hex(random_bytes(4));
$userA = 'tuser_' . $suffix;
$emailA = 'tuser_' . $suffix . '@example.test';
$userB = 'tdup_' . $suffix;
$emailB = 'tdup_' . $suffix . '@example.test';
$pass = 'TestPass!' . $suffix;

// --- Unauthenticated guard ---
$r = http_request('GET', $base . '/dashboard.php', null, [], false);
ok('Unauthenticated dashboard redirects', $r['code'] >= 300 && $r['code'] < 400, 'HTTP ' . $r['code']);
$loc = location_header($r['headers']) ?? '';
ok('Dashboard redirect targets login', str_contains($loc, 'login.php'), $loc);

// --- Registration page ---
$regPage = http_request('GET', $base . '/register.php', $cookie);
ok('Register page loads', $r['code'] !== 0 && $regPage['code'] === 200, 'HTTP ' . $regPage['code']);
$csrf = extract_csrf($regPage['body']);
ok('CSRF token present on register', $csrf !== null);

// Empty fields
$bad = http_request('POST', $base . '/register.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'username' => '',
    'email' => '',
    'password' => '',
    'password_confirm' => '',
], false);
ok('Empty registration rejected (stays on form)', $bad['code'] === 200 && str_contains($bad['body'], 'required'), 'HTTP ' . $bad['code']);

// Invalid email
$csrf = extract_csrf($bad['body']) ?? $csrf;
$badEmail = http_request('POST', $base . '/register.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'username' => $userA,
    'email' => 'not-an-email',
    'password' => $pass,
    'password_confirm' => $pass,
], false);
ok('Invalid email rejected', str_contains($badEmail['body'], 'valid email') || str_contains($badEmail['body'], 'Email'), 'body check');

// Password mismatch
$csrf = extract_csrf($badEmail['body']) ?? $csrf;
$mismatch = http_request('POST', $base . '/register.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'username' => $userA,
    'email' => $emailA,
    'password' => $pass,
    'password_confirm' => $pass . 'x',
], false);
ok('Password mismatch rejected', str_contains($mismatch['body'], 'match') || str_contains($mismatch['body'], 'Confirm'), 'body check');

// Valid registration
$csrf = extract_csrf($mismatch['body']) ?? $csrf;
$regOk = http_request('POST', $base . '/register.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'username' => $userA,
    'email' => $emailA,
    'password' => $pass,
    'password_confirm' => $pass,
], false);
$regLoc = location_header($regOk['headers']) ?? '';
ok('Valid registration redirects to login', $regOk['code'] >= 300 && $regOk['code'] < 400 && str_contains($regLoc, 'login.php'), 'HTTP ' . $regOk['code'] . ' ' . $regLoc);

// Password stored as hash
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT password FROM users WHERE username = :u LIMIT 1');
    $stmt->execute(['u' => $userA]);
    $hash = (string) $stmt->fetchColumn();
    ok('Password stored as hash', $hash !== '' && $hash !== $pass && str_starts_with($hash, '$'), 'prefix ok');
    ok('password_verify works for stored hash', password_verify($pass, $hash));
} catch (Throwable $e) {
    ok('Password hash DB check', false, 'db error');
}

// Duplicate username
@unlink($cookie);
$regPage2 = http_request('GET', $base . '/register.php', $cookie);
$csrf = extract_csrf($regPage2['body']);
$dupUser = http_request('POST', $base . '/register.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'username' => $userA,
    'email' => $emailB,
    'password' => $pass,
    'password_confirm' => $pass,
], false);
ok('Duplicate username rejected', str_contains($dupUser['body'], 'already') || str_contains($dupUser['body'], 'taken'), 'body check');

// Duplicate email
$csrf = extract_csrf($dupUser['body']) ?? $csrf;
$dupEmail = http_request('POST', $base . '/register.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'username' => $userB,
    'email' => $emailA,
    'password' => $pass,
    'password_confirm' => $pass,
], false);
ok('Duplicate email rejected', str_contains($dupEmail['body'], 'already') || str_contains($dupEmail['body'], 'registered'), 'body check');

// Login with username
@unlink($cookie);
$loginPage = http_request('GET', $base . '/login.php', $cookie);
$csrf = extract_csrf($loginPage['body']);
$loginBad = http_request('POST', $base . '/login.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'identifier' => $userA,
    'password' => 'wrong-password-!!!',
], false);
ok('Wrong password rejected', $loginBad['code'] === 200 && str_contains($loginBad['body'], 'Invalid'), 'HTTP ' . $loginBad['code']);

$csrf = extract_csrf($loginBad['body']) ?? $csrf;
$loginUnknown = http_request('POST', $base . '/login.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'identifier' => 'nouser_' . $suffix,
    'password' => $pass,
], false);
ok('Unknown account rejected', str_contains($loginUnknown['body'], 'Invalid'), 'generic message');

// SQL injection attempt
$csrf = extract_csrf($loginUnknown['body']) ?? $csrf;
$sqli = http_request('POST', $base . '/login.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'identifier' => "' OR '1'='1",
    'password' => "' OR '1'='1",
], false);
ok('SQL injection login blocked', str_contains($sqli['body'], 'Invalid') || ($sqli['code'] === 200 && !str_contains($sqli['headers'], 'dashboard.php')), 'no bypass');

$csrf = extract_csrf($sqli['body']) ?? $csrf;
$loginUser = http_request('POST', $base . '/login.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'identifier' => $userA,
    'password' => $pass,
], false);
$loginLoc = location_header($loginUser['headers']) ?? '';
ok('Login with username redirects to dashboard', $loginUser['code'] >= 300 && str_contains($loginLoc, 'dashboard.php'), $loginLoc);

$dash = http_request('GET', $base . '/dashboard.php', $cookie, [], false);
ok('Authenticated dashboard accessible', $dash['code'] === 200 && str_contains($dash['body'], $userA), 'HTTP ' . $dash['code']);

// Authenticated user redirected from login
$loginWhileAuth = http_request('GET', $base . '/login.php', $cookie, [], false);
$lwLoc = location_header($loginWhileAuth['headers']) ?? '';
ok('Authenticated user redirected from login', $loginWhileAuth['code'] >= 300 && str_contains($lwLoc, 'dashboard.php'), $lwLoc);

// Login with email (fresh cookie)
@unlink($cookie);
$loginPage = http_request('GET', $base . '/login.php', $cookie);
$csrf = extract_csrf($loginPage['body']);
$loginEmail = http_request('POST', $base . '/login.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'identifier' => $emailA,
    'password' => $pass,
], false);
ok('Login with email redirects to dashboard', str_contains(location_header($loginEmail['headers']) ?? '', 'dashboard.php'));

// GET logout must not destroy session
$getLogout = http_request('GET', $base . '/logout.php', $cookie, [], false);
ok('GET logout does not succeed as POST logout', $getLogout['code'] >= 300, 'HTTP ' . $getLogout['code']);
$stillIn = http_request('GET', $base . '/dashboard.php', $cookie, [], false);
ok('Session still valid after GET logout', $stillIn['code'] === 200 && str_contains($stillIn['body'], 'Dashboard'), 'HTTP ' . $stillIn['code']);

// POST logout without CSRF
$badLogout = http_request('POST', $base . '/logout.php', $cookie, [], false);
ok('Logout without CSRF rejected or redirected safely', $badLogout['code'] >= 300 || $badLogout['code'] === 403, 'HTTP ' . $badLogout['code']);

// Valid POST logout
$dash = http_request('GET', $base . '/dashboard.php', $cookie);
$csrf = extract_csrf($dash['body']);
// CSRF may be on logout form in nav
if ($csrf === null) {
    // try login page after following
    $csrf = extract_csrf($stillIn['body'] ?? '');
}
$dash2 = http_request('GET', $base . '/dashboard.php', $cookie);
$csrf = extract_csrf($dash2['body']);
ok('CSRF on dashboard logout form', $csrf !== null);
$logoutOk = http_request('POST', $base . '/logout.php', $cookie, [
    '_csrf' => $csrf ?? '',
], false);
ok('POST logout redirects', $logoutOk['code'] >= 300, 'HTTP ' . $logoutOk['code']);
$afterLogout = http_request('GET', $base . '/dashboard.php', $cookie, [], false);
ok('Dashboard blocked after logout', $afterLogout['code'] >= 300 && str_contains(location_header($afterLogout['headers']) ?? '', 'login.php'));

// Escape check — register user with HTML-ish username then ensure escaped on dashboard
@unlink($cookie);
$xssUser = 'x' . $suffix; // keep username charset-safe; inject in display via crafted check on homepage flash not needed
// Verify homepage escapes by checking register redisplay
$regPage = http_request('GET', $base . '/register.php', $cookie);
$csrf = extract_csrf($regPage['body']);
$xssEmail = 'xss_' . $suffix . '@example.test';
$xssAttempt = http_request('POST', $base . '/register.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'username' => 'bad<>user', // will fail charset validation
    'email' => $xssEmail,
    'password' => $pass,
    'password_confirm' => $pass,
], false);
ok('Dangerous username characters rejected or escaped', !str_contains($xssAttempt['body'], '<script>') && (str_contains($xssAttempt['body'], 'Username') || str_contains($xssAttempt['body'], 'letters')), 'validation');

// Cleanup test users only
try {
    $pdo = getPDO();
    $del = $pdo->prepare('DELETE FROM users WHERE username IN (:a, :b) OR email IN (:ea, :eb)');
    // PDO can't reuse named params easily with IN - do separate
    $pdo->prepare('DELETE FROM users WHERE username = :u')->execute(['u' => $userA]);
    $pdo->prepare('DELETE FROM users WHERE username = :u')->execute(['u' => $userB]);
    $pdo->prepare('DELETE FROM users WHERE email = :e')->execute(['e' => $emailA]);
    $pdo->prepare('DELETE FROM users WHERE email = :e')->execute(['e' => $emailB]);
    ok('Temporary test accounts removed', true);
} catch (Throwable $e) {
    ok('Temporary test accounts removed', false, 'cleanup error');
}

@unlink($cookie);

echo str_repeat('-', 56) . PHP_EOL;
echo "Passed: {$passed}  Failed: {$failed}" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
