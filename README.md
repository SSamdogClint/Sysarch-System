# UC Sit-in System

**University of Cebu — College of Computer Studies**

A web-based sit-in monitoring system for CCS students and administrators.

---

## Fixed Notes

This copy includes fixes for:

- Broken `home.php` links for logout and dashboard.
- Database name mismatch: the project now consistently uses `sitin`.
- Duplicate `reservation_end_time` SQL issue removed.
- `logout_time` is now included directly in the `sitin_records` table.
- Reservation auto-cancel grace period fixed from 1 minute to 15 minutes.
- Student profile update is safer: students can only update their own account.
- Feedback submission is safer: students can only submit feedback for their own sit-in record.
- Login session now includes `middlename`.
- Admin sit-in registration no longer silently closes active sessions.

---

## Installation Guide

### Step 1: Install XAMPP

1. Install XAMPP.
2. Open XAMPP Control Panel.
3. Start **Apache** and **MySQL**.

---

### Step 2: Copy Project Folder

Copy the `Sysarch-System` folder to:

```text
C:\xampp\htdocs\
```

Your path should look like this:

```text
C:\xampp\htdocs\Sysarch-System\
```

---

### Step 3: Import / Update Database

1. Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

2. Click your `sitin` database, then open the **SQL** tab.
3. Open this file from the project folder:

```text
database/sitin_all_in_one.sql
```

4. Copy all SQL contents, paste them in phpMyAdmin, then click **Go**.

This one combined SQL file creates missing tables and adds missing columns without deleting your existing records.

For a completely fresh database only, you may also import:

```text
database/sitin.sql
```

Reminder: `database/sitin.sql` drops and recreates the tables, so use `sitin_all_in_one.sql` if you want to keep your current data.

---

## Manual Database SQL

If you want to paste the SQL manually, use this combined SQL file:

```text
database/sitin_all_in_one.sql
```

---

## Database Connection

Open:

```text
config/db_config.php
```

Make sure it matches your XAMPP setup:

```php
<?php
// config/db_config.php

define('ROOT_PATH', dirname(__DIR__, 1));

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sitin');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
```

---

## Access the System

Home page:

```text
http://localhost/Sysarch-System/home.php
```

Login page:

```text
http://localhost/Sysarch-System/login_page.php
```

Register page:

```text
http://localhost/Sysarch-System/register_page.php
```

Student dashboard:

```text
http://localhost/Sysarch-System/student_module/student_dashboard.php
```

Admin dashboard:

```text
http://localhost/Sysarch-System/admin_module/admin_dashboard.php
```

---

## Default Admin Login

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `admin123` |

For real deployment, move admin accounts to a database table and use hashed passwords.

---

## Main Folder Structure

```text
Sysarch-System/
├── admin_module/
├── assets/
│   ├── css/
│   └── images/
├── config/
│   └── db_config.php
├── controllers/
│   ├── announcements/
│   ├── auth/
│   ├── dashboard/
│   ├── reservation/
│   ├── sitin/
│   └── student/
├── database/
│   ├── sitin.sql
│   ├── sitin_all_in_one.sql
│   └── combined_database_update.sql
├── student_module/
├── home.php
├── login_page.php
├── register_page.php
└── README.md
```

---

## Tables Created

- `students`
- `announcements`
- `sitin_records`
- `feedback`
- `lab_reservations`
- `lab_pc_status`
- `student_notifications`
- `software_applications`
- `testimonials`

---

## Important Reminder

Use only one database setup file at a time. Recommended file for your current project is:

```text
database/sitin_all_in_one.sql
```

This is the combined SQL file for notifications, reports, software availability, testimonials, and PC number support.

## Whiteboard Features Added

The following files were added for the remaining whiteboard items:

- `admin_module/Admin_Reports.php` - admin report page for sit-in and feedback reports.
- `controllers/reports/export_report.php` - exports PDF and CSV reports.
- `admin_module/Admin_Software.php` - software application import/upload module.
- `student_module/testimonials.php` - student testimonial submission page.
- `admin_module/Admin_Testimonials.php` - admin testimonial approval/management page.
- `database/add_whiteboard_features.sql` - migration script for existing databases.
- `database/software_import_sample.csv` - sample CSV format for software import.

### Important Database Update

Run this one combined file in phpMyAdmin SQL tab:

```text
database/sitin_all_in_one.sql
```

It includes the database updates for:

- `student_notifications` table
- `pc_number` column in `sitin_records`
- `software_applications` table
- `testimonials` table
- `reservation_end_time` safety update
