# Kalina Engineering — Attendance Management System

## Design Specification

**Date:** 2026-04-01
**Project:** Kalina Engineering Attendance System
**Subsystems:** PHP REST API → Flutter Mobile App → Admin Web Dashboard

---

## 1. Project Overview

A geofenced, biometric attendance system for Kalina Engineering Pvt. Ltd. Employees must be physically present at their assigned office/factory to mark attendance. The system includes leave management, payroll generation, and salary slips.

### Core Principles

- **Geofence-verified attendance** — GPS coordinates validated server-side using Haversine formula
- **Biometric + device binding** — Fingerprint/face via device sensor + one device per employee
- **Anti-spoofing** — Fake GPS detection on client, distance validation on server
- **Offline-resilient** — Local caching with background sync
- **Single admin (superadmin)** — One admin controls all branches and employees

---

## 2. System Architecture

```
┌──────────────────┐     ┌──────────────────┐
│  Flutter Mobile   │     │  Admin Dashboard  │
│  App (Employee)   │     │  (Superadmin)     │
│  - Biometrics     │     │  - HTML/CSS/JS    │
│  - GPS            │     │  - Bootstrap 5    │
│  - Riverpod       │     │  - PHP Sessions   │
└────────┬─────────┘     └────────┬─────────┘
         │  HTTPS (REST)          │  PHP Direct
         └──────────┬─────────────┘
                    ▼
         ┌──────────────────────┐
         │  PHP REST API         │
         │  (GoDaddy Shared)     │
         │  - Vanilla PHP        │
         │  - JWT Auth           │
         │  - Geofence Validate  │
         │  - Rate Limiting      │
         └──────────┬───────────┘
                    │  PDO
                    ▼
         ┌──────────────────┐    ┌─────────────┐
         │  MySQL Database   │    │ Firebase FCM │
         │  (GoDaddy)        │    │ (Push)       │
         └──────────────────┘    └─────────────┘
```

### Tech Stack

| Component | Technology |
|-----------|-----------|
| Mobile App | Flutter 3.27+, Dart, Riverpod |
| Backend API | Vanilla PHP, PDO, JWT (firebase/php-jwt) |
| Database | MySQL (GoDaddy) |
| Admin Dashboard | HTML5, CSS3, Bootstrap 5, Vanilla JS, PHP |
| Push Notifications | Firebase Cloud Messaging |
| Maps | Google Maps JS API (admin only, one-time setup) |
| Hosting | GoDaddy Shared Hosting (existing domain) |

---

## 3. Color Theme (from Logo)

| Color | Hex | Usage |
|-------|-----|-------|
| Primary Dark Blue | `#0055A4` | App bars, primary buttons, sidebar |
| Primary Light Blue | `#4A9BD9` | Accents, links, secondary elements |
| White | `#FFFFFF` | Backgrounds, text on dark |
| Background Gray | `#F8F9FB` | Screen backgrounds |
| Text Dark | `#1F2937` | Primary text |
| Success Green | `#059669` | Present status, approvals |
| Error Red | `#DC2626` | Absent, rejections, errors |
| Warning Amber | `#D97706` | Late, pending states |

---

## 4. Database Schema

### 4.1 admins
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| name | VARCHAR(100) | |
| email | VARCHAR(150) UNIQUE | |
| password | VARCHAR(255) | bcrypt hash |
| created_at | TIMESTAMP | |

### 4.2 branches
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| name | VARCHAR(100) | e.g., "Borivali Factory" |
| address | TEXT | Full address |
| latitude | DECIMAL(10,8) | GPS from Google Maps pin |
| longitude | DECIMAL(11,8) | GPS from Google Maps pin |
| radius_meters | INT | Geofence radius set by admin |
| is_active | TINYINT(1) | Soft delete |
| created_at | TIMESTAMP | |

### 4.3 employees
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| employee_code | VARCHAR(20) UNIQUE | e.g., "KE-001" |
| full_name | VARCHAR(100) | |
| email | VARCHAR(150) | |
| phone | VARCHAR(15) | |
| profile_photo | VARCHAR(255) | File path |
| designation | VARCHAR(100) | |
| department | VARCHAR(100) | |
| branch_id | FK → branches | |
| date_of_joining | DATE | |
| employment_type | ENUM('full','part','contract') | |
| monthly_salary | DECIMAL(12,2) | |
| bank_account | VARCHAR(20) | |
| ifsc_code | VARCHAR(11) | |
| pan_number | VARCHAR(10) | AES-256 encrypted |
| aadhar_number | VARCHAR(12) | AES-256 encrypted |
| username | VARCHAR(50) UNIQUE | Login credential |
| password | VARCHAR(255) | bcrypt hash |
| device_id | VARCHAR(255) | Bound on first login |
| fcm_token | VARCHAR(255) | Push notification token |
| is_active | TINYINT(1) | Soft delete |
| created_at | TIMESTAMP | |

### 4.4 attendance_logs
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| employee_id | FK → employees | |
| date | DATE | UNIQUE with employee_id |
| clock_in | DATETIME | UTC |
| clock_out | DATETIME | UTC |
| clock_in_lat | DECIMAL(10,8) | |
| clock_in_lng | DECIMAL(11,8) | |
| clock_out_lat | DECIMAL(10,8) | |
| clock_out_lng | DECIMAL(11,8) | |
| device_id | VARCHAR(255) | For audit |
| status | ENUM('present','half_day','late') | |
| work_hours | DECIMAL(4,2) | Calculated on clock_out |
| created_at | TIMESTAMP | |

**Constraints:** UNIQUE(employee_id, date)

### 4.5 leave_policies
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| branch_id | FK → branches | Configurable per branch |
| leave_type | ENUM('CL','SL','EL','CO','LWP') | |
| annual_quota | INT | e.g., 12 |
| carry_forward | TINYINT(1) | Allow carry-forward? |
| max_carry | INT | Max days to carry |

### 4.6 leave_balances
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| employee_id | FK → employees | |
| leave_type | ENUM('CL','SL','EL','CO','LWP') | |
| year | YEAR | |
| total_quota | INT | From policy + carry-forward |
| used | INT | Approved leaves consumed |
| carried_forward | INT | From previous year |

**Constraints:** UNIQUE(employee_id, leave_type, year)

### 4.7 leave_requests
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| employee_id | FK → employees | |
| leave_type | ENUM('CL','SL','EL','CO','LWP') | |
| start_date | DATE | |
| end_date | DATE | |
| is_half_day | TINYINT(1) | |
| reason | TEXT | |
| status | ENUM('pending','approved','rejected') | |
| admin_remarks | TEXT | |
| created_at | TIMESTAMP | |

### 4.8 salary_slips
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| employee_id | FK → employees | |
| month | TINYINT | 1-12 |
| year | YEAR | |
| total_days | INT | Working days in month |
| present_days | INT | From attendance_logs |
| leave_days | INT | Approved paid leaves |
| lwp_days | INT | Unpaid leave days |
| overtime_hours | DECIMAL(5,2) | |
| gross_salary | DECIMAL(12,2) | |
| deductions | DECIMAL(12,2) | LWP + other |
| net_salary | DECIMAL(12,2) | gross - deductions |
| generated_at | TIMESTAMP | |

**Constraints:** UNIQUE(employee_id, month, year)

### 4.9 holidays
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| branch_id | FK → branches | Per-branch holidays |
| name | VARCHAR(100) | e.g., "Diwali" |
| date | DATE | |
| is_optional | TINYINT(1) | Optional holiday flag |

### 4.10 notifications
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| employee_id | FK → employees | |
| title | VARCHAR(200) | |
| body | TEXT | |
| type | ENUM('leave','salary','general') | |
| is_read | TINYINT(1) | |
| created_at | TIMESTAMP | |

---

## 5. API Endpoints (~30 total)

### Base URL: `https://yourdomain.com/api/`

All times stored in UTC, converted to IST (UTC+5:30) at display layer.

### 5.1 Authentication
| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | /auth/login | Public | Employee login (username + password + device_id) |
| POST | /auth/admin_login | Public | Admin login |
| POST | /auth/refresh | Auth | Refresh JWT access token |
| POST | /auth/register_device | Auth | Bind device on first login |

### 5.2 Branches (Admin only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /branches/create | Create branch with GPS + radius |
| GET | /branches/list | List all branches |
| PUT | /branches/update | Update branch details |
| DELETE | /branches/delete | Soft-delete branch |

### 5.3 Employees (Admin only, except profile)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /employees/create | Create employee with all details |
| GET | /employees/list | List all employees (filterable by branch) |
| GET | /employees/view | Single employee details |
| PUT | /employees/update | Update employee |
| DELETE | /employees/delete | Soft-delete employee |
| POST | /employees/reset_device | Unbind device (admin) |
| GET | /employees/profile | Employee's own profile (auth) |

### 5.4 Attendance (Auth required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /attendance/clock_in | Mark clock-in (lat, lng, device_id) |
| POST | /attendance/clock_out | Mark clock-out (lat, lng, device_id) |
| GET | /attendance/today | Today's attendance status |
| GET | /attendance/history | Monthly attendance history |
| GET | /attendance/report | Daily report — admin only |

### 5.5 Leaves (Auth required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /leaves/apply | Apply for leave |
| POST | /leaves/cancel | Cancel pending leave |
| GET | /leaves/balance | Get leave balances |
| GET | /leaves/history | Leave request history |
| GET | /leaves/pending | Pending requests — admin only |
| POST | /leaves/approve | Approve leave — admin only |
| POST | /leaves/reject | Reject leave — admin only |

### 5.6 Salary
| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | /salary/generate | Admin | Generate monthly payroll |
| GET | /salary/slips | Auth | Employee's salary slips |
| GET | /salary/slip_detail | Auth | Single slip breakdown |

### 5.7 Holidays
| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | /holidays/create | Admin | Add holiday |
| GET | /holidays/list | Auth | Holiday calendar |
| DELETE | /holidays/delete | Admin | Remove holiday |

### 5.8 Notifications
| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | /notifications/list | Auth | Employee notifications |
| POST | /notifications/mark_read | Auth | Mark as read |

### 5.9 Leave Policies
| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | /leave_policies/set | Admin | Set branch leave policy |
| GET | /leave_policies/view | Auth | View branch policy |

---

## 6. Critical Flows

### 6.1 Clock In Flow
1. Employee opens app → biometric prompt (fingerprint/face)
2. Biometric verified locally via `local_auth`
3. App reads GPS via `geolocator` (high accuracy mode)
4. App checks for mock/fake location via `safe_device`
5. Sends to API: `{ latitude, longitude, device_id }` + JWT header
6. Server validates: JWT → device_id match → no duplicate today → Haversine distance ≤ branch radius
7. Records in attendance_logs, returns success with IST time
8. Error cases: "Not in office premises", "Device not registered", "Already clocked in"

### 6.2 Clock Out Flow
Same as Clock In but updates `clock_out`, `clock_out_lat/lng`, calculates `work_hours`.

### 6.3 Device Binding Flow
1. Admin creates employee (username + password)
2. Employee installs app, enters credentials
3. First login: no device_id bound → app sends persistent_device_id → server stores it
4. Subsequent logins: server checks device_id matches → rejects if different device
5. New device: admin must call `/employees/reset_device` to unbind

### 6.4 Leave Application Flow
1. Employee selects leave type, dates, reason
2. App checks local balance → sends to API
3. Server validates: balance available, no date conflicts, valid type
4. Stores as "pending" → push notification to admin
5. Admin approves/rejects from dashboard → push notification to employee
6. On approval: leave_balances.used incremented

### 6.5 Payroll Calculation
1. Admin selects branch + month/year
2. Server for each employee in branch:
   - Counts present_days from attendance_logs
   - Counts approved leave_days (paid leaves)
   - Counts lwp_days (unpaid leaves)
   - `per_day = monthly_salary / total_working_days`
   - `gross_salary = per_day × (present_days + paid_leave_days)`
   - `deductions = per_day × lwp_days`
   - `net_salary = gross_salary - deductions`
3. Generates salary_slips records
4. Push notification to employees: "Salary slip for [month] generated"

---

## 7. Security

### Authentication
- **JWT Access Token**: 30 minute expiry
- **JWT Refresh Token**: 30 day expiry, rotation on each use
- **Passwords**: bcrypt hashed (cost 12)
- **Sensitive data** (PAN, Aadhar): AES-256 encrypted at rest

### Rate Limiting (DB-based)
- Login: 5 attempts / 15 minutes
- Attendance endpoints: 10 requests / minute
- General API: 100 requests / minute per user

### Anti-Spoofing
- Client: `safe_device` package detects mock locations, rooted devices, emulators
- Server: Haversine distance check is the authoritative validation
- Server: Flag impossible location jumps (>100km between consecutive punches)

### API Security
- All inputs sanitized and validated
- PDO prepared statements (no SQL injection)
- CORS restricted to app domain
- `.htaccess` passes Authorization header on shared hosting

---

## 8. Flutter App Structure

```
lib/
├── main.dart
├── app/
│   ├── theme.dart                    # Kalina colors
│   └── routes.dart                   # GoRouter
├── core/
│   ├── constants/
│   │   ├── api_endpoints.dart
│   │   └── app_colors.dart
│   ├── network/
│   │   ├── api_client.dart           # Dio + interceptors
│   │   ├── auth_interceptor.dart     # JWT auto-attach + 401 refresh
│   │   └── api_exceptions.dart
│   ├── services/
│   │   ├── auth_service.dart
│   │   ├── biometric_service.dart    # local_auth
│   │   ├── location_service.dart     # geolocator + fake detection
│   │   ├── device_service.dart       # persistent_device_id
│   │   ├── notification_service.dart # Firebase FCM
│   │   └── secure_storage.dart       # flutter_secure_storage
│   └── utils/
│       ├── date_utils.dart           # IST conversion
│       └── validators.dart
├── features/
│   ├── auth/          # Login, splash, biometric
│   ├── attendance/    # Clock in/out, calendar, history
│   ├── leaves/        # Apply, balance, history
│   ├── salary/        # Slips list, detail breakdown
│   ├── profile/       # Employee personal details
│   ├── notifications/ # Notification list
│   └── holidays/      # Holiday calendar
└── shared/widgets/    # Drawer, loading, errors
```

### Flutter Packages
| Package | Purpose |
|---------|---------|
| flutter_riverpod | State management |
| dio | HTTP client with interceptors |
| go_router | Declarative navigation |
| local_auth | Fingerprint / Face ID |
| geolocator | GPS coordinates |
| persistent_device_id | Unique device binding |
| flutter_secure_storage | Encrypted token storage |
| firebase_messaging | Push notifications |
| hive_flutter | Local offline cache |
| safe_device | Fake GPS / root detection |
| intl | Date/time IST formatting |
| table_calendar | Attendance calendar widget |

---

## 9. Admin Dashboard Structure

### Tech Stack
- HTML5 + CSS3 (responsive)
- Bootstrap 5 (UI framework)
- Vanilla JavaScript (ES6+)
- Chart.js (attendance graphs)
- DataTables (sortable, searchable tables)
- Google Maps JS API (branch location pin — one-time setup)
- PHP sessions (admin authentication)
- DomPDF (PDF export for reports/salary slips)

### Dashboard Pages (9 pages)
1. **Dashboard Home** — Daily stats: total employees, present, on leave, absent. Branch-wise breakdown.
2. **Branch Management** — CRUD branches with Google Maps pin + radius setting.
3. **Employee Management** — Full CRUD with all details, salary, assign branch, reset device.
4. **Daily Attendance Report** — Filter by date + branch. Shows clock in/out times, hours, status. Export PDF/Excel.
5. **Leave Requests** — Pending queue, approve/reject with remarks, view balance before action.
6. **Leave Policies** — Set per-branch leave quotas, carry-forward rules.
7. **Holiday Calendar** — Add/remove holidays per branch, optional/mandatory flag.
8. **Generate Salary** — Select branch + month, auto-calculate, review, generate slips.
9. **Admin Settings** — Change password, working hours config, late threshold (configurable, default: clock-in after 9:30 AM = late).

---

## 10. PHP API Directory Structure

```
api/
├── config/
│   ├── database.php          # MySQL PDO connection
│   ├── constants.php         # JWT secret, defaults, timezone
│   └── cors.php              # CORS headers
├── middleware/
│   ├── auth.php              # JWT verification
│   ├── admin_auth.php        # Admin-only check
│   └── rate_limit.php        # DB-based rate limiting
├── helpers/
│   ├── response.php          # JSON response formatter
│   ├── validator.php         # Input validation
│   ├── geofence.php          # Haversine distance calculation
│   ├── jwt_handler.php       # JWT encode/decode
│   └── encryption.php        # AES encrypt/decrypt
├── endpoints/
│   ├── auth/                 # login, admin_login, refresh, register_device
│   ├── branches/             # create, list, update, delete
│   ├── employees/            # create, list, view, update, delete, reset_device, profile
│   ├── attendance/           # clock_in, clock_out, today, history, report
│   ├── leaves/               # apply, cancel, balance, history, pending, approve, reject
│   ├── salary/               # generate, slips, slip_detail
│   ├── holidays/             # create, list, delete
│   ├── notifications/        # list, mark_read
│   └── leave_policies/       # set, view
├── .htaccess                 # URL rewriting + auth header pass-through
└── index.php                 # Simple router
```

---

## 11. Build Order

### Phase 1: Backend API + Database
1. Set up MySQL database with all tables
2. Build PHP API structure (config, middleware, helpers)
3. Implement auth endpoints (login, JWT, device binding)
4. Implement branch CRUD
5. Implement employee CRUD
6. Implement attendance (clock in/out with geofence validation)
7. Implement leaves (apply, approve, balance)
8. Implement salary generation
9. Implement holidays and notifications
10. Deploy API to GoDaddy

### Phase 2: Flutter Mobile App
1. Set up project structure, theme, routing
2. Build auth flow (login, biometric, device binding)
3. Build attendance screen (clock in/out, GPS check)
4. Build attendance history/calendar
5. Build leave management (apply, balance, history)
6. Build salary slips screen
7. Build profile, notifications, holidays
8. Firebase FCM integration
9. Testing and APK build

### Phase 3: Admin Web Dashboard
1. Set up Bootstrap template with sidebar
2. Build admin login
3. Build dashboard home with daily stats
4. Build branch management (with Google Maps)
5. Build employee management
6. Build attendance reports
7. Build leave request management
8. Build salary/payroll section
9. Build leave policies and holidays
10. PDF export functionality

---

## 12. Deployment

- **API + Dashboard**: Upload to GoDaddy shared hosting via FTP/File Manager
- **Database**: Create via GoDaddy phpMyAdmin
- **Flutter App**: Build APK for Android distribution
- **Firebase**: Set up project for FCM push notifications
- **Google Maps**: Get API key (free tier, minimal usage for admin branch setup)
- **Domain**: Use existing GoDaddy domain for API (e.g., `api.yourdomain.com` or `yourdomain.com/api/`)
