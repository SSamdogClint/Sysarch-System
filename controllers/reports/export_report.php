<?php
// controllers/reports/export_report.php

session_start();
require_once '../../config/db_config.php';

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$report = strtolower(trim($_GET['report'] ?? 'sitin'));
$format = strtolower(trim($_GET['format'] ?? 'csv'));
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');

if (!in_array($report, ['sitin', 'feedback'], true)) {
    $report = 'sitin';
}

if (!in_array($format, ['csv', 'pdf'], true)) {
    $format = 'csv';
}

function validDate($date) {
    if ($date === '') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date ? $date : '';
}

$from = validDate($from);
$to = validDate($to);

function tableHasColumn(mysqli $conn, string $table, string $column): bool {
    $sql = "
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();

    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int)$count > 0;
}

function fetchSitinReport(mysqli $conn, string $from, string $to): array {
    $hasPcNumber = tableHasColumn($conn, 'sitin_records', 'pc_number');
    $pcSelect = $hasPcNumber ? 'sr.pc_number' : 'NULL AS pc_number';

    $where = [];
    $params = [];
    $types = '';

    if ($from !== '') {
        $where[] = 'DATE(sr.login_time) >= ?';
        $params[] = $from;
        $types .= 's';
    }

    if ($to !== '') {
        $where[] = 'DATE(sr.login_time) <= ?';
        $params[] = $to;
        $types .= 's';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
          sr.id,
          sr.studentid,
          sr.fullname,
          sr.purpose,
          sr.lab,
          $pcSelect,
          sr.login_time,
          sr.logout_time,
          sr.status,
          sr.session_at_sitin,
          TIMESTAMPDIFF(MINUTE, sr.login_time, COALESCE(sr.logout_time, NOW())) AS duration_minutes
        FROM sitin_records sr
        $whereSql
        ORDER BY sr.login_time DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function fetchFeedbackReport(mysqli $conn, string $from, string $to): array {
    $where = [];
    $params = [];
    $types = '';

    if ($from !== '') {
        $where[] = 'DATE(f.created_at) >= ?';
        $params[] = $from;
        $types .= 's';
    }

    if ($to !== '') {
        $where[] = 'DATE(f.created_at) <= ?';
        $params[] = $to;
        $types .= 's';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
          f.id,
          s.studentid,
          CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename) AS student_name,
          sr.purpose,
          sr.lab,
          f.issue_type,
          f.feedback_text,
          f.created_at
        FROM feedback f
        INNER JOIN students s ON s.id = f.student_id
        INNER JOIN sitin_records sr ON sr.id = f.sitin_id
        $whereSql
        ORDER BY f.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function durationLabel($minutes): string {
    $minutes = max(0, (int)$minutes);
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    if ($hours > 0 && $mins > 0) return $hours . ' hr ' . $mins . ' min';
    if ($hours > 0) return $hours . ' hr';
    return $mins . ' min';
}

function cleanCell($value): string {
    $value = (string)($value ?? '');
    $value = str_replace(["\r", "\n", "\t"], ' ', $value);
    return trim($value);
}

function sendCsv(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

function pdfEscape(string $text): string {
    $text = str_replace(["\r", "\n", "\t"], ' ', $text);
    $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function makeSimplePdf(string $title, array $lines): string {
    $objects = [];
    $pages = [];
    $lineLimit = 46;
    $chunks = array_chunk($lines, $lineLimit);
    if (!$chunks) {
        $chunks = [[]];
    }

    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = 'PAGES_PLACEHOLDER';

    foreach ($chunks as $chunkIndex => $chunk) {
        $content = "BT\n/F1 10 Tf\n50 790 Td\n";
        $content .= '(' . pdfEscape($title) . ") Tj\n0 -18 Td\n";
        $content .= '/F1 8 Tf' . "\n";
        foreach ($chunk as $line) {
            $content .= '(' . pdfEscape($line) . ") Tj\n0 -14 Td\n";
        }
        $content .= "ET";

        $contentObjNo = count($objects) + 1;
        $pageObjNo = $contentObjNo + 1;
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . (count($objects) + 2) . ' 0 R >> >> /Contents ' . $contentObjNo . ' 0 R >>';
        $pages[] = $pageObjNo . ' 0 R';
    }

    $fontObjNo = count($objects) + 1;
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

    foreach ($objects as $i => $object) {
        if (strpos($object, '/F1 ' . ($i + 2) . ' 0 R') !== false) {
            $objects[$i] = str_replace('/F1 ' . ($i + 2) . ' 0 R', '/F1 ' . $fontObjNo . ' 0 R', $object);
        }
    }

    $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $pages) . '] /Count ' . count($pages) . ' >>';

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

    return $pdf;
}

function sendPdf(string $filename, string $title, array $lines): void {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo makeSimplePdf($title, $lines);
    exit;
}

if ($report === 'feedback') {
    $records = fetchFeedbackReport($conn, $from, $to);
    $headers = ['ID', 'Student ID', 'Student Name', 'Purpose', 'Lab', 'Issue Type', 'Feedback', 'Created At'];
    $rows = [];
    foreach ($records as $row) {
        $rows[] = [
            $row['id'],
            cleanCell($row['studentid']),
            cleanCell($row['student_name']),
            cleanCell($row['purpose']),
            cleanCell($row['lab']),
            cleanCell($row['issue_type']),
            cleanCell($row['feedback_text']),
            cleanCell($row['created_at'])
        ];
    }

    if ($format === 'csv') {
        sendCsv('feedback-report.csv', $headers, $rows);
    }

    $lines = ['Generated: ' . date('Y-m-d h:i A'), 'Filter: ' . ($from ?: 'Start') . ' to ' . ($to ?: 'End'), str_repeat('-', 100)];
    $lines[] = 'ID | Student ID | Name | Lab | Issue | Date | Feedback';
    $lines[] = str_repeat('-', 100);
    foreach ($records as $row) {
        $lines[] = cleanCell($row['id']) . ' | ' . cleanCell($row['studentid']) . ' | ' . cleanCell($row['student_name']) . ' | ' . cleanCell($row['lab']) . ' | ' . cleanCell($row['issue_type']) . ' | ' . cleanCell($row['created_at']);
        $lines[] = 'Feedback: ' . substr(cleanCell($row['feedback_text']), 0, 120);
    }
    sendPdf('feedback-report.pdf', 'Feedback Report', $lines);
}

$records = fetchSitinReport($conn, $from, $to);
$headers = ['ID', 'Student ID', 'Full Name', 'Purpose', 'Lab', 'PC No.', 'Time In', 'Time Out', 'Duration', 'Session Credit', 'Status'];
$rows = [];
foreach ($records as $row) {
    $rows[] = [
        $row['id'],
        cleanCell($row['studentid']),
        cleanCell($row['fullname']),
        cleanCell($row['purpose']),
        cleanCell($row['lab']),
        cleanCell($row['pc_number'] ?: 'N/A'),
        cleanCell($row['login_time']),
        cleanCell($row['logout_time'] ?: 'N/A'),
        durationLabel($row['duration_minutes']),
        cleanCell($row['session_at_sitin']),
        cleanCell($row['status'])
    ];
}

if ($format === 'csv') {
    sendCsv('sitin-report.csv', $headers, $rows);
}

$lines = ['Generated: ' . date('Y-m-d h:i A'), 'Filter: ' . ($from ?: 'Start') . ' to ' . ($to ?: 'End'), str_repeat('-', 110)];
$lines[] = 'ID | Student ID | Name | Lab | PC | Time In | Time Out | Duration | Status';
$lines[] = str_repeat('-', 110);
foreach ($records as $row) {
    $lines[] = cleanCell($row['id']) . ' | ' . cleanCell($row['studentid']) . ' | ' . cleanCell($row['fullname']) . ' | ' . cleanCell($row['lab']) . ' | PC ' . cleanCell($row['pc_number'] ?: 'N/A') . ' | ' . cleanCell($row['login_time']) . ' | ' . cleanCell($row['logout_time'] ?: 'N/A') . ' | ' . durationLabel($row['duration_minutes']) . ' | ' . cleanCell($row['status']);
}
sendPdf('sitin-report.pdf', 'Sit-in Report', $lines);
