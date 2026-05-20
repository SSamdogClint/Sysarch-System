-- ============================================================
-- Sysarch-System / UC Sit-in System
-- FULL DATABASE TABLE QUERY
-- Database name: sitin
-- Run this in phpMyAdmin SQL tab.
-- This script is safe for existing database because it uses IF NOT EXISTS.
-- ============================================================

CREATE DATABASE IF NOT EXISTS sitin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE sitin;

-- ============================================================
-- 1. ADMINS
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(150) DEFAULT 'Administrator',
  email VARCHAR(150) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default admin password is: admin123
-- Hash generated using PHP password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO admins (username, password, fullname, email)
VALUES (
  'admin',
  '$2y$10$8V5L6Z4eQvM9w5W9JpX6uO6DJvP9LhDnMs16u7ZzW6c5d9fEwVq1K',
  'Administrator',
  'admin@uc.edu.ph'
)
ON DUPLICATE KEY UPDATE username = username;

-- ============================================================
-- 2. STUDENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  studentid VARCHAR(50) NOT NULL UNIQUE,
  firstname VARCHAR(100) NOT NULL,
  middlename VARCHAR(100) DEFAULT '',
  lastname VARCHAR(100) NOT NULL,
  course VARCHAR(100) DEFAULT '',
  yearlvl VARCHAR(50) DEFAULT '',
  email VARCHAR(150) DEFAULT '',
  addrs VARCHAR(255) DEFAULT '',
  password VARCHAR(255) NOT NULL,

  -- Remaining available sit-in sessions
  session_credits INT NOT NULL DEFAULT 30,

  -- Spendable reward balance. This decreases when student redeems.
  reward_points DECIMAL(10,2) NOT NULL DEFAULT 0,

  -- Earned reward points for leaderboard score. This does NOT decrease on redeem.
  reward_points_earned DECIMAL(10,2) NOT NULL DEFAULT 0,

  -- Task points earned from admin rating
  task_completed DECIMAL(10,2) NOT NULL DEFAULT 0,

  avatar VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- If your students table already exists, these make sure required columns are present.
ALTER TABLE students
ADD COLUMN IF NOT EXISTS session_credits INT NOT NULL DEFAULT 30;

ALTER TABLE students
ADD COLUMN IF NOT EXISTS reward_points DECIMAL(10,2) NOT NULL DEFAULT 0;

ALTER TABLE students
ADD COLUMN IF NOT EXISTS reward_points_earned DECIMAL(10,2) NOT NULL DEFAULT 0;

ALTER TABLE students
ADD COLUMN IF NOT EXISTS task_completed DECIMAL(10,2) NOT NULL DEFAULT 0;

ALTER TABLE students
ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL;

-- One-time sync so old reward points become earned points too.
UPDATE students
SET reward_points_earned = reward_points
WHERE reward_points_earned = 0
  AND reward_points > 0;

-- ============================================================
-- 3. SIT-IN RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS sitin_records (
  id INT AUTO_INCREMENT PRIMARY KEY,

  -- Optional relation to students.id
  student_id INT NULL,

  -- Stored display fields for easier reports/history
  studentid VARCHAR(50) NOT NULL,
  fullname VARCHAR(180) NOT NULL,

  purpose VARCHAR(150) NOT NULL,
  lab VARCHAR(100) NOT NULL,
  pc_number INT NULL,

  -- Session number/credit count at the time of sit-in
  session_at_sitin INT DEFAULT NULL,

  login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  logout_time DATETIME NULL,

  duration_minutes INT NOT NULL DEFAULT 0,

  status ENUM('active','done') NOT NULL DEFAULT 'active',

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_sitin_student_id (student_id),
  INDEX idx_sitin_studentid (studentid),
  INDEX idx_sitin_status (status),
  INDEX idx_sitin_login_time (login_time),

  CONSTRAINT fk_sitin_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE sitin_records
ADD COLUMN IF NOT EXISTS student_id INT NULL;

ALTER TABLE sitin_records
ADD COLUMN IF NOT EXISTS pc_number INT NULL;

ALTER TABLE sitin_records
ADD COLUMN IF NOT EXISTS session_at_sitin INT DEFAULT NULL;

ALTER TABLE sitin_records
ADD COLUMN IF NOT EXISTS logout_time DATETIME NULL;

ALTER TABLE sitin_records
ADD COLUMN IF NOT EXISTS duration_minutes INT NOT NULL DEFAULT 0;

ALTER TABLE sitin_records
ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ============================================================
-- 4. LAB RESERVATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS lab_reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NULL,
  studentid VARCHAR(50) DEFAULT NULL,
  fullname VARCHAR(180) DEFAULT NULL,

  purpose VARCHAR(150) NOT NULL,
  lab VARCHAR(100) NOT NULL,
  pc_number INT NULL,

  reservation_date DATE NOT NULL,
  reservation_time TIME NOT NULL,
  reservation_end_time TIME NULL,

  status ENUM('pending','approved','rejected','cancelled','done','completed') NOT NULL DEFAULT 'pending',

  admin_note VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_reservation_student_id (student_id),
  INDEX idx_reservation_studentid (studentid),
  INDEX idx_reservation_status (status),
  INDEX idx_reservation_date (reservation_date),

  CONSTRAINT fk_reservation_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE lab_reservations
ADD COLUMN IF NOT EXISTS student_id INT NULL;

ALTER TABLE lab_reservations
ADD COLUMN IF NOT EXISTS studentid VARCHAR(50) DEFAULT NULL;

ALTER TABLE lab_reservations
ADD COLUMN IF NOT EXISTS fullname VARCHAR(180) DEFAULT NULL;

ALTER TABLE lab_reservations
ADD COLUMN IF NOT EXISTS pc_number INT NULL;

ALTER TABLE lab_reservations
ADD COLUMN IF NOT EXISTS reservation_end_time TIME NULL;

ALTER TABLE lab_reservations
ADD COLUMN IF NOT EXISTS admin_note VARCHAR(255) DEFAULT NULL;

ALTER TABLE lab_reservations
ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- ============================================================
-- 5. ANNOUNCEMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  posted_by VARCHAR(100) DEFAULT 'Administrator',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 6. TESTIMONIALS / REVIEWS
-- ============================================================
CREATE TABLE IF NOT EXISTS testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  rating INT NOT NULL DEFAULT 5,
  message TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_testimonial_student (student_id),
  INDEX idx_testimonial_status (status),

  CONSTRAINT fk_testimonial_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 7. SOFTWARE AVAILABILITY
-- ============================================================
CREATE TABLE IF NOT EXISTS software_availability (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lab VARCHAR(100) NOT NULL,
  software_name VARCHAR(150) NOT NULL,
  category VARCHAR(100) DEFAULT '',
  version VARCHAR(100) DEFAULT '',
  status ENUM('installed','not installed','maintenance','unavailable') NOT NULL DEFAULT 'installed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_software_lab (lab),
  INDEX idx_software_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample software data
INSERT INTO software_availability (lab, software_name, category, version, status) VALUES
('Lab 524', 'Visual Studio Code', 'Programming', '1.90', 'installed'),
('Lab 524', 'XAMPP', 'Web Development', '8.2', 'installed'),
('Lab 524', 'Google Chrome', 'Browser', 'Latest', 'installed'),
('Lab 524', 'Microsoft Office', 'Productivity', '2021', 'installed'),

('Lab 526', 'Python', 'Programming', '3.12', 'installed'),
('Lab 526', 'Visual Studio Code', 'Programming', '1.90', 'installed'),
('Lab 526', 'Git', 'Version Control', '2.x', 'installed'),
('Lab 526', 'Node.js', 'Web Development', '20.x', 'installed'),

('Lab 528', 'Android Studio', 'Mobile Development', 'Koala', 'installed'),
('Lab 528', 'Java JDK', 'Programming', '21', 'installed'),
('Lab 528', 'Flutter SDK', 'Mobile Development', 'Stable', 'installed'),
('Lab 528', 'MySQL Workbench', 'Database', '8.x', 'installed'),

('Lab 530', 'Cisco Packet Tracer', 'Networking', '8.2', 'installed'),
('Lab 530', 'Wireshark', 'Networking', 'Latest', 'installed'),
('Lab 530', 'VirtualBox', 'Virtualization', '7.x', 'installed'),
('Lab 530', 'PuTTY', 'Networking', 'Latest', 'installed'),

('Lab 542', 'Adobe Photoshop', 'Multimedia', '2024', 'installed'),
('Lab 542', 'Canva', 'Design', 'Web', 'installed'),
('Lab 542', 'Figma', 'UI/UX Design', 'Web', 'installed'),
('Lab 542', 'Audacity', 'Audio Editing', 'Latest', 'installed'),

('Lab 544', 'Visual Studio', 'Programming', '2022', 'installed'),
('Lab 544', 'SQL Server Management Studio', 'Database', 'Latest', 'installed'),
('Lab 544', 'Postman', 'API Testing', 'Latest', 'installed'),
('Lab 544', 'Notepad++', 'Text Editor', 'Latest', 'installed')
ON DUPLICATE KEY UPDATE software_name = software_name;

-- ============================================================
-- 8. FEEDBACK / REPORT FEEDBACK
-- ============================================================
CREATE TABLE IF NOT EXISTS feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sitin_id INT NULL,
  student_id INT NULL,
  studentid VARCHAR(50) DEFAULT NULL,
  student_name VARCHAR(180) DEFAULT NULL,
  lab VARCHAR(100) DEFAULT NULL,
  purpose VARCHAR(150) DEFAULT NULL,
  issue_type VARCHAR(100) DEFAULT 'General',
  feedback_text TEXT NOT NULL,
  status ENUM('new','reviewed','resolved') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_feedback_sitin (sitin_id),
  INDEX idx_feedback_student_id (student_id),
  INDEX idx_feedback_studentid (studentid),
  INDEX idx_feedback_status (status),

  CONSTRAINT fk_feedback_sitin
    FOREIGN KEY (sitin_id)
    REFERENCES sitin_records(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT fk_feedback_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE feedback
ADD COLUMN IF NOT EXISTS sitin_id INT NULL AFTER id;

-- ============================================================
-- 9. REWARD POINT LOGS
-- Admin gives 0%, 25%, 50%, 75%, 100%
-- 100% = 10 points
-- ============================================================
CREATE TABLE IF NOT EXISTS reward_point_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  reward_percent INT NOT NULL DEFAULT 0,
  task_percent INT NOT NULL DEFAULT 0,
  points_added DECIMAL(10,2) NOT NULL DEFAULT 0,
  task_added DECIMAL(10,2) NOT NULL DEFAULT 0,
  reason VARCHAR(255) NOT NULL,
  awarded_by VARCHAR(100) DEFAULT 'Administrator',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_reward_logs_student (student_id),
  INDEX idx_reward_logs_created (created_at),

  CONSTRAINT fk_reward_logs_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE reward_point_logs
ADD COLUMN IF NOT EXISTS reward_percent INT NOT NULL DEFAULT 0 AFTER student_id;

ALTER TABLE reward_point_logs
ADD COLUMN IF NOT EXISTS task_percent INT NOT NULL DEFAULT 0 AFTER reward_percent;

ALTER TABLE reward_point_logs
MODIFY points_added DECIMAL(10,2) NOT NULL DEFAULT 0;

ALTER TABLE reward_point_logs
MODIFY task_added DECIMAL(10,2) NOT NULL DEFAULT 0;

-- ============================================================
-- 10. REWARD REDEMPTION LOGS
-- Rule: 10 spendable reward points = 1 extra sit-in session
-- ============================================================
CREATE TABLE IF NOT EXISTS reward_redemption_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  points_used DECIMAL(10,2) NOT NULL DEFAULT 0,
  sessions_added INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_redemption_student (student_id),
  INDEX idx_redemption_created (created_at),

  CONSTRAINT fk_redemption_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 11. REWARD SEASON SETTINGS
-- Used for reset/archive seasons.
-- Current leaderboard only counts records after current_started_at.
-- ============================================================
CREATE TABLE IF NOT EXISTS reward_season_settings (
  id INT PRIMARY KEY,
  current_started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO reward_season_settings (id, current_started_at)
VALUES (1, '2000-01-01 00:00:00')
ON DUPLICATE KEY UPDATE id = id;

-- ============================================================
-- 12. LEADERBOARD ARCHIVES
-- ============================================================
CREATE TABLE IF NOT EXISTS leaderboard_archives (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  season_started_at DATETIME NOT NULL,
  season_ended_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by VARCHAR(100) DEFAULT 'Administrator',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_leaderboard_archive_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS leaderboard_archive_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  archive_id INT NOT NULL,
  student_id INT NULL,
  studentid VARCHAR(50) NOT NULL,
  fullname VARCHAR(180) NOT NULL,
  course VARCHAR(100) DEFAULT NULL,
  yearlvl VARCHAR(50) DEFAULT NULL,

  rank_no INT NOT NULL,

  reward_points_earned DECIMAL(10,2) NOT NULL DEFAULT 0,
  reward_points_balance DECIMAL(10,2) NOT NULL DEFAULT 0,
  task_points DECIMAL(10,2) NOT NULL DEFAULT 0,

  total_sessions INT NOT NULL DEFAULT 0,
  total_minutes INT NOT NULL DEFAULT 0,
  assessment_count INT NOT NULL DEFAULT 0,

  reward_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  hour_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  task_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  final_score DECIMAL(10,2) NOT NULL DEFAULT 0,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_archive_entries_archive (archive_id),
  INDEX idx_archive_entries_rank (rank_no),

  CONSTRAINT fk_archive_entries_archive
    FOREIGN KEY (archive_id)
    REFERENCES leaderboard_archives(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 13. SESSION RESET LOGS
-- Used by Reset Sessions button.
-- This does not delete sit-in records. It resets students.session_credits to 30.
-- ============================================================
CREATE TABLE IF NOT EXISTS session_reset_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reset_title VARCHAR(150) NOT NULL,
  total_students INT NOT NULL DEFAULT 0,
  total_credits_before INT NOT NULL DEFAULT 0,
  total_credits_after INT NOT NULL DEFAULT 0,
  reset_by VARCHAR(100) DEFAULT 'Administrator',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_session_reset_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 14. STUDENT NOTIFICATIONS
-- Used by notification_helpers.php and student notification bell.
-- ============================================================
CREATE TABLE IF NOT EXISTS student_notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'notification',
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_student_notifications_student (student_id),
  INDEX idx_student_notifications_read (is_read),
  INDEX idx_student_notifications_created (created_at),

  CONSTRAINT fk_student_notifications_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 15. OPTIONAL NOTIFICATIONS
-- If you later want DB-backed notifications.
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_type ENUM('student','admin','all') NOT NULL DEFAULT 'student',
  student_id INT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(50) DEFAULT 'general',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_notifications_student (student_id),
  INDEX idx_notifications_user_type (user_type),
  INDEX idx_notifications_is_read (is_read),

  CONSTRAINT fk_notification_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 16. OPTIONAL PC UNITS / LAB COMPUTERS
-- For PC availability tracking.
-- ============================================================
CREATE TABLE IF NOT EXISTS lab_computers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lab VARCHAR(100) NOT NULL,
  pc_number INT NOT NULL,
  status ENUM('available','in_use','reserved','maintenance','unavailable') NOT NULL DEFAULT 'available',
  notes VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_lab_pc (lab, pc_number),
  INDEX idx_lab_computer_lab (lab),
  INDEX idx_lab_computer_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample PC units: PC 1 to 56 per lab
INSERT IGNORE INTO lab_computers (lab, pc_number, status) VALUES
('Lab 524', 1, 'available'),
('Lab 524', 2, 'available'),
('Lab 524', 3, 'available'),
('Lab 524', 4, 'available'),
('Lab 524', 5, 'available'),
('Lab 524', 6, 'available'),
('Lab 524', 7, 'available'),
('Lab 524', 8, 'available'),
('Lab 524', 9, 'available'),
('Lab 524', 10, 'available'),
('Lab 524', 11, 'available'),
('Lab 524', 12, 'available'),
('Lab 524', 13, 'available'),
('Lab 524', 14, 'available'),
('Lab 524', 15, 'available'),
('Lab 524', 16, 'available'),
('Lab 524', 17, 'available'),
('Lab 524', 18, 'available'),
('Lab 524', 19, 'available'),
('Lab 524', 20, 'available'),
('Lab 524', 21, 'available'),
('Lab 524', 22, 'available'),
('Lab 524', 23, 'available'),
('Lab 524', 24, 'available'),
('Lab 524', 25, 'available'),
('Lab 524', 26, 'available'),
('Lab 524', 27, 'available'),
('Lab 524', 28, 'available'),
('Lab 524', 29, 'available'),
('Lab 524', 30, 'available'),
('Lab 524', 31, 'available'),
('Lab 524', 32, 'available'),
('Lab 524', 33, 'available'),
('Lab 524', 34, 'available'),
('Lab 524', 35, 'available'),
('Lab 524', 36, 'available'),
('Lab 524', 37, 'available'),
('Lab 524', 38, 'available'),
('Lab 524', 39, 'available'),
('Lab 524', 40, 'available'),
('Lab 524', 41, 'available'),
('Lab 524', 42, 'available'),
('Lab 524', 43, 'available'),
('Lab 524', 44, 'available'),
('Lab 524', 45, 'available'),
('Lab 524', 46, 'available'),
('Lab 524', 47, 'available'),
('Lab 524', 48, 'available'),
('Lab 524', 49, 'available'),
('Lab 524', 50, 'available'),
('Lab 524', 51, 'available'),
('Lab 524', 52, 'available'),
('Lab 524', 53, 'available'),
('Lab 524', 54, 'available'),
('Lab 524', 55, 'available'),
('Lab 524', 56, 'available'),
('Lab 526', 1, 'available'),
('Lab 526', 2, 'available'),
('Lab 526', 3, 'available'),
('Lab 526', 4, 'available'),
('Lab 526', 5, 'available'),
('Lab 526', 6, 'available'),
('Lab 526', 7, 'available'),
('Lab 526', 8, 'available'),
('Lab 526', 9, 'available'),
('Lab 526', 10, 'available'),
('Lab 526', 11, 'available'),
('Lab 526', 12, 'available'),
('Lab 526', 13, 'available'),
('Lab 526', 14, 'available'),
('Lab 526', 15, 'available'),
('Lab 526', 16, 'available'),
('Lab 526', 17, 'available'),
('Lab 526', 18, 'available'),
('Lab 526', 19, 'available'),
('Lab 526', 20, 'available'),
('Lab 526', 21, 'available'),
('Lab 526', 22, 'available'),
('Lab 526', 23, 'available'),
('Lab 526', 24, 'available'),
('Lab 526', 25, 'available'),
('Lab 526', 26, 'available'),
('Lab 526', 27, 'available'),
('Lab 526', 28, 'available'),
('Lab 526', 29, 'available'),
('Lab 526', 30, 'available'),
('Lab 526', 31, 'available'),
('Lab 526', 32, 'available'),
('Lab 526', 33, 'available'),
('Lab 526', 34, 'available'),
('Lab 526', 35, 'available'),
('Lab 526', 36, 'available'),
('Lab 526', 37, 'available'),
('Lab 526', 38, 'available'),
('Lab 526', 39, 'available'),
('Lab 526', 40, 'available'),
('Lab 526', 41, 'available'),
('Lab 526', 42, 'available'),
('Lab 526', 43, 'available'),
('Lab 526', 44, 'available'),
('Lab 526', 45, 'available'),
('Lab 526', 46, 'available'),
('Lab 526', 47, 'available'),
('Lab 526', 48, 'available'),
('Lab 526', 49, 'available'),
('Lab 526', 50, 'available'),
('Lab 526', 51, 'available'),
('Lab 526', 52, 'available'),
('Lab 526', 53, 'available'),
('Lab 526', 54, 'available'),
('Lab 526', 55, 'available'),
('Lab 526', 56, 'available'),
('Lab 528', 1, 'available'),
('Lab 528', 2, 'available'),
('Lab 528', 3, 'available'),
('Lab 528', 4, 'available'),
('Lab 528', 5, 'available'),
('Lab 528', 6, 'available'),
('Lab 528', 7, 'available'),
('Lab 528', 8, 'available'),
('Lab 528', 9, 'available'),
('Lab 528', 10, 'available'),
('Lab 528', 11, 'available'),
('Lab 528', 12, 'available'),
('Lab 528', 13, 'available'),
('Lab 528', 14, 'available'),
('Lab 528', 15, 'available'),
('Lab 528', 16, 'available'),
('Lab 528', 17, 'available'),
('Lab 528', 18, 'available'),
('Lab 528', 19, 'available'),
('Lab 528', 20, 'available'),
('Lab 528', 21, 'available'),
('Lab 528', 22, 'available'),
('Lab 528', 23, 'available'),
('Lab 528', 24, 'available'),
('Lab 528', 25, 'available'),
('Lab 528', 26, 'available'),
('Lab 528', 27, 'available'),
('Lab 528', 28, 'available'),
('Lab 528', 29, 'available'),
('Lab 528', 30, 'available'),
('Lab 528', 31, 'available'),
('Lab 528', 32, 'available'),
('Lab 528', 33, 'available'),
('Lab 528', 34, 'available'),
('Lab 528', 35, 'available'),
('Lab 528', 36, 'available'),
('Lab 528', 37, 'available'),
('Lab 528', 38, 'available'),
('Lab 528', 39, 'available'),
('Lab 528', 40, 'available'),
('Lab 528', 41, 'available'),
('Lab 528', 42, 'available'),
('Lab 528', 43, 'available'),
('Lab 528', 44, 'available'),
('Lab 528', 45, 'available'),
('Lab 528', 46, 'available'),
('Lab 528', 47, 'available'),
('Lab 528', 48, 'available'),
('Lab 528', 49, 'available'),
('Lab 528', 50, 'available'),
('Lab 528', 51, 'available'),
('Lab 528', 52, 'available'),
('Lab 528', 53, 'available'),
('Lab 528', 54, 'available'),
('Lab 528', 55, 'available'),
('Lab 528', 56, 'available'),
('Lab 530', 1, 'available'),
('Lab 530', 2, 'available'),
('Lab 530', 3, 'available'),
('Lab 530', 4, 'available'),
('Lab 530', 5, 'available'),
('Lab 530', 6, 'available'),
('Lab 530', 7, 'available'),
('Lab 530', 8, 'available'),
('Lab 530', 9, 'available'),
('Lab 530', 10, 'available'),
('Lab 530', 11, 'available'),
('Lab 530', 12, 'available'),
('Lab 530', 13, 'available'),
('Lab 530', 14, 'available'),
('Lab 530', 15, 'available'),
('Lab 530', 16, 'available'),
('Lab 530', 17, 'available'),
('Lab 530', 18, 'available'),
('Lab 530', 19, 'available'),
('Lab 530', 20, 'available'),
('Lab 530', 21, 'available'),
('Lab 530', 22, 'available'),
('Lab 530', 23, 'available'),
('Lab 530', 24, 'available'),
('Lab 530', 25, 'available'),
('Lab 530', 26, 'available'),
('Lab 530', 27, 'available'),
('Lab 530', 28, 'available'),
('Lab 530', 29, 'available'),
('Lab 530', 30, 'available'),
('Lab 530', 31, 'available'),
('Lab 530', 32, 'available'),
('Lab 530', 33, 'available'),
('Lab 530', 34, 'available'),
('Lab 530', 35, 'available'),
('Lab 530', 36, 'available'),
('Lab 530', 37, 'available'),
('Lab 530', 38, 'available'),
('Lab 530', 39, 'available'),
('Lab 530', 40, 'available'),
('Lab 530', 41, 'available'),
('Lab 530', 42, 'available'),
('Lab 530', 43, 'available'),
('Lab 530', 44, 'available'),
('Lab 530', 45, 'available'),
('Lab 530', 46, 'available'),
('Lab 530', 47, 'available'),
('Lab 530', 48, 'available'),
('Lab 530', 49, 'available'),
('Lab 530', 50, 'available'),
('Lab 530', 51, 'available'),
('Lab 530', 52, 'available'),
('Lab 530', 53, 'available'),
('Lab 530', 54, 'available'),
('Lab 530', 55, 'available'),
('Lab 530', 56, 'available'),
('Lab 542', 1, 'available'),
('Lab 542', 2, 'available'),
('Lab 542', 3, 'available'),
('Lab 542', 4, 'available'),
('Lab 542', 5, 'available'),
('Lab 542', 6, 'available'),
('Lab 542', 7, 'available'),
('Lab 542', 8, 'available'),
('Lab 542', 9, 'available'),
('Lab 542', 10, 'available'),
('Lab 542', 11, 'available'),
('Lab 542', 12, 'available'),
('Lab 542', 13, 'available'),
('Lab 542', 14, 'available'),
('Lab 542', 15, 'available'),
('Lab 542', 16, 'available'),
('Lab 542', 17, 'available'),
('Lab 542', 18, 'available'),
('Lab 542', 19, 'available'),
('Lab 542', 20, 'available'),
('Lab 542', 21, 'available'),
('Lab 542', 22, 'available'),
('Lab 542', 23, 'available'),
('Lab 542', 24, 'available'),
('Lab 542', 25, 'available'),
('Lab 542', 26, 'available'),
('Lab 542', 27, 'available'),
('Lab 542', 28, 'available'),
('Lab 542', 29, 'available'),
('Lab 542', 30, 'available'),
('Lab 542', 31, 'available'),
('Lab 542', 32, 'available'),
('Lab 542', 33, 'available'),
('Lab 542', 34, 'available'),
('Lab 542', 35, 'available'),
('Lab 542', 36, 'available'),
('Lab 542', 37, 'available'),
('Lab 542', 38, 'available'),
('Lab 542', 39, 'available'),
('Lab 542', 40, 'available'),
('Lab 542', 41, 'available'),
('Lab 542', 42, 'available'),
('Lab 542', 43, 'available'),
('Lab 542', 44, 'available'),
('Lab 542', 45, 'available'),
('Lab 542', 46, 'available'),
('Lab 542', 47, 'available'),
('Lab 542', 48, 'available'),
('Lab 542', 49, 'available'),
('Lab 542', 50, 'available'),
('Lab 542', 51, 'available'),
('Lab 542', 52, 'available'),
('Lab 542', 53, 'available'),
('Lab 542', 54, 'available'),
('Lab 542', 55, 'available'),
('Lab 542', 56, 'available'),
('Lab 544', 1, 'available'),
('Lab 544', 2, 'available'),
('Lab 544', 3, 'available'),
('Lab 544', 4, 'available'),
('Lab 544', 5, 'available'),
('Lab 544', 6, 'available'),
('Lab 544', 7, 'available'),
('Lab 544', 8, 'available'),
('Lab 544', 9, 'available'),
('Lab 544', 10, 'available'),
('Lab 544', 11, 'available'),
('Lab 544', 12, 'available'),
('Lab 544', 13, 'available'),
('Lab 544', 14, 'available'),
('Lab 544', 15, 'available'),
('Lab 544', 16, 'available'),
('Lab 544', 17, 'available'),
('Lab 544', 18, 'available'),
('Lab 544', 19, 'available'),
('Lab 544', 20, 'available'),
('Lab 544', 21, 'available'),
('Lab 544', 22, 'available'),
('Lab 544', 23, 'available'),
('Lab 544', 24, 'available'),
('Lab 544', 25, 'available'),
('Lab 544', 26, 'available'),
('Lab 544', 27, 'available'),
('Lab 544', 28, 'available'),
('Lab 544', 29, 'available'),
('Lab 544', 30, 'available'),
('Lab 544', 31, 'available'),
('Lab 544', 32, 'available'),
('Lab 544', 33, 'available'),
('Lab 544', 34, 'available'),
('Lab 544', 35, 'available'),
('Lab 544', 36, 'available'),
('Lab 544', 37, 'available'),
('Lab 544', 38, 'available'),
('Lab 544', 39, 'available'),
('Lab 544', 40, 'available'),
('Lab 544', 41, 'available'),
('Lab 544', 42, 'available'),
('Lab 544', 43, 'available'),
('Lab 544', 44, 'available'),
('Lab 544', 45, 'available'),
('Lab 544', 46, 'available'),
('Lab 544', 47, 'available'),
('Lab 544', 48, 'available'),
('Lab 544', 49, 'available'),
('Lab 544', 50, 'available'),
('Lab 544', 51, 'available'),
('Lab 544', 52, 'available'),
('Lab 544', 53, 'available'),
('Lab 544', 54, 'available'),
('Lab 544', 55, 'available'),
('Lab 544', 56, 'available');

-- ============================================================
-- DONE
-- ============================================================


-- ============================================================
-- MOCK DATA FOR UC SIT-IN SYSTEM
-- Run after database/final_create_tables_sitin.sql
-- Passwords:
--   Admin: admin / admin123
--   Students: 1001 / user1001 up to 1020 / user1020
-- ============================================================

USE sitin;

SET FOREIGN_KEY_CHECKS = 0;

-- Ensure default admin exists
INSERT INTO admins (username, password, fullname, email)
VALUES ('admin', '$2y$12$drQIt3S7CflU.eUHwKoMaOZFkvu7fNZkRqS0Ei.LOrBgree2ZcIk6', 'Administrator', 'admin@uc.edu.ph')
ON DUPLICATE KEY UPDATE
  password = VALUES(password),
  fullname = VALUES(fullname),
  email = VALUES(email);

-- Announcements
INSERT INTO announcements (title, message, posted_by, created_at) VALUES
('Welcome to the UC Sit-in System','Students may now reserve laboratory sessions online. Please always check your schedule before proceeding to the lab.','Administrator', NOW() - INTERVAL 1 DAY),
('Laboratory Rules Reminder','Eating, drinking, and installing unauthorized software inside the laboratories are strictly prohibited.','Administrator', NOW() - INTERVAL 2 DAY),
('Reward Points Enabled','Students can now earn reward points from completed tasks and redeem 10 points for 1 additional sit-in session.','Administrator', NOW() - INTERVAL 3 DAY),
('Software Availability Updated','Installed applications per laboratory are now viewable from the student dashboard and reservation page.','Administrator', NOW() - INTERVAL 4 DAY);

-- Students
INSERT INTO students
(studentid, firstname, middlename, lastname, course, yearlvl, email, addrs, password, session_credits, reward_points, reward_points_earned, task_completed)
VALUES
('1001','Liam','Santos','Cruz','BSIT','3','liam.santos@uc.edu.ph','Cebu City','$2y$12$N9JxgBxiBrD3zFKEZ/ilmuyOCv1P/DeaJd6kR8JT9fcahSP8W6hr2',28,0,0,0),
('1002','Ava','Reyes','Garcia','BSCS','2','ava.garcia@uc.edu.ph','Mandaue City','$2y$12$APCOaXhPl2p4W7/RHEwcWeuIFMS.2UHwQKtAeal1GTHENcI/AbcL.',19,0,0,0),
('1003','Noah','Dela Cruz','Villanueva','BSIT','4','noah.villanueva@uc.edu.ph','Lapu-Lapu City','$2y$12$FMqGUwMVyCq1ychA7BlRsuwt65y4jwS5HZoz8F67k.bVO8sOjPlNm',18,0,0,0),
('1004','Mia','Lopez','Ramos','BSIS','1','mia.ramos@uc.edu.ph','Talisay City','$2y$12$vEbaxV9lEgkBDxQWIzhp8ebMdIJZZ9uA3ei9xe3lcy.emGg4oVKHG',29,0,0,0),
('1005','Ethan','Mendoza','Torres','BSIT','2','ethan.torres@uc.edu.ph','Cebu City','$2y$12$fqkJ8el.tdLn2TAGQZEYDuP1i/ErNwB.zexcwbTDUaQN4ZFhPm0BS',22,0,0,0),
('1006','Sophia','Flores','Navarro','BSCS','3','sophia.navarro@uc.edu.ph','Consolacion','$2y$12$LTchlZjMcUXtcmUa6UB2SeAlOOZdZjE8OQN7F0OdtncifkWfwXhs6',21,0,0,0),
('1007','Lucas','Bautista','Rivera','BSIT','1','lucas.rivera@uc.edu.ph','Minglanilla','$2y$12$BFtqb9nJXQyC.SAHYuFLI.xF6/wKesssuYCUdMjXnAsh1ICmpJHSe',21,0,0,0),
('1008','Isabella','Aquino','Morales','BSIS','4','isabella.morales@uc.edu.ph','Cebu City','$2y$12$AoHFqK39UkN1T786wTReHOM.r5gd37WYpRnwxO0.O3Jz3jrQR5vee',20,0,0,0),
('1009','James','Castillo','Perez','BSIT','3','james.perez@uc.edu.ph','Mandaue City','$2y$12$wsASLrOqhVVeHFdxrNR6T.CNi0Zx8YgfEM0b.4nXrRtwOfsHJleva',29,0,0,0),
('1010','Charlotte','Gonzales','Lim','BSCS','2','charlotte.lim@uc.edu.ph','Lapu-Lapu City','$2y$12$AEfplz5ZiK/oOMzwWTTue.1juMYJEGLLOkYUctYcBiQrjGeZMhZTC',19,0,0,0),
('1011','Benjamin','Chua','Tan','BSIT','4','benjamin.tan@uc.edu.ph','Cebu City','$2y$12$LA4okydfo9Jeb1frMnExEOVTjQQPWl4lWNa0fiILU/mNjnNpwn6PW',28,0,0,0),
('1012','Amelia','Uy','Sy','BSIS','3','amelia.sy@uc.edu.ph','Talisay City','$2y$12$WQa1fgoZ3Rm.t9vhtEXMg.XsovRr.zmS.e15Bombnbr5TgQCAMQ5q',29,0,0,0),
('1013','Mason','Ong','Lee','BSCS','1','mason.lee@uc.edu.ph','Cebu City','$2y$12$JJ/2vGfsK8ddDWZbE4JHvenpIsjWW3m0D5CfpgqRGQJnaJS7DFlJS',26,0,0,0),
('1014','Harper','Cabrera','Diaz','BSIT','2','harper.diaz@uc.edu.ph','Consolacion','$2y$12$Gr9aUqEXD3FKYNxp1L0DM.3dLd7tKibyQepTwqz4WawdKHleKUkPK',19,0,0,0),
('1015','Elijah','Fernandez','Mercado','BSIS','4','elijah.mercado@uc.edu.ph','Mandaue City','$2y$12$m7To3s6E4jbtJt5g5uKUD.7NXE4eHkU85PKOV.DEaZe9bCIF13uDC',27,0,0,0),
('1016','Evelyn','Pineda','Salazar','BSIT','3','evelyn.salazar@uc.edu.ph','Lapu-Lapu City','$2y$12$H/UvY1IUHNxD8xLjMIf4HeFucKonbi6/YXqqwxurAcHRB8b/XKVAC',24,0,0,0),
('1017','Daniel','Cortez','Domingo','BSCS','4','daniel.domingo@uc.edu.ph','Cebu City','$2y$12$rbvG7HqBjLIBsU2KoDBuOuBYVkwXxfejfv1ew0KMPMuuMKpLrUi4O',18,0,0,0),
('1018','Abigail','Padilla','Rosales','BSIT','1','abigail.rosales@uc.edu.ph','Minglanilla','$2y$12$OQoNxYZttGSLS2SvWbUFKegqD3y/yEz0n4Eg/tKPq4hzyfwXG/wzm',18,0,0,0),
('1019','Henry','Marquez','Valdez','BSIS','2','henry.valdez@uc.edu.ph','Cebu City','$2y$12$SflNM4WlezeDoIXvveJR.uuXn528NpuLaJBxFKQFEX2MDcYbAGLMK',19,0,0,0),
('1020','Emily','Aguilar','Fuentes','BSIT','3','emily.fuentes@uc.edu.ph','Talisay City','$2y$12$IneT6T.WyBDMARo9WKC8meAt3BxN5xOyr7ikHaEm/G9Hmo3AeJkD6',21,0,0,0)
ON DUPLICATE KEY UPDATE
  firstname = VALUES(firstname),
  middlename = VALUES(middlename),
  lastname = VALUES(lastname),
  course = VALUES(course),
  yearlvl = VALUES(yearlvl),
  email = VALUES(email),
  addrs = VALUES(addrs),
  password = VALUES(password),
  session_credits = VALUES(session_credits);

-- Sit-in records
INSERT INTO sitin_records
(student_id, studentid, fullname, purpose, lab, pc_number, session_at_sitin, login_time, logout_time, duration_minutes, status, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),'1001','Cruz, Liam Santos','Networking Activity','Lab 526',46,8,'2026-03-15 14:00:00','2026-03-15 16:30:00',150,'done','2026-03-15 14:00:00'),
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),'1001','Cruz, Liam Santos','Networking Activity','Lab 526',45,11,'2026-03-22 14:15:00','2026-03-22 15:15:00',60,'done','2026-03-22 14:15:00'),
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),'1001','Cruz, Liam Santos','Networking Activity','Lab 524',6,4,'2026-04-13 09:00:00','2026-04-13 10:30:00',90,'done','2026-04-13 09:00:00'),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Web Development Activity','Lab 544',30,30,'2026-04-04 14:15:00','2026-04-04 15:15:00',60,'done','2026-04-04 14:15:00'),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Mobile App Development','Lab 544',40,19,'2026-03-31 08:30:00','2026-03-31 10:00:00',90,'done','2026-03-31 08:30:00'),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Capstone Research','Lab 544',15,3,'2026-04-24 15:00:00','2026-04-24 16:00:00',60,'done','2026-04-24 15:00:00'),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Mobile App Development','Lab 528',30,6,'2026-04-19 16:00:00','2026-04-19 18:00:00',120,'done','2026-04-19 16:00:00'),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Web Development Activity','Lab 544',44,20,'2026-04-03 09:30:00','2026-04-03 11:00:00',90,'done','2026-04-03 09:30:00'),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Networking Activity','Lab 526',30,9,'2026-04-27 14:30:00','2026-04-27 15:45:00',75,'done','2026-04-27 14:30:00'),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','C Programming Practice','Lab 524',15,26,'2026-03-08 09:30:00','2026-03-08 11:00:00',90,'done','2026-03-08 09:30:00'),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Mobile App Development','Lab 526',37,7,'2026-04-08 13:15:00','2026-04-08 14:15:00',60,'done','2026-04-08 13:15:00'),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Java OOP Practice','Lab 528',9,24,'2026-03-29 15:15:00','2026-03-29 16:30:00',75,'done','2026-03-29 15:15:00'),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Networking Activity','Lab 542',28,12,'2026-03-08 14:15:00','2026-03-08 17:15:00',180,'done','2026-03-08 14:15:00'),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','C Programming Practice','Lab 524',49,28,'2026-04-20 09:30:00','2026-04-20 11:30:00',120,'done','2026-04-20 09:30:00'),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Web Development Activity','Lab 544',28,13,'2026-05-04 09:30:00','2026-05-04 10:45:00',75,'done','2026-05-04 09:30:00'),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','C Programming Practice','Lab 528',36,22,'2026-03-31 14:15:00','2026-03-31 16:45:00',150,'done','2026-03-31 14:15:00'),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Capstone Research','Lab 528',8,14,'2026-03-11 16:15:00','2026-03-11 19:15:00',180,'done','2026-03-11 16:15:00'),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Database Laboratory','Lab 544',17,17,'2026-04-28 13:00:00','2026-04-28 16:00:00',180,'done','2026-04-28 13:00:00'),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','Database Laboratory','Lab 542',13,12,'2026-04-10 16:30:00','2026-04-10 19:00:00',150,'done','2026-04-10 16:30:00'),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','UI/UX Design Activity','Lab 542',21,1,'2026-04-28 14:30:00','2026-04-28 15:30:00',60,'done','2026-04-28 14:30:00'),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','Web Development Activity','Lab 524',16,3,'2026-04-02 16:15:00','2026-04-02 17:30:00',75,'done','2026-04-02 16:15:00'),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','UI/UX Design Activity','Lab 526',9,18,'2026-03-17 16:00:00','2026-03-17 18:30:00',150,'done','2026-03-17 16:00:00'),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','Java OOP Practice','Lab 526',35,23,'2026-04-15 14:30:00','2026-04-15 16:30:00',120,'done','2026-04-15 14:30:00'),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','UI/UX Design Activity','Lab 528',29,4,'2026-04-09 13:30:00','2026-04-09 16:30:00',180,'done','2026-04-09 13:30:00'),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','Java OOP Practice','Lab 524',38,19,'2026-04-17 09:00:00','2026-04-17 10:30:00',90,'done','2026-04-17 09:00:00'),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','Web Development Activity','Lab 524',15,29,'2026-05-18 08:30:00','2026-05-18 11:30:00',180,'done','2026-05-18 08:30:00'),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','Capstone Research','Lab 542',16,22,'2026-05-14 16:15:00','2026-05-14 17:15:00',60,'done','2026-05-14 16:15:00'),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','UI/UX Design Activity','Lab 544',37,8,'2026-03-17 09:30:00','2026-03-17 10:45:00',75,'done','2026-03-17 09:30:00'),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Mobile App Development','Lab 544',28,14,'2026-03-27 09:00:00','2026-03-27 10:00:00',60,'done','2026-03-27 09:00:00'),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Web Development Activity','Lab 544',42,2,'2026-03-27 13:30:00','2026-03-27 14:30:00',60,'done','2026-03-27 13:30:00'),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Java OOP Practice','Lab 526',13,18,'2026-03-28 15:15:00','2026-03-28 16:15:00',60,'done','2026-03-28 15:15:00'),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Java OOP Practice','Lab 528',30,28,'2026-03-22 09:15:00','2026-03-22 10:30:00',75,'done','2026-03-22 09:15:00'),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','C Programming Practice','Lab 524',42,3,'2026-05-09 13:30:00','2026-05-09 14:30:00',60,'done','2026-05-09 13:30:00'),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','Networking Activity','Lab 526',56,29,'2026-04-27 13:15:00','2026-04-27 15:15:00',120,'done','2026-04-27 13:15:00'),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','UI/UX Design Activity','Lab 530',17,10,'2026-05-11 09:15:00','2026-05-11 10:15:00',60,'done','2026-05-11 09:15:00'),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','UI/UX Design Activity','Lab 544',46,5,'2026-03-25 15:30:00','2026-03-25 18:00:00',150,'done','2026-03-25 15:30:00'),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','C Programming Practice','Lab 544',35,24,'2026-04-11 09:00:00','2026-04-11 11:30:00',150,'done','2026-04-11 09:00:00'),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','Database Laboratory','Lab 530',33,2,'2026-04-08 08:00:00','2026-04-08 10:30:00',150,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','Java OOP Practice','Lab 542',5,13,'2026-03-14 08:00:00','2026-03-14 09:00:00',60,'done','2026-03-14 08:00:00'),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'1013','Lee, Mason Ong','Web Development Activity','Lab 524',40,14,'2026-03-07 09:30:00','2026-03-07 12:00:00',150,'done','2026-03-07 09:30:00'),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'1013','Lee, Mason Ong','Mobile App Development','Lab 528',14,8,'2026-03-05 14:30:00','2026-03-05 16:00:00',90,'done','2026-03-05 14:30:00'),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','Mobile App Development','Lab 528',30,30,'2026-03-29 09:30:00','2026-03-29 12:30:00',180,'done','2026-03-29 09:30:00'),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','Web Development Activity','Lab 542',7,18,'2026-05-09 08:15:00','2026-05-09 10:45:00',150,'done','2026-05-09 08:15:00'),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','Java OOP Practice','Lab 528',5,12,'2026-04-21 14:15:00','2026-04-21 15:30:00',75,'done','2026-04-21 14:15:00'),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','C Programming Practice','Lab 544',20,22,'2026-04-12 09:15:00','2026-04-12 11:45:00',150,'done','2026-04-12 09:15:00'),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','Database Laboratory','Lab 524',48,9,'2026-05-05 09:15:00','2026-05-05 10:15:00',60,'done','2026-05-05 09:15:00'),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','Capstone Research','Lab 528',14,17,'2026-04-12 14:00:00','2026-04-12 17:00:00',180,'done','2026-04-12 14:00:00'),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','Capstone Research','Lab 544',28,2,'2026-03-17 10:00:00','2026-03-17 11:00:00',60,'done','2026-03-17 10:00:00'),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','UI/UX Design Activity','Lab 528',11,18,'2026-05-18 10:00:00','2026-05-18 13:00:00',180,'done','2026-05-18 10:00:00'),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','C Programming Practice','Lab 544',10,27,'2026-03-08 08:00:00','2026-03-08 09:00:00',60,'done','2026-03-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','C Programming Practice','Lab 530',9,10,'2026-04-01 14:30:00','2026-04-01 15:45:00',75,'done','2026-04-01 14:30:00'),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','Java OOP Practice','Lab 526',44,22,'2026-04-02 16:00:00','2026-04-02 17:30:00',90,'done','2026-04-02 16:00:00'),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','Database Laboratory','Lab 542',48,30,'2026-05-05 10:30:00','2026-05-05 12:30:00',120,'done','2026-05-05 10:30:00'),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','Database Laboratory','Lab 530',2,24,'2026-04-18 16:00:00','2026-04-18 17:15:00',75,'done','2026-04-18 16:00:00'),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Database Laboratory','Lab 526',18,26,'2026-03-27 16:30:00','2026-03-27 19:30:00',180,'done','2026-03-27 16:30:00'),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','UI/UX Design Activity','Lab 526',13,12,'2026-05-05 13:00:00','2026-05-05 15:00:00',120,'done','2026-05-05 13:00:00'),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Java OOP Practice','Lab 524',43,13,'2026-04-09 16:00:00','2026-04-09 17:15:00',75,'done','2026-04-09 16:00:00'),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Networking Activity','Lab 528',42,22,'2026-04-06 10:00:00','2026-04-06 11:30:00',90,'done','2026-04-06 10:00:00'),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','C Programming Practice','Lab 542',17,4,'2026-05-15 08:15:00','2026-05-15 09:30:00',75,'done','2026-05-15 08:15:00'),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Web Development Activity','Lab 528',28,13,'2026-03-03 13:15:00','2026-03-03 16:15:00',180,'done','2026-03-03 13:15:00'),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','C Programming Practice','Lab 544',28,17,'2026-03-06 09:15:00','2026-03-06 10:15:00',60,'done','2026-03-06 09:15:00'),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Java OOP Practice','Lab 544',43,12,'2026-03-11 15:30:00','2026-03-11 18:30:00',180,'done','2026-03-11 15:30:00'),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Web Development Activity','Lab 528',43,24,'2026-05-10 15:15:00','2026-05-10 17:45:00',150,'done','2026-05-10 15:15:00'),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Networking Activity','Lab 530',21,23,'2026-04-10 14:15:00','2026-04-10 17:15:00',180,'done','2026-04-10 14:15:00'),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Networking Activity','Lab 530',43,22,'2026-04-11 14:00:00','2026-04-11 15:15:00',75,'done','2026-04-11 14:00:00'),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','C Programming Practice','Lab 530',36,10,'2026-04-26 14:30:00','2026-04-26 16:00:00',90,'done','2026-04-26 14:30:00'),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Mobile App Development','Lab 542',42,15,'2026-04-12 09:15:00','2026-04-12 11:45:00',150,'done','2026-04-12 09:15:00'),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','Database Laboratory','Lab 530',51,22,'2026-03-23 15:00:00','2026-03-23 17:30:00',150,'done','2026-03-23 15:00:00'),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','Mobile App Development','Lab 544',40,3,'2026-05-08 10:30:00','2026-05-08 13:30:00',180,'done','2026-05-08 10:30:00'),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','C Programming Practice','Lab 526',10,2,'2026-04-18 15:15:00','2026-04-18 16:30:00',75,'done','2026-04-18 15:15:00'),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','Java OOP Practice','Lab 530',27,23,'2026-04-17 13:30:00','2026-04-17 14:30:00',60,'done','2026-04-17 13:30:00'),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','C Programming Practice','Lab 526',42,29,'2026-03-30 13:15:00','2026-03-30 14:30:00',75,'done','2026-03-30 13:15:00'),
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),'1001','Cruz, Liam Santos','Capstone Research','Lab 530',15,NULL,'2026-05-19 08:00:00',NULL,0,'active','2026-05-19 08:00:00'),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Capstone Research','Lab 544',34,NULL,'2026-05-19 09:00:00',NULL,0,'active','2026-05-19 09:00:00'),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Capstone Research','Lab 524',36,NULL,'2026-05-19 13:00:00',NULL,0,'active','2026-05-19 13:00:00');

-- Lab reservations
INSERT INTO lab_reservations
(student_id, studentid, fullname, purpose, lab, pc_number, reservation_date, reservation_time, reservation_end_time, status, admin_note, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),'1001','Cruz, Liam Santos','UI/UX Design Activity','Lab 526',52,'2026-06-07','08:00:00','09:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 21 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Networking Activity','Lab 530',40,'2026-05-26','14:00:00','15:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 26 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','UI/UX Design Activity','Lab 526',48,'2026-06-07','14:00:00','15:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 14 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Java OOP Practice','Lab 542',32,'2026-05-17','09:00:00','10:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Mobile App Development','Lab 526',18,'2026-05-11','15:00:00','16:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Java OOP Practice','Lab 526',10,'2026-06-06','14:00:00','15:00:00','pending','Auto-generated mock reservation',NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Networking Activity','Lab 524',27,'2026-05-13','15:00:00','16:00:00','approved','Auto-generated mock reservation',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Networking Activity','Lab 524',14,'2026-05-26','13:00:00','14:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','UI/UX Design Activity','Lab 542',25,'2026-06-06','14:00:00','15:00:00','pending','Auto-generated mock reservation',NOW() - INTERVAL 0 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Java OOP Practice','Lab 542',48,'2026-05-18','13:00:00','14:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 15 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Networking Activity','Lab 530',2,'2026-05-16','10:00:00','11:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','C Programming Practice','Lab 530',9,'2026-05-30','13:00:00','14:00:00','approved','Auto-generated mock reservation',NOW() - INTERVAL 29 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','Web Development Activity','Lab 544',2,'2026-05-21','14:00:00','15:00:00','cancelled','Auto-generated mock reservation',NOW() - INTERVAL 20 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','Capstone Research','Lab 526',4,'2026-05-22','09:00:00','10:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','Networking Activity','Lab 528',49,'2026-05-15','13:00:00','14:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','C Programming Practice','Lab 524',31,'2026-06-02','13:00:00','14:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 23 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','C Programming Practice','Lab 544',5,'2026-05-10','10:00:00','11:00:00','approved','Auto-generated mock reservation',NOW() - INTERVAL 24 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','Database Laboratory','Lab 524',40,'2026-05-09','09:00:00','10:00:00','approved','Auto-generated mock reservation',NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','UI/UX Design Activity','Lab 542',14,'2026-05-13','13:00:00','14:00:00','pending','Auto-generated mock reservation',NOW() - INTERVAL 22 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','Web Development Activity','Lab 542',39,'2026-06-02','10:00:00','11:00:00','approved','Auto-generated mock reservation',NOW() - INTERVAL 24 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','C Programming Practice','Lab 524',38,'2026-06-04','09:00:00','10:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 29 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Java OOP Practice','Lab 530',46,'2026-05-27','15:00:00','16:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Capstone Research','Lab 524',45,'2026-05-27','15:00:00','16:00:00','approved','Auto-generated mock reservation',NOW() - INTERVAL 27 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','Networking Activity','Lab 524',23,'2026-05-28','08:00:00','09:00:00','cancelled','Auto-generated mock reservation',NOW() - INTERVAL 21 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','C Programming Practice','Lab 544',22,'2026-05-20','08:00:00','09:00:00','cancelled','Auto-generated mock reservation',NOW() - INTERVAL 27 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','UI/UX Design Activity','Lab 530',24,'2026-05-22','13:00:00','14:00:00','pending','Auto-generated mock reservation',NOW() - INTERVAL 22 DAY),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','UI/UX Design Activity','Lab 544',18,'2026-05-22','09:00:00','10:00:00','cancelled','Auto-generated mock reservation',NOW() - INTERVAL 14 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'1013','Lee, Mason Ong','Java OOP Practice','Lab 528',21,'2026-06-04','15:00:00','16:00:00','cancelled','Auto-generated mock reservation',NOW() - INTERVAL 26 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'1013','Lee, Mason Ong','UI/UX Design Activity','Lab 530',16,'2026-06-07','08:00:00','09:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 18 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','Mobile App Development','Lab 524',32,'2026-05-30','13:00:00','14:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','Capstone Research','Lab 528',22,'2026-05-24','09:00:00','10:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 28 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','Java OOP Practice','Lab 542',1,'2026-05-28','15:00:00','16:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','Java OOP Practice','Lab 542',49,'2026-06-01','13:00:00','14:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 22 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','C Programming Practice','Lab 530',51,'2026-05-29','15:00:00','16:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','Capstone Research','Lab 544',16,'2026-05-18','09:00:00','10:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 21 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Networking Activity','Lab 542',23,'2026-05-20','13:00:00','14:00:00','cancelled','Auto-generated mock reservation',NOW() - INTERVAL 23 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Capstone Research','Lab 544',30,'2026-05-26','10:00:00','11:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 9 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Mobile App Development','Lab 544',13,'2026-05-17','09:00:00','10:00:00','pending','Auto-generated mock reservation',NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','UI/UX Design Activity','Lab 526',14,'2026-05-26','15:00:00','16:00:00','approved','Auto-generated mock reservation',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Web Development Activity','Lab 542',19,'2026-06-01','14:00:00','15:00:00','cancelled','Auto-generated mock reservation',NOW() - INTERVAL 26 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Capstone Research','Lab 528',12,'2026-05-15','10:00:00','11:00:00','approved','Auto-generated mock reservation',NOW() - INTERVAL 0 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Capstone Research','Lab 524',4,'2026-05-26','09:00:00','10:00:00','rejected','Auto-generated mock reservation',NOW() - INTERVAL 22 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','C Programming Practice','Lab 524',56,'2026-06-08','09:00:00','10:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 18 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Database Laboratory','Lab 530',22,'2026-05-18','13:00:00','14:00:00','completed','Auto-generated mock reservation',NOW() - INTERVAL 30 DAY),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','UI/UX Design Activity','Lab 524',26,'2026-05-17','13:00:00','14:00:00','pending','Auto-generated mock reservation',NOW() - INTERVAL 2 DAY);

-- Reward ratings
INSERT INTO reward_point_logs
(student_id, reward_percent, task_percent, points_added, task_added, reason, awarded_by, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),50,50,5.00,5.00,'Completed assigned research task','Administrator',NOW() - INTERVAL 20 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),50,25,5.00,2.50,'Completed assigned research task','Administrator',NOW() - INTERVAL 27 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),100,100,10.00,10.00,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 20 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),100,75,10.00,7.50,'Completed assigned research task','Administrator',NOW() - INTERVAL 40 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),25,50,2.50,5.00,'Submitted database activity','Administrator',NOW() - INTERVAL 17 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),50,50,5.00,5.00,'Submitted database activity','Administrator',NOW() - INTERVAL 36 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),50,25,5.00,2.50,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 29 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),75,25,7.50,2.50,'Submitted database activity','Administrator',NOW() - INTERVAL 19 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),75,100,7.50,10.00,'Completed programming task','Administrator',NOW() - INTERVAL 15 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),75,50,7.50,5.00,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),50,50,5.00,5.00,'Finished laboratory exercise','Administrator',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),25,50,2.50,5.00,'Finished laboratory exercise','Administrator',NOW() - INTERVAL 39 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),100,25,10.00,2.50,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 20 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),100,75,10.00,7.50,'Completed assigned research task','Administrator',NOW() - INTERVAL 35 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),100,100,10.00,10.00,'Completed programming task','Administrator',NOW() - INTERVAL 39 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),100,75,10.00,7.50,'Completed assigned research task','Administrator',NOW() - INTERVAL 17 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),25,50,2.50,5.00,'Completed assigned research task','Administrator',NOW() - INTERVAL 38 DAY),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),75,25,7.50,2.50,'Submitted database activity','Administrator',NOW() - INTERVAL 31 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),75,50,7.50,5.00,'Completed assigned research task','Administrator',NOW() - INTERVAL 28 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),100,25,10.00,2.50,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 23 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),100,75,10.00,7.50,'Finished laboratory exercise','Administrator',NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),50,75,5.00,7.50,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 32 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),100,25,10.00,2.50,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),75,75,7.50,7.50,'Finished laboratory exercise','Administrator',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),100,25,10.00,2.50,'Completed assigned research task','Administrator',NOW() - INTERVAL 30 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),25,50,2.50,5.00,'Completed assigned research task','Administrator',NOW() - INTERVAL 24 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),100,100,10.00,10.00,'Completed programming task','Administrator',NOW() - INTERVAL 14 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),75,50,7.50,5.00,'Finished laboratory exercise','Administrator',NOW() - INTERVAL 29 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),100,25,10.00,2.50,'Completed programming task','Administrator',NOW() - INTERVAL 39 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),50,75,5.00,7.50,'Completed assigned research task','Administrator',NOW() - INTERVAL 1 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),100,25,10.00,2.50,'Submitted database activity','Administrator',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),25,50,2.50,5.00,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 19 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),75,100,7.50,10.00,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 31 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),50,100,5.00,10.00,'Completed assigned research task','Administrator',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),100,50,10.00,5.00,'Completed assigned research task','Administrator',NOW() - INTERVAL 33 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),25,75,2.50,7.50,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 22 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),75,25,7.50,2.50,'Finished laboratory exercise','Administrator',NOW() - INTERVAL 20 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),50,100,5.00,10.00,'Completed assigned research task','Administrator',NOW() - INTERVAL 31 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),75,75,7.50,7.50,'Completed assigned research task','Administrator',NOW() - INTERVAL 35 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),100,100,10.00,10.00,'Finished laboratory exercise','Administrator',NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),50,100,5.00,10.00,'Submitted database activity','Administrator',NOW() - INTERVAL 27 DAY),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),75,100,7.50,10.00,'Helped maintain proper lab conduct','Administrator',NOW() - INTERVAL 25 DAY);

-- Sync student reward totals from reward logs
UPDATE students s
LEFT JOIN (
  SELECT student_id, COALESCE(SUM(points_added),0) AS reward_total, COALESCE(SUM(task_added),0) AS task_total
  FROM reward_point_logs
  GROUP BY student_id
) r ON r.student_id = s.id
SET
  s.reward_points = COALESCE(r.reward_total, 0),
  s.reward_points_earned = COALESCE(r.reward_total, 0),
  s.task_completed = COALESCE(r.task_total, 0);

-- Reward redemptions
INSERT INTO reward_redemption_logs (student_id, points_used, sessions_added, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),10.00,1,NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),10.00,1,NOW() - INTERVAL 16 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),10.00,1,NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),10.00,1,NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),10.00,1,NOW() - INTERVAL 17 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),10.00,1,NOW() - INTERVAL 19 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),10.00,1,NOW() - INTERVAL 11 DAY);

-- Apply redemption effects to spendable balance and session credits only
UPDATE students
SET reward_points = GREATEST(0, reward_points - 10),
    session_credits = session_credits + 1
WHERE studentid IN ('1001','1003','1005','1008','1011','1014','1017');

-- Feedback
INSERT INTO feedback
(sitin_id, student_id, studentid, student_name, lab, purpose, issue_type, feedback_text, status, created_at)
VALUES
((SELECT id FROM sitin_records WHERE studentid='1001' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1001' LIMIT 1),'1001','Cruz, Liam Santos','Lab 524','UI/UX Design Activity','Computer Issue','Laboratory was clean and organized.','new',NOW() - INTERVAL 24 DAY),
((SELECT id FROM sitin_records WHERE studentid='1002' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Lab 544','Database Laboratory','Network Issue','Laboratory was clean and organized.','new',NOW() - INTERVAL 16 DAY),
((SELECT id FROM sitin_records WHERE studentid='1003' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Lab 542','Networking Activity','Software Issue','Requested software was available and working.','resolved',NOW() - INTERVAL 3 DAY),
((SELECT id FROM sitin_records WHERE studentid='1004' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Lab 530','Mobile App Development','Software Issue','Mouse/keyboard needs checking for future users.','resolved',NOW() - INTERVAL 23 DAY),
((SELECT id FROM sitin_records WHERE studentid='1005' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Lab 524','Web Development Activity','General','Mouse/keyboard needs checking for future users.','new',NOW() - INTERVAL 21 DAY),
((SELECT id FROM sitin_records WHERE studentid='1006' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','Lab 544','Web Development Activity','Software Issue','Internet connection was unstable during the session.','reviewed',NOW() - INTERVAL 4 DAY),
((SELECT id FROM sitin_records WHERE studentid='1007' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','Lab 526','Capstone Research','Computer Issue','Laboratory was clean and organized.','new',NOW() - INTERVAL 2 DAY),
((SELECT id FROM sitin_records WHERE studentid='1008' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','Lab 528','Mobile App Development','Software Issue','PC performed well during the activity.','reviewed',NOW() - INTERVAL 14 DAY),
((SELECT id FROM sitin_records WHERE studentid='1009' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','Lab 542','Networking Activity','Network Issue','Internet connection was unstable during the session.','resolved',NOW() - INTERVAL 22 DAY),
((SELECT id FROM sitin_records WHERE studentid='1010' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Lab 526','Web Development Activity','Network Issue','Internet connection was unstable during the session.','resolved',NOW() - INTERVAL 28 DAY),
((SELECT id FROM sitin_records WHERE studentid='1011' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','Lab 544','Java OOP Practice','General','Mouse/keyboard needs checking for future users.','reviewed',NOW() - INTERVAL 30 DAY),
((SELECT id FROM sitin_records WHERE studentid='1012' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','Lab 526','UI/UX Design Activity','Facility Concern','Internet connection was unstable during the session.','resolved',NOW() - INTERVAL 9 DAY);

-- Testimonials
INSERT INTO testimonials (student_id, rating, message, status, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),5,'The reward points feature motivates students to complete tasks.','approved',NOW() - INTERVAL 29 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),5,'The reward points feature motivates students to complete tasks.','approved',NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),5,'The reward points feature motivates students to complete tasks.','approved',NOW() - INTERVAL 21 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),5,'The reward points feature motivates students to complete tasks.','pending',NOW() - INTERVAL 28 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),5,'I like that I can view available software before reserving a lab.','pending',NOW() - INTERVAL 28 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),5,'The sit-in system makes laboratory reservations easier and faster.','approved',NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),5,'The dashboard is clear and easy to use.','approved',NOW() - INTERVAL 23 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),5,'The sit-in system makes laboratory reservations easier and faster.','pending',NOW() - INTERVAL 9 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),4,'The dashboard is clear and easy to use.','approved',NOW() - INTERVAL 30 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),5,'The reward points feature motivates students to complete tasks.','approved',NOW() - INTERVAL 20 DAY);

-- Student notifications
INSERT INTO student_notifications (student_id, type, title, message, is_read, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 4 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 4 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 14 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 15 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 1 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 1 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'announcement','System Update','Please check the latest announcements and laboratory rules.',0,NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'reward','Reward Points Added','You received reward points from your recent laboratory activity.',0,NOW() - INTERVAL 2 DAY);

-- Session reset logs
INSERT INTO session_reset_logs
(reset_title, total_students, total_credits_before, total_credits_after, reset_by, created_at)
VALUES
('Mock Midterm Session Reset', 20, 420, 600, 'Administrator', NOW() - INTERVAL 45 DAY),
('Mock Final Practice Reset', 20, 390, 600, 'Administrator', NOW() - INTERVAL 15 DAY);

-- Mock past leaderboard archive
INSERT INTO leaderboard_archives
(title, season_started_at, season_ended_at, created_by, created_at)
VALUES
('Mock Midterm Leaderboard', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY), 'Administrator', DATE_SUB(NOW(), INTERVAL 30 DAY));

SET @mock_archive_id = LAST_INSERT_ID();
SET @rank_no = 0;

INSERT INTO leaderboard_archive_entries
(
  archive_id, student_id, studentid, fullname, course, yearlvl, rank_no,
  reward_points_earned, reward_points_balance, task_points,
  total_sessions, total_minutes, assessment_count,
  reward_score, hour_score, task_score, final_score
)
SELECT
  @mock_archive_id AS archive_id,
  ranked.id AS student_id,
  ranked.studentid,
  ranked.fullname,
  ranked.course,
  ranked.yearlvl,
  (@rank_no := @rank_no + 1) AS rank_no,
  ranked.reward_points_earned,
  ranked.reward_points_balance,
  ranked.task_points,
  ranked.total_sessions,
  ranked.total_minutes,
  ranked.assessment_count,
  ranked.reward_score,
  ranked.hour_score,
  ranked.task_score,
  ranked.final_score
FROM (
  SELECT
    s.id,
    s.studentid,
    CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename) AS fullname,
    s.course,
    s.yearlvl,
    COALESCE(s.reward_points_earned, 0) AS reward_points_earned,
    COALESCE(s.reward_points, 0) AS reward_points_balance,
    COALESCE(s.task_completed, 0) AS task_points,
    COALESCE(sr.total_sessions, 0) AS total_sessions,
    COALESCE(sr.total_minutes, 0) AS total_minutes,
    COALESCE(rpl.assessment_count, 0) AS assessment_count,
    LEAST(100, COALESCE(s.reward_points_earned, 0) / GREATEST(COALESCE(rpl.assessment_count, 0) * 10, 1) * 100) AS reward_score,
    LEAST(100, COALESCE(sr.total_minutes, 0) / 1800 * 100) AS hour_score,
    LEAST(100, COALESCE(s.task_completed, 0) / GREATEST(COALESCE(rpl.assessment_count, 0) * 10, 1) * 100) AS task_score,
    ROUND(
      (LEAST(100, COALESCE(s.reward_points_earned, 0) / GREATEST(COALESCE(rpl.assessment_count, 0) * 10, 1) * 100) * 0.60) +
      (LEAST(100, COALESCE(sr.total_minutes, 0) / 1800 * 100) * 0.20) +
      (LEAST(100, COALESCE(s.task_completed, 0) / GREATEST(COALESCE(rpl.assessment_count, 0) * 10, 1) * 100) * 0.20),
      2
    ) AS final_score
  FROM students s
  LEFT JOIN (
    SELECT student_id, COUNT(*) AS total_sessions, COALESCE(SUM(duration_minutes), 0) AS total_minutes
    FROM sitin_records
    WHERE status IN ('done', 'completed')
    GROUP BY student_id
  ) sr ON sr.student_id = s.id
  LEFT JOIN (
    SELECT student_id, COUNT(*) AS assessment_count
    FROM reward_point_logs
    GROUP BY student_id
  ) rpl ON rpl.student_id = s.id
  ORDER BY final_score DESC
) ranked;



-- Make sure mock sit-in records always show a session number in Sit-in Records/History.
-- This fixes old mock records where session_at_sitin was NULL.
SET @prev_student := NULL;
SET @session_no := 31;
UPDATE sitin_records s
JOIN (
  SELECT ordered.id,
         @session_no := IF(@prev_student = ordered.student_id, @session_no - 1, 30) AS computed_session,
         @prev_student := ordered.student_id AS student_marker
  FROM (
    SELECT id, student_id, login_time
    FROM sitin_records
    WHERE session_at_sitin IS NULL
    ORDER BY student_id, login_time ASC, id ASC
  ) ordered
  CROSS JOIN (SELECT @prev_student := NULL, @session_no := 31) vars
) fixed ON fixed.id = s.id
SET s.session_at_sitin = GREATEST(fixed.computed_session, 1)
WHERE s.session_at_sitin IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
