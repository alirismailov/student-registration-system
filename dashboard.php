<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$username = currentUsername() ?? 'مستخدم';
$studentCount = null;

try {
    $studentCount = (int) getPDO()->query('SELECT COUNT(*) FROM students')->fetchColumn();
} catch (Throwable $e) {
    error_log('Dashboard student count failed: ' . $e->getMessage());
}

$currentPage = 'dashboard';
$pageTitle = 'لوحة التحكم | نظام تسجيل الطلاب';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-shell">
    <div class="container">
        <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="page-kicker">الإدارة</p>
                <h1>مرحباً، <?= e($username) ?></h1>
                <p class="page-desc">أدر سجلات الطلاب من مكان واحد. استخدم الاختصارات أدناه لإضافة الطلاب أو تصفحهم.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="stat-card">
                    <div class="stat-label">عدد الطلاب المسجلين</div>
                    <div class="stat-value">
                        <?= $studentCount !== null ? e((string) $studentCount) : '—' ?>
                    </div>
                    <p class="small text-muted mb-0 mt-2">عدد مباشر من قاعدة البيانات.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="stat-card">
                    <div class="stat-label">مسجَّل الدخول باسم</div>
                    <div class="stat-value" style="font-size:1.25rem;"><?= e($username) ?></div>
                    <p class="small text-muted mb-0 mt-2">حساب محمي بجلسة.</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="action-card">
                    <h3>عرض الطلاب</h3>
                    <p>ابحث في سجل الطلاب وافرزه وتصفحه مع تقسيم الصفحات.</p>
                    <a class="btn btn-outline-navy" href="<?= e(base_url('view_students.php')) ?>">فتح قائمة الطلاب</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="action-card">
                    <h3>إضافة طالب</h3>
                    <p>أنشئ سجلاً جديداً يتضمن بيانات التواصل وصورة شخصية اختيارية.</p>
                    <a class="btn btn-navy" href="<?= e(base_url('add_student.php')) ?>">إضافة طالب</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
