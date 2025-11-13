<?php
session_start();
require '../../config/database.php';

// cek role admin
if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

// pesan notifikasi
$message = "";

// proses upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gambar'])) {
    $judul = $_POST['judul'] ?? "Pengumuman Baru";

    $uploadDir = "../../uploads/pengumuman/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = time() . "_" . basename($_FILES["gambar"]["name"]);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetPath)) {
        $stmt = $conn->prepare("INSERT INTO pengumuman (judul, gambar) VALUES (?, ?)");
        $stmt->bind_param("ss", $judul, $fileName);
        $stmt->execute();
        $message = "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i> Pengumuman berhasil diupload!</div>";
    } else {
        $message = "<div class='alert alert-danger'><i class='bi bi-x-circle me-2'></i> Gagal upload file.</div>";
    }
}

// hapus pengumuman
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM pengumuman WHERE id = $id");
    header("Location: admin_upload.php");
    exit;
}

// set pengumuman aktif
if (isset($_GET['aktif'])) {
    $id = intval($_GET['aktif']);
    $conn->query("UPDATE pengumuman SET is_active = 0");
    $conn->query("UPDATE pengumuman SET is_active = 1 WHERE id = $id");
    header("Location: admin_upload.php");
    exit;
}

// ambil daftar pengumuman
$result = $conn->query("SELECT * FROM pengumuman ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Pengumuman - Admin InStep </title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
    .header-bar {
      background: linear-gradient(135deg, #cc0000, #ff4444);
      padding: 15px 20px;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .upload-card, .table-card {
      border-radius: 15px;
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }
    .upload-header {
      background: linear-gradient(135deg, #cc0000, #990000);
      color: white;
      padding: 20px;
      text-align: center;
      border-radius: 15px 15px 0 0;
    }
    .btn-telkom { background: #cc0000; color: white; font-weight: bold; }
    .btn-telkom:hover { background: #990000; color: #fff; }
    table img { width: 80px; border-radius: 8px; }
    .table thead { background: #cc0000; color: white; }
    .badge-success { background-color: #28a745 !important; }
    .badge-secondary { background-color: #6c757d !important; }
  </style>
</head>
<body>

<div class="container my-4">
  <!-- Header & Tombol Kembali -->
  <div class="header-bar">
    <h4 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i> Manajemen Pengumuman</h4>
    <a href="../admin/dashboard.php" class="btn btn-light btn-sm">
      <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Dashboard
    </a>
  </div>

  <!-- Upload Form -->
  <div class="card upload-card mb-4">
    <div class="upload-header">
      <h5><i class="bi bi-upload me-2"></i> Upload Pengumuman Baru</h5>
    </div>
    <div class="card-body p-4">
      <?= $message ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Judul Pengumuman</label>
          <input type="text" name="judul" class="form-control" placeholder="Masukkan judul pengumuman" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Pilih Gambar</label>
          <input type="file" name="gambar" class="form-control" accept="image/*" required>
          <small class="text-muted">Format: JPG, JPEG, PNG</small>
        </div>
        <button type="submit" class="btn btn-telkom w-100">
          <i class="bi bi-cloud-upload me-2"></i> Upload Sekarang
        </button>
      </form>
    </div>
  </div>

  <!-- Daftar Pengumuman -->
  <div class="card table-card">
    <div class="card-header bg-danger text-white">
      <h5 class="mb-0"><i class="bi bi-card-list me-2"></i> Daftar Pengumuman</h5>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-striped mb-0">
        <thead>
          <tr>
            <th style="width: 50px;">ID</th>
            <th>Judul</th>
            <th style="width: 120px;">Gambar</th>
            <th style="width: 120px;">Status</th>
            <th style="width: 150px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['judul']) ?></td>
            <td><img src="../../uploads/pengumuman/<?= $row['gambar'] ?>" alt=""></td>
            <td>
              <?php if($row['is_active'] == 1): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Aktif</span>
              <?php else: ?>
                <span class="badge bg-secondary">Nonaktif</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="?aktif=<?= $row['id'] ?>" class="btn btn-sm btn-success me-1" title="Aktifkan">
                <i class="bi bi-check2-circle"></i>
              </a>
              <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" 
                 onclick="return confirm('Hapus pengumuman ini?')" title="Hapus">
                <i class="bi bi-trash"></i>
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
