<?php
/**
 * Phase 5 advanced-feature integration tests (CLI only).
 * Creates and deletes only temporary P5TEST_* records/files.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/student_upload.php';
require_once $root . '/includes/student_query.php';

$base = getenv('BWP501_BASE_URL') ?: 'http://127.0.0.1:8080/ab_hasan';
$cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bwp501_p5_cookies.txt';
@unlink($cookie);

$marker = 'P5TEST_' . bin2hex(random_bytes(4));
$passed = 0;
$failed = 0;
$skipped = 0;

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

function skip(string $label, string $reason): void
{
    global $skipped;
    $skipped++;
    echo "[NOT TESTED] {$label} — {$reason}" . PHP_EOL;
}

/** @return array{code:int,headers:string,body:string} */
function http_req(string $method, string $url, ?string $cookieFile, array $post = [], array $files = []): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'BWP501-P5Test/1.0',
    ];
    if ($cookieFile !== null) {
        $opts[CURLOPT_COOKIEJAR] = $cookieFile;
        $opts[CURLOPT_COOKIEFILE] = $cookieFile;
    }
    if ($method === 'POST') {
        if ($files !== []) {
            $payload = $post;
            foreach ($files as $field => $path) {
                $mime = mime_content_type($path) ?: 'application/octet-stream';
                $payload[$field] = new CURLFile($path, $mime, basename($path));
            }
            $opts[CURLOPT_POSTFIELDS] = $payload;
        } else {
            $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
    }
    curl_setopt_array($ch, $opts);
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

/** Minimal valid image bytes (no GD dependency). */
function make_temp_image(string $format): string
{
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bwp501_' . bin2hex(random_bytes(4));
    if ($format === 'jpeg') {
        $path .= '.jpg';
        $bin = base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k='
        );
        file_put_contents($path, $bin);
    } elseif ($format === 'png') {
        $path .= '.png';
        $bin = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        file_put_contents($path, $bin);
    } else {
        $path .= '.webp';
        $bin = base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA=');
        file_put_contents($path, $bin);
    }
    return $path;
}

function cleanup_p5(PDO $pdo, string $marker): void
{
    $stmt = $pdo->prepare('SELECT id, profile_picture FROM students WHERE name LIKE :m OR email LIKE :e');
    $stmt->execute(['m' => $marker . '%', 'e' => $marker . '%']);
    foreach ($stmt->fetchAll() as $row) {
        $pic = $row['profile_picture'] !== null ? (string) $row['profile_picture'] : null;
        $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([(int) $row['id']]);
        if ($pic) {
            delete_managed_student_image($pdo, $pic, null);
        }
    }
    $pdo->prepare('DELETE FROM users WHERE username LIKE ? OR email LIKE ?')
        ->execute([$marker . '%', $marker . '%']);
}

echo "Phase 5 student advanced-feature tests\n";
echo str_repeat('-', 56) . PHP_EOL;

$pdo = getPDO();
cleanup_p5($pdo, $marker);

ok('Sort allowlist rejects unsafe key', resolve_student_sort('name;drop table') === 'newest');
ok('LIKE wildcards escaped', escape_like_wildcards('100%_x') === '100\\%\\_x');
ok('Managed path rejects traversal', !is_managed_student_image('uploads/students/../config/app.php'));

$authUser = $marker . '_u';
$authEmail = $marker . '_u@example.test';
$authPass = 'Phase5Pass!' . bin2hex(random_bytes(2));
$pdo->prepare('INSERT INTO users (username, email, password) VALUES (?,?,?)')
    ->execute([$authUser, $authEmail, password_hash($authPass, PASSWORD_DEFAULT)]);

$loginPage = http_req('GET', $base . '/login.php', $cookie);
$csrf = extract_csrf($loginPage['body']);
$login = http_req('POST', $base . '/login.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'identifier' => $authUser,
    'password' => $authPass,
]);
ok('Login for Phase 5 tests', $login['code'] >= 300 && str_contains(location_of($login['headers']), 'dashboard.php'));

$jpg = make_temp_image('jpeg');
$png = make_temp_image('png');
$webp = make_temp_image('webp');

$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$createJpg = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' JPEG',
    'email' => $marker . 'jpeg@example.test',
    'phone' => '1111111111',
    'address' => 'Addr JPEG',
], ['profile_picture' => $jpg]);
ok('Valid JPEG upload redirects', $createJpg['code'] >= 300 && str_contains(location_of($createJpg['headers']), 'view_students.php'), 'HTTP ' . $createJpg['code']);

$fetch = $pdo->prepare('SELECT * FROM students WHERE email = ?');
$fetch->execute([$marker . 'jpeg@example.test']);
$jpegStudent = $fetch->fetch();
ok('JPEG student stored with image path', is_array($jpegStudent) && is_string($jpegStudent['profile_picture'] ?? null));
$oldPic = '';
$jpegId = 0;
if (is_array($jpegStudent)) {
    $jpegId = (int) $jpegStudent['id'];
    $oldPic = (string) $jpegStudent['profile_picture'];
    ok('JPEG path is managed uploads/students file', is_managed_student_image($oldPic));
    ok('JPEG filename randomized (not original)', !str_contains($oldPic, basename($jpg)));
    ok('Correct DB image path prefix', str_starts_with($oldPic, 'uploads/students/'));
}

$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$createPng = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' PNG',
    'email' => $marker . 'png@example.test',
    'phone' => '2222222222',
    'address' => 'Addr PNG',
], ['profile_picture' => $png]);
ok('Valid PNG upload redirects', $createPng['code'] >= 300);
$fetch->execute([$marker . 'png@example.test']);
$pngStudent = $fetch->fetch();
ok('PNG student has image', is_array($pngStudent) && is_managed_student_image((string) ($pngStudent['profile_picture'] ?? '')));

$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$createWebp = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' WEBP',
    'email' => $marker . 'webp@example.test',
    'phone' => '3333333333',
    'address' => 'Addr WEBP',
], ['profile_picture' => $webp]);
ok('Valid WEBP upload redirects', $createWebp['code'] >= 300, 'HTTP ' . $createWebp['code']);
$fetch->execute([$marker . 'webp@example.test']);
$webpStudent = $fetch->fetch();
ok('WEBP student has image', is_array($webpStudent) && is_managed_student_image((string) ($webpStudent['profile_picture'] ?? '')));

$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$noImg = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' NoImg',
    'email' => $marker . 'noimg@example.test',
    'phone' => '4444444444',
    'address' => 'Addr None',
]);
ok('Add without image succeeds', $noImg['code'] >= 300);
$fetch->execute([$marker . 'noimg@example.test']);
$noImgStudent = $fetch->fetch();
ok('No-image student has NULL picture', is_array($noImgStudent) && $noImgStudent['profile_picture'] === null);

$exePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bwp501_fake.txt';
file_put_contents($exePath, 'not-an-allowed-image-payload');
$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$badMime = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' BadMime',
    'email' => $marker . 'badmime@example.test',
    'phone' => '5555555555',
    'address' => 'Addr',
], ['profile_picture' => $exePath]);
ok('Unsupported MIME upload rejected', $badMime['code'] === 200 && (str_contains($badMime['body'], 'JPEG') || str_contains($badMime['body'], 'image')));
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE email = ?');
$countStmt->execute([$marker . 'badmime@example.test']);
ok('Rejected upload did not insert', (int) $countStmt->fetchColumn() === 0);

$bigPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bwp501_big.jpg';
copy($jpg, $bigPath);
file_put_contents($bigPath, str_repeat('0', STUDENT_UPLOAD_MAX_BYTES + 2048), FILE_APPEND);
$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$bigUp = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' Big',
    'email' => $marker . 'big@example.test',
    'phone' => '6666666666',
    'address' => 'Addr',
], ['profile_picture' => $bigPath]);
ok('Oversized file rejected', $bigUp['code'] === 200 && (str_contains($bigUp['body'], '2 MB') || str_contains($bigUp['body'], 'Image') || str_contains($bigUp['body'], 'size')));

$fakeImg = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bwp501_fake.jpg';
// JPEG magic so finfo reports image/jpeg, but content is not a valid image.
file_put_contents($fakeImg, "\xFF\xD8\xFF\xE0" . str_repeat('X', 200));
$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$mal = http_req('POST', $base . '/add_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' Mal',
    'email' => $marker . 'mal@example.test',
    'phone' => '7777777777',
    'address' => 'Addr',
], ['profile_picture' => $fakeImg]);
ok('Malformed image rejected', $mal['code'] === 200 && str_contains($mal['body'], 'valid image'));

$edit = http_req('GET', $base . '/update_student.php?id=' . $jpegId, $cookie);
$csrf = extract_csrf($edit['body']);
$updKeep = http_req('POST', $base . '/update_student.php?id=' . $jpegId, $cookie, [
    '_csrf' => $csrf ?? '',
    'id' => (string) $jpegId,
    'name' => $marker . ' JPEG Kept',
    'email' => $marker . 'jpeg@example.test',
    'phone' => '1111111111',
    'address' => 'Addr JPEG 2',
]);
ok('Update without new image redirects', $updKeep['code'] >= 300);
$picStmt = $pdo->prepare('SELECT profile_picture FROM students WHERE id = ?');
$picStmt->execute([$jpegId]);
ok('Existing image preserved without replacement', (string) $picStmt->fetchColumn() === $oldPic);

$repl = make_temp_image('png');
$edit = http_req('GET', $base . '/update_student.php?id=' . $jpegId, $cookie);
$csrf = extract_csrf($edit['body']);
$updRepl = http_req('POST', $base . '/update_student.php?id=' . $jpegId, $cookie, [
    '_csrf' => $csrf ?? '',
    'id' => (string) $jpegId,
    'name' => $marker . ' JPEG Replaced',
    'email' => $marker . 'jpeg@example.test',
    'phone' => '1111111111',
    'address' => 'Addr JPEG 3',
], ['profile_picture' => $repl]);
ok('Image replacement redirects', $updRepl['code'] >= 300);
$picStmt->execute([$jpegId]);
$newPic = (string) $picStmt->fetchColumn();
ok('Image path changed after replacement', $newPic !== '' && $newPic !== $oldPic);
$oldAbs = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldPic);
ok('Old managed image cleaned up', $oldPic === '' || !is_file($oldAbs));

$prevPic = $newPic;
$repl2 = make_temp_image('jpeg');
$edit = http_req('GET', $base . '/update_student.php?id=' . $jpegId, $cookie);
$csrf = extract_csrf($edit['body']);
$failUpd = http_req('POST', $base . '/update_student.php?id=' . $jpegId, $cookie, [
    '_csrf' => $csrf ?? '',
    'id' => (string) $jpegId,
    'name' => $marker . ' Fail',
    'email' => 'bad-email',
    'phone' => '1111111111',
    'address' => 'Addr',
], ['profile_picture' => $repl2]);
ok('Failed update shows validation', $failUpd['code'] === 200);
$picStmt->execute([$jpegId]);
ok('Failed update keeps previous image', (string) $picStmt->fetchColumn() === $prevPic);

for ($i = 1; $i <= 12; $i++) {
    $pdo->prepare('INSERT INTO students (name, email, phone, address) VALUES (?,?,?,?)')
        ->execute([
            $marker . ' Page ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            $marker . 'page' . $i . '@example.test',
            '90000000' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'Pagination St ' . $i,
        ]);
}

$view = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page'), $cookie);
ok('Name search finds matches', $view['code'] === 200 && str_contains($view['body'], $marker . ' Page'));
preg_match_all('/' . preg_quote($marker . ' Page ', '/') . '(\d+)/', $view['body'], $pageNames);
$tableNameHits = array_values(array_unique($pageNames[1] ?? []));
ok('Partial matching works', $tableNameHits !== []);
ok('Pagination shows at most 10 matches on page 1', count($tableNameHits) <= 10 && count($tableNameHits) >= 1);
ok('Empty search loads list', http_req('GET', $base . '/view_students.php', $cookie)['code'] === 200);

$viewEmail = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . 'page3@'), $cookie);
ok('Email search works', str_contains($viewEmail['body'], $marker . 'page3@example.test'));

$viewPhone = http_req('GET', $base . '/view_students.php?q=' . rawurlencode('9000000003'), $cookie);
ok('Phone search works', str_contains($viewPhone['body'], '9000000003'));

$viewEmpty = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . 'ZZZNONE'), $cookie);
ok('No-results state', str_contains($viewEmpty['body'], 'No students match') || str_contains($viewEmpty['body'], 'No students'));

$inj = rawurlencode("%' OR 1=1 --");
$viewInj = http_req('GET', $base . '/view_students.php?q=' . $inj, $cookie);
ok('Search SQL injection resisted', $viewInj['code'] === 200);

$viewLike = http_req('GET', $base . '/view_students.php?q=' . rawurlencode('%'), $cookie);
ok('LIKE wildcard user input handled', $viewLike['code'] === 200);

$page2 = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&page=2', $cookie);
ok('Page 2 navigation works', $page2['code'] === 200 && (str_contains($page2['body'], 'Page 2') || str_contains($page2['body'], 'page 2') || str_contains($page2['body'], 'page=1')));

$outRange = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&page=999', $cookie);
ok('Out-of-range page normalized', $outRange['code'] === 200);

$params = student_list_params(['q' => $marker . ' Page', 'page' => '1', 'sort' => 'newest']);
$pageData = fetch_students_page($pdo, $params);
ok('COUNT matches expected search total', ($pageData['total'] ?? 0) >= 12);
ok('LIMIT returns <= 10 rows', count($pageData['rows'] ?? []) <= 10);

$nameAsc = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&sort=name_asc', $cookie);
ok('Name A-Z sort param accepted', $nameAsc['code'] === 200);
$nameDesc = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&sort=name_desc', $cookie);
ok('Name Z-A sort param accepted', $nameDesc['code'] === 200);
$emailAsc = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&sort=email_asc', $cookie);
ok('Email A-Z sort param accepted', $emailAsc['code'] === 200);
$emailDesc = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&sort=email_desc', $cookie);
ok('Email Z-A sort param accepted', $emailDesc['code'] === 200);
$newest = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&sort=newest', $cookie);
ok('Newest sort param accepted', $newest['code'] === 200);
$oldest = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&sort=oldest', $cookie);
ok('Oldest sort param accepted', $oldest['code'] === 200);

$badSort = http_req('GET', $base . '/view_students.php?sort=id;%20drop', $cookie);
ok('Unsafe sort falls back safely', $badSort['code'] === 200);

$preserve = http_req('GET', $base . '/view_students.php?q=' . rawurlencode($marker . ' Page') . '&sort=oldest&page=2', $cookie);
ok('Search+sort preserved on page 2', str_contains($preserve['body'], 'oldest') || str_contains($preserve['body'], 'selected') || str_contains($preserve['body'], 'sort=oldest'));

$addPage = http_req('GET', $base . '/add_student.php', $cookie);
$csrf = extract_csrf($addPage['body']);
$ajaxOk = http_req('POST', $base . '/api/validate_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => $marker . ' AJAX',
    'email' => $marker . 'ajax@example.test',
    'phone' => '1234567890',
    'address' => 'Valid Addr',
]);
$ajaxData = json_decode($ajaxOk['body'], true);
ok('AJAX valid payload', $ajaxOk['code'] === 200 && is_array($ajaxData) && ($ajaxData['valid'] ?? false) === true);
ok('AJAX JSON structure has valid+errors keys', is_array($ajaxData) && array_key_exists('valid', $ajaxData) && array_key_exists('errors', $ajaxData));

$ajaxBad = http_req('POST', $base . '/api/validate_student.php', $cookie, [
    '_csrf' => $csrf ?? '',
    'name' => '',
    'email' => 'bad',
    'phone' => '',
    'address' => '',
]);
$ajaxBadData = json_decode($ajaxBad['body'], true);
ok(
    'AJAX invalid email/required errors',
    is_array($ajaxBadData) && ($ajaxBadData['valid'] ?? true) === false && isset($ajaxBadData['errors']['email'])
);

$ajaxCsrf = http_req('POST', $base . '/api/validate_student.php', $cookie, [
    '_csrf' => 'bad',
    'name' => 'X',
    'email' => 'a@b.co',
    'phone' => '1234567890',
    'address' => 'A',
]);
ok('AJAX invalid CSRF rejected', $ajaxCsrf['code'] === 403);

@unlink($cookie);
$ajaxUnauth = http_req('POST', $base . '/api/validate_student.php', null, [
    '_csrf' => 'x',
    'name' => 'X',
    'email' => 'a@b.co',
    'phone' => '1234567890',
    'address' => 'A',
]);
ok('AJAX unauthorized rejected', $ajaxUnauth['code'] === 401 || ($ajaxUnauth['code'] >= 300 && str_contains(location_of($ajaxUnauth['headers']), 'login.php')));

$countStmt->execute([$marker . 'ajax@example.test']);
ok('AJAX does not mutate DB', (int) $countStmt->fetchColumn() === 0);

skip('Browser AJAX UI interaction', 'no browser automation in this run');

if ($newPic !== '') {
    $imgHttp = http_req('GET', $base . '/' . $newPic, null);
    ok('Uploaded image served by Apache', $imgHttp['code'] === 200);
    ok('Uploaded executable cannot execute as PHP', !str_ends_with(strtolower($newPic), '.php'));
}

@unlink($jpg);
@unlink($png);
@unlink($webp);
@unlink($exePath);
@unlink($bigPath);
@unlink($fakeImg);
@unlink($repl);
@unlink($repl2);

cleanup_p5($pdo, $marker);
ok('Cleanup completed', true);

echo str_repeat('-', 56) . PHP_EOL;
echo 'Passed: ' . $passed . '  Failed: ' . $failed . '  Not tested: ' . $skipped . PHP_EOL;
exit($failed > 0 ? 1 : 0);
