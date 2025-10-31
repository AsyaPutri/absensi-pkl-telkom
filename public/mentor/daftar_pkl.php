<?php
include "../../includes/auth.php";
include "../../config/database.php";
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar PKL - Admin</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --telkom-red: #cc0000;
      --telkom-dark: #990000;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      min-height: 100vh;
      background: linear-gradient(135deg, var(--telkom-red), var(--telkom-dark));
      color: #fff;
      padding: 1rem;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      transition: left .3s;
    }
    .sidebar .nav-link {
      color: #eee;
      border-radius: 12px;
      padding: 12px 16px;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover {
      background: rgba(255, 255, 255, 0.12);
      color: #fff !important;
      transform: translateX(5px);
    }

    /* Overlay untuk HP */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, .5);
      z-index: 900;
    }

    /* Konten Utama */
    .main-content {
      margin-left: 260px;
      transition: margin-left .3s;
      min-height: 100vh;
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
      height: 70px;
    }

    /* Card dan Table */
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .table thead th {
      background: var(--telkom-red);
      color: #fff;
      text-align: center;
    }

    /* Responsif */
    @media(max-width: 768px) {
      .sidebar {
        left: -260px;
      }
      .sidebar.active {
        left: 0;
      }
      .main-content {
        margin-left: 0;
      }
    }
  </style>
</head>

<body>
  <!-- Overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebarMenu">
    <div class="text-center py-3">
      <i class="bi bi-person-circle fs-1 text-white"></i>
      <p class="fw-bold mb-0">Admin PKL</p>
      <small class="text-white-50">Telkom Witel Bekasi</small>
    </div>
    <hr class="text-white-50">
    <ul class="nav flex-column">
      <li><a href="dashboard.php" class="nav-link"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link active"><i class="bi bi-journal-text me-2"></i> Daftar PKL</a></li>
      <li><a href="peserta.php" class="nav-link"><i class="bi bi-people me-2"></i> Data Peserta</a></li>
      <li><a href="absensi.php" class="nav-link"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="riwayat_peserta.php" class="nav-link"><i class="bi bi-clock-history me-2"></i> Riwayat Peserta</a></li>
      <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="header">
      <div class="d-flex align-items-center">
        <button class="btn btn-outline-secondary d-md-none me-2" id="menuToggle"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="mb-0 fw-bold text-danger">Data Daftar PKL</h4>
          <small class="text-muted">Sistem Manajemen Praktik Kerja Lapangan</small>
        </div>
      </div>
      <img src="../assets/img/logo_telkom.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <div class="container-fluid p-4">

      <!-- Alert -->
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Filter dan Pencarian -->
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="get" class="d-flex flex-wrap align-items-center gap-2">
          <label class="fw-semibold mb-0">Status:</label>
          <select name="filter_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="all">Semua</option>
            <option value="pending">Pending</option>
            <option value="diterima">Diterima</option>
            <option value="ditolak">Ditolak</option>
            <option value="nonaktif">Nonaktif</option>
          </select>

          <input type="text" name="search" class="form-control form-control-sm w-auto" placeholder="Cari Jurusan / Instansi">
          <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-search"></i> Cari</button>
        </form>
      </div>

      <!-- Tabel Data -->
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="fw-bold text-danger mb-0"><i class="bi bi-list-check me-2"></i> Daftar PKL</h5>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Instansi</th>
                <th>Jurusan</th>
                <th>No HP</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="text-center">1</td>
                <td>Rizky Ramadhan</td>
                <td>SMK Telkom</td>
                <td>RPL</td>
                <td>08123456789</td>
                <td class="text-center"><span class="badge bg-warning text-dark">Pending</span></td>
                <td class="text-center">
                  <button class="btn btn-success btn-sm">✔ Terima</button>
                  <button class="btn btn-danger btn-sm">❌ Tolak</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar Toggle
  const sidebar = document.getElementById("sidebarMenu");
  const overlay = document.getElementById("sidebarOverlay");
  const toggle = document.getElementById("menuToggle");

  toggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    overlay.style.display = sidebar.classList.contains("active") ? "block" : "none";
  });

  overlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    overlay.style.display = "none";
  });
</script>
</body>
</html>
