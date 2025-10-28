<?php
// ============================
// riwayat_peserta.php
// ============================
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// ============================
// Ambil filter dari URL
// ============================
$status_filter = $_GET['status'] ?? 'all';
$filter_unit   = $_GET['unit'] ?? 'all';
$q             = trim($_GET['q'] ?? '');

// ============================
// Ambil daftar unit untuk dropdown
// ============================
$unitResult = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");

// ============================
// Query Data Riwayat
// ============================
$sql = "
  SELECT 
    r.*, 
    u.nama_unit
  FROM riwayat_peserta_pkl r
  LEFT JOIN unit_pkl u ON r.unit_id = u.id
  WHERE 1=1
";

$params = [];
$types = "";

// ============================
// Filter berdasarkan unit
// ============================
if ($filter_unit !== 'all') {
  $sql .= " AND r.unit_id = ?";
  $params[] = $filter_unit;
  $types .= "i";
}

// ============================
// Filter berdasarkan status
// ============================
if ($status_filter === 'selesai') {
  $sql .= " AND r.status = 'selesai'";
} elseif ($status_filter === 'keluar') {
  $sql .= " AND r.status = 'keluar'";
}

// ============================
// Pencarian (nama, NIS, instansi, jurusan)
// ============================
if ($q !== '') {
  $sql .= " AND (r.nama LIKE ? OR r.nis_npm LIKE ? OR r.instansi_pendidikan LIKE ? OR r.jurusan LIKE ?)";
  $like = "%$q%";
  $params = array_merge($params, [$like, $like, $like, $like]);
  $types .= "ssss";
}

$sql .= " ORDER BY r.tgl_selesai DESC";

// ============================
// Eksekusi Query
// ============================
$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("Query gagal disiapkan: " . $conn->error);
}

if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
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
      box-shadow: 4px 0 15px rgba(0,0,0,0.15);
      transition: left 0.3s ease;
    }

    .sidebar a {
      color: #e0e0e0 !important;
      border-radius: 12px;
      padding: 12px 18px;
      display: flex;
      align-items: center;
      margin-bottom: 8px;
      text-decoration: none;
      transition: 0.3s;
    }

    .sidebar a.active,
    .sidebar a:hover {
      background-color: rgba(255,255,255,0.15);
      transform: translateX(6px);
    }

    /* Main content */
    .main-content {
      margin-left: 280px;
      min-height: 100vh;
    }

    .table-header-red th {
      background-color: var(--telkom-red);
      color: white;
      text-align: center;
      vertical-align: middle;
    }

    /* Spacing fix antara filter dan tabel */
    .filter-card {
      margin-bottom: 2rem !important;
    }

    .card.table-card {
      margin-top: 1rem !important;
    }

    @media (max-width: 768px) {
      .sidebar { left: -280px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
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
    <div class="header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
      <div>
        <h4 class="mb-0 fw-bold text-danger">Riwayat Peserta PKL</h4>
        <small class="text-muted">Filter, cari, dan lihat data peserta yang sudah selesai atau keluar</small>
      </div>
      <img src="../assets/img/logo_telkom.png" style="height:72px" alt="Telkom Logo">
    </div>

  <!-- Filter Section -->
  <div class="card shadow-sm border-0 mt-4 mx-3 filter-card">
    <div class="card-body py-4">
      <form method="GET" class="row g-3 align-items-end">
        <!-- Filter Unit -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
          <label for="unit" class="form-label fw-semibold text-secondary">Pilih Unit</label>
          <select name="unit" id="unit" class="form-select shadow-sm border-0 bg-light" onchange="this.form.submit()">
            <option value="all" <?= ($filter_unit == 'all') ? 'selected' : '' ?>>Semua Unit</option>
            <?php while ($unit = $unitResult->fetch_assoc()): ?>
              <option value="<?= $unit['id']; ?>" <?= ($filter_unit == $unit['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($unit['nama_unit']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Pencarian -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
          <label for="q" class="form-label fw-semibold text-secondary">Cari (Nama / NIS / Instansi / Jurusan)</label>
          <div class="input-group shadow-sm">
            <span class="input-group-text bg-danger text-white border-0">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>" 
                  class="form-control border-0 bg-light"
                  placeholder="Ketik kata kunci...">
          </div>
        </div>

        <!-- Filter Status -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
          <label for="status" class="form-label fw-semibold text-secondary">Status Peserta</label>
          <select name="status" id="status" class="form-select shadow-sm border-0 bg-light" onchange="this.form.submit()">
            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>Semua Status</option>
            <option value="selesai" <?= $status_filter == 'selesai' ? 'selected' : '' ?>>Selesai</option>
            <option value="keluar" <?= $status_filter == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>

        <!-- Tombol Aksi -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 d-flex gap-2">
          <button type="submit" class="btn btn-danger flex-fill shadow-sm">
            <i class="bi bi-filter-circle me-1"></i> Filter
          </button>
          <a href="riwayat_peserta.php" class="btn btn-outline-secondary flex-fill shadow-sm">
            <i class="bi bi-arrow-repeat me-1"></i> Reset
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Data Riwayat Peserta -->
  <div class="card mx-3 shadow-sm mb-4 table-card">
    <div class="card-header bg-white">
      <h5 class="fw-bold text-danger mb-0">
        <i class="bi bi-clock-history me-2"></i> Data Riwayat Peserta
      </h5>
    </div>

    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover align-middle text-center">
        <thead class="table-header-red">
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>NIS/NPM</th>
            <th>Instansi</th>
            <th>Jurusan</th>
            <th>Email</th>
            <th>No HP</th>
            <th>Unit</th>
            <th>Periode</th>
            <th>Status</th>
            <th>Aksi</th>
            <th>Cetak Surat & Sertifikat</th>
            <th>Hapus Data</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): $no = 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['nis_npm']) ?></td>
                <td><?= htmlspecialchars($row['instansi_pendidikan']) ?></td>
                <td><?= htmlspecialchars($row['jurusan']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['no_hp']) ?></td>
                <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                <td>
                  <?= $row['tgl_mulai'] ? date('d M Y', strtotime($row['tgl_mulai'])) : '-' ?> -
                  <?= $row['tgl_selesai'] ? date('d M Y', strtotime($row['tgl_selesai'])) : '-' ?>
                </td>
                <td>
                  <?php if ($row['status'] == 'nonaktif'): ?>
                    <span class="badge bg-dark text-white">Nonaktif</span>
                  <?php elseif ($row['status'] == 'selesai'): ?>
                    <span class="badge bg-success text-white">Selesai</span>
                  <?php else: ?>
                    <span class="badge bg-secondary text-white"><?= htmlspecialchars($row['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="POST" action="keluar_riwayat_peserta.php" class="form-nonaktif">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="button" class="btn btn-sm btn-danger btn-nonaktif">
                      <i class="bi bi-box-arrow-right"></i> Nonaktif
                    </button>
                  </form>
                </td>
                <td>
                  <?php
                    if (!empty($row['user_id'])) {
                        $suratQuery = 'user_id=' . intval($row['user_id']);
                        $sertQuery  = 'user_id=' . intval($row['user_id']);
                    } else {
                        $suratQuery = 'riwayat_id=' . intval($row['id']);
                        $sertQuery  = 'riwayat_id=' . intval($row['id']);
                    }
                  ?>
                  <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="../dokumen/surat_selesai.php?<?= $suratQuery ?>" class="btn btn-sm btn-danger" target="_blank" title="Cetak Surat Selesai">
                      <i class="bi bi-file-earmark-text"></i>
                    </a>
                    <a href="../dokumen/sertifikat.php?<?= $sertQuery ?>" class="btn btn-sm btn-outline-danger" target="_blank" title="Cetak Sertifikat">
                      <i class="bi bi-award"></i>
                    </a>
                  </div>
                </td>
                <td>
                  <form method="POST" action="hapus_riwayat_peserta.php" class="form-hapus">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus">
                      <i class="bi bi-trash"></i> Hapus
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="13" class="text-muted text-center">Tidak ada data riwayat peserta.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

  <!-- Script -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    // Konfirmasi keluar akun
    document.querySelectorAll('.btn-nonaktif').forEach(button => {
      button.addEventListener('click', function () {
        const form = this.closest('.form-nonaktif');
        Swal.fire({
          title: 'Yakin ingin menghapus akun peserta PKL ini?',
          text: 'Akun dan data login anak PKL akan dihapus secara permanen dari sistem!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Ya, hapus akun!',
          cancelButtonText: 'Batal',
          reverseButtons: true,
        }).then((result) => {
          if (result.isConfirmed) form.submit();
        });
      });
    });

    // Konfirmasi hapus permanen
    document.querySelectorAll('.btn-hapus').forEach(button => {
      button.addEventListener('click', function () {
        const form = this.closest('.form-hapus');
        Swal.fire({
          title: 'Hapus data riwayat peserta?',
          text: 'Data ini akan dihapus secara permanen dari sistem dan tidak bisa dikembalikan!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Ya, hapus permanen!',
          cancelButtonText: 'Batal',
          reverseButtons: true,
        }).then((result) => {
          if (result.isConfirmed) form.submit();
        });
      });
    });

    // Notifikasi sukses
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('logout_success')) {
      Swal.fire({
        icon: 'success',
        title: 'Akun peserta berhasil dihapus!',
        text: 'Status peserta telah berubah menjadi "Nonaktif".',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    }
    if (urlParams.has('hapus_success')) {
      Swal.fire({
        icon: 'success',
        title: 'Data riwayat berhasil dihapus!',
        text: 'Data peserta telah dihapus permanen dari sistem.',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    }
  });
  </script>
</body>
</html>
