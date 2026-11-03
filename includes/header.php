<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

init_session();

$currentPage = $currentPage ?? '';
$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';
$flash = flash_get();
$loggedIn = isAuthenticated();
$navUsername = currentUsername();

if ($loggedIn && $bodyClass === '') {
    $bodyClass = 'app-authenticated';
} elseif (!$loggedIn && $bodyClass === '') {
    $bodyClass = 'app-public';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Student Registration System — BWP501 university project">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/students.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="visually-hidden-focusable btn btn-sm btn-primary skip-link" href="#main-content">Skip to main content</a>

<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(base_url($loggedIn ? 'dashboard.php' : 'index.php')) ?>">
            <?= e(APP_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                <?php if ($loggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'dashboard' ? ' active' : '' ?>"
                           href="<?= e(base_url('dashboard.php')) ?>"<?= $currentPage === 'dashboard' ? ' aria-current="page"' : '' ?>>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'view' ? ' active' : '' ?>"
                           href="<?= e(base_url('view_students.php')) ?>"<?= $currentPage === 'view' ? ' aria-current="page"' : '' ?>>Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'add' ? ' active' : '' ?>"
                           href="<?= e(base_url('add_student.php')) ?>"<?= $currentPage === 'add' ? ' aria-current="page"' : '' ?>>Add Student</a>
                    </li>
                    <?php if ($navUsername !== null): ?>
                        <li class="nav-item d-none d-lg-block">
                            <span class="nav-link nav-user" title="Signed-in account">
                                <?= e($navUsername) ?>
                            </span>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <form method="post" action="<?= e(base_url('logout.php')) ?>" class="d-inline ms-lg-1">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-logout">Logout</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'home' ? ' active' : '' ?>"
                           href="<?= e(base_url('index.php')) ?>"<?= $currentPage === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentPage === 'login' ? ' active' : '' ?>"
                           href="<?= e(base_url('login.php')) ?>"<?= $currentPage === 'login' ? ' aria-current="page"' : '' ?>>Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-light text-navy fw-semibold ms-lg-2 px-3"
                           href="<?= e(base_url('register.php')) ?>">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main id="main-content" class="app-main" tabindex="-1">
<?php if ($flash !== null): ?>
    <div class="container pt-3">
        <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : ($flash['type'] === 'success' ? 'success' : 'info'))) ?> alert-dismissible fade show"
             role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close notification"></button>
        </div>
    </div>
<?php endif; ?>
