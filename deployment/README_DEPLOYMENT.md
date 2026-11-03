# Phase 8.1 — Deployment Package (Student Registration System)

**Status:** Staging package prepared. **Public upload not performed.**  
**Preferred host candidate:** InfinityFree (PHP/MySQL shared hosting).  
**Do not upload until Phase 8.2.**

This folder is a **generated copy**. The original project under `ab_hasan/` is unchanged for local development (`/ab_hasan/`, MariaDB `:3306`).

---

## Already completed (Phase 8.1)

- [x] Hosting compatibility assessment (docs + public InfinityFree feature list)
- [x] Production config templates (no real secrets)
- [x] `deployment/public_html/` application copy
- [x] `deployment/private/` SQL + private docs
- [x] Exclusion of local secrets, tools, tests, PDF, `.git`
- [x] Upload directory + script-blocking `.htaccess`
- [x] Production `APP_ENV` flag in staged `config/app.local.php`
- [ ] Live hosting account / subdomain / HTTPS (user — Phase 8.2)
- [ ] MySQL create + SQL import on host (user — Phase 8.2)
- [ ] FTP/File Manager upload (user — Phase 8.2)
- [ ] Public URL verification (user — Phase 8.2)

---

## 1. Expected hosting requirements

| Requirement | Notes |
|-------------|--------|
| PHP **8.0+** | Developed on **8.2.12**. InfinityFree advertises **PHP 8.4** — compatible assumption; confirm in panel. |
| Extensions | `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `session`, `json` |
| Image APIs | `getimagesize()`, `finfo` / fileinfo, `move_uploaded_file()` |
| Database | MySQL **8** / MariaDB with InnoDB + `utf8mb4` |
| Web server | Apache with `.htaccess` (`AllowOverride`) preferred |
| HTTPS | Required for secure session cookies |
| Uploads | Writable `uploads/students/` (prefer `755`/`775`, **not** `777`) |

**InfinityFree (from public docs — verify on your account):**

- PHP 8.4, MySQL 8.0 / MariaDB 11.4, PDO/MySQLi, full `.htaccess`, free subdomain + SSL
- Free subdomains: HTTPS often enabled by default (confirm in panel)
- **DB host is often NOT `localhost`** — copy exact hostname from MySQL Databases
- Document root is typically `htdocs/` (flat upload of `public_html/*` contents)
- Invalid `.htaccess` rules can cause HTTP 500 — if so, temporarily rename `.htaccess` and check error logs

**Assumptions still requiring host verification:** exact PHP build/extensions, `upload_max_filesize` / `post_max_size`, whether parent-path includes outside `htdocs` are allowed (this package keeps includes inside the public tree for shared-hosting safety).

---

## 2. Public and private file layout

```text
deployment/
  README_DEPLOYMENT.md          ← this file
  public_html/                  ← upload these files into host htdocs / public_html
    *.php                       ← entry points
    api/
    assets/
    config/                     ← no real passwords until YOU create database.local.php on host
    includes/
    uploads/students/           ← empty placeholder (.gitkeep)
    .htaccess
  private/                      ← DO NOT put inside public document root
    student_registration_system.sql
    database.local.php.PRODUCTION.example
    DEPLOYMENT_GUIDE.md
    SECURITY_AUDIT.md
    MANIFEST.txt
```

**On InfinityFree:** upload the *contents* of `public_html/` into `htdocs/` (not necessarily a nested `public_html` folder). Keep `private/` on your PC (or a non-web folder) for SQL import only.

**Outside-docroot includes:** not used in this package. All PHP includes stay under the web tree and are blocked from direct HTTP via `config/.htaccess` and `includes/.htaccess`. If a future host forbids `.htaccess`, relocate those dirs outside the web root and adjust require paths.

---

## 3. Creating a hosting account (user — Phase 8.2)

1. Sign up at the host (e.g. InfinityFree) with an email you control.
2. Create a hosting account / website.
3. Do **not** reuse local XAMPP passwords as hosting passwords.

---

## 4. Creating a free subdomain (user — Phase 8.2)

1. In the client area, create or note the free subdomain (e.g. `yoursite.infinityfreeapp.com`).
2. Confirm the document root path (`htdocs`).
3. Prefer non-`www` URLs on free InfinityFree subdomains if the host documents that restriction.

---

## 5. Enabling HTTPS (user — Phase 8.2)

1. Free InfinityFree subdomains: confirm SSL is already active; open `https://…`.
2. Custom domains: request Let’s Encrypt (or host SSL) and wait until HTTPS works.
3. Optionally force HTTP→HTTPS via host UI or `.htaccess` rewrite after uploads work.
4. Session cookies set `Secure` automatically when the request is HTTPS (`includes/session.php`).

---

## 6. Creating a MySQL database (user — Phase 8.2)

1. Open **MySQL Databases** in the control panel.
2. Create a database (name may be prefixed, e.g. `if0_XXXX_srs`).
3. Note **hostname**, **database name**, **username**, and **password** exactly as shown.
4. Do **not** assume `localhost` or port `3306`.

---

## 7. Importing SQL through phpMyAdmin (user — Phase 8.2)

1. Open phpMyAdmin → select the new empty database.
2. Import `deployment/private/student_registration_system.sql`.
3. If the host forbids `CREATE DATABASE` / `USE`, delete those two statements and import into the selected DB.
4. Confirm tables `users` and `students` exist (schema only — no seed accounts).

---

## 8. Configuring database credentials securely (user — Phase 8.2)

1. Copy `config/database.local.php.PRODUCTION.example` → `config/database.local.php` **on the server**.
2. Fill host, port (`3306` typical), dbname, username, password from the panel.
3. Confirm `config/.htaccess` is present (denies HTTP).
4. Never commit or paste real passwords into Moodle docs or chat.
5. Local `config/database.local.php` on your PC stays unchanged and must **not** be uploaded.

---

## 9. Uploading files through FTP / File Manager (user — Phase 8.2)

1. Upload **contents** of `deployment/public_html/` → host `htdocs/` (or `public_html/`).
2. Enable “show hidden files” so all `.htaccess` files upload.
3. Do **not** upload: `tools/`, `docs/`, `.git/`, assignment PDF, local `database.local.php`, SQL dumps with real data, `deployment/private/` into the web root.
4. After upload, create `database.local.php` on the server (step 8).

---

## 10. Setting production mode

Staged package already includes `config/app.local.php`:

```php
define('APP_ENV', 'production');
```

This disables visitor-facing PHP error display. Keep that file on the host. Technical errors still go to the host’s PHP error log when logging is enabled by the provider.

---

## 11. Testing PHP execution (user — Phase 8.2)

1. Visit `https://YOUR-DOMAIN/` (or subdirectory path).
2. Expect login/home page HTML (not raw PHP source).
3. If you see download/source, PHP is not enabled — contact host support.

---

## 12. Testing database connectivity safely (user — Phase 8.2)

1. Open Register and create a throwaway account.
2. Success ⇒ DB connectivity works.
3. Failure / 500 ⇒ check host error log and `database.local.php` values (especially **hostname**).
4. Do **not** upload local CLI test scripts (`tools/*.php`) to production.

---

## 13. Testing authentication (user — Phase 8.2)

1. Register → logout → login with username and with email.
2. Open Dashboard while logged out → must redirect to login.
3. Logout clears the session.

---

## 14. Testing CRUD (user — Phase 8.2)

1. Add student → appear in list.
2. Edit fields → persist.
3. Delete with confirmation → removed.
4. Search / sort / pagination if you create enough rows.

---

## 15. Testing profile image uploads (user — Phase 8.2)

1. Ensure `uploads/students/` is writable.
2. Upload JPEG/PNG/WEBP ≤ 2 MB.
3. Confirm image displays; replace image; confirm old managed file is removed.
4. Confirm `.php` cannot execute under `uploads/` (upload a non-image should be rejected by app validation).

---

## 16. Checking private-file access restrictions (user — Phase 8.2)

In a logged-out browser, request (expect **403** or not found):

- `/config/database.local.php`
- `/config/app.local.php`
- `/includes/auth.php`
- Any `*.sql` if accidentally placed under the web root

If `.htaccess` is ignored, move `config/` and `includes/` outside the web root or ask the host how to deny paths.

---

## 17. Troubleshooting 403 / 404 / 500

| Code | Likely cause | Action |
|------|----------------|--------|
| 403 | Deny rules / permissions | Check `.htaccess`; fix directory perms |
| 404 | Wrong upload path / subdirectory | Confirm files are in `htdocs`; check URL path |
| 500 | PHP crash or bad `.htaccess` | Rename `.htaccess` temporarily; read error log; verify DB credentials |
| CSS missing | Wrong base path | Confirm install at root vs subdirectory; hard refresh |
| DB error | Wrong host (used `localhost`) | Use panel MySQL hostname |
| Upload fail | Permissions / size limits | Writable uploads dir; raise upload limits in panel if available |

---

## 18. Confirming the final site is publicly reachable (user — Phase 8.2)

1. Open the HTTPS URL from a different network or phone (not only localhost).
2. Login + add one student + upload one image.
3. Record the public URL for the university report (Phase 8.3).

---

## Production configuration strategy (summary)

| Setting | Strategy |
|---------|----------|
| DB host/port/name/user/password | `config/database.local.php` on server only (from PRODUCTION example) |
| Environment | `config/app.local.php` → `APP_ENV=production` |
| Base URL | Dynamic via `SCRIPT_NAME` — works at domain root or subdirectory |
| Sessions | HTTPS → `Secure` cookie; HttpOnly; SameSite=Lax |
| Errors | Hidden from visitors in production |
| Secrets | Never in repo, never in `public_html` templates as real values |

Local development continues to use the original project’s `database.local.php` and optional XAMPP `/ab_hasan` fallback in **source** `config/app.php` only. The **staged** `deployment/public_html/config/app.php` removes the XAMPP hard-coded path fallback.

---

## Next phase

**Phase 8.2:** Create hosting account, import SQL, upload `public_html` contents, configure `database.local.php`, verify public HTTPS URL.  
**Phase 8.3:** Screenshots, Moodle report, submission ZIP — not started.
