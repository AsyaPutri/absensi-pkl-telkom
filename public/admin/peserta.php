<?php
// ============================
// Include file authentication & database
// ============================
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// ============================
// Ambil filter dari URL
// ============================
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_unit   = isset($_GET['unit']) ? $_GET['unit'] : 'all';

// ============================
// Daftar unit untuk dropdown filter
// ============================
$unitResult = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");

// ============================
// Ambil Data Peserta PKL
// ============================
$sql = "
  SELECT 
    p.id AS peserta_id,
    p.nama, 
    p.email, 
    p.nis_npm, 
    p.no_hp,
    p.instansi_pendidikan, 
    p.jurusan,
    p.tgl_mulai, 
    p.tgl_selesai, 
    p.status,
    p.laporan_pkl,
    p.pesan_admin,
    p.pesan_status,
    d.skill, 
    d.durasi, 
    d.alamat,
    d.upload_foto, 
    d.upload_kartu_identitas, 
    d.upload_surat_permohonan,
    d.memiliki_laptop, 
    d.bersedia_unit_manapun, 
    d.nomor_surat_permohonan,
    d.ipk_nilai_ratarata, 
    d.semester, 
    d.tgl_daftar,
    u.nama_unit
  FROM peserta_pkl p
  LEFT JOIN daftar_pkl d ON p.email = d.email
  LEFT JOIN unit_pkl u ON p.unit_id = u.id
  WHERE 1=1
";

// ============================
// Filter berdasarkan unit
// ============================
if ($filter_unit !== 'all') {
  $sql .= " AND p.unit_id = '" . $conn->real_escape_string($filter_unit) . "'";
}

// ============================
// Filter berdasarkan status
// ============================
if ($status_filter === 'berlangsung') {
  $sql .= " AND p.status = 'berlangsung'";
} elseif ($status_filter === 'selesai') {
  $sql .= " AND p.status = 'selesai'";
}

// Pencarian (nama, NIS, instansi, jurusan)
$search = $_GET['search'] ?? '';
if (!empty($search)) {
  $s = $conn->real_escape_string($search);
  $sql .= " AND (
      p.nama LIKE '%$s%' 
      OR p.nis_npm LIKE '%$s%'
      OR p.instansi_pendidikan LIKE '%$s%' 
      OR p.jurusan LIKE '%$s%'
      OR d.skill LIKE '%$s%'
  )";
}

if (isset($_GET['read_id'])) {

  $read_id = intval($_GET['read_id']);

  if ($read_id > 0) {
      $conn->query("
          UPDATE peserta_pkl
          SET pesan_status = 1
          WHERE id = '$read_id'
      ");
  }

  exit;
}

// ============================
// Urutkan berdasarkan tanggal mulai
// ============================
$sql .= " ORDER BY p.tgl_mulai DESC";

// ============================
// Eksekusi query
// ============================
$result = $conn->query($sql);

// ============================
// Nama file aktif untuk sidebar highlight
// ============================
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Peserta InStep - Telkom Witel Bekasi - Karawang</title>

  <!-- Bootstrap & Icon -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

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
    .telkom-logo { height: 125px; width: auto; }

    /* Table style */
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
    .table tbody tr:hover { background: #f9f9f9; }

    /* Filter Card */
    .filter-card {
      border: none;
      border-radius: 10px;
      background: #fff;
      box-shadow: 0 3px 8px rgba(0,0,0,0.05);
    }
    .filter-label {
      font-weight: 600;
      color: #555;
      margin-right: 10px;
      white-space: nowrap;
    }
    .filter-card .form-select {
      min-width: 200px;
    }
    @media(max-width:768px){
      .sidebar { left: -280px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .telkom-logo { height: 60px; }
      .filter-card { text-align: center; }
      .filter-label { margin-bottom: 6px; }
    }
    .filter-card {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      border-radius: 12px;
      border: 1px solid #eee;
    }

    .filter-card .form-label {
      font-size: 0.9rem;
      color: #444;
    }

    .filter-card select {
      border-radius: 10px;
    }

    .filter-card button {
      border-radius: 10px;
    }

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

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar Navigasi -->
  <div class="sidebar" id="sidebarMenu">
    <div class="text-center py-3">
      <i class="bi bi-person-circle fs-1 text-white"></i>
      <p class="fw-bold mb-0 text-white">Admin Internship | InStep</p>
      <small class="text-white-50">Telkom Witel Bekasi - Karawang</small>
    </div>
    <hr class="text-white-50">
    <ul class="nav flex-column">
      <li><a href="dashboard.php" class="nav-link <?= ($current_page=='dashboard.php')?'active':'' ?>"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link <?= ($current_page=='daftar_pkl.php')?'active':'' ?>"><i class="bi bi-journal-text me-2"></i> Data Daftar Internship</a></li>
      <li><a href="peserta.php" class="nav-link <?= ($current_page=='peserta.php')?'active':'' ?>"><i class="bi bi-people me-2"></i> Data Peserta Internship</a></li>
      <li><a href="absensi.php" class="nav-link <?= ($current_page=='absensi.php')?'active':'' ?>"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="riwayat_peserta.php" class="nav-link <?= ($current_page=='riwayat_peserta.php')?'active':'' ?>"><i class="bi bi-clock-history me-2"></i> Riwayat Peserta</a></li>
      <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">

  <!-- Alert Success / Error -->
  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i>
      Peserta magang berhasil diselesaikan!
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      Terjadi kesalahan saat memperbarui status peserta.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="header">
    <div class="d-flex align-items-center">
      <button class="btn btn-outline-secondary d-md-none me-2" id="menuToggle">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <h4 class="mb-0 fw-bold text-danger">Data Peserta Internship | InStep</h4>
        <small class="text-muted">Sistem Manajemen Internship</small>
      </div>
    </div>
    <img src="../assets/img/InStep.png" class="telkom-logo" alt="Telkom Logo">
  </div>

  <!-- Filter Section -->
  <div class="card shadow-sm border-0 mt-4 mx-3 filter-card">
    <div class="card-body py-4">
      <form method="GET" class="row g-3 align-items-end">
        
        <!-- Filter Unit -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
          <label for="unit" class="form-label fw-semibold text-secondary">Pilih Unit</label>
          <select name="unit" id="unit" class="form-select shadow-sm border-0 bg-light"
                  onchange="this.form.submit()">
            <option value="all" <?= ($filter_unit == 'all') ? 'selected' : '' ?>>Semua Unit</option>
            <?php
            $unitResult->data_seek(0);
            while ($unit = $unitResult->fetch_assoc()): ?>
              <option value="<?= $unit['id']; ?>" <?= ($filter_unit == $unit['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($unit['nama_unit']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Pencarian -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
          <label for="q" class="form-label fw-semibold text-secondary">
            Cari (Nama / NIS / Instansi / Jurusan)
          </label>
          <div class="input-group shadow-sm">
            <span class="input-group-text bg-danger text-white border-0">
              <i class="bi bi-search"></i>
            </span>
            <input 
              type="text" 
              name="search" 
              value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
              class="form-control border-0 bg-light" 
              placeholder="Ketik kata kunci..."
            >
          </div>
        </div>

        <!-- Filter Status -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
          <label for="status" class="form-label fw-semibold text-secondary">Status Peserta</label>
          <select name="status" id="status" class="form-select shadow-sm border-0 bg-light"
                  onchange="this.form.submit()">
            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>Semua Status</option>
            <option value="berlangsung" <?= $status_filter == 'berlangsung' ? 'selected' : '' ?>>Berlangsung</option>
            <option value="selesai" <?= $status_filter == 'selesai' ? 'selected' : '' ?>>Selesai</option>
          </select>
        </div>

        <!-- Tombol Aksi -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 d-flex gap-2">
          <button type="submit" class="btn btn-danger flex-fill shadow-sm">
            <i class="bi bi-filter-circle me-1"></i> Filter
          </button>
          <a href="peserta.php" class="btn btn-outline-secondary flex-fill shadow-sm">
            <i class="bi bi-arrow-repeat me-1"></i> Reset
          </a>
        </div>

      </form>
    </div>
  </div>

  <!-- Data Peserta PKL -->
  <div class="card mt-4 shadow-sm mx-3 mb-4">
    <div class="card-header bg-white">
      <h5 class="mb-0 text-danger">
        <i class="bi bi-people-fill me-2 text-danger"></i> Data Peserta Internship
      </h5>
    </div>

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
            <th>Rincian</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): $no = 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['instansi_pendidikan']) ?></td>
                <td><?= htmlspecialchars($row['jurusan']) ?></td>
                <td><?= htmlspecialchars($row['nis_npm']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['no_hp']) ?></td>
                <td><?= htmlspecialchars($row['nama_unit']) ?></td>

                <!-- Tombol aksi -->
                <td class="text-center">
                  <div class="inline-flex items-center justify-center gap-2">

                    <!-- RINCIAN -->
                    <button type="button"
                      class="px-2 py-1 rounded-md bg-blue-600 text-white hover:bg-blue-700"
                      data-bs-toggle="modal"
                      data-bs-target="#detailModal<?= $row['peserta_id']; ?>">
                      <i class="bi bi-search"></i>
                    </button>

                    <!-- LAPORAN -->
                    <button type="button"
                      class="px-2 py-1 rounded-md bg-yellow-400 text-black hover:bg-yellow-500"
                      data-bs-toggle="modal"
                      data-bs-target="#modalLaporan<?= $row['peserta_id']; ?>">
                      <i class="bi bi-file-earmark-text"></i>
                    </button>

                    <!-- PESAN -->
                    <button 
                      type="button"
                      class="position-relative px-2 py-1 rounded-md bg-green-500 text-white hover:bg-green-600"
                      data-bs-toggle="modal"
                      data-bs-target="#modalPesan<?= $row['peserta_id']; ?>"
                      onclick="fetch('?read_id=<?= $row['peserta_id']; ?>').then(()=>{ this.querySelector('.badge')?.remove(); });">

                      <i class="bi bi-chat-dots"></i>

                      <?php if ($row['pesan_status'] == 0 && !empty($row['pesan_admin'])): ?>
                          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                              !
                          </span>
                      <?php endif; ?>
                    </button>
                  </div>
                </td>

                <!-- Status -->
                <td class="text-center">
                  <?php if ($row['status'] == 'selesai' || date('Y-m-d') > $row['tgl_selesai']): ?>
                    <span class="badge bg-success">Selesai</span>
                  <?php else: ?>
                    <span class="badge bg-info">Berlangsung</span>
                  <?php endif; ?>
                </td>

                <!-- Tombol Selesai -->
                <td class="text-center">
                  <?php if ($row['status'] == 'berlangsung' && date('Y-m-d') <= $row['tgl_selesai']): ?>
                    <form id="formSelesai<?= $row['peserta_id'] ?>" action="ubah_status.php" method="POST">
                      <input type="hidden" name="id" value="<?= $row['peserta_id'] ?>">
                      <button type="button" class="btn btn-sm btn-warning"
                        onclick="konfirmasiSelesai(<?= $row['peserta_id'] ?>)">
                        <i class="bi bi-check2-circle"></i> Selesai
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>

              <!-- ===================================================== -->
              <!-- ================  MODAL DETAIL PESERTA =============== -->
              <!-- ===================================================== -->
              <div class="modal fade" id="detailModal<?= $row['peserta_id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                  <div class="modal-content">

                    <div class="modal-header bg-danger text-white">
                      <h5 class="modal-title fw-bold">Rincian Peserta: <?= htmlspecialchars($row['nama']); ?></h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                      <!-- Informasi Pribadi -->
                      <h6 class="fw-bold text-danger mb-3">Informasi Pribadi</h6>

                      <div class="p-3 border rounded bg-light mb-4">
                        <div class="row mb-2">
                          <div class="col-md-6"><strong>Tanggal Daftar:</strong> <?= htmlspecialchars($row['tgl_daftar']); ?></div>
                          <div class="col-md-6"><strong>Instansi:</strong> <?= htmlspecialchars($row['instansi_pendidikan']); ?></div>
                        </div>

                        <div class="row mb-2">
                          <div class="col-md-6"><strong>Nama:</strong> <?= htmlspecialchars($row['nama']); ?></div>
                          <div class="col-md-6"><strong>Jurusan:</strong> <?= htmlspecialchars($row['jurusan']); ?></div>
                        </div>

                        <div class="row mb-2">
                          <div class="col-md-6"><strong>Email:</strong> <?= htmlspecialchars($row['email']); ?></div>
                          <div class="col-md-6"><strong>Semester:</strong> <?= htmlspecialchars($row['semester']); ?></div>
                        </div>

                        <div class="row mb-2">
                          <div class="col-md-6"><strong>No HP:</strong> <?= htmlspecialchars($row['no_hp']); ?></div>
                          <div class="col-md-6"><strong>IPK / Nilai Rata-rata:</strong> <?= htmlspecialchars($row['ipk_nilai_ratarata']); ?></div>
                        </div>

                        <div class="row mb-2">
                          <div class="col-md-6"><strong>NIS / NPM:</strong> <?= htmlspecialchars($row['nis_npm']); ?></div>
                          <div class="col-md-6"><strong>Skill:</strong> <?= htmlspecialchars($row['skill']); ?></div>
                        </div>

                        <div class="row">
                          <div class="col-12"><strong>Alamat:</strong> <?= htmlspecialchars($row['alamat']); ?></div>
                        </div>
                      </div>


                      <!-- Informasi Penempatan -->
                      <h6 class="fw-bold text-danger mb-3">Informasi Penempatan</h6>

                      <div class="p-3 border rounded bg-light mb-4">
                        <div class="row mb-2">
                          <div class="col-md-6"><strong>Unit:</strong> <?= htmlspecialchars($row['nama_unit']); ?></div>
                          <div class="col-md-6"><strong>Durasi PKL:</strong> <?= htmlspecialchars($row['durasi']); ?></div>
                        </div>

                        <div class="row mb-2">
                          <div class="col-md-6"><strong>Bersedia Unit Manapun:</strong> <?= htmlspecialchars($row['bersedia_unit_manapun']); ?></div>
                          <div class="col-md-6"><strong>Periode:</strong> <?= htmlspecialchars($row['tgl_mulai']); ?> – <?= htmlspecialchars($row['tgl_selesai']); ?></div>
                        </div>

                        <div class="row">
                          <div class="col-md-6"><strong>Memiliki Laptop:</strong> <?= htmlspecialchars($row['memiliki_laptop']); ?></div>
                          <div class="col-md-6"><strong>No. Surat Permohonan:</strong> <?= htmlspecialchars($row['nomor_surat_permohonan']); ?></div>
                        </div>
                      </div>


                      <!-- Lampiran Berkas -->
                      <h6 class="fw-bold text-danger mb-3">Lampiran Berkas</h6>

                      <div class="p-3 border rounded bg-light mb-4">
                        <div class="row g-3">

                          <div class="col-md-4">
                            <strong>Foto Formal</strong><br>
                            <a href="../../uploads/Foto_daftarpkl/<?= htmlspecialchars($row['upload_foto']); ?>"
                              class="btn btn-outline-primary btn-sm mt-2 w-100" target="_blank">Lihat</a>
                          </div>

                          <div class="col-md-4">
                            <strong>Kartu Pelajar / KTM</strong><br>
                            <a href="../../uploads/Foto_Kartuidentitas/<?= htmlspecialchars($row['upload_kartu_identitas']); ?>"
                              class="btn btn-outline-primary btn-sm mt-2 w-100" target="_blank">Lihat</a>
                          </div>

                          <div class="col-md-4">
                            <strong>Surat Permohonan Magang</strong><br>
                            <a href="../../uploads/Surat_Permohonan/<?= htmlspecialchars($row['upload_surat_permohonan']); ?>"
                              class="btn btn-outline-primary btn-sm mt-2 w-100" target="_blank">Lihat</a>
                          </div>

                        </div>
                      </div>

                      <!-- Cetak ID Card -->
                      <div class="text-center mt-3">
                        <a href="id_card/generate_idcard.php?id=<?= $row['peserta_id']; ?>"
                          class="btn btn-danger" target="_blank">
                          <i class="bi bi-printer"></i> Cetak ID Card
                        </a>
                      </div>
                    </div>
                     <!-- FOOTER -->
                     <div class="modal-footer bg-light">
                      <button class="btn px-4 text-white"
                              style="background: #d50000; border-radius: 8px;"
                              data-bs-dismiss="modal">
                        Tutup
                      </button>
                    </div>

                  </div>
                </div>
              </div>

              <!-- ===================================================== -->
              <!-- ==================  MODAL LAPORAN  =================== -->
              <!-- ===================================================== -->
              <div class="modal fade" id="modalLaporan<?= $row['peserta_id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">

                    <?php
                      // Siapkan variabel file dan ekstensi lebih awal (untuk HEADER & BODY)
                      $file = !empty($row['laporan_pkl']) ? "../../uploads/laporan/" . $row['laporan_pkl'] : null;
                      $ext  = $file ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : null;
                    ?>

                    <!-- HEADER -->
                    <div class="modal-header text-white"
                        style="background: linear-gradient(90deg, #d50000, #ff5252); border-radius: 12px 12px 0 0;">
                      <h5 class="modal-title fw-bold">
                        <i class="bi bi-file-earmark-text"></i>
                        Laporan Magang — <?= htmlspecialchars($row['nama']); ?> - <?= htmlspecialchars($row['instansi_pendidikan']); ?>
                      </h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>


                    <!-- BODY -->
                    <div class="modal-body" style="background: #fafafa;">

                      <?php if (!empty($row['laporan_pkl'])): ?>

                        <!-- PREVIEW -->
                        <div class="p-4 bg-white border rounded shadow-sm mb-3"
                            style="border-left: 4px solid #d50000;">

                          <h6 class="fw-bold mb-3">Preview Laporan</h6>

                          <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])): ?>

                            <img src="<?= $file ?>" class="img-fluid rounded shadow">

                          <?php elseif ($ext === 'pdf'): ?>

                            <embed src="<?= $file ?>" type="application/pdf"
                                  style="width:100%; height:500px; border-radius:8px; border:1px solid #ccc;" />

                          <?php elseif ($ext === 'txt'): ?>

                            <pre style="background:#f5f5f5; padding:15px; border-radius:8px; max-height:400px; overflow:auto;"><?= htmlspecialchars(file_get_contents($file)); ?>
                            </pre>

                          <?php else: ?>

                            <div class="alert alert-info">
                              File laporan tidak bisa dipreview. Silakan download.
                            </div>

                          <?php endif; ?>
                        </div>

                        <!-- DOWNLOAD -->
                        <div>
                          <a href="<?= $file ?>"
                            download="laporan magang - <?= htmlspecialchars($row['nama']); ?> - <?= htmlspecialchars($row['instansi_pendidikan']); ?>.<?= $ext; ?>"
                            class="btn text-white px-4"
                            style="background:#d50000; border-radius: 8px;">
                            <i class="bi bi-download"></i> Download Laporan
                          </a>
                        </div>

                      <?php else: ?>

                        <div class="alert alert-danger mb-0">
                          Peserta belum mengupload laporan magang.
                        </div>

                      <?php endif; ?>

                    </div>


                    <!-- FOOTER -->
                    <div class="modal-footer bg-light">
                      <button class="btn px-4 text-white"
                              style="background: #d50000; border-radius: 8px;"
                              data-bs-dismiss="modal">
                        Tutup
                      </button>
                    </div>

                  </div>
                </div>
              </div>

              <!-- ===================================================== -->
              <!-- ==================== MODAL PESAN ===================== -->
              <!-- ===================================================== -->
              <!-- ===================================================== -->
              <!-- ==================== MODAL PESAN ===================== -->
              <!-- ===================================================== -->
              <div class="modal fade" id="modalPesan<?= $row['peserta_id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">

                    <!-- HEADER -->
                    <div class="modal-header text-white"
                        style="background: linear-gradient(90deg, #d50000, #ff1744);">
                      <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-chat-dots-fill"></i>
                        Pesan — <?= htmlspecialchars($row['nama']); ?>
                      </h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body" style="background: #fdfdfd;">
                      <?php if (!empty($row['pesan_admin'])): ?>
                        <div class="p-3 bg-white border rounded shadow-sm"
                            style="border-left: 5px solid #d50000; border-radius: 10px; font-size: 15px;">
                          <?= nl2br(htmlspecialchars($row['pesan_admin'])); ?>
                        </div>
                      <?php else: ?>
                        <div class="alert alert-danger mb-0 d-flex align-items-center gap-2"
                            style="background: #ffebee; border: 1px solid #ffcdd2; color: #c62828;">
                          <i class="bi bi-exclamation-circle-fill"></i>
                          Belum ada pesan dari peserta.
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer bg-light">
                      <button class="btn px-4 text-white"
                              style="background: #d50000; border-radius: 8px;"
                              data-bs-dismiss="modal">
                        Tutup
                      </button>
                    </div>

                  </div>
                </div>
              </div>

            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="12" class="text-center text-muted">Belum ada peserta Internship</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.tailwindcss.com"></script>
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
    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
        menuToggle.style.display = 'inline-block';
      });
    }
    function konfirmasiSelesai(id) {
      Swal.fire({
        title: 'Yakin?',
        text: "Apakah Anda yakin ingin menyelesaikan peserta Internship ini?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Selesaikan!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('formSelesai' + id).submit();
        }
      });
    }
    document.addEventListener("DOMContentLoaded", function () {

      document.querySelectorAll("[data-bs-target^='#modalPesan']").forEach(btn => {

          btn.addEventListener("click", function () {

              let id = this.getAttribute("data-id");

              // AJAX update status ke 'dibaca'
              fetch("update_pesan_status.php?id=" + id);
          });
      });
    });
  </script>
</body>
</html>