<?php
// controllers/reports/export_report.php

session_start();
require_once '../../config/db_config.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../../login_page.php');
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';

$format = strtolower(trim($_GET['format'] ?? 'pdf'));
$report = strtolower(trim($_GET['report'] ?? 'sitin'));
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');

if (!in_array($format, ['pdf', 'csv'], true)) {
    $format = 'pdf';
}

if (!in_array($report, ['sitin', 'feedback', 'reservation'], true)) {
    $report = 'sitin';
}

function cleanDate($date)
{
    if ($date === '') {
        return '';
    }

    $dt = DateTime::createFromFormat('Y-m-d', $date);

    return $dt && $dt->format('Y-m-d') === $date ? $date : '';
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reportTitle(string $report): string
{
    if ($report === 'feedback') {
        return 'Feedback Report';
    }

    if ($report === 'reservation') {
        return 'Reservation Report';
    }

    return 'Sit-in Report';
}

function durationLabel($minutes)
{
    $minutes = max(0, (int)$minutes);
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    if ($hours > 0 && $mins > 0) {
        return $hours . ' hr ' . $mins . ' min';
    }

    if ($hours > 0) {
        return $hours . ' hr';
    }

    return $mins . ' min';
}

function hasColumn(mysqli $conn, string $table, string $column): bool
{
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

function fetchSitinRows(mysqli $conn, string $from, string $to): array
{
    $hasPcNumber = hasColumn($conn, 'sitin_records', 'pc_number');
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
        LIMIT 1000
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $rows;
}

function fetchFeedbackRows(mysqli $conn, string $from, string $to): array
{
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
        LIMIT 1000
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $rows;
}

function fetchReservationRows(mysqli $conn, string $from, string $to): array
{
    $where = [];
    $params = [];
    $types = '';

    if ($from !== '') {
        $where[] = 'lr.reservation_date >= ?';
        $params[] = $from;
        $types .= 's';
    }

    if ($to !== '') {
        $where[] = 'lr.reservation_date <= ?';
        $params[] = $to;
        $types .= 's';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            lr.id,
            lr.studentid,
            COALESCE(NULLIF(lr.fullname, ''), CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename)) AS fullname,
            lr.purpose,
            lr.lab,
            lr.pc_number,
            lr.reservation_date,
            lr.reservation_time,
            lr.reservation_end_time,
            lr.status,
            lr.created_at,
            lr.updated_at
        FROM lab_reservations lr
        LEFT JOIN students s ON s.id = lr.student_id
        $whereSql
        ORDER BY lr.reservation_date DESC, lr.reservation_time DESC, lr.created_at DESC
        LIMIT 1000
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $rows;
}

$from = cleanDate($from);
$to = cleanDate($to);

if ($report === 'feedback') {
    $rows = fetchFeedbackRows($conn, $from, $to);
} elseif ($report === 'reservation') {
    $rows = fetchReservationRows($conn, $from, $to);
} else {
    $rows = fetchSitinRows($conn, $from, $to);
}

$title = reportTitle($report);
$filterLabel = ($from ?: 'Start') . ' to ' . ($to ?: 'End');
$fileName = strtolower(str_replace(' ', '-', $title)) . '-' . date('Ymd-His');

/*
  CSV EXPORT
*/
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');

    $output = fopen('php://output', 'w');

    if ($report === 'feedback') {
        fputcsv($output, ['ID', 'Student ID', 'Name', 'Lab', 'Purpose', 'Issue', 'Feedback', 'Date']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['studentid'],
                $row['student_name'],
                $row['lab'],
                $row['purpose'],
                $row['issue_type'] ?: 'General',
                $row['feedback_text'],
                $row['created_at']
            ]);
        }
    } elseif ($report === 'reservation') {
        fputcsv($output, ['ID', 'Student ID', 'Name', 'Purpose', 'Lab', 'PC No.', 'Date', 'Start Time', 'End Time', 'Status', 'Created At']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['studentid'],
                $row['fullname'],
                $row['purpose'],
                $row['lab'],
                !empty($row['pc_number']) ? 'PC ' . $row['pc_number'] : 'N/A',
                $row['reservation_date'],
                $row['reservation_time'],
                $row['reservation_end_time'],
                $row['status'],
                $row['created_at']
            ]);
        }
    } else {
        fputcsv($output, ['ID', 'Student ID', 'Name', 'Purpose', 'Lab', 'PC No.', 'Time In', 'Time Out', 'Duration', 'Status']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['studentid'],
                $row['fullname'],
                $row['purpose'],
                $row['lab'],
                !empty($row['pc_number']) ? 'PC ' . $row['pc_number'] : 'N/A',
                $row['login_time'],
                $row['logout_time'] ?: 'N/A',
                durationLabel($row['duration_minutes']),
                $row['status']
            ]);
        }
    }

    fclose($output);
    exit;
}

/*
  PDF EXPORT USING jsPDF + AutoTable
*/
header('Content-Type: text/html; charset=utf-8');

$logoDataUrl = '';
$logoPath = realpath(__DIR__ . '/../../assets/images/ccsmainlog_nobg.png');

if ($logoPath && file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoDataUrl = 'data:image/png;base64,' . $logoData;
}

$pdfColumns = [];
$pdfRows = [];

if ($report === 'feedback') {
    $pdfColumns = ['#', 'Student ID', 'Name', 'Lab', 'Purpose', 'Issue', 'Feedback', 'Date'];

    foreach ($rows as $index => $row) {
        $issue = !empty($row['issue_type']) ? $row['issue_type'] : 'General';

        $pdfRows[] = [
            $index + 1,
            $row['studentid'] ?? '',
            $row['student_name'] ?? '',
            $row['lab'] ?? '',
            $row['purpose'] ?? '',
            $issue,
            $row['feedback_text'] ?? '',
            $row['created_at'] ?? ''
        ];
    }
} elseif ($report === 'reservation') {
    $pdfColumns = ['#', 'Student ID', 'Name', 'Purpose', 'Lab', 'PC No.', 'Date', 'Time', 'Status', 'Created'];

    foreach ($rows as $index => $row) {
        $time = '';

        if (!empty($row['reservation_time']) && !empty($row['reservation_end_time'])) {
            $time = date('h:i A', strtotime($row['reservation_time'])) . ' - ' . date('h:i A', strtotime($row['reservation_end_time']));
        }

        $pdfRows[] = [
            $index + 1,
            $row['studentid'] ?? '',
            $row['fullname'] ?? '',
            $row['purpose'] ?? '',
            $row['lab'] ?? '',
            !empty($row['pc_number']) ? 'PC ' . $row['pc_number'] : 'N/A',
            $row['reservation_date'] ?? '',
            $time,
            $row['status'] ?? '',
            $row['created_at'] ?? ''
        ];
    }
} else {
    $pdfColumns = ['#', 'Student ID', 'Name', 'Purpose', 'Lab', 'PC No.', 'Time In', 'Time Out', 'Duration', 'Status'];

    foreach ($rows as $index => $row) {
        $pdfRows[] = [
            $index + 1,
            $row['studentid'] ?? '',
            $row['fullname'] ?? '',
            $row['purpose'] ?? '',
            $row['lab'] ?? '',
            !empty($row['pc_number']) ? 'PC ' . $row['pc_number'] : 'N/A',
            $row['login_time'] ?? '',
            !empty($row['logout_time']) ? $row['logout_time'] : 'N/A',
            durationLabel($row['duration_minutes'] ?? 0),
            $row['status'] ?? ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= e($title) ?></title>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e5e7eb;
      font-family: Arial, sans-serif;
      color: #111827;
    }

    .box {
      background: #ffffff;
      border-radius: 16px;
      padding: 28px 32px;
      width: 460px;
      box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
      text-align: center;
    }

    .box h1 {
      margin: 0 0 8px;
      color: #1e3a8a;
      font-size: 22px;
    }

    .box p {
      margin: 0 0 20px;
      color: #6b7280;
      font-size: 14px;
    }

    .btn {
      border: 0;
      background: #1d4ed8;
      color: #ffffff;
      padding: 10px 18px;
      border-radius: 999px;
      cursor: pointer;
      font-weight: 700;
    }
  </style>
</head>

<body>
  <div class="box">
    <h1>Generating PDF...</h1>
    <p id="pdfStatus">Please wait while your report is being downloaded.</p>
    <button class="btn" onclick="generatePDF()">Download Again</button>
  </div>

  <script>
    const reportTitle = <?= json_encode($title) ?>;
    const adminName = <?= json_encode($admin_name) ?>;
    const filterLabel = <?= json_encode($filterLabel) ?>;
    const fileName = <?= json_encode($fileName . '.pdf') ?>;
    const totalRecords = <?= json_encode(count($rows)) ?>;
    const logoDataUrl = <?= json_encode($logoDataUrl) ?>;
    const pdfColumns = <?= json_encode($pdfColumns, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const pdfRows = <?= json_encode($pdfRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    function generatePDF() {
      const status = document.getElementById('pdfStatus');

      if (status) {
        status.textContent = 'Creating PDF file...';
      }

      if (!window.jspdf || !window.jspdf.jsPDF) {
        if (status) {
          status.textContent = 'jsPDF failed to load. Please check your internet connection.';
        }
        return;
      }

      const { jsPDF } = window.jspdf;

      const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4'
      });

      const pageWidth = doc.internal.pageSize.getWidth();
      const pageHeight = doc.internal.pageSize.getHeight();

      if (logoDataUrl) {
        doc.addImage(logoDataUrl, 'PNG', 14, 11, 18, 18);
      }

      doc.setFont('helvetica', 'bold');
      doc.setFontSize(18);
      doc.setTextColor(30, 58, 138);
      doc.text('UC Sit-in System', 36, 17);

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(9);
      doc.setTextColor(75, 85, 99);
      doc.text('Main Campus - College of Computer Studies', 36, 23);

      doc.setFont('helvetica', 'bold');
      doc.setFontSize(17);
      doc.setTextColor(17, 24, 39);
      doc.text(reportTitle, pageWidth - 14, 17, { align: 'right' });

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(9);
      doc.setTextColor(107, 114, 128);
      doc.text('Official Generated Report', pageWidth - 14, 23, { align: 'right' });

      doc.setDrawColor(29, 78, 216);
      doc.setLineWidth(0.8);
      doc.line(14, 33, pageWidth - 14, 33);

      const cardY = 40;
      const cardW = (pageWidth - 36) / 3;
      const cardH = 15;
      const gap = 4;

      const meta = [
        ['Generated By', adminName],
        ['Generated At', new Date().toLocaleString()],
        ['Date Filter', filterLabel],
        ['Report Type', reportTitle],
        ['Total Records', String(totalRecords)],
        ['System', 'UC Sit-in Monitoring']
      ];

      meta.forEach((item, index) => {
        const row = Math.floor(index / 3);
        const col = index % 3;
        const x = 14 + col * (cardW + gap);
        const y = cardY + row * (cardH + gap);

        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(219, 227, 239);
        doc.roundedRect(x, y, cardW, cardH, 2, 2, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(100, 116, 139);
        doc.text(item[0].toUpperCase(), x + 3, y + 5);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(17, 24, 39);

        const value = String(item[1] || '');
        const splitValue = doc.splitTextToSize(value, cardW - 6);
        doc.text(splitValue, x + 3, y + 10);
      });

      let tableBody = pdfRows;

      if (!tableBody.length) {
        tableBody = [Array(pdfColumns.length).fill('')];
        tableBody[0][0] = 'No records found';
      }

      doc.autoTable({
        head: [pdfColumns],
        body: tableBody,
        startY: 78,
        theme: 'grid',
        styles: {
          font: 'helvetica',
          fontSize: 7,
          cellPadding: 2,
          overflow: 'linebreak',
          valign: 'middle'
        },
        headStyles: {
          fillColor: [30, 58, 138],
          textColor: [255, 255, 255],
          fontStyle: 'bold'
        },
        alternateRowStyles: {
          fillColor: [248, 250, 252]
        },
        margin: {
          left: 14,
          right: 14
        },
        didDrawPage: function () {
          const pageNumber = doc.internal.getNumberOfPages();

          doc.setFontSize(8);
          doc.setTextColor(107, 114, 128);

          doc.text(
            'Generated by UC Sit-in System',
            14,
            pageHeight - 10
          );

          doc.text(
            'Page ' + pageNumber,
            pageWidth - 14,
            pageHeight - 10,
            { align: 'right' }
          );
        }
      });

      doc.save(fileName);

      if (status) {
        status.textContent = 'PDF downloaded successfully.';
      }
    }

    window.addEventListener('load', function () {
      setTimeout(generatePDF, 500);
    });
  </script>
</body>
</html>