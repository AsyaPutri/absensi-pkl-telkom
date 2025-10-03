<?php
session_start();
require '../config/database.php'; // koneksi database

// Ambil data unit untuk dropdown
$units = [];
$result = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");
while ($row = $result->fetch_assoc()) {
    $units[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ambil semua input
    $nama       = $_POST['nama'];
    $email      = $_POST['email'];
    $nis_npm    = $_POST['nis_npm'];
    $instansi   = $_POST['instansi_pendidikan'];
    $jurusan    = $_POST['jurusan'];
    $semester   = $_POST['semester'];
    $ipk_nilai_ratarata = $_POST['ipk_nilai_ratarata'];
    $memiliki_laptop = $_POST['memiliki_laptop'];
    $bersedia_unit   = $_POST['bersedia_unit_manapun'];
    $no_surat        = $_POST['nomor_surat_permohonan'];
    $skill           = $_POST['skill'];
    $durasi          = $_POST['durasi'];
    $unit_id         = $_POST['unit_id'];
    $no_hp           = $_POST['no_hp'];
    $alamat          = $_POST['alamat'];
    $tgl_mulai       = $_POST['tgl_mulai'];
    $tgl_selesai     = $_POST['tgl_selesai'];

    // upload file
    $dir = "uploads/";
    if (!is_dir($dir)) mkdir($dir);

    $upload_surat = $_FILES['upload_surat_permohonan']['name'];
    $upload_foto  = $_FILES['upload_foto']['name'];
    $upload_ktp   = $_FILES['upload_kartu_identitas']['name'];

    move_uploaded_file($_FILES['upload_surat_permohonan']['tmp_name'], $dir.$upload_surat);
    move_uploaded_file($_FILES['upload_foto']['tmp_name'], $dir.$upload_foto);
    move_uploaded_file($_FILES['upload_kartu_identitas']['tmp_name'], $dir.$upload_ktp);

    $sql = "INSERT INTO daftar_pkl 
(nama,email,nis_npm,instansi_pendidikan,jurusan,tingkat_kelas,semester,
 memiliki_laptop,bersedia_unit_manapun,nomor_surat_permohonan,skill,durasi,
 unit_id,no_hp,alamat,tgl_mulai,tgl_selesai,
 upload_surat_permohonan,upload_foto,upload_kartu_identitas,ipk_nilai_ratarata,status) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);
$status = 'pending';
$stmt->bind_param("ssssssssssssssssssssds",
    $nama,$email,$nis_npm,$instansi,$jurusan,$tingkat,$semester,
    $memiliki_laptop,$bersedia_unit,$no_surat,$skill,$durasi,
    $unit_id,$no_hp,$alamat,$tgl_mulai,$tgl_selesai,
    $upload_surat,$upload_foto,$upload_ktp,$ipk_nilai_ratarata,$status
);


    if ($stmt->execute()) {
        echo "<script>alert('Registrasi berhasil!'); window.location='register.php';</script>";
    } else {
        echo "<script>alert('Gagal registrasi: ".$stmt->error."');</script>";
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
      padding: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background: linear-gradient(to right, #d32f2f, #f44336);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .container {
      background: #fff;
      width: 90%;
      max-width: 1000px;
      border-radius: 15px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.2);
      display: flex;
      overflow: hidden;
    }
    .info-section {
      flex: 1;
      background: #fff5f5;
      padding: 30px;
      border-right: 3px solid #f44336;
    }
    .info-section h2 {
      color: #d32f2f;
      margin-bottom: 15px;
    }
    .info-section ul { padding-left: 20px; }
    .form-section {
      flex: 2;
      padding: 30px 40px;
    }
    .form-section h2 {
      margin-top: 0;
      margin-bottom: 20px;
      color: #d32f2f;
      text-align: center;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .form-group { display: flex; flex-direction: column; }
    label {
      font-weight: 600;
      margin-bottom: 6px;
      color: #444;
    }
    input, select, textarea {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
      transition: 0.2s;
    }
    input:focus, select:focus, textarea:focus {
      border-color: #e53935;
      outline: none;
      box-shadow: 0 0 5px rgba(229,57,53,0.3);
    }
    textarea { resize: vertical; min-height: 60px; }
    .form-full { grid-column: 1 / 3; }
    button {
      background: #e53935;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      margin-top: 20px;
      width: 100%;
      transition: 0.2s;
    }
    button:hover { background: #c62828; }
    .note { font-size: 13px; color: #777; margin-top: 10px; }
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
  <!-- Bagian Informasi -->
  <div class="info-section">
    <h2>Registrasi <em>Internship</em> Witel Bekasi - Karawang</h2>
    <p>Pendaftaran <em>Internship</em> di Witel Bekasi - Karawang</p>
    <p>Harap dilihat terlebih dahulu:  
      <a href="https://www.canva.com/design/DAFshvFSoU8/8oKHE3loBGx3jD3tgRq0VA" target="_blank">
        Link Informasi Pendaftaran
      </a>
    </p>

    <p><strong>Syarat PKL:</strong></p>
    <ul>
      <li>Pas foto 3x4 = 1 lembar</li>
      <li>Surat permohonan dari sekolah / kampus</li>
      <li>Materai 10K</li>
      <li>Nomor HP Telkomsel</li>
      <li>Mempunyai laptop</li>
    </ul>

    <p><em>Catatan:</em> Bersedia ditempatkan di unit manapun sesuai domisili.</p>
    <p>Info lebih lanjut: <strong>Orient (62 85316144454)</strong></p>
  </div>

  <!-- Bagian Form -->
  <div class="form-section">
    <h2>Form Registrasi PKL</h2>
    <form method="POST" enctype="multipart/form-data" class="form-grid">
      <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-group"><label>NIM / NIS</label><input type="text" name="nis_npm" required></div>
      <div class="form-group"><label>Instansi Pendidikan</label><input type="text" name="instansi_pendidikan" required></div>
      <div class="form-group"><label>Jurusan</label><input type="text" name="jurusan" required></div>
      <div class="form-group"><label>Semester/Tingkat Kelas</label><input type="text" name="semester"></div>
      <div class="form-group">
  <label>IPK / Nilai Rata-rata</label>
  <input type="number" step="0.01" min="0" max="4" name="ipk_nilai_ratarata" placeholder="contoh: 3.25" required>
</div>

      <div class="form-group"><label>Memiliki Laptop?</label>
        <select name="memiliki_laptop"><option>Ya</option><option>Tidak</option></select>
      </div>
      <div class="form-group"><label>Bersedia di Unit Manapun?</label>
        <select name="bersedia_unit_manapun"><option>Bersedia</option><option>Tidak Bersedia</option></select>
      </div>
      <div class="form-group"><label>Nomor Surat Permohonan</label><input type="text" name="nomor_surat_permohonan"></div>
      <div class="form-group"><label>Skill</label><input type="text" name="skill"></div>
      <div class="form-group">
        <label>Durasi PKL</label>
        <select name="durasi" required>
            <option value="">-- Pilih Durasi --</option>
            <option value="2 Bulan">2 Bulan</option>
            <option value="3 Bulan">3 Bulan</option>
            <option value="4 Bulan">4 Bulan</option>
            <option value="5 Bulan">5 Bulan</option>
            <option value="6 Bulan">6 Bulan</option>
        </select>
      </div>
      <!-- 🔽 Unit dropdown -->
      <div class="form-group">
        <label>Unit</label>
        <select name="unit_id" required>
            <option value="">-- Pilih Unit --</option>
            <?php foreach ($units as $u): ?>
                <option value="<?= $u['id']; ?>"><?= htmlspecialchars($u['nama_unit']); ?></option>
            <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>No HP</label><input type="text" name="no_hp"></div>
      <div class="form-full form-group"><label>Alamat</label><textarea name="alamat"></textarea></div>
      <div class="form-group"><label>Tanggal Usulan Mulai</label><input type="date" name="tgl_mulai"></div>
      <div class="form-group"><label>Tanggal Usulan Selesai</label><input type="date" name="tgl_selesai"></div>
      <div class="form-group"><label>Upload Surat Permohonan</label><input type="file" name="upload_surat_permohonan"></div>
      <div class="form-group"><label>Upload Foto</label><input type="file" name="upload_foto"></div>
      <div class="form-group"><label>Upload Kartu Identitas</label><input type="file" name="upload_kartu_identitas"></div>

      <div class="form-full">
        <button type="submit">Daftar Sekarang</button>
        <p class="note">Pastikan data sudah benar sebelum submit.</p>
      </div>
    </form>
  </div>
</div>

</body>
</html>
