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
  <title>Manajemen Pengumuman - Admin InStep</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background: #f2f4f6;
      font-family: 'Segoe UI', sans-serif;
    }

    /* Header Bar */
    .header-bar {
      background: linear-gradient(135deg, #cc0000, #ff4444);
      padding: 16px 20px;
      color: white;
      border-radius: 15px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Upload Card */
    .upload-card {
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }

    .upload-header {
      background: linear-gradient(135deg, #cc0000, #990000);
      color: white;
      padding: 20px;
      text-align: center;
    }

    .btn-telkom {
      background: #cc0000;
      color: white;
      font-weight: 600;
    }

    .btn-telkom:hover {
      background: #990000;
      color: #fff;
    }

    /* Tabel */
    .table-card {
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .table thead {
      background: linear-gradient(135deg, #cc0000, #ff4444);
      color: #fff;
    }

    .table th, .table td {
      vertical-align: middle;
    }

    .table img {
      width: 90px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    .btn-action {
      width: 32px;
      height: 32px;
      padding: 0;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      border-radius: 6px;
    }

    .btn-action i {
      font-size: 16px;
    }
  </style>
</head>

<body>

<div class="container my-4">

  <!-- Header -->
  <div class="header-bar">
    <h4 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i> Manajemen Pengumuman</h4>
    <a href="../admin/dashboard.php" class="btn btn-light btn-sm">
      <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Dashboard
    </a>
  </div>

  <!-- Upload Form -->
  <div class="card upload-card mb-4">
    <div class="upload-header">
      <h5 class="mb-0"><i class="bi bi-upload me-2"></i> Upload Pengumuman Baru</h5>
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
    <div class="card-header bg-danger text-white py-3">
      <h5 class="mb-0"><i class="bi bi-card-list me-2"></i> Daftar Pengumuman</h5>
    </div>

    <div class="card-body p-0">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-4">Judul</th>
            <th class="text-center" style="width: 130px;">Gambar</th>
            <th class="text-center" style="width: 120px;">Status</th>
            <th class="text-center" style="width: 150px;">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td class="ps-4 fw-semibold"><?= htmlspecialchars($row['judul']) ?></td>

            <td class="text-center">
              <img src="../../uploads/pengumuman/<?= $row['gambar'] ?>" alt="gambar">
            </td>

            <td class="text-center">
              <?php if($row['is_active'] == 1): ?>
                <span class="badge bg-success px-3 py-2">
                  <i class="bi bi-check-circle me-1"></i> Aktif
                </span>
              <?php else: ?>
                <span class="badge bg-secondary px-3 py-2">Nonaktif</span>
              <?php endif; ?>
            </td>

            <td class="text-center">
              <a href="?aktif=<?= $row['id'] ?>" 
                 class="btn btn-success btn-sm btn-action me-1"
                 title="Aktifkan">
                <i class="bi bi-check2-circle"></i>
              </a>

              <a href="?hapus=<?= $row['id'] ?>" 
                 class="btn btn-danger btn-sm btn-action"
                 onclick="return confirm('Hapus pengumuman ini?')"
                 title="Hapus">
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
