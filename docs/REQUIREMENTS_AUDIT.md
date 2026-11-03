# Requirements Traceability Audit — Phase 7

**Source:** `BWP501_F25_Project .pdf` (8 pages)  
**Workspace:** `C:\Users\LOQ\OneDrive\Desktop\ab_hasan`  
**Date:** 2026-10-28  

Status legend: **PASS** | **FAIL** | **PARTIAL** | **NOT TESTED** | **NOT YET APPLICABLE**

---

## Summary

| Area | Result |
|------|--------|
| Authentication | **PASS** |
| Student CRUD | **PASS** |
| Advanced features (upload, search, sort, pagination, AJAX, Bootstrap) | **PASS** |
| Database schema + PDO | **PASS** |
| Deliverable: code + SQL + README | **PASS** |
| Deliverable: screenshots, public hosting URL, Moodle final report | **NOT YET APPLICABLE** (Phase 8) |

No assignment-required feature was found **FAIL** during this audit.

---

## Traceability matrix

| Requirement | Source Page | Implementation | Test Evidence | Status |
|-------------|-------------|----------------|---------------|--------|
| Dynamic Student Registration System with CRUD + advanced features | p.2 | Full PHP app under project root | Phases 3–6 + Phase 7 regressions | **PASS** |
| User registration (username, email, password) | p.2 | `register.php` + `users` insert | Phase 3 suite; browser Phase 6/7 | **PASS** |
| Login with username **or** email + password | p.2, p.4 | `login.php` PDO `username = :u OR email = :e` | Phase 3; browser login | **PASS** |
| Logout ends session | p.2, p.5 | `logout.php` + `logout_user()` | Phase 3; browser logout | **PASS** |
| `password_hash()` / `password_verify()` | p.2 | `register.php`, `login.php` | Phase 3 | **PASS** |
| PHP sessions secure pages | p.2, p.5 | `includes/session.php`, `auth_guard.php`, `requireAuth()` | Phase 3–5; protected redirect after logout | **PASS** |
| Add student: name, email, phone, address, optional picture | p.2–3, p.4 | `add_student.php` + upload helper | Phase 4–5; browser add | **PASS** |
| View students with sort, search, pagination | p.2–3, p.4–5 | `view_students.php` + `student_query.php` | Phase 5; browser Phase 7 pagination | **PASS** |
| Update student fields | p.2–3, p.5 | `update_student.php` | Phase 4–5; browser edit Phase 7 | **PASS** |
| Delete with confirmation | p.3, p.5 | Modal + `delete_student.php` POST/CSRF | Phase 4; browser delete Phase 6 | **PASS** |
| Profile picture upload; store file on server; path in DB | p.3, p.5–6 | `process_student_image_upload()`, `move_uploaded_file()`, `students.profile_picture` | Phase 5; Phase 7 HTTP upload/replace | **PASS** |
| Search by name, email, or phone (`LIKE`) | p.3, p.6 | `student_search_where()` prepared LIKE | Phase 5; browser search | **PASS** |
| Pagination ~10 per page, Prev/Next | p.3, p.5–6 | `STUDENTS_PER_PAGE=10`, LIMIT/OFFSET, UI links | Phase 5; browser Phase 7 (12 records → 10 + 2) | **PASS** |
| AJAX validation Add Student (empty/invalid email, no reload) | p.3, p.4, p.6–7 | `api/validate_student.php` + `assets/js/student_form.js` | Phase 5 JSON; browser Phase 6 AJAX | **PASS** |
| Responsive Bootstrap 5 grid/components | p.3, p.6 | Bootstrap 5.3 CDN + `style.css` / `students.css` | Phase 6 UI; browser pages render | **PASS** |
| DB `student_registration_system` | p.3 | MariaDB DB + SQL dump | `check_database.php` **PASS** | **PASS** |
| Table `students` fields as specified | p.3–4 | `database/student_registration_system.sql` | Schema check columns | **PASS** |
| Table `users` fields as specified | p.4 | Same SQL + live DB | Schema check columns | **PASS** |
| Homepage welcome + nav links | p.4 | `index.php` (PHP equivalent of index.html) | HTTP 200; browser home | **PASS** |
| Login / Register pages with errors & password confirm | p.4 | `login.php`, `register.php` | Phase 3; browser | **PASS** |
| Add Student AJAX real-time validation | p.4 | `data-ajax-validate` on form | Browser invalid email inline | **PASS** |
| View: search, pagination, edit/delete | p.4–5 | `view_students.php` | Browser Phase 6–7 | **PASS** |
| Update: prefill + optional picture replace | p.5 | `update_student.php` | Browser prefills; HTTP replace | **PASS** |
| Backend register/login/logout/CRUD PHP scripts | p.5 | Matching filenames | Suites + browser | **PASS** |
| PDO or MySQLi | p.5 | PDO in `config/database.php` | All CRUD tests | **PASS** |
| `move_uploaded_file()` for pictures | p.6 | `student_upload.php` | Phase 5 upload tests | **PASS** |
| Website files HTML/CSS/PHP/JS | p.6 | Project tree | Present | **PASS** |
| SQL file for schema | p.6 | `database/student_registration_system.sql` | File reviewed (no live passwords) | **PASS** |
| README: DB setup, local run, usage | p.6–7 | `README.md` + `docs/DATABASE_SETUP.md` | Present | **PASS** |
| Screenshots of CRUD flows | p.7 | — | Reserved for Phase 8 packaging | **NOT YET APPLICABLE** |
| Brief code explanation of features | p.7 | README / PROJECT_PLAN / this audit | Partial in docs; full Moodle report = Phase 8 | **PARTIAL** |
| Public web hosting URL in report | p.1, p.8 | — | Not deployed (Phase 7 forbids public deploy) | **NOT YET APPLICABLE** |
| Moodle single-team submission package | p.1, p.8 | — | Phase 8 | **NOT YET APPLICABLE** |
| Security best practices (hashing, SQLi prevention) | p.7 | Hashing + prepared statements + CSRF + escapes | Suites + security audit | **PASS** |

---

## Notes on interpretation

1. Assignment names some pages `.html`; the implementation correctly uses `.php` for dynamic pages while preserving required behaviour.
2. “Client-side and server-side” pagination is satisfied by SQL `LIMIT`/`OFFSET` plus client-rendered Previous/Next controls (not JS-only slicing of a full dump).
3. Sorting is implemented (allowlisted) even though the PDF emphasises search/pagination more than sort labels; the view requirement explicitly mentions sorting.

---

## Evidence index

- Automated: `tools/check_database.php`, `tools/test_auth_phase3.php`, `tools/test_students_phase4.php`, `tools/test_students_phase5.php`
- Browser: `docs/MANUAL_QA_CHECKLIST.md` (Phases 6–7)
- Security: `docs/SECURITY_AUDIT.md`
