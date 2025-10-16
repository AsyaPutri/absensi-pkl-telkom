<?php
// ============================
// riwayat_peserta.php
// ============================
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// ============================
// Ambil filter dari GET
// ============================
$unit = isset($_GET['unit']) ? $_GET['unit'] : 'all';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Ambil daftar unit untuk dropdown
$unitResult = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");

// ============================
// Query Data Riwayat
// ============================
$sql = "SELECT 
          r.*, 
          u.nama_unit
        FROM riwayat_peserta_pkl r
        LEFT JOIN unit_pkl u ON r.unit_id = u.id
        WHERE 1=1";

$params = [];
$types = "";

// Filter unit
if ($unit !== 'all' && $unit !== '') {
    $sql .= " AND r.unit_id = ?";
    $params[] = $unit;
    $types .= "i";
}

// Pencarian (nama, NIS, instansi, jurusan)
if ($q !== '') {
    $sql .= " AND (r.nama LIKE ? OR r.nis_npm LIKE ? OR r.instansi_pendidikan LIKE ? OR r.jurusan LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}

$sql .= " ORDER BY r.tgl_selesai DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query gagal disiapkan: " . $conn->error);
}
if ($types !== "") {
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
        <small class="text-muted">Filter, cari, dan lihat data peserta yang sudah selesai</small>
      </div>
      <img src="../assets/img/logo_telkom.png" style="height:72px" alt="Telkom Logo">
    </div>

    <!-- Filter -->
    <div class="card my-3 mx-3 shadow-sm">
      <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Unit</label>
            <select name="unit" class="form-select form-select-sm">
              <option value="all" <?= ($unit === 'all') ? 'selected' : '' ?>>Semua Unit</option>
              <?php
              $unitResult->data_seek(0);
              while ($u = $unitResult->fetch_assoc()):
                $sel = ($unit == $u['id']) ? 'selected' : '';
              ?>
                <option value="<?= $u['id'] ?>" <?= $sel ?>><?= htmlspecialchars($u['nama_unit']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small">Cari (nama / NIS / Instansi)</label>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="Ketik kata kunci...">
          </div>

          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-filter"></i> Terapkan</button>
            <a href="riwayat_peserta.php" class="btn btn-outline-secondary btn-sm">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="card mx-3 shadow-sm mb-4">
      <div class="card-header bg-white">
        <h5 class="fw-bold text-danger mb-0"><i class="bi bi-clock-history me-2"></i> Data Riwayat Peserta</h5>
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
              <th>Unit</th>
              <th>Periode</th>
              <th>Status</th>
              <th>Rincian</th>
              <th>Cetak Surat & Sertifikat </th>
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
                  <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                  <td>
                    <?= date('d M Y', strtotime($row['tgl_mulai'])) ?> - 
                    <?= date('d M Y', strtotime($row['tgl_selesai'])) ?>
                  </td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#detailModal" 
                            data-row='<?= json_encode($row, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>
                      Rincian
                    </button>
                  </td>
                  <td>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                      <a href="../dokumen/surat_selesai.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger">
                        <i class="bi bi-file-earmark-text"></i>
                      </a>
                      <a href="../dokumen/sertifikat.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-award"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="10" class="text-muted text-center">Tidak ada data riwayat peserta.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal Rincian -->
  <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow-lg border-0">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title fw-bold">
            <i class="bi bi-person-badge me-2"></i>Rincian Peserta PKL
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body" id="detailContent">
          <!-- Konten dinamis via JS -->
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i> Tutup
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.getElementById('detailModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const row = JSON.parse(btn.getAttribute('data-row'));

    let html = `
      <div class="row mb-2">
        <div class="col-md-6">
          <p><strong>Nama:</strong> ${row.nama ?? '-'}</p>
          <p><strong>NIS/NPM:</strong> ${row.nis_npm ?? '-'}</p>
          <p><strong>Email:</strong> ${row.email ?? '-'}</p>
          <p><strong>No HP:</strong> ${row.no_hp ?? '-'}</p>
          <p><strong>Unit:</strong> ${row.nama_unit ?? '-'}</p>
        </div>
        <div class="col-md-6">
          <p><strong>Instansi:</strong> ${row.instansi_pendidikan ?? '-'}</p>
          <p><strong>Jurusan:</strong> ${row.jurusan ?? '-'}</p>
          <p><strong>Semester:</strong> ${row.semester ?? '-'}</p>
          <p><strong>Durasi:</strong> ${row.durasi ?? '-'}</p>
        </div>
      </div>
      <hr>
      <p><strong>Periode:</strong> ${new Date(row.tgl_mulai).toLocaleDateString()} – ${new Date(row.tgl_selesai).toLocaleDateString()}</p>

      <hr class="my-3">
      <h6 class="fw-bold text-danger"><i class="bi bi-file-earmark-text me-2"></i> Dokumen</h6>
      <div class="row text-center">
        <div class="col-md-4 mb-2">
          <p class="mb-1"><strong>Foto Formal</strong></p>
          <a href="../../uploads/${row.upload_foto ?? '#'}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
        </div>
        <div class="col-md-4 mb-2">
          <p class="mb-1"><strong>Kartu Identitas</strong></p>
          <a href="../../uploads/${row.upload_kartu_identitas ?? '#'}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
        </div>
        <div class="col-md-4 mb-2">
          <p class="mb-1"><strong>Surat Permohonan PKL</strong></p>
          <a href="../../uploads/${row.upload_surat_permohonan ?? '#'}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
        </div>
      </div>
    `;
    document.getElementById('detailContent').innerHTML = html;
  });
</script>
</body>
</html>
