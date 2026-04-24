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
(note: in my case this not the final folder structure yet)

```
Sysarch-System/
├── admin_module/
│   ├── admin_dashboard.php
│   └── Admin_StudentList.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── images/
│       ├── ccsmainlog_nobg.png
│       └── uclogo_nobg.png
├── config/
│   └── db_config.php
├── includes/
│   ├── check_session.php
│   ├── delete_student.php
│   ├── register_sitin.php
│   ├── reset_sessions.php
│   └── search_student.php
├── student_module/
│   └── student_dashboard.php
├── home.php
├── login_page.php
├── login_handler.php
├── register_page.php
├── register_handler.php
├── logout.php
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

### Step 4: Create the Database Tables

1. Select the `sitin` database from the left sidebar
2. Click the **SQL** tab
3. Paste the following query and click **Go**:

```sql
USE sitin;

-- Students table
CREATE TABLE IF NOT EXISTS students (
  id              INT          AUTO_INCREMENT PRIMARY KEY,
  studentid       VARCHAR(20)  NOT NULL UNIQUE,
  lastname        VARCHAR(50)  NOT NULL,
  firstname       VARCHAR(50)  NOT NULL,
  middlename      VARCHAR(50)  DEFAULT '',
  course          VARCHAR(30)  NOT NULL,
  yearlvl         TINYINT      NOT NULL DEFAULT 1,
  email           VARCHAR(100) NOT NULL UNIQUE,
  password        VARCHAR(255) NOT NULL,
  addrs           VARCHAR(150) DEFAULT '',
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  session_credits INT          NOT NULL DEFAULT 30
);

-- Sit-in records table
CREATE TABLE IF NOT EXISTS sitin_records (
  id               INT          AUTO_INCREMENT PRIMARY KEY,
  student_id       INT          NOT NULL,
  studentid        VARCHAR(20)  NOT NULL,
  fullname         VARCHAR(150) NOT NULL,
  purpose          VARCHAR(100) NOT NULL,
  lab              VARCHAR(50)  NOT NULL,
  session_at_sitin INT          NOT NULL DEFAULT 0,
  login_time       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  status           VARCHAR(20)  DEFAULT 'active',
  FOREIGN KEY (student_id) REFERENCES students(id)
);

-- incase you created the table before i have changes in sitin records table
ALTER TABLE sitin_records ADD COLUMN session_at_sitin INT NOT NULL DEFAULT 0;

-- create feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sitin_id INT NOT NULL,
    student_id INT NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    feedback_text TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sitin_id) REFERENCES sitin_records(id) ON DELETE CASCADE
);
```

---

### Step 5: Configure Database Connection

Open `config/db_config.php` and make sure it matches your XAMPP setup:

```php
<?php
define('ROOT_PATH', dirname(__DIR__, 1));

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
| Admin Student List | `http://localhost/Sysarch-System/admin_module/Admin_StudentList.php` |

---

## 🗄️ Database Schema

### Table: `students`

| Column | Type | Description |
|---|---|---|
| `id` | INT | Auto-increment primary key |
| `studentid` | VARCHAR(20) | Unique student ID number |
| `lastname` | VARCHAR(50) | Last name |
| `firstname` | VARCHAR(50) | First name |
| `middlename` | VARCHAR(50) | Middle name (optional) |
| `course` | VARCHAR(30) | Course (e.g. BSIT, BSCS) |
| `yearlvl` | TINYINT | Year level (1–4) |
| `email` | VARCHAR(100) | Unique email address |
| `password` | VARCHAR(255) | Hashed password (bcrypt) |
| `addrs` | VARCHAR(150) | Home address |
| `created_at` | TIMESTAMP | Date registered |
| `session_credits` | INT | Remaining sit-in credits (default: 30) |

### Table: `sitin_records`

| Column | Type | Description |
|---|---|---|
| `id` | INT | Auto-increment primary key |
| `student_id` | INT | Foreign key → `students.id` |
| `studentid` | VARCHAR(20) | Student ID (for easy display) |
| `fullname` | VARCHAR(150) | Full name (for easy display) |
| `purpose` | VARCHAR(100) | Purpose of sit-in session |
| `lab` | VARCHAR(50) | Lab number used |
| `login_time` | TIMESTAMP | Date and time of sit-in |
| `status` | VARCHAR(20) | `active` or `done` |

### Relationship

```
students                   sitin_records
────────────────           ─────────────────────
id  ◄──────────────────── student_id (FK)
studentid                  studentid
session_credits            purpose
...                        lab
                           login_time
                           status
```

> One student can have **many** sit-in records (one-to-many relationship).

---

## ✅ Features

- Student registration and login by Student ID
- Student dashboard with session credits, announcements, and lab rules
- Edit profile via modal
- Admin dashboard with sit-in stats and announcements
- Admin can search students by ID
- Admin can register sit-in sessions (deducts 1 session credit per sit-in)
- Admin student list with edit and delete actions
- Reset all session credits to 30 (new semester)
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