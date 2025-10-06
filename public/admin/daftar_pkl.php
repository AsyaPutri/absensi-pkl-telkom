<?php
// ============================================================
// FILE: daftar_pkl_action.php
// Fungsi: CRUD & status handler untuk pendaftar PKL
// ============================================================

include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// ============================================================
// SETUP FOLDER UPLOAD
// ============================================================
$upload_dir = "../../uploads/";
$foto_dir   = $upload_dir . "Foto_daftarpkl/";
$ktm_dir    = $upload_dir . "Foto_Kartuidentitas/";
$surat_dir  = $upload_dir . "Surat_Permohonan/";

foreach ([$upload_dir, $foto_dir, $ktm_dir, $surat_dir] as $dir) {
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================
function safe($v) {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function uploadFileUnique($fileKey, $upload_dir) {
  if (!isset($_FILES[$fileKey]) || !$_FILES[$fileKey]['name']) return '';

  $name = basename($_FILES[$fileKey]['name']);
  $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
  $newname = time() . "_" . $name;

  return move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $newname)
    ? $newname
    : '';
}

// ============================================================
// INSERT DATA PENDAFTAR PKL
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'insert') {

  // --- Ambil data dari form ---
  $nama       = trim($_POST['nama'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $nis_npm    = trim($_POST['nis_npm'] ?? '');
  $instansi   = trim($_POST['instansi_pendidikan'] ?? '');
  $jurusan    = trim($_POST['jurusan'] ?? '');
  $ipk        = ($_POST['ipk_nilai_ratarata'] !== '') ? (float) $_POST['ipk_nilai_ratarata'] : null;
  $semester   = trim($_POST['semester'] ?? '');
  $memiliki_laptop = trim($_POST['memiliki_laptop'] ?? '');
  $bersedia_unit   = trim($_POST['bersedia_unit_manapun'] ?? '');
  $no_surat        = trim($_POST['nomor_surat_permohonan'] ?? '');
  $skill           = trim($_POST['skill'] ?? '');
  $durasi          = trim($_POST['durasi'] ?? '');
  $unit_id         = !empty($_POST['unit_id']) ? (int) $_POST['unit_id'] : null;
  $no_hp           = trim($_POST['no_hp'] ?? '');
  $alamat          = trim($_POST['alamat'] ?? '');
  $tgl_mulai       = trim($_POST['tgl_mulai'] ?? null);
  $tgl_selesai     = trim($_POST['tgl_selesai'] ?? null);
  $status          = 'pending';
  $user_id         = $_SESSION['user_id'] ?? null;

  // --- Upload file ---
  $foto  = uploadFileUnique('upload_foto', $foto_dir);
  $ktm   = uploadFileUnique('upload_kartu_identitas', $ktm_dir);
  $surat = uploadFileUnique('upload_surat_permohonan', $surat_dir);

  // --- Simpan ke database ---
  $sql = "INSERT INTO daftar_pkl
      (user_id, nama, email, nis_npm, instansi_pendidikan, jurusan, skill, durasi, unit_id, no_hp, alamat,
       tgl_mulai, tgl_selesai, upload_surat_permohonan, upload_foto, upload_kartu_identitas,
       ipk_nilai_ratarata, semester, memiliki_laptop, bersedia_unit_manapun,
       nomor_surat_permohonan, status)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

  $stmt = $conn->prepare($sql);
  if ($stmt) {
      $stmt->bind_param(
          "isssssssisssssssssssss",
          $user_id, $nama, $email, $nis_npm, $instansi, $jurusan,
          $skill, $durasi, $unit_id, $no_hp, $alamat,
          $tgl_mulai, $tgl_selesai, $surat, $foto, $ktm,
          $ipk, $semester, $memiliki_laptop, $bersedia_unit,
          $no_surat, $status
      );

      if ($stmt->execute()) {
          $_SESSION['success'] = "✅ Pendaftar PKL berhasil disimpan!";
      } else {
          $_SESSION['error'] = "❌ Error saat simpan: " . $stmt->error;
      }
      $stmt->close();
  } else {
      $_SESSION['error'] = "❌ Error prepare: " . $conn->error;
  }

  header("Location: daftar_pkl.php");
  exit();
}

// ============================================================
// UPDATE STATUS PENDAFTAR (diterima / ditolak / pending)
// ============================================================
if (isset($_GET['id']) && isset($_GET['status'])) {
  $id     = (int) $_GET['id'];
  $status = $_GET['status'];
  $allowed = ['diterima', 'pending', 'ditolak'];

  if (!in_array($status, $allowed)) {
      $_SESSION['error'] = "❌ Status tidak valid!";
      header("Location: daftar_pkl.php");
      exit;
  }

  $conn->begin_transaction();
  try {
      // --- Ambil data pendaftar ---
      $q = $conn->prepare("SELECT * FROM daftar_pkl WHERE id=? FOR UPDATE");
      $q->bind_param("i", $id);
      $q->execute();
      $d = $q->get_result()->fetch_assoc();
      $q->close();

      if (!$d) throw new Exception("Data pendaftar tidak ditemukan.");

      // --- Update status daftar_pkl ---
      $upd = $conn->prepare("UPDATE daftar_pkl SET status=? WHERE id=?");
      $upd->bind_param("si", $status, $id);
      if (!$upd->execute()) throw new Exception($upd->error);
      $upd->close();

      // === STATUS: DITERIMA ===
      if ($status === 'diterima') {
          // Buat akun user jika belum ada
          $cekUser = $conn->prepare("SELECT id FROM users WHERE email=?");
          $cekUser->bind_param("s", $d['email']);
          $cekUser->execute();
          $userRow = $cekUser->get_result()->fetch_assoc();
          $cekUser->close();

          if ($userRow) {
              $user_id = $userRow['id'];
          } else {
              $passHash = password_hash($d['nis_npm'], PASSWORD_DEFAULT);
              $role = "magang";
              $insUser = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
              $insUser->bind_param("ssss", $d['nama'], $d['email'], $passHash, $role);
              $insUser->execute();
              $user_id = $insUser->insert_id;
              $insUser->close();
          }

          // Insert peserta_pkl jika belum ada
          $cekPeserta = $conn->prepare("SELECT id FROM peserta_pkl WHERE email=?");
          $cekPeserta->bind_param("s", $d['email']);
          $cekPeserta->execute();
          $exists = $cekPeserta->get_result()->num_rows > 0;
          $cekPeserta->close();

          if (!$exists) {
              $statusPeserta = "berlangsung";
              $insPeserta = $conn->prepare("INSERT INTO peserta_pkl 
                  (user_id, nama, email, instansi_pendidikan, jurusan, nis_npm, unit_id, no_hp, tgl_mulai, tgl_selesai, status)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?)");
              $insPeserta->bind_param(
                  "isssssissss",
                  $user_id, $d['nama'], $d['email'], $d['instansi_pendidikan'], $d['jurusan'],
                  $d['nis_npm'], $d['unit_id'], $d['no_hp'], $d['tgl_mulai'], $d['tgl_selesai'], $statusPeserta
              );
              $insPeserta->execute();
              $insPeserta->close();
          }
      }

      // === STATUS: DITOLAK ===
      elseif ($status === 'ditolak') {
          $delPeserta = $conn->prepare("DELETE FROM peserta_pkl WHERE email=?");
          $delPeserta->bind_param("s", $d['email']);
          $delPeserta->execute();
          $delPeserta->close();

          $delUser = $conn->prepare("DELETE FROM users WHERE email=? AND role='magang'");
          $delUser->bind_param("s", $d['email']);
          $delUser->execute();
          $delUser->close();
      }

      // === STATUS: PENDING ===
      elseif ($status === 'pending') {
          $updPeserta = $conn->prepare("UPDATE peserta_pkl SET status='pending' WHERE email=?");
          $updPeserta->bind_param("s", $d['email']);
          $updPeserta->execute();
          $updPeserta->close();
      }

      $conn->commit();
      $_SESSION['success'] = "✅ Status berhasil diubah menjadi $status";

  } catch (Exception $e) {
      $conn->rollback();
      $_SESSION['error'] = "❌ Gagal mengubah status: " . $e->getMessage();
  }

  header("Location: daftar_pkl.php");
  exit;
}

// ============================================================
// UPDATE DATA PENDAFTAR
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
      $_SESSION['error'] = "ID peserta tidak valid.";
      header("Location: daftar_pkl.php");
      exit();
  }

  // --- Ambil data lama ---
  $oldFoto = $oldKtm = $oldSurat = '';
  $q = $conn->prepare("SELECT upload_foto, upload_kartu_identitas, upload_surat_permohonan FROM daftar_pkl WHERE id=?");
  $q->bind_param("i", $id);
  $q->execute();
  $r = $q->get_result();
  if ($row = $r->fetch_assoc()) {
      $oldFoto = $row['upload_foto'];
      $oldKtm = $row['upload_kartu_identitas'];
      $oldSurat = $row['upload_surat_permohonan'];
  }
  $q->close();

  // --- Data baru dari form ---
  $nama = trim($_POST['nama'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $nis = trim($_POST['nis_npm'] ?? '');
  $inst = trim($_POST['instansi_pendidikan'] ?? '');
  $jur = trim($_POST['jurusan'] ?? '');
  $skill = trim($_POST['skill'] ?? '');
  $dur = trim($_POST['durasi'] ?? '');
  $unit_id = trim($_POST['unit_id'] ?? '');
  $hp = trim($_POST['no_hp'] ?? '');
  $alamat = trim($_POST['alamat'] ?? '');
  $ipk = ($_POST['ipk_nilai_ratarata'] !== '') ? (float) $_POST['ipk_nilai_ratarata'] : null;
  $semester = trim($_POST['semester'] ?? '');
  $memiliki_laptop = trim($_POST['memiliki_laptop'] ?? '');
  $bersedia_unit = trim($_POST['bersedia_unit_manapun'] ?? '');
  $no_surat = trim($_POST['nomor_surat_permohonan'] ?? '');
  $tgl_mulai = $_POST['tgl_mulai'] ?? null;
  $tgl_selesai = $_POST['tgl_selesai'] ?? null;

  // --- Upload file baru ---
  $fotoNew = uploadFileUnique('upload_foto', $foto_dir);
  $ktmNew = uploadFileUnique('upload_kartu_identitas', $ktm_dir);
  $suratNew = uploadFileUnique('upload_surat_permohonan', $surat_dir);

  // --- Tentukan file akhir ---
  $fotoDB = $fotoNew ?: $oldFoto;
  $ktmDB = $ktmNew ?: $oldKtm;
  $suratDB = $suratNew ?: $oldSurat;

  // --- Hapus file lama jika ada file baru ---
  if ($fotoNew && file_exists($foto_dir . $oldFoto)) @unlink($foto_dir . $oldFoto);
  if ($ktmNew && file_exists($ktm_dir . $oldKtm)) @unlink($ktm_dir . $oldKtm);
  if ($suratNew && file_exists($surat_dir . $oldSurat)) @unlink($surat_dir . $oldSurat);

  // --- Update daftar_pkl ---
  $sql = "UPDATE daftar_pkl SET
              nama=?, email=?, nis_npm=?, instansi_pendidikan=?, jurusan=?, ipk_nilai_ratarata=?, semester=?,
              memiliki_laptop=?, bersedia_unit_manapun=?, nomor_surat_permohonan=?,
              skill=?, durasi=?, unit_id=?, no_hp=?, alamat=?, tgl_mulai=?, tgl_selesai=?,
              upload_foto=?, upload_kartu_identitas=?, upload_surat_permohonan=?
          WHERE id=?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare statement gagal: " . $conn->error . "<br>SQL: " . $sql);
}

// Pastikan urutan parameter sesuai kolom di query UPDATE kamu
$stmt->bind_param(
    "sssssdssssssisssssssi",
    $nama,           // s
    $email,          // s
    $nis,            // s
    $inst,           // s
    $jur,            // s
    $ipk,            // d (double)
    $semester,       // s
    $memiliki_laptop,// s
    $bersedia_unit,  // s
    $no_surat,       // s
    $skill,          // s
    $dur,            // s
    $unit_id,        // i (integer)
    $hp,             // s
    $alamat,         // s
    $tgl_mulai,      // s
    $tgl_selesai,    // s
    $fotoDB,         // s
    $ktmDB,          // s
    $suratDB,        // s
    $id              // i (integer)
);

  if ($stmt->execute()) {
      // Sinkronisasi ke peserta_pkl
      $cekPeserta = $conn->prepare("SELECT id FROM peserta_pkl WHERE email=?");
      $cekPeserta->bind_param("s", $email);
      $cekPeserta->execute();
      $resPeserta = $cekPeserta->get_result();

      if ($resPeserta && $resPeserta->num_rows > 0) {
          $updPeserta = $conn->prepare("UPDATE peserta_pkl SET 
              nama=?, instansi_pendidikan=?, jurusan=?, nis_npm=?, unit_id=?, no_hp=?, tgl_mulai=?, tgl_selesai=? 
              WHERE email=?");
          $updPeserta->bind_param("ssssissss", $nama, $inst, $jur, $nis, $unit_id, $hp, $tgl_mulai, $tgl_selesai, $email);
          $updPeserta->execute();
          $updPeserta->close();
      } else {
          $statusPeserta = "berlangsung";
          $insPeserta = $conn->prepare("INSERT INTO peserta_pkl 
              (user_id, nama, email, instansi_pendidikan, jurusan, nis_npm, unit_id, no_hp, tgl_mulai, tgl_selesai, status)
              VALUES (?,?,?,?,?,?,?,?,?,?,?)");
          $insPeserta->bind_param("issssisssss", $user_id, $nama, $email, $inst, $jur, $nis, $unit_id, $hp, $tgl_mulai, $tgl_selesai, $statusPeserta);
          $insPeserta->execute();
          $insPeserta->close();
      }
      $cekPeserta->close();

      $_SESSION['success'] = "✅ Data pendaftar peserta berhasil diperbarui.";
  } else {
      $_SESSION['error'] = "❌ Gagal update: " . $stmt->error;
  }
  $stmt->close();

  header("Location: daftar_pkl.php");
  exit();
}

// ============================================================
// DELETE PENDAFTAR
// ============================================================
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];

  $q = $conn->prepare("SELECT email, status, upload_foto, upload_kartu_identitas, upload_surat_permohonan 
                       FROM daftar_pkl WHERE id=?");
  $q->bind_param("i", $id);
  $q->execute();
  $rw = $q->get_result()->fetch_assoc();
  $q->close();

  if ($rw) {
      $email = $rw['email'];

      // hapus file upload
      foreach (['upload_foto' => $foto_dir, 'upload_kartu_identitas' => $ktm_dir, 'upload_surat_permohonan' => $surat_dir] as $key => $dir) {
          if ($rw[$key] && file_exists($dir . $rw[$key])) @unlink($dir . $rw[$key]);
      }

      // hapus peserta & user
      $delPeserta = $conn->prepare("DELETE FROM peserta_pkl WHERE email=?");
      $delPeserta->bind_param("s", $email);
      $delPeserta->execute();
      $delPeserta->close();

      $delUser = $conn->prepare("DELETE FROM users WHERE email=? AND role='magang'");
      $delUser->bind_param("s", $email);
      $delUser->execute();
      $delUser->close();

      // hapus daftar_pkl
      $stmt = $conn->prepare("DELETE FROM daftar_pkl WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->close();

      $_SESSION['success'] = "✅ Data pendaftar, akun user, dan peserta PKL berhasil dihapus.";
  } else {
      $_SESSION['error'] = "❌ Data tidak ditemukan.";
  }

  header("Location: daftar_pkl.php");
  exit();
}

// =========================
// Tambah Unit dari Modal
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['action']) && $_POST['action'] == 'insert_unit') {
    $nama_unit = trim($_POST['nama_unit']);
    if ($nama_unit != '') {
      $stmt = $conn->prepare("INSERT INTO unit_pkl (nama_unit) VALUES (?)");
      $stmt->bind_param("s", $nama_unit);
      $stmt->execute();
      $stmt->close();
    }
    // Redirect biar tidak double insert saat refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  }
}

// ================== Ambil daftar unit ==================
$unitResult = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");

// ================== Ambil data daftar_pkl ==================
// JOIN supaya dapat nama_unit
$stmtList = $conn->prepare("
  SELECT dp.*, u.nama_unit 
  FROM daftar_pkl dp
  LEFT JOIN unit_pkl u ON dp.unit_id = u.id
  ORDER BY dp.created_at DESC
");
$stmtList->execute();
$result = $stmtList->get_result();
$stmtList->close();

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
      <li><a href="riwayat_peserta.php" class="nav-link <?= ($current_page== 'riwayat_peserta.php') ?'active':'' ?> "><i class="bi bi-clock-history me-2"></i> Riwayat Peserta</a></li>
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
                            <p><strong>Instansi:</strong> <?= safe($row['instansi_pendidikan']); ?></p>
                            <p><strong>Jurusan:</strong> <?= safe($row['jurusan']); ?></p>
                            <p><strong>IPK/Nilai Rata-Rata:</strong> <?= safe($row['ipk_nilai_ratarata']); ?></p>
                          </div>
                          <div class="col-md-6">
                            <p><strong>Semester/Tingkat Kelas:</strong> <?= safe($row['semester']); ?></p>
                            <p><strong>Skill:</strong> <?= safe($row['skill']); ?></p>
                            <p><strong>Unit:</strong> <?= safe($row['nama_unit']); ?></p>
                            <p><strong>Bersedia Unit Manapun:</strong> <?= safe($row['bersedia_unit_manapun']); ?></p>
                            <p><strong>Memiliki Laptop:</strong> <?= safe($row['memiliki_laptop']); ?></p>
                            <p><strong>Durasi:</strong> <?= safe($row['durasi']); ?></p>
                            <p><strong>No Surat Permohonan:</strong> <?= safe($row['nomor_surat_permohonan']); ?></p>
                          </div>
                        </div>
                        <hr>
                        <p><strong>Alamat:</strong> <?= safe($row['alamat']); ?></p>
                        <p><strong>Periode:</strong> <?= safe($row['tgl_mulai']); ?> — <?= safe($row['tgl_selesai']); ?></p>
                        <div class="row g-3">
                          <div class="col-md-4"><strong>Foto</strong><br><?= $row['upload_foto'] ? '<a href="'. $foto_dir . safe($row['upload_foto']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
                          <div class="col-md-4"><strong>KTM</strong><br><?= $row['upload_kartu_identitas'] ? '<a href="'. $ktm_dir . safe($row['upload_kartu_identitas']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
                          <div class="col-md-4"><strong>Surat</strong><br><?= $row['upload_surat_permohonan'] ? '<a href="'. $surat_dir . safe($row['upload_surat_permohonan']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
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
                            <div class="col-md-6">
                             <label class="form-label">Nama</label>
                             <input type="text" name="nama" class="form-control" value="<?= safe($row['nama']); ?>" required>
                            </div>
                          <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= safe($row['email']); ?>" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">NIS/NPM</label>
                            <input type="text" name="nis_npm" class="form-control" value="<?= safe($row['nis_npm']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= safe($row['no_hp']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Instansi Pendidikan</label>
                            <input type="text" name="instansi_pendidikan" class="form-control" value="<?= safe($row['instansi_pendidikan']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Jurusan</label>
                            <input type="text" name="jurusan" class="form-control" value="<?= safe($row['jurusan']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Semester/Tingkat Kelas</label>
                            <input type="text" name="semester" class="form-control" value="<?= safe($row['semester']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">IPK / Nilai Rata-rata</label>
                            <input type="number" step="0.01" name="ipk_nilai_ratarata" class="form-control" value="<?= safe($row['ipk_nilai_ratarata'] ?? ''); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Memiliki Laptop?</label>
                            <select name="memiliki_laptop" class="form-select">
                              <option value="Ya" <?= $row['memiliki_laptop'] == 'Ya' ? 'selected' : ''; ?>>Ya</option>
                              <option value="Tidak" <?= $row['memiliki_laptop'] == 'Tidak' ? 'selected' : ''; ?>>Tidak</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Bersedia di Unit Manapun?</label>
                            <select name="bersedia_unit_manapun" class="form-select">
                              <option value="Bersedia" <?= $row['bersedia_unit_manapun'] == 'Bersedia' ? 'selected' : ''; ?>>Bersedia</option>
                              <option value="Tidak Bersedia" <?= $row['bersedia_unit_manapun'] == 'Tidak Bersedia' ? 'selected' : ''; ?>>Tidak Bersedia</option>
                            </select>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Nomor Surat Permohonan</label>
                            <input type="text" name="nomor_surat_permohonan" class="form-control" value="<?= safe($row['nomor_surat_permohonan']); ?>">
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Skill</label>
                            <input type="text" name="skill" class="form-control" value="<?= safe($row['skill']); ?>">
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Durasi</label>
                            <select name="durasi" class="form-select" required>
                              <option value="">-- Pilih Durasi --</option>
                              <?php
                              $durasi_opsi = ["2 Bulan", "3 Bulan", "4 Bulan", "5 Bulan", "6 Bulan"];
                              foreach ($durasi_opsi as $dur) {
                                $selected = ($row['durasi'] == $dur) ? 'selected' : '';
                                echo "<option value='$dur' $selected>$dur</option>";
                              }
                              ?>
                            </select>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <div class="input-group">
                              <select name="unit_id" class="form-select">
                                <?php
                                $q_unit = $conn->query("SELECT * FROM unit_pkl ORDER BY nama_unit ASC");
                                while ($u = $q_unit->fetch_assoc()) {
                                  $selected = ($row['unit_id'] == $u['id']) ? 'selected' : '';
                                  echo "<option value='{$u['id']}' $selected>{$u['nama_unit']}</option>";
                                }
                                ?>
                              </select>
                              <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalTambahUnit">
                                <i class="bi bi-plus"></i>
                              </button>
                            </div>
                          </div>

                          <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control"><?= safe($row['alamat']); ?></textarea>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control" value="<?= safe($row['tgl_mulai']); ?>">
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control" value="<?= safe($row['tgl_selesai']); ?>">
                          </div>

                          <div class="col-md-4">
                            <label class="form-label">Ganti Foto (opsional)</label>
                            <input type="file" name="upload_foto" class="form-control">
                          </div>

                          <div class="col-md-4">
                            <label class="form-label">Ganti KTM (opsional)</label>
                            <input type="file" name="upload_kartu_identitas" class="form-control">
                          </div>

                          <div class="col-md-4">
                            <label class="form-label">Ganti Surat (opsional)</label>
                            <input type="file" name="upload_surat_permohonan" class="form-control">
                          </div>

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
              <div class="col-md-6">
                <label class="form-label">Nama</label>
                <input type="text" name="nama" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">NIS / NPM</label>
                <input type="text" name="nis_npm" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">No HP</label>
                <input type="text" name="no_hp" class="form-control">
              </div>

              <div class="col-md-6">
                <label class="form-label">Instansi Pendidikan</label>
                <input type="text" name="instansi_pendidikan" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Jurusan</label>
                <input type="text" name="jurusan" class="form-control">
              </div>

              <div class="col-md-4">
                <label class="form-label">IPK / Nilai Rata-rata</label>
                <input type="number" step="0.01" name="ipk" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Semester</label>
                <input type="text" name="semester" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Nomor Surat Permohonan</label>
                <input type="text" name="no_surat" class="form-control">
              </div>

              <div class="col-md-6">
                <label class="form-label">Skill</label>
                <input type="text" name="skill" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Durasi</label>
                  <select name="durasi" class="form-select" required>
                    <option value="">-- Pilih Durasi --</option>
                    <?php
                    $durasi_opsi = ["2 Bulan", "3 Bulan", "4 Bulan", "5 Bulan", "6 Bulan"];
                    foreach ($durasi_opsi as $dur) {
                      $selected = ($row['durasi'] == $dur) ? 'selected' : '';
                      echo "<option value='$dur' $selected>$dur</option>";
                    }
                    ?>
                  </select>
                </div>
              <div class="col-md-6">
                <label class="form-label">Mempunyai Laptop</label>
                <select name="memiliki_laptop" class="form-select">
                  <option value="">-- Pilih --</option>
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Bersedia Ditempatkan di Unit Mana Pun</label>
                <select name="bersedia_unit" class="form-select">
                  <option value="">-- Pilih --</option>
                  <option value="Bersedia">Bersedia</option>
                  <option value="Tidak Bersedia">Tidak Bersedia</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Unit</label>
                <div class="input-group">
                  <select name="unit_id" id="unitSelect" class="form-select" required>
                    <option value="">-- Pilih Unit --</option>
                    <?php
                    $q_unit = $conn->query("SELECT * FROM unit_pkl ORDER BY nama_unit ASC");
                    while ($u = $q_unit->fetch_assoc()) {
                      echo "<option value='{$u['id']}'>{$u['nama_unit']}</option>";
                    }
                    ?>
                  </select>
                  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalTambahUnit">
                    <i class="bi bi-plus"></i>
                  </button>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control"></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="tgl_selesai" class="form-control" required>
              </div>

              <div class="col-md-4">
                <label class="form-label">Upload Foto</label>
                <input type="file" name="upload_foto" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Upload Kartu Identitas</label>
                <input type="file" name="upload_kartu_identitas" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Upload Surat Permohonan</label>
                <input type="file" name="upload_surat_permohonan" class="form-control">
              </div>
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

  <!-- ======================== -->
  <!-- Modal Tambah Unit -->
  <!-- ======================== -->
  <div class="modal fade" id="modalTambahUnit" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="insert_unit">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Tambah Unit Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <label class="form-label">Nama Unit</label>
            <input type="text" name="nama_unit" class="form-control" required>
          </div>
          <div class="modal-footer">
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
