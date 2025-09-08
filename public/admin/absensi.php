<?php
include "../../includes/auth.php";
checkRole('admin');

// untuk highlight menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin PKL - Telkom Indonesia</title>
  
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    /* ==========================
       Warna tema Telkom
    ========================== */
    :root {
      --telkom-red: #cc0000;
      --telkom-red-dark: #990000;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }

    /* ==========================
       Sidebar
    ========================== */
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
      transition: 0.3s;
      text-decoration: none;
    }
    .sidebar a.active,
    .sidebar a:hover {
      background-color: rgba(255,255,255,0.15);
      color: #fff !important;
      transform: translateX(6px);
    }

    /* Overlay hitam untuk mobile */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top:0;
      left:0;
      width:100%;
      height:100%;
      background: rgba(0,0,0,0.5);
      z-index: 900;
    }

    /* ==========================
       Konten utama
    ========================== */
    .main-content {
      margin-left: 280px;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }

    /* ==========================
       Header
    ========================== */
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

    .table-header-red th {
        background-color: #cc0000 !important; /* Merah Telkom */
        color: #fff !important;              /* Tulisan putih */
    }

    /* ==========================
       Responsiveness
    ========================== */
    @media (max-width: 768px) {
      .sidebar { left: -280px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }

      .telkom-logo { height: 70px; }
      .card-number { font-size: 2rem; }
    }
    @media (max-width: 576px) {
      .stats-card .card-body { padding: 1rem; }
      .card-number { font-size: 1.8rem; }
      .header h4 { font-size: 1.2rem; }
      .header small { font-size: 0.8rem; }
      .telkom-logo { height: 50px; }
    }
  </style>
</head>
<body>
  <!-- Overlay hitam di mobile -->
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
      <li><a href="dashboard.php" class="nav-link <?= ($current_page=='dashboard.php')?'active':'' ?>"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link <?= ($current_page=='daftar_pkl.php')?'active':'' ?>"><i class="bi bi-journal-text me-2"></i> Data Daftar PKL</a></li>
      <li><a href="peserta.php" class="nav-link <?= ($current_page=='peserta.php')?'active':'' ?>"><i class="bi bi-people me-2"></i> Data Peserta</a></li>
      <li><a href="absensi.php" class="nav-link <?= ($current_page=='absensi.php')?'active':'' ?>"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="header">
      <div class="d-flex align-items-center">
        <!-- tombol toggle sidebar untuk mobile -->
        <button class="btn btn-outline-secondary d-md-none me-2" id="menuToggle">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h4 class="mb-0 fw-bold text-danger">Dashboard Admin PKL</h4>
          <small class="text-muted">Sistem Manajemen Praktik Kerja Lapangan</small>
        </div>
      </div>
      <img src="../assets/img/logo_telkom.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <!-- Filter Card -->
    <div class="card mb-3">
      <div class="card-body">
        <form id="filterForm" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Tanggal Awal</label>
            <input type="date" name="tgl_awal" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Tanggal Akhir</label>
            <input type="date" name="tgl_akhir" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Unit</label>
            <select name="unit" class="form-select">
              <option value="">Semua Unit</option>
              <!-- Isi opsi dari DB -->
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-danger">
              <i class="bi bi-filter"></i> Filter
            </button>
            <button type="button" id="resetBtn" class="btn btn-outline-secondary">Reset</button>
            <button type="button" id="exportCsv" class="btn btn-outline-success ms-auto">
              <i class="bi bi-download"></i> Export CSV
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Rekap -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="fw-bold text-danger mb-0"><i class="bi bi-calendar-check me-2"></i> Data Rekap Absensi</h5>
        </div>
    </div>
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center align-middle" id="rekapTable">
            <thead class="table-header-red">
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Instansi / Unit</th>
                <th>Tgl Mulai</th>
                <th>Tgl Selesai</th>
                <th>Hari Kerja</th>
                <th>Hadir</th>
                <th>Izin</th>
                <th>Sakit</th>
                <th>Alpha</th>
                <th>% Kehadiran</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="rekapBody">
              <!-- Diisi oleh server / AJAX -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Detail Rekap: <span id="modalNama"></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody id="modalBody">
                  <!-- Diisi AJAX -->
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button class="btn btn-primary" id="exportPerPeserta">Export peserta</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Script -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle sidebar di mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');

    menuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
    });

    overlay.addEventListener('click', () => {
      sidebar.classList.remove('active');
      overlay.style.display = 'none';
    });
  </script>
</body>
</html>
