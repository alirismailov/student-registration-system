<?php
/**
 * Shared student field validation (Phase 4).
 */

declare(strict_types=1);

/**
 * Normalize and validate student form fields.
 *
 * @param array<string, mixed> $input
 * @return array{data: array{name: string, email: string, phone: string, address: string}, errors: array<string, string>}
 */
function validate_student_input(array $input): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    $address = trim((string) ($input['address'] ?? ''));

    // Collapse internal whitespace in phone for storage consistency (keep + and digits/spaces/dashes/parens).
    $phoneNormalized = preg_replace('/\s+/u', ' ', $phone) ?? $phone;

    $errors = [];

    if ($name === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'Full name must be at most 100 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 100) {
        $errors['email'] = 'Email must be at most 100 characters.';
    }

    if ($phoneNormalized === '') {
        $errors['phone'] = 'Phone number is required.';
    } elseif (mb_strlen($phoneNormalized) > 15) {
        $errors['phone'] = 'Phone number must be at most 15 characters.';
    } elseif (!preg_match('/^\+?[0-9][0-9\s\-()]{5,14}$/', $phoneNormalized)) {
        $errors['phone'] = 'Enter a valid phone number (digits, spaces, +, -, or parentheses).';
    }

    if ($address === '') {
        $errors['address'] = 'Address is required.';
    } elseif (mb_strlen($address) > 5000) {
        $errors['address'] = 'Address is too long.';
    }

    return [
        'data' => [
            'name' => $name,
            'email' => $email,
            'phone' => $phoneNormalized,
            'address' => $address,
        ],
        'errors' => $errors,
    ];
}

/**
 * Parse a positive integer ID from mixed input.
 */
function parse_positive_int(mixed $value): ?int
{
    if (is_int($value) && $value > 0) {
        return $value;
    }

    if (is_string($value) && ctype_digit($value)) {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    return null;
}

/**
 * Fetch a student by ID or return null.
 *
 * @return array<string, mixed>|null
 */
function find_student_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, email, phone, address, profile_picture, created_at
         FROM students
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}
