<?php
// admin_module/Admin_Software.php

session_start();
require_once '../config/db_config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../login_page.php');
    exit;
}

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator');
$message = $_SESSION['software_flash_message'] ?? '';
$messageType = $_SESSION['software_flash_type'] ?? 'success';
unset($_SESSION['software_flash_message'], $_SESSION['software_flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $lab = trim($_POST['lab'] ?? '');
        $softwareName = trim($_POST['software_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $version = trim($_POST['version'] ?? '');
        $status = trim($_POST['status'] ?? 'installed');

        if ($lab === '' || $softwareName === '') {
            $message = 'Lab and software name are required.';
            $messageType = 'danger';
        } else {
            if (!in_array($status, ['installed', 'unavailable'], true)) {
                $status = 'installed';
            }

            $stmt = $conn->prepare("
                INSERT INTO software_availability (lab, software_name, category, version, status)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    category = VALUES(category),
                    version = VALUES(version),
                    status = VALUES(status)
            ");
            if ($stmt) {
                $stmt->bind_param('sssss', $lab, $softwareName, $category, $version, $status);
                $stmt->execute();
                $affectedRows = $stmt->affected_rows;
                $stmt->close();
                $message = ($affectedRows === 2)
                    ? 'Software application already existed, so its details were updated.'
                    : 'Software application added successfully.';
            } else {
                $message = 'Unable to add software. Please check the software_availability table.';
                $messageType = 'danger';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM software_availability WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                $message = 'Software application deleted successfully.';
            }
        }
    }

    if ($action === 'import') {
        if (empty($_FILES['csv_file']['tmp_name'])) {
            $message = 'Please choose a CSV file first.';
            $messageType = 'danger';
        } else {
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $imported = 0;
            $updated = 0;
            $skipped = 0;

            if ($handle) {
                $rowNumber = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    if (count($row) < 2) {
                            $skipped++;
                            continue;
                        }

                    $firstCell = strtolower(trim($row[0] ?? ''));
                    $secondCell = strtolower(trim($row[1] ?? ''));
                    if ($rowNumber === 1 && ($firstCell === 'lab' || $secondCell === 'software_name' || $secondCell === 'software name')) {
                        continue;
                    }

                    $lab = trim($row[0] ?? '');
                    $softwareName = trim($row[1] ?? '');
                    $category = trim($row[2] ?? '');
                    $version = trim($row[3] ?? '');
                    $status = strtolower(trim($row[4] ?? 'installed'));

                    if ($lab === '' || $softwareName === '') {
                            $skipped++;
                            continue;
                        }
                    if (!in_array($status, ['installed', 'unavailable'], true)) $status = 'installed';

                    $stmt = $conn->prepare("
                        INSERT INTO software_availability (lab, software_name, category, version, status)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            category = VALUES(category),
                            version = VALUES(version),
                            status = VALUES(status)
                    ");
                    if ($stmt) {
                        $stmt->bind_param('sssss', $lab, $softwareName, $category, $version, $status);
                        $stmt->execute();

                        if ($stmt->affected_rows === 1) {
                            $imported++;
                        } elseif ($stmt->affected_rows === 2) {
                            $updated++;
                        } else {
                            // affected_rows can be 0 when the duplicate row is exactly the same.
                            $skipped++;
                        }

                        $stmt->close();
                    } else {
                        $skipped++;
                    }
                }
                fclose($handle);
            }

            $message = $imported . ' new software record(s) imported, ' . $updated . ' existing record(s) updated, and ' . $skipped . ' duplicate/invalid row(s) skipped.';
        }
    }
    $_SESSION['software_flash_message'] = $message;
    $_SESSION['software_flash_type'] = $messageType;

    header('Location: Admin_Software.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$labFilter = trim($_GET['lab'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
if (!in_array($statusFilter, ['', 'installed', 'unavailable'], true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(software_name LIKE ? OR category LIKE ? OR version LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($labFilter !== '') {
    $where[] = 'lab = ?';
    $params[] = $labFilter;
    $types .= 's';
}

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$softwareList = [];
$stmt = $conn->prepare("
    SELECT 
        id,
        lab,
        software_name,
        category,
        version,
        status,
        created_at,
        created_at AS uploaded_at
    FROM software_availability
    $whereSql
    ORDER BY lab ASC, software_name ASC
");
if ($stmt) {
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $softwareList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$labs = [];
$result = $conn->query("SELECT DISTINCT lab FROM software_availability ORDER BY lab ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $labs[] = $row['lab'];
    }
}

$totalApps = 0;
$totalLabs = 0;
$installedApps = 0;
$unavailableApps = 0;
$result = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT lab) AS labs, SUM(status = 'installed') AS installed, SUM(status = 'unavailable') AS unavailable FROM software_availability");
if ($result && $row = $result->fetch_assoc()) {
    $totalApps = (int)$row['total'];
    $totalLabs = (int)$row['labs'];
    $installedApps = (int)$row['installed'];
    $unavailableApps = (int)$row['unavailable'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UC – Software Import</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="admin-dashboard-page">
  <nav class="uc-nav">
    <a class="nav-brand" href="admin_dashboard.php">
      <img src="../assets/images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Admin Panel</div>
      </div>
    </a>
    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu"><span></span><span></span><span></span></button>
    <div class="nav-links" id="navLinks">
      <span style="font-size:13px; color:#6b7280; padding: 0 8px;"><?= $admin_name ?></span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
      <div class="container-fluid py-4">
        <div class="row align-items-stretch g-3 mb-4">
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-circle bg-primary text-white p-3 d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                  </div>
                  <div>
                    <h1 class="h3 mb-1">Software App Import</h1>
                    <p class="text-muted mb-0">Upload, add, and manage available applications per laboratory.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                  <p class="text-muted mb-1">Quick Action</p>
                  <h2 class="h5 mb-0">Add software manually</h2>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSoftwareModal">
                  <i class="bi bi-plus-circle me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>

        <?php if ($message): ?>
          <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
          <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Total Apps</div><div class="h3 mb-0"><?= $totalApps ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Labs</div><div class="h3 mb-0"><?= $totalLabs ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Installed</div><div class="h3 mb-0 text-success"><?= $installedApps ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Unavailable</div><div class="h3 mb-0 text-danger"><?= $unavailableApps ?></div></div></div></div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-0"><i class="bi bi-upload text-primary me-2"></i>Import CSV</h2>
                <small class="text-muted">Format: lab, software_name, category, version, status</small>
              </div>
              <div class="card-body p-4">
                <div class="alert alert-info border-0 shadow-sm mb-3" style="background:#eff6ff;color:#1e3a8a;">
                  <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i>CSV Upload Note</div>
                  <div class="small mb-2">Use the sample format below so you do not need to manually create a CSV file during presentation.</div>
                  <a class="btn btn-sm btn-outline-primary" href="../database/software_import_sample.csv" download>
                    <i class="bi bi-download me-1"></i>Download example CSV file
                  </a>
                  <div class="small mt-2">Required columns: <code>lab, software_name, category, version, status</code></div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="row g-3">
                  <input type="hidden" name="action" value="import">
                  <div class="col-12">
                    <label class="form-label fw-semibold">CSV File</label>
                    <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                  </div>
                  <div class="col-12">
                    <button class="btn btn-success w-100" type="submit"><i class="bi bi-upload me-1"></i>Upload CSV</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-0"><i class="bi bi-funnel text-primary me-2"></i>Filter Software</h2>
              </div>
              <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                  <div class="col-lg-4">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Software, category, version">
                  </div>
                  <div class="col-lg-3">
                    <label class="form-label fw-semibold">Lab</label>
                    <select class="form-select" name="lab">
                      <option value="">All Labs</option>
                      <?php foreach ($labs as $lab): ?>
                        <option value="<?= htmlspecialchars($lab) ?>" <?= $labFilter === $lab ? 'selected' : '' ?>><?= htmlspecialchars($lab) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-lg-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                      <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All</option>
                      <option value="installed" <?= $statusFilter === 'installed' ? 'selected' : '' ?>>Installed</option>
                      <option value="unavailable" <?= $statusFilter === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                  </div>
                  <div class="col-lg-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i></button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <h2 class="h5 mb-0">Software List</h2>
                <small class="text-muted">Records visible to students in Software Availability.</small>
              </div>
              <span class="badge text-bg-primary rounded-pill"><?= count($softwareList) ?> record(s)</span>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th class="ps-4">#</th><th>Lab</th><th>Software Name</th><th>Category</th><th>Version</th><th>Status</th><th>Created</th><th class="pe-4">Action</th></tr>
                </thead>
                <tbody>
                  <?php if (!$softwareList): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No software records found.</td></tr>
                  <?php endif; ?>
                  <?php foreach ($softwareList as $index => $software): ?>
                    <tr>
                      <td class="ps-4"><?= $index + 1 ?></td>
                      <td><span class="badge text-bg-secondary"><?= htmlspecialchars($software['lab']) ?></span></td>
                      <td class="fw-semibold"><?= htmlspecialchars($software['software_name']) ?></td>
                      <td><?= htmlspecialchars($software['category'] ?: 'N/A') ?></td>
                      <td><?= htmlspecialchars($software['version'] ?: 'N/A') ?></td>
                      <td><span class="badge text-bg-<?= $software['status'] === 'installed' ? 'success' : 'danger' ?>"><?= htmlspecialchars(ucfirst($software['status'])) ?></span></td>
                      <td>
                          <?= !empty($software['uploaded_at']) ? date('M d, Y h:i A', strtotime($software['uploaded_at'])) : 'N/A' ?>
                        </td>
                      <td class="pe-4">
                        <form method="POST" data-confirm-message="Delete this software record?" data-confirm-title="Delete Confirmation" data-confirm-type="danger" data-confirm-ok="Yes, Delete">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int)$software['id'] ?>">
                          <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal fade" id="addSoftwareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Software Application</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Lab</label><input class="form-control" name="lab" placeholder="Lab 524" required></div>
              <div class="col-md-6"><label class="form-label">Software Name</label><input class="form-control" name="software_name" placeholder="Visual Studio Code" required></div>
              <div class="col-md-6"><label class="form-label">Category</label><input class="form-control" name="category" placeholder="Programming"></div>
              <div class="col-md-6"><label class="form-label">Version</label><input class="form-control" name="version" placeholder="Latest"></div>
              <div class="col-12"><label class="form-label">Status</label><select class="form-select" name="status"><option value="installed">Installed</option><option value="unavailable">Unavailable</option></select></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Software</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const toggler = document.getElementById('navToggler');
    const navLinks = document.getElementById('navLinks');
    const sidebar = document.getElementById('sidebar');
    if (toggler) {
      toggler.addEventListener('click', () => {
        navLinks.classList.toggle('open');
        sidebar.classList.toggle('open');
      });
    }
  </script>
</body>
</html>
