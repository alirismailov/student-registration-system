# Student Registration System

BWP501 — Syrian Virtual University.

PHP/MySQL student administration app: authentication, CRUD, profile images, search, sorting, pagination, AJAX validation, and a Bootstrap 5 UI.

## Stack

HTML5, CSS3, Vanilla JS, Bootstrap 5.3, PHP 8.x, MariaDB/MySQL, PDO.

## Status

| Item | Status |
|------|--------|
| Local development (Phases 1–7) | **COMPLETE** |
| Production preparation (Phase 8.1) | **COMPLETE** — see `deployment/` |
| Public deployment (Phase 8.2) | **PENDING** |
| Public URL | **PENDING** |
| Final report | **PENDING** |
| Moodle submission | **PENDING** |

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

## Production staging (Phase 8.1)

Safe deployable copy (no local secrets uploaded yet):

- `deployment/public_html/` — files to upload to host `htdocs` / `public_html`
- `deployment/private/` — SQL schema + credential template (keep off the public web root)
- `deployment/README_DEPLOYMENT.md` — step-by-step InfinityFree-oriented instructions

Do **not** upload local `config/database.local.php`, `tools/`, `.git/`, or the assignment PDF.

## Tests

```bat
C:\xampp\php\php.exe tools\check_database.php
C:\xampp\php\php.exe tools\test_auth_phase3.php
C:\xampp\php\php.exe tools\test_students_phase4.php
C:\xampp\php\php.exe tools\test_students_phase5.php
```

## Documentation

- [`docs/PROJECT_PLAN.md`](docs/PROJECT_PLAN.md)
- [`docs/DATABASE_SETUP.md`](docs/DATABASE_SETUP.md)
- [`docs/REQUIREMENTS_AUDIT.md`](docs/REQUIREMENTS_AUDIT.md)
- [`docs/SECURITY_AUDIT.md`](docs/SECURITY_AUDIT.md)
- [`docs/DEPLOYMENT_GUIDE.md`](docs/DEPLOYMENT_GUIDE.md)
- [`deployment/README_DEPLOYMENT.md`](deployment/README_DEPLOYMENT.md)
- [`docs/MANUAL_QA_CHECKLIST.md`](docs/MANUAL_QA_CHECKLIST.md)
