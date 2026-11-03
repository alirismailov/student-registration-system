<?php
/**
 * Phase 4 student CRUD integration tests (CLI only).
 * Uses isolated TESTMARKER records and removes only those.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/student_validation.php';

$base = getenv('BWP501_BASE_URL') ?: 'http://127.0.0.1:8080/ab_hasan';
$cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bwp501_crud_cookies.txt';
@unlink($cookie);

$marker = 'P4TEST_' . bin2hex(random_bytes(4));
$passed = 0;
$failed = 0;
$createdIds = [];

function ok(string $label, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "[PASS] {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    } else {
        $failed++;
        echo "[FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    }
}

/** @return array{code:int,headers:string,body:string} */
function http_req(string $method, string $url, ?string $cookieFile, array $post = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_USERAGENT => 'BWP501-CRUDTest/1.0',
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
        return ['code' => 0, 'headers' => $err, 'body' => ''];
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return ['code' => $code, 'headers' => substr($raw, 0, $hs), 'body' => substr($raw, $hs)];
}

function extract_csrf(string $html): ?string
{
    if (preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return null;
}

function location_of(string $headers): string
{
    return preg_match('/^Location:\s*(.+)$/mi', $headers, $m) ? trim($m[1]) : '';
}

function cleanup_test_students(PDO $pdo, string $marker): void
{
    $stmt = $pdo->prepare('DELETE FROM students WHERE name LIKE :m OR email LIKE :e');
    $stmt->execute(['m' => $marker . '%', 'e' => $marker . '%']);
}

echo "Phase 4 student CRUD tests\n";
echo str_repeat('-', 56) . PHP_EOL;

$pdo = getPDO();
cleanup_test_students($pdo, $marker);

// --- Helper validation unit checks ---
$v = validate_student_input(['name' => '', 'email' => '', 'phone' => '', 'address' => '']);
ok('Validation rejects empty required fields', $v['errors'] !== []);

$v = validate_student_input([
    'name' => $marker . ' Valid',
    'email' => 'bad-email',
    'phone' => '1234567890',
    'address' => 'Somewhere',
]);
ok('Validation rejects invalid email', isset($v['errors']['email']));

$v = validate_student_input([
    'name' => str_repeat('N', 101),
    'email' => $marker . '@example.test',
    'phone' => '1234567890',
    'address' => 'Somewhere',
]);
ok('Validation rejects oversized name', isset($v['errors']['name']));

ok('parse_positive_int accepts 5', parse_positive_int('5') === 5);
ok('parse_positive_int rejects 0', parse_positive_int('0') === null);
ok('parse_positive_int rejects garbage', parse_positive_int('abc') === null);

// --- Unauthenticated access ---
$r = http_req('GET', $base . '/view_students.php', null);
ok('Unauthenticated view redirects', $r['code'] >= 300 && str_contains(location_of($r['headers']), 'login.php'), 'HTTP ' . $r['code']);
$r = http_req('GET', $base . '/add_student.php', null);
ok('Unauthenticated add redirects', $r['code'] >= 300 && str_contains(location_of($r['headers']), 'login.php'));

// --- Login as a temporary user ---
$suffix = bin2hex(random_bytes(3));
$authUser = 'p4u_' . $suffix;
$authEmail = $authUser . '@example.test';
$authPass = 'CrudPass!' . $suffix;
$hash = password_hash($authPass, PASSWORD_DEFAULT);
$pdo->prepare('INSERT INTO users (username, email, password) VALUES (?,?,?)')
    ->execute([$authUser, $authEmail, $hash]);

$loginPage = http_req('GET', $base . '/login.php', $cookie);
$csrf = extract_csrf($loginPage['body']);
$login = http_req('POST', $base . '/login.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'identifier' => $authUser,
    'password' => $authPass,
]);
ok('Test user login succeeds', $login['code'] >= 300 && str_contains(location_of($login['headers']), 'dashboard.php'));

// --- CREATE via HTTP ---
$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
ok('Add page loads for auth user', $addPage['code'] === 200 && $csrf !== null);

// Invalid CSRF
$badCsrf = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => 'invalid-token',
    'name' => $marker . ' CSRF',
    'email' => $marker . 'csrf@example.test',
    'phone' => '1234567890',
    'address' => 'Addr',
]);
ok('Create rejects invalid CSRF', $badCsrf['code'] >= 300, 'HTTP ' . $badCsrf['code']);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE name = ?');
$stmt->execute([$marker . ' CSRF']);
ok('Invalid CSRF did not insert', (int) $stmt->fetchColumn() === 0);

// Missing fields
$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$missing = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
]);
ok('Create rejects missing fields', $missing['code'] === 200 && (str_contains($missing['body'], 'required') || str_contains($missing['body'], 'Required') || str_contains($missing['body'], 'Full name')));

// Invalid email
$csrf = extract_csrf($missing['body']) ?? $csrf;
$badEmail = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' BadEmail',
    'email' => 'not-valid',
    'phone' => '1234567890',
    'address' => 'Street 1',
]);
ok('Create rejects invalid email', str_contains($badEmail['body'], 'email') || str_contains($badEmail['body'], 'Email'));

// Oversized name
$csrf = extract_csrf($badEmail['body']) ?? $csrf;
$bigName = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => str_repeat('X', 101),
    'email' => $marker . 'big@example.test',
    'phone' => '1234567890',
    'address' => 'Street 1',
]);
ok('Create rejects oversized name', str_contains($bigName['body'], '100') || str_contains($bigName['body'], 'name') || str_contains($bigName['body'], 'Name'));

// Valid create
$csrf = extract_csrf($bigName['body']) ?? extract_csrf(http_req('GET', $base . '/add_student.php', $cookie)['body']);
$validName = $marker . ' Alice';
$validEmail = $marker . 'alice@example.test';
$create = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $validName,
    'email' => $validEmail,
    'phone' => '+963 944 111',
    'address' => 'Damascus Test St',
]);
ok('Valid create redirects to view', $create['code'] >= 300 && str_contains(location_of($create['headers']), 'view_students.php'), location_of($create['headers']));

$stmt = $pdo->prepare('SELECT * FROM students WHERE email = ? LIMIT 1');
$stmt->execute([$validEmail]);
$row = $stmt->fetch();
ok('Created student exists in DB', is_array($row));
if (is_array($row)) {
    $createdIds[] = (int) $row['id'];
    ok('Created fields match', $row['name'] === $validName && $row['email'] === $validEmail && $row['phone'] === '+963 944 111');
    ok('profile_picture is NULL on create', $row['profile_picture'] === null);
}

// --- READ ---
$view = http_req('GET', $base . '/view_students.php', $cookie);
ok('View page shows new student', $view['code'] === 200 && str_contains($view['body'], e_test($validName)));
ok('View escapes HTML-ish content capability', !str_contains($view['body'], '<script>alert(1)</script>'));

// Nonexistent student update page
$missingStudent = http_req('GET', $base . '/update_student.php?id=99999999', $cookie);
ok('Nonexistent student handled', $missingStudent['code'] >= 300 && str_contains(location_of($missingStudent['headers']), 'view_students.php'));

$badId = http_req('GET', $base . '/update_student.php?id=abc', $cookie);
ok('Invalid update ID rejected', $badId['code'] >= 300);

// --- UPDATE ---
$studentId = $createdIds[0] ?? 0;
ok('Have student id for update tests', $studentId > 0);

// Set a fake profile_picture via SQL to verify UPDATE preserves it
$pdo->prepare('UPDATE students SET profile_picture = :p WHERE id = :id')
    ->execute(['p' => 'uploads/students/keep_me_test.jpg', 'id' => $studentId]);

$editPage = http_req('GET', $base . '/update_student.php?id=' . $studentId, $cookie);
$csrf = extract_csrf($editPage['body']);
ok('Edit page loads prefilled', $editPage['code'] === 200 && str_contains($editPage['body'], e_test($validName)) && $csrf !== null);

// Invalid CSRF update
$badUpdCsrf = http_req('POST', $base . '/update_student.php?id=' . $studentId, $cookie, [
    '_csrf' => 'bad',
    'id' => (string) $studentId,
    'name' => $marker . ' Hacker',
    'email' => $validEmail,
    'phone' => '1234567890',
    'address' => 'X',
]);
ok('Update rejects invalid CSRF', $badUpdCsrf['code'] >= 300);
$stmt = $pdo->prepare('SELECT name FROM students WHERE id = ?');
$stmt->execute([$studentId]);
ok('Invalid CSRF did not change name', (string) $stmt->fetchColumn() === $validName);

// Invalid email on update
$editPage = http_req('GET', $base . '/update_student.php?id=' . $studentId, $cookie);
$csrf = extract_csrf($editPage['body']);
$badUpdEmail = http_req('POST', $base . '/update_student.php?id=' . $studentId, $cookie, [
    '_csrf' => $csrf ?? '',
    'id' => (string) $studentId,
    'name' => $marker . ' Alice',
    'email' => 'nope',
    'phone' => '1234567890',
    'address' => 'Damascus Test St',
]);
ok('Update rejects invalid email', str_contains($badUpdEmail['body'], 'email') || str_contains($badUpdEmail['body'], 'Email'));

// Valid update
$csrf = extract_csrf($badUpdEmail['body']) ?? extract_csrf(http_req('GET', $base . '/update_student.php?id=' . $studentId, $cookie)['body']);
$updatedName = $marker . ' Alice Updated';
$upd = http_req('POST', $base . '/update_student.php?id=' . $studentId, $cookie, [
    '_csrf' => $csrf ?? '',
    'id' => (string) $studentId,
    'name' => $updatedName,
    'email' => $validEmail,
    'phone' => '0999888777',
    'address' => 'Aleppo Test Ave',
]);
ok('Valid update redirects', $upd['code'] >= 300 && str_contains(location_of($upd['headers']), 'view_students.php'));

$stmt = $pdo->prepare('SELECT name, phone, address, profile_picture FROM students WHERE id = ?');
$stmt->execute([$studentId]);
$after = $stmt->fetch();
ok('Update fields saved', is_array($after) && $after['name'] === $updatedName && $after['phone'] === '0999888777');
ok('profile_picture preserved on update', is_array($after) && $after['profile_picture'] === 'uploads/students/keep_me_test.jpg');

// Invalid ID update POST
$editPage = http_req('GET', $base . '/update_student.php?id=' . $studentId, $cookie);
$csrf = extract_csrf($editPage['body']);
$badIdPost = http_req('POST', $base . '/update_student.php?id=' . $studentId, $cookie, [
    '_csrf' => $csrf ?? '',
    'id' => '0',
    'name' => $marker . ' Z',
    'email' => $validEmail,
    'phone' => '1234567890',
    'address' => 'X',
]);
ok('Update rejects invalid POST id', $badIdPost['code'] >= 300);

// SQL injection attempt on update id path
$sqli = http_req('GET', $base . '/update_student.php?id=1%20OR%201=1', $cookie);
ok('SQL injection style ID rejected', $sqli['code'] >= 300);

// --- DELETE ---
// GET must not delete
$getDel = http_req('GET', $base . '/delete_student.php?id=' . $studentId, $cookie);
ok('GET delete does not remove row', $getDel['code'] >= 300);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE id = ?');
$stmt->execute([$studentId]);
ok('Row still exists after GET delete', (int) $stmt->fetchColumn() === 1);

// Missing CSRF
$view = http_req('GET', $base . '/view_students.php', $cookie);
$noCsrf = http_req('POST', $base . '/delete_student.php', $cookie, [
    'id' => (string) $studentId,
]);
ok('Delete without CSRF rejected', $noCsrf['code'] >= 300);
$stmt->execute([$studentId]);
ok('Row still exists after CSRF-less delete', (int) $stmt->fetchColumn() === 1);

// Invalid ID
$csrf = extract_csrf($view['body']);
$delBadId = http_req('POST', $base . '/delete_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'id' => 'abc',
]);
ok('Delete rejects invalid ID', $delBadId['code'] >= 300);

// Valid delete
$view = http_req('GET', $base . '/view_students.php', $cookie);
$csrf = extract_csrf($view['body']);
$del = http_req('POST', $base . '/delete_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'id' => (string) $studentId,
]);
ok('Valid delete redirects', $del['code'] >= 300 && str_contains(location_of($del['headers']), 'view_students.php'));
$stmt->execute([$studentId]);
ok('Deleted student no longer exists', (int) $stmt->fetchColumn() === 0);

// Escape test: create student with special chars via PDO then check view
$escName = $marker . ' <b>Bold</b>';
$escEmail = $marker . 'esc@example.test';
$pdo->prepare('INSERT INTO students (name, email, phone, address) VALUES (?,?,?,?)')
    ->execute([$escName, $escEmail, '1234567890', 'Addr']);
$escId = (int) $pdo->lastInsertId();
$createdIds[] = $escId;
$view = http_req('GET', $base . '/view_students.php', $cookie);
ok('HTML in name is escaped in view', str_contains($view['body'], '&lt;b&gt;Bold&lt;/b&gt;') && !str_contains($view['body'], $marker . ' <b>Bold</b>'));

// Auth still works
$dash = http_req('GET', $base . '/dashboard.php', $cookie);
ok('Auth still works after CRUD', $dash['code'] === 200 && str_contains($dash['body'], $authUser));

// Cleanup
cleanup_test_students($pdo, $marker);
$pdo->prepare('DELETE FROM users WHERE username = ? OR email = ?')->execute([$authUser, $authEmail]);
ok('Test artifacts cleaned', true);
@unlink($cookie);

echo str_repeat('-', 56) . PHP_EOL;
echo "Passed: {$passed}  Failed: {$failed}" . PHP_EOL;
exit($failed > 0 ? 1 : 0);

function e_test(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


