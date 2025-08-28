<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// Hitung jumlah pending dari daftar_pkl
$sql = "SELECT COUNT(*) as total FROM daftar_pkl WHERE status='pending'";
$pending = $conn->query($sql)->fetch_assoc()['total'] ?? 0;

// Hitung jumlah sedang berlangsung dari peserta_pkl
$sql = "SELECT COUNT(*) as total 
        FROM peserta_pkl 
        WHERE CURDATE() BETWEEN tgl_mulai AND tgl_selesai";
$sedang = $conn->query($sql)->fetch_assoc()['total'] ?? 0;

// Hitung jumlah selesai dari peserta_pkl
$sql = "SELECT COUNT(*) as total 
        FROM peserta_pkl 
        WHERE tgl_selesai < CURDATE()";
$selesai = $conn->query($sql)->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin</title>
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <?php include "template/sidebar.php"; ?>

    <!-- Konten -->
    <div class="flex-grow-1 p-4">
      <h1 class="mb-4">Beranda Admin</h1>
      <p>Halo, <?= htmlspecialchars($_SESSION['nama']) ?> 👋</p>

      <div class="container-fluid mt-4">
        <div class="row g-4">
          
          <!-- Sedang Berlangsung -->
          <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-white bg-primary shadow h-100 rounded-3">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="card-title">Sedang Berlangsung</h5>
                <p class="display-5 fw-bold mb-0"><?= $sedang ?></p>
              </div>
            </div>
          </div>

          <!-- Pending -->
          <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-dark bg-warning shadow h-100 rounded-3">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="card-title">Pending</h5>
                <p class="display-5 fw-bold mb-0"><?= $pending ?></p>
              </div>
            </div>
          </div>

          <!-- Selesai -->
          <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-white bg-success shadow h-100 rounded-3">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="card-title">Selesai</h5>
                <p class="display-5 fw-bold mb-0"><?= $selesai ?></p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
