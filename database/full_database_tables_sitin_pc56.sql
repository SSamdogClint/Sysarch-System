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
