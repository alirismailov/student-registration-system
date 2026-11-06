<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/includes/student_query.php';
require_once __DIR__ . '/includes/student_upload.php';

$listError = null;
$params = student_list_params($_GET);
$students = [];
$total = 0;
$page = 1;
$totalPages = 1;

try {
    $result = fetch_students_page(getPDO(), $params);
    $students = $result['students'];
    $total = $result['total'];
    $page = $result['page'];
    $totalPages = $result['total_pages'];
} catch (Throwable $e) {
    error_log('View students failed: ' . $e->getMessage());
    $listError = 'تعذّر تحميل الطلاب حالياً. يرجى المحاولة لاحقاً.';
}

$search = $params['search'];
$sort = $params['sort'];
$sortLabels = [
    'newest' => 'الأحدث أولاً',
    'oldest' => 'الأقدم أولاً',
    'name_asc' => 'الاسم تصاعدياً (أ–ي)',
    'name_desc' => 'الاسم تنازلياً (ي–أ)',
    'email_asc' => 'البريد الإلكتروني تصاعدياً',
    'email_desc' => 'البريد الإلكتروني تنازلياً',
];

$currentPage = 'view';
$pageTitle = 'عرض الطلاب | نظام تسجيل الطلاب';

require_once __DIR__ . '/includes/header.php';

$queryBase = static function (array $overrides = []) use ($search, $sort, $page): string {
    $q = array_merge(['q' => $search, 'sort' => $sort, 'page' => (string) $page], $overrides);
    if (trim((string) ($q['q'] ?? '')) === '') {
        unset($q['q']);
    }
    if ((string) ($q['page'] ?? '1') === '1') {
        unset($q['page']);
    }
    $qs = http_build_query($q);
    return $qs === '' ? 'view_students.php' : 'view_students.php?' . $qs;
};
?>

<section class="page-shell">
    <div class="container">
        <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="page-kicker">الطلاب</p>
                <h1>سجل الطلاب</h1>
                <p class="page-desc">ابحث بالاسم أو البريد الإلكتروني أو الهاتف. افرز وتصفح 10 طلاب في كل صفحة.</p>
            </div>
            <a class="btn btn-navy" href="<?= e(base_url('add_student.php')) ?>">إضافة طالب</a>
        </div>

        <div class="panel students-toolbar mb-3">
            <form method="get" action="<?= e(base_url('view_students.php')) ?>" class="row g-3 align-items-end" role="search">
                <div class="col-md-6">
                    <label for="student_search" class="form-label">بحث</label>
                    <input type="search" class="form-control" id="student_search" name="q"
                           value="<?= e($search) ?>" placeholder="الاسم أو البريد الإلكتروني أو الهاتف" maxlength="100"
                           aria-describedby="student_search_help">
                    <div class="form-text" id="student_search_help">يشمل البحث التطابقات الجزئية في جميع البيانات.</div>
                </div>
                <div class="col-md-4">
                    <label for="student_sort" class="form-label">الفرز حسب</label>
                    <select class="form-select" id="student_sort" name="sort">
                        <?php foreach ($sortLabels as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $sort === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-navy">تطبيق</button>
                </div>
            </form>
            <?php if ($search !== ''): ?>
                <div class="mt-3">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url($queryBase(['q' => '', 'page' => '1']))) ?>">مسح البحث</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <?php if ($listError !== null): ?>
                <div class="alert alert-danger mb-0" role="alert"><?= e($listError) ?></div>
            <?php elseif ($total === 0): ?>
                <div class="students-empty text-muted">
                    <div class="students-empty-icon" aria-hidden="true">0</div>
                    <?php if ($search !== ''): ?>
                        <h2 class="h5 text-navy">لا يوجد طلاب مطابقون</h2>
                        <p class="mb-3">لا توجد نتائج مطابقة لـ «<?= e($search) ?>».</p>
                        <a class="btn btn-outline-navy" href="<?= e(base_url('view_students.php')) ?>">مسح البحث</a>
                    <?php else: ?>
                        <h2 class="h5 text-navy">لا يوجد طلاب بعد</h2>
                        <p class="mb-3">أضف أول طالب لبدء السجل.</p>
                        <a class="btn btn-navy" href="<?= e(base_url('add_student.php')) ?>">إضافة أول طالب</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="small text-muted mb-0" id="students_summary">
                        عرض الصفحة <?= e((string) $page) ?> من <?= e((string) $totalPages) ?>
                        (عدد الطلاب: <?= e((string) $total) ?><?= $search !== '' ? ' مطابقون للبحث' : '' ?>).
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 students-table" aria-describedby="students_summary">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">الصورة</th>
                                <th scope="col">الرقم</th>
                                <th scope="col">الاسم</th>
                                <th scope="col">البريد الإلكتروني</th>
                                <th scope="col">الهاتف</th>
                                <th scope="col">العنوان</th>
                                <th scope="col">تاريخ التسجيل</th>
                                <th scope="col" class="text-end">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <?php
                                $sid = (int) $student['id'];
                                $sname = (string) $student['name'];
                                $created = (string) ($student['created_at'] ?? '');
                                $createdLabel = $created !== '' ? date('Y-m-d H:i', strtotime($created)) : '—';
                                $imgUrl = student_image_url(
                                    $student['profile_picture'] !== null ? (string) $student['profile_picture'] : null
                                );
                                $initial = mb_strtoupper(mb_substr($sname !== '' ? $sname : '?', 0, 1));
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($imgUrl !== null): ?>
                                            <img src="<?= e($imgUrl) ?>" alt="صورة <?= e($sname) ?>" class="student-avatar rounded">
                                        <?php else: ?>
                                            <div class="student-avatar student-avatar-placeholder rounded" aria-hidden="true"><?= e($initial) ?></div>
                                            <span class="visually-hidden">لا توجد صورة لـ <?= e($sname) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e((string) $sid) ?></td>
                                    <td class="fw-semibold"><?= e($sname) ?></td>
                                    <td class="text-break"><?= e((string) $student['email']) ?></td>
                                    <td class="text-nowrap"><?= e((string) $student['phone']) ?></td>
                                    <td class="text-break student-address-cell"><?= e((string) $student['address']) ?></td>
                                    <td class="text-nowrap"><?= e($createdLabel) ?></td>
                                    <td class="text-end">
                                        <div class="students-actions">
                                            <a class="btn btn-sm btn-outline-navy"
                                               href="<?= e(base_url('update_student.php?id=' . $sid)) ?>">تعديل</a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteStudentModal"
                                                    data-student-id="<?= e((string) $sid) ?>"
                                                    data-student-name="<?= e($sname) ?>"
                                                    aria-label="حذف <?= e($sname) ?>">
                                                حذف
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3" aria-label="ترقيم صفحات قائمة الطلاب">
                        <ul class="pagination mb-0 flex-wrap">
                            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                <?php if ($page <= 1): ?>
                                    <span class="page-link" aria-disabled="true">السابق</span>
                                <?php else: ?>
                                    <a class="page-link" rel="prev" href="<?= e(base_url($queryBase(['page' => (string) ($page - 1)]))) ?>">السابق</a>
                                <?php endif; ?>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link" aria-current="page">الصفحة <?= e((string) $page) ?> / <?= e((string) $totalPages) ?></span>
                            </li>
                            <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
                                <?php if ($page >= $totalPages): ?>
                                    <span class="page-link" aria-disabled="true">التالي</span>
                                <?php else: ?>
                                    <a class="page-link" rel="next" href="<?= e(base_url($queryBase(['page' => (string) ($page + 1)]))) ?>">التالي</a>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($listError === null && $students !== []): ?>
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= e(base_url('delete_student.php')) ?>" id="deleteStudentForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="deleteStudentId" value="">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="deleteStudentModalLabel">تأكيد الحذف</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        هل تريد حذف الطالب <strong id="deleteStudentName"></strong>؟ لا يمكن التراجع عن هذا الإجراء.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">حذف الطالب</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('deleteStudentModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    if (!(button instanceof HTMLElement)) return;
    const id = button.getAttribute('data-student-id') || '';
    const name = button.getAttribute('data-student-name') || '';
    const idInput = document.getElementById('deleteStudentId');
    const nameEl = document.getElementById('deleteStudentName');
    if (idInput instanceof HTMLInputElement) idInput.value = id;
    if (nameEl) nameEl.textContent = name;
  });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
