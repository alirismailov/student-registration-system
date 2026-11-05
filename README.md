# Student Registration System

BWP501 — Syrian Virtual University.

PHP/MySQL student administration app: authentication, CRUD, profile images, search, sorting, pagination, AJAX validation, and a Bootstrap 5 UI.

 **Live Demo:** https://bwp501-hw-studentregsystem.infinityfreeapp.com/

## Stack

HTML5, CSS3, Vanilla JS, Bootstrap 5.3, PHP 8.x, MariaDB/MySQL, PDO.

## Status

| Item | Status |
|------|--------|
| Local development | **COMPLETE** |
| Public deployment | **COMPLETE** |
| Public URL | **[LIVE](https://bwp501-hw-studentregsystem.infinityfreeapp.com/)** |
| Final report | **COMPLETE** |
| Moodle submission | **COMPLETE** |

## Local runtime

- Site: http://127.0.0.1:8080/ab_hasan/
- DB: XAMPP MariaDB **127.0.0.1:3306** (see `config/database.local.php`)
- Start DB: `tools\start_xampp_mariadb_3306.bat`
- Health: `C:\xampp\php\php.exe tools\check_database.php`

## Features

- Register / login (username or email) / logout, hashing, sessions, CSRF
- Student add / view / update / delete (confirmation modal)
- Optional JPEG/PNG/WEBP profile pictures (≤ 2 MB) under `uploads/students/`
- Search, allowlisted sorting, 10-per-page SQL pagination
- AJAX validation via `api/validate_student.php`

## Setup (local)

1. Import `database/student_registration_system.sql` (or use existing DB).
2. Copy `config/database.local.php.example` → `config/database.local.php` and set credentials.
3. Ensure Apache can serve the project (e.g. junction under `htdocs/ab_hasan`).
4. Open the site URL above and register an account.


