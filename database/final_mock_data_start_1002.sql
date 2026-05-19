-- ============================================================
-- MOCK DATA FOR UC SIT-IN SYSTEM
-- STUDENT IDs START FROM 1002
-- Use this because student 1001 is already registered.
--
-- Run after: final_create_tables_sitin.sql
--
-- Login accounts:
--   Admin: admin / admin123
--   Students: 1002 / user1002 up to 1020 / user1020
--
-- NOTE:
-- This file will clean and reinsert mock data only for students 1002-1020.
-- It will NOT insert or update student 1001.
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

-- Clean old mock-related records for student IDs 1002-1020 only.
DELETE FROM notifications
WHERE student_id IN (SELECT id FROM students WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020'));

DELETE FROM feedback
WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020')
   OR student_id IN (SELECT id FROM students WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020'));

DELETE FROM reward_redemption_logs
WHERE student_id IN (SELECT id FROM students WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020'));

DELETE FROM reward_point_logs
WHERE student_id IN (SELECT id FROM students WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020'));

DELETE FROM testimonials
WHERE student_id IN (SELECT id FROM students WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020'));

DELETE FROM lab_reservations
WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020')
   OR student_id IN (SELECT id FROM students WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020'));

DELETE FROM sitin_records
WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020')
   OR student_id IN (SELECT id FROM students WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020'));

-- Announcements
INSERT INTO announcements (title, message, posted_by, created_at) VALUES
('Welcome to the UC Sit-in System','Students may now reserve laboratory sessions online. Please always check your schedule before proceeding to the lab.','Administrator', NOW() - INTERVAL 1 DAY),
('Laboratory Rules Reminder','Eating, drinking, and installing unauthorized software inside the laboratories are strictly prohibited.','Administrator', NOW() - INTERVAL 2 DAY),
('Reward Points Enabled','Students can earn reward points from completed tasks and redeem 10 points for 1 additional sit-in session.','Administrator', NOW() - INTERVAL 3 DAY),
('Software Availability Updated','Installed applications per laboratory are viewable from the student dashboard and reservation page.','Administrator', NOW() - INTERVAL 4 DAY);

-- Students 1002-1020 only
INSERT INTO students
(studentid, firstname, middlename, lastname, course, yearlvl, email, addrs, password, session_credits, reward_points, reward_points_earned, task_completed)
VALUES
('1002','Ava','Reyes','Garcia','BSCS','2','ava.garcia@uc.edu.ph','Mandaue City','$2y$12$APCOaXhPl2p4W7/RHEwcWeuIFMS.2UHwQKtAeal1GTHENcI/AbcL.',30,0,0,0),
('1003','Noah','Dela Cruz','Villanueva','BSIT','4','noah.villanueva@uc.edu.ph','Lapu-Lapu City','$2y$12$FMqGUwMVyCq1ychA7BlRsuwt65y4jwS5HZoz8F67k.bVO8sOjPlNm',30,0,0,0),
('1004','Mia','Lopez','Ramos','BSIS','1','mia.ramos@uc.edu.ph','Talisay City','$2y$12$vEbaxV9lEgkBDxQWIzhp8ebMdIJZZ9uA3ei9xe3lcy.emGg4oVKHG',30,0,0,0),
('1005','Ethan','Mendoza','Torres','BSIT','2','ethan.torres@uc.edu.ph','Cebu City','$2y$12$fqkJ8el.tdLn2TAGQZEYDuP1i/ErNwB.zexcwbTDUaQN4ZFhPm0BS',30,0,0,0),
('1006','Sophia','Flores','Navarro','BSCS','3','sophia.navarro@uc.edu.ph','Consolacion','$2y$12$LTchlZjMcUXtcmUa6UB2SeAlOOZdZjE8OQN7F0OdtncifkWfwXhs6',30,0,0,0),
('1007','Lucas','Bautista','Rivera','BSIT','1','lucas.rivera@uc.edu.ph','Minglanilla','$2y$12$BFtqb9nJXQyC.SAHYuFLI.xF6/wKesssuYCUdMjXnAsh1ICmpJHSe',30,0,0,0),
('1008','Isabella','Aquino','Morales','BSIS','4','isabella.morales@uc.edu.ph','Cebu City','$2y$12$AoHFqK39UkN1T786wTReHOM.r5gd37WYpRnwxO0.O3Jz3jrQR5vee',30,0,0,0),
('1009','James','Castillo','Perez','BSIT','3','james.perez@uc.edu.ph','Mandaue City','$2y$12$wsASLrOqhVVeHFdxrNR6T.CNi0Zx8YgfEM0b.4nXrRtwOfsHJleva',30,0,0,0),
('1010','Charlotte','Gonzales','Lim','BSCS','2','charlotte.lim@uc.edu.ph','Lapu-Lapu City','$2y$12$AEfplz5ZiK/oOMzwWTTue.1juMYJEGLLOkYUctYcBiQrjGeZMhZTC',30,0,0,0),
('1011','Benjamin','Chua','Tan','BSIT','4','benjamin.tan@uc.edu.ph','Cebu City','$2y$12$LA4okydfo9Jeb1frMnExEOVTjQQPWl4lWNa0fiILU/mNjnNpwn6PW',30,0,0,0),
('1012','Amelia','Uy','Sy','BSIS','3','amelia.sy@uc.edu.ph','Talisay City','$2y$12$WQa1fgoZ3Rm.t9vhtEXMg.XsovRr.zmS.e15Bombnbr5TgQCAMQ5q',30,0,0,0),
('1013','Mason','Ong','Lee','BSCS','1','mason.lee@uc.edu.ph','Cebu City','$2y$12$JJ/2vGfsK8ddDWZbE4JHvenpIsjWW3m0D5CfpgqRGQJnaJS7DFlJS',30,0,0,0),
('1014','Harper','Cabrera','Diaz','BSIT','2','harper.diaz@uc.edu.ph','Consolacion','$2y$12$Gr9aUqEXD3FKYNxp1L0DM.3dLd7tKibyQepTwqz4WawdKHleKUkPK',30,0,0,0),
('1015','Elijah','Fernandez','Mercado','BSIS','4','elijah.mercado@uc.edu.ph','Mandaue City','$2y$12$m7To3s6E4jbtJt5g5uKUD.7NXE4eHkU85PKOV.DEaZe9bCIF13uDC',30,0,0,0),
('1016','Evelyn','Pineda','Salazar','BSIT','3','evelyn.salazar@uc.edu.ph','Lapu-Lapu City','$2y$12$H/UvY1IUHNxD8xLjMIf4HeFucKonbi6/YXqqwxurAcHRB8b/XKVAC',30,0,0,0),
('1017','Daniel','Cortez','Domingo','BSCS','4','daniel.domingo@uc.edu.ph','Cebu City','$2y$12$rbvG7HqBjLIBsU2KoDBuOuBYVkwXxfejfv1ew0KMPMuuMKpLrUi4O',30,0,0,0),
('1018','Abigail','Padilla','Rosales','BSIT','1','abigail.rosales@uc.edu.ph','Minglanilla','$2y$12$OQoNxYZttGSLS2SvWbUFKegqD3y/yEz0n4Eg/tKPq4hzyfwXG/wzm',30,0,0,0),
('1019','Henry','Marquez','Valdez','BSIS','2','henry.valdez@uc.edu.ph','Cebu City','$2y$12$SflNM4WlezeDoIXvveJR.uuXn528NpuLaJBxFKQFEX2MDcYbAGLMK',30,0,0,0),
('1020','Emily','Aguilar','Fuentes','BSIT','3','emily.fuentes@uc.edu.ph','Talisay City','$2y$12$IneT6T.WyBDMARo9WKC8meAt3BxN5xOyr7ikHaEm/G9Hmo3AeJkD6',30,0,0,0)
ON DUPLICATE KEY UPDATE
  firstname = VALUES(firstname),
  middlename = VALUES(middlename),
  lastname = VALUES(lastname),
  course = VALUES(course),
  yearlvl = VALUES(yearlvl),
  email = VALUES(email),
  addrs = VALUES(addrs),
  password = VALUES(password),
  session_credits = VALUES(session_credits),
  reward_points = VALUES(reward_points),
  reward_points_earned = VALUES(reward_points_earned),
  task_completed = VALUES(task_completed);

-- Sit-in records
INSERT INTO sitin_records
(student_id, studentid, fullname, purpose, lab, pc_number, session_at_sitin, login_time, logout_time, duration_minutes, status, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','C Programming Practice','Lab 524',1,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Web Development Activity','Lab 526',8,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 90 MINUTE),90,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Database Design','Lab 528',15,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Web Development Activity','Lab 526',4,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Database Design','Lab 528',11,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 120 MINUTE),120,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Capstone Research','Lab 530',18,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Database Design','Lab 528',7,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Capstone Research','Lab 530',14,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 60 MINUTE),60,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Networking Activity','Lab 542',21,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Capstone Research','Lab 530',10,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Networking Activity','Lab 542',17,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 90 MINUTE),90,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Java Programming','Lab 544',24,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','Networking Activity','Lab 542',13,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','Java Programming','Lab 544',20,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 120 MINUTE),120,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','UI/UX Design Activity','Lab 524',27,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','Java Programming','Lab 544',16,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','UI/UX Design Activity','Lab 524',23,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 60 MINUTE),60,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','Mobile App Development','Lab 526',30,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','UI/UX Design Activity','Lab 524',19,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','Mobile App Development','Lab 526',26,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 90 MINUTE),90,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','C Programming Practice','Lab 528',33,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','Mobile App Development','Lab 526',22,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','C Programming Practice','Lab 528',29,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 120 MINUTE),120,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','Web Development Activity','Lab 530',36,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','C Programming Practice','Lab 528',25,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Web Development Activity','Lab 530',32,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 60 MINUTE),60,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Database Design','Lab 542',39,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','Web Development Activity','Lab 530',28,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','Database Design','Lab 542',35,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 90 MINUTE),90,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','Capstone Research','Lab 544',42,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','Database Design','Lab 542',31,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','Capstone Research','Lab 544',38,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 120 MINUTE),120,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','Networking Activity','Lab 524',45,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'1013','Lee, Mason Ong','Capstone Research','Lab 544',34,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'1013','Lee, Mason Ong','Networking Activity','Lab 524',41,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 60 MINUTE),60,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'1013','Lee, Mason Ong','Java Programming','Lab 526',48,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','Networking Activity','Lab 524',37,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','Java Programming','Lab 526',44,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 90 MINUTE),90,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','UI/UX Design Activity','Lab 528',51,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','Java Programming','Lab 526',40,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','UI/UX Design Activity','Lab 528',47,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 120 MINUTE),120,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','Mobile App Development','Lab 530',54,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','UI/UX Design Activity','Lab 528',43,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','Mobile App Development','Lab 530',50,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 60 MINUTE),60,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','C Programming Practice','Lab 542',1,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Mobile App Development','Lab 530',46,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','C Programming Practice','Lab 542',53,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 90 MINUTE),90,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Web Development Activity','Lab 544',4,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','C Programming Practice','Lab 542',49,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Web Development Activity','Lab 544',56,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 120 MINUTE),120,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Database Design','Lab 524',7,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Web Development Activity','Lab 544',52,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Database Design','Lab 524',3,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 60 MINUTE),60,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Capstone Research','Lab 526',10,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 90 MINUTE),90,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','Database Design','Lab 524',55,30,'2026-04-08 08:00:00',DATE_ADD('2026-04-08 08:00:00', INTERVAL 60 MINUTE),60,'done','2026-04-08 08:00:00'),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','Capstone Research','Lab 526',6,29,'2026-04-15 09:30:00',DATE_ADD('2026-04-15 09:30:00', INTERVAL 90 MINUTE),90,'done','2026-04-15 09:30:00'),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','Networking Activity','Lab 528',13,28,'2026-04-22 13:00:00',DATE_ADD('2026-04-22 13:00:00', INTERVAL 120 MINUTE),120,'done','2026-04-22 13:00:00'),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Capstone Research','Lab 528',5,27,NOW() - INTERVAL 1 HOUR,NULL,0,'active',NOW() - INTERVAL 1 HOUR),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Networking Activity','Lab 530',16,27,NOW() - INTERVAL 2 HOUR,NULL,0,'active',NOW() - INTERVAL 2 HOUR),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','Java Programming','Lab 542',27,27,NOW() - INTERVAL 3 HOUR,NULL,0,'active',NOW() - INTERVAL 3 HOUR),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','UI/UX Design Activity','Lab 544',38,27,NOW() - INTERVAL 4 HOUR,NULL,0,'active',NOW() - INTERVAL 4 HOUR),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Mobile App Development','Lab 524',49,27,NOW() - INTERVAL 5 HOUR,NULL,0,'active',NOW() - INTERVAL 5 HOUR);

-- Lab reservations
INSERT INTO lab_reservations
(student_id, studentid, fullname, purpose, lab, pc_number, reservation_date, reservation_time, reservation_end_time, status, admin_note, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Database Design','Lab 526',9,'2026-06-01','08:00:00','09:00:00','pending','Waiting for admin approval',NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),'1003','Villanueva, Noah Dela Cruz','Capstone Research','Lab 528',14,'2026-06-02','09:00:00','10:00:00','approved','Approved reservation',NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Networking Activity','Lab 530',19,'2026-06-03','10:00:00','11:00:00','completed','Completed scheduled reservation',NOW() - INTERVAL 4 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),'1005','Torres, Ethan Mendoza','Java Programming','Lab 542',24,'2026-06-04','11:00:00','12:00:00','rejected','Rejected due to schedule conflict',NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','UI/UX Design Activity','Lab 544',29,'2026-06-05','12:00:00','13:00:00','cancelled','Cancelled by student request',NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),'1007','Rivera, Lucas Bautista','Mobile App Development','Lab 524',34,'2026-06-06','13:00:00','14:00:00','pending','Waiting for admin approval',NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','C Programming Practice','Lab 526',39,'2026-06-07','14:00:00','15:00:00','approved','Approved reservation',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),'1009','Perez, James Castillo','Web Development Activity','Lab 528',44,'2026-06-08','15:00:00','16:00:00','completed','Completed scheduled reservation',NOW() - INTERVAL 9 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Database Design','Lab 530',49,'2026-06-09','08:00:00','09:00:00','rejected','Rejected due to schedule conflict',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),'1011','Tan, Benjamin Chua','Capstone Research','Lab 542',54,'2026-06-10','09:00:00','10:00:00','cancelled','Cancelled by student request',NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','Networking Activity','Lab 544',3,'2026-06-11','10:00:00','11:00:00','pending','Waiting for admin approval',NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),'1013','Lee, Mason Ong','Java Programming','Lab 524',8,'2026-06-12','11:00:00','12:00:00','approved','Approved reservation',NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),'1014','Diaz, Harper Cabrera','UI/UX Design Activity','Lab 526',13,'2026-06-13','12:00:00','13:00:00','completed','Completed scheduled reservation',NOW() - INTERVAL 14 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','Mobile App Development','Lab 528',18,'2026-06-14','13:00:00','14:00:00','rejected','Rejected due to schedule conflict',NOW() - INTERVAL 15 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),'1016','Salazar, Evelyn Pineda','C Programming Practice','Lab 530',23,'2026-06-15','14:00:00','15:00:00','cancelled','Cancelled by student request',NOW() - INTERVAL 16 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),'1017','Domingo, Daniel Cortez','Web Development Activity','Lab 542',28,'2026-06-16','15:00:00','16:00:00','pending','Waiting for admin approval',NOW() - INTERVAL 17 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Database Design','Lab 544',33,'2026-06-17','08:00:00','09:00:00','approved','Approved reservation',NOW() - INTERVAL 18 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),'1019','Valdez, Henry Marquez','Capstone Research','Lab 524',38,'2026-06-18','09:00:00','10:00:00','completed','Completed scheduled reservation',NOW() - INTERVAL 19 DAY),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),'1020','Fuentes, Emily Aguilar','Networking Activity','Lab 526',43,'2026-06-19','10:00:00','11:00:00','rejected','Rejected due to schedule conflict',NOW() - INTERVAL 20 DAY);

-- Reward logs
INSERT INTO reward_point_logs
(student_id, reward_percent, task_percent, points_added, task_added, reason, awarded_by, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),100,100,10.00,10.00,'Completed assigned programming task','Administrator',NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),75,100,7.50,10.00,'Finished laboratory activity on time','Administrator',NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),75,100,7.50,10.00,'Finished laboratory activity on time','Administrator',NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),50,75,5.00,7.50,'Submitted database exercise','Administrator',NOW() - INTERVAL 4 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),50,75,5.00,7.50,'Submitted database exercise','Administrator',NOW() - INTERVAL 4 DAY),
((SELECT id FROM students WHERE studentid='1004' LIMIT 1),75,50,7.50,5.00,'Participated in capstone research work','Administrator',NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),75,50,7.50,5.00,'Participated in capstone research work','Administrator',NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),100,75,10.00,7.50,'Completed networking simulation','Administrator',NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),100,75,10.00,7.50,'Completed networking simulation','Administrator',NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1006' LIMIT 1),25,50,2.50,5.00,'Assisted in laboratory activity','Administrator',NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),25,50,2.50,5.00,'Assisted in laboratory activity','Administrator',NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),100,100,10.00,10.00,'Completed assigned programming task','Administrator',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),100,100,10.00,10.00,'Completed assigned programming task','Administrator',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),75,100,7.50,10.00,'Finished laboratory activity on time','Administrator',NOW() - INTERVAL 9 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),75,100,7.50,10.00,'Finished laboratory activity on time','Administrator',NOW() - INTERVAL 9 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),50,75,5.00,7.50,'Submitted database exercise','Administrator',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),50,75,5.00,7.50,'Submitted database exercise','Administrator',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1010' LIMIT 1),75,50,7.50,5.00,'Participated in capstone research work','Administrator',NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),75,50,7.50,5.00,'Participated in capstone research work','Administrator',NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),100,75,10.00,7.50,'Completed networking simulation','Administrator',NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),100,75,10.00,7.50,'Completed networking simulation','Administrator',NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1012' LIMIT 1),25,50,2.50,5.00,'Assisted in laboratory activity','Administrator',NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),25,50,2.50,5.00,'Assisted in laboratory activity','Administrator',NOW() - INTERVAL 13 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),100,100,10.00,10.00,'Completed assigned programming task','Administrator',NOW() - INTERVAL 14 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),100,100,10.00,10.00,'Completed assigned programming task','Administrator',NOW() - INTERVAL 14 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),75,100,7.50,10.00,'Finished laboratory activity on time','Administrator',NOW() - INTERVAL 15 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),75,100,7.50,10.00,'Finished laboratory activity on time','Administrator',NOW() - INTERVAL 15 DAY),
((SELECT id FROM students WHERE studentid='1015' LIMIT 1),50,75,5.00,7.50,'Submitted database exercise','Administrator',NOW() - INTERVAL 16 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),50,75,5.00,7.50,'Submitted database exercise','Administrator',NOW() - INTERVAL 16 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),75,50,7.50,5.00,'Participated in capstone research work','Administrator',NOW() - INTERVAL 17 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),75,50,7.50,5.00,'Participated in capstone research work','Administrator',NOW() - INTERVAL 17 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),100,75,10.00,7.50,'Completed networking simulation','Administrator',NOW() - INTERVAL 18 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),100,75,10.00,7.50,'Completed networking simulation','Administrator',NOW() - INTERVAL 18 DAY),
((SELECT id FROM students WHERE studentid='1018' LIMIT 1),25,50,2.50,5.00,'Assisted in laboratory activity','Administrator',NOW() - INTERVAL 19 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),25,50,2.50,5.00,'Assisted in laboratory activity','Administrator',NOW() - INTERVAL 19 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),100,100,10.00,10.00,'Completed assigned programming task','Administrator',NOW() - INTERVAL 20 DAY),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),100,100,10.00,10.00,'Completed assigned programming task','Administrator',NOW() - INTERVAL 20 DAY),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),75,100,7.50,10.00,'Finished laboratory activity on time','Administrator',NOW() - INTERVAL 21 DAY);

-- Update student earned points, spendable balance, and task points based on reward logs.
UPDATE students s
LEFT JOIN (
    SELECT
        student_id,
        COALESCE(SUM(points_added), 0) AS earned_points,
        COALESCE(SUM(task_added), 0) AS task_points
    FROM reward_point_logs
    GROUP BY student_id
) r ON r.student_id = s.id
SET
    s.reward_points = COALESCE(r.earned_points, 0),
    s.reward_points_earned = COALESCE(r.earned_points, 0),
    s.task_completed = COALESCE(r.task_points, 0)
WHERE s.studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020');

-- Reward redemptions
INSERT INTO reward_redemption_logs
(student_id, points_used, sessions_added, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),10.00,1,NOW() - INTERVAL 1 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),10.00,1,NOW() - INTERVAL 2 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),10.00,1,NOW() - INTERVAL 3 DAY),
((SELECT id FROM students WHERE studentid='1008' LIMIT 1),10.00,1,NOW() - INTERVAL 4 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),10.00,1,NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1014' LIMIT 1),10.00,1,NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1017' LIMIT 1),10.00,1,NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1020' LIMIT 1),10.00,1,NOW() - INTERVAL 8 DAY);

-- Apply redemption effect:
-- spendable reward_points decreases, session_credits increases.
UPDATE students s
LEFT JOIN (
    SELECT
        student_id,
        COALESCE(SUM(points_used), 0) AS used_points,
        COALESCE(SUM(sessions_added), 0) AS added_sessions
    FROM reward_redemption_logs
    GROUP BY student_id
) rr ON rr.student_id = s.id
SET
    s.reward_points = GREATEST(0, s.reward_points - COALESCE(rr.used_points, 0)),
    s.session_credits = 30
WHERE s.studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020');

-- Recalculate session credits based on sessions used and redemptions.
UPDATE students s
LEFT JOIN (
    SELECT student_id, COUNT(*) AS used_sessions
    FROM sitin_records
    GROUP BY student_id
) sr ON sr.student_id = s.id
LEFT JOIN (
    SELECT student_id, COALESCE(SUM(sessions_added), 0) AS added_sessions
    FROM reward_redemption_logs
    GROUP BY student_id
) rr ON rr.student_id = s.id
SET
    s.session_credits = GREATEST(0, 30 - COALESCE(sr.used_sessions, 0) + COALESCE(rr.added_sessions, 0))
WHERE s.studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020');

-- Feedback
INSERT INTO feedback
(sitin_id, student_id, studentid, student_name, lab, purpose, issue_type, feedback_text, status, created_at)
VALUES
((SELECT id FROM sitin_records WHERE studentid='1002' AND status='done' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1002' LIMIT 1),'1002','Garcia, Ava Reyes','Lab 524','Web Development Activity','General','Laboratory was clean and organized.','new',NOW() - INTERVAL 3 DAY),
((SELECT id FROM sitin_records WHERE studentid='1004' AND status='done' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1004' LIMIT 1),'1004','Ramos, Mia Lopez','Lab 526','Database Design','Computer Issue','PC was working but internet speed was slow.','reviewed',NOW() - INTERVAL 4 DAY),
((SELECT id FROM sitin_records WHERE studentid='1006' AND status='done' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1006' LIMIT 1),'1006','Navarro, Sophia Flores','Lab 528','Capstone Research','Software Request','Requested additional software for programming practice.','resolved',NOW() - INTERVAL 5 DAY),
((SELECT id FROM sitin_records WHERE studentid='1008' AND status='done' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1008' LIMIT 1),'1008','Morales, Isabella Aquino','Lab 530','Networking Activity','Internet Connection','The session was helpful for finishing school activity.','new',NOW() - INTERVAL 6 DAY),
((SELECT id FROM sitin_records WHERE studentid='1010' AND status='done' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1010' LIMIT 1),'1010','Lim, Charlotte Gonzales','Lab 542','Java Programming','Laboratory Concern','Mouse and keyboard were working properly.','reviewed',NOW() - INTERVAL 7 DAY),
((SELECT id FROM sitin_records WHERE studentid='1012' AND status='done' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1012' LIMIT 1),'1012','Sy, Amelia Uy','Lab 544','UI/UX Design Activity','General','The lab assistant was responsive.','resolved',NOW() - INTERVAL 8 DAY),
((SELECT id FROM sitin_records WHERE studentid='1015' AND status='done' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1015' LIMIT 1),'1015','Mercado, Elijah Fernandez','Lab 524','Mobile App Development','Computer Issue','Software availability helped me choose the correct laboratory.','new',NOW() - INTERVAL 9 DAY),
((SELECT id FROM sitin_records WHERE studentid='1018' AND status='done' ORDER BY id DESC LIMIT 1),(SELECT id FROM students WHERE studentid='1018' LIMIT 1),'1018','Rosales, Abigail Padilla','Lab 526','C Programming Practice','Software Request','Reservation process was easy to follow.','reviewed',NOW() - INTERVAL 10 DAY);

-- Testimonials
INSERT INTO testimonials
(student_id, rating, message, status, created_at)
VALUES
((SELECT id FROM students WHERE studentid='1002' LIMIT 1),5,'The system makes sit-in monitoring easier and faster.','approved',NOW() - INTERVAL 5 DAY),
((SELECT id FROM students WHERE studentid='1003' LIMIT 1),5,'Reservation and software availability are helpful for students.','approved',NOW() - INTERVAL 6 DAY),
((SELECT id FROM students WHERE studentid='1005' LIMIT 1),4,'The reward points feature motivates students to complete tasks.','approved',NOW() - INTERVAL 7 DAY),
((SELECT id FROM students WHERE studentid='1007' LIMIT 1),5,'The dashboard is easy to understand and use.','approved',NOW() - INTERVAL 8 DAY),
((SELECT id FROM students WHERE studentid='1009' LIMIT 1),4,'I like that I can view my sit-in history anytime.','approved',NOW() - INTERVAL 9 DAY),
((SELECT id FROM students WHERE studentid='1011' LIMIT 1),5,'The leaderboard makes the system more engaging.','approved',NOW() - INTERVAL 10 DAY),
((SELECT id FROM students WHERE studentid='1013' LIMIT 1),5,'The reservation process is clear and convenient.','approved',NOW() - INTERVAL 11 DAY),
((SELECT id FROM students WHERE studentid='1016' LIMIT 1),4,'I can easily check available software before booking.','pending',NOW() - INTERVAL 12 DAY),
((SELECT id FROM students WHERE studentid='1019' LIMIT 1),5,'The system helps organize laboratory usage better.','pending',NOW() - INTERVAL 13 DAY);

-- Software availability sample data
INSERT INTO software_availability (lab, software_name, category, version, status) VALUES
('Lab 524','Visual Studio Code','Programming','1.90','installed'),
('Lab 524','XAMPP','Web Development','8.2','installed'),
('Lab 524','Google Chrome','Browser','Latest','installed'),
('Lab 526','Python','Programming','3.12','installed'),
('Lab 526','Node.js','Web Development','20.x','installed'),
('Lab 528','Android Studio','Mobile Development','Koala','installed'),
('Lab 528','Flutter SDK','Mobile Development','Stable','installed'),
('Lab 530','Cisco Packet Tracer','Networking','8.2','installed'),
('Lab 530','Wireshark','Networking','Latest','installed'),
('Lab 542','Figma','UI/UX Design','Web','installed'),
('Lab 542','Adobe Photoshop','Multimedia','2024','installed'),
('Lab 544','Visual Studio','Programming','.NET 2022','installed'),
('Lab 544','Postman','API Testing','Latest','installed')
ON DUPLICATE KEY UPDATE software_name = software_name;

-- Make sure PC 1 to 56 exists for each lab.
INSERT IGNORE INTO lab_computers (lab, pc_number, status)
SELECT labs.lab, nums.n, 'available'
FROM (
    SELECT 'Lab 524' AS lab UNION ALL
    SELECT 'Lab 526' UNION ALL
    SELECT 'Lab 528' UNION ALL
    SELECT 'Lab 530' UNION ALL
    SELECT 'Lab 542' UNION ALL
    SELECT 'Lab 544'
) labs
CROSS JOIN (
    SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL
    SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL
    SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL
    SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20 UNION ALL
    SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL
    SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29 UNION ALL SELECT 30 UNION ALL
    SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34 UNION ALL SELECT 35 UNION ALL
    SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39 UNION ALL SELECT 40 UNION ALL
    SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44 UNION ALL SELECT 45 UNION ALL
    SELECT 46 UNION ALL SELECT 47 UNION ALL SELECT 48 UNION ALL SELECT 49 UNION ALL SELECT 50 UNION ALL
    SELECT 51 UNION ALL SELECT 52 UNION ALL SELECT 53 UNION ALL SELECT 54 UNION ALL SELECT 55 UNION ALL
    SELECT 56
) nums;

-- Mark PCs currently in active sit-in as in_use.
UPDATE lab_computers lc
INNER JOIN sitin_records sr
    ON sr.lab = lc.lab
   AND sr.pc_number = lc.pc_number
   AND sr.status = 'active'
SET lc.status = 'in_use';

-- Notifications
INSERT INTO notifications (user_type, student_id, title, message, type, is_read, created_at)
SELECT 'student', id, 'Mock Data Loaded', 'Sample records were added to your account for system demonstration.', 'general', 0, NOW()
FROM students
WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020');

SET FOREIGN_KEY_CHECKS = 1;

-- Quick check
SELECT studentid, firstname, lastname, session_credits, reward_points, reward_points_earned, task_completed
FROM students
WHERE studentid IN ('1002','1003','1004','1005','1006','1007','1008','1009','1010','1011','1012','1013','1014','1015','1016','1017','1018','1019','1020')
ORDER BY studentid;

-- Done.
