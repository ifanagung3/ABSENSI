-- Hadirin — referensi skema MySQL/MariaDB
-- Instalasi utama tetap menggunakan: php artisan migrate --seed

CREATE DATABASE IF NOT EXISTS absensi_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_db;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at TIMESTAMP NULL,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_users_role (role),
  INDEX idx_users_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
  email VARCHAR(255) PRIMARY KEY,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE sessions (
  id VARCHAR(255) PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  payload LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  INDEX idx_sessions_user (user_id),
  INDEX idx_sessions_activity (last_activity)
) ENGINE=InnoDB;

CREATE TABLE departments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE positions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE work_locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address TEXT NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  radius_meters SMALLINT UNSIGNED NOT NULL DEFAULT 100,
  maximum_accuracy_meters SMALLINT UNSIGNED NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_locations_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE shifts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  check_in_open_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  late_tolerance_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  check_out_open_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  crosses_midnight TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE employees (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  department_id BIGINT UNSIGNED NULL,
  position_id BIGINT UNSIGNED NULL,
  default_location_id BIGINT UNSIGNED NULL,
  employee_number VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  profile_photo_path VARCHAR(255) NULL,
  join_date DATE NULL,
  employment_status ENUM('permanent','contract','intern','inactive') NOT NULL DEFAULT 'permanent',
  attendance_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_employees_attendance_enabled (attendance_enabled),
  CONSTRAINT fk_employees_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_employees_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_employees_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
  CONSTRAINT fk_employees_location FOREIGN KEY (default_location_id) REFERENCES work_locations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE employee_schedules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  shift_id BIGINT UNSIGNED NOT NULL,
  work_location_id BIGINT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  status ENUM('working','off','leave','holiday') NOT NULL DEFAULT 'working',
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_employee_schedule_date (employee_id, work_date),
  INDEX idx_schedule_date (work_date),
  CONSTRAINT fk_schedule_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_schedule_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE RESTRICT,
  CONSTRAINT fk_schedule_location FOREIGN KEY (work_location_id) REFERENCES work_locations(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE attendance_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  schedule_id BIGINT UNSIGNED NULL,
  attendance_date DATE NOT NULL,
  status ENUM('present','late','absent','leave','sick','permission','pending_review','invalid') NOT NULL DEFAULT 'present',
  checked_in_at TIMESTAMP NULL,
  checked_out_at TIMESTAMP NULL,
  late_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  early_leave_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  work_duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  notes TEXT NULL,
  corrected_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_attendance_employee_date (employee_id, attendance_date),
  INDEX idx_attendance_date (attendance_date),
  INDEX idx_attendance_status (status),
  CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_attendance_schedule FOREIGN KEY (schedule_id) REFERENCES employee_schedules(id) ON DELETE SET NULL,
  CONSTRAINT fk_attendance_corrector FOREIGN KEY (corrected_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE attendance_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attendance_record_id BIGINT UNSIGNED NOT NULL,
  event_type ENUM('check_in','check_out') NOT NULL,
  server_time TIMESTAMP NOT NULL,
  device_time TIMESTAMP NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  location_accuracy DECIMAL(8,2) NOT NULL,
  distance_meters DECIMAL(10,2) NOT NULL,
  inside_geofence TINYINT(1) NOT NULL,
  photo_path VARCHAR(255) NOT NULL,
  photo_hash VARCHAR(64) NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  validation_status ENUM('valid','review','rejected') NOT NULL DEFAULT 'valid',
  rejection_reason TEXT NULL,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_attendance_event_type (attendance_record_id, event_type),
  INDEX idx_event_type (event_type),
  INDEX idx_event_server_time (server_time),
  INDEX idx_event_photo_hash (photo_hash),
  INDEX idx_event_validation (validation_status),
  CONSTRAINT fk_event_record FOREIGN KEY (attendance_record_id) REFERENCES attendance_records(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE leave_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  request_type ENUM('permission','sick','leave') NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason TEXT NOT NULL,
  attachment_path VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at TIMESTAMP NULL,
  review_notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_leave_status (status),
  CONSTRAINT fk_leave_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(150) NULL,
  entity_id BIGINT UNSIGNED NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_audit_action (action),
  INDEX idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Data lokasi awal. Untuk akun, shift, jadwal, cache, queue, dan hashing password,
-- tetap gunakan Laravel seeder agar hasil konsisten dan aman.
INSERT INTO work_locations
(name, address, latitude, longitude, radius_meters, maximum_accuracy_meters, is_active, created_at, updated_at)
VALUES
('SMKN 4 Pariaman', 'Jalan Tuanku Naali, Desa Taluk, Kecamatan Pariaman Selatan, Kota Pariaman, Sumatera Barat', -0.6447000, 100.1401000, 100, 100, 1, NOW(), NOW());
