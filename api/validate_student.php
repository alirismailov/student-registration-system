<?php
/**
 * AJAX student field validation (no DB mutation).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/student_validation.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

init_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'errors' => ['form' => 'الطريقة غير مسموح بها.']]);
    exit;
}

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['valid' => false, 'errors' => ['form' => 'يلزم تسجيل الدخول.']]);
    exit;
}

// Soft existence check without full HTML redirect guard.
$userId = currentUserId();
if ($userId === null) {
    http_response_code(401);
    echo json_encode(['valid' => false, 'errors' => ['form' => 'يلزم تسجيل الدخول.']]);
    exit;
}

$token = isset($_POST['_csrf']) ? (string) $_POST['_csrf'] : null;
if (!csrf_verify($token)) {
    http_response_code(403);
    echo json_encode(['valid' => false, 'errors' => ['form' => 'رمز الجلسة غير صالح. حدّث الصفحة وحاول مرة أخرى.']]);
    exit;
}

$validated = validate_student_input($_POST);
$valid = $validated['errors'] === [];

http_response_code(200);
echo json_encode([
    'valid' => $valid,
    'errors' => $validated['errors'],
], JSON_UNESCAPED_UNICODE);
