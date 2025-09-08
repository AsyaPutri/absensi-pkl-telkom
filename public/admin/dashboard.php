<?php 
include "../../includes/auth.php"; 
checkRole('admin'); 
include "../../config/database.php";

// ==========================
// Hitung data statistik
// ==========================
$pending = $conn->query("SELECT COUNT(*) as total 
                         FROM daftar_pkl 
                         WHERE status='pending'")
                ->fetch_assoc()['total'] ?? 0;

$sedang = $conn->query("SELECT COUNT(*) as total 
                        FROM peserta_pkl 
                        WHERE status='berlangsung'")
                ->fetch_assoc()['total'] ?? 0;

$selesai = $conn->query("SELECT COUNT(*) as total 
                         FROM peserta_pkl 
                         WHERE status='selesai'")
                ->fetch_assoc()['total'] ?? 0;

$total_peserta = $conn->query("SELECT COUNT(*) as total 
                               FROM peserta_pkl")
                      ->fetch_assoc()['total'] ?? 0;

// Ambil nama file halaman yang sedang dibuka 
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

    /* ==========================
       Card Statistik
    ========================== */
    .stats-card {
      border: none;
      border-radius: 20px;
      transition: 0.3s;
    }
    .stats-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .card-number {
      font-size: 3rem;
      font-weight: bold;
    }

    /* ==========================
       Welcome Card
    ========================== */
    .welcome-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 20px;
      color: white;
      padding: 2rem;
      margin-bottom: 2rem;
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

    <div class="container-fluid p-4">
      <!-- Statistik -->
      <div class="row g-4">
        <div class="col-lg-3 col-md-6 col-12">
          <div class="card stats-card bg-success text-white text-center p-3">
            <h6>Sedang Berlangsung</h6>
            <div class="card-number"><?= $sedang ?></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
          <div class="card stats-card bg-warning text-dark text-center p-3">
            <h6>Menunggu Persetujuan</h6>
            <div class="card-number"><?= $pending ?></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
          <div class="card stats-card bg-info text-white text-center p-3">
            <h6>Telah Selesai</h6>
            <div class="card-number"><?= $selesai ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Sidebar Toggle Script -->
  <script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');

    if(menuToggle){
      menuToggle.addEventListener('click', ()=>{
        sidebar.classList.toggle('active');
        overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        
        // Sembunyikan tombol saat sidebar aktif
        menuToggle.style.display = sidebar.classList.contains('active') ? 'none' : 'inline-block';
      });
    }
    if(overlay){
      overlay.addEventListener('click', ()=>{
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
        menuToggle.style.display = 'inline-block';
      });
    }

    // Tutup sidebar otomatis setelah klik menu di mobile
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('active');
          overlay.style.display = 'none';
          menuToggle.style.display = 'inline-block';
        }
      });
    });
  </script>
</body>
</html>