USE sitin;

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

  CONSTRAINT fk_notification_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
