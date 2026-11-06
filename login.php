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
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $errors['form'] = 'اسم المستخدم/البريد الإلكتروني أو كلمة المرور غير صحيحة.';
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare(
                'SELECT id, username, password FROM users
                 WHERE username = :username OR email = :email
                 LIMIT 1'
            );
            $stmt->execute([
                'username' => $identifier,
                'email' => $identifier,
            ]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, (string) $user['password'])) {
                $errors['form'] = 'اسم المستخدم/البريد الإلكتروني أو كلمة المرور غير صحيحة.';
            } else {
                login_user((int) $user['id'], (string) $user['username']);
                flash_set('success', 'أهلاً بعودتك، ' . (string) $user['username'] . '.');
                redirect_to('dashboard.php');
            }
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors['form'] = 'تعذّر تسجيل الدخول حالياً. يرجى المحاولة لاحقاً.';
        }
    }
}

$currentPage = 'login';
$pageTitle = 'تسجيل الدخول | نظام تسجيل الطلاب';
$bodyClass = 'page-auth app-public';

require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-shell">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="panel auth-panel">
                    <p class="auth-brand mb-0">تسجيل الطلاب</p>
                    <h1 class="h3 text-navy mb-2">تسجيل الدخول</h1>
                    <p class="text-muted mb-4">استخدم اسم المستخدم أو البريد الإلكتروني وكلمة المرور للمتابعة.</p>

                    <?php if (isset($errors['form'])): ?>
                        <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(base_url('login.php')) ?>" novalidate
                          aria-label="نموذج تسجيل الدخول" id="loginForm" data-submit-once>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="login_identifier" class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                            <input type="text"
                                   class="form-control<?= isset($errors['form']) ? ' is-invalid' : '' ?>"
                                   id="login_identifier" name="identifier"
                                   value="<?= e($identifier) ?>"
                                   autocomplete="username" required
                                   aria-describedby="<?= isset($errors['form']) ? 'login_error' : '' ?>">
                        </div>

                        <div class="mb-4">
                            <label for="login_password" class="form-label">كلمة المرور</label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control<?= isset($errors['form']) ? ' is-invalid' : '' ?>"
                                       id="login_password" name="password"
                                       autocomplete="current-password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        data-toggle-password="login_password"
                                        aria-pressed="false"
                                        aria-label="إظهار كلمة المرور">إظهار</button>
                            </div>
                        </div>

                        <?php if (isset($errors['form'])): ?>
                            <div id="login_error" class="visually-hidden"><?= e($errors['form']) ?></div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-navy w-100" data-loading-label="جارٍ تسجيل الدخول…">تسجيل الدخول</button>
                    </form>

                    <p class="small text-muted mt-3 mb-0">
                        ليس لديك حساب بعد؟
                        <a href="<?= e(base_url('register.php')) ?>">إنشاء حساب</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
