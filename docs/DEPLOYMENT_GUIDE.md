# Deployment Guide — PHP/MySQL Shared Hosting

This guide prepares the BWP501 Student Registration System for typical shared hosting (InfinityFree preferred candidate).

| Milestone | Status |
|-----------|--------|
| Local development | **COMPLETE** |
| Production preparation (Phase 8.1) | **COMPLETE** — package in `deployment/` |
| Public deployment (Phase 8.2) | **PENDING** |
| Public URL | **PENDING** |
| Final report / Moodle ZIP | **PENDING** |

Local development uses Apache `:8080`, MariaDB `:3306`, and URL `/ab_hasan/`. Production will differ.

**Detailed Phase 8.1 / InfinityFree steps:** [`deployment/README_DEPLOYMENT.md`](../deployment/README_DEPLOYMENT.md)

---

## 1. Minimum hosting requirements

- PHP **8.0+** (project developed on **8.2.12**; InfinityFree advertises **8.4** — confirm on account)
- MySQL or MariaDB with InnoDB + `utf8mb4`
- Apache with `.htaccess` / `AllowOverride` **or** equivalent Nginx rules
- Ability to create a MySQL database and user
- HTTPS certificate (host-provided / Let’s Encrypt)

## 2. Required PHP extensions

- `pdo`, `pdo_mysql`
- `mbstring`
- `fileinfo`
- `session`, `json` (normally built-in)
- Image checks use `getimagesize()` and `finfo`

## 3. Staged package layout (Phase 8.1)

```text
deployment/
  public_html/     ← upload contents to host htdocs / public_html
  private/         ← SQL + templates; keep off the public web root
  README_DEPLOYMENT.md
```

Do **not** upload from the local project:

- `config/database.local.php` (local secrets)
- `tools/`
- `.git/`
- Assignment PDF
- Live SQL dumps with real accounts
- `docs/` (optional; sensitive audits stay private)

## 4. Creating a production database

In cPanel / InfinityFree client area / host panel:

1. Create database (name may be prefixed).
2. Note the panel **hostname** (often **not** `localhost` on InfinityFree).
3. Use the provided username/password (free hosts may not allow extra DB users).

## 5. Importing SQL with phpMyAdmin

1. Open phpMyAdmin → select the new database.
2. Import `deployment/private/student_registration_system.sql`.
3. If the host forbids `CREATE DATABASE`, remove `CREATE DATABASE` / `USE` lines and import into the selected DB.
4. Confirm tables `users` and `students` (schema only — no seed users).

## 6. Setting private database credentials

1. On the server, copy `config/database.local.php.PRODUCTION.example` → `config/database.local.php`.
2. Set host, port (usually `3306`), dbname, username, password from the panel.
3. Ensure `config/.htaccess` denies HTTP access.
4. Keep local PC `database.local.php` unchanged and unuploaded.

## 7. Production mode and base URL

- Staged `config/app.local.php` sets `APP_ENV=production` (hides PHP errors from visitors).
- `base_url()` uses `SCRIPT_NAME` — works at domain root or in a subdirectory.
- Do not hardcode the future public domain.
- Local `/ab_hasan/` continues to work from the original project tree.

## 8. HTTPS and sessions

1. Enable / confirm HTTPS.
2. With HTTPS, session cookies get the `Secure` flag automatically.
3. Optional HTTP→HTTPS redirect after the site works.

## 9. File upload permissions

- `uploads/students/` writable by PHP (prefer `755`/`775`; avoid `777`).
- Keep `uploads/.htaccess` (blocks script execution).
- Relative paths: `uploads/students/<random>.{jpg,png,webp}`.

## 10. Protecting private files

Verify **403** (or unreachable) for:

- `/config/database.local.php`
- `/includes/*.php` (direct URL)
- Any `*.sql` under the web root

`.htaccess` is the primary shared-hosting control. If ignored, relocate private dirs outside the docroot.

## 11–12. Post-upload testing (Phase 8.2)

Register/login, CRUD, image upload, search/sort/pagination, AJAX validation.  
Do **not** run local destructive CLI tests against production.

## 13. Common troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| White page / 500 | PHP error; bad credentials; bad `.htaccess` | Host error log; verify `database.local.php`; temporarily rename `.htaccess` |
| CSS missing | Wrong path | Confirm root vs subdirectory install |
| DB connection failed | Wrong host/port | Use panel hostname; port usually 3306 |
| Upload fails | Permissions / size limits | Writable uploads; raise upload limits if possible |
| Session lost | Cookie/HTTPS mismatch | Force HTTPS |

---

## What Phase 8.2–8.3 still own

- Creating the hosting account and public URL
- Live upload and connectivity proof
- Screenshots and Moodle report / ZIP
