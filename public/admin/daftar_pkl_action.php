<?php
// ============================================================
// FILE: daftar_pkl_action.php
// Fungsi: CRUD & status handler untuk pendaftar PKL
// ============================================================

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
  $name = preg_replace('/[^A-Za-z0-9.-]/', '', $name);
  $newname = time() . "_" . $name;

  return move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $newname)
    ? $newname
    : '';
}

// ============================================================
// INSERT DATA PENDAFTAR PKL
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'insert') {

  // ==========================
  // Ambil data dari form
  // ==========================
  $nama             = trim($_POST['nama'] ?? '');
  $email            = trim($_POST['email'] ?? '');
  $nis_npm          = trim($_POST['nis_npm'] ?? '');
  $instansi         = trim($_POST['instansi_pendidikan'] ?? '');
  $jurusan          = trim($_POST['jurusan'] ?? '');
  $ipk              = isset($_POST['ipk_nilai_ratarata']) && $_POST['ipk_nilai_ratarata'] !== ''
                      ? (float) $_POST['ipk_nilai_ratarata']
                      : null;
  $semester         = trim($_POST['semester'] ?? '');
  $memiliki_laptop  = trim($_POST['memiliki_laptop'] ?? '');
  $bersedia_unit    = trim($_POST['bersedia_unit_manapun'] ?? '');
  $no_surat         = trim($_POST['nomor_surat_permohonan'] ?? '');
  $skill            = trim($_POST['skill'] ?? '');
  $durasi           = trim($_POST['durasi'] ?? '');
  $unit_id          = !empty($_POST['unit_id']) ? (int) $_POST['unit_id'] : null;
  $no_hp            = trim($_POST['no_hp'] ?? '');
  $alamat           = trim($_POST['alamat'] ?? '');
  $tgl_mulai        = trim($_POST['tgl_mulai'] ?? null);
  $tgl_selesai      = trim($_POST['tgl_selesai'] ?? null);
  $status           = 'pending';
  $tgl_daftar       = date('Y-m-d'); // hanya tanggal, tanpa jam

  // ==========================
  // Upload file
  // ==========================
  $foto  = uploadFileUnique('upload_foto', $foto_dir);
  $ktm   = uploadFileUnique('upload_kartu_identitas', $ktm_dir);
  $surat = uploadFileUnique('upload_surat_permohonan', $surat_dir);

  // ==========================
  // Query Insert
  // ==========================
  $sql = "INSERT INTO daftar_pkl (
            nama, email, nis_npm, instansi_pendidikan, jurusan,
            skill, durasi, unit_id, no_hp, alamat,
            tgl_mulai, tgl_selesai, upload_surat_permohonan,
            upload_foto, upload_kartu_identitas,
            ipk_nilai_ratarata, semester, memiliki_laptop,
            bersedia_unit_manapun, nomor_surat_permohonan,
            status, tgl_daftar
          )
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    $_SESSION['error'] = "❌ Prepare failed: " . $conn->error;
    header("Location: daftar_pkl.php");
    exit;
  }

  // ==========================
  // Bind parameter (22 kolom)
  // ==========================
  $stmt = $conn->prepare($sql);
  if(!$stmt){
    $_SESSION['error'] = "DB prepare error: " . $conn->error;
  } else {
    // types: 7x s + i + many s + d + s...
    $types = "sssssssisssssssdssssss"; // 22 params
    $bind = $stmt->bind_param(
      $types,
      $nama, $email, $nis_npm, $instansi, $jurusan, $skill, $durasi,
      $unit_id,
      $no_hp, $alamat,
      $tgl_mulai, $tgl_selesai,
      $upload_surat, $upload_foto, $upload_ktm,
      $ipk, $semester, $memiliki_laptop, $bersedia_unit,
      $no_surat, $status, $tgl_daftar
    );

  // ==========================
  // Eksekusi Query
  // ==========================
  if ($stmt->execute()) {
    $_SESSION['success'] = "✅ Pendaftar PKL berhasil disimpan!";
  } else {
    $_SESSION['error'] = "❌ Gagal menyimpan data: " . $stmt->error;
  }

  $stmt->close();

  header("Location: daftar_pkl.php");
  exit();}
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

    // Cek apakah peserta sudah ada di peserta_pkl
    $cekPeserta = $conn->prepare("SELECT id, nomor_surat FROM peserta_pkl WHERE email=?");
    $cekPeserta->bind_param("s", $d['email']);
    $cekPeserta->execute();
    $resPeserta = $cekPeserta->get_result();
    $cekPeserta->close();

    $statusPeserta = "berlangsung";

    if ($resPeserta->num_rows > 0) {
        // Sudah ada peserta
        $rowPeserta = $resPeserta->fetch_assoc();
        $nomor_surat = $rowPeserta['nomor_surat'];

        // Kalau belum punya nomor_surat, generate baru
        if (!$nomor_surat) {
            $resNomor = $conn->query("SELECT MAX(nomor_surat) AS last_no FROM peserta_pkl");
            $rowNomor = $resNomor->fetch_assoc();
            $next_no = $rowNomor['last_no'] ? $rowNomor['last_no'] + 1 : 1;
            $updateNo = $conn->prepare("UPDATE peserta_pkl SET nomor_surat=? WHERE id=?");
            $updateNo->bind_param("ii", $next_no, $rowPeserta['id']);
            $updateNo->execute();
            $updateNo->close();
        }

        // Update data peserta lainnya
        $updPeserta = $conn->prepare("UPDATE peserta_pkl SET 
            nama=?, instansi_pendidikan=?, jurusan=?, nis_npm=?, unit_id=?, no_hp=?, 
            tgl_mulai=?, tgl_selesai=?, status=? 
            WHERE id=?");
        $updPeserta->bind_param(
            "ssssissssi",
            $d['nama'], $d['instansi_pendidikan'], $d['jurusan'], $d['nis_npm'], 
            $d['unit_id'], $d['no_hp'], $d['tgl_mulai'], $d['tgl_selesai'], 
            $statusPeserta, $rowPeserta['id']
        );
        $updPeserta->execute();
        $updPeserta->close();
    } else {
        // Belum ada peserta -> buat baru + nomor surat otomatis
        $resNomor = $conn->query("SELECT MAX(nomor_surat) AS last_no FROM peserta_pkl");
        $rowNomor = $resNomor->fetch_assoc();
        $next_no = $rowNomor['last_no'] ? $rowNomor['last_no'] + 1 : 1;

        $insPeserta = $conn->prepare("INSERT INTO peserta_pkl 
            (user_id, nama, email, instansi_pendidikan, jurusan, nis_npm, unit_id, no_hp, 
             tgl_mulai, tgl_selesai, status, nomor_surat)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $insPeserta->bind_param(
            "issssisssssi",
            $user_id, $d['nama'], $d['email'], $d['instansi_pendidikan'], $d['jurusan'],
            $d['nis_npm'], $d['unit_id'], $d['no_hp'], $d['tgl_mulai'], $d['tgl_selesai'],
            $statusPeserta, $next_no
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

  // Redirect kembali ke halaman daftar, pertahankan filter jika ada
  $redirect = "daftar_pkl.php";
  if (isset($_GET['filter_status']) && in_array($_GET['filter_status'], ['all','pending','diterima','ditolak'])) {
    $redirect .= '?filter_status=' . urlencode($_GET['filter_status']);
  }
  header("Location: " . $redirect);
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
// Tambah / Edit / Hapus Unit
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if ($_POST['action'] == 'insert_unit') {
    $nama_unit = trim($_POST['nama_unit']);
    if ($nama_unit != '') {
      $stmt = $conn->prepare("INSERT INTO unit_pkl (nama_unit) VALUES (?)");
      $stmt->bind_param("s", $nama_unit);
      $stmt->execute();
      $_SESSION['success'] = "Unit berhasil ditambahkan.";
    }
    header("Location: daftar_pkl.php");
    exit;
  }

  if ($_POST['action'] == 'update_unit') {
    $id = (int)$_POST['id'];
    $nama_unit = trim($_POST['nama_unit']);
    if ($nama_unit != '') {
      $stmt = $conn->prepare("UPDATE unit_pkl SET nama_unit=? WHERE id=?");
      $stmt->bind_param("si", $nama_unit, $id);
      $stmt->execute();
      $_SESSION['success'] = "Unit berhasil diperbarui.";
    }
    header("Location: daftar_pkl.php");
    exit;
  }
}

// Hapus Unit
if (isset($_GET['delete_unit'])) {
  $id = (int)$_GET['delete_unit'];
  $stmt = $conn->prepare("DELETE FROM unit_pkl WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $_SESSION['success'] = "Unit berhasil dihapus.";
  header("Location: daftar_pkl.php");
  exit;
}

// ================== Ambil daftar unit ==================
$unitResult = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");

// ================== Ambil data daftar_pkl ==================
// Filter status (dari GET) - gunakan filter_status supaya tidak tabrakan dengan param status untuk aksi
$allowed_filters = ['all','pending','diterima','ditolak'];
$filter_status = isset($_GET['filter_status']) && in_array($_GET['filter_status'], $allowed_filters) ? $_GET['filter_status'] : 'all';

// JOIN supaya dapat nama_unit
$sql = "
  SELECT dp.*, u.nama_unit 
  FROM daftar_pkl dp
  LEFT JOIN unit_pkl u ON dp.unit_id = u.id
";
if ($filter_status !== 'all') {
    $sql .= " WHERE dp.status = ?";
}
$sql .= " ORDER BY dp.created_at DESC";

$stmtList = $conn->prepare($sql);
if ($filter_status !== 'all') {
    $stmtList->bind_param("s", $filter_status);
}
$stmtList->execute();
$result = $stmtList->get_result();
$stmtList->close();

// Filter dan pencarian
$filter_status = $_GET['filter_status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, u.nama_unit 
        FROM daftar_pkl p 
        LEFT JOIN unit_pkl u ON p.unit_id = u.id 
        WHERE 1=1";

if ($filter_status !== 'all') {
    $sql .= " AND p.status = '" . $conn->real_escape_string($filter_status) . "'";
}

if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $sql .= " AND (
        p.nama LIKE '%$s%' 
        OR p.instansi_pendidikan LIKE '%$s%' 
        OR p.jurusan LIKE '%$s%'
        OR p.skill LIKE '%$s%'
    )";
}

$sql .= " ORDER BY p.id DESC";
$result = $conn->query($sql);
?>