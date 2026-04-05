# Phase 1: Backend API + Database — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the complete PHP REST API with MySQL database for the Kalina Engineering Attendance System, deployable to GoDaddy shared hosting.

**Architecture:** Vanilla PHP REST API with a simple file-based router. Each endpoint is a separate PHP file. JWT authentication via firebase/php-jwt. MySQL via PDO prepared statements. No framework, no Composer — all dependencies are single-file includes.

**Tech Stack:** PHP 8.x, MySQL 8.x, firebase/php-jwt (single file), PDO, bcrypt, AES-256, Haversine formula

**Design Spec:** `docs/superpowers/specs/2026-04-01-kalina-attendance-system-design.md`

---

## File Structure

```
api/
├── config/
│   ├── database.php              # PDO connection singleton
│   ├── constants.php             # JWT secret, AES key, timezone, defaults
│   └── cors.php                  # CORS headers for all responses
├── middleware/
│   ├── auth.php                  # JWT verification, returns employee_id
│   ├── admin_auth.php            # Verifies admin JWT token
│   └── rate_limit.php            # DB-based rate limiter
├── helpers/
│   ├── response.php              # json_response(), error_response()
│   ├── validator.php             # validate_required(), validate_email(), etc.
│   ├── geofence.php              # haversine_distance()
│   ├── jwt_handler.php           # generate_tokens(), decode_token()
│   └── encryption.php            # aes_encrypt(), aes_decrypt()
├── lib/
│   ├── php-jwt/                  # firebase/php-jwt library files
│   │   ├── JWT.php
│   │   ├── Key.php
│   │   ├── JWK.php
│   │   ├── SignatureInvalidException.php
│   │   ├── BeforeValidException.php
│   │   ├── ExpiredException.php
│   │   └── CachedKeySet.php
│   └── README.md                 # Library attribution
├── endpoints/
│   ├── auth/
│   │   ├── login.php             # POST — employee login
│   │   ├── admin_login.php       # POST — admin login
│   │   ├── refresh.php           # POST — refresh access token
│   │   └── register_device.php   # POST — bind device to employee
│   ├── branches/
│   │   ├── create.php            # POST — create branch
│   │   ├── list.php              # GET — list branches
│   │   ├── update.php            # PUT — update branch
│   │   └── delete.php            # DELETE — soft-delete branch
│   ├── employees/
│   │   ├── create.php            # POST — create employee
│   │   ├── list.php              # GET — list employees
│   │   ├── view.php              # GET — single employee
│   │   ├── update.php            # PUT — update employee
│   │   ├── delete.php            # DELETE — soft-delete employee
│   │   ├── reset_device.php      # POST — unbind device
│   │   └── profile.php           # GET — employee's own profile
│   ├── attendance/
│   │   ├── clock_in.php          # POST — mark clock in
│   │   ├── clock_out.php         # POST — mark clock out
│   │   ├── today.php             # GET — today's status
│   │   ├── history.php           # GET — monthly history
│   │   └── report.php            # GET — daily report (admin)
│   ├── leaves/
│   │   ├── apply.php             # POST — apply for leave
│   │   ├── cancel.php            # POST — cancel pending leave
│   │   ├── balance.php           # GET — leave balances
│   │   ├── history.php           # GET — leave history
│   │   ├── pending.php           # GET — pending requests (admin)
│   │   ├── approve.php           # POST — approve leave (admin)
│   │   └── reject.php            # POST — reject leave (admin)
│   ├── salary/
│   │   ├── generate.php          # POST — generate payroll (admin)
│   │   ├── slips.php             # GET — salary slips list
│   │   └── slip_detail.php       # GET — single slip detail
│   ├── holidays/
│   │   ├── create.php            # POST — add holiday (admin)
│   │   ├── list.php              # GET — holiday list
│   │   └── delete.php            # DELETE — remove holiday (admin)
│   ├── notifications/
│   │   ├── list.php              # GET — notification list
│   │   └── mark_read.php         # POST — mark notification read
│   └── leave_policies/
│       ├── set.php               # POST — set branch policy (admin)
│       └── view.php              # GET — view branch policy
├── sql/
│   └── schema.sql                # Complete database creation script
├── .htaccess                     # URL rewriting + auth header
└── index.php                     # Router
```

---

### Task 1: Database Schema

**Files:**
- Create: `api/sql/schema.sql`

- [ ] **Step 1: Write the complete MySQL schema**

```sql
-- Kalina Engineering Attendance System
-- Database Schema

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `kalina_attendance`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `kalina_attendance`;

-- ============================================
-- 1. ADMINS
-- ============================================
CREATE TABLE `admins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. BRANCHES
-- ============================================
CREATE TABLE `branches` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `radius_meters` INT NOT NULL DEFAULT 200,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. EMPLOYEES
-- ============================================
CREATE TABLE `employees` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_code` VARCHAR(20) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(15) DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `designation` VARCHAR(100) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `branch_id` INT NOT NULL,
  `date_of_joining` DATE NOT NULL,
  `employment_type` ENUM('full','part','contract') NOT NULL DEFAULT 'full',
  `monthly_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `bank_account` VARCHAR(20) DEFAULT NULL,
  `ifsc_code` VARCHAR(11) DEFAULT NULL,
  `pan_number` VARCHAR(255) DEFAULT NULL,
  `aadhar_number` VARCHAR(255) DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `device_id` VARCHAR(255) DEFAULT NULL,
  `fcm_token` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_employee_code` (`employee_code`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `fk_employee_branch` (`branch_id`),
  CONSTRAINT `fk_employee_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. ATTENDANCE LOGS
-- ============================================
CREATE TABLE `attendance_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `clock_in` DATETIME NOT NULL,
  `clock_out` DATETIME DEFAULT NULL,
  `clock_in_lat` DECIMAL(10,8) NOT NULL,
  `clock_in_lng` DECIMAL(11,8) NOT NULL,
  `clock_out_lat` DECIMAL(10,8) DEFAULT NULL,
  `clock_out_lng` DECIMAL(11,8) DEFAULT NULL,
  `device_id` VARCHAR(255) NOT NULL,
  `status` ENUM('present','half_day','late') NOT NULL DEFAULT 'present',
  `work_hours` DECIMAL(4,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_employee_date` (`employee_id`, `date`),
  KEY `idx_date` (`date`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. LEAVE POLICIES
-- ============================================
CREATE TABLE `leave_policies` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `branch_id` INT NOT NULL,
  `leave_type` ENUM('CL','SL','EL','CO','LWP') NOT NULL,
  `annual_quota` INT NOT NULL DEFAULT 0,
  `carry_forward` TINYINT(1) NOT NULL DEFAULT 0,
  `max_carry` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_branch_leave_type` (`branch_id`, `leave_type`),
  CONSTRAINT `fk_policy_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. LEAVE BALANCES
-- ============================================
CREATE TABLE `leave_balances` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `leave_type` ENUM('CL','SL','EL','CO','LWP') NOT NULL,
  `year` YEAR NOT NULL,
  `total_quota` INT NOT NULL DEFAULT 0,
  `used` INT NOT NULL DEFAULT 0,
  `carried_forward` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_leave_year` (`employee_id`, `leave_type`, `year`),
  CONSTRAINT `fk_balance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. LEAVE REQUESTS
-- ============================================
CREATE TABLE `leave_requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `leave_type` ENUM('CL','SL','EL','CO','LWP') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_half_day` TINYINT(1) NOT NULL DEFAULT 0,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_leave_employee` (`employee_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_leave_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 8. SALARY SLIPS
-- ============================================
CREATE TABLE `salary_slips` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `month` TINYINT NOT NULL,
  `year` YEAR NOT NULL,
  `total_days` INT NOT NULL,
  `present_days` INT NOT NULL DEFAULT 0,
  `leave_days` INT NOT NULL DEFAULT 0,
  `lwp_days` INT NOT NULL DEFAULT 0,
  `overtime_hours` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `gross_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_month_year` (`employee_id`, `month`, `year`),
  CONSTRAINT `fk_salary_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 9. HOLIDAYS
-- ============================================
CREATE TABLE `holidays` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `branch_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `date` DATE NOT NULL,
  `is_optional` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_holiday_branch` (`branch_id`),
  CONSTRAINT `fk_holiday_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 10. NOTIFICATIONS
-- ============================================
CREATE TABLE `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `body` TEXT DEFAULT NULL,
  `type` ENUM('leave','salary','general') NOT NULL DEFAULT 'general',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notif_employee` (`employee_id`),
  KEY `idx_read` (`is_read`),
  CONSTRAINT `fk_notif_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 11. RATE LIMITS (for API rate limiting)
-- ============================================
CREATE TABLE `rate_limits` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `endpoint` VARCHAR(100) NOT NULL,
  `hits` INT NOT NULL DEFAULT 1,
  `window_start` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_endpoint` (`ip_address`, `endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 12. REFRESH TOKENS (for JWT refresh rotation)
-- ============================================
CREATE TABLE `refresh_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `user_type` ENUM('employee','admin') NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `revoked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_user` (`user_id`, `user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED: Default admin account
-- Password: admin123 (bcrypt hash)
-- ============================================
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Super Admin', 'admin@kalinaengineering.com', '$2y$12$LJ3m4ys3Gz8y6C5FMqjXaeJD7VhWOHRqFgTfVKBbSE4rMHWyFO2tW');
```

- [ ] **Step 2: Commit**

```bash
git add api/sql/schema.sql
git commit -m "feat: add complete MySQL schema with all 12 tables"
```

---

### Task 2: Core Config Files

**Files:**
- Create: `api/config/database.php`
- Create: `api/config/constants.php`
- Create: `api/config/cors.php`

- [ ] **Step 1: Create database.php**

```php
<?php
// api/config/database.php
// PDO database connection

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = 'localhost';
    $dbname = 'kalina_attendance';
    $username = 'root';        // Update for GoDaddy
    $password = '';             // Update for GoDaddy

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        // Set timezone to UTC for consistent storage
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
}
```

- [ ] **Step 2: Create constants.php**

```php
<?php
// api/config/constants.php

// JWT
define('JWT_SECRET', 'CHANGE_THIS_TO_A_RANDOM_64_CHAR_STRING_IN_PRODUCTION');
define('JWT_ACCESS_EXPIRY', 1800);      // 30 minutes
define('JWT_REFRESH_EXPIRY', 2592000);  // 30 days
define('JWT_ALGORITHM', 'HS256');

// AES Encryption (for PAN, Aadhar)
define('AES_KEY', 'CHANGE_THIS_TO_A_RANDOM_32_CHAR_KEY_NOW');
define('AES_METHOD', 'aes-256-cbc');

// Timezone
define('APP_TIMEZONE', 'Asia/Kolkata');
define('UTC_OFFSET_IST', '+05:30');

// Rate Limiting
define('RATE_LIMIT_LOGIN', 5);           // 5 attempts
define('RATE_LIMIT_LOGIN_WINDOW', 900);  // per 15 minutes
define('RATE_LIMIT_ATTENDANCE', 10);     // 10 requests
define('RATE_LIMIT_ATTENDANCE_WINDOW', 60); // per 1 minute
define('RATE_LIMIT_GENERAL', 100);       // 100 requests
define('RATE_LIMIT_GENERAL_WINDOW', 60); // per 1 minute

// Late Threshold (default 9:30 AM IST)
define('DEFAULT_LATE_HOUR', 9);
define('DEFAULT_LATE_MINUTE', 30);

// Employee Code Prefix
define('EMPLOYEE_CODE_PREFIX', 'KE');
```

- [ ] **Step 3: Create cors.php**

```php
<?php
// api/config/cors.php
// Set CORS headers — call at the top of index.php

function set_cors_headers() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=UTF-8');

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add api/config/
git commit -m "feat: add core config (database, constants, CORS)"
```

---

### Task 3: Helper Functions

**Files:**
- Create: `api/helpers/response.php`
- Create: `api/helpers/validator.php`
- Create: `api/helpers/geofence.php`
- Create: `api/helpers/encryption.php`

- [ ] **Step 1: Create response.php**

```php
<?php
// api/helpers/response.php

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => 'OK'
    ]);
    exit;
}

function success_response($message, $data = null, $status_code = 200) {
    http_response_code($status_code);
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

function error_response($message, $status_code = 400) {
    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

function get_json_input() {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        error_response('Invalid JSON input', 400);
    }
    return $input ?? [];
}
```

- [ ] **Step 2: Create validator.php**

```php
<?php
// api/helpers/validator.php

function validate_required($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        error_response('Missing required fields: ' . implode(', ', $missing), 400);
    }
}

function validate_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_response('Invalid email format', 400);
    }
}

function validate_enum($value, $allowed, $field_name) {
    if (!in_array($value, $allowed, true)) {
        error_response("Invalid {$field_name}. Allowed: " . implode(', ', $allowed), 400);
    }
}

function validate_date($date, $field_name = 'date') {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        error_response("Invalid {$field_name} format. Use YYYY-MM-DD", 400);
    }
}

function validate_positive_number($value, $field_name) {
    if (!is_numeric($value) || $value < 0) {
        error_response("{$field_name} must be a positive number", 400);
    }
}

function sanitize_string($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}
```

- [ ] **Step 3: Create geofence.php**

```php
<?php
// api/helpers/geofence.php

/**
 * Calculate distance between two GPS coordinates using Haversine formula.
 * Returns distance in meters.
 */
function haversine_distance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371000; // meters

    $lat1_rad = deg2rad($lat1);
    $lat2_rad = deg2rad($lat2);
    $delta_lat = deg2rad($lat2 - $lat1);
    $delta_lng = deg2rad($lng2 - $lng1);

    $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($delta_lng / 2) * sin($delta_lng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earth_radius * $c;
}

/**
 * Check if employee coordinates are within branch geofence.
 * Returns true if within radius, false otherwise.
 */
function is_within_geofence($emp_lat, $emp_lng, $branch_lat, $branch_lng, $radius_meters) {
    $distance = haversine_distance($emp_lat, $emp_lng, $branch_lat, $branch_lng);
    return $distance <= $radius_meters;
}
```

- [ ] **Step 4: Create encryption.php**

```php
<?php
// api/helpers/encryption.php

require_once __DIR__ . '/../config/constants.php';

function aes_encrypt($plaintext) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_METHOD));
    $ciphertext = openssl_encrypt($plaintext, AES_METHOD, AES_KEY, 0, $iv);
    return base64_encode($iv . '::' . $ciphertext);
}

function aes_decrypt($encrypted) {
    $data = base64_decode($encrypted);
    $parts = explode('::', $data, 2);
    if (count($parts) !== 2) {
        return null;
    }
    return openssl_decrypt($parts[1], AES_METHOD, AES_KEY, 0, $parts[0]);
}
```

- [ ] **Step 5: Commit**

```bash
git add api/helpers/
git commit -m "feat: add helper functions (response, validator, geofence, encryption)"
```

---

### Task 4: JWT Handler + php-jwt Library

**Files:**
- Create: `api/helpers/jwt_handler.php`
- Create: `api/lib/php-jwt/JWT.php` (download from GitHub)
- Create: `api/lib/php-jwt/Key.php` (download from GitHub)
- Create: `api/lib/php-jwt/SignatureInvalidException.php`
- Create: `api/lib/php-jwt/BeforeValidException.php`
- Create: `api/lib/php-jwt/ExpiredException.php`

- [ ] **Step 1: Download firebase/php-jwt library files**

Download the following files from https://github.com/firebase/php-jwt/tree/main/src into `api/lib/php-jwt/`:
- `JWT.php`
- `Key.php`
- `SignatureInvalidException.php`
- `BeforeValidException.php`
- `ExpiredException.php`
- `JWK.php`
- `CachedKeySet.php`

```bash
mkdir -p api/lib/php-jwt
cd api/lib/php-jwt

# Download each file from the firebase/php-jwt v6.x release
curl -sL "https://raw.githubusercontent.com/firebase/php-jwt/v6.10.2/src/JWT.php" -o JWT.php
curl -sL "https://raw.githubusercontent.com/firebase/php-jwt/v6.10.2/src/Key.php" -o Key.php
curl -sL "https://raw.githubusercontent.com/firebase/php-jwt/v6.10.2/src/SignatureInvalidException.php" -o SignatureInvalidException.php
curl -sL "https://raw.githubusercontent.com/firebase/php-jwt/v6.10.2/src/BeforeValidException.php" -o BeforeValidException.php
curl -sL "https://raw.githubusercontent.com/firebase/php-jwt/v6.10.2/src/ExpiredException.php" -o ExpiredException.php
curl -sL "https://raw.githubusercontent.com/firebase/php-jwt/v6.10.2/src/JWK.php" -o JWK.php
curl -sL "https://raw.githubusercontent.com/firebase/php-jwt/v6.10.2/src/CachedKeySet.php" -o CachedKeySet.php
```

Note: If curl is unavailable, manually download these files and place them in `api/lib/php-jwt/`. The namespace in each file is `Firebase\JWT`. You need to add a simple autoloader or use require_once for each file.

- [ ] **Step 2: Create jwt_handler.php**

```php
<?php
// api/helpers/jwt_handler.php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Load php-jwt library
require_once __DIR__ . '/../lib/php-jwt/BeforeValidException.php';
require_once __DIR__ . '/../lib/php-jwt/ExpiredException.php';
require_once __DIR__ . '/../lib/php-jwt/SignatureInvalidException.php';
require_once __DIR__ . '/../lib/php-jwt/JWT.php';
require_once __DIR__ . '/../lib/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * Generate access + refresh token pair.
 */
function generate_tokens($user_id, $user_type = 'employee') {
    $now = time();

    // Access token
    $access_payload = [
        'iss' => 'kalina-attendance',
        'sub' => $user_id,
        'type' => $user_type,
        'iat' => $now,
        'exp' => $now + JWT_ACCESS_EXPIRY,
    ];
    $access_token = JWT::encode($access_payload, JWT_SECRET, JWT_ALGORITHM);

    // Refresh token (random string stored hashed in DB)
    $refresh_token = bin2hex(random_bytes(32));
    $refresh_hash = hash('sha256', $refresh_token);

    // Store refresh token in DB
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        "INSERT INTO refresh_tokens (user_id, user_type, token_hash, expires_at)
         VALUES (:user_id, :user_type, :hash, :expires)"
    );
    $stmt->execute([
        ':user_id' => $user_id,
        ':user_type' => $user_type,
        ':hash' => $refresh_hash,
        ':expires' => date('Y-m-d H:i:s', $now + JWT_REFRESH_EXPIRY),
    ]);

    return [
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'expires_in' => JWT_ACCESS_EXPIRY,
    ];
}

/**
 * Decode and validate access token from Authorization header.
 * Returns decoded payload or calls error_response.
 */
function decode_access_token() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($auth_header) || !preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
        error_response('Missing or invalid Authorization header', 401);
    }

    $token = $matches[1];

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
        return (array)$decoded;
    } catch (ExpiredException $e) {
        error_response('Token expired', 401);
    } catch (SignatureInvalidException $e) {
        error_response('Invalid token signature', 401);
    } catch (\Exception $e) {
        error_response('Invalid token', 401);
    }
}

/**
 * Validate refresh token and issue new token pair (rotation).
 */
function refresh_access_token($refresh_token) {
    $pdo = get_db_connection();
    $hash = hash('sha256', $refresh_token);

    $stmt = $pdo->prepare(
        "SELECT id, user_id, user_type, expires_at, revoked
         FROM refresh_tokens WHERE token_hash = :hash LIMIT 1"
    );
    $stmt->execute([':hash' => $hash]);
    $row = $stmt->fetch();

    if (!$row) {
        error_response('Invalid refresh token', 401);
    }
    if ($row['revoked']) {
        // Possible token reuse — revoke all tokens for this user
        $stmt2 = $pdo->prepare(
            "UPDATE refresh_tokens SET revoked = 1
             WHERE user_id = :uid AND user_type = :utype"
        );
        $stmt2->execute([':uid' => $row['user_id'], ':utype' => $row['user_type']]);
        error_response('Refresh token reused — all sessions revoked', 401);
    }
    if (strtotime($row['expires_at']) < time()) {
        error_response('Refresh token expired', 401);
    }

    // Revoke old refresh token
    $stmt3 = $pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = :id");
    $stmt3->execute([':id' => $row['id']]);

    // Generate new pair
    return generate_tokens($row['user_id'], $row['user_type']);
}
```

- [ ] **Step 3: Commit**

```bash
git add api/lib/ api/helpers/jwt_handler.php
git commit -m "feat: add JWT handler with token generation, validation, and refresh rotation"
```

---

### Task 5: Middleware (Auth, Admin Auth, Rate Limiting)

**Files:**
- Create: `api/middleware/auth.php`
- Create: `api/middleware/admin_auth.php`
- Create: `api/middleware/rate_limit.php`

- [ ] **Step 1: Create auth.php**

```php
<?php
// api/middleware/auth.php
// Verifies JWT and returns the employee_id. Call at top of protected endpoints.

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt_handler.php';

function require_auth() {
    $payload = decode_access_token();

    if ($payload['type'] !== 'employee') {
        error_response('Employee access required', 403);
    }

    return $payload['sub']; // employee_id
}
```

- [ ] **Step 2: Create admin_auth.php**

```php
<?php
// api/middleware/admin_auth.php
// Verifies JWT and checks admin type.

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt_handler.php';

function require_admin() {
    $payload = decode_access_token();

    if ($payload['type'] !== 'admin') {
        error_response('Admin access required', 403);
    }

    return $payload['sub']; // admin_id
}
```

- [ ] **Step 3: Create rate_limit.php**

```php
<?php
// api/middleware/rate_limit.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/response.php';

function check_rate_limit($endpoint, $max_hits, $window_seconds) {
    $pdo = get_db_connection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $now = time();
    $window_start = date('Y-m-d H:i:s', $now - $window_seconds);

    // Clean old entries
    $pdo->prepare("DELETE FROM rate_limits WHERE window_start < :cutoff")
        ->execute([':cutoff' => $window_start]);

    // Count hits in current window
    $stmt = $pdo->prepare(
        "SELECT SUM(hits) as total FROM rate_limits
         WHERE ip_address = :ip AND endpoint = :ep AND window_start >= :ws"
    );
    $stmt->execute([':ip' => $ip, ':ep' => $endpoint, ':ws' => $window_start]);
    $total = (int)($stmt->fetch()['total'] ?? 0);

    if ($total >= $max_hits) {
        $retry_after = $window_seconds - ($now - strtotime($window_start));
        header("Retry-After: {$retry_after}");
        error_response('Rate limit exceeded. Try again later.', 429);
    }

    // Record this hit
    $stmt2 = $pdo->prepare(
        "INSERT INTO rate_limits (ip_address, endpoint, hits, window_start)
         VALUES (:ip, :ep, 1, NOW())
         ON DUPLICATE KEY UPDATE hits = hits + 1"
    );
    $stmt2->execute([':ip' => $ip, ':ep' => $endpoint]);
}

function rate_limit_login() {
    check_rate_limit('login', RATE_LIMIT_LOGIN, RATE_LIMIT_LOGIN_WINDOW);
}

function rate_limit_attendance() {
    check_rate_limit('attendance', RATE_LIMIT_ATTENDANCE, RATE_LIMIT_ATTENDANCE_WINDOW);
}

function rate_limit_general() {
    check_rate_limit('general', RATE_LIMIT_GENERAL, RATE_LIMIT_GENERAL_WINDOW);
}
```

- [ ] **Step 4: Commit**

```bash
git add api/middleware/
git commit -m "feat: add auth, admin auth, and rate limiting middleware"
```

---

### Task 6: Router + .htaccess

**Files:**
- Create: `api/index.php`
- Create: `api/.htaccess`

- [ ] **Step 1: Create .htaccess**

```apache
# api/.htaccess
RewriteEngine On

# Pass Authorization header through (GoDaddy shared hosting fix)
RewriteCond %{HTTP:Authorization} ^(.+)$
RewriteRule .* - [E=HTTP_AUTHORIZATION:%1]

# Route all requests to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

- [ ] **Step 2: Create index.php router**

```php
<?php
// api/index.php
// Simple file-based router

require_once __DIR__ . '/config/cors.php';
set_cors_headers();

require_once __DIR__ . '/helpers/response.php';

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string and base path
$uri = parse_url($uri, PHP_URL_PATH);

// Remove /api/ prefix if present (adjust based on your hosting path)
$uri = preg_replace('#^/api/#', '/', $uri);
$uri = '/' . trim($uri, '/');

// Map URI to endpoint file
$endpoint_map = [
    // Auth
    'POST /auth/login'             => 'auth/login.php',
    'POST /auth/admin_login'       => 'auth/admin_login.php',
    'POST /auth/refresh'           => 'auth/refresh.php',
    'POST /auth/register_device'   => 'auth/register_device.php',

    // Branches
    'POST /branches/create'        => 'branches/create.php',
    'GET /branches/list'           => 'branches/list.php',
    'PUT /branches/update'         => 'branches/update.php',
    'DELETE /branches/delete'      => 'branches/delete.php',

    // Employees
    'POST /employees/create'       => 'employees/create.php',
    'GET /employees/list'          => 'employees/list.php',
    'GET /employees/view'          => 'employees/view.php',
    'PUT /employees/update'        => 'employees/update.php',
    'DELETE /employees/delete'     => 'employees/delete.php',
    'POST /employees/reset_device' => 'employees/reset_device.php',
    'GET /employees/profile'       => 'employees/profile.php',

    // Attendance
    'POST /attendance/clock_in'    => 'attendance/clock_in.php',
    'POST /attendance/clock_out'   => 'attendance/clock_out.php',
    'GET /attendance/today'        => 'attendance/today.php',
    'GET /attendance/history'      => 'attendance/history.php',
    'GET /attendance/report'       => 'attendance/report.php',

    // Leaves
    'POST /leaves/apply'           => 'leaves/apply.php',
    'POST /leaves/cancel'          => 'leaves/cancel.php',
    'GET /leaves/balance'          => 'leaves/balance.php',
    'GET /leaves/history'          => 'leaves/history.php',
    'GET /leaves/pending'          => 'leaves/pending.php',
    'POST /leaves/approve'         => 'leaves/approve.php',
    'POST /leaves/reject'          => 'leaves/reject.php',

    // Salary
    'POST /salary/generate'        => 'salary/generate.php',
    'GET /salary/slips'            => 'salary/slips.php',
    'GET /salary/slip_detail'      => 'salary/slip_detail.php',

    // Holidays
    'POST /holidays/create'        => 'holidays/create.php',
    'GET /holidays/list'           => 'holidays/list.php',
    'DELETE /holidays/delete'      => 'holidays/delete.php',

    // Notifications
    'GET /notifications/list'      => 'notifications/list.php',
    'POST /notifications/mark_read' => 'notifications/mark_read.php',

    // Leave Policies
    'POST /leave_policies/set'     => 'leave_policies/set.php',
    'GET /leave_policies/view'     => 'leave_policies/view.php',
];

$route_key = $method . ' ' . $uri;

if (isset($endpoint_map[$route_key])) {
    $file = __DIR__ . '/endpoints/' . $endpoint_map[$route_key];
    if (file_exists($file)) {
        require $file;
    } else {
        error_response('Endpoint not implemented', 501);
    }
} else {
    error_response('Route not found: ' . $method . ' ' . $uri, 404);
}
```

- [ ] **Step 3: Commit**

```bash
git add api/index.php api/.htaccess
git commit -m "feat: add PHP router and .htaccess for URL rewriting"
```

---

### Task 7: Auth Endpoints (Login, Admin Login, Refresh, Device Registration)

**Files:**
- Create: `api/endpoints/auth/login.php`
- Create: `api/endpoints/auth/admin_login.php`
- Create: `api/endpoints/auth/refresh.php`
- Create: `api/endpoints/auth/register_device.php`

- [ ] **Step 1: Create login.php**

```php
<?php
// api/endpoints/auth/login.php
// POST /auth/login — Employee login

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/jwt_handler.php';
require_once __DIR__ . '/../../middleware/rate_limit.php';

rate_limit_login();

$input = get_json_input();
validate_required($input, ['username', 'password']);

$username = sanitize_string($input['username']);
$password = $input['password'];
$device_id = sanitize_string($input['device_id'] ?? '');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT id, full_name, password, device_id, branch_id, is_active
     FROM employees WHERE username = :username LIMIT 1"
);
$stmt->execute([':username' => $username]);
$employee = $stmt->fetch();

if (!$employee || !password_verify($password, $employee['password'])) {
    error_response('Invalid username or password', 401);
}

if (!$employee['is_active']) {
    error_response('Account is deactivated. Contact admin.', 403);
}

// Device binding check
$needs_device_registration = false;
if (empty($employee['device_id'])) {
    // First login — device will be bound
    $needs_device_registration = true;
} elseif (!empty($device_id) && $employee['device_id'] !== $device_id) {
    error_response('Device not registered. This account is bound to another device. Contact admin to reset.', 403);
}

// Generate tokens
$tokens = generate_tokens($employee['id'], 'employee');

success_response('Login successful', [
    'employee_id' => $employee['id'],
    'full_name' => $employee['full_name'],
    'branch_id' => $employee['branch_id'],
    'needs_device_registration' => $needs_device_registration,
    'access_token' => $tokens['access_token'],
    'refresh_token' => $tokens['refresh_token'],
    'expires_in' => $tokens['expires_in'],
]);
```

- [ ] **Step 2: Create admin_login.php**

```php
<?php
// api/endpoints/auth/admin_login.php
// POST /auth/admin_login — Admin login

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/jwt_handler.php';
require_once __DIR__ . '/../../middleware/rate_limit.php';

rate_limit_login();

$input = get_json_input();
validate_required($input, ['email', 'password']);

$email = sanitize_string($input['email']);
$password = $input['password'];

$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT id, name, password FROM admins WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    error_response('Invalid email or password', 401);
}

$tokens = generate_tokens($admin['id'], 'admin');

success_response('Admin login successful', [
    'admin_id' => $admin['id'],
    'name' => $admin['name'],
    'access_token' => $tokens['access_token'],
    'refresh_token' => $tokens['refresh_token'],
    'expires_in' => $tokens['expires_in'],
]);
```

- [ ] **Step 3: Create refresh.php**

```php
<?php
// api/endpoints/auth/refresh.php
// POST /auth/refresh — Refresh access token

require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/jwt_handler.php';

$input = get_json_input();
validate_required($input, ['refresh_token']);

$tokens = refresh_access_token($input['refresh_token']);

success_response('Token refreshed', $tokens);
```

- [ ] **Step 4: Create register_device.php**

```php
<?php
// api/endpoints/auth/register_device.php
// POST /auth/register_device — Bind device to employee on first login

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['device_id']);
$device_id = sanitize_string($input['device_id']);

$pdo = get_db_connection();

// Check if device is already bound
$stmt = $pdo->prepare("SELECT device_id FROM employees WHERE id = :id");
$stmt->execute([':id' => $employee_id]);
$employee = $stmt->fetch();

if (!empty($employee['device_id'])) {
    error_response('Device already registered. Contact admin to reset.', 409);
}

// Bind device
$stmt2 = $pdo->prepare("UPDATE employees SET device_id = :device_id WHERE id = :id");
$stmt2->execute([':device_id' => $device_id, ':id' => $employee_id]);

// Also store FCM token if provided
$fcm_token = sanitize_string($input['fcm_token'] ?? '');
if (!empty($fcm_token)) {
    $stmt3 = $pdo->prepare("UPDATE employees SET fcm_token = :token WHERE id = :id");
    $stmt3->execute([':token' => $fcm_token, ':id' => $employee_id]);
}

success_response('Device registered successfully');
```

- [ ] **Step 5: Commit**

```bash
git add api/endpoints/auth/
git commit -m "feat: add auth endpoints (login, admin login, refresh, device registration)"
```

---

### Task 8: Branch CRUD Endpoints

**Files:**
- Create: `api/endpoints/branches/create.php`
- Create: `api/endpoints/branches/list.php`
- Create: `api/endpoints/branches/update.php`
- Create: `api/endpoints/branches/delete.php`

- [ ] **Step 1: Create branches/create.php**

```php
<?php
// api/endpoints/branches/create.php
// POST /branches/create — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['name', 'address', 'latitude', 'longitude', 'radius_meters']);
validate_positive_number($input['latitude'], 'latitude');
validate_positive_number($input['radius_meters'], 'radius_meters');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "INSERT INTO branches (name, address, latitude, longitude, radius_meters)
     VALUES (:name, :address, :lat, :lng, :radius)"
);
$stmt->execute([
    ':name' => sanitize_string($input['name']),
    ':address' => sanitize_string($input['address']),
    ':lat' => $input['latitude'],
    ':lng' => $input['longitude'],
    ':radius' => (int)$input['radius_meters'],
]);

success_response('Branch created', ['id' => $pdo->lastInsertId()], 201);
```

- [ ] **Step 2: Create branches/list.php**

```php
<?php
// api/endpoints/branches/list.php
// GET /branches/list — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$pdo = get_db_connection();
$stmt = $pdo->query(
    "SELECT b.*, COUNT(e.id) as employee_count
     FROM branches b
     LEFT JOIN employees e ON e.branch_id = b.id AND e.is_active = 1
     WHERE b.is_active = 1
     GROUP BY b.id
     ORDER BY b.name"
);

json_response($stmt->fetchAll());
```

- [ ] **Step 3: Create branches/update.php**

```php
<?php
// api/endpoints/branches/update.php
// PUT /branches/update — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['id']);

$pdo = get_db_connection();

// Build dynamic update
$fields = [];
$params = [':id' => (int)$input['id']];

$allowed = ['name', 'address', 'latitude', 'longitude', 'radius_meters'];
foreach ($allowed as $field) {
    if (isset($input[$field])) {
        $fields[] = "{$field} = :{$field}";
        $params[":{$field}"] = $field === 'radius_meters' ? (int)$input[$field] : sanitize_string($input[$field]);
    }
}

if (empty($fields)) {
    error_response('No fields to update', 400);
}

$sql = "UPDATE branches SET " . implode(', ', $fields) . " WHERE id = :id AND is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() === 0) {
    error_response('Branch not found', 404);
}

success_response('Branch updated');
```

- [ ] **Step 4: Create branches/delete.php**

```php
<?php
// api/endpoints/branches/delete.php
// DELETE /branches/delete — Admin only (soft delete)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    error_response('Branch ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("UPDATE branches SET is_active = 0 WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => (int)$id]);

if ($stmt->rowCount() === 0) {
    error_response('Branch not found', 404);
}

success_response('Branch deactivated');
```

- [ ] **Step 5: Commit**

```bash
git add api/endpoints/branches/
git commit -m "feat: add branch CRUD endpoints"
```

---

### Task 9: Employee CRUD Endpoints

**Files:**
- Create: `api/endpoints/employees/create.php`
- Create: `api/endpoints/employees/list.php`
- Create: `api/endpoints/employees/view.php`
- Create: `api/endpoints/employees/update.php`
- Create: `api/endpoints/employees/delete.php`
- Create: `api/endpoints/employees/reset_device.php`
- Create: `api/endpoints/employees/profile.php`

- [ ] **Step 1: Create employees/create.php**

```php
<?php
// api/endpoints/employees/create.php
// POST /employees/create — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/encryption.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['full_name', 'branch_id', 'date_of_joining', 'monthly_salary', 'username', 'password']);
validate_date($input['date_of_joining'], 'date_of_joining');
validate_positive_number($input['monthly_salary'], 'monthly_salary');

$pdo = get_db_connection();

// Check username uniqueness
$stmt = $pdo->prepare("SELECT id FROM employees WHERE username = :u");
$stmt->execute([':u' => sanitize_string($input['username'])]);
if ($stmt->fetch()) {
    error_response('Username already exists', 409);
}

// Generate employee code (KE-001, KE-002, etc.)
$stmt2 = $pdo->query("SELECT MAX(id) as max_id FROM employees");
$max_id = (int)($stmt2->fetch()['max_id'] ?? 0);
$employee_code = EMPLOYEE_CODE_PREFIX . '-' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);

// Hash password
$hashed_password = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);

// Encrypt sensitive fields
$pan = isset($input['pan_number']) ? aes_encrypt(sanitize_string($input['pan_number'])) : null;
$aadhar = isset($input['aadhar_number']) ? aes_encrypt(sanitize_string($input['aadhar_number'])) : null;

$stmt3 = $pdo->prepare(
    "INSERT INTO employees (
        employee_code, full_name, email, phone, designation, department,
        branch_id, date_of_joining, employment_type, monthly_salary,
        bank_account, ifsc_code, pan_number, aadhar_number,
        username, password
    ) VALUES (
        :code, :name, :email, :phone, :designation, :department,
        :branch_id, :doj, :emp_type, :salary,
        :bank, :ifsc, :pan, :aadhar,
        :username, :password
    )"
);
$stmt3->execute([
    ':code' => $employee_code,
    ':name' => sanitize_string($input['full_name']),
    ':email' => sanitize_string($input['email'] ?? ''),
    ':phone' => sanitize_string($input['phone'] ?? ''),
    ':designation' => sanitize_string($input['designation'] ?? ''),
    ':department' => sanitize_string($input['department'] ?? ''),
    ':branch_id' => (int)$input['branch_id'],
    ':doj' => $input['date_of_joining'],
    ':emp_type' => $input['employment_type'] ?? 'full',
    ':salary' => $input['monthly_salary'],
    ':bank' => sanitize_string($input['bank_account'] ?? ''),
    ':ifsc' => sanitize_string($input['ifsc_code'] ?? ''),
    ':pan' => $pan,
    ':aadhar' => $aadhar,
    ':username' => sanitize_string($input['username']),
    ':password' => $hashed_password,
]);

$new_id = $pdo->lastInsertId();

// Initialize leave balances for current year from branch policy
$year = date('Y');
$policy_stmt = $pdo->prepare(
    "SELECT leave_type, annual_quota FROM leave_policies WHERE branch_id = :bid"
);
$policy_stmt->execute([':bid' => (int)$input['branch_id']]);
$policies = $policy_stmt->fetchAll();

$balance_stmt = $pdo->prepare(
    "INSERT INTO leave_balances (employee_id, leave_type, year, total_quota, used, carried_forward)
     VALUES (:eid, :type, :year, :quota, 0, 0)"
);
foreach ($policies as $policy) {
    $balance_stmt->execute([
        ':eid' => $new_id,
        ':type' => $policy['leave_type'],
        ':year' => $year,
        ':quota' => $policy['annual_quota'],
    ]);
}

success_response('Employee created', [
    'id' => $new_id,
    'employee_code' => $employee_code,
], 201);
```

- [ ] **Step 2: Create employees/list.php**

```php
<?php
// api/endpoints/employees/list.php
// GET /employees/list — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$pdo = get_db_connection();

$branch_id = $_GET['branch_id'] ?? null;

$sql = "SELECT e.id, e.employee_code, e.full_name, e.email, e.phone,
               e.designation, e.department, e.date_of_joining, e.employment_type,
               e.monthly_salary, e.is_active, e.device_id IS NOT NULL as device_bound,
               b.name as branch_name
        FROM employees e
        JOIN branches b ON b.id = e.branch_id";
$params = [];

if ($branch_id) {
    $sql .= " WHERE e.branch_id = :bid";
    $params[':bid'] = (int)$branch_id;
}

$sql .= " ORDER BY e.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

json_response($stmt->fetchAll());
```

- [ ] **Step 3: Create employees/view.php**

```php
<?php
// api/endpoints/employees/view.php
// GET /employees/view?id=X — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/encryption.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    error_response('Employee ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT e.*, b.name as branch_name
     FROM employees e
     JOIN branches b ON b.id = e.branch_id
     WHERE e.id = :id"
);
$stmt->execute([':id' => (int)$id]);
$employee = $stmt->fetch();

if (!$employee) {
    error_response('Employee not found', 404);
}

// Decrypt sensitive fields
$employee['pan_number'] = $employee['pan_number'] ? aes_decrypt($employee['pan_number']) : null;
$employee['aadhar_number'] = $employee['aadhar_number'] ? aes_decrypt($employee['aadhar_number']) : null;

// Remove password hash from response
unset($employee['password']);

json_response($employee);
```

- [ ] **Step 4: Create employees/update.php**

```php
<?php
// api/endpoints/employees/update.php
// PUT /employees/update — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/encryption.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['id']);

$pdo = get_db_connection();
$fields = [];
$params = [':id' => (int)$input['id']];

$plain_fields = ['full_name', 'email', 'phone', 'designation', 'department',
                 'branch_id', 'date_of_joining', 'employment_type', 'monthly_salary',
                 'bank_account', 'ifsc_code', 'is_active'];

foreach ($plain_fields as $field) {
    if (isset($input[$field])) {
        $fields[] = "{$field} = :{$field}";
        $params[":{$field}"] = is_numeric($input[$field]) ? $input[$field] : sanitize_string($input[$field]);
    }
}

// Encrypted fields
if (isset($input['pan_number'])) {
    $fields[] = "pan_number = :pan";
    $params[':pan'] = aes_encrypt(sanitize_string($input['pan_number']));
}
if (isset($input['aadhar_number'])) {
    $fields[] = "aadhar_number = :aadhar";
    $params[':aadhar'] = aes_encrypt(sanitize_string($input['aadhar_number']));
}

// Password update
if (isset($input['password']) && !empty($input['password'])) {
    $fields[] = "password = :password";
    $params[':password'] = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
}

if (empty($fields)) {
    error_response('No fields to update', 400);
}

$sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() === 0) {
    error_response('Employee not found', 404);
}

success_response('Employee updated');
```

- [ ] **Step 5: Create employees/delete.php**

```php
<?php
// api/endpoints/employees/delete.php
// DELETE /employees/delete?id=X — Admin only (soft delete)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    error_response('Employee ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("UPDATE employees SET is_active = 0 WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => (int)$id]);

if ($stmt->rowCount() === 0) {
    error_response('Employee not found', 404);
}

success_response('Employee deactivated');
```

- [ ] **Step 6: Create employees/reset_device.php**

```php
<?php
// api/endpoints/employees/reset_device.php
// POST /employees/reset_device — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['employee_id']);

$pdo = get_db_connection();
$stmt = $pdo->prepare("UPDATE employees SET device_id = NULL WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => (int)$input['employee_id']]);

if ($stmt->rowCount() === 0) {
    error_response('Employee not found', 404);
}

success_response('Device binding reset. Employee can register a new device on next login.');
```

- [ ] **Step 7: Create employees/profile.php**

```php
<?php
// api/endpoints/employees/profile.php
// GET /employees/profile — Employee's own profile

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/encryption.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT e.id, e.employee_code, e.full_name, e.email, e.phone, e.profile_photo,
            e.designation, e.department, e.date_of_joining, e.employment_type,
            e.monthly_salary, e.bank_account, e.ifsc_code, e.pan_number, e.aadhar_number,
            b.name as branch_name
     FROM employees e
     JOIN branches b ON b.id = e.branch_id
     WHERE e.id = :id"
);
$stmt->execute([':id' => $employee_id]);
$employee = $stmt->fetch();

// Decrypt sensitive fields
$employee['pan_number'] = $employee['pan_number'] ? aes_decrypt($employee['pan_number']) : null;
$employee['aadhar_number'] = $employee['aadhar_number'] ? aes_decrypt($employee['aadhar_number']) : null;

json_response($employee);
```

- [ ] **Step 8: Commit**

```bash
git add api/endpoints/employees/
git commit -m "feat: add employee CRUD endpoints with encryption and device reset"
```

---

### Task 10: Attendance Endpoints (Clock In, Clock Out, Today, History, Report)

**Files:**
- Create: `api/endpoints/attendance/clock_in.php`
- Create: `api/endpoints/attendance/clock_out.php`
- Create: `api/endpoints/attendance/today.php`
- Create: `api/endpoints/attendance/history.php`
- Create: `api/endpoints/attendance/report.php`

- [ ] **Step 1: Create attendance/clock_in.php**

```php
<?php
// api/endpoints/attendance/clock_in.php
// POST /attendance/clock_in — Geofence-verified attendance

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/geofence.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rate_limit.php';

rate_limit_attendance();
$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['latitude', 'longitude', 'device_id']);

$lat = (float)$input['latitude'];
$lng = (float)$input['longitude'];
$device_id = sanitize_string($input['device_id']);

$pdo = get_db_connection();

// 1. Verify device binding
$stmt = $pdo->prepare("SELECT device_id, branch_id FROM employees WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    error_response('Employee not found', 404);
}
if ($employee['device_id'] !== $device_id) {
    error_response('Device not registered. Contact admin.', 403);
}

// 2. Check no duplicate clock-in today
$today = gmdate('Y-m-d'); // UTC date
$stmt2 = $pdo->prepare(
    "SELECT id FROM attendance_logs WHERE employee_id = :eid AND date = :date"
);
$stmt2->execute([':eid' => $employee_id, ':date' => $today]);
if ($stmt2->fetch()) {
    error_response('Already clocked in today', 409);
}

// 3. Geofence check
$stmt3 = $pdo->prepare(
    "SELECT latitude, longitude, radius_meters FROM branches WHERE id = :bid AND is_active = 1"
);
$stmt3->execute([':bid' => $employee['branch_id']]);
$branch = $stmt3->fetch();

if (!$branch) {
    error_response('Branch not found', 404);
}

if (!is_within_geofence($lat, $lng, $branch['latitude'], $branch['longitude'], $branch['radius_meters'])) {
    $distance = round(haversine_distance($lat, $lng, $branch['latitude'], $branch['longitude']));
    error_response("Cannot mark attendance. You are {$distance}m from office. Required: within {$branch['radius_meters']}m.", 403);
}

// 4. Determine if late
$now_utc = gmdate('Y-m-d H:i:s');
$now_ist = new DateTime($now_utc, new DateTimeZone('UTC'));
$now_ist->setTimezone(new DateTimeZone(APP_TIMEZONE));
$hour = (int)$now_ist->format('H');
$minute = (int)$now_ist->format('i');

$status = 'present';
if ($hour > DEFAULT_LATE_HOUR || ($hour === DEFAULT_LATE_HOUR && $minute > DEFAULT_LATE_MINUTE)) {
    $status = 'late';
}

// 5. Record attendance
$stmt4 = $pdo->prepare(
    "INSERT INTO attendance_logs (employee_id, date, clock_in, clock_in_lat, clock_in_lng, device_id, status)
     VALUES (:eid, :date, :clock_in, :lat, :lng, :device, :status)"
);
$stmt4->execute([
    ':eid' => $employee_id,
    ':date' => $today,
    ':clock_in' => $now_utc,
    ':lat' => $lat,
    ':lng' => $lng,
    ':device' => $device_id,
    ':status' => $status,
]);

success_response('Attendance marked', [
    'clock_in' => $now_ist->format('h:i A'),
    'status' => $status,
    'date' => $today,
]);
```

- [ ] **Step 2: Create attendance/clock_out.php**

```php
<?php
// api/endpoints/attendance/clock_out.php
// POST /attendance/clock_out — Geofence-verified clock out

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/geofence.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rate_limit.php';

rate_limit_attendance();
$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['latitude', 'longitude', 'device_id']);

$lat = (float)$input['latitude'];
$lng = (float)$input['longitude'];
$device_id = sanitize_string($input['device_id']);

$pdo = get_db_connection();

// 1. Verify device
$stmt = $pdo->prepare("SELECT device_id, branch_id FROM employees WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $employee_id]);
$employee = $stmt->fetch();

if (!$employee || $employee['device_id'] !== $device_id) {
    error_response('Device not registered. Contact admin.', 403);
}

// 2. Find today's clock-in record
$today = gmdate('Y-m-d');
$stmt2 = $pdo->prepare(
    "SELECT id, clock_in, clock_out FROM attendance_logs
     WHERE employee_id = :eid AND date = :date"
);
$stmt2->execute([':eid' => $employee_id, ':date' => $today]);
$record = $stmt2->fetch();

if (!$record) {
    error_response('No clock-in found for today. Clock in first.', 400);
}
if ($record['clock_out'] !== null) {
    error_response('Already clocked out today', 409);
}

// 3. Geofence check
$stmt3 = $pdo->prepare(
    "SELECT latitude, longitude, radius_meters FROM branches WHERE id = :bid AND is_active = 1"
);
$stmt3->execute([':bid' => $employee['branch_id']]);
$branch = $stmt3->fetch();

if (!is_within_geofence($lat, $lng, $branch['latitude'], $branch['longitude'], $branch['radius_meters'])) {
    $distance = round(haversine_distance($lat, $lng, $branch['latitude'], $branch['longitude']));
    error_response("Cannot clock out. You are {$distance}m from office. Required: within {$branch['radius_meters']}m.", 403);
}

// 4. Calculate work hours
$now_utc = gmdate('Y-m-d H:i:s');
$clock_in_time = new DateTime($record['clock_in'], new DateTimeZone('UTC'));
$clock_out_time = new DateTime($now_utc, new DateTimeZone('UTC'));
$diff = $clock_out_time->diff($clock_in_time);
$work_hours = round($diff->h + ($diff->i / 60), 2);

// Check if half day (less than 4 hours)
$status_update = '';
if ($work_hours < 4) {
    $status_update = ", status = 'half_day'";
}

// 5. Update record
$stmt4 = $pdo->prepare(
    "UPDATE attendance_logs SET clock_out = :out, clock_out_lat = :lat,
     clock_out_lng = :lng, work_hours = :hours {$status_update}
     WHERE id = :id"
);
$stmt4->execute([
    ':out' => $now_utc,
    ':lat' => $lat,
    ':lng' => $lng,
    ':hours' => $work_hours,
    ':id' => $record['id'],
]);

$now_ist = new DateTime($now_utc, new DateTimeZone('UTC'));
$now_ist->setTimezone(new DateTimeZone(APP_TIMEZONE));

success_response('Clocked out', [
    'clock_out' => $now_ist->format('h:i A'),
    'work_hours' => $work_hours,
]);
```

- [ ] **Step 3: Create attendance/today.php**

```php
<?php
// api/endpoints/attendance/today.php
// GET /attendance/today — Today's attendance status

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();
$today = gmdate('Y-m-d');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT * FROM attendance_logs WHERE employee_id = :eid AND date = :date"
);
$stmt->execute([':eid' => $employee_id, ':date' => $today]);
$record = $stmt->fetch();

if (!$record) {
    json_response([
        'date' => $today,
        'clocked_in' => false,
        'clocked_out' => false,
    ]);
}

// Convert UTC times to IST for display
$clock_in_ist = null;
$clock_out_ist = null;

if ($record['clock_in']) {
    $dt = new DateTime($record['clock_in'], new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
    $clock_in_ist = $dt->format('h:i A');
}
if ($record['clock_out']) {
    $dt = new DateTime($record['clock_out'], new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
    $clock_out_ist = $dt->format('h:i A');
}

json_response([
    'date' => $today,
    'clocked_in' => true,
    'clocked_out' => $record['clock_out'] !== null,
    'clock_in_time' => $clock_in_ist,
    'clock_out_time' => $clock_out_ist,
    'status' => $record['status'],
    'work_hours' => $record['work_hours'],
]);
```

- [ ] **Step 4: Create attendance/history.php**

```php
<?php
// api/endpoints/attendance/history.php
// GET /attendance/history?month=4&year=2026 — Monthly attendance

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT date, clock_in, clock_out, status, work_hours
     FROM attendance_logs
     WHERE employee_id = :eid AND MONTH(date) = :month AND YEAR(date) = :year
     ORDER BY date ASC"
);
$stmt->execute([':eid' => $employee_id, ':month' => (int)$month, ':year' => (int)$year]);
$records = $stmt->fetchAll();

// Convert times to IST
foreach ($records as &$r) {
    if ($r['clock_in']) {
        $dt = new DateTime($r['clock_in'], new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
        $r['clock_in'] = $dt->format('h:i A');
    }
    if ($r['clock_out']) {
        $dt = new DateTime($r['clock_out'], new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
        $r['clock_out'] = $dt->format('h:i A');
    }
}

// Summary
$present = count(array_filter($records, fn($r) => in_array($r['status'], ['present', 'late'])));
$late = count(array_filter($records, fn($r) => $r['status'] === 'late'));
$half_day = count(array_filter($records, fn($r) => $r['status'] === 'half_day'));

json_response([
    'month' => (int)$month,
    'year' => (int)$year,
    'summary' => [
        'present' => $present,
        'late' => $late,
        'half_day' => $half_day,
        'total_records' => count($records),
    ],
    'records' => $records,
]);
```

- [ ] **Step 5: Create attendance/report.php**

```php
<?php
// api/endpoints/attendance/report.php
// GET /attendance/report?date=2026-04-01&branch_id=1 — Daily report (admin)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$date = $_GET['date'] ?? gmdate('Y-m-d');
$branch_id = $_GET['branch_id'] ?? null;

$pdo = get_db_connection();

// Get all active employees (optionally filtered by branch)
$emp_sql = "SELECT e.id, e.employee_code, e.full_name, e.designation, e.department, b.name as branch_name
            FROM employees e
            JOIN branches b ON b.id = e.branch_id
            WHERE e.is_active = 1";
$params = [];
if ($branch_id) {
    $emp_sql .= " AND e.branch_id = :bid";
    $params[':bid'] = (int)$branch_id;
}
$emp_sql .= " ORDER BY b.name, e.full_name";
$emp_stmt = $pdo->prepare($emp_sql);
$emp_stmt->execute($params);
$employees = $emp_stmt->fetchAll();

// Get attendance records for the date
$att_stmt = $pdo->prepare(
    "SELECT employee_id, clock_in, clock_out, status, work_hours
     FROM attendance_logs WHERE date = :date"
);
$att_stmt->execute([':date' => $date]);
$attendance = [];
foreach ($att_stmt->fetchAll() as $a) {
    $attendance[$a['employee_id']] = $a;
}

// Get approved leaves for the date
$leave_stmt = $pdo->prepare(
    "SELECT lr.employee_id, lr.leave_type, lr.reason
     FROM leave_requests lr
     WHERE lr.status = 'approved' AND :date BETWEEN lr.start_date AND lr.end_date"
);
$leave_stmt->execute([':date' => $date]);
$leaves = [];
foreach ($leave_stmt->fetchAll() as $l) {
    $leaves[$l['employee_id']] = $l;
}

// Build report
$report = [];
$counts = ['present' => 0, 'late' => 0, 'half_day' => 0, 'on_leave' => 0, 'absent' => 0];

foreach ($employees as $emp) {
    $entry = [
        'employee_id' => $emp['id'],
        'employee_code' => $emp['employee_code'],
        'full_name' => $emp['full_name'],
        'designation' => $emp['designation'],
        'branch_name' => $emp['branch_name'],
    ];

    if (isset($attendance[$emp['id']])) {
        $att = $attendance[$emp['id']];
        $entry['status'] = $att['status'];
        $entry['clock_in'] = $att['clock_in'];
        $entry['clock_out'] = $att['clock_out'];
        $entry['work_hours'] = $att['work_hours'];

        // Convert to IST
        if ($entry['clock_in']) {
            $dt = new DateTime($entry['clock_in'], new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
            $entry['clock_in'] = $dt->format('h:i A');
        }
        if ($entry['clock_out']) {
            $dt = new DateTime($entry['clock_out'], new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
            $entry['clock_out'] = $dt->format('h:i A');
        }

        $counts[$att['status']]++;
    } elseif (isset($leaves[$emp['id']])) {
        $entry['status'] = 'on_leave';
        $entry['leave_type'] = $leaves[$emp['id']]['leave_type'];
        $entry['reason'] = $leaves[$emp['id']]['reason'];
        $counts['on_leave']++;
    } else {
        $entry['status'] = 'absent';
        $counts['absent']++;
    }

    $report[] = $entry;
}

json_response([
    'date' => $date,
    'summary' => $counts,
    'total_employees' => count($employees),
    'records' => $report,
]);
```

- [ ] **Step 6: Commit**

```bash
git add api/endpoints/attendance/
git commit -m "feat: add attendance endpoints (clock in/out with geofence, today, history, daily report)"
```

---

### Task 11: Leave Endpoints

**Files:**
- Create: `api/endpoints/leaves/apply.php`
- Create: `api/endpoints/leaves/cancel.php`
- Create: `api/endpoints/leaves/balance.php`
- Create: `api/endpoints/leaves/history.php`
- Create: `api/endpoints/leaves/pending.php`
- Create: `api/endpoints/leaves/approve.php`
- Create: `api/endpoints/leaves/reject.php`

- [ ] **Step 1: Create leaves/apply.php**

```php
<?php
// api/endpoints/leaves/apply.php
// POST /leaves/apply

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['leave_type', 'start_date', 'end_date']);
validate_enum($input['leave_type'], ['CL', 'SL', 'EL', 'CO', 'LWP'], 'leave_type');
validate_date($input['start_date'], 'start_date');
validate_date($input['end_date'], 'end_date');

if ($input['start_date'] > $input['end_date']) {
    error_response('Start date must be before or equal to end date', 400);
}

$leave_type = $input['leave_type'];
$is_half_day = (int)($input['is_half_day'] ?? 0);
$year = date('Y', strtotime($input['start_date']));

// Calculate days
$start = new DateTime($input['start_date']);
$end = new DateTime($input['end_date']);
$days = $start->diff($end)->days + 1;
if ($is_half_day) {
    $days = 0.5;
}

$pdo = get_db_connection();

// Check balance (skip for LWP — unpaid is unlimited)
if ($leave_type !== 'LWP') {
    $stmt = $pdo->prepare(
        "SELECT total_quota, used, carried_forward FROM leave_balances
         WHERE employee_id = :eid AND leave_type = :type AND year = :year"
    );
    $stmt->execute([':eid' => $employee_id, ':type' => $leave_type, ':year' => $year]);
    $balance = $stmt->fetch();

    if (!$balance) {
        error_response("No {$leave_type} policy configured for your branch this year", 400);
    }

    $remaining = ($balance['total_quota'] + $balance['carried_forward']) - $balance['used'];
    if ($days > $remaining) {
        error_response("Insufficient {$leave_type} balance. Remaining: {$remaining} days", 400);
    }
}

// Check for overlapping pending/approved leaves
$stmt2 = $pdo->prepare(
    "SELECT id FROM leave_requests
     WHERE employee_id = :eid AND status IN ('pending','approved')
     AND start_date <= :end AND end_date >= :start"
);
$stmt2->execute([
    ':eid' => $employee_id,
    ':start' => $input['start_date'],
    ':end' => $input['end_date'],
]);
if ($stmt2->fetch()) {
    error_response('Overlapping leave request exists for these dates', 409);
}

// Create request
$stmt3 = $pdo->prepare(
    "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, is_half_day, reason)
     VALUES (:eid, :type, :start, :end, :half, :reason)"
);
$stmt3->execute([
    ':eid' => $employee_id,
    ':type' => $leave_type,
    ':start' => $input['start_date'],
    ':end' => $input['end_date'],
    ':half' => $is_half_day,
    ':reason' => sanitize_string($input['reason'] ?? ''),
]);

success_response('Leave application submitted', ['id' => $pdo->lastInsertId()], 201);
```

- [ ] **Step 2: Create leaves/cancel.php**

```php
<?php
// api/endpoints/leaves/cancel.php
// POST /leaves/cancel

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['leave_id']);

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "DELETE FROM leave_requests
     WHERE id = :id AND employee_id = :eid AND status = 'pending'"
);
$stmt->execute([':id' => (int)$input['leave_id'], ':eid' => $employee_id]);

if ($stmt->rowCount() === 0) {
    error_response('Leave request not found or already processed', 404);
}

success_response('Leave request cancelled');
```

- [ ] **Step 3: Create leaves/balance.php**

```php
<?php
// api/endpoints/leaves/balance.php
// GET /leaves/balance?year=2026

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();
$year = $_GET['year'] ?? date('Y');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT leave_type, total_quota, used, carried_forward,
            (total_quota + carried_forward - used) as remaining
     FROM leave_balances
     WHERE employee_id = :eid AND year = :year"
);
$stmt->execute([':eid' => $employee_id, ':year' => (int)$year]);

json_response($stmt->fetchAll());
```

- [ ] **Step 4: Create leaves/history.php**

```php
<?php
// api/endpoints/leaves/history.php
// GET /leaves/history

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT id, leave_type, start_date, end_date, is_half_day, reason, status, admin_remarks, created_at
     FROM leave_requests
     WHERE employee_id = :eid
     ORDER BY created_at DESC"
);
$stmt->execute([':eid' => $employee_id]);

json_response($stmt->fetchAll());
```

- [ ] **Step 5: Create leaves/pending.php**

```php
<?php
// api/endpoints/leaves/pending.php
// GET /leaves/pending — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$pdo = get_db_connection();
$stmt = $pdo->query(
    "SELECT lr.*, e.full_name, e.employee_code, b.name as branch_name
     FROM leave_requests lr
     JOIN employees e ON e.id = lr.employee_id
     JOIN branches b ON b.id = e.branch_id
     WHERE lr.status = 'pending'
     ORDER BY lr.created_at ASC"
);

json_response($stmt->fetchAll());
```

- [ ] **Step 6: Create leaves/approve.php**

```php
<?php
// api/endpoints/leaves/approve.php
// POST /leaves/approve — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['leave_id']);

$pdo = get_db_connection();

// Get leave request
$stmt = $pdo->prepare(
    "SELECT * FROM leave_requests WHERE id = :id AND status = 'pending'"
);
$stmt->execute([':id' => (int)$input['leave_id']]);
$leave = $stmt->fetch();

if (!$leave) {
    error_response('Leave request not found or already processed', 404);
}

// Calculate days
$start = new DateTime($leave['start_date']);
$end = new DateTime($leave['end_date']);
$days = $start->diff($end)->days + 1;
if ($leave['is_half_day']) {
    $days = 0.5;
}

$year = date('Y', strtotime($leave['start_date']));

// Begin transaction
$pdo->beginTransaction();
try {
    // Update request status
    $stmt2 = $pdo->prepare(
        "UPDATE leave_requests SET status = 'approved', admin_remarks = :remarks WHERE id = :id"
    );
    $stmt2->execute([
        ':id' => $leave['id'],
        ':remarks' => sanitize_string($input['remarks'] ?? ''),
    ]);

    // Update leave balance (skip for LWP)
    if ($leave['leave_type'] !== 'LWP') {
        $stmt3 = $pdo->prepare(
            "UPDATE leave_balances SET used = used + :days
             WHERE employee_id = :eid AND leave_type = :type AND year = :year"
        );
        $stmt3->execute([
            ':days' => $days,
            ':eid' => $leave['employee_id'],
            ':type' => $leave['leave_type'],
            ':year' => $year,
        ]);
    }

    // Create notification for employee
    $stmt4 = $pdo->prepare(
        "INSERT INTO notifications (employee_id, title, body, type)
         VALUES (:eid, :title, :body, 'leave')"
    );
    $stmt4->execute([
        ':eid' => $leave['employee_id'],
        ':title' => 'Leave Approved',
        ':body' => "{$leave['leave_type']} leave from {$leave['start_date']} to {$leave['end_date']} has been approved.",
    ]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_response('Failed to approve leave', 500);
}

success_response('Leave approved');
```

- [ ] **Step 7: Create leaves/reject.php**

```php
<?php
// api/endpoints/leaves/reject.php
// POST /leaves/reject — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['leave_id']);

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT * FROM leave_requests WHERE id = :id AND status = 'pending'"
);
$stmt->execute([':id' => (int)$input['leave_id']]);
$leave = $stmt->fetch();

if (!$leave) {
    error_response('Leave request not found or already processed', 404);
}

// Update status
$stmt2 = $pdo->prepare(
    "UPDATE leave_requests SET status = 'rejected', admin_remarks = :remarks WHERE id = :id"
);
$stmt2->execute([
    ':id' => $leave['id'],
    ':remarks' => sanitize_string($input['remarks'] ?? ''),
]);

// Notify employee
$stmt3 = $pdo->prepare(
    "INSERT INTO notifications (employee_id, title, body, type)
     VALUES (:eid, :title, :body, 'leave')"
);
$stmt3->execute([
    ':eid' => $leave['employee_id'],
    ':title' => 'Leave Rejected',
    ':body' => "{$leave['leave_type']} leave from {$leave['start_date']} to {$leave['end_date']} has been rejected. Reason: " . ($input['remarks'] ?? 'No reason provided'),
]);

success_response('Leave rejected');
```

- [ ] **Step 8: Commit**

```bash
git add api/endpoints/leaves/
git commit -m "feat: add leave endpoints (apply, cancel, balance, history, approve, reject)"
```

---

### Task 12: Salary Endpoints

**Files:**
- Create: `api/endpoints/salary/generate.php`
- Create: `api/endpoints/salary/slips.php`
- Create: `api/endpoints/salary/slip_detail.php`

- [ ] **Step 1: Create salary/generate.php**

```php
<?php
// api/endpoints/salary/generate.php
// POST /salary/generate — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['branch_id', 'month', 'year']);

$branch_id = (int)$input['branch_id'];
$month = (int)$input['month'];
$year = (int)$input['year'];

if ($month < 1 || $month > 12) {
    error_response('Invalid month', 400);
}

$pdo = get_db_connection();

// Get all active employees in this branch
$stmt = $pdo->prepare(
    "SELECT id, full_name, monthly_salary FROM employees
     WHERE branch_id = :bid AND is_active = 1"
);
$stmt->execute([':bid' => $branch_id]);
$employees = $stmt->fetchAll();

if (empty($employees)) {
    error_response('No active employees in this branch', 400);
}

// Calculate total working days in the month (excluding Sundays and holidays)
$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$working_days = 0;
for ($d = 1; $d <= $total_days_in_month; $d++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
    $day_of_week = date('w', strtotime($date)); // 0=Sunday
    if ($day_of_week != 0) {
        $working_days++;
    }
}

// Subtract holidays
$hol_stmt = $pdo->prepare(
    "SELECT COUNT(*) as cnt FROM holidays
     WHERE branch_id = :bid AND MONTH(date) = :m AND YEAR(date) = :y
     AND is_optional = 0 AND DAYOFWEEK(date) != 1"
);
$hol_stmt->execute([':bid' => $branch_id, ':m' => $month, ':y' => $year]);
$holidays = (int)$hol_stmt->fetch()['cnt'];
$working_days -= $holidays;

if ($working_days <= 0) {
    error_response('No working days in selected month', 400);
}

$pdo->beginTransaction();
$generated = [];

try {
    foreach ($employees as $emp) {
        // Check if already generated
        $check = $pdo->prepare(
            "SELECT id FROM salary_slips
             WHERE employee_id = :eid AND month = :m AND year = :y"
        );
        $check->execute([':eid' => $emp['id'], ':m' => $month, ':y' => $year]);
        if ($check->fetch()) {
            continue; // Skip already generated
        }

        // Count present days
        $att_stmt = $pdo->prepare(
            "SELECT COUNT(*) as cnt FROM attendance_logs
             WHERE employee_id = :eid AND MONTH(date) = :m AND YEAR(date) = :y"
        );
        $att_stmt->execute([':eid' => $emp['id'], ':m' => $month, ':y' => $year]);
        $present_days = (int)$att_stmt->fetch()['cnt'];

        // Count approved paid leave days
        $leave_stmt = $pdo->prepare(
            "SELECT SUM(
                CASE WHEN is_half_day = 1 THEN 0.5
                ELSE DATEDIFF(LEAST(end_date, LAST_DAY(:date1)), GREATEST(start_date, :date2)) + 1
                END
             ) as leave_days
             FROM leave_requests
             WHERE employee_id = :eid AND status = 'approved'
             AND leave_type != 'LWP'
             AND start_date <= LAST_DAY(:date3) AND end_date >= :date4"
        );
        $first_of_month = sprintf('%04d-%02d-01', $year, $month);
        $leave_stmt->execute([
            ':eid' => $emp['id'],
            ':date1' => $first_of_month,
            ':date2' => $first_of_month,
            ':date3' => $first_of_month,
            ':date4' => $first_of_month,
        ]);
        $leave_days = (int)($leave_stmt->fetch()['leave_days'] ?? 0);

        // Count LWP days
        $lwp_stmt = $pdo->prepare(
            "SELECT SUM(
                CASE WHEN is_half_day = 1 THEN 0.5
                ELSE DATEDIFF(LEAST(end_date, LAST_DAY(:date1)), GREATEST(start_date, :date2)) + 1
                END
             ) as lwp_days
             FROM leave_requests
             WHERE employee_id = :eid AND status = 'approved'
             AND leave_type = 'LWP'
             AND start_date <= LAST_DAY(:date3) AND end_date >= :date4"
        );
        $lwp_stmt->execute([
            ':eid' => $emp['id'],
            ':date1' => $first_of_month,
            ':date2' => $first_of_month,
            ':date3' => $first_of_month,
            ':date4' => $first_of_month,
        ]);
        $lwp_days = (int)($lwp_stmt->fetch()['lwp_days'] ?? 0);

        // Calculate salary
        $per_day = $emp['monthly_salary'] / $working_days;
        $paid_days = $present_days + $leave_days;
        $gross_salary = round($per_day * $paid_days, 2);
        $deductions = round($per_day * $lwp_days, 2);
        $net_salary = round($gross_salary - $deductions, 2);

        // Insert salary slip
        $ins_stmt = $pdo->prepare(
            "INSERT INTO salary_slips
             (employee_id, month, year, total_days, present_days, leave_days, lwp_days, gross_salary, deductions, net_salary)
             VALUES (:eid, :m, :y, :total, :present, :leave, :lwp, :gross, :ded, :net)"
        );
        $ins_stmt->execute([
            ':eid' => $emp['id'],
            ':m' => $month,
            ':y' => $year,
            ':total' => $working_days,
            ':present' => $present_days,
            ':leave' => $leave_days,
            ':lwp' => $lwp_days,
            ':gross' => $gross_salary,
            ':ded' => $deductions,
            ':net' => $net_salary,
        ]);

        // Notify employee
        $notif_stmt = $pdo->prepare(
            "INSERT INTO notifications (employee_id, title, body, type)
             VALUES (:eid, :title, :body, 'salary')"
        );
        $month_name = date('F', mktime(0, 0, 0, $month, 1));
        $notif_stmt->execute([
            ':eid' => $emp['id'],
            ':title' => "Salary Slip Generated",
            ':body' => "Your salary slip for {$month_name} {$year} is now available. Net salary: ₹" . number_format($net_salary, 2),
        ]);

        $generated[] = [
            'employee_id' => $emp['id'],
            'full_name' => $emp['full_name'],
            'net_salary' => $net_salary,
        ];
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_response('Payroll generation failed: ' . $e->getMessage(), 500);
}

success_response('Payroll generated', [
    'branch_id' => $branch_id,
    'month' => $month,
    'year' => $year,
    'working_days' => $working_days,
    'employees_processed' => count($generated),
    'slips' => $generated,
]);
```

- [ ] **Step 2: Create salary/slips.php**

```php
<?php
// api/endpoints/salary/slips.php
// GET /salary/slips — Employee's salary slips list

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT id, month, year, total_days, present_days, leave_days, lwp_days,
            gross_salary, deductions, net_salary, generated_at
     FROM salary_slips
     WHERE employee_id = :eid
     ORDER BY year DESC, month DESC"
);
$stmt->execute([':eid' => $employee_id]);

json_response($stmt->fetchAll());
```

- [ ] **Step 3: Create salary/slip_detail.php**

```php
<?php
// api/endpoints/salary/slip_detail.php
// GET /salary/slip_detail?id=X

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$slip_id = $_GET['id'] ?? null;
if (!$slip_id) {
    error_response('Slip ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT ss.*, e.full_name, e.employee_code, e.designation, e.department,
            e.monthly_salary, e.bank_account, e.ifsc_code, b.name as branch_name
     FROM salary_slips ss
     JOIN employees e ON e.id = ss.employee_id
     JOIN branches b ON b.id = e.branch_id
     WHERE ss.id = :id AND ss.employee_id = :eid"
);
$stmt->execute([':id' => (int)$slip_id, ':eid' => $employee_id]);
$slip = $stmt->fetch();

if (!$slip) {
    error_response('Salary slip not found', 404);
}

json_response($slip);
```

- [ ] **Step 4: Commit**

```bash
git add api/endpoints/salary/
git commit -m "feat: add salary endpoints (generate payroll, list slips, slip detail)"
```

---

### Task 13: Holiday, Notification, and Leave Policy Endpoints

**Files:**
- Create: `api/endpoints/holidays/create.php`
- Create: `api/endpoints/holidays/list.php`
- Create: `api/endpoints/holidays/delete.php`
- Create: `api/endpoints/notifications/list.php`
- Create: `api/endpoints/notifications/mark_read.php`
- Create: `api/endpoints/leave_policies/set.php`
- Create: `api/endpoints/leave_policies/view.php`

- [ ] **Step 1: Create holidays/create.php**

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['branch_id', 'name', 'date']);
validate_date($input['date']);

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "INSERT INTO holidays (branch_id, name, date, is_optional)
     VALUES (:bid, :name, :date, :optional)"
);
$stmt->execute([
    ':bid' => (int)$input['branch_id'],
    ':name' => sanitize_string($input['name']),
    ':date' => $input['date'],
    ':optional' => (int)($input['is_optional'] ?? 0),
]);

success_response('Holiday added', ['id' => $pdo->lastInsertId()], 201);
```

- [ ] **Step 2: Create holidays/list.php**

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$year = $_GET['year'] ?? date('Y');

// Get employee's branch
$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT branch_id FROM employees WHERE id = :id");
$stmt->execute([':id' => $employee_id]);
$emp = $stmt->fetch();

$stmt2 = $pdo->prepare(
    "SELECT id, name, date, is_optional FROM holidays
     WHERE branch_id = :bid AND YEAR(date) = :year
     ORDER BY date ASC"
);
$stmt2->execute([':bid' => $emp['branch_id'], ':year' => (int)$year]);

json_response($stmt2->fetchAll());
```

- [ ] **Step 3: Create holidays/delete.php**

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    error_response('Holiday ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("DELETE FROM holidays WHERE id = :id");
$stmt->execute([':id' => (int)$id]);

if ($stmt->rowCount() === 0) {
    error_response('Holiday not found', 404);
}

success_response('Holiday deleted');
```

- [ ] **Step 4: Create notifications/list.php**

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT id, title, body, type, is_read, created_at
     FROM notifications
     WHERE employee_id = :eid
     ORDER BY created_at DESC
     LIMIT 50"
);
$stmt->execute([':eid' => $employee_id]);

json_response($stmt->fetchAll());
```

- [ ] **Step 5: Create notifications/mark_read.php**

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['notification_id']);

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "UPDATE notifications SET is_read = 1 WHERE id = :id AND employee_id = :eid"
);
$stmt->execute([':id' => (int)$input['notification_id'], ':eid' => $employee_id]);

success_response('Notification marked as read');
```

- [ ] **Step 6: Create leave_policies/set.php**

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['branch_id', 'leave_type', 'annual_quota']);
validate_enum($input['leave_type'], ['CL', 'SL', 'EL', 'CO', 'LWP'], 'leave_type');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "INSERT INTO leave_policies (branch_id, leave_type, annual_quota, carry_forward, max_carry)
     VALUES (:bid, :type, :quota, :carry, :max_carry)
     ON DUPLICATE KEY UPDATE annual_quota = :quota2, carry_forward = :carry2, max_carry = :max_carry2"
);
$stmt->execute([
    ':bid' => (int)$input['branch_id'],
    ':type' => $input['leave_type'],
    ':quota' => (int)$input['annual_quota'],
    ':carry' => (int)($input['carry_forward'] ?? 0),
    ':max_carry' => (int)($input['max_carry'] ?? 0),
    ':quota2' => (int)$input['annual_quota'],
    ':carry2' => (int)($input['carry_forward'] ?? 0),
    ':max_carry2' => (int)($input['max_carry'] ?? 0),
]);

success_response('Leave policy set');
```

- [ ] **Step 7: Create leave_policies/view.php**

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

// Get employee's branch
$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT branch_id FROM employees WHERE id = :id");
$stmt->execute([':id' => $employee_id]);
$emp = $stmt->fetch();

$branch_id = $_GET['branch_id'] ?? $emp['branch_id'];

$stmt2 = $pdo->prepare(
    "SELECT leave_type, annual_quota, carry_forward, max_carry
     FROM leave_policies WHERE branch_id = :bid"
);
$stmt2->execute([':bid' => (int)$branch_id]);

json_response($stmt2->fetchAll());
```

- [ ] **Step 8: Commit**

```bash
git add api/endpoints/holidays/ api/endpoints/notifications/ api/endpoints/leave_policies/
git commit -m "feat: add holiday, notification, and leave policy endpoints"
```

---

### Task 14: Final Verification and Init Git

- [ ] **Step 1: Initialize git repo (if not already)**

```bash
cd c:/Users/prash/AndroidStudioProjects/attendance
git init
```

- [ ] **Step 2: Verify all API files exist**

```bash
ls -R api/
```

Expected: all directories and files from the file structure above should be present.

- [ ] **Step 3: Test the router locally (if PHP available)**

```bash
cd api
php -S localhost:8080
```

Then test with:
```bash
curl -X POST http://localhost:8080/auth/admin_login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@kalinaengineering.com","password":"admin123"}'
```

Expected response:
```json
{"success":true,"message":"Admin login successful","data":{"admin_id":1,"name":"Super Admin","access_token":"...","refresh_token":"...","expires_in":1800}}
```

- [ ] **Step 4: Commit the full API**

```bash
git add .
git commit -m "feat: complete Phase 1 — PHP REST API with all 30 endpoints"
```

---

## Self-Review Checklist

1. **Spec coverage:** All 10 tables, all 30 endpoints, auth flow, geofence, payroll, leaves, notifications — all covered.
2. **Placeholder scan:** No TBD/TODO found. All steps have complete code.
3. **Type consistency:** `employee_id`, `branch_id`, `leave_type` ENUM values, response format (`success/message/data`) — consistent across all files.
4. **Missing from spec:** Added `rate_limits` and `refresh_tokens` tables (needed for implementation but not in original schema). Added `schema.sql` for reproducibility.
