# Manual / Browser QA Checklist

Base URL: `http://127.0.0.1:8080/ab_hasan/`

Legend: **PASS** / **FAIL** / **NOT TESTED**

---

## Phase 6 browser session (primary workflows)

| # | Check | Result |
|---|--------|--------|
| 1 | Login page renders | **PASS** |
| 2 | Registration page renders | **PASS** |
| 3 | Valid registration works | **PASS** |
| 4 | Login works | **PASS** |
| 5 | Dashboard loads | **PASS** |
| 6 | Add Student page loads | **PASS** |
| 7 | AJAX validation sends requests | **PASS** |
| 8 | Invalid email inline feedback (no reload) | **PASS** |
| 9 | Corrected email clears error | **PASS** |
| 10 | Valid student submission succeeds | **PASS** |
| 11 | Student appears in the list | **PASS** |
| 12 | Search works | **PASS** |
| 13 | Sorting works | **PASS** |
| 14 | Pagination works | Deferred → Phase 7 |
| 15 | Edit Student works | Deferred → Phase 7 |
| 16 | Image upload / replacement | Deferred → Phase 7 |
| 17 | Delete confirmation appears | **PASS** |
| 18 | Confirmed deletion works | **PASS** |
| 19 | Logout works | **PASS** |
| 20 | Protected pages redirect after logout | **PASS** |

---

## Phase 7 remaining browser / HTTP verification

Temporary marker: `P7TEST_32f2a0*` (cleaned after tests).

### Pagination

| Check | Result | Evidence |
|-------|--------|----------|
| Seed ≥12 matching students | **PASS** | CLI seed inserted 12 rows |
| Page 1 shows 10 students | **PASS** | Browser: Delete buttons Student 01–10; “page 1 of 2 (12 students…)” |
| Page 2 shows remainder | **PASS** | Browser: Students 11–12; “page 2 of 2” |
| Next control works | **PASS** | Navigated to `page=2`; Previous returned to page 1 |
| Search param preserved | **PASS** | `q=P7TEST_32f2a0` retained on both pages |
| Sort param preserved | **PASS** | `sort=name_asc` retained |
| Total count correct | **PASS** | UI showed 12 matching |

### Edit flow

| Check | Result | Evidence |
|-------|--------|----------|
| Open Edit | **PASS** | `update_student.php?id=68` |
| Prefill values | **PASS** | Name/email/phone/address matched DB |
| Change details + submit | **PASS** | Name → `… Edited`, address → `… Updated` |
| List / DB persistence | **PASS** | MySQL row confirmed after redirect |

### Image upload / replacement

| Check | Result | Evidence |
|-------|--------|----------|
| Native browser file-picker automation | **NOT TESTED** | CDP `DOM.setFileInputFiles` denied by browser tool policy |
| Authenticated HTTP upload of valid JPEG | **PASS** | Stored `uploads/students/*.jpg`; file on disk |
| Image displays in UI | **PASS** | Edit page `<img class="student-avatar" src="/ab_hasan/uploads/students/…">` |
| Replacement upload (PNG) | **PASS** | New path in DB; image HTTP **200** |
| Previous managed file cleaned | **PASS** | Retest: `before_still_exists=n` after replace |

Manual optional: use the OS file dialog once on Add/Edit to confirm the same path end-users take.

---

## Security probes (Phase 7)

| URL | Expected | Actual |
|-----|----------|--------|
| `/ab_hasan/` | 200 | **200** |
| `/ab_hasan/config/database.local.php` | denied | **403** |
| `/ab_hasan/database/student_registration_system.sql` | denied | **403** |
| `/ab_hasan/tools/check_database.php` | denied | **403** |
| `/ab_hasan/includes/auth.php` | denied | **403** |
| `/ab_hasan/docs/PROJECT_PLAN.md` | denied | **403** |
