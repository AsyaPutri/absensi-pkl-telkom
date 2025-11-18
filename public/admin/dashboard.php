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

$request = $conn->query("SELECT COUNT(*) as total 
                          FROM daftar_pkl 
                          WHERE status='request'")
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
  <title>Dashboard Admin InStep - Telkom Witel Bekasi - Karawang </title>
  
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --telkom-red: #cc0000;
      --telkom-red-dark: #990000;
      --telkom-red-light: #e60000;
      --telkom-gray: #6c757d;
      --telkom-gray-light: #e9ecef;
      --telkom-gray-dark: #495057;
    }

    body {
      font-family: 'Inter', 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }

    /* Sidebar */
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

    /* Konten utama */
    .main-content {
      margin-left: 280px;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }

    /* Header */
    .header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    .telkom-logo {
      height: 125px;
      width: auto;
    }

    /* Card Statistik dengan Tema Telkom */
    .stats-card {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: 24px;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      cursor: pointer;
      overflow: hidden;
      position: relative;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      height: 100%;
      min-height: 200px;
    }
    
    .stats-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 100%);
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    
    .stats-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
      border-color: rgba(255, 255, 255, 0.8);
    }
    
    .stats-card:hover::before {
      opacity: 1;
    }

    .card-icon {
      width: 70px;
      height: 70px;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      transition: all 0.3s ease;
    }
    
    .stats-card:hover .card-icon {
      transform: rotate(360deg) scale(1.1);
    }

    .card-number {
      font-size: 3.5rem;
      font-weight: 800;
      margin: 0;
      line-height: 1;
      color: #1f2937;
    }

    .card-label {
      color: #4b5563;
      font-size: 0.75rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .card-info {
      color: #6b7280;
      font-size: 0.8rem;
      font-weight: 500;
    }

    /* Warna Modern & Menarik dengan Sentuhan Telkom */
    .stats-card.telkom-red-primary {
      background: linear-gradient(135deg, #fff5f5 0%, #ffe4e6 100%);
      border-color: rgba(244, 114, 182, 0.3);
    }
    .stats-card.telkom-red-primary .card-icon {
      background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
      color: white;
      box-shadow: 0 4px 20px rgba(244, 63, 94, 0.35);
    }
    .stats-card.telkom-red-primary .card-number {
      color: #be123c;
    }
    .stats-card.telkom-red-primary .card-label {
      color: #9f1239;
    }

    .stats-card.telkom-red-secondary {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      border-color: rgba(251, 191, 36, 0.3);
    }
    .stats-card.telkom-red-secondary .card-icon {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      color: white;
      box-shadow: 0 4px 20px rgba(245, 158, 11, 0.35);
    }
    .stats-card.telkom-red-secondary .card-number {
      color: #b45309;
    }
    .stats-card.telkom-red-secondary .card-label {
      color: #92400e;
    }

    .stats-card.telkom-gray-primary {
      background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
      border-color: rgba(59, 130, 246, 0.3);
    }
    .stats-card.telkom-gray-primary .card-icon {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
      box-shadow: 0 4px 20px rgba(59, 130, 246, 0.35);
    }
    .stats-card.telkom-gray-primary .card-number {
      color: #1e40af;
    }
    .stats-card.telkom-gray-primary .card-label {
      color: #1e3a8a;
    }

    .stats-card.telkom-gray-secondary {
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      border-color: rgba(34, 197, 94, 0.3);
    }
    .stats-card.telkom-gray-secondary .card-icon {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      box-shadow: 0 4px 20px rgba(16, 185, 129, 0.35);
    }
    .stats-card.telkom-gray-secondary .card-number {
      color: #065f46;
    }
    .stats-card.telkom-gray-secondary .card-label {
      color: #047857;
    }

    /* Action Cards */
    .action-card {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: 24px;
      padding: 2rem;
      text-decoration: none;
      display: block;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      height: 100%;
      min-height: 150px;
    }

    .action-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 100%);
      opacity: 0;
      transition: opacity 0.4s ease;
    }

    .action-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    .action-card:hover::before {
      opacity: 1;
    }

    .action-card.telkom-red {
      background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
      border-color: rgba(236, 72, 153, 0.4);
    }

    .action-card.telkom-gray {
      background: linear-gradient(135deg, #ddd6fe 0%, #c7d2fe 100%);
      border-color: rgba(99, 102, 241, 0.4);
    }

    .action-card-text {
      color: #1f2937;
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0;
    }

    .action-card-icon {
      font-size: 3rem;
      transition: transform 0.3s ease;
    }

    .action-card.telkom-red .action-card-icon {
      color: #ec4899;
    }

    .action-card.telkom-gray .action-card-icon {
      color: #6366f1;
    }

    .action-card:hover .action-card-icon {
      transform: scale(1.2);
    }

    .action-card small {
      color: #4b5563;
      font-weight: 500;
    }

    /* Animasi masuk */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .stats-card, .action-card {
      animation: fadeInUp 0.6s ease-out backwards;
    }

    .stats-card:nth-child(1) { animation-delay: 0.1s; }
    .stats-card:nth-child(2) { animation-delay: 0.2s; }
    .stats-card:nth-child(3) { animation-delay: 0.3s; }
    .stats-card:nth-child(4) { animation-delay: 0.4s; }
    .action-card:nth-child(5) { animation-delay: 0.5s; }
    .action-card:nth-child(6) { animation-delay: 0.6s; }

    @media (max-width: 992px) {
      .card-number { font-size: 3rem; }
      .card-icon { width: 65px; height: 65px; font-size: 1.75rem; }
    }

    @media (max-width: 768px) {
      .sidebar { left: -280px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .telkom-logo { height: 70px; }
      .card-number { font-size: 2.5rem; }
      .card-icon { width: 60px; height: 60px; font-size: 1.5rem; }
      .stats-card { min-height: 180px; }
      .action-card { min-height: 130px; }
    }
    
    @media (max-width: 576px) {
      .card-number { font-size: 2rem; }
      .card-icon { width: 50px; height: 50px; font-size: 1.25rem; }
      .header h4 { font-size: 1.2rem; }
      .header small { font-size: 0.8rem; }
      .telkom-logo { height: 50px; }
      .action-card-text { font-size: 1rem; }
      .action-card-icon { font-size: 2rem; }
      .stats-card { min-height: 160px; }
      .action-card { min-height: 120px; }
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
      <p class="fw-bold mb-0 text-white">Admin Internship | InStep </p>
      <small class="text-white-50">Telkom Witel Bekasi - Karawang </small>
    </div>
    <hr class="text-white-50">
    <ul class="nav flex-column">
      <li><a href="dashboard.php" class="nav-link <?= ($current_page=='dashboard.php')?'active':'' ?>"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link <?= ($current_page=='daftar_pkl.php')?'active':'' ?>"><i class="bi bi-journal-text me-2"></i> Data Daftar Internship</a></li>
      <li><a href="peserta.php" class="nav-link <?= ($current_page=='peserta.php')?'active':'' ?>"><i class="bi bi-people me-2"></i> Data Peserta Internship</a></li>
      <li><a href="absensi.php" class="nav-link <?= ($current_page=='absensi.php')?'active':'' ?>"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="riwayat_peserta.php" class="nav-link <?= ($current_page== 'riwayat_peserta.php') ?'active':'' ?>"><i class="bi bi-clock-history me-2"></i> Riwayat Peserta </a></li>
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
          <h4 class="mb-0 fw-bold text-danger">Dashboard Admin Internship | InStep</h4>
          <small class="text-muted">Sistem Manajemen Internship </small>
        </div>
      </div>
      <img src="../assets/img/InStep.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <!-- Content Area -->
    <div class="container-fluid p-4">
      <div class="row g-4">
        
        <!-- Sedang Berlangsung -->
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stats-card telkom-red-primary h-100">
            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <p class="card-label">Sedang Berlangsung</p>
                  <h2 class="card-number"><?= $sedang ?></h2>
                </div>
                <div class="card-icon">
                  <i class="bi bi-play-circle"></i>
                </div>
              </div>
              <div class="mt-auto">
                <small class="card-info">
                  <i class="bi bi-arrow-up"></i> Peserta aktif
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Menunggu Persetujuan -->
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stats-card telkom-red-secondary h-100">
            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <p class="card-label">Menunggu Persetujuan</p>
                  <h2 class="card-number"><?= $pending ?></h2>
                </div>
                <div class="card-icon">
                  <i class="bi bi-hourglass-split"></i>
                </div>
              </div>
              <div class="mt-auto">
                <small class="card-info">
                  <i class="bi bi-clock"></i> Perlu ditindak
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Telah Selesai -->
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stats-card telkom-gray-primary h-100">
            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <p class="card-label">Telah Selesai</p>
                  <h2 class="card-number"><?= $selesai ?></h2>
                </div>
                <div class="card-icon">
                  <i class="bi bi-check2-circle"></i>
                </div>
              </div>
              <div class="mt-auto">
                <small class="card-info">
                  <i class="bi bi-check-all"></i> Internship selesai
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Permintaan Request -->
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stats-card telkom-gray-secondary h-100">
            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <p class="card-label">Permintaan Request</p>
                  <h2 class="card-number"><?= $request ?></h2>
                </div>
                <div class="card-icon">
                  <i class="bi bi-arrow-repeat"></i>
                </div>
              </div>
              <div class="mt-auto">
                <small class="card-info">
                  <i class="bi bi-envelope"></i> Request baru
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Pengumuman -->
        <div class="col-12 col-md-6">
          <a href="admin_upload.php" class="action-card telkom-red">
            <div class="d-flex justify-content-between align-items-center h-100">
              <div>
                <p class="action-card-text mb-2">Upload Pengumuman</p>
                <small>Kelola pengumuman internship</small>
              </div>
              <i class="bi bi-upload action-card-icon"></i>
            </div>
          </a>
        </div>

        <!-- CP Karyawan -->
        <div class="col-12 col-md-6">
          <a href="cp_karyawan.php" class="action-card telkom-gray">
            <div class="d-flex justify-content-between align-items-center h-100">
              <div>
                <p class="action-card-text mb-2">CP Karyawan</p>
                <small>Kontak person karyawan</small>
              </div>
              <i class="bi bi-telephone action-card-icon"></i>
            </div>
          </a>
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