<?php
include "../../includes/auth.php";
include "../../config/database.php"; // koneksi pakai $conn

// --- fallback keamanan: pastikan $conn ada
if (!isset($conn)) {
    die("<h3 style='color:red; text-align:center;'>Koneksi database tidak ditemukan. Pastikan config/database.php mengatur \$conn.</h3>");
}

// Cek apakah kolom created_at ada di tabel peserta_pkl
$col_check_q = "SHOW COLUMNS FROM peserta_pkl LIKE 'created_at'";
$col_check_res = mysqli_query($conn, $col_check_q);
$order_by = ($col_check_res && mysqli_num_rows($col_check_res) > 0) ? "peserta_pkl.created_at DESC" : "peserta_pkl.id DESC";

// 🔗 JOIN peserta_pkl dengan unit_pkl untuk ambil nama unit
$query = "
  SELECT 
    peserta_pkl.*, 
    unit_pkl.nama_unit 
  FROM peserta_pkl
  LEFT JOIN unit_pkl ON peserta_pkl.unit_id = unit_pkl.id
  ORDER BY $order_by
";
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
      margin: 0;
      padding: 0;
    }
    /* Header */
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
    .header img { height: 60px; width: auto; }
    .header .title h4 { color: var(--telkom-red); font-weight: 700; margin-bottom: 4px; }
    .header .title small { color: #6c757d; }
    .back-btn {
      background: var(--telkom-red);
      color: #fff;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 500;
      transition: all .3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .back-btn:hover { background: var(--telkom-dark); color: #fff; }

    /* Container */
    .container-fluid { padding: 2rem 2rem; max-width: 1200px; margin: auto; }

    /* Card */
    .card { border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .card-header { background: #fff; border-bottom: 3px solid var(--telkom-red); padding: 1rem 1.5rem; }
    .card-header h5 { color: var(--telkom-red); font-weight: 700; margin: 0; }

    /* Table */
    .table { margin: 0; border-collapse: collapse; text-align: center; vertical-align: middle; }
    .table thead th { background: var(--telkom-red); color: #fff; padding: 12px; font-size: 15px; vertical-align: middle; }
    .table tbody td { padding: 10px 12px; vertical-align: middle; }
    .table-hover tbody tr:hover { background-color: #ffecec; }

    /* Responsif */
    @media (max-width: 768px) {
      .header { flex-direction: column; align-items: flex-start; gap: 10px; }
      .container-fluid { padding: 1rem; }
      .table thead { display: none; }
      .table tbody tr { display: block; margin-bottom: 1rem; background: #fff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); padding: 10px; }
      .table tbody td { display: flex; justify-content: space-between; text-align: left; padding: 8px 5px; border: none; }
      .table tbody td::before { content: attr(data-label); font-weight: 600; color: var(--telkom-red); }
    }
  </style>
</head>
<body>
  <!-- Header -->
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

  <!-- Konten -->
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <h5><i class="bi bi-people-fill me-2"></i> Data Peserta Internship</h5>
      </div>
      <div class="card-body table-responsive">
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
                $nama = $row['nama'] ?? ($row['nama_lengkap'] ?? '-');
                $instansi = $row['instansi'] ?? ($row['instansi_pendidikan'] ?? '-');
                $jurusan = $row['jurusan'] ?? '-';
                $nohp = $row['no_hp'] ?? ($row['no_telepon'] ?? '-');
                $status = $row['status'] ?? '-';
                $unit = $row['nama_unit'] ?? '-';
            ?>
              <tr>
                <td data-label="No"><?= $no++; ?></td>
                <td data-label="Nama"><?= htmlspecialchars($nama); ?></td>
                <td data-label="Instansi"><?= htmlspecialchars($instansi); ?></td>
                <td data-label="Jurusan"><?= htmlspecialchars($jurusan); ?></td>
                <td data-label="No HP"><?= htmlspecialchars($nohp); ?></td>
                <td data-label="Unit"><?= htmlspecialchars($unit); ?></td>
                <td data-label="Status">
                  <span class="badge <?= ($status==='berlangsung' ? 'bg-success' : ($status==='pending' ? 'bg-warning text-dark' : 'bg-secondary text-white')) ?>">
                    <?= htmlspecialchars(ucfirst($status)); ?>
                  </span>
                </td>
                <td data-label="Aksi">
                  <a href="rincian_peserta.php?id=<?= urlencode($row['id']); ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-eye"></i> Rincian
                  </a>
                </td>
              </tr>
            <?php
              endwhile;
            else:
            ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-3">Belum ada data peserta Internship.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
