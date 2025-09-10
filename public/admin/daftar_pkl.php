<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

$upload_dir = "../../uploads/";
if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

function safe($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

function uploadFileUnique($fileKey, $upload_dir){
    if (!isset($_FILES[$fileKey]) || !$_FILES[$fileKey]['name']) return '';
    $name = basename($_FILES[$fileKey]['name']);
    $name = preg_replace('/[^A-Za-z0-9._-]/','_',$name);
    $newname = time() . "_" . $name;
    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $newname)) return $newname;
    return '';
}

// ================== INSERT DATA ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'insert') {
    $nama        = $_POST['nama'] ?? '';
    $email       = $_POST['email'] ?? '';
    $nis_npm     = $_POST['nis_npm'] ?? '';
    $instansi    = $_POST['instansi_pendidikan'] ?? '';
    $jurusan     = $_POST['jurusan'] ?? '';
    $skill       = $_POST['skill'] ?? '';
    $durasi      = $_POST['durasi'] ?? '';
    $unit        = $_POST['unit'] ?? '';
    $no_hp       = $_POST['no_hp'] ?? '';
    $alamat      = $_POST['alamat'] ?? '';
    $tgl_mulai   = $_POST['tgl_mulai'] ?? '';
    $tgl_selesai = $_POST['tgl_selesai'] ?? '';
    $status      = 'pending';

    $foto = uploadFileUnique('upload_foto', $upload_dir);
    $ktm  = uploadFileUnique('upload_kartu_identitas', $upload_dir);
    $surat= uploadFileUnique('upload_surat_permohonan', $upload_dir);

    $stmt = $conn->prepare("INSERT INTO daftar_pkl
      (nama,email,nis_npm,instansi_pendidikan,jurusan,skill,durasi,unit,no_hp,alamat,tgl_mulai,tgl_selesai,upload_surat_permohonan,upload_foto,upload_kartu_identitas,status)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if ($stmt){
      $stmt->bind_param("ssssssssssssssss",
          $nama,$email,$nis_npm,$instansi,$jurusan,$skill,$durasi,$unit,
          $no_hp,$alamat,$tgl_mulai,$tgl_selesai,
          $surat,$foto,$ktm,$status
      );
      if ($stmt->execute()) {
          $_SESSION['success'] = "✅ Pendaftar PKL berhasil disimpan!";
      } else {
          $_SESSION['error'] = "❌ Error: " . $stmt->error;
      }
      $stmt->close();
    } else {
      $_SESSION['error'] = "❌ Error prepare: " . $conn->error;
    }
    header("Location: daftar_pkl.php");
    exit();
}

// ================== UPDATE STATUS ==================
if (isset($_GET['id']) && isset($_GET['status'])) {
  $id     = (int) $_GET['id'];
  $status = $_GET['status'];

  // validasi status
  $allowed = ['diterima', 'pending', 'ditolak'];
  if (!in_array($status, $allowed)) {
      $_SESSION['error'] = "❌ Status tidak valid!";
      header("Location: daftar_pkl.php");
      exit;
  }

  // update status di daftar_pkl
  $upd = $conn->prepare("UPDATE daftar_pkl SET status=? WHERE id=?");
  if (!$upd) {
      $_SESSION['error'] = "DB prepare error (update daftar): " . $conn->error;
      header("Location: daftar_pkl.php");
      exit;
  }
  $upd->bind_param("si", $status, $id);
  if (!$upd->execute()) {
      $_SESSION['error'] = "DB execute error (update daftar): " . $upd->error;
      $upd->close();
      header("Location: daftar_pkl.php");
      exit;
  }
  $upd->close();

  // ================== JIKA DITERIMA ==================
  if ($status === 'diterima') {
      // ambil data peserta dari daftar_pkl
      $q = $conn->prepare("SELECT * FROM daftar_pkl WHERE id=?");
      if (!$q) {
          $_SESSION['error'] = "DB prepare error (select daftar): " . $conn->error;
          header("Location: daftar_pkl.php");
          exit;
      }
      $q->bind_param("i", $id);
      $q->execute();
      $res = $q->get_result();
      $d   = $res ? $res->fetch_assoc() : null;
      $q->close();

      if ($d) {
          // 1. cek apakah email sudah ada di users
          $cekUser = $conn->prepare("SELECT id FROM users WHERE email=?");
          $cekUser->bind_param("s", $d['email']);
          $cekUser->execute();
          $rUser   = $cekUser->get_result();
          $userRow = $rUser ? $rUser->fetch_assoc() : null;
          $cekUser->close();

          if ($userRow) {
              $user_id = $userRow['id']; // pakai user lama
          } else {
              // buat akun user baru
              $passHash = password_hash($d['nis_npm'], PASSWORD_DEFAULT);
              $role     = "magang";

              $insUser = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
              if (!$insUser) {
                  $_SESSION['error'] = "DB prepare error (insert users): " . $conn->error;
                  header("Location: daftar_pkl.php");
                  exit;
              }
              $insUser->bind_param("ssss", $d['nama'], $d['email'], $passHash, $role);
              if (!$insUser->execute()) {
                  $_SESSION['error'] = "DB execute error (insert users): " . $insUser->error;
                  $insUser->close();
                  header("Location: daftar_pkl.php");
                  exit;
              }
              $user_id = $insUser->insert_id;
              $insUser->close();
          }

          // 2. cek apakah sudah ada di peserta_pkl
          $cekPeserta = $conn->prepare("SELECT id FROM peserta_pkl WHERE email=?");
          $cekPeserta->bind_param("s", $d['email']);
          $cekPeserta->execute();
          $rPeserta = $cekPeserta->get_result();
          $already  = $rPeserta && $rPeserta->num_rows > 0;
          $cekPeserta->close();

          // 3. kalau belum ada → insert peserta baru
          if (!$already) {
              $statusPeserta = "berlangsung";

              $insPeserta = $conn->prepare("INSERT INTO peserta_pkl 
                  (user_id, nama, email, instansi_pendidikan, jurusan, nis_npm, unit, no_hp, 
                   tgl_mulai, tgl_selesai, status) 
                  VALUES (?,?,?,?,?,?,?,?,?,?,?)");
              if (!$insPeserta) {
                  $_SESSION['error'] = "DB prepare error (insert peserta): " . $conn->error;
                  header("Location: daftar_pkl.php");
                  exit;
              }
              $insPeserta->bind_param(
                  "issssssssss",
                  $user_id,
                  $d['nama'],
                  $d['email'],
                  $d['instansi_pendidikan'],
                  $d['jurusan'],
                  $d['nis_npm'],
                  $d['unit'],
                  $d['no_hp'],
                  $d['tgl_mulai'],
                  $d['tgl_selesai'],
                  $statusPeserta
              );

              if (!$insPeserta->execute()) {
                  $_SESSION['error'] = "DB execute error (insert peserta): " . $insPeserta->error;
                  $insPeserta->close();
                  header("Location: daftar_pkl.php");
                  exit;
              }
              $insPeserta->close();
          }
      }

      $_SESSION['success'] = "✅ Peserta diterima, akun dibuat, dan dipindahkan ke Data Peserta (status: sedang berlangsung).";
  } else {
      // kalau pending / ditolak
      $_SESSION['success'] = "✅ Status berhasil diubah menjadi $status";
  }

  header("Location: daftar_pkl.php");
  exit;
}

// ================== UPDATE DATA ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id = (int)($_POST['id'] ?? 0);

    $oldFoto = $oldKtm = $oldSurat = '';
    $q = $conn->prepare("SELECT upload_foto, upload_kartu_identitas, upload_surat_permohonan FROM daftar_pkl WHERE id=?");
    $q->bind_param("i",$id);
    $q->execute();
    $r = $q->get_result();
    if ($row=$r->fetch_assoc()){ 
        $oldFoto=$row['upload_foto']; 
        $oldKtm=$row['upload_kartu_identitas']; 
        $oldSurat=$row['upload_surat_permohonan']; 
    } 
    $q->close();

    $nama  = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $nis   = $_POST['nis_npm'] ?? '';
    $inst  = $_POST['instansi_pendidikan'] ?? '';
    $jur   = $_POST['jurusan'] ?? '';
    $skill = $_POST['skill'] ?? '';
    $dur   = $_POST['durasi'] ?? '';
    $unit  = $_POST['unit'] ?? '';
    $hp    = $_POST['no_hp'] ?? '';
    $alamat= $_POST['alamat'] ?? '';
    $tgl_mulai = $_POST['tgl_mulai'] ?? '';
    $tgl_selesai = $_POST['tgl_selesai'] ?? '';

    $fotoNew = uploadFileUnique('upload_foto', $upload_dir);
    $ktmNew  = uploadFileUnique('upload_kartu_identitas', $upload_dir);
    $suratNew= uploadFileUnique('upload_surat_permohonan', $upload_dir);

    $fotoDB = $fotoNew ?: $oldFoto;
    $ktmDB  = $ktmNew ?: $oldKtm;
    $suratDB= $suratNew ?: $oldSurat;

    if ($fotoNew && $oldFoto && file_exists($upload_dir . $oldFoto)) @unlink($upload_dir . $oldFoto);
    if ($ktmNew && $oldKtm && file_exists($upload_dir . $oldKtm)) @unlink($upload_dir . $oldKtm);
    if ($suratNew && $oldSurat && file_exists($upload_dir . $oldSurat)) @unlink($upload_dir . $oldSurat);

    $stmt = $conn->prepare("UPDATE daftar_pkl SET
        nama=?, email=?, nis_npm=?, instansi_pendidikan=?, jurusan=?, skill=?, durasi=?, unit=?, no_hp=?, alamat=?, tgl_mulai=?, tgl_selesai=?, upload_foto=?, upload_kartu_identitas=?, upload_surat_permohonan=?
        WHERE id=?");
    if ($stmt){
        $stmt->bind_param("sssssssssssssssi",
            $nama,$email,$nis,$inst,$jur,$skill,$dur,$unit,$hp,$alamat,$tgl_mulai,$tgl_selesai,$fotoDB,$ktmDB,$suratDB,$id
        );
        if ($stmt->execute()) $_SESSION['success'] = "Data pendaftar berhasil diperbarui.";
        else $_SESSION['error'] = "❌ Gagal update: " . $stmt->error;
        $stmt->close();
    } else {
        $_SESSION['error'] = "❌ Error prepare: " . $conn->error;
    }
    header("Location: daftar_pkl.php");
    exit();
}

// ================== DELETE ==================
if (isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $q = $conn->prepare("SELECT upload_foto, upload_kartu_identitas, upload_surat_permohonan FROM daftar_pkl WHERE id=?");
    $q->bind_param("i",$id); $q->execute(); $res=$q->get_result();
    if ($rw=$res->fetch_assoc()){
        if ($rw['upload_foto'] && file_exists($upload_dir . $rw['upload_foto'])) @unlink($upload_dir . $rw['upload_foto']);
        if ($rw['upload_kartu_identitas'] && file_exists($upload_dir . $rw['upload_kartu_identitas'])) @unlink($upload_dir . $rw['upload_kartu_identitas']);
        if ($rw['upload_surat_permohonan'] && file_exists($upload_dir . $rw['upload_surat_permohonan'])) @unlink($upload_dir . $rw['upload_surat_permohonan']);
    }
    $q->close();

    $stmt = $conn->prepare("DELETE FROM daftar_pkl WHERE id=?");
    if ($stmt){
        $stmt->bind_param("i",$id);
        if ($stmt->execute()) $_SESSION['success'] = "Data pendaftar berhasil dihapus.";
        else $_SESSION['error'] = "❌ Gagal hapus: " . $stmt->error;
        $stmt->close();
    } else $_SESSION['error'] = "❌ Error prepare: " . $conn->error;

    header("Location: daftar_pkl.php");
    exit();
}

// ================== Ambil data ==================
$stmtList = $conn->prepare("SELECT * FROM daftar_pkl ORDER BY created_at DESC");
$stmtList->execute();
$result = $stmtList->get_result();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar PKL - Admin</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{ --telkom-red:#cc0000; --telkom-dark:#990000; }
    body{ font-family:'Segoe UI',sans-serif; background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%); min-height:100vh; }
    .sidebar{ width:260px; min-height:100vh; background: linear-gradient(135deg,var(--telkom-red),var(--telkom-dark)); color:#fff; padding:1rem; position:fixed; top:0; left:0; z-index:1000; transition:left .3s;}
    .sidebar .nav-link{ color:#eee; border-radius:12px; padding:12px 16px; margin-bottom:6px; display:flex; align-items:center;}
    .sidebar .nav-link.active, .sidebar .nav-link:hover{ background:rgba(255,255,255,0.12); color:#fff !important; transform:translateX(5px); }
    .sidebar-overlay{ display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:900; }

    .main-content{ margin-left:260px; transition:margin-left .3s; min-height:100vh; }
    .header{ background:#fff; border-bottom:1px solid #eee; padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; }
    .telkom-logo{ height:80px; }

    .card{ border:none; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
    .table thead th{ background:var(--telkom-red); color:#fff; text-align:center; }
    .badge-pending{ background:#ffc107; color:#000; }
    .badge-berlangsung{ background:#28a745; }
    .badge-batal{ background:#dc3545; }

    @media(max-width:768px){
      .sidebar{ left:-260px; }
      .sidebar.active{ left:0; }
      .main-content{ margin-left:0; }
      .telkom-logo{ height:70px; }
    }
  </style>
</head>
<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="sidebar" id="sidebarMenu">
    <div class="text-center py-3">
      <i class="bi bi-person-circle fs-1 text-white"></i>
      <p class="fw-bold mb-0 text-white">Admin PKL</p>
      <small class="text-white-50">Telkom Witel Bekasi</small>
    </div>
    <hr class="text-white-50">
    <ul class="nav flex-column">
      <li><a href="dashboard.php" class="nav-link <?= ($current_page=='dashboard.php')?'active':'' ?>"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link <?= ($current_page=='daftar_pkl.php')?'active':'' ?>"><i class="bi bi-journal-text me-2"></i> Daftar PKL</a></li>
      <li><a href="peserta.php" class="nav-link <?= ($current_page=='peserta.php')?'active':'' ?>"><i class="bi bi-people me-2"></i> Data Peserta</a></li>
      <li><a href="absensi.php" class="nav-link <?= ($current_page=='absensi.php')?'active':'' ?>"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </div>

  <div class="main-content">
    <div class="header">
      <div class="d-flex align-items-center">
        <button class="btn btn-outline-secondary d-md-none me-2" id="menuToggle"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="mb-0 fw-bold text-danger">Data Daftar PKL</h4>
          <small class="text-muted">Sistem Manajemen Praktik kerja Lapangan</small>
        </div>
      </div>
      <img src="../assets/img/logo_telkom.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <div class="container-fluid p-4">
      <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= safe($_SESSION['success']); unset($_SESSION['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= safe($_SESSION['error']); unset($_SESSION['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-person-plus"></i> Tambah Pendaftar</button>
      </div>

     
      <div class="card shadow-sm">
        <div class="card-header bg-white">
        <h5 class="fw-bold text-danger mb-0"><i class="bi bi-list-check me-2"></i> Daftar PKL</h5>
      </div>
      <div class="card">
        <div class="card-body table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead>
              <tr>
                <th style="width:60px">No</th>
                <th>Nama</th>
                <th>Instansi</th>
                <th>Jurusan</th>
                 <th>No HP</th>
                <th>Status</th>
                <th>Aksi</th>
                <th>Rincian</th>
                <th>Edit Data</th>
              </tr>
            </thead>
            <tbody>
              <?php if($result->num_rows>0){ $no=1; while($row=$result->fetch_assoc()): $id=(int)$row['id']; ?>
                <tr>
                  <td class="text-center"><?= $no++; ?></td>
                  <td><?= safe($row['nama']); ?></td>
                  <td><?= safe($row['instansi_pendidikan']); ?></td>
                  <td><?= safe($row['jurusan']); ?></td>
                  <td><?= safe($row['no_hp'] ?? $row['no_hp']); ?></td>
                  <td class="text-center">
                    <?php if($row['status']==='pending'): ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                      <?php elseif($row['status']==='diterima'): ?>
                        <span class="badge bg-success">Diterima</span>
                      <?php elseif($row['status']==='ditolak'): ?>
                        <span class="badge bg-danger">Ditolak</span>
                      <?php elseif($row['status']==='berlangsung'): ?>
                        <span class="badge bg-primary">Sedang Berlangsung</span>
                      <?php elseif($row['status']==='batal'): ?>
                        <span class="badge bg-secondary">Batal</span>
                      <?php else: ?>
                        <span class="badge bg-light text-dark">-</span>
                      <?php endif; ?>
                  </td>

                  <td class="text-center">
                    <!-- status buttons -->
                    <a href="daftar_pkl.php?id=<?= $row['id'] ?>&status=diterima" class="btn btn-success btn-sm">✔ Terima</a>
                    <a href="daftar_pkl.php?id=<?= $row['id'] ?>&status=pending" class="btn btn-warning btn-sm">⏳ Pending</a>
                    <a href="daftar_pkl.php?id=<?= $row['id'] ?>&status=ditolak" class="btn btn-danger btn-sm">❌ Tolak</a>
                  </td>
                  <!-- Detail -->
                  <td class="text-center">
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?= $id; ?>" title="Rincian">🔍</button>
                  </td>
                  <!-- Edit Data -->
                  <td class="text-center">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $id; ?>" title="Edit">✏️</button>
                    <a href="?delete=<?= $id; ?>" onclick="return confirm('Yakin hapus data ini?');" class="btn btn-dark btn-sm" title="Hapus">🗑️</a>
                  </td>
                </tr>

                <!-- Detail Modal -->
                <div class="modal fade" id="detailModal<?= $id; ?>" tabindex="-1">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Rincian Pendaftar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                          <div class="col-md-6">
                            <p><strong>Nama:</strong> <?= safe($row['nama']); ?></p>
                            <p><strong>Email:</strong> <?= safe($row['email']); ?></p>
                            <p><strong>NIS/NPM:</strong> <?= safe($row['nis_npm']); ?></p>
                            <p><strong>No HP:</strong> <?= safe($row['no_hp']); ?></p>
                          </div>
                          <div class="col-md-6">
                            <p><strong>Instansi:</strong> <?= safe($row['instansi_pendidikan']); ?></p>
                            <p><strong>Jurusan:</strong> <?= safe($row['jurusan']); ?></p>
                            <p><strong>Skill:</strong> <?= safe($row['skill']); ?></p>
                            <p><strong>Unit:</strong> <?= safe($row['unit']); ?></p>
                            <p><strong>Durasi:</strong> <?= safe($row['durasi']); ?></p>
                          </div>
                        </div>
                        <hr>
                        <p><strong>Alamat:</strong> <?= safe($row['alamat']); ?></p>
                        <p><strong>Periode:</strong> <?= safe($row['tgl_mulai']); ?> — <?= safe($row['tgl_selesai']); ?></p>
                        <div class="row g-3">
                          <div class="col-md-4"><strong>Foto</strong><br><?= $row['upload_foto'] ? '<a href="'. $upload_dir . safe($row['upload_foto']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
                          <div class="col-md-4"><strong>KTM</strong><br><?= $row['upload_kartu_identitas'] ? '<a href="'. $upload_dir . safe($row['upload_kartu_identitas']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
                          <div class="col-md-4"><strong>Surat</strong><br><?= $row['upload_surat_permohonan'] ? '<a href="'. $upload_dir . safe($row['upload_surat_permohonan']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $id; ?>" tabindex="-1">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $id; ?>">
                        <div class="modal-header bg-danger text-white">
                          <h5 class="modal-title">Edit Pendaftar</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" value="<?= safe($row['nama']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= safe($row['email']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">NIS/NPM</label><input type="text" name="nis_npm" class="form-control" value="<?= safe($row['nis_npm']); ?>"></div>
                            <div class="col-md-6"><label class="form-label">No HP</label><input type="text" name="no_hp" class="form-control" value="<?= safe($row['no_hp']); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Instansi Pendidikan</label><input type="text" name="instansi_pendidikan" class="form-control" value="<?= safe($row['instansi_pendidikan']); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Jurusan</label><input type="text" name="jurusan" class="form-control" value="<?= safe($row['jurusan']); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Skill</label><input type="text" name="skill" class="form-control" value="<?= safe($row['skill']); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Durasi</label><input type="text" name="durasi" class="form-control" value="<?= safe($row['durasi']); ?>"></div>
                            <div class="col-md-6">
                              <label class="form-label">Unit</label>
                              <select name="unit" class="form-select">
                                <option value="<?= safe($row['unit']); ?>"><?= safe($row['unit']); ?></option>
                                <option value="Finance & Human Capital">Finance & Human Capital</option>
                                <option value="Payment Collection">Payment Collection</option>
                                <option value="Witel Bussines Service">Witel Bussines Service</option>
                                <option value="Government Service">Government Service</option>
                                <option value="Office Area Pekayon">Office Area Pekayon</option>
                                <option value="Office Area Kaliabang">Office Area Kaliabang</option>
                                <option value="Office Area Cibitung">Office Area Cibitung</option>
                                <option value="Office Area Karawang">Office Area Karawang</option>
                                <option value="Office Area Purwakarta">Office Area Purwakarta</option>
                                <option value="Office Area Subang">Office Area Subang</option>
                              </select>
                            </div>
                            <div class="col-12"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control"><?= safe($row['alamat']); ?></textarea></div>
                            <div class="col-md-6"><label class="form-label">Tanggal Mulai</label><input type="date" name="tgl_mulai" class="form-control" value="<?= safe($row['tgl_mulai']); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Tanggal Selesai</label><input type="date" name="tgl_selesai" class="form-control" value="<?= safe($row['tgl_selesai']); ?>"></div>

                            <div class="col-md-4"><label class="form-label">Ganti Foto (opsional)</label><input type="file" name="upload_foto" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Ganti KTM (opsional)</label><input type="file" name="upload_kartu_identitas" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Ganti Surat (opsional)</label><input type="file" name="upload_surat_permohonan" class="form-control"></div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="submit" class="btn btn-success">Simpan</button>
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

              <?php endwhile; } else { ?>
                <tr><td colspan="9" class="text-center text-muted">Belum ada data pendaftar</td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="insert">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i> Tambah Pendaftar PKL</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">NIS/NPM</label><input type="text" name="nis_npm" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">No HP</label><input type="text" name="no_hp" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Instansi Pendidikan</label><input type="text" name="instansi_pendidikan" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Jurusan</label><input type="text" name="jurusan" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Skill</label><input type="text" name="skill" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Durasi</label><input type="text" name="durasi" class="form-control"></div>
              <div class="col-md-6">
                <label class="form-label">Unit</label>
                <select name="unit" class="form-select" required>
                  <option value="">-- Pilih Unit --</option>
                  <option value="Finance & Human Capital">Finance & Human Capital</option>
                  <option value="Payment Collection">Payment Collection</option>
                  <option value="Witel Bussines Service">Witel Bussines Service</option>
                  <option value="Government Service">Government Service</option>
                  <option value="Office Area Pekayon">Office Area Pekayon</option>
                  <option value="Office Area Kaliabang">Office Area Kaliabang</option>
                  <option value="Office Area Cibitung">Office Area Cibitung</option>
                  <option value="Office Area Karawang">Office Area Karawang</option>
                  <option value="Office Area Purwakarta">Office Area Purwakarta</option>
                  <option value="Office Area Subang">Office Area Subang</option>
                </select>
              </div>
              <div class="col-12"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control"></textarea></div>
              <div class="col-md-6"><label class="form-label">Tanggal Mulai</label><input type="date" name="tgl_mulai" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Tanggal Selesai</label><input type="date" name="tgl_selesai" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Upload Foto</label><input type="file" name="upload_foto" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Upload Kartu Identitas</label><input type="file" name="upload_kartu_identitas" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Upload Surat Permohonan</label><input type="file" name="upload_surat_permohonan" class="form-control"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');

    if (menuToggle) {
      menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        menuToggle.style.display = sidebar.classList.contains('active') ? 'none' : 'inline-block';
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
        menuToggle.style.display = 'inline-block';
      });
    }

    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('active');
          overlay.style.display = 'none';
          menuToggle.style.display = 'inline-block';
        }
      });
    });
  </script>
</body>
</html>
