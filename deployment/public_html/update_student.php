<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/includes/student_validation.php';
require_once __DIR__ . '/includes/student_upload.php';

$pdo = getPDO();
$errors = [];

$id = parse_positive_int($_GET['id'] ?? ($_POST['id'] ?? null));

if ($id === null) {
    flash_set('danger', 'Invalid student ID.');
    redirect_to('view_students.php');
}

try {
    $student = find_student_by_id($pdo, $id);
} catch (Throwable $e) {
    error_log('Load student for update failed: ' . $e->getMessage());
    flash_set('danger', 'Unable to load the student. Please try again later.');
    redirect_to('view_students.php');
}

if ($student === null) {
    flash_set('warning', 'Student not found.');
    redirect_to('view_students.php');
}

$name = (string) $student['name'];
$email = (string) $student['email'];
$phone = (string) $student['phone'];
$address = (string) $student['address'];
$currentPicture = $student['profile_picture'] !== null ? (string) $student['profile_picture'] : null;
$currentImageUrl = student_image_url($currentPicture);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $postId = parse_positive_int($_POST['id'] ?? null);
    if ($postId === null || $postId !== $id) {
        flash_set('danger', 'Invalid student ID.');
        redirect_to('view_students.php');
    }

    $validated = validate_student_input($_POST);
    $name = $validated['data']['name'];
    $email = $validated['data']['email'];
    $phone = $validated['data']['phone'];
    $address = $validated['data']['address'];
    $errors = $validated['errors'];

    $upload = process_student_image_upload($_FILES['profile_picture'] ?? null);
    if ($upload['error'] !== null) {
        $errors['profile_picture'] = $upload['error'];
    }
    $newImagePath = $upload['path'];

    if ($errors === []) {
        $pictureToSave = $currentPicture;
        if ($newImagePath !== null) {
            $pictureToSave = $newImagePath;
        }

        try {
            $stmt = $pdo->prepare(
                'UPDATE students
                 SET name = :name, email = :email, phone = :phone, address = :address, profile_picture = :profile_picture
                 WHERE id = :id'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'profile_picture' => $pictureToSave,
                'id' => $id,
            ]);

            if ($newImagePath !== null && $currentPicture !== null && $currentPicture !== $newImagePath) {
                delete_managed_student_image($pdo, $currentPicture, $id);
            }

            flash_set('success', 'Student updated successfully.');
            redirect_to('view_students.php');
        } catch (Throwable $e) {
            error_log('Update student failed: ' . $e->getMessage());
            remove_orphan_upload($newImagePath);
            $errors['form'] = 'Unable to update the student. Please try again later.';
        }
    } else {
        remove_orphan_upload($newImagePath);
    }
}

$currentPage = 'update';
$pageTitle = 'Update Student | Student Registration System';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-shell">
    <div class="container">
        <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <p class="page-kicker">Students</p>
                <h1>Edit student</h1>
                <p class="page-desc">Updating <strong><?= e($name) ?></strong> (ID <?= e((string) $id) ?>). Leave the image empty to keep the current photo.</p>
            </div>
            <a class="btn btn-outline-navy" href="<?= e(base_url('view_students.php')) ?>">Back to list</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="panel panel-elevated">
                    <?php if (isset($errors['form'])): ?>
                        <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
                    <?php endif; ?>

                    <div id="studentFormLive" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

                    <form method="post" action="<?= e(base_url('update_student.php?id=' . $id)) ?>"
                          enctype="multipart/form-data" novalidate id="studentForm"
                          data-ajax-validate="<?= e(base_url('api/validate_student.php')) ?>"
                          data-submit-once aria-describedby="studentFormLive">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e((string) $id) ?>">

                        <div class="image-preview-wrap mb-4">
                            <?php if ($currentImageUrl !== null): ?>
                                <img src="<?= e($currentImageUrl) ?>" alt="Current profile picture for <?= e($name) ?>"
                                     class="student-avatar student-avatar-lg rounded">
                            <?php else: ?>
                                <div class="student-avatar student-avatar-lg student-avatar-placeholder rounded"
                                     aria-hidden="true"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></div>
                            <?php endif; ?>
                            <div class="image-preview-meta">
                                <p class="fw-semibold text-navy mb-1">Current photo</p>
                                <p class="small text-muted mb-0">
                                    <?= $currentImageUrl !== null
                                        ? 'Upload a new file below to replace it. Leaving the file empty keeps this image.'
                                        : 'No photo yet. You may upload one below.' ?>
                                </p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="student_name" class="form-label">Full name <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="text" id="student_name" name="name" maxlength="100" required
                                       class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                                       value="<?= e($name) ?>" data-validate-field="name"
                                       aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
                                <div class="invalid-feedback" data-error-for="name"><?= isset($errors['name']) ? e($errors['name']) : '' ?></div>
                            </div>

                            <div class="col-md-6">
                                <label for="student_email" class="form-label">Email <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="email" id="student_email" name="email" maxlength="100" required
                                       class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                       value="<?= e($email) ?>" data-validate-field="email"
                                       aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                                <div class="invalid-feedback" data-error-for="email"><?= isset($errors['email']) ? e($errors['email']) : '' ?></div>
                            </div>

                            <div class="col-md-6">
                                <label for="student_phone" class="form-label">Phone number <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="tel" id="student_phone" name="phone" maxlength="15" required
                                       class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                                       value="<?= e($phone) ?>" data-validate-field="phone"
                                       aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>">
                                <div class="invalid-feedback" data-error-for="phone"><?= isset($errors['phone']) ? e($errors['phone']) : '' ?></div>
                            </div>

                            <div class="col-12">
                                <label for="student_address" class="form-label">Address <span class="required-mark" aria-hidden="true">*</span></label>
                                <textarea id="student_address" name="address" rows="3" required
                                          class="form-control<?= isset($errors['address']) ? ' is-invalid' : '' ?>"
                                          data-validate-field="address"
                                          aria-invalid="<?= isset($errors['address']) ? 'true' : 'false' ?>"><?= e($address) ?></textarea>
                                <div class="invalid-feedback" data-error-for="address"><?= isset($errors['address']) ? e($errors['address']) : '' ?></div>
                            </div>

                            <div class="col-12">
                                <label for="student_picture" class="form-label">Replace profile picture</label>
                                <div class="image-preview-wrap mb-2">
                                    <div class="image-preview-frame" aria-hidden="true">
                                        <img id="studentImagePreview" class="d-none" alt="">
                                    </div>
                                    <div class="image-preview-meta">
                                        <p class="small text-muted mb-0" id="studentImagePreviewHint">Leave empty to keep the current image. JPEG/PNG/WEBP, max 2&nbsp;MB.</p>
                                    </div>
                                </div>
                                <input type="file" id="student_picture" name="profile_picture"
                                       class="form-control<?= isset($errors['profile_picture']) ? ' is-invalid' : '' ?>"
                                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                       aria-describedby="studentImagePreviewHint">
                                <?php if (isset($errors['profile_picture'])): ?>
                                    <div class="invalid-feedback d-block"><?= e($errors['profile_picture']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions mt-4">
                            <button type="submit" class="btn btn-navy" data-loading-label="Saving…">Save changes</button>
                            <a class="btn btn-outline-secondary" href="<?= e(base_url('view_students.php')) ?>">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= e(base_url('assets/js/student_form.js')) ?>"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
