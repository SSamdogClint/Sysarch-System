# UC Sit-in System
**University of Cebu — College of Computer Studies**
> A web-based sit-in monitoring system for CCS students and administrators.

---

## Installation Guide

### Step 1: Install XAMPP

1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to the default location
3. Open XAMPP Control Panel
4. Click **Start** on both **Apache** and **MySQL**

---

### Step 2: Setup Project Files

1. Download or clone this repository
2. Copy the `Sysarch-System` folder to XAMPP's htdocs directory:

| OS | Path |
|---|---|
| Windows | `C:\xampp\htdocs\` |
| macOS | `/Applications/XAMPP/htdocs/` |
| Linux | `/opt/lampp/htdocs/` |

3. Make sure the folder structure looks like this:
   (Note: This is not yet the folder structure)

```
Sysarch-System/
├── config/
│   └── db_config.php
├── css/
│   └── style.css
├── images/
├── admin_module/
│   └── admin_dashboard.php
├── student_module/
│   └── student_dashboard.php
├── home.php
├── login_page.php
├── login_handler.php
├── register_page.php
├── register_handler.php
├── logout.php
├── check_session.php
└── update_profile.php
```

---

### Step 3: Create the Database

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click **New** in the left sidebar
3. Set the database name to `sitin`
4. Set collation to `utf8mb4_general_ci`
5. Click **Create**

---

### Step 4: Create the Database Table

1. Select the `sitin` database from the left sidebar
2. Click the **SQL** tab
3. Paste the following query and click **Go**:

```sql
USE sitin;

CREATE TABLE IF NOT EXISTS students (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  studentid  VARCHAR(20)  NOT NULL UNIQUE,
  lastname   VARCHAR(50)  NOT NULL,
  firstname  VARCHAR(50)  NOT NULL,
  middlename VARCHAR(50)  DEFAULT '',
  course     VARCHAR(30)  NOT NULL,
  yearlvl    TINYINT      NOT NULL DEFAULT 1,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  addrs      VARCHAR(150) DEFAULT '',
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
```

---

### Step 5: Configure Database Connection

Open `config/db_config.php` and make sure it matches your XAMPP setup:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Leave empty if no password set
define('DB_NAME', 'sitin');   // Must match the database you created

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
```

---

### Step 6: Access the System

Open your browser and go to:

```
http://localhost/Sysarch-System/home.php
```

---

## 🔐 Default Login Credentials

### Administrator *(hardcoded for now)*

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `admin123` |

### Student

> Register a new student account at:
> `http://localhost/Sysarch-System/register_page.php`

---

## 📁 Pages

| Page | URL |
|---|---|
| Home | `http://localhost/Sysarch-System/home.php` |
| Login | `http://localhost/Sysarch-System/login_page.php` |
| Register | `http://localhost/Sysarch-System/register_page.php` |
| Student Dashboard | `http://localhost/Sysarch-System/student_module/student_dashboard.php` |
| Admin Dashboard | `http://localhost/Sysarch-System/admin_module/admin_dashboard.php` |

---

## ✅ Features

- Student registration and login by Student ID
- Student dashboard with session credits, announcements, and lab rules
- Edit profile via modal
- Admin dashboard with sit-in stats and announcements
- Logout with back-button protection (session-based)
- Shared session checker for both student and admin

---

## 🛠️ Tech Stack

| Technology | Usage |
|---|---|
| PHP | Backend logic |
| MySQL | Database |
| Bootstrap 5 | UI framework |
| JavaScript | Frontend interactivity |
| XAMPP | Local development server |
