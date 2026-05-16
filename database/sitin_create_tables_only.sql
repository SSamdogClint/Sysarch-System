-- =========================================================
-- UC Sit-in System Database Tables Only
-- Copy and paste this in phpMyAdmin SQL tab.
-- This script creates the database and all required tables.
-- It does NOT insert sample data.
-- =========================================================

CREATE DATABASE IF NOT EXISTS sitin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE sitin;

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- CORE TABLES
-- =========================================================

CREATE TABLE IF NOT EXISTS students (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  studentid       VARCHAR(20)  NOT NULL UNIQUE,
  lastname        VARCHAR(50)  NOT NULL,
  firstname       VARCHAR(50)  NOT NULL,
  middlename      VARCHAR(50)  DEFAULT '',
  course          VARCHAR(30)  NOT NULL,
  yearlvl         TINYINT      NOT NULL DEFAULT 1,
  email           VARCHAR(100) NOT NULL UNIQUE,
  password        VARCHAR(255) NOT NULL,
  addrs           VARCHAR(150) DEFAULT '',
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  session_credits INT          NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS announcements (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(255) NOT NULL,
  message    TEXT NOT NULL,
  posted_by  VARCHAR(100) DEFAULT 'Administrator',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS sitin_records (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  student_id       INT NOT NULL,
  studentid        VARCHAR(20) NOT NULL,
  fullname         VARCHAR(150) NOT NULL,
  purpose          VARCHAR(100) NOT NULL,
  lab              VARCHAR(50) NOT NULL,
  pc_number        INT NULL,
  login_time       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  logout_time      DATETIME NULL,
  status           VARCHAR(20) DEFAULT 'active',
  session_at_sitin INT NOT NULL DEFAULT 0,

  INDEX idx_sitin_student_id (student_id),
  INDEX idx_sitin_studentid (studentid),
  INDEX idx_sitin_status (status),
  INDEX idx_sitin_login_time (login_time),

  CONSTRAINT fk_sitin_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS feedback (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  sitin_id      INT NOT NULL,
  student_id    INT NOT NULL,
  issue_type    VARCHAR(100) DEFAULT NULL,
  feedback_text TEXT NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY unique_sitin_feedback (sitin_id),
  INDEX idx_feedback_student (student_id),
  INDEX idx_feedback_created (created_at),

  CONSTRAINT fk_feedback_sitin
    FOREIGN KEY (sitin_id)
    REFERENCES sitin_records(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT fk_feedback_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS lab_reservations (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  student_id            INT NOT NULL,
  studentid             VARCHAR(20) NOT NULL,
  fullname              VARCHAR(120) NOT NULL,
  purpose               VARCHAR(150) NOT NULL,
  lab                   VARCHAR(50) NOT NULL,
  pc_number             INT NOT NULL,
  reservation_date      DATE NOT NULL,
  reservation_time      TIME NOT NULL,
  reservation_end_time  TIME NOT NULL,
  status                ENUM('pending','approved','rejected','cancelled','done') NOT NULL DEFAULT 'pending',
  created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_lab_slot (lab, reservation_date, reservation_time, reservation_end_time, pc_number),
  INDEX idx_student_slot (student_id, reservation_date, reservation_time, reservation_end_time),
  INDEX idx_reservation_status (status),
  INDEX idx_reservation_date (reservation_date),

  CONSTRAINT fk_reservation_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS lab_pc_status (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  lab         VARCHAR(50) NOT NULL,
  pc_number   INT NOT NULL,
  status      ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  note        VARCHAR(150) DEFAULT NULL,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_lab_pc (lab, pc_number),
  INDEX idx_lab_pc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- STUDENT NOTIFICATION TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS student_notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  type       VARCHAR(50) NOT NULL DEFAULT 'notification',
  title      VARCHAR(255) NOT NULL,
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_student_notifications_student (student_id),
  INDEX idx_student_notifications_created (created_at),
  INDEX idx_student_notifications_read (is_read),

  CONSTRAINT fk_notification_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- SOFTWARE AVAILABILITY TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS software_applications (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  lab           VARCHAR(50) NOT NULL,
  software_name VARCHAR(150) NOT NULL,
  category      VARCHAR(100) DEFAULT NULL,
  version       VARCHAR(80) DEFAULT NULL,
  status        ENUM('installed','unavailable') NOT NULL DEFAULT 'installed',
  uploaded_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_software_lab (lab),
  INDEX idx_software_name (software_name),
  INDEX idx_software_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- TESTIMONIAL TABLE
-- No approval needed. Status defaults to approved.
-- =========================================================

CREATE TABLE IF NOT EXISTS testimonials (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  rating     TINYINT NOT NULL DEFAULT 5,
  message    TEXT NOT NULL,
  status     ENUM('approved','pending','rejected') NOT NULL DEFAULT 'approved',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_testimonials_student (student_id),
  INDEX idx_testimonials_status (status),
  INDEX idx_testimonials_created (created_at),

  CONSTRAINT fk_testimonial_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- CHECK TABLES
-- =========================================================
SHOW TABLES;
