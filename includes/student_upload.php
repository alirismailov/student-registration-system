<?php
/**
 * Safe student profile picture upload helpers (Phase 5).
 */

declare(strict_types=1);

const STUDENT_UPLOAD_DIR = 'uploads/students';
const STUDENT_UPLOAD_MAX_BYTES = 2097152; // 2 MB

/**
 * Allowed MIME → extension map.
 *
 * @return array<string, string>
 */
function student_upload_mime_map(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
}

/**
 * Absolute filesystem path to the students upload directory.
 */
function student_upload_absolute_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, STUDENT_UPLOAD_DIR);
}

/**
 * Whether a stored relative path is a managed local student upload.
 */
function is_managed_student_image(?string $relativePath): bool
{
    if ($relativePath === null || $relativePath === '') {
        return false;
    }

    $normalized = str_replace('\\', '/', $relativePath);
    if (!preg_match('#^uploads/students/[a-zA-Z0-9][a-zA-Z0-9._-]{0,180}\.(jpg|jpeg|png|webp)$#i', $normalized)) {
        return false;
    }

    $absDir = realpath(student_upload_absolute_dir());
    if ($absDir === false) {
        return false;
    }

    $absFile = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized));
    if ($absFile === false || !is_file($absFile)) {
        return false;
    }

    $absDir = str_replace('\\', '/', $absDir);
    $absFile = str_replace('\\', '/', $absFile);

    return str_starts_with($absFile, rtrim($absDir, '/') . '/');
}

/**
 * Public URL for a managed student image, or null.
 */
function student_image_url(?string $relativePath): ?string
{
    if (!is_managed_student_image($relativePath)) {
        return null;
    }

    require_once dirname(__DIR__) . '/config/app.php';

    return base_url(str_replace('\\', '/', (string) $relativePath));
}

/**
 * Process an optional uploaded profile picture.
 *
 * @param array<string, mixed>|null $file Typically $_FILES['profile_picture']
 * @return array{path: ?string, error: ?string}
 *         path = relative path when a new file was stored; null when no file uploaded
 */
function process_student_image_upload(?array $file): array
{
    if ($file === null || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    $errorCode = (int) $file['error'];
    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Image upload failed. Please try again.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['path' => null, 'error' => 'Invalid upload. Please try again.'];
    }

    if ($size <= 0 || $size > STUDENT_UPLOAD_MAX_BYTES) {
        return ['path' => null, 'error' => 'Image must be 2 MB or smaller.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $map = student_upload_mime_map();

    if (!isset($map[$mime])) {
        return ['path' => null, 'error' => 'Only JPEG, PNG, and WEBP images are allowed.'];
    }

    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) {
        return ['path' => null, 'error' => 'The uploaded file is not a valid image.'];
    }

    $detectedMime = (string) ($imageInfo['mime'] ?? '');
    if (!isset($map[$detectedMime]) || $detectedMime !== $mime) {
        return ['path' => null, 'error' => 'The uploaded file is not a valid image.'];
    }

    $ext = $map[$mime];
    $dir = student_upload_absolute_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['path' => null, 'error' => 'Unable to store the image right now.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $dir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $destination)) {
        return ['path' => null, 'error' => 'Unable to store the image right now.'];
    }

    @chmod($destination, 0644);

    return [
        'path' => STUDENT_UPLOAD_DIR . '/' . $filename,
        'error' => null,
    ];
}

/**
 * Delete a managed student image file if unused by other students.
 */
function delete_managed_student_image(PDO $pdo, ?string $relativePath, ?int $exceptStudentId = null): void
{
    if (!is_managed_student_image($relativePath)) {
        return;
    }

    $sql = 'SELECT COUNT(*) FROM students WHERE profile_picture = :path';
    $params = ['path' => $relativePath];
    if ($exceptStudentId !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $exceptStudentId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ((int) $stmt->fetchColumn() > 0) {
        return;
    }

    $abs = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $relativePath));
    if ($abs !== false && is_file($abs)) {
        @unlink($abs);
    }
}

/**
 * Remove a newly uploaded orphan file (not yet referenced in DB).
 */
function remove_orphan_upload(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $normalized = str_replace('\\', '/', $relativePath);
    if (!preg_match('#^uploads/students/[a-zA-Z0-9][a-zA-Z0-9._-]{0,180}\.(jpg|jpeg|png|webp)$#i', $normalized)) {
        return;
    }

    $absDir = realpath(student_upload_absolute_dir());
    $candidate = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    if ($absDir === false || !is_file($candidate)) {
        return;
    }

    $absFile = realpath($candidate);
    if ($absFile === false) {
        return;
    }

    $absDir = str_replace('\\', '/', $absDir);
    $absFile = str_replace('\\', '/', $absFile);
    if (str_starts_with($absFile, rtrim($absDir, '/') . '/')) {
        @unlink($absFile);
    }
}
