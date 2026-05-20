-- ============================================================
-- Sysarch-System / UC Sit-in System
-- FULL DATABASE TABLE QUERY + ACCURATE RELATED MOCK DATA
-- Database name: sitin
-- Run this in phpMyAdmin SQL tab.
--
-- Login accounts:
--   Admin:    username = admin, password = admin123
--   Students: username/studentid = 1001 to 1020
--             password = user1001 to user1020
--
-- IMPORTANT:
--   This script resets the mock/sample data tables so the relationships are clean.
--   Use this for testing/demo database only.
-- ============================================================

CREATE DATABASE IF NOT EXISTS sitin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE sitin;
SET time_zone = '+08:00';

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(150) DEFAULT 'Administrator',
  email VARCHAR(150) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  session_credits INT NOT NULL DEFAULT 30,
  reward_points DECIMAL(10,2) NOT NULL DEFAULT 0,
  reward_points_earned DECIMAL(10,2) NOT NULL DEFAULT 0,
  task_completed DECIMAL(10,2) NOT NULL DEFAULT 0,
  avatar VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE students ADD COLUMN IF NOT EXISTS middlename VARCHAR(100) DEFAULT '';
ALTER TABLE students ADD COLUMN IF NOT EXISTS session_credits INT NOT NULL DEFAULT 30;
ALTER TABLE students ADD COLUMN IF NOT EXISTS reward_points DECIMAL(10,2) NOT NULL DEFAULT 0;
ALTER TABLE students ADD COLUMN IF NOT EXISTS reward_points_earned DECIMAL(10,2) NOT NULL DEFAULT 0;
ALTER TABLE students ADD COLUMN IF NOT EXISTS task_completed DECIMAL(10,2) NOT NULL DEFAULT 0;
ALTER TABLE students ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL;
ALTER TABLE students ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS labs (
  lab VARCHAR(100) PRIMARY KEY,
  description VARCHAR(150) DEFAULT NULL,
  status ENUM('available','maintenance','unavailable') NOT NULL DEFAULT 'available',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE IF NOT EXISTS sitin_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT NULL,
  student_id INT NULL,
  studentid VARCHAR(50) NOT NULL,
  fullname VARCHAR(180) NOT NULL,
  purpose VARCHAR(150) NOT NULL,
  lab VARCHAR(100) NOT NULL,
  pc_number INT NULL,
  session_at_sitin INT DEFAULT NULL,
  login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  logout_time DATETIME NULL,
  duration_minutes INT NOT NULL DEFAULT 0,
  status ENUM('active','done','completed') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sitin_reservation_id (reservation_id),
  INDEX idx_sitin_student_id (student_id),
  INDEX idx_sitin_studentid (studentid),
  INDEX idx_sitin_status (status),
  INDEX idx_sitin_login_time (login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE sitin_records ADD COLUMN IF NOT EXISTS reservation_id INT NULL AFTER id;
ALTER TABLE sitin_records ADD COLUMN IF NOT EXISTS student_id INT NULL;
ALTER TABLE sitin_records ADD COLUMN IF NOT EXISTS pc_number INT NULL;
ALTER TABLE sitin_records ADD COLUMN IF NOT EXISTS session_at_sitin INT DEFAULT NULL;
ALTER TABLE sitin_records ADD COLUMN IF NOT EXISTS logout_time DATETIME NULL;
ALTER TABLE sitin_records ADD COLUMN IF NOT EXISTS duration_minutes INT NOT NULL DEFAULT 0;
ALTER TABLE sitin_records ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE sitin_records MODIFY status ENUM('active','done','completed') NOT NULL DEFAULT 'active';

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
  status ENUM('pending','approved','rejected','cancelled','done','completed','no_show') NOT NULL DEFAULT 'pending',
  approved_by INT NULL,
  approved_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  completed_at DATETIME NULL,
  rejected_at DATETIME NULL,
  no_show_at DATETIME NULL,
  admin_note VARCHAR(255) DEFAULT NULL,
  admin_remarks VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_reservation_student_id (student_id),
  INDEX idx_reservation_studentid (studentid),
  INDEX idx_reservation_status (status),
  INDEX idx_reservation_date (reservation_date),
  INDEX idx_reservation_lab_pc_date (lab, pc_number, reservation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS student_id INT NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS studentid VARCHAR(50) DEFAULT NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS fullname VARCHAR(180) DEFAULT NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS pc_number INT NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS reservation_end_time TIME NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS approved_by INT NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS completed_at DATETIME NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS rejected_at DATETIME NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS no_show_at DATETIME NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS admin_note VARCHAR(255) DEFAULT NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS admin_remarks VARCHAR(255) DEFAULT NULL;
ALTER TABLE lab_reservations ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;
UPDATE lab_reservations SET status = 'completed' WHERE status = 'done';
ALTER TABLE lab_reservations MODIFY status ENUM('pending','approved','rejected','cancelled','done','completed','no_show') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  posted_by VARCHAR(100) DEFAULT 'Administrator',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  rating INT NOT NULL DEFAULT 5,
  message TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_testimonial_student (student_id),
  INDEX idx_testimonial_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS software_availability (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lab VARCHAR(100) NOT NULL,
  software_name VARCHAR(150) NOT NULL,
  category VARCHAR(100) DEFAULT '',
  version VARCHAR(100) DEFAULT '',
  status ENUM('installed','not installed','maintenance','unavailable') NOT NULL DEFAULT 'installed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_software_lab_name (lab, software_name),
  INDEX idx_software_lab (lab),
  INDEX idx_software_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  INDEX idx_feedback_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS sitin_id INT NULL AFTER id;

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
  INDEX idx_reward_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE reward_point_logs ADD COLUMN IF NOT EXISTS reward_percent INT NOT NULL DEFAULT 0 AFTER student_id;
ALTER TABLE reward_point_logs ADD COLUMN IF NOT EXISTS task_percent INT NOT NULL DEFAULT 0 AFTER reward_percent;
ALTER TABLE reward_point_logs MODIFY points_added DECIMAL(10,2) NOT NULL DEFAULT 0;
ALTER TABLE reward_point_logs MODIFY task_added DECIMAL(10,2) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS reward_redemption_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  points_used DECIMAL(10,2) NOT NULL DEFAULT 0,
  sessions_added INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_redemption_student (student_id),
  INDEX idx_redemption_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS reward_season_settings (
  id INT PRIMARY KEY,
  current_started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  INDEX idx_archive_entries_rank (rank_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  INDEX idx_student_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  INDEX idx_notifications_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TRIGGERS: prevents accepting past pending/approved reservation time
-- and prevents overlapping pending/approved PC reservation schedules.
-- ============================================================

DELIMITER $$

DROP TRIGGER IF EXISTS trg_lab_reservations_bi $$
CREATE TRIGGER trg_lab_reservations_bi
BEFORE INSERT ON lab_reservations
FOR EACH ROW
BEGIN
  IF NEW.reservation_end_time IS NOT NULL AND NEW.reservation_end_time <= NEW.reservation_time THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reservation end time must be later than start time.';
  END IF;

  IF NEW.pc_number IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM lab_computers c
    WHERE c.lab = NEW.lab AND c.pc_number = NEW.pc_number
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Selected PC does not exist in the selected lab.';
  END IF;

  IF NEW.status IN ('pending','approved') AND TIMESTAMP(NEW.reservation_date, NEW.reservation_time) <= NOW() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pending/approved reservations cannot use past date/time.';
  END IF;

  IF NEW.status IN ('pending','approved') AND NEW.pc_number IS NOT NULL AND EXISTS (
    SELECT 1 FROM lab_reservations r
    WHERE r.lab = NEW.lab
      AND r.pc_number = NEW.pc_number
      AND r.reservation_date = NEW.reservation_date
      AND r.status IN ('pending','approved')
      AND COALESCE(r.reservation_end_time, ADDTIME(r.reservation_time, '01:00:00')) > NEW.reservation_time
      AND COALESCE(NEW.reservation_end_time, ADDTIME(NEW.reservation_time, '01:00:00')) > r.reservation_time
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This PC already has a pending/approved reservation for that time.';
  END IF;
END $$

DROP TRIGGER IF EXISTS trg_lab_reservations_bu $$
CREATE TRIGGER trg_lab_reservations_bu
BEFORE UPDATE ON lab_reservations
FOR EACH ROW
BEGIN
  IF NEW.reservation_end_time IS NOT NULL AND NEW.reservation_end_time <= NEW.reservation_time THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reservation end time must be later than start time.';
  END IF;

  IF NEW.pc_number IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM lab_computers c
    WHERE c.lab = NEW.lab AND c.pc_number = NEW.pc_number
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Selected PC does not exist in the selected lab.';
  END IF;

  IF NEW.status IN ('pending','approved') AND TIMESTAMP(NEW.reservation_date, NEW.reservation_time) <= NOW() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pending/approved reservations cannot use past date/time.';
  END IF;

  IF NEW.status IN ('pending','approved') AND NEW.pc_number IS NOT NULL AND EXISTS (
    SELECT 1 FROM lab_reservations r
    WHERE r.id <> NEW.id
      AND r.lab = NEW.lab
      AND r.pc_number = NEW.pc_number
      AND r.reservation_date = NEW.reservation_date
      AND r.status IN ('pending','approved')
      AND COALESCE(r.reservation_end_time, ADDTIME(r.reservation_time, '01:00:00')) > NEW.reservation_time
      AND COALESCE(NEW.reservation_end_time, ADDTIME(NEW.reservation_time, '01:00:00')) > r.reservation_time
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This PC already has a pending/approved reservation for that time.';
  END IF;
END $$

DELIMITER ;

-- ============================================================
-- CLEAN MOCK DATA RESET
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM leaderboard_archive_entries;
DELETE FROM leaderboard_archives;
DELETE FROM session_reset_logs;
DELETE FROM notifications;
DELETE FROM student_notifications;
DELETE FROM reward_redemption_logs;
DELETE FROM reward_point_logs;
DELETE FROM testimonials;
DELETE FROM feedback;
DELETE FROM sitin_records;
DELETE FROM lab_reservations;
DELETE FROM announcements;
DELETE FROM software_availability;
DELETE FROM lab_computers;
DELETE FROM labs;
DELETE FROM students;
DELETE FROM admins;
ALTER TABLE leaderboard_archive_entries AUTO_INCREMENT = 1;
ALTER TABLE leaderboard_archives AUTO_INCREMENT = 1;
ALTER TABLE session_reset_logs AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE student_notifications AUTO_INCREMENT = 1;
ALTER TABLE reward_redemption_logs AUTO_INCREMENT = 1;
ALTER TABLE reward_point_logs AUTO_INCREMENT = 1;
ALTER TABLE testimonials AUTO_INCREMENT = 1;
ALTER TABLE feedback AUTO_INCREMENT = 1;
ALTER TABLE sitin_records AUTO_INCREMENT = 1;
ALTER TABLE lab_reservations AUTO_INCREMENT = 1;
ALTER TABLE announcements AUTO_INCREMENT = 1;
ALTER TABLE software_availability AUTO_INCREMENT = 1;
ALTER TABLE lab_computers AUTO_INCREMENT = 1;
ALTER TABLE students AUTO_INCREMENT = 1;
ALTER TABLE admins AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED BASE DATA
-- ============================================================

INSERT INTO admins (username, password, fullname, email)
VALUES ('admin', '$2y$12$6xQVBNuKrecq0ZJNm9gPBuqfSIsYY7gw6pwoBmuCA3CPqUarRPXei', 'Administrator', 'admin@uc.edu.ph');

INSERT INTO labs (lab, description, status) VALUES
('Lab 524','Programming Laboratory','available'),
('Lab 526','Networking and Programming Laboratory','available'),
('Lab 528','Mobile Development Laboratory','available'),
('Lab 530','Networking Laboratory','available'),
('Lab 542','Multimedia and UI/UX Laboratory','available'),
('Lab 544','Database and Web Development Laboratory','available');

DELIMITER $$
DROP PROCEDURE IF EXISTS seed_lab_pcs $$
CREATE PROCEDURE seed_lab_pcs()
BEGIN
  DECLARE n INT DEFAULT 1;
  SET n = 1;
  WHILE n <= 56 DO
    INSERT IGNORE INTO lab_computers (lab, pc_number, status) VALUES ('Lab 524', n, 'available');
    INSERT IGNORE INTO lab_computers (lab, pc_number, status) VALUES ('Lab 526', n, 'available');
    INSERT IGNORE INTO lab_computers (lab, pc_number, status) VALUES ('Lab 528', n, 'available');
    INSERT IGNORE INTO lab_computers (lab, pc_number, status) VALUES ('Lab 530', n, 'available');
    INSERT IGNORE INTO lab_computers (lab, pc_number, status) VALUES ('Lab 542', n, 'available');
    INSERT IGNORE INTO lab_computers (lab, pc_number, status) VALUES ('Lab 544', n, 'available');
    SET n = n + 1;
  END WHILE;
END $$
DELIMITER ;
CALL seed_lab_pcs();
DROP PROCEDURE IF EXISTS seed_lab_pcs;

UPDATE lab_computers SET status = 'maintenance', notes = 'Under keyboard replacement' WHERE lab = 'Lab 524' AND pc_number = 56;
UPDATE lab_computers SET status = 'maintenance', notes = 'For software update' WHERE lab = 'Lab 542' AND pc_number = 3;
UPDATE lab_computers SET status = 'unavailable', notes = 'Monitor for replacement' WHERE lab = 'Lab 530' AND pc_number = 55;

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
('Lab 544', 'Notepad++', 'Text Editor', 'Latest', 'installed');

INSERT INTO announcements (title, message, posted_by, created_at) VALUES
('Welcome to the UC Sit-in System', 'Students may now reserve laboratory sessions online. Please always check your schedule before proceeding to the lab.', 'Administrator', NOW() - INTERVAL 1 DAY),
('Laboratory Rules Reminder', 'Eating, drinking, and installing unauthorized software inside the laboratories are strictly prohibited.', 'Administrator', NOW() - INTERVAL 2 DAY),
('Reward Points Enabled', 'Students can earn reward points from completed tasks and redeem 10 points for 1 additional sit-in session.', 'Administrator', NOW() - INTERVAL 3 DAY),
('Software Availability Updated', 'Installed applications per laboratory are now viewable from the student dashboard and reservation page.', 'Administrator', NOW() - INTERVAL 4 DAY);

INSERT INTO students
(studentid, firstname, middlename, lastname, course, yearlvl, email, addrs, password, session_credits, reward_points, reward_points_earned, task_completed)
VALUES
('1001','Liam','Santos','Cruz','BSIT','3','liam.santos@uc.edu.ph','Cebu City','$2y$12$IOuVUwE/7YUydhKBW8Zs5.PHf5U1XZ6QuEW5huwRSEv/VsY3fYxLu',30,0,0,0),
('1002','Ava','Reyes','Garcia','BSCS','2','ava.garcia@uc.edu.ph','Mandaue City','$2y$12$Jz.LmwZGySp5ZgQbD0vtn.TR/qCh/kpOC/Sk/mFyFxxW0DhidYAYi',30,0,0,0),
('1003','Noah','Dela Cruz','Villanueva','BSIT','4','noah.villanueva@uc.edu.ph','Lapu-Lapu City','$2y$12$HEufwGeLtk2qSMJgYhoauO3vqCeKaXI4ro/oQYUuYXkXMzS8bfRRe',30,0,0,0),
('1004','Mia','Lopez','Ramos','BSIS','1','mia.ramos@uc.edu.ph','Talisay City','$2y$12$tJKFahjaCtrv9mnz9QSTPOo/.1Vb5hOx2lJJGa.6d6GUv3OZWCIh.',30,0,0,0),
('1005','Ethan','Mendoza','Torres','BSIT','2','ethan.torres@uc.edu.ph','Cebu City','$2y$12$APMYy2AU.BDZNPiSp/jUPenFzGbbbiP2kyUDiNCSYGfE7h.1T3SNi',30,0,0,0),
('1006','Sophia','Flores','Navarro','BSCS','3','sophia.navarro@uc.edu.ph','Consolacion','$2y$12$i30Iesh53gr8s0kfeAewMObKnldajLW9x9p3do/r7Pe4H5ifUBk/W',30,0,0,0),
('1007','Lucas','Bautista','Rivera','BSIT','1','lucas.rivera@uc.edu.ph','Minglanilla','$2y$12$DNfNeoBAwVu0QDKFIhHSAO0d6ecf/t7GM9Y8hz90EaK7Zbfejdczu',30,0,0,0),
('1008','Isabella','Aquino','Morales','BSIS','4','isabella.morales@uc.edu.ph','Cebu City','$2y$12$BN4u0mjXN/jgO3aFO.WjUuutjx7/7xgOUerpN.s9Xli1p0.N/2DMG',30,0,0,0),
('1009','James','Castillo','Perez','BSIT','3','james.perez@uc.edu.ph','Mandaue City','$2y$12$EljR3c.tLhm1CpOm1Zfip..LOuU2elUtud3ogRdzha3b1sE28wINC',30,0,0,0),
('1010','Charlotte','Gonzales','Lim','BSCS','2','charlotte.lim@uc.edu.ph','Lapu-Lapu City','$2y$12$PXkl6nVOq.EvUHrzcUds4OY74gGCCPgjq3Fer1jSDCHmYe5i9OJlq',30,0,0,0),
('1011','Benjamin','Chua','Tan','BSIT','4','benjamin.tan@uc.edu.ph','Cebu City','$2y$12$UJ6UGmCl3izpLqanOKWwPu6dwvozEgMqQPhz0n2B.V.RJgkqtgp7q',30,0,0,0),
('1012','Amelia','Uy','Sy','BSIS','3','amelia.sy@uc.edu.ph','Talisay City','$2y$12$3mqfjyRurOUYGmjHpqTWAOnkdgtiEV/Mgn8y0qX0M2p1HbsjQvkH2',30,0,0,0),
('1013','Mason','Ong','Lee','BSCS','1','mason.lee@uc.edu.ph','Cebu City','$2y$12$8F5mqgknb0uA.rl9a1AJqepubwNAW1jLJqwRZlEfg3qEeAe3Ce4fu',30,0,0,0),
('1014','Harper','Cabrera','Diaz','BSIT','2','harper.diaz@uc.edu.ph','Consolacion','$2y$12$UhCCAbyCaYummqt/6iDtj.HBySInpHDJ8wPT3NQwW2.GqlE7m85KW',30,0,0,0),
('1015','Elijah','Fernandez','Mercado','BSIS','4','elijah.mercado@uc.edu.ph','Mandaue City','$2y$12$WCJwB9W4qmZ6zTANZyjkDezqaQBHG18VU9BGsrWZTMYAjZ2OHH53i',30,0,0,0),
('1016','Evelyn','Pineda','Salazar','BSIT','3','evelyn.salazar@uc.edu.ph','Lapu-Lapu City','$2y$12$OaUVn3zopXmXfErC1GzQ2OpOjFRXLHjBlxijHKJPlW4VHGlPOUa8S',30,0,0,0),
('1017','Daniel','Cortez','Domingo','BSCS','4','daniel.domingo@uc.edu.ph','Cebu City','$2y$12$cdtzEFDnq7AEq/iF48fjXuv1LapSuZXnnoo.OYv02Y1hf9A20CzXy',30,0,0,0),
('1018','Abigail','Padilla','Rosales','BSIT','1','abigail.rosales@uc.edu.ph','Minglanilla','$2y$12$upFVBxc2Uy529ZfKlUjBPu6qs3WwF3MdBzl9G/LI/KtiL0ePTWyGS',30,0,0,0),
('1019','Henry','Marquez','Valdez','BSIS','2','henry.valdez@uc.edu.ph','Cebu City','$2y$12$A/S1FPl3Eb4Ftx3SiskUl.7tK.haLrUwp2roIXVK2n/PNHlxXDKqG',30,0,0,0),
('1020','Emily','Aguilar','Fuentes','BSIT','3','emily.fuentes@uc.edu.ph','Talisay City','$2y$12$Im2ydC1l8bpRzRWQ/Ds4kOI6eLHKOcX5sZpuF4rhWBexm1wlA4A5a',30,0,0,0);

-- ============================================================
-- RESERVATION STATUS GUIDE
-- pending   = waiting for admin approval; must be future only
-- approved  = approved and upcoming; must be future only
-- completed = reservation already used and finished; past only
-- cancelled = reservation cancelled by student/admin
-- rejected  = admin rejected the request
-- no_show   = approved but student did not attend; past only
-- ============================================================

INSERT INTO lab_reservations
(student_id, studentid, fullname, purpose, lab, pc_number, reservation_date, reservation_time, reservation_end_time,
 status, approved_by, approved_at, admin_note, admin_remarks, created_at, cancelled_at, completed_at, rejected_at, no_show_at)
VALUES
( (SELECT id FROM students WHERE studentid='1003' LIMIT 1), '1003', 'Villanueva, Noah Dela Cruz', 'Java OOP Practice', 'Lab 526', 10, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', '15:00:00', 'pending', NULL, NULL, 'Waiting for admin approval.', 'Waiting for admin approval.', NOW() - INTERVAL 2 HOUR, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1004' LIMIT 1), '1004', 'Ramos, Mia Lopez', 'UI/UX Design Activity', 'Lab 542', 25, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', '10:00:00', 'pending', NULL, NULL, 'Waiting for admin approval.', 'Waiting for admin approval.', NOW() - INTERVAL 1 HOUR, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1006' LIMIT 1), '1006', 'Navarro, Sophia Flores', 'Web Development Activity', 'Lab 544', 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '13:00:00', '14:00:00', 'pending', NULL, NULL, 'Waiting for admin approval.', 'Waiting for admin approval.', NOW() - INTERVAL 30 MINUTE, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1007' LIMIT 1), '1007', 'Rivera, Lucas Bautista', 'C Programming Practice', 'Lab 524', 31, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:00:00', '11:00:00', 'pending', NULL, NULL, 'Waiting for admin approval.', 'Waiting for admin approval.', NOW() - INTERVAL 4 HOUR, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1001' LIMIT 1), '1001', 'Cruz, Liam Santos', 'Networking Activity', 'Lab 526', 52, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '09:00:00', 'approved', 1, NOW() - INTERVAL 1 DAY + INTERVAL 1 HOUR, 'Approved. Please arrive on time.', 'Approved. Please arrive on time.', NOW() - INTERVAL 1 DAY, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1002' LIMIT 1), '1002', 'Garcia, Ava Reyes', 'Database Laboratory', 'Lab 530', 40, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', '15:00:00', 'approved', 1, NOW() - INTERVAL 1 DAY + INTERVAL 1 HOUR, 'Approved for laboratory use.', 'Approved for laboratory use.', NOW() - INTERVAL 1 DAY, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1005' LIMIT 1), '1005', 'Torres, Ethan Mendoza', 'Capstone Research', 'Lab 528', 8, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', '12:00:00', 'approved', 1, NOW() - INTERVAL 3 HOUR + INTERVAL 1 HOUR, 'Approved for capstone research.', 'Approved for capstone research.', NOW() - INTERVAL 3 HOUR, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1008' LIMIT 1), '1008', 'Morales, Isabella Aquino', 'Mobile App Development', 'Lab 528', 30, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '15:00:00', '16:00:00', 'approved', 1, NOW() - INTERVAL 6 HOUR + INTERVAL 1 HOUR, 'Approved. Please bring your student ID.', 'Approved. Please bring your student ID.', NOW() - INTERVAL 6 HOUR, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1009' LIMIT 1), '1009', 'Perez, James Castillo', 'Web Development Activity', 'Lab 544', 37, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '09:00:00', '10:30:00', 'approved', 1, NOW() - INTERVAL 8 HOUR + INTERVAL 1 HOUR, 'Approved for web activity.', 'Approved for web activity.', NOW() - INTERVAL 8 HOUR, NULL, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1010' LIMIT 1), '1010', 'Lim, Charlotte Gonzales', 'Java OOP Practice', 'Lab 528', 30, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:00:00', '10:00:00', 'completed', 1, NOW() - INTERVAL 4 DAY + INTERVAL 1 HOUR, 'Reservation completed successfully.', 'Reservation completed successfully.', NOW() - INTERVAL 4 DAY, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY),'10:00:00'), NULL, NULL),
( (SELECT id FROM students WHERE studentid='1011' LIMIT 1), '1011', 'Tan, Benjamin Chua', 'C Programming Practice', 'Lab 524', 23, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '13:00:00', '14:30:00', 'completed', 1, NOW() - INTERVAL 5 DAY + INTERVAL 1 HOUR, 'Reservation completed successfully.', 'Reservation completed successfully.', NOW() - INTERVAL 5 DAY, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'14:30:00'), NULL, NULL),
( (SELECT id FROM students WHERE studentid='1012' LIMIT 1), '1012', 'Sy, Amelia Uy', 'Database Laboratory', 'Lab 530', 33, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00', '10:00:00', 'completed', 1, NOW() - INTERVAL 6 DAY + INTERVAL 1 HOUR, 'Reservation completed successfully.', 'Reservation completed successfully.', NOW() - INTERVAL 6 DAY, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY),'10:00:00'), NULL, NULL),
( (SELECT id FROM students WHERE studentid='1013' LIMIT 1), '1013', 'Lee, Mason Ong', 'Mobile App Development', 'Lab 528', 14, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '14:30:00', '16:00:00', 'completed', 1, NOW() - INTERVAL 7 DAY + INTERVAL 1 HOUR, 'Reservation completed successfully.', 'Reservation completed successfully.', NOW() - INTERVAL 7 DAY, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 4 DAY),'16:00:00'), NULL, NULL),
( (SELECT id FROM students WHERE studentid='1014' LIMIT 1), '1014', 'Diaz, Harper Cabrera', 'UI/UX Design Activity', 'Lab 542', 7, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:15:00', '10:45:00', 'completed', 1, NOW() - INTERVAL 8 DAY + INTERVAL 1 HOUR, 'Reservation completed successfully.', 'Reservation completed successfully.', NOW() - INTERVAL 8 DAY, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY),'10:45:00'), NULL, NULL),
( (SELECT id FROM students WHERE studentid='1015' LIMIT 1), '1015', 'Mercado, Elijah Fernandez', 'Programming Practice', 'Lab 524', 48, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '15:00:00', '16:00:00', 'cancelled', NULL, NULL, 'Cancelled by student before approval.', 'Cancelled by student before approval.', NOW() - INTERVAL 3 DAY, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY),'08:30:00'), NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1016' LIMIT 1), '1016', 'Salazar, Evelyn Pineda', 'System Testing', 'Lab 544', 10, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '11:00:00', '12:00:00', 'cancelled', 1, NOW() - INTERVAL 1 DAY + INTERVAL 1 HOUR, 'Cancelled by admin due to laboratory maintenance.', 'Cancelled by admin due to laboratory maintenance.', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 5 HOUR, NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1017' LIMIT 1), '1017', 'Domingo, Daniel Cortez', 'Networking Activity', 'Lab 530', 21, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '10:00:00', '11:00:00', 'cancelled', NULL, NULL, 'Cancelled by student.', 'Cancelled by student.', NOW() - INTERVAL 4 DAY, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'07:45:00'), NULL, NULL, NULL),
( (SELECT id FROM students WHERE studentid='1018' LIMIT 1), '1018', 'Rosales, Abigail Padilla', 'Online Class', 'Lab 542', 17, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '13:00:00', '14:00:00', 'rejected', 1, NULL, 'Rejected because the requested schedule was not available.', 'Rejected because the requested schedule was not available.', NOW() - INTERVAL 3 DAY, NULL, NULL, NOW() - INTERVAL 2 DAY, NULL),
( (SELECT id FROM students WHERE studentid='1019' LIMIT 1), '1019', 'Valdez, Henry Marquez', 'Research Work', 'Lab 524', 4, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:00:00', '10:00:00', 'rejected', 1, NULL, 'Rejected because the purpose is not allowed for that schedule.', 'Rejected because the purpose is not allowed for that schedule.', NOW() - INTERVAL 4 DAY, NULL, NULL, NOW() - INTERVAL 3 DAY, NULL),
( (SELECT id FROM students WHERE studentid='1020' LIMIT 1), '1020', 'Fuentes, Emily Aguilar', 'Capstone Research', 'Lab 526', 10, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '15:00:00', '16:00:00', 'rejected', 1, NULL, 'Rejected because PC 10 was already reserved.', 'Rejected because PC 10 was already reserved.', NOW() - INTERVAL 2 DAY, NULL, NULL, NOW() - INTERVAL 1 DAY, NULL),
( (SELECT id FROM students WHERE studentid='1002' LIMIT 1), '1002', 'Garcia, Ava Reyes', 'Capstone Research', 'Lab 544', 34, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '09:00:00', 'no_show', 1, NOW() - INTERVAL 3 DAY + INTERVAL 1 HOUR, 'Student did not attend the approved reservation.', 'Student did not attend the approved reservation.', NOW() - INTERVAL 3 DAY, NULL, NULL, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY),'09:15:00')),
( (SELECT id FROM students WHERE studentid='1006' LIMIT 1), '1006', 'Navarro, Sophia Flores', 'C Programming Practice', 'Lab 530', 9, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '13:00:00', '14:00:00', 'no_show', 1, NOW() - INTERVAL 4 DAY + INTERVAL 1 HOUR, 'Student did not attend the approved reservation.', 'Student did not attend the approved reservation.', NOW() - INTERVAL 4 DAY, NULL, NULL, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'14:15:00'));

-- Matching sit-in records for completed reservations.
-- These prove that reservation lab/PC/student details reflect correctly in sit-in history.
INSERT INTO sitin_records
(reservation_id, student_id, studentid, fullname, purpose, lab, pc_number, session_at_sitin, login_time, logout_time, duration_minutes, status, created_at)
SELECT
  r.id, r.student_id, r.studentid, r.fullname, r.purpose, r.lab, r.pc_number,
  30,
  TIMESTAMP(r.reservation_date, r.reservation_time),
  TIMESTAMP(r.reservation_date, r.reservation_end_time),
  TIMESTAMPDIFF(MINUTE, TIMESTAMP(r.reservation_date, r.reservation_time), TIMESTAMP(r.reservation_date, r.reservation_end_time)),
  'done',
  TIMESTAMP(r.reservation_date, r.reservation_time)
FROM lab_reservations r
WHERE r.status = 'completed';

-- Manual sit-in records not from reservation.
INSERT INTO sitin_records
(reservation_id, student_id, studentid, fullname, purpose, lab, pc_number, session_at_sitin, login_time, logout_time, duration_minutes, status, created_at)
VALUES
(NULL, (SELECT id FROM students WHERE studentid='1001' LIMIT 1), '1001', 'Cruz, Liam Santos', 'Networking Activity', 'Lab 526', 46, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 20 DAY),'14:00:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 20 DAY),'16:30:00'), 150, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 20 DAY),'14:00:00')),
(NULL, (SELECT id FROM students WHERE studentid='1001' LIMIT 1), '1001', 'Cruz, Liam Santos', 'Web Development Activity', 'Lab 524', 6, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 15 DAY),'09:00:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 15 DAY),'10:30:00'), 90, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 15 DAY),'09:00:00')),
(NULL, (SELECT id FROM students WHERE studentid='1002' LIMIT 1), '1002', 'Garcia, Ava Reyes', 'Mobile App Development', 'Lab 528', 30, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 18 DAY),'16:00:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 18 DAY),'18:00:00'), 120, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 18 DAY),'16:00:00')),
(NULL, (SELECT id FROM students WHERE studentid='1003' LIMIT 1), '1003', 'Villanueva, Noah Dela Cruz', 'C Programming Practice', 'Lab 524', 15, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 12 DAY),'09:30:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 12 DAY),'11:00:00'), 90, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 12 DAY),'09:30:00')),
(NULL, (SELECT id FROM students WHERE studentid='1004' LIMIT 1), '1004', 'Ramos, Mia Lopez', 'Java OOP Practice', 'Lab 528', 9, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY),'15:15:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY),'16:30:00'), 75, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY),'15:15:00')),
(NULL, (SELECT id FROM students WHERE studentid='1005' LIMIT 1), '1005', 'Torres, Ethan Mendoza', 'Database Laboratory', 'Lab 544', 17, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY),'13:00:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY),'16:00:00'), 180, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY),'13:00:00')),
(NULL, (SELECT id FROM students WHERE studentid='1006' LIMIT 1), '1006', 'Navarro, Sophia Flores', 'UI/UX Design Activity', 'Lab 542', 21, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 8 DAY),'14:30:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 8 DAY),'15:30:00'), 60, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 8 DAY),'14:30:00')),
(NULL, (SELECT id FROM students WHERE studentid='1007' LIMIT 1), '1007', 'Rivera, Lucas Bautista', 'Web Development Activity', 'Lab 524', 16, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY),'16:15:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY),'17:30:00'), 75, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY),'16:15:00')),
(NULL, (SELECT id FROM students WHERE studentid='1008' LIMIT 1), '1008', 'Morales, Isabella Aquino', 'Java OOP Practice', 'Lab 526', 35, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY),'14:30:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY),'16:30:00'), 120, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY),'14:30:00')),
(NULL, (SELECT id FROM students WHERE studentid='1009' LIMIT 1), '1009', 'Perez, James Castillo', 'Capstone Research', 'Lab 542', 16, NULL, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY),'16:15:00'), TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY),'17:15:00'), 60, 'done', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY),'16:15:00')),
(NULL, (SELECT id FROM students WHERE studentid='1018' LIMIT 1), '1018', 'Rosales, Abigail Padilla', 'Capstone Research', 'Lab 530', 15, NULL, NOW() - INTERVAL 1 HOUR, NULL, 0, 'active', NOW() - INTERVAL 1 HOUR),
(NULL, (SELECT id FROM students WHERE studentid='1019' LIMIT 1), '1019', 'Valdez, Henry Marquez', 'Database Laboratory', 'Lab 530', 22, NULL, NOW() - INTERVAL 30 MINUTE, NULL, 0, 'active', NOW() - INTERVAL 30 MINUTE),
(NULL, (SELECT id FROM students WHERE studentid='1020' LIMIT 1), '1020', 'Fuentes, Emily Aguilar', 'C Programming Practice', 'Lab 526', 42, NULL, NOW() - INTERVAL 20 MINUTE, NULL, 0, 'active', NOW() - INTERVAL 20 MINUTE);

-- Mark PCs currently used by active sit-in records.
UPDATE lab_computers c
JOIN sitin_records s ON s.lab = c.lab AND s.pc_number = c.pc_number
SET c.status = 'in_use', c.notes = 'Currently used by active sit-in'
WHERE s.status = 'active';



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

-- Reward ratings.
INSERT INTO reward_point_logs
(student_id, reward_percent, task_percent, points_added, task_added, reason, awarded_by, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1001' LIMIT 1), 50, 50, 5.00, 5.00, 'Completed assigned research task', 'Administrator', NOW() - INTERVAL 20 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1), 50, 25, 5.00, 2.50, 'Completed database activity', 'Administrator', NOW() - INTERVAL 18 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1), 100, 100, 10.00, 10.00, 'Completed programming task', 'Administrator', NOW() - INTERVAL 16 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1), 75, 75, 7.50, 7.50, 'Helped maintain proper lab conduct', 'Administrator', NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1), 25, 50, 2.50, 5.00, 'Submitted database activity', 'Administrator', NOW() - INTERVAL 15 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1), 50, 50, 5.00, 5.00, 'Completed assigned research task', 'Administrator', NOW() - INTERVAL 14 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1), 75, 25, 7.50, 2.50, 'Finished laboratory exercise', 'Administrator', NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1), 75, 100, 7.50, 10.00, 'Completed programming task', 'Administrator', NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1), 25, 50, 2.50, 5.00, 'Finished laboratory exercise', 'Administrator', NOW() - INTERVAL 9 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1), 100, 75, 10.00, 7.50, 'Completed assigned research task', 'Administrator', NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1), 100, 75, 10.00, 7.50, 'Completed reservation activity', 'Administrator', NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1), 25, 50, 2.50, 5.00, 'Completed reservation activity', 'Administrator', NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1), 75, 25, 7.50, 2.50, 'Submitted database activity', 'Administrator', NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1), 100, 25, 10.00, 2.50, 'Completed mobile app activity', 'Administrator', NOW() - INTERVAL 4 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1), 75, 75, 7.50, 7.50, 'Finished UI/UX exercise', 'Administrator', NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1), 25, 50, 2.50, 5.00, 'Completed programming practice', 'Administrator', NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1), 50, 75, 5.00, 7.50, 'Completed assigned research task', 'Administrator', NOW() - INTERVAL 1 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1), 25, 50, 2.50, 5.00, 'Helped maintain proper lab conduct', 'Administrator', NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1), 75, 25, 7.50, 2.50, 'Finished laboratory exercise', 'Administrator', NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1), 50, 100, 5.00, 10.00, 'Submitted database activity', 'Administrator', NOW() - INTERVAL 4 DAY),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1), 75, 100, 7.50, 10.00, 'Helped maintain proper lab conduct', 'Administrator', NOW() - INTERVAL 6 DAY);

-- Reward redemptions: 10 points = 1 extra sit-in session.
INSERT INTO reward_redemption_logs (student_id, points_used, sessions_added, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1003' LIMIT 1), 10.00, 1, NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1), 10.00, 1, NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1), 10.00, 1, NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1), 10.00, 1, NOW() - INTERVAL 1 DAY);

-- Sync student totals from related logs and sit-in records.
UPDATE students s
LEFT JOIN (
  SELECT student_id, COALESCE(SUM(points_added),0) AS reward_total, COALESCE(SUM(task_added),0) AS task_total
  FROM reward_point_logs
  GROUP BY student_id
) r ON r.student_id = s.id
LEFT JOIN (
  SELECT student_id, COALESCE(SUM(points_used),0) AS points_used, COALESCE(SUM(sessions_added),0) AS sessions_added
  FROM reward_redemption_logs
  GROUP BY student_id
) red ON red.student_id = s.id
LEFT JOIN (
  SELECT student_id, COUNT(*) AS used_sessions
  FROM sitin_records
  WHERE status IN ('active','done','completed')
  GROUP BY student_id
) sr ON sr.student_id = s.id
SET
  s.reward_points_earned = COALESCE(r.reward_total, 0),
  s.reward_points = GREATEST(0, COALESCE(r.reward_total, 0) - COALESCE(red.points_used, 0)),
  s.task_completed = COALESCE(r.task_total, 0),
  s.session_credits = GREATEST(0, 30 - COALESCE(sr.used_sessions, 0) + COALESCE(red.sessions_added, 0));

-- Feedback tied to existing sit-in records.
INSERT INTO feedback
(sitin_id, student_id, studentid, student_name, lab, purpose, issue_type, feedback_text, status, created_at)
SELECT id, student_id, studentid, fullname, lab, purpose, 'General', 'Laboratory was clean and organized.', 'reviewed', NOW() - INTERVAL 2 DAY
FROM sitin_records
WHERE status = 'done'
ORDER BY id
LIMIT 6;

-- Testimonials.
INSERT INTO testimonials (student_id, rating, message, status, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1001' LIMIT 1),5,'The sit-in system makes laboratory reservations easier and faster.','approved',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),5,'I like that I can view available software before reserving a lab.','approved',NOW() - INTERVAL 9 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),5,'The reward points feature motivates students to complete tasks.','approved',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),4,'The dashboard is clear and easy to use.','pending',NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),5,'Reservation tracking is useful for students.','approved',NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),4,'The system helps avoid confusion in lab scheduling.','pending',NOW() - INTERVAL 5 DAY);

-- Student notifications connected to reservation/reward status.
INSERT INTO student_notifications (student_id, type, title, message, is_read, created_at)
SELECT student_id, 'reservation', 'Reservation Status Updated', CONCAT('Your reservation for ', lab, ' PC ', pc_number, ' is ', status, '.'), 0, NOW() - INTERVAL 1 DAY
FROM lab_reservations
WHERE status IN ('approved','rejected','cancelled','completed','no_show')
LIMIT 15;

INSERT INTO student_notifications (student_id, type, title, message, is_read, created_at)
SELECT student_id, 'reward', 'Reward Points Added', CONCAT('You received ', points_added, ' reward points from your recent activity.'), 0, created_at
FROM reward_point_logs
WHERE points_added > 0
LIMIT 15;

-- Optional general admin/student notifications.
INSERT INTO notifications (user_type, student_id, title, message, type, is_read, created_at) VALUES
('all', NULL, 'Laboratory Policy Reminder', 'Please follow laboratory rules and reserve only future schedules.', 'announcement', 0, NOW() - INTERVAL 1 DAY),
('admin', NULL, 'Mock Data Loaded', 'Accurate related mock data has been loaded for testing.', 'system', 0, NOW());

INSERT INTO reward_season_settings (id, current_started_at)
VALUES (1, DATE_SUB(NOW(), INTERVAL 60 DAY))
ON DUPLICATE KEY UPDATE current_started_at = VALUES(current_started_at);

INSERT INTO session_reset_logs
(reset_title, total_students, total_credits_before, total_credits_after, reset_by, created_at)
VALUES
('Mock Midterm Session Reset', 20, 420, 600, 'Administrator', NOW() - INTERVAL 45 DAY),
('Mock Final Practice Reset', 20, 390, 600, 'Administrator', NOW() - INTERVAL 15 DAY);

-- Mock leaderboard archive generated from current related data.
INSERT INTO leaderboard_archives
(title, season_started_at, season_ended_at, created_by, created_at)
VALUES
('Mock Leaderboard Archive', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'Administrator', NOW() - INTERVAL 1 DAY);

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
  ORDER BY final_score DESC, reward_points_earned DESC, total_minutes DESC
) ranked;

-- Final accuracy checks you can run after import.
-- 1) Pending/approved must be future only:
-- SELECT * FROM lab_reservations WHERE status IN ('pending','approved') AND TIMESTAMP(reservation_date, reservation_time) <= NOW();
-- 2) Reservation PC must exist in lab_computers:
-- SELECT r.* FROM lab_reservations r LEFT JOIN lab_computers c ON c.lab=r.lab AND c.pc_number=r.pc_number WHERE c.id IS NULL;
-- 3) Completed reservations must have matching sit-in records:
-- SELECT r.* FROM lab_reservations r LEFT JOIN sitin_records s ON s.reservation_id = r.id WHERE r.status='completed' AND s.id IS NULL;

-- ============================================================
-- DONE: sitin database + accurate related mock data loaded.
-- ============================================================
