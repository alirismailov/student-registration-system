# Database Setup Guide (Windows / XAMPP)

BWP501 Student Registration System — Phase 2.2

## Active setup on this machine

| Item | Value |
|------|--------|
| Project DB server | **XAMPP MariaDB 10.4.32** |
| Bind / port | **127.0.0.1:3306** (localhost only) |
| Database | `student_registration_system` |
| App user | `bwp501_app` (limited privileges; password only in private local config) |
| Config file for instance | `C:\xampp\mysql\bin\my-bwp501-3306.ini` |
| Original XAMPP `my.ini` | Unchanged (backup: `my.ini.bwp501.bak`) |
| Data directory | Existing `C:\xampp\mysql\data` (not re-initialized; other DBs preserved) |
| Other MySQL | **MySQL Server 9.6** service `MySQL96` on **3306** — left running, unmodified |
| PDO config | `config/database.local.php` (gitignored) |
| Health check | `tools/check_database.php` → **PASS** (verified Phase 2.2) |
| Website | `http://127.0.0.1:8080/ab_hasan/` |

**Never document or commit database passwords.**

---

## Why port 3306?

Port **3306** is occupied by MySQL 9.6 (`MySQL96`). The MySQL96 root password is unknown and must not be reset for this assignment.

XAMPP’s MariaDB was started as a **separate** instance on **3306** using a dedicated defaults file, without stopping MySQL96.

---

## Start MariaDB on 3306 (after reboot)

```bat
cd C:\Users\LOQ\OneDrive\Desktop\ab_hasan
tools\start_xampp_mariadb_3306.bat
C:\xampp\php\php.exe tools\check_database.php
```

Expect `Result: PASS` and exit code `0`.

Manual equivalent:

```bat
start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:/xampp/mysql/bin/my-bwp501-3306.ini
```

Confirm listener: port **3306** on **127.0.0.1** only. Port **3306** should still belong to MySQL96.

---

## Schema

File: `database/student_registration_system.sql`

Tables (InnoDB, utf8mb4):

- `users` — id, username, email, password, created_at  
- `students` — id, name, email, phone, address, profile_picture, created_at  

Import uses `CREATE TABLE IF NOT EXISTS` (non-destructive). Existing XAMPP databases such as `craftores_db` and `real_estate_db` were left intact.

---

## PDO configuration

`config/database.php` loads defaults, then merges `config/database.local.php` when present.

Local project settings (non-secret):

- host: `127.0.0.1`
- port: `3306`
- dbname: `student_registration_system`
- username: `bwp501_app`
- charset: `utf8mb4`

Password is stored only in `database.local.php`.

---

## Apache / site

- Apache listens on **8080**
- Project junction: `C:\xampp\htdocs\ab_hasan` → Desktop project folder
- URL: **http://127.0.0.1:8080/ab_hasan/**

---

## Troubleshooting

### Health check fails after reboot

Start MariaDB 3306 with `tools\start_xampp_mariadb_3306.bat`, then re-run the health check.

### Connection to port 3306

Do not point this project at MySQL96 unless you intentionally configure it. Project PDO uses **3306**.

### phpMyAdmin

Default XAMPP phpMyAdmin may still expect MariaDB on 3306. For this project, prefer the CLI health check or configure phpMyAdmin’s host/port to `127.0.0.1:3306` only if you know how.

### Do not

- Stop or reconfigure MySQL96 for this course project  
- Re-initialize `C:\xampp\mysql\data`  
- Use `--skip-grant-tables`  
- Commit `config/database.local.php`

---

## Out of scope (later phases)

- Authentication / CRUD application logic  
- Sample users or students  
