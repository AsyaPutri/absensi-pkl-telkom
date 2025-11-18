<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// ============================
// Ambil filter 
// ============================
$status_filter = $_GET['status'] ?? 'all';
$filter_unit   = $_GET['unit'] ?? 'all';
$search        = $_GET['search'] ?? '';

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
$types  = "";

// ============================
// 🔍 Filter pencarian
// ============================
if (!empty($search)) {
  $sql .= " AND (
      r.nama LIKE ?
      OR r.nis_npm LIKE ?
      OR r.instansi_pendidikan LIKE ?
      OR r.jurusan LIKE ?
  )";
  // karena prepared statement, tambahkan parameter 4x
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $types .= "ssss";
}

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
} elseif ($status_filter === 'keluar' || $status_filter === 'nonaktif') {
  $sql .= " AND r.status = 'nonaktif'";
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Riwayat Peserta InStep - Admin</title>

  <!-- Bootstrap (untuk grid / komponen) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Tailwind CDN (utility tambahan) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    :root {
      --telkom-red: #cc0000;
      --telkom-red-dark: #990000;
    }

    /* --- dasar tampilan (mengambil style lama, sedikit diperhalus) --- */
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
      z-index: 1050;
      box-shadow: 4px 0 15px rgba(0,0,0,0.15);
      transition: transform 0.25s ease, left 0.25s ease;
      transform: translateX(0);
    }

    /* saat disembunyikan untuk mobile */
    .sidebar.hidden {
      transform: translateX(-110%);
    }

    .sidebar a {
      color: #e0e0e0 !important;
      border-radius: 12px;
      padding: 12px 18px;
      display: flex;
      align-items: center;
      margin-bottom: 8px;
      text-decoration: none;
      transition: 0.2s;
    }

    .sidebar a.active,
    .sidebar a:hover {
      background-color: rgba(255,255,255,0.12);
      transform: translateX(6px);
    }

    /* overlay saat sidebar mobile aktif */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 1040;
      opacity: 0;
      transition: opacity 0.2s ease;
      pointer-events: none;
    }
    .sidebar-overlay.active {
      display: block;
      opacity: 1;
      pointer-events: auto;
    }

    /* Main content (menggeser saat desktop) */
    .main-content {
      margin-left: 280px;
      min-height: 100vh;
      transition: margin-left 0.25s ease;
    }

    /* header */
    .header {
      background: #fff;
      border-bottom: 1px solid #eee;
      padding: 1rem 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .telkom-logo {
      height: 125px;
      width: auto;
    }

    .table-header-red th {
      background-color: var(--telkom-red);
      color: white;
      text-align: center;
      vertical-align: middle;
      white-space: nowrap;
    }

    /* spacing fixes */
    .filter-card { margin-bottom: 1.25rem !important; }
    .card.table-card { margin-top: 1rem !important; }

    /* responsive adjustments */
    @media (max-width: 992px) {
      .main-content { margin-left: 0; }
      .sidebar { transform: translateX(-110%); } /* default hidden on <= lg */
      .sidebar.show { transform: translateX(0); }
      .telkom-logo { height: 90px; }
    }

    @media (max-width: 768px) {
      .telkom-logo { height: 70px; }
      .header h4 { font-size: 1.25rem; }
      .header small { font-size: 0.9rem; }
    }

    @media (max-width: 576px) {
      .telkom-logo { height: 50px; }
      .header { align-items: flex-start; }
      .header h4 { font-size: 1.05rem; }
      .table-responsive { overflow-x: auto; }
    }

    /* make table cells wrap on small screens but keep readable */
    td, th { vertical-align: middle; }
    .nowrap-sm { white-space: nowrap; }

    /* small visual tweaks for buttons to match Tailwind/Bootstrap mixing */
    .btn-outline-danger, .btn-danger { border-radius: 8px; }
  </style>
</head>
<body class="antialiased">

  <!-- Overlay untuk mobile -->
  <div id="sidebarOverlay" class="sidebar-overlay"></div>

  <!-- SIDEBAR (tetap gunakan struktur link yang sama) -->
  <aside id="sidebar" class="sidebar lg:translate-x-0">
    <div class="text-center py-3">
      <i class="bi bi-person-circle fs-1 text-white"></i>
      <p class="fw-bold mb-0 text-white">Admin Internship | InStep</p>
      <small class="text-white-50">Telkom Witel Bekasi - Karawang</small>
    </div>
    <hr class="text-white-50" />
    <ul class="nav flex-column mt-2">
      <li><a href="dashboard.php" class="nav-link"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link"><i class="bi bi-journal-text me-2"></i> Data Daftar Internship</a></li>
      <li><a href="peserta.php" class="nav-link"><i class="bi bi-people me-2"></i> Data Peserta Internship</a></li>
      <li><a href="absensi.php" class="nav-link"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="riwayat_peserta.php" class="nav-link active"><i class="bi bi-clock-history me-2"></i> Riwayat Peserta</a></li>
      <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="main-content">

    <!-- Header (tambah tombol toggle untuk mobile) -->
    <header class="header">
      <div class="d-flex align-items-center">
        <!-- tombol toggle sidebar untuk mobile -->
        <button id="btnToggle" class="btn btn-outline-secondary d-lg-none me-2" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>

        <div>
          <h4 class="mb-0 fw-bold text-danger">Riwayat Peserta | InStep</h4>
          <small class="text-muted">Sistem Manajemen Internship</small>
        </div>
      </div>

      <img src="../assets/img/InStep.png" class="telkom-logo" alt="Telkom Logo">
    </header>

    <!-- Filter Section -->
    <div class="card shadow-sm border-0 mt-4 mx-3 filter-card">
      <div class="card-body py-4">
        <form method="GET" class="row gx-3 gy-3 align-items-end">
          <!-- Filter Unit -->
          <div class="col-12 col-sm-6 col-md-4 col-xl-3">
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
          <div class="col-12 col-sm-6 col-md-4 col-xl-3">
            <label for="q" class="form-label fw-semibold text-secondary">Cari (Nama / NIS)</label>
            <div class="input-group shadow-sm">
              <span class="input-group-text bg-danger text-white border-0"><i class="bi bi-search"></i></span>
              <input 
                type="text" 
                name="search" 
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                class="form-control border-0 bg-light" 
                placeholder="Ketik kata kunci..."
              />
            </div>
          </div>

          <!-- Filter Status -->
          <div class="col-12 col-sm-6 col-md-4 col-xl-3">
            <label for="status" class="form-label fw-semibold text-secondary">Status Peserta</label>
            <select name="status" id="status" class="form-select shadow-sm border-0 bg-light" onchange="this.form.submit()">
              <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>Semua Status</option>
              <option value="selesai" <?= $status_filter == 'selesai' ? 'selected' : '' ?>>Selesai</option>
              <option value="keluar" <?= $status_filter == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
          </div>

          <!-- Tombol Aksi -->
          <div class="col-12 col-sm-6 col-md-12 col-xl-3 d-flex gap-2">
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

      <div class="card-body table-responsive px-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle text-center mb-0">
            <thead class="table-header-red">
              <tr>
                <th class="nowrap-sm">No</th>
                <th class="nowrap-sm">Nama</th>
                <th class="nowrap-sm">NIS/NPM</th>
                <th class="nowrap-sm">Instansi</th>
                <th class="nowrap-sm">Jurusan</th>
                <th class="nowrap-sm">Email</th>
                <th class="nowrap-sm">No HP</th>
                <th class="nowrap-sm">Unit</th>
                <th class="nowrap-sm">Periode</th>
                <th class="nowrap-sm">Status</th>
                <th class="nowrap-sm">Aksi</th>
                <th class="nowrap-sm">Cetak Surat & Sertifikat</th>
                <th class="nowrap-sm">Hapus Data</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows > 0): $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['nis_npm']) ?></td>
                    <td><?= htmlspecialchars($row['instansi_pendidikan']) ?></td>
                    <td><?= htmlspecialchars($row['jurusan']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['no_hp']) ?></td>
                    <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                    <td class="nowrap-sm">
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

  </div> <!-- akhir main-content -->

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Toggle sidebar + overlay (kombinasi Bootstrap + Tailwind friendly)
    (function () {
      const btn = document.getElementById('btnToggle');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');

      function showSidebar() {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('show');
        overlay.classList.add('active');
      }
      function hideSidebar() {
        sidebar.classList.add('hidden');
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
      }

      // default for small screens: hide
      if (window.innerWidth <= 992) {
        sidebar.classList.add('hidden');
      }

      btn && btn.addEventListener('click', function (e) {
        if (sidebar.classList.contains('show')) {
          hideSidebar();
        } else {
          showSidebar();
        }
      });

      overlay && overlay.addEventListener('click', function () {
        hideSidebar();
      });

      // on resize: if large screen, ensure sidebar shown; if small, hidden
      window.addEventListener('resize', function () {
        if (window.innerWidth > 992) {
          sidebar.classList.remove('hidden');
          sidebar.classList.add('show');
          overlay.classList.remove('active');
        } else {
          sidebar.classList.add('hidden');
          sidebar.classList.remove('show');
        }
      });

      // -------------------
      // SweetAlert confirmations (tidak diubah logika)
      document.addEventListener('DOMContentLoaded', function () {
        // Konfirmasi keluar akun
        document.querySelectorAll('.btn-nonaktif').forEach(button => {
          button.addEventListener('click', function () {
            const form = this.closest('.form-nonaktif');
            Swal.fire({
              title: 'Yakin ingin menghapus akun peserta Internship ini?',
              text: 'Akun dan data login anak Internship akan dihapus secara permanen dari sistem!',
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
    })();
  </script>
</body>
</html>
