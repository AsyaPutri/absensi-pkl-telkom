<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php"; // koneksi DB

// ====== FILTER SETUP ======
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
$unit = isset($_GET['unit']) ? $_GET['unit'] : 'all'; // default "all"

// Highlight menu aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil data unit dari database
$unitResult = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");

// ====== QUERY DASAR ======
$sql = "
  SELECT 
    d.nama, 
    d.nis_npm, 
    d.instansi_pendidikan, 
    d.jurusan,
    u.nama_unit AS unit, 
    p.tgl_mulai, 
    p.tgl_selesai, 
    p.status
  FROM peserta_pkl p
  JOIN daftar_pkl d ON p.user_id = d.id
  LEFT JOIN unit_pkl u ON d.unit_id = u.id
  WHERE p.status = 'Selesai'
";

if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $tgl_awal_esc = $conn->real_escape_string($tgl_awal);
    $tgl_akhir_esc = $conn->real_escape_string($tgl_akhir);
    $sql .= " AND p.tgl_selesai BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
}

if ($unit !== 'all') {
    $unit_esc = $conn->real_escape_string($unit);
    $sql .= " AND u.id = '$unit_esc'";
}

$sql .= " ORDER BY p.tgl_selesai DESC";

$result = $conn->query($sql);

// Debug error jika query gagal
if (!$result) {
    die("Query error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Peserta PKL - Admin</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --telkom-red: #cc0000;
      --telkom-red-dark: #990000;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }

    /* SIDEBAR */
    .sidebar {
      width: 280px;
      min-height: 100vh;
      background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-red-dark) 100%);
      color: #e0e0e0;
      padding: 1rem;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      box-shadow: 4px 0 15px rgba(0,0,0,0.15);
      transition: left 0.3s ease;
    }

    .sidebar a {
      color: #e0e0e0 !important;
      border-radius: 12px;
      padding: 12px 18px;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      text-decoration: none;
      transition: 0.3s;
    }

    .sidebar a.active,
    .sidebar a:hover {
      background-color: rgba(255,255,255,0.15);
      color: #fff !important;
      transform: translateX(6px);
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 900;
    }

    /* MAIN CONTENT */
    .main-content {
      margin-left: 280px;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }

    .header {
      background: #fff;
      border-bottom: 1px solid #eee;
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .telkom-logo {
      height: 80px;
      width: auto;
    }

    /* TABLE STYLE */
    .table-header-red th {
      background-color: #cc0000 !important;
      color: #fff !important;
      vertical-align: middle;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .sidebar { left: -280px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .telkom-logo { height: 70px; }
    }
  </style>
</head>

<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebarMenu">
    <div class="text-center py-3">
      <i class="bi bi-person-circle fs-1 text-white"></i>
      <p class="fw-bold mb-0 text-white">Admin PKL</p>
      <small class="text-white-50">Telkom Witel Bekasi</small>
    </div>
    <hr class="text-white-50">
    <ul class="nav flex-column">
      <li><a href="dashboard.php" class="nav-link"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link"><i class="bi bi-journal-text me-2"></i> Data Daftar PKL</a></li>
      <li><a href="peserta.php" class="nav-link"><i class="bi bi-people me-2"></i> Data Peserta</a></li>
      <li><a href="absensi.php" class="nav-link"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="riwayat_peserta.php" class="nav-link active"><i class="bi bi-clock-history me-2"></i> Riwayat Peserta</a></li>
      <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">

    <!-- Header -->
    <div class="header">
      <div class="d-flex align-items-center">
        <button class="btn btn-outline-secondary d-md-none me-2" id="menuToggle">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h4 class="mb-0 fw-bold text-danger">Riwayat Peserta PKL</h4>
          <small class="text-muted">Filter dan lihat data peserta yang sudah selesai PKL</small>
        </div>
      </div>
      <img src="../assets/img/logo_telkom.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <!-- Filter -->
    <div class="card mb-3 shadow-sm">
      <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Tanggal Awal</label>
            <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Tanggal Akhir</label>
            <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Unit</label>
            <select name="unit" class="form-select">
              <option value="all" <?= $unit === 'all' ? 'selected' : '' ?>>Semua Unit</option>
              <?php
              $unitQuery = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");
              while ($u = $unitQuery->fetch_assoc()) {
                $sel = ($unit == $u['id']) ? 'selected' : '';
                echo "<option value='{$u['id']}' $sel>" . htmlspecialchars($u['nama_unit']) . "</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-danger"><i class="bi bi-filter"></i> Filter</button>
            <a href="riwayat_peserta.php" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Data -->
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="fw-bold text-danger mb-0">
          <i class="bi bi-clock-history"></i> Data Riwayat Peserta
        </h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center align-middle">
            <thead class="table-header-red">
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIS/NPM</th>
                <th>Instansi</th>
                <th>Jurusan</th>
                <th>Unit</th>
                <th>Periode</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows > 0): ?>
                <?php $no=1; while($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['nis_npm']) ?></td>
                    <td><?= htmlspecialchars($row['instansi_pendidikan']) ?></td>
                    <td><?= htmlspecialchars($row['jurusan']) ?></td>
                    <td><?= htmlspecialchars($row['unit']) ?></td>
                    <td><?= date('d M Y', strtotime($row['tgl_mulai'])) ?> - <?= date('d M Y', strtotime($row['tgl_selesai'])) ?></td>
                    <td><span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center text-muted">Tidak ada data riwayat peserta pada rentang waktu & unit ini.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <!-- Script -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const menuToggle = document.getElementById("menuToggle");
    const sidebarMenu = document.getElementById("sidebarMenu");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    menuToggle?.addEventListener("click", () => {
      sidebarMenu.classList.toggle("active");
      sidebarOverlay.style.display = sidebarMenu.classList.contains("active") ? "block" : "none";
    });
    sidebarOverlay.addEventListener("click", () => {
      sidebarMenu.classList.remove("active");
      sidebarOverlay.style.display = "none";
    });
  </script>
</body>
</html>
