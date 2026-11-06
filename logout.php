<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

init_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    flash_set('warning', 'يجب تسجيل الخروج باستخدام زر تسجيل الخروج.');
    redirect_to(isAuthenticated() ? 'dashboard.php' : 'login.php');
}

require_valid_csrf();

logout_user();
init_session();
flash_set('success', 'تم تسجيل خروجك.');
redirect_to('login.php');
