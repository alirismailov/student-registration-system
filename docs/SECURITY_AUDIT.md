# Security Audit — Phase 7

**Application:** BWP501 Student Registration System  
**Date:** 2026-10-28  

---

## 1. Scope

Reviewed authentication, CSRF, SQL access, XSS output, sessions, uploads, redirects, configuration exposure, HTTP method handling, and Apache `.htaccess` controls.

---

## 2. Checks and findings

| Check | Result | Evidence / notes |
|-------|--------|------------------|
| SQL injection | **PASS** | PDO prepared statements; ORDER BY from allowlist only (`student_query.php`) |
| XSS | **PASS** | `e()` / `htmlspecialchars` on output; attributes escaped |
| CSRF | **PASS** | `csrf_field()` / `require_valid_csrf()` on mutating POSTs; AJAX validate requires CSRF |
| Session fixation | **PASS** | `session_regenerate_id(true)` in `login_user()` |
| Session cookie flags | **PASS** | `HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS |
| Authentication bypass | **PASS** | `auth_guard` / `requireAuth()`; user re-checked in DB |
| Unsafe redirects | **PASS** | `redirect_to()` + `base_url()` builds app-relative URLs |
| Unrestricted uploads | **PASS** | finfo MIME, `getimagesize`, size ≤ 2 MB, extension allowlist, random names |
| PHP execution in uploads | **PASS** | `uploads/.htaccess` denies script handlers / PHP extensions |
| Path traversal (images) | **PASS** | `is_managed_student_image()` regex + realpath containment |
| Arbitrary file deletion | **PASS** | Deletes only managed upload paths not referenced by others |
| Config / SQL / tools exposure | **PASS** (local Apache) | HTTP probe: `config/*`, `database/*.sql`, `tools/*`, `includes/*`, `docs/*` → **403** |
| Exposed credentials in repo | **PASS** | `.gitignore` excludes `database.local.php`; SQL has no passwords/accounts |
| Debug / stack traces to clients | **PASS** (code path) | DB errors logged; user-facing generic messages; `APP_ENV=production` disables `display_errors` |
| Unsafe HTTP methods | **PASS** | Delete requires POST; AJAX validate POST-only (405 otherwise) |

### Narrow fixes applied in Phase 7

| Fix | Why |
|-----|-----|
| Added `includes/.htaccess`, `docs/.htaccess` | Deny direct web access to helpers and internal docs |
| Added root `.htaccess` (`Options -Indexes`, block sensitive basenames) | Extra shared-hosting hardening |
| `APP_ENV=production` display_errors off in `config/app.php` | Reduce information disclosure on hosts |
| Clarified `database.local.php.example` ports | Avoid wrong production port assumptions |

### Image replacement cleanup

Authenticated HTTP test confirmed previous managed image is removed after successful replacement (`before_still_exists=n`). An earlier false positive was caused by checking too quickly / path timing; retest passed.

---

## 3. Remaining risks

1. **Host without `AllowOverride`** — `.htaccess` rules may not apply; prefer placing credentials outside the web root on production.
2. **Session cookie `path=/`** — works for subdirectory apps; more scoped path optional later.
3. **Shared student table** — any authenticated user can manage all students (assignment model; no roles).
4. **Local `display_errors`** — may still be on via `php.ini` when `APP_ENV=local`.
5. **Native browser file-picker automation** — not executable via denied CDP method; upload security still covered by HTTP suite.

---

## 4. Production recommendations

1. Set strong unique DB user/password; never use empty root password.
2. Set `APP_ENV=production` (environment variable) on the host.
3. Force HTTPS; confirm Secure session cookies activate.
4. Ensure `uploads/students` is writable by the web user only as needed.
5. Confirm Apache/Nginx blocks `config/`, `database/`, `tools/`, `includes/`, `docs/`.
6. Keep `database.local.php` unreadable from the web (and ideally outside docroot).
7. Do not commit production credentials or live SQL dumps with user rows.

---

## 5. Verdict

**No critical vulnerabilities found that block deployment preparation.**  
Access controls for private paths verified on the local Apache instance. Remaining items are host-dependent configuration steps for Phase 8.
