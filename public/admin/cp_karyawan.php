<?php 
include "../../includes/auth.php"; 
checkRole('admin'); 
include "../../config/database.php";

// Tambah CP Karyawan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'] ?? '';
    $nama = $_POST['nama_karyawan'] ?? '';
    $posisi = $_POST['posisi'] ?? '';
    $nohp = $_POST['no_telepon'] ?? '';

    if ($nik && $nama && $posisi && $nohp) {
        $stmt = $conn->prepare("INSERT INTO cp_karyawan (nik, nama_karyawan, posisi, no_telepon) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $nik, $nama, $posisi, $nohp);
        $stmt->execute();
        $stmt->close();
        header("Location: cp_karyawan.php?success=1");
        exit;
    }
}

// Ambil data cp karyawan
$cpList = $conn->query("SELECT * FROM cp_karyawan ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>CP Karyawan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #ffffff 0%, #ffe5e5 100%);
      font-family: 'Segoe UI', sans-serif;
    }
    .page-header {
      background-color: #cc0000;
      color: #fff;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
    }
    .card-header {
      font-weight: bold;
    }
    .btn-danger {
      background-color: #cc0000;
      border: none;
    }
    .btn-danger:hover {
      background-color: #990000;
    }
    .table thead {
      background-color: #cc0000;
      color: white;
    }
    .back-btn {
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<div class="container py-4">

  <!-- Header -->
  <div class="page-header d-flex justify-content-between align-items-center">
    <h3 class="mb-0"><i class="bi bi-telephone me-2"></i> Manajemen CP Karyawan</h3>
    <a href="dashboard.php" class="btn btn-light back-btn">
      <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Dashboard
    </a>
  </div>

  <!-- Form tambah -->
  <div class="card mb-4 shadow">
    <div class="card-header bg-danger text-white">Tambah CP Karyawan</div>
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">NIK</label>
            <input type="text" name="nik" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Nama Karyawan</label>
            <input type="text" name="nama_karyawan" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Posisi</label>
            <input type="text" name="posisi" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Nomor Telepon</label>
            <input type="text" name="no_telepon" class="form-control" required>
          </div>
        </div>
        <div class="mt-3">
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-plus-circle me-1"></i> Tambah
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel data -->
  <div class="card shadow">
    <div class="card-header bg-danger text-white">Daftar CP Karyawan</div>
    <div class="card-body">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>NIK</th>
            <th>Nama</th>
            <th>Posisi</th>
            <th>No. Telepon</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $cpList->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nik']) ?></td>
            <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
            <td><?= htmlspecialchars($row['posisi']) ?></td>
            <td><?= htmlspecialchars($row['no_telepon']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

</body>
</html>
