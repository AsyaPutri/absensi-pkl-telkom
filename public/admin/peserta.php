<?php
include "../../includes/auth.php"; 
checkRole('admin'); 
include "../../config/database.php";

// Ambil data peserta PKL
$sql = "SELECT p.id, p.nama, p.instansi_pendidikan AS sekolah, p.jurusan, 
               p.nis_npm, p.no_hp, p.unit,
               p.tgl_mulai, p.tgl_selesai, p.status, u.email
        FROM peserta_pkl p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.tgl_mulai DESC";
$result = $conn->query($sql);

// Ambil nama file halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Peserta PKL - Telkom Indonesia</title>
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
    /* Overlay untuk mobile */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 900;
    }
    /* Main Content */
    .main-content {
      margin-left: 280px;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }
    /* Header */
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
    /* Table style */
    .table-container {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      white-space: nowrap;
    }
    .table thead th {
      background: var(--telkom-red);
      color: white;
      text-align: center;
      vertical-align: middle;
      position: sticky;
      top: 0;
      z-index: 2;
    }
    .table td {
      vertical-align: middle;
      font-size: 0.9rem;
    }
    .table tbody tr:hover {
      background: #f9f9f9;
    }
    .badge {
      font-size: 0.75rem;
    }
    .btn-sm {
      margin: 2px 0;
    }
    /* Responsive */
    @media(max-width:768px){
      .sidebar { left: -280px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .telkom-logo { height: 60px; }
      .table td, .table th {
        font-size: 0.75rem;
        padding: 6px;
      }
    }
    @media(max-width:576px){
      .header h4 { font-size: 1.2rem; }
      .header small { font-size: 0.8rem; }
      .telkom-logo { height: 45px; }
    }
  </style>
</head>
<body>
  <!-- Overlay mobile -->
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
      <li>
        <a href="dashboard.php" class="nav-link <?= ($current_page=='dashboard.php')?'active':'' ?>">
          <i class="bi bi-house-door me-2"></i> Beranda
        </a>
      </li>
      <li>
        <a href="daftar_pkl.php" class="nav-link <?= ($current_page=='daftar_pkl.php')?'active':'' ?>">
          <i class="bi bi-journal-text me-2"></i> Data Daftar PKL
        </a>
      </li>
      <li>
        <a href="peserta.php" class="nav-link <?= ($current_page=='peserta.php')?'active':'' ?>">
          <i class="bi bi-people me-2"></i> Data Peserta
        </a>
      </li>
      <li>
        <a href="absensi.php" class="nav-link <?= ($current_page=='absensi.php')?'active':'' ?>">
          <i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi
        </a>
      </li>
      <li>
        <a href="../logout.php" class="nav-link">
          <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
      </li>
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
          <h4 class="mb-0 fw-bold text-danger">Data Peserta PKL</h4>
          <small class="text-muted">Sistem Manajemen Praktik Kerja Lapangan</small>
        </div>
      </div>
      <img src="../assets/img/logo_telkom.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <!-- Isi Halaman -->
    <div class="card mt-4 shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0 text-danger">
          <i class="bi bi-people-fill me-2 text-danger"></i> Data Peserta PKL
        </h5>
      </div>
      <div class="card">
        <div class="card-body table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Instansi</th>
                <th>Jurusan</th>
                <th>NIS/NPM</th>
                <th>Email</th>
                <th>No.HP</th>
                <th>Unit</th>
                <th>Periode</th>
                <th>Status</th>
                <th>Dokumen</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if($result && $result->num_rows > 0): $no=1; ?>
                <?php while($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['sekolah']) ?></td>
                    <td><?= htmlspecialchars($row['jurusan']) ?></td>
                    <td><?= htmlspecialchars($row['nis_npm']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['no_hp']) ?></td>
                    <td><?= htmlspecialchars($row['unit']) ?></td>
                    <td class="text-center"><?= $row['tgl_mulai']." s/d ".$row['tgl_selesai'] ?></td>
                    <td class="text-center">
                      <?php if($row['status']=='selesai' || date('Y-m-d') > $row['tgl_selesai']): ?>
                        <span class="badge bg-secondary">Selesai</span>
                      <?php else: ?>
                        <span class="badge bg-success">Berlangsung</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                    </td>
                    <td class="text-center">
                      <?php if($row['status']=='berlangsung' && date('Y-m-d') <= $row['tgl_selesai']): ?>
                        <!-- Tombol ubah jadi selesai -->
                        <form action="ubah_status.php" method="POST" style="display:inline;">
                          <input type="hidden" name="id" value="<?= $row['id'] ?>">
                          <button type="submit" name="selesai" class="btn btn-sm btn-warning">
                            <i class="bi bi-check2-circle"></i> Selesai
                          </button>
                        </form>
                      <?php else: ?>
                        <!-- Tombol download surat & sertifikat -->
                        <a href="generate_surat.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-primary">
                          <i class="bi bi-file-earmark-text"></i> Surat
                        </a>
                        <a href="generate_sertifikat.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-success">
                          <i class="bi bi-award"></i> Sertifikat
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="12" class="text-center text-muted">Belum ada peserta PKL</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

    document.querySelectorAll('.sidebar .nav-link').forEach(link=>{
      link.addEventListener('click', ()=>{
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
