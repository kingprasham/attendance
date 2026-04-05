# Kalina Engineering — Attendance Management System

## Project Summary

Geofenced, biometric attendance system for Kalina Engineering Pvt. Ltd. Employees clock in/out using fingerprint + GPS verification. Includes leave management, payroll/salary slips, and push notifications.

**Full design spec:** `docs/superpowers/specs/2026-04-01-kalina-attendance-system-design.md`

## Architecture

Three independent subsystems, built in this order:

1. **PHP REST API + MySQL** (GoDaddy shared hosting) — ~30 endpoints, vanilla PHP, JWT auth
2. **Flutter Mobile App** (employee-facing) — biometrics, geofence, attendance, leaves, salary
3. **Admin Web Dashboard** (superadmin only) — Bootstrap 5, PHP, HTML/CSS/JS

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Mobile App | Flutter 3.27+, Dart, Riverpod, Dio, GoRouter |
| Backend API | Vanilla PHP (no framework), PDO, firebase/php-jwt |
| Database | MySQL on GoDaddy shared hosting |
| Admin Dashboard | HTML5, CSS3, Bootstrap 5, Vanilla JS, PHP sessions |
| Push Notifications | Firebase Cloud Messaging |
| Maps | Google Maps JS API (admin dashboard only, one-time branch setup) |
| Hosting | GoDaddy shared hosting (existing domain + web hosting) |

## Color Theme (from Kalina logo)

| Color | Hex | Usage |
|-------|-----|-------|
| Primary Dark Blue | `#0055A4` | App bars, primary buttons, sidebar |
| Primary Light Blue | `#4A9BD9` | Accents, links, secondary elements |
| White | `#FFFFFF` | Backgrounds |
| Background | `#F8F9FB` | Screen/page backgrounds |
| Text | `#1F2937` | Primary text |
| Success | `#059669` | Present, approved |
| Error | `#DC2626` | Absent, rejected |
| Warning | `#D97706` | Late, pending |

## Key Design Decisions

- **Geofence validation is SERVER-SIDE** — Haversine formula in PHP. Client GPS check is UX only, not trusted.
- **Device binding** — Each employee locked to one device via `persistent_device_id`. Admin can reset.
- **Anti-spoofing** — Client uses `safe_device` to detect mock GPS/rooted devices. Server validates distance.
- **UTC storage** — All datetimes stored in UTC. Convert to IST (UTC+5:30) at display layer only.
- **Single admin** — One superadmin role, no RBAC. Daily reports only.
- **Leave policies per branch** — Different branches can have different leave quotas.
- **Payroll calculation** — `net = (monthly_salary / working_days) × (present + paid_leave) - (per_day × lwp_days)`
- **No framework for PHP** — Vanilla PHP with clean MVC-like structure. Deploys via FTP, no Composer needed.
- **JWT auth** — Access token 30min, refresh token 30 days with rotation.

## Project Structure

```
attendance/
├── api/                          # PHP REST API (deploy to GoDaddy)
│   ├── config/                   # database.php, constants.php, cors.php
│   ├── middleware/                # auth.php, admin_auth.php, rate_limit.php
│   ├── helpers/                  # response.php, validator.php, geofence.php, jwt_handler.php, encryption.php
│   ├── endpoints/                # auth/, branches/, employees/, attendance/, leaves/, salary/, holidays/, notifications/, leave_policies/
│   ├── .htaccess
│   └── index.php                 # Router
├── dashboard/                    # Admin web dashboard (deploy to GoDaddy)
│   ├── assets/                   # CSS, JS, images
│   ├── includes/                 # PHP partials (header, sidebar, footer, auth check)
│   ├── pages/                    # dashboard.php, branches.php, employees.php, attendance.php, leaves.php, salary.php, holidays.php, settings.php
│   ├── login.php
│   └── index.php
├── lib/                          # Flutter mobile app
│   ├── main.dart
│   ├── app/                      # theme.dart, routes.dart
│   ├── core/                     # constants/, network/, services/, utils/
│   ├── features/                 # auth/, attendance/, leaves/, salary/, profile/, notifications/, holidays/
│   └── shared/widgets/
├── docs/superpowers/specs/       # Design spec
└── CLAUDE.md                     # This file
```

## Database (10 tables)

`admins`, `branches`, `employees`, `attendance_logs`, `leave_policies`, `leave_balances`, `leave_requests`, `salary_slips`, `holidays`, `notifications`

See full schema with all columns in the design spec, Section 4.

## API Endpoints (~30 total)

- **Auth:** login, admin_login, refresh, register_device
- **Branches (admin):** create, list, update, delete
- **Employees (admin):** create, list, view, update, delete, reset_device, profile
- **Attendance:** clock_in, clock_out, today, history, report (admin)
- **Leaves:** apply, cancel, balance, history, pending (admin), approve (admin), reject (admin)
- **Salary:** generate (admin), slips, slip_detail
- **Holidays:** create (admin), list, delete (admin)
- **Notifications:** list, mark_read
- **Leave Policies:** set (admin), view

## Flutter Packages

`flutter_riverpod`, `dio`, `go_router`, `local_auth`, `geolocator`, `persistent_device_id`, `flutter_secure_storage`, `firebase_messaging`, `hive_flutter`, `safe_device`, `intl`, `table_calendar`

## Build Order

### Phase 1: Backend API + Database
1. MySQL schema (all 10 tables)
2. PHP API structure (config, middleware, helpers)
3. Auth endpoints (login, JWT, device binding)
4. Branch CRUD
5. Employee CRUD
6. Attendance (clock in/out + geofence)
7. Leave management
8. Salary generation
9. Holidays + notifications
10. Deploy to GoDaddy

### Phase 2: Flutter Mobile App
1. Project setup (theme, routing, Riverpod)
2. Auth flow (login, biometric, device binding)
3. Attendance (clock in/out, GPS)
4. Attendance history/calendar
5. Leave management
6. Salary slips
7. Profile, notifications, holidays
8. Firebase FCM
9. Test + build APK

### Phase 3: Admin Dashboard
1. Bootstrap template + sidebar
2. Admin login (PHP sessions)
3. Dashboard home (daily stats)
4. Branch management (Google Maps pin)
5. Employee management
6. Attendance reports + PDF export
7. Leave requests
8. Salary/payroll
9. Leave policies + holidays
10. Settings

## Security Rules

- Passwords: bcrypt (cost 12)
- PAN/Aadhar: AES-256 encrypted
- JWT: access 30min, refresh 30 days, rotation
- Rate limiting: login 5/15min, attendance 10/min, general 100/min
- All DB queries via PDO prepared statements
- CORS restricted to app domain
- Validate all inputs server-side
- `.htaccess` must pass Authorization header on GoDaddy

## Conventions

- PHP: snake_case for functions/variables, PascalCase not used (no classes/OOP needed for this scale)
- Dart/Flutter: standard Dart conventions (camelCase variables, PascalCase classes)
- API responses: always `{ "success": true/false, "data": {...}, "message": "..." }`
- HTTP status codes: 200 OK, 201 Created, 400 Bad Request, 401 Unauthorized, 403 Forbidden, 404 Not Found, 429 Rate Limited, 500 Server Error
- All dates in API: ISO 8601 format (UTC). Convert to IST at display.
- Employee code format: "KE-001", "KE-002", etc.
- Late threshold: configurable per branch, default clock-in after 9:30 AM = late
