<?php 
include "../../includes/auth.php"; 
checkRole('admin'); 
include "../../config/database.php";
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
      <li><a href="absensi.php" class="nav-link <?= ($current_page=='data_kegiatan.php')?'active':'' ?>"><i class="bi bi-clipboard-data me-2"></i> Data_Kegiatan</a></li>
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
    <div class="card shadow-sm mt-4">
  <div class="card-header bg-danger text-white">
    <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i> Rekap Data Kegiatan PKL</h5>
  </div>
  <div class="card-body">
    <!-- Filter -->
    <form method="GET" class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Bulan</label>
        <select name="bulan" class="form-select">
          <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= ($m==($_GET['bulan'] ?? date('m'))) ? 'selected':'' ?>>
              <?= date("F", mktime(0,0,0,$m,10)) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Tahun</label>
        <select name="tahun" class="form-select">
          <?php for($y=date('Y');$y>=2022;$y--): ?>
            <option value="<?= $y ?>" <?= ($y==($_GET['tahun'] ?? date('Y'))) ? 'selected':'' ?>>
              <?= $y ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Unit</label>
        <select name="unit" class="form-select">
          <option value="">Semua Unit</option>
          <?php
          $units = mysqli_query($conn,"SELECT DISTINCT unit FROM peserta_pkl");
          while($u=mysqli_fetch_assoc($units)){
            $selected = ($u['unit']==($_GET['unit']??'')) ? 'selected':''; 
            echo "<option value='{$u['unit']}' $selected>{$u['unit']}</option>";
          }
          ?>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-danger w-100">
          <i class="bi bi-search"></i> Tampilkan
        </button>
      </div>
    </form>

    <!-- Tabel Rekap -->
    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead class="table-header-red">
          <tr>
            <th>Nama</th>
            <th>NIM</th>
            <th>Universitas</th>
            <th>Unit Kerja</th>
            <th>Jumlah Hari Kerja</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $bulan = $_GET['bulan'] ?? date('m');
          $tahun = $_GET['tahun'] ?? date('Y');
          $unit = $_GET['unit'] ?? '';

          $query = "SELECT p.id as user_id, p.nama, p.nis_npm, p.instansi_pendidikan, p.unit,
                           COUNT(a.id) as total_hari
                    FROM peserta_pkl p
                    LEFT JOIN absen a 
                      ON p.id = a.user_id 
                      AND MONTH(a.tanggal)='$bulan' 
                      AND YEAR(a.tanggal)='$tahun'";

          if ($unit != '') {
            $query .= " WHERE p.unit='$unit'";
          }

          $query .= " GROUP BY p.id";

          $result = mysqli_query($conn, $query);

          if (!$result) {
              echo "<tr><td colspan='6' class='text-danger'>Query Error: " . mysqli_error($conn) . "</td></tr>";
          } else {
            while($row = mysqli_fetch_assoc($result)){ ?>
              <tr>
                <td><?= $row['nama'] ?></td>
                <td><?= $row['nis_npm'] ?></td>
                <td><?= $row['instansi_pendidikan'] ?></td>
                <td><?= $row['unit'] ?></td>
                <td><?= $row['total_hari'] ?></td>
                <td>
                  <a href="detail_kegiatan.php?user_id=<?= $row['user_id'] ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" 
                     class="btn btn-sm btn-danger">
                    <i class="bi bi-eye"></i> Rincian
                  </a>
                </td>
              </tr>
            <?php }
          } ?>
        </tbody>
      </table>
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