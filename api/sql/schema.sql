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
