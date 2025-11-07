<?php
include "../../includes/auth.php";
include "../../config/database.php"; // koneksi pakai $conn

if (!isset($conn)) {
  die("<h3 style='color:red; text-align:center;'>Koneksi database tidak ditemukan.</h3>");
}

// Ambil semua unit untuk dropdown filter
$unit_query = mysqli_query($conn, "SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");
$units = [];
while ($u = mysqli_fetch_assoc($unit_query)) {
  $units[] = $u;
}

// Ambil filter unit dari input
$filter_unit = $_GET['unit'] ?? '';

// Tentukan kolom urutan (created_at jika ada)
$col_check_q = "SHOW COLUMNS FROM peserta_pkl LIKE 'created_at'";
$col_check_res = mysqli_query($conn, $col_check_q);
$order_by = ($col_check_res && mysqli_num_rows($col_check_res) > 0) ? "peserta_pkl.created_at DESC" : "peserta_pkl.id DESC";

// Query utama
$query = "
  SELECT 
    peserta_pkl.*, 
    unit_pkl.nama_unit 
  FROM peserta_pkl
  LEFT JOIN unit_pkl ON peserta_pkl.unit_id = unit_pkl.id
  WHERE 1
";

// Filter unit jika dipilih
if ($filter_unit !== '') {
  $filter_safe = mysqli_real_escape_string($conn, $filter_unit);
  $query .= " AND peserta_pkl.unit_id = '$filter_safe'";
}

$query .= " ORDER BY $order_by";
$result = mysqli_query($conn, $query);

if (!$result) {
  die("<h3 style='color:red; text-align:center;'>Query gagal: " . mysqli_error($conn) . "</h3>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Data Peserta Internship - Mentor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --telkom-red: #cc0000;
      --telkom-dark: #990000;
      --light-bg: #f8f9fc;
    }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: var(--light-bg);
      min-height: 100vh;
    }
    .header {
      background: #fff;
      border-bottom: 2px solid var(--telkom-red);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .header img { height: 60px; }
    .header .title h4 { color: var(--telkom-red); font-weight: 700; margin-bottom: 4px; }
    .header .title small { color: #6c757d; }
    .back-btn {
      background: var(--telkom-red);
      color: #fff;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 500;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .back-btn:hover { background: var(--telkom-dark); color: #fff; }
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .card-header { background: #fff; border-bottom: 3px solid var(--telkom-red); padding: 1rem 1.5rem; }
    .card-header h5 { color: var(--telkom-red); font-weight: 700; margin: 0; }
    .table thead th { background: var(--telkom-red); color: #fff; }
    .table-hover tbody tr:hover { background-color: #ffecec; }
    .modal-header { background-color: var(--telkom-red); color: white; }
    .filter-section {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 1rem;
      align-items: center;
    }
    .filter-section select {
      max-width: 250px;
    }
  </style>
</head>
<body>
<div class="header">
  <div class="d-flex align-items-center">
    <img src="../assets/img/logo_telkom.png" alt="Telkom Logo">
    <div class="title ms-3">
      <h4>Data Peserta Internship</h4>
      <small>Sistem Monitoring Internship | Telkom Witel Bekasi - Karawang</small>
    </div>
  </div>
  <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
</div>

<div class="container-fluid mt-4">
  <div class="card">
    <div class="card-header">
      <h5><i class="bi bi-people-fill me-2"></i> Data Peserta Internship</h5>
    </div>
    <div class="card-body">
      <!-- 🔍 Filter Unit -->
      <form method="GET" class="filter-section" id="filterForm">
        <select name="unit" class="form-select" id="unitSelect">
          <option value="">-- Semua Unit --</option>
          <?php foreach ($units as $u): ?>
            <option value="<?= $u['id']; ?>" <?= ($filter_unit == $u['id']) ? 'selected' : ''; ?>>
              <?= htmlspecialchars($u['nama_unit']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <a href="peserta_pkl.php" class="btn btn-secondary"><i class="bi bi-arrow-repeat"></i> Reset</a>
      </form>

      <!-- 🔹 Tabel Data -->
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>Instansi</th>
              <th>Jurusan</th>
              <th>No HP</th>
              <th>Unit</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($result) > 0):
              while ($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= htmlspecialchars($row['nama']); ?></td>
              <td><?= htmlspecialchars($row['instansi_pendidikan']); ?></td>
              <td><?= htmlspecialchars($row['jurusan']); ?></td>
              <td><?= htmlspecialchars($row['no_hp']); ?></td>
              <td><?= htmlspecialchars($row['nama_unit']); ?></td>
              <td><span class="badge bg-success"><?= ucfirst($row['status']); ?></span></td>
              <td>
                <button class="btn btn-primary btn-sm btn-rincian" data-email="<?= htmlspecialchars($row['email']); ?>">
                  <i class="bi bi-eye"></i> Detail
                </button>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-3">Tidak ada data ditemukan.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="rincianModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Peserta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="rincian-body">
        <div class="text-center py-5 text-muted">Memuat data...</div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// 🔁 Auto submit ketika dropdown unit diubah
$('#unitSelect').on('change', function() {
  $('#filterForm').submit();
});

// 🔍 Load detail peserta
$(document).on('click', '.btn-rincian', function() {
  const email = $(this).data('email');
  $('#rincianModal').modal('show');
  $('#rincian-body').html('<div class="text-center py-5 text-muted">Memuat data...</div>');
  
  $.get('get_rincian_peserta.php', { email: email }, function(data) {
    $('#rincian-body').html(data);
  }).fail(() => {
    $('#rincian-body').html('<div class="text-danger text-center py-4">Gagal memuat data.</div>');
  });
});
</script>
</body>
</html>
