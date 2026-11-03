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
    $listError = 'Unable to load students right now. Please try again later.';
}

$search = $params['search'];
$sort = $params['sort'];
$sortLabels = [
    'newest' => 'Newest first',
    'oldest' => 'Oldest first',
    'name_asc' => 'Name A–Z',
    'name_desc' => 'Name Z–A',
    'email_asc' => 'Email A–Z',
    'email_desc' => 'Email Z–A',
];

$currentPage = 'view';
$pageTitle = 'View Students | Student Registration System';

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
                <p class="page-kicker">Students</p>
                <h1>Student register</h1>
                <p class="page-desc">Search by name, email, or phone. Sort and browse 10 students per page.</p>
            </div>
            <a class="btn btn-navy" href="<?= e(base_url('add_student.php')) ?>">Add student</a>
        </div>

        <div class="panel students-toolbar mb-3">
            <form method="get" action="<?= e(base_url('view_students.php')) ?>" class="row g-3 align-items-end" role="search">
                <div class="col-md-6">
                    <label for="student_search" class="form-label">Search</label>
                    <input type="search" class="form-control" id="student_search" name="q"
                           value="<?= e($search) ?>" placeholder="Name, email, or phone" maxlength="100"
                           aria-describedby="student_search_help">
                    <div class="form-text" id="student_search_help">Partial matches across the full dataset.</div>
                </div>
                <div class="col-md-4">
                    <label for="student_sort" class="form-label">Sort by</label>
                    <select class="form-select" id="student_sort" name="sort">
                        <?php foreach ($sortLabels as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $sort === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-navy">Apply</button>
                </div>
            </form>
            <?php if ($search !== ''): ?>
                <div class="mt-3">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url($queryBase(['q' => '', 'page' => '1']))) ?>">Clear search</a>
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
                        <h2 class="h5 text-navy">No students match</h2>
                        <p class="mb-3">Nothing matched “<?= e($search) ?>”.</p>
                        <a class="btn btn-outline-navy" href="<?= e(base_url('view_students.php')) ?>">Clear search</a>
                    <?php else: ?>
                        <h2 class="h5 text-navy">No students yet</h2>
                        <p class="mb-3">Add the first student to start the register.</p>
                        <a class="btn btn-navy" href="<?= e(base_url('add_student.php')) ?>">Add the first student</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="small text-muted mb-0" id="students_summary">
                        Showing page <?= e((string) $page) ?> of <?= e((string) $totalPages) ?>
                        (<?= e((string) $total) ?> student<?= $total === 1 ? '' : 's' ?><?= $search !== '' ? ' matching search' : '' ?>).
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 students-table" aria-describedby="students_summary">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Photo</th>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Address</th>
                                <th scope="col">Registered</th>
                                <th scope="col" class="text-end">Actions</th>
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
                                            <img src="<?= e($imgUrl) ?>" alt="Photo of <?= e($sname) ?>" class="student-avatar rounded">
                                        <?php else: ?>
                                            <div class="student-avatar student-avatar-placeholder rounded" aria-hidden="true"><?= e($initial) ?></div>
                                            <span class="visually-hidden">No photo for <?= e($sname) ?></span>
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
                                               href="<?= e(base_url('update_student.php?id=' . $sid)) ?>">Edit</a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteStudentModal"
                                                    data-student-id="<?= e((string) $sid) ?>"
                                                    data-student-name="<?= e($sname) ?>"
                                                    aria-label="Delete <?= e($sname) ?>">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3" aria-label="Student list pagination">
                        <ul class="pagination mb-0 flex-wrap">
                            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                <?php if ($page <= 1): ?>
                                    <span class="page-link" aria-disabled="true">Previous</span>
                                <?php else: ?>
                                    <a class="page-link" rel="prev" href="<?= e(base_url($queryBase(['page' => (string) ($page - 1)]))) ?>">Previous</a>
                                <?php endif; ?>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link" aria-current="page">Page <?= e((string) $page) ?> / <?= e((string) $totalPages) ?></span>
                            </li>
                            <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
                                <?php if ($page >= $totalPages): ?>
                                    <span class="page-link" aria-disabled="true">Next</span>
                                <?php else: ?>
                                    <a class="page-link" rel="next" href="<?= e(base_url($queryBase(['page' => (string) ($page + 1)]))) ?>">Next</a>
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
                    <h2 class="modal-title fs-5" id="deleteStudentModalLabel">Confirm deletion</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        Delete student <strong id="deleteStudentName"></strong>? This cannot be undone.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete student</button>
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
