<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

init_session();
$isLoggedIn = isAuthenticated();

$currentPage = 'home';
$pageTitle = 'الرئيسية | نظام تسجيل الطلاب';
$bodyClass = $isLoggedIn ? 'page-home app-authenticated' : 'page-home app-public';

require_once __DIR__ . '/includes/header.php';
?>


<section class="hero">
    
    <div class="container py-5">
        
        <div class="row align-items-center g-4">
            <section class="developer-banner">
    <div class="developer-banner-content">
        <span class="developer-label">مطوّرو المشروع</span>
        <h2>تصميم وتطوير</h2>

        <div class="developer-names">
            <span class="developer-name">
                <span class="developer-icon">✦</span>
                191708 - علي رافع اسماعيل
            </span>

            <span class="developer-name">
                <span class="developer-icon">✦</span>
                259605 - جوى هيثم عثمان
            </span>
        </div>
    </div>

    <div class="developer-decoration">BWP501</div>
</section>
            <div class="col-lg-7">
                <span class="hero-badge mb-3">BWP501 · الجامعة الافتراضية السورية</span>
                <h1 class="display-5 fw-bold text-navy mb-3">نظام تسجيل الطلاب</h1>
                <p class="lead text-muted mb-4">
                    تطبيق آمن للإدارة الأكاديمية لإدارة سجلات الطلاب —
                    المصادقة، والإضافة والعرض والتعديل والحذف، والصور الشخصية، والبحث، والفرز، وتقسيم الصفحات.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($isLoggedIn): ?>
                        <a class="btn btn-navy btn-lg" href="<?= e(base_url('dashboard.php')) ?>">فتح لوحة التحكم</a>
                        <a class="btn btn-outline-navy btn-lg" href="<?= e(base_url('view_students.php')) ?>">عرض الطلاب</a>
                        <a class="btn btn-outline-secondary btn-lg" href="<?= e(base_url('add_student.php')) ?>">إضافة طالب</a>
                    <?php else: ?>
                        <a class="btn btn-navy btn-lg" href="<?= e(base_url('login.php')) ?>">تسجيل الدخول</a>
                        <a class="btn btn-outline-navy btn-lg" href="<?= e(base_url('register.php')) ?>">إنشاء حساب</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-panel">
                    <h2 class="h5 text-navy mb-3">إمكانيات النظام</h2>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><span class="status-pill me-1">مكتمل</span> المصادقة والجلسات</li>
                        <li class="mb-2"><span class="status-pill me-1">مكتمل</span> إضافة / عرض / تعديل / حذف الطلاب</li>
                        <li class="mb-2"><span class="status-pill me-1">مكتمل</span> الصور الشخصية (JPEG / PNG / WEBP)</li>
                        <li class="mb-2"><span class="status-pill me-1">مكتمل</span> البحث والفرز وتقسيم الصفحات</li>
                        <li class="mb-0"><span class="status-pill me-1">مكتمل</span> التحقق من النماذج عبر AJAX</li>
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
                <h2 class="h3 text-navy mb-2">مصمَّم لسير عمل إدارة المقررات</h2>
                <p class="text-muted mb-0">
                    أدوات بسيطة للحفاظ على سجل طلاب دقيق —
                    دون ادعاءات تسويقية أو إحصاءات وهمية.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <article class="feature-tile">
                    <h3>وصول آمن</h3>
                    <p>إنشاء الحساب، وتسجيل الدخول والخروج، وتشفير كلمات المرور، وجلسات PHP، والحماية من هجمات CSRF.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-tile">
                    <h3>سجلات الطلاب</h3>
                    <p>إضافة الطلاب وتصفحهم وتعديلهم وحذفهم مع التأكيد والتحقق من صحة البيانات.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-tile">
                    <h3>أدوات تشغيلية</h3>
                    <p>الصور الشخصية، والبحث، والفرز، وتقسيم الصفحات من جهة الخادم، والتحقق الفوري عبر AJAX.</p>
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
                    <h2 class="h4 text-navy mb-2">ابدأ الآن</h2>
                    <p class="text-muted mb-0">
                        <?php if ($isLoggedIn): ?>
                            تابع إلى لوحة التحكم أو انتقل مباشرة إلى إدارة الطلاب.
                        <?php else: ?>
                            أنشئ حساباً أو سجّل الدخول لإدارة سجلات الطلاب.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="d-grid gap-2">
                        <?php if ($isLoggedIn): ?>
                            <a class="btn btn-navy" href="<?= e(base_url('dashboard.php')) ?>">لوحة التحكم</a>
                            <a class="btn btn-outline-navy" href="<?= e(base_url('view_students.php')) ?>">عرض الطلاب</a>
                        <?php else: ?>
                            <a class="btn btn-navy" href="<?= e(base_url('register.php')) ?>">إنشاء حساب</a>
                            <a class="btn btn-outline-navy" href="<?= e(base_url('login.php')) ?>">تسجيل الدخول</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
