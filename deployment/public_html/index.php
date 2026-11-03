<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

init_session();
$isLoggedIn = isAuthenticated();

$currentPage = 'home';
$pageTitle = 'Home | Student Registration System';
$bodyClass = $isLoggedIn ? 'page-home app-authenticated' : 'page-home app-public';

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container py-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="hero-badge mb-3">BWP501 · Syrian Virtual University</span>
                <h1 class="display-5 fw-bold text-navy mb-3">Student Registration System</h1>
                <p class="lead text-muted mb-4">
                    A secure academic administration app for managing student records —
                    authentication, CRUD, profile images, search, sorting, and pagination.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($isLoggedIn): ?>
                        <a class="btn btn-navy btn-lg" href="<?= e(base_url('dashboard.php')) ?>">Open dashboard</a>
                        <a class="btn btn-outline-navy btn-lg" href="<?= e(base_url('view_students.php')) ?>">View students</a>
                        <a class="btn btn-outline-secondary btn-lg" href="<?= e(base_url('add_student.php')) ?>">Add student</a>
                    <?php else: ?>
                        <a class="btn btn-navy btn-lg" href="<?= e(base_url('login.php')) ?>">Login</a>
                        <a class="btn btn-outline-navy btn-lg" href="<?= e(base_url('register.php')) ?>">Register</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-panel">
                    <h2 class="h5 text-navy mb-3">System capabilities</h2>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><span class="status-pill me-1">Done</span> Authentication &amp; sessions</li>
                        <li class="mb-2"><span class="status-pill me-1">Done</span> Student create / read / update / delete</li>
                        <li class="mb-2"><span class="status-pill me-1">Done</span> Profile pictures (JPEG / PNG / WEBP)</li>
                        <li class="mb-2"><span class="status-pill me-1">Done</span> Search, sort, and pagination</li>
                        <li class="mb-0"><span class="status-pill me-1">Done</span> AJAX form validation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <h2 class="h3 text-navy mb-2">Built for course administration workflows</h2>
                <p class="text-muted mb-0">
                    Straightforward tools for maintaining an accurate student register —
                    without inventing marketing claims or fictitious statistics.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <article class="feature-tile">
                    <h3>Secure access</h3>
                    <p>Registration, login, logout, password hashing, PHP sessions, and CSRF protection.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-tile">
                    <h3>Student records</h3>
                    <p>Add, browse, update, and delete students with confirmation and validation.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-tile">
                    <h3>Operational tools</h3>
                    <p>Profile images, search, sorting, server-side pagination, and AJAX feedback.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="section-block pt-0">
    <div class="container">
        <div class="panel panel-elevated">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <h2 class="h4 text-navy mb-2">Get started</h2>
                    <p class="text-muted mb-0">
                        <?php if ($isLoggedIn): ?>
                            Continue to the dashboard or jump straight into student management.
                        <?php else: ?>
                            Create an account or sign in to manage student records.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="d-grid gap-2">
                        <?php if ($isLoggedIn): ?>
                            <a class="btn btn-navy" href="<?= e(base_url('dashboard.php')) ?>">Dashboard</a>
                            <a class="btn btn-outline-navy" href="<?= e(base_url('view_students.php')) ?>">View students</a>
                        <?php else: ?>
                            <a class="btn btn-navy" href="<?= e(base_url('register.php')) ?>">Create account</a>
                            <a class="btn btn-outline-navy" href="<?= e(base_url('login.php')) ?>">Sign in</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
