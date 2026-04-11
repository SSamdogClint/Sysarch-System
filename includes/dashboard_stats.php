<?php
require_once __DIR__ . '/../config/db_config.php';

// Total registered students
$total_students = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM students");
if ($result) {
    $row = $result->fetch_assoc();
    $total_students = (int)$row['total'];
}

// Currently active sit-ins
$current_sitin = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM sitin_records WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $current_sitin = (int)$row['total'];
}

// Total sit-in records
$total_sitin = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM sitin_records");
if ($result) {
    $row = $result->fetch_assoc();
    $total_sitin = (int)$row['total'];
}

// Total announcements
$total_posts = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM announcements");
if ($result) {
    $row = $result->fetch_assoc();
    $total_posts = (int)$row['total'];
}

// Chart data: Sit-ins by purpose
$chart_labels = [];
$chart_values = [];

$result = $conn->query("
    SELECT purpose, COUNT(*) AS total
    FROM sitin_records
    GROUP BY purpose
    ORDER BY total DESC
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $chart_labels[] = $row['purpose'] ?: 'Unknown';
        $chart_values[] = (int)$row['total'];
    }
} else {
    $chart_labels = ['No Data'];
    $chart_values = [1];
}