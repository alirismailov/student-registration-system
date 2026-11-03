<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$username = currentUsername() ?? 'User';
$studentCount = null;

try {
    $studentCount = (int) getPDO()->query('SELECT COUNT(*) FROM students')->fetchColumn();
} catch (Throwable $e) {
    error_log('Dashboard student count failed: ' . $e->getMessage());
}

$currentPage = 'dashboard';
$pageTitle = 'Dashboard | Student Registration System';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-shell">
    <div class="container">
        <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="page-kicker">Administration</p>
                <h1>Welcome, <?= e($username) ?></h1>
                <p class="page-desc">Manage student records from one place. Use the shortcuts below to add or browse students.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="stat-card">
                    <div class="stat-label">Students on record</div>
                    <div class="stat-value">
                        <?= $studentCount !== null ? e((string) $studentCount) : '—' ?>
                    </div>
                    <p class="small text-muted mb-0 mt-2">Live count from the database.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="stat-card">
                    <div class="stat-label">Signed in as</div>
                    <div class="stat-value" style="font-size:1.25rem;"><?= e($username) ?></div>
                    <p class="small text-muted mb-0 mt-2">Session-protected account.</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="action-card">
                    <h3>View students</h3>
                    <p>Search, sort, and browse the student register with pagination.</p>
                    <a class="btn btn-outline-navy" href="<?= e(base_url('view_students.php')) ?>">Open student list</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="action-card">
                    <h3>Add student</h3>
                    <p>Create a new record with contact details and an optional profile photo.</p>
                    <a class="btn btn-navy" href="<?= e(base_url('add_student.php')) ?>">Add a student</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
