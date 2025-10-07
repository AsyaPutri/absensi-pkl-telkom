<?php
session_start();
require '../config/database.php'; // koneksi database ($conn)


// pastikan semua folder ada
$upload_dir = "../uploads/";
$foto_dir    = $upload_dir . "Foto_daftarpkl/"; 
$ktm_dir     = $upload_dir . "Foto_Kartuidentitas/"; 
$surat_dir   = $upload_dir . "Surat_Permohonan/"; 
foreach ([$upload_dir, $foto_dir, $ktm_dir, $surat_dir] as $dir) {
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

// ================== Helper Function ==================
function safe($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

function uploadFileUnique($fileKey, $upload_dir){
  if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return '';
  $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
  $newname = time() . "_" . uniqid() . "." . $ext;
  if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $newname)) {
      return $newname;
  }
  return '';
}

// ================== Ambil daftar unit ==================
$units = [];
$result = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $units[] = $row;
    }
}

// ================== Proses Submit ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Upload file
    $foto  = uploadFileUnique('upload_foto', $foto_dir);
    $ktm   = uploadFileUnique('upload_kartu_identitas', $ktm_dir);
    $surat = uploadFileUnique('upload_surat_permohonan', $surat_dir);

    // Query insert (21 kolom)
    $sql = "INSERT INTO daftar_pkl (
        nama, email, nis_npm, instansi_pendidikan, jurusan,
        ipk_nilai_ratarata, semester, memiliki_laptop, bersedia_unit_manapun,
        nomor_surat_permohonan, skill, durasi, unit_id,
        no_hp, alamat, tgl_mulai, tgl_selesai,
        upload_surat_permohonan, upload_foto, upload_kartu_identitas, status
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }

    $types = "sssss"  // nama, email, nis_npm, instansi, jurusan
       . "d"      // ipk
       . "ssssss" // semester, memiliki_laptop, bersedia_unit, no_surat, skill, durasi
       . "i"      // unit_id
       . "ssss"   // no_hp, alamat, tgl_mulai, tgl_selesai
       . "ssss";  // surat, foto, ktm, status
    $stmt->bind_param(
        $types,
        $nama, $email, $nis_npm, $instansi, $jurusan,
        $ipk,
        $semester, $memiliki_laptop, $bersedia_unit, $no_surat, $skill, $durasi,
        $unit_id,
        $no_hp, $alamat, $tgl_mulai, $tgl_selesai,
        $surat, $foto, $ktm, $status
    );

    if ($stmt->execute()) {
        echo "<script>alert('Registrasi PKL berhasil!'); window.location='login.php';</script>";
        exit;
    } else {
        die('Gagal menyimpan data: ' . $stmt->error);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrasi PKL - Witel Bekasi Karawang</title>
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background: linear-gradient(135deg, #d32f2f, #f44336);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    .container {
      background: #fff;
      max-width: 1200px;
      border-radius: 16px;
      box-shadow: 0 6px 25px rgba(0,0,0,0.2);
      display: flex;
      overflow: hidden;
      width: 100%;
    }
    .info-section {
      flex: 1;
      background: #fff5f5;
      padding: 40px 30px;
      border-right: 4px solid #f44336;
    }
    .info-section h2 { color: #d32f2f; margin-bottom: 15px; }
    .info-section p, .info-section li { color: #444; }
    .form-section { flex: 2; padding: 40px; }
    .form-section h2 {
      margin-bottom: 25px;
      color: #d32f2f;
      text-align: center;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .form-group { display: flex; flex-direction: column; }
    label { font-weight: 600; margin-bottom: 6px; color: #444; }
    input, select, textarea {
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      background: #fafafa;
      transition: 0.2s;
    }
    input:focus, select:focus, textarea:focus {
      border-color: #e53935;
      outline: none;
      box-shadow: 0 0 6px rgba(229,57,53,0.25);
      background: #fff;
    }
    textarea { resize: vertical; min-height: 70px; }
    .form-full { grid-column: 1 / 3; }
    button {
      background: linear-gradient(135deg, #e53935, #d32f2f);
      color: white;
      padding: 14px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      margin-top: 10px;
      width: 100%;
      transition: all 0.2s ease;
    }
    button:hover { transform: scale(1.03); background: #c62828; }
    .note { font-size: 13px; color: #777; margin-top: 8px; text-align: center; }
    @media(max-width: 900px) {
      .container { flex-direction: column; }
      .form-grid { grid-template-columns: 1fr; }
      .form-full { grid-column: 1 / 2; }
      .info-section { border-right: none; border-bottom: 3px solid #f44336; }
    }
  </style>
</head>
<body>
<div class="container">

  <!-- Bagian Info -->
  <div class="info-section">
    <h2>Registrasi Internship<br>Witel Bekasi - Karawang</h2>
    <p>Silakan isi form berikut untuk mendaftar PKL.</p>
    <p><strong>Syarat PKL:</strong></p>
    <ul>
      <li>Pas foto 3x4 = 1 lembar</li>
      <li>Surat permohonan dari sekolah/kampus</li>
      <li>Materai 10K</li>
      <li>Nomor HP Telkomsel</li>
      <li>Mempunyai laptop</li>
    </ul>
    <p><em>Catatan:</em> Bersedia ditempatkan di unit manapun sesuai domisili.</p>
    <p>Info: <strong>Orient (62 85316144454)</strong></p>
  </div>

  <!-- Bagian Form -->
  <div class="form-section">
    <h2>Form Registrasi PKL</h2>
    <form method="POST" enctype="multipart/form-data" class="form-grid">
      <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-group"><label>NIS/NPM</label><input type="text" name="nis_npm" required></div>
      <div class="form-group"><label>Instansi Pendidikan</label><input type="text" name="instansi_pendidikan" required></div>
      <div class="form-group"><label>Jurusan</label><input type="text" name="jurusan" required></div>
      <div class="form-group"><label>Semester/Tingkat</label><input type="text" name="semester"></div>
      <div class="form-group"><label>IPK/Nilai Rata-rata</label><input type="number" step="0.01" name="ipk_nilai_ratarata"></div>
<<<<<<< HEAD

      <div class="form-group">
        <label>Memiliki Laptop?</label>
        <select name="memiliki_laptop" required>
          <option value="">-- Pilih --</option>
          <option value="Ya">Ya</option>
          <option value="Tidak">Tidak</option>
        </select>
      </div>
      <div class="form-group">
        <label>Bersedia di Unit Manapun?</label>
        <select name="bersedia_unit_manapun" required>
          <option value="">-- Pilih --</option>
          <option value="Bersedia">Bersedia</option>
          <option value="Tidak Bersedia">Tidak Bersedia</option>
=======
      <div class="form-group"><label>Memiliki Laptop?</label>
        <select name="memiliki_laptop">
          <option value="" disabled selected>-- Pilih --</option>
          <option value>Ya</option>
          <option value>Tidak</option>
        </select>
      </div>
      <div class="form-group"><label>Bersedia di Unit Manapun?</label>
        <select name="bersedia_unit_manapun">
          <option value="" disabled selected>-- Pilih --</option>
          <option value>Bersedia</option>
          <option value>Tidak Bersedia</option>
>>>>>>> 78bc4a7b702164f9f0a82e86ad50b5cdf1d0bd34
        </select>
      </div>

      <div class="form-group"><label>Nomor Surat Permohonan</label><input type="text" name="nomor_surat_permohonan"></div>
      <div class="form-group"><label>Skill</label><input type="text" name="skill"></div>

      <div class="form-group"><label>Durasi PKL</label>
        <select name="durasi" required>
          <option value="">-- Pilih Durasi --</option>
          <option value="2 Bulan">2 Bulan</option>
          <option value="3 Bulan">3 Bulan</option>
          <option value="4 Bulan">4 Bulan</option>
          <option value="5 Bulan">5 Bulan</option>
          <option value="6 Bulan">6 Bulan</option>
        </select>
      </div>

      <div class="form-group"><label>Unit Tujuan</label>
        <select name="unit_id" required>
          <option value="">-- Pilih Unit --</option>
          <?php foreach ($units as $u): ?>
            <option value="<?= $u['id']; ?>"><?= safe($u['nama_unit']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group"><label>No HP</label><input type="text" name="no_hp" required></div>
      <div class="form-full form-group"><label>Alamat</label><textarea name="alamat"></textarea></div>
      <div class="form-group"><label>Tanggal Usulan Mulai</label><input type="date" name="tgl_mulai"></div>
      <div class="form-group"><label>Tanggal Usulan Selesai</label><input type="date" name="tgl_selesai"></div>
<<<<<<< HEAD

      <div class="form-group"><label>Upload Surat Permohonan</label><input type="file" name="upload_surat_permohonan" required></div>
      <div class="form-group"><label>Upload Foto</label><input type="file" name="upload_foto" required></div>
      <div class="form-group"><label>Upload Kartu Identitas</label><input type="file" name="upload_kartu_identitas" required></div>

      <div class="form-full">
        <button type="submit">Daftar Sekarang</button>
        <p class="note">Pastikan data sudah benar sebelum submit.</p>
        <p class="note">Sudah punya akun? 
          <a href="login.php" style="color:#d32f2f; font-weight:600; text-decoration:none;">Login di sini</a>
=======
      <div class="form-group"><label>Upload Surat Permohonan</label><input type="file" name="upload_surat_permohonan"></div>
      <div class="form-group"><label>Upload Foto</label><input type="file" name="upload_foto"></div>
      <div class="form-group"><label>Upload Kartu Pelajar/KTM</label><input type="file" name="upload_kartu_identitas"></div>
      <div class="form-full">
        <button type="submit">Daftar Sekarang</button>
        <p class="note">Pastikan data sudah benar sebelum submit.</p>
        <p class="note">
          Sudah punya akun? <a href="login.php" style="color:#d32f2f; font-weight:600; text-decoration:none;">Login di sini</a>
>>>>>>> 78bc4a7b702164f9f0a82e86ad50b5cdf1d0bd34
        </p>
      </div>
    </form>
  </div>
</div>
</body>
</html>
