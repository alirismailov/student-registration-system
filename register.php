<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

init_session();
redirectIfAuthenticated();

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '') {
        $errors['username'] = 'اسم المستخدم مطلوب.';
    } elseif (mb_strlen($username) > 100) {
        $errors['username'] = 'يجب ألا يتجاوز اسم المستخدم 100 حرف.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
        $errors['username'] = 'يمكن أن يحتوي اسم المستخدم على أحرف إنجليزية وأرقام ونقاط وشرطات سفلية وشرطات فقط.';
    }

    if ($email === '') {
        $errors['email'] = 'البريد الإلكتروني مطلوب.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'يرجى إدخال بريد إلكتروني صحيح.';
    } elseif (mb_strlen($email) > 100) {
        $errors['email'] = 'يجب ألا يتجاوز البريد الإلكتروني 100 حرف.';
    }

    if ($password === '') {
        $errors['password'] = 'كلمة المرور مطلوبة.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.';
    }

    if ($passwordConfirm === '') {
        $errors['password_confirm'] = 'يرجى تأكيد كلمة المرور.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'تأكيد كلمة المرور غير مطابق.';
    }

    if ($errors === []) {
        try {
            $pdo = getPDO();

            $check = $pdo->prepare(
                'SELECT username, email FROM users
                 WHERE username = :username OR email = :email
                 LIMIT 1'
            );
            $check->execute([
                'username' => $username,
                'email' => $email,
            ]);
            $existing = $check->fetch();

            if ($existing) {
                if (strcasecmp((string) $existing['username'], $username) === 0) {
                    $errors['username'] = 'اسم المستخدم هذا مستخدم مسبقاً.';
                }
                if (strcasecmp((string) $existing['email'], $email) === 0) {
                    $errors['email'] = 'هذا البريد الإلكتروني مسجل مسبقاً.';
                }
            }

            if ($errors === []) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($hash === false) {
                    throw new RuntimeException('Unable to hash password.');
                }

                $insert = $pdo->prepare(
                    'INSERT INTO users (username, email, password)
                     VALUES (:username, :email, :password)'
                );
                $insert->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => $hash,
                ]);

                flash_set('success', 'تم إنشاء الحساب بنجاح. يرجى تسجيل الدخول.');
                redirect_to('login.php');
            }
        } catch (PDOException $e) {
            error_log('Registration PDO error: ' . $e->getMessage());
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '23000')) {
                $errors['form'] = 'اسم المستخدم أو البريد الإلكتروني مسجل مسبقاً.';
            } else {
                $errors['form'] = 'تعذّر إكمال التسجيل. يرجى المحاولة لاحقاً.';
            }
        } catch (Throwable $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['form'] = 'تعذّر إكمال التسجيل. يرجى المحاولة لاحقاً.';
        }
    }
}

$currentPage = 'register';
$pageTitle = 'إنشاء حساب | نظام تسجيل الطلاب';
$bodyClass = 'page-auth app-public';

require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-shell">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="panel auth-panel">
                    <p class="auth-brand mb-0">تسجيل الطلاب</p>
                    <h1 class="h3 text-navy mb-2">إنشاء حساب جديد</h1>
                    <p class="text-muted mb-4">سجّل باستخدام اسم مستخدم وبريد إلكتروني وكلمة مرور لإدارة الطلاب.</p>

                    <?php if (isset($errors['form'])): ?>
                        <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(base_url('register.php')) ?>" novalidate
                          aria-label="نموذج إنشاء الحساب" id="registerForm" data-submit-once>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="reg_username" class="form-label">اسم المستخدم <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control<?= isset($errors['username']) ? ' is-invalid' : '' ?>"
                                   id="reg_username" name="username" value="<?= e($username) ?>"
                                   autocomplete="username" maxlength="100" required
                                   aria-invalid="<?= isset($errors['username']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['username']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="reg_email" class="form-label">البريد الإلكتروني <span class="required-mark" aria-hidden="true">*</span></label>
                            <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                   id="reg_email" name="email" value="<?= e($email) ?>"
                                   autocomplete="email" maxlength="100" required
                                   aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="reg_password" class="form-label">كلمة المرور <span class="required-mark" aria-hidden="true">*</span></label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                                       id="reg_password" name="password" autocomplete="new-password"
                                       minlength="8" required
                                       aria-describedby="reg_password_help"
                                       aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>">
                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="reg_password"
                                        aria-pressed="false" aria-label="إظهار كلمة المرور">إظهار</button>
                            </div>
                            <div class="form-text" id="reg_password_help">8 أحرف على الأقل.</div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label for="reg_password_confirm" class="form-label">تأكيد كلمة المرور <span class="required-mark" aria-hidden="true">*</span></label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>"
                                       id="reg_password_confirm" name="password_confirm"
                                       autocomplete="new-password" minlength="8" required
                                       aria-invalid="<?= isset($errors['password_confirm']) ? 'true' : 'false' ?>">
                                <button class="btn btn-outline-secondary" type="button"
                                        data-toggle-password="reg_password_confirm"
                                        aria-pressed="false"
                                        aria-label="إظهار تأكيد كلمة المرور">إظهار</button>
                            </div>
                            <?php if (isset($errors['password_confirm'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['password_confirm']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-navy w-100" data-loading-label="جارٍ إنشاء الحساب…">إنشاء الحساب</button>
                    </form>

                    <p class="small text-muted mt-3 mb-0">
                        لديك حساب بالفعل؟
                        <a href="<?= e(base_url('login.php')) ?>">تسجيل الدخول</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
