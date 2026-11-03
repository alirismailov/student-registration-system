<?php
declare(strict_types=1);
?>
</main>

<footer class="app-footer mt-auto">
    <div class="container py-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <p class="mb-1 fw-semibold text-navy"><?= e(APP_NAME) ?></p>
                <p class="mb-0 small text-muted">
                    BWP501 course project — Syrian Virtual University.
                    Secure authentication and student administration with search, pagination, and profile images.
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if (function_exists('isAuthenticated') && isAuthenticated()): ?>
                    <a class="footer-link me-3" href="<?= e(base_url('dashboard.php')) ?>">Dashboard</a>
                    <a class="footer-link me-3" href="<?= e(base_url('view_students.php')) ?>">Students</a>
                    <a class="footer-link" href="<?= e(base_url('add_student.php')) ?>">Add</a>
                <?php else: ?>
                    <a class="footer-link me-3" href="<?= e(base_url('login.php')) ?>">Login</a>
                    <a class="footer-link" href="<?= e(base_url('register.php')) ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>
        <p class="small text-muted mt-3 mb-0">
            &copy; <span data-current-year></span> Academic administration demo — assignment use only.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
