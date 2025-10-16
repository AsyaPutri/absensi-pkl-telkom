<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Surat</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .card {
      border-radius: 20px;
      overflow: hidden;
    }
    .card-header {
      background: linear-gradient(90deg, #c1121f, #dc3545);
      font-weight: 600;
      font-size: 1.2rem;
      letter-spacing: 0.5px;
    }
    .list-group-item {
      border: none;
      border-radius: 12px;
      margin-bottom: 10px;
      padding: 16px;
      font-size: 1rem;
      transition: all 0.2s ease-in-out;
    }
    .list-group-item:hover {
      background-color: #f1f1f1;
      transform: scale(1.02);
    }
    .icon-box {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 1.3rem;
    }
    .icon-red { background: #fde2e2; color: #dc3545; }
    .icon-green { background: #e0f7e9; color: #198754; }
    .icon-blue { background: #e0f0ff; color: #0d6efd; }
    .icon-purple { background: #f3e8ff; color: #6f42c1; }
    .btn-back {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: #dc3545;
      color: #fff;
      font-weight: 500;
      border-radius: 12px;
      padding: 10px 20px;
      text-decoration: none;
      transition: all 0.2s ease-in-out;
    }
    .btn-back:hover {
      background: #b02a37;
      transform: translateY(-2px);
      color: #fff;
    }
  </style>
</head>
<body>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
      <div class="card shadow-lg">
        <div class="card-header text-white text-center py-3">
          <i class="bi bi-file-earmark-text me-2"></i> Cetak Surat Resmi
        </div>
        <div class="card-body p-4">
          <p class="text-center text-muted mb-4" style="font-size: 16px; line-height: 1.6;">
  ✨ <strong>Silakan pilih jenis surat yang ingin dicetak.</strong><br>
  Surat ini merupakan <em>dokumen resmi</em> dan <strong>tidak untuk disalahgunakan</strong>.<br>
  🗂️ Jangan lupa segera <span class="fw-semibold text-dark">menyimpan</span> setelah dicetak!<br>
  ⚠️ <em>Apabila lupa menyimpan, hal tersebut bukan menjadi tanggung jawab kami.</em>
</p>


          <div class="list-group mb-4">
            <!-- Surat Balasan -->
            <a href="../dokumen/surat_balasan.php" target="_blank" class="list-group-item d-flex align-items-center shadow-sm">
              <div class="icon-box icon-red">
                <i class="bi bi-envelope-open"></i>
              </div>
              <div>
                <div class="fw-semibold">Surat Balasan</div>
                <small class="text-muted">Cetak surat balasan permohonan PKL</small>
              </div>
            </a>

            <!-- Surat Konfirmasi -->
            <a href="../dokumen/surat_konfirmasi.php" target="_blank" class="list-group-item d-flex align-items-center shadow-sm">
              <div class="icon-box icon-green">
                <i class="bi bi-check2-square"></i>
              </div>
              <div>
                <div class="fw-semibold">Surat Konfirmasi</div>
                <small class="text-muted">Cetak surat konfirmasi penerimaan PKL</small>
              </div>
            </a>

            <!-- Surat Selesai -->
            <a href="../dokumen/surat_selesai.php" target="_blank" class="list-group-item d-flex align-items-center shadow-sm">
              <div class="icon-box icon-blue">
                <i class="bi bi-flag"></i>
              </div>
              <div>
                <div class="fw-semibold">Surat Selesai</div>
                <small class="text-muted">Cetak surat selesai kegiatan PKL</small>
              </div>
            </a>

            <!-- Sertifikat -->
            <a href="../dokumen/sertifikat.php" target="_blank" class="list-group-item d-flex align-items-center shadow-sm">
              <div class="icon-box icon-purple">
                <i class="bi bi-award"></i>
              </div>
              <div>
                <div class="fw-semibold">Sertifikat</div>
                <small class="text-muted">Cetak sertifikat penyelesaian PKL</small>
              </div>
            </a>
          </div>

          <!-- Kembali  -->
          <div class="text-center">
            <a href="dashboard.php" class="btn-back">
              <i class="bi bi-arrow-left-circle"></i> Kembali ke Dashboard
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

</body> 
</html>
