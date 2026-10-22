# Health Appointment System (HAMS2)

A web-based hospital appointment booking system built with plain **PHP + MySQL + vanilla HTML/CSS/JS** (no frameworks). Patients book appointments by department/doctor; admins manage users, departments, doctors, schedules, time slots, appointments and announcements.

> Final-year project. Runs locally on XAMPP. Timezone: East Africa Time (`Africa/Dar_es_Salaam`).

---

## How it works (architecture)

```
Browser (pages/*.html + js/main.js)
        │  fetch() / form POST  →  JSON
        ▼
PHP endpoints (php/*.php)  ── require config.php (DB + session) ──►  MySQL (hams2_db)
```

- **Frontend**: static HTML pages in `pages/`. Each page loads its data on open by calling a PHP endpoint with `fetch()` and rendering the JSON. Shared helpers live in `js/main.js`.
- **Backend**: one PHP file per action (e.g. `get_slots.php`, `book_appointment.php`). Every endpoint includes `php/config.php`, which defines DB constants, starts the session, configures error logging and creates the `$pdo` connection.
- **Auth**: session-based. `php/session_check.php` guards pages/endpoints and checks the user's role (`patient` / `admin`).
- **Login is by phone number + password** (it was originally email; see `php/apply_phone_login_migration.php`).
- **Caching**: simple file cache in `php/cache.php`, stored in `cache/`.
- **Audit**: admin actions are logged via `php/audit_log.php`.

## Features

**Patients**
- Register and log in (phone + password)
- Book an appointment: department → doctor → date/slot → confirm
- View/filter appointment history, cancel pending/confirmed ones (frees the slot)
- Manage family members and book on their behalf
- Edit profile, see announcements

**Admins**
- Dashboard with statistics and today's appointments
- Manage users, departments and doctors
- **Schedules**: weekly schedule templates, holidays and doctor exceptions; generate time slots from templates (`admin-schedules.html`)
- Manage time slots manually
- View and update appointment status
- Post/delete announcements
- View audit log

## Project structure

```
├── index.html            # Landing / entry page
├── css/                  # style.css, responsive.css
├── js/                   # main.js (shared helpers), responsive.js
├── images/
├── pages/                # All UI pages (patient + admin-*.html)
│   └── pages_xplanation.md   # Plain-language description of every page
├── php/
│   ├── config.php        # DB credentials, session, error log, $pdo  ← edit this
│   ├── session_check.php # Auth/role guard
│   ├── login.php / register.php / logout.php
│   ├── get_*.php, book_appointment.php, ...   # Patient endpoints
│   ├── admin_*.php                            # Admin endpoints
│   ├── apply_*_migration.php                  # One-off DB migrations (run in browser)
│   └── test_*.php, debug_*.php                # Dev-only test/debug scripts
├── cache/                # File cache (generated)
└── logs/php_errors.log   # PHP error log (generated)
```

Naming convention: `admin_` prefix = admin-only endpoint; `get_` = read (GET); `save_` / `update_` / `delete_` = write (POST).

## Database tables

`users`, `departments`, `doctors`, `time_slots`, `appointments`, `family_profiles`, `schedule_templates`, `holidays`, `doctor_exceptions`, `announcements`, `audit_log`, `login_attempts`.

> ⚠️ The SQL schema file is **not** included in this repo yet. Export it from phpMyAdmin (`hams2_db` → Export) and commit it as `hams2_database_schema.sql` so others can set up the project.

## Local setup

1. Install **XAMPP** (PHP 7.4+, MySQL/MariaDB) and start **Apache** and **MySQL**.
2. Clone into the web root:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Api-cmd/health-appointment-system.git hams2
   ```
3. Create the database `hams2_db` and import the schema (phpMyAdmin → Import, or):
   ```bash
   mysql -u root -p hams2_db < hams2_database_schema.sql
   ```
4. Run the migrations once in the browser (if your schema is older):
   - `http://localhost/hams2/php/apply_doctor_id_migration.php`
   - `http://localhost/hams2/php/apply_time_slots_migration.php`
   - `http://localhost/hams2/php/apply_phone_login_migration.php`
5. Check `php/config.php` — defaults are `root` / empty password, DB `hams2_db`, `APP_URL = http://localhost/hams2`.
6. Open `http://localhost/hams2/` and log in.

## Debugging tips

- PHP errors go to `logs/php_errors.log` (not the browser).
- Open the browser DevTools → Network tab to see the JSON each endpoint returns.
- `php/test_*.php` scripts can be opened directly in the browser to test DB queries.
- Clear `cache/` if data looks stale.

## Security

- Passwords hashed with bcrypt (`password_hash`)
- PDO prepared statements everywhere
- Login rate limiting by IP and phone (`login_attempts` table)
- HttpOnly session cookies, strict session mode, 2-hour lifetime

**Before deploying anywhere public:** change DB credentials, change the default admin password, and delete `test_*.php`, `debug_*.php` and the migration scripts.

## License

Educational project — provided as-is.
