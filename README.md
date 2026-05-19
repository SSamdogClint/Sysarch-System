# UC Sit-in System

**University of Cebu — College of Computer Studies**

A PHP + MySQL web-based sit-in monitoring, reservation, software availability, rewards, reports, and leaderboard system.

## Final Submission Notes

This cleaned copy includes fixes for:

- Correct reward controller: `controllers/rewards/update_reward_points.php`
- Reward score no longer decreases when points are redeemed
- Admin Rewards page includes Current Leaderboard, Past Leaderboards, and Archive & Reset in one page
- Reset Sessions button with reset title and reset logs
- PC availability now uses `lab_computers`
- Software availability now uses `software_availability`
- Feedback reports now support `feedback.sitin_id`
- Student notifications table is included
- PC units are generated from PC 1 to PC 56 per lab
- `.git` folder removed from the final ZIP

## Requirements

- XAMPP
- PHP 8.x
- MySQL / MariaDB
- Web browser

## Installation

1. Copy the project folder to:

```text
C:\xampp\htdocs\Sysarch-System
```

2. Start **Apache** and **MySQL** in XAMPP.

3. Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

4. Run this SQL file:

```text
database/full_database_tables_sitin_pc56.sql
```

This creates/updates the `sitin` database and the required tables.

## Database Connection

Open:

```text
config/db_config.php
```

Default XAMPP setup:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sitin');
```

## Access URLs

Home:

```text
http://localhost/Sysarch-System/home.php
```

Login:

```text
http://localhost/Sysarch-System/login_page.php
```

Admin dashboard:

```text
http://localhost/Sysarch-System/admin_module/admin_dashboard.php
```

Student dashboard:

```text
http://localhost/Sysarch-System/student_module/student_dashboard.php
```

## Default Admin Login

| Field | Value |
|---|---|
| ID Number | `admin` |
| Password | `admin123` |

## Main Tables

- `admins`
- `students`
- `sitin_records`
- `lab_reservations`
- `announcements`
- `testimonials`
- `software_availability`
- `feedback`
- `reward_point_logs`
- `reward_redemption_logs`
- `reward_season_settings`
- `leaderboard_archives`
- `leaderboard_archive_entries`
- `session_reset_logs`
- `student_notifications`
- `notifications`
- `lab_computers`

## Feature Summary

### Sit-in Records
Admin can register, deactivate, delete, and view sit-in records.

### Reservations
Students can reserve lab PCs. Admin can approve, reject, cancel, mark done, and mark PCs available/unavailable.

### Rewards and Leaderboard
Admin can rate students by percentage:

```text
0% = 0 points
25% = 2.5 points
50% = 5 points
75% = 7.5 points
100% = 10 points
```

Leaderboard score:

```text
Final Score = Earned Reward Score × 60% + Sit-in Hour Score × 20% + Task Score × 20%
```

### Redeem Sessions
Students can redeem:

```text
10 spendable points = 1 additional sit-in session
```

Redeeming does **not** reduce `reward_points_earned`, so leaderboard score does not go down.

### Archive and Reset
Admin can archive the current leaderboard and reset the reward season. Past leaderboards are viewable in the same Rewards page.

### Reset Sessions
Admin can reset all student session credits back to 30 and save a reset title/log.

## Final Reminder

Before final demo, test these accounts/features:

- Register a new student
- Login as student
- Create a reservation
- Approve reservation as admin
- Register and deactivate sit-in
- Add reward rating
- Redeem 10 points
- Archive and reset leaderboard
- Reset sessions with title
