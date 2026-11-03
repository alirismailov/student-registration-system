<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/includes/student_validation.php';
require_once __DIR__ . '/includes/student_upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    flash_set('warning', 'Students can only be deleted using the Delete confirmation form.');
    redirect_to('view_students.php');
}

require_valid_csrf();

$id = parse_positive_int($_POST['id'] ?? null);
if ($id === null) {
    flash_set('danger', 'Invalid student ID.');
    redirect_to('view_students.php');
}

try {
    $pdo = getPDO();
    $student = find_student_by_id($pdo, $id);

    if ($student === null) {
        flash_set('warning', 'Student not found or already deleted.');
        redirect_to('view_students.php');
    }

    $picture = $student['profile_picture'] !== null ? (string) $student['profile_picture'] : null;

    $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
    $stmt->execute(['id' => $id]);

    delete_managed_student_image($pdo, $picture, null);

    flash_set('success', 'Student deleted successfully.');
    redirect_to('view_students.php');
} catch (Throwable $e) {
    error_log('Delete student failed: ' . $e->getMessage());
    flash_set('danger', 'Unable to delete the student. Please try again later.');
    redirect_to('view_students.php');
}
