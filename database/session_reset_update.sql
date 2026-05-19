-- database/session_reset_update.sql
-- Run this once in phpMyAdmin if session_reset_logs is missing.
-- This supports the reusable Reset Sessions button.

USE sitin;

ALTER TABLE students
ADD COLUMN IF NOT EXISTS session_credits INT NOT NULL DEFAULT 30;

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
