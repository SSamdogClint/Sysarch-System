USE sitin;

SET @has_pc_number := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sitin_records'
    AND COLUMN_NAME = 'pc_number'
);

SET @add_pc_number_sql := IF(
  @has_pc_number = 0,
  'ALTER TABLE sitin_records ADD COLUMN pc_number INT NULL AFTER lab',
  'SELECT "pc_number already exists" AS message'
);

PREPARE add_pc_number_stmt FROM @add_pc_number_sql;
EXECUTE add_pc_number_stmt;
DEALLOCATE PREPARE add_pc_number_stmt;

CREATE TABLE IF NOT EXISTS software_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lab VARCHAR(50) NOT NULL,
  software_name VARCHAR(150) NOT NULL,
  category VARCHAR(100) DEFAULT NULL,
  version VARCHAR(80) DEFAULT NULL,
  status ENUM('installed','unavailable') NOT NULL DEFAULT 'installed',
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_software_lab (lab),
  INDEX idx_software_name (software_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  message TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_testimonials_student (student_id),
  INDEX idx_testimonials_status (status),
  CONSTRAINT fk_testimonial_student
    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
