<?php
/**
 * Production runtime flags for the staged deployment package.
 * Contains no database credentials.
 */
declare(strict_types=1);

if (!defined('APP_ENV')) {
    define('APP_ENV', 'production');
}