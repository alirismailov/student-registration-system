<?php
/**
 * Protect pages that require authentication.
 * Include this file before any HTML output.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

requireAuth();
