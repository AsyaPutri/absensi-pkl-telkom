<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// Highlight menu aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil filter bulan & tahun dari GET
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;

// Query riwayat peserta
$sql = "SELECT d.nama, d.instansi_pendidikan, d.jurusan, d.nis_npm,
               p.unit, p.tgl_mulai, p.tgl_selesai, p.status
        FROM peserta_pkl p
        JOIN daftar_pkl d ON p.user_id = d.id
        WHERE p.status = 'Selesai'";

// Tambah kondisi filter jika dipilih
if ($bulan > 0 && $tahun > 0) {
    $sql .= " AND MONTH(p.tgl_selesai) = $bulan AND YEAR(p.tgl_selesai) = $tahun";
}
$sql .= " ORDER BY p.tgl_selesai DESC";

$result = $conn->query($sql);

// List bulan untuk dropdown
$nama_bulan = [
  1=>"Januari",2=>"Februari",3=>"Maret",4=>"April",5=>"Mei",6=>"Juni",
  7=>"Juli",8=>"Agustus",9=>"September",10=>"Oktober",11=>"November",12=>"Desember"
];

// Tahun dinamis dari 2020 sampai sekarang+5
$tahun_sekarang = date("Y");
$tahun_list = range(2020, $tahun_sekarang+5);
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
    :root { --telkom-red: #cc0000; --telkom-red-dark: #990000; }
    body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
    .sidebar { width: 280px; min-height: 100vh; background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-red-dark) 100%); color: #e0e0e0; padding: 1rem; position: fixed; top: 0; left: 0; z-index: 1000; box-shadow: 4px 0 15px rgba(0,0,0,0.15); transition: left 0.3s ease; }
    .sidebar a { color: #e0e0e0 !important; border-radius: 12px; padding: 12px 18px; margin-bottom: 8px; display: flex; align-items: center; text-decoration: none; transition: 0.3s; }
    .sidebar a.active, .sidebar a:hover { background-color: rgba(255,255,255,0.15); color: #fff !important; transform: translateX(6px); }
    .sidebar-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 900; }
    .main-content { margin-left: 280px; min-height: 100vh; transition: margin-left 0.3s ease; }
    .header { background: #fff; border-bottom: 1px solid #eee; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .telkom-logo { height: 80px; width: auto; }
    .table-header-red th { background-color: #cc0000 !important; color: #fff !important; }
    @media (max-width: 768px) { .sidebar { left: -280px; } .sidebar.active { left: 0; } .main-content { margin-left: 0; } .telkom-logo { height: 70px; } }
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

    <!-- Content -->
    <div class="container py-4">
      <div class="card shadow-sm">
        <div class="card-header bg-danger text-white fw-bold">
          <i class="bi bi-funnel me-2"></i> Filter Riwayat Peserta
        </div>
        <div class="card-body">
          <form method="get" class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Bulan</label>
              <select name="bulan" class="form-select">
                <option value="0">-- Semua Bulan --</option>
                <?php foreach ($nama_bulan as $num=>$nama): ?>
                  <option value="<?= $num ?>" <?= ($bulan==$num)?'selected':'' ?>><?= $nama ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tahun</label>
              <select name="tahun" class="form-select">
                <option value="0">-- Semua Tahun --</option>
                <?php foreach ($tahun_list as $th): ?>
                  <option value="<?= $th ?>" <?= ($tahun==$th)?'selected':'' ?>><?= $th ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <button type="submit" class="btn btn-danger w-100">
                <i class="bi bi-search me-1"></i> Tampilkan
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="card shadow-sm mt-4">
        <div class="card-header bg-danger text-white fw-bold">
          <i class="bi bi-clock-history me-2"></i> Data Riwayat Peserta
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
              <thead class="table-header-red">
                <tr>
                  <th>No</th>
                  <th>Nama</th>
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
                      <td><?= htmlspecialchars($row['instansi_pendidikan']) ?></td>
                      <td><?= htmlspecialchars($row['jurusan']) ?></td>
                      <td><?= htmlspecialchars($row['unit']) ?></td>
                      <td>
                        <?= date('d M Y', strtotime($row['tgl_mulai'])) ?> - 
                        <?= date('d M Y', strtotime($row['tgl_selesai'])) ?>
                      </td>
                      <td><span class="badge bg-success"><?= $row['status'] ?></span></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
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
