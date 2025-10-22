<?php
session_start();
require '../config/database.php';

// ================== Inisialisasi awal ==================
$showSuccess = false;

// ================== Folder Upload ==================
$upload_dir = "../uploads/";
$foto_dir   = $upload_dir . "Foto_daftarpkl/";
$ktm_dir    = $upload_dir . "Foto_Kartuidentitas/";
$surat_dir  = $upload_dir . "Surat_Permohonan/";
foreach ([$upload_dir, $foto_dir, $ktm_dir, $surat_dir] as $dir) {
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

// ================== Helper ==================
function safe($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

function uploadFileUnique($fileKey, $upload_dir, $allowed_types = []) {
  if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return '';

  $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
  if (!empty($allowed_types) && !in_array($ext, $allowed_types)) {
    echo "<script>alert('Format file tidak valid! Hanya diperbolehkan: " . implode(', ', $allowed_types) . "'); history.back();</script>";
    exit;
  }

  $newname = time() . "_" . uniqid() . "." . $ext;
  if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $newname)) return $newname;
  return '';
}

// ================== Ambil daftar unit ==================
$units = [];
$result = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");
if ($result) while ($row = $result->fetch_assoc()) $units[] = $row;

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

  $foto  = uploadFileUnique('upload_foto', $foto_dir, ['jpg', 'jpeg', 'png', 'gif']);
  $ktm   = uploadFileUnique('upload_kartu_identitas', $ktm_dir, ['jpg', 'jpeg', 'png', 'gif']);
  $surat = uploadFileUnique('upload_surat_permohonan', $surat_dir);

  $sql = "INSERT INTO daftar_pkl (
      nama, email, nis_npm, instansi_pendidikan, jurusan,
      ipk_nilai_ratarata, semester, memiliki_laptop, bersedia_unit_manapun,
      nomor_surat_permohonan, skill, durasi, unit_id,
      no_hp, alamat, tgl_mulai, tgl_selesai,
      upload_surat_permohonan, upload_foto, upload_kartu_identitas, status
  ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

  $stmt = $conn->prepare($sql);
  if (!$stmt) die('Prepare failed: ' . $conn->error);

  $types = "sssss" . "d" . "ssssss" . "i" . "ssss" . "ssss";
  $stmt->bind_param(
    $types,
    $nama, $email, $nis_npm, $instansi, $jurusan,
    $ipk,
    $semester, $memiliki_laptop, $bersedia_unit, $no_surat, $skill, $durasi,
    $unit_id,
    $no_hp, $alamat, $tgl_mulai, $tgl_selesai,
    $surat, $foto, $ktm, $status
  );

  if ($stmt->execute()) $showSuccess = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi PKL - Telkom Witel Bekasi Karawang</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  background: #f5f5f5;
  min-height: 100vh;
  padding: 30px 20px;
  position: relative;
  overflow-x: hidden;
}

.header-logo {
  text-align: center;
  margin-bottom: 30px;
  animation: fadeInDown 0.8s ease;
}

.logo-wrapper {
  display: inline-flex;
  align-items: center;
  gap: 30px;
}

.logo-telkom {
  height: 130px;
  object-fit: contain;
}

.logo-akhlak {
  height: 65px;
  object-fit: contain;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  background: white;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.1);
  display: grid;
  grid-template-columns: 380px 1fr;
  position: relative;
  animation: fadeInUp 0.8s ease;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-30px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.info-section {
  background: linear-gradient(180deg, #ED1C24 0%, #C71C1C 100%);
  color: white;
  padding: 50px 35px;
  position: relative;
  overflow: hidden;
}

.info-section::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: rotate 20s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.info-content {
  position: relative;
  z-index: 2;
}

.info-section h2 {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 15px;
  line-height: 1.3;
}

.info-section p {
  font-size: 0.95rem;
  margin-bottom: 20px;
  opacity: 0.95;
  line-height: 1.6;
}

.info-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: white;
  color: #ED1C24;
  padding: 12px 24px;
  border-radius: 12px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  margin-bottom: 25px;
}

.info-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.requirements-box {
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 24px;
  margin-top: 25px;
}

.requirements-box h3 {
  font-size: 1.15rem;
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.requirements-box ul {
  list-style: none;
  padding: 0;
}

.requirements-box li {
  padding: 10px 0;
  padding-left: 28px;
  position: relative;
  font-size: 0.9rem;
  line-height: 1.5;
}

.requirements-box li::before {
  content: '✓';
  position: absolute;
  left: 0;
  background: white;
  color: #ED1C24;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 12px;
}

.contact-info {
  margin-top: 20px;
  padding: 16px;
  background: rgba(255,255,255,0.1);
  border-radius: 12px;
  border-left: 4px solid white;
}

.contact-info strong {
  display: block;
  margin-bottom: 5px;
  font-size: 0.95rem;
}

.form-section {
  padding: 50px 45px;
  background: #FAFAFA;
}

.form-header {
  text-align: center;
  margin-bottom: 35px;
}

.form-header h2 {
  color: #ED1C24;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.form-header p {
  color: #666;
  font-size: 0.95rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

label {
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

label .required {
  color: #ED1C24;
}

input, select, textarea {
  border: 2px solid #E0E0E0;
  border-radius: 12px;
  padding: 14px 16px;
  background: white;
  font-size: 0.95rem;
  font-family: 'Poppins', sans-serif;
  transition: all 0.3s ease;
  color: #333;
}

input:focus, select:focus, textarea:focus {
  border-color: #ED1C24;
  outline: none;
  box-shadow: 0 0 0 4px rgba(237, 28, 36, 0.1);
  background: white;
}

input[type="file"] {
  padding: 12px;
  cursor: pointer;
}

textarea {
  resize: vertical;
  min-height: 100px;
  font-family: 'Poppins', sans-serif;
}

select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  padding-right: 40px;
}

.submit-button {
  grid-column: 1 / -1;
  background: linear-gradient(135deg, #ED1C24 0%, #C71C1C 100%);
  border: none;
  color: white;
  padding: 16px 32px;
  font-size: 1.05rem;
  font-weight: 600;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 16px rgba(237, 28, 36, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 10px;
}

.submit-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(237, 28, 36, 0.4);
}

.submit-button:active {
  transform: translateY(0);
}

.form-footer {
  grid-column: 1 / -1;
  text-align: center;
  margin-top: 20px;
}

.note {
  color: #666;
  font-size: 0.85rem;
  margin-bottom: 15px;
}

.login-link {
  color: #666;
  font-size: 0.9rem;
}

.login-link a {
  color: #ED1C24;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.login-link a:hover {
  text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .container {
    grid-template-columns: 1fr;
  }
  
  .info-section {
    padding: 40px 30px;
  }
  
  .form-section {
    padding: 40px 30px;
  }
}

@media (max-width: 768px) {
  body {
    padding: 20px 15px;
  }
  
  .logo-wrapper {
    flex-direction: row;
    gap: 20px;
    padding: 0;
  }
  
  .logo-telkom {
    height: 55px;
  }
  
  .logo-akhlak {
    height: 55px;
    padding-left: 0;
    border-left: none;
    padding-top: 0;
    border-top: none;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .info-section h2 {
    font-size: 1.5rem;
  }
  
  .form-header h2 {
    font-size: 1.6rem;
  }
  
  .form-section {
    padding: 30px 20px;
  }
  
  .info-section {
    padding: 30px 20px;
  }
}

@media (max-width: 480px) {
  .logo-wrapper {
    padding: 0;
    gap: 15px;
  }
  
  .logo-telkom {
    height: 45px;
  }
  
  .logo-akhlak {
    height: 45px;
  }
  
  .info-section h2 {
    font-size: 1.3rem;
  }
  
  .form-header h2 {
    font-size: 1.4rem;
  }
  
  input, select, textarea {
    padding: 12px 14px;
    font-size: 0.9rem;
  }
  
  .submit-button {
    padding: 14px 24px;
    font-size: 1rem;
  }
}

/* Loading Animation */
.loading-spinner {
  border: 3px solid rgba(255,255,255,0.3);
  border-top: 3px solid white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
</head>
<body>

<div class="container">
  <div class="info-section">
    <div class="info-content">
      <h2>📋 Registrasi PKL<br>Witel Bekasi - Karawang</h2>
      <p>Bergabunglah dengan program Praktik Kerja Lapangan di Telkom Indonesia dan kembangkan pengalaman profesional Anda!</p>
      
      <a href="https://www.canva.com/design/DAFshvFSOu8/8oKHE3loBGx3jD3tgRqOVA/edit?utm_content=DAFshvFSOu8&utm_campaign=designshare&utm_medium=link2&utm_source=sharebutton" target="_blank" class="info-link">
        <span>🔗</span>
        <span>Lihat Informasi Lengkap</span>
      </a>
      
      <div class="requirements-box">
        <h3>📌 Persyaratan PKL</h3>
        <ul>
          <li>Pas foto formal 3x4 (1 lembar)</li>
          <li>Surat permohonan resmi dari institusi pendidikan</li>
          <li>Kartu Tanda Mahasiswa / Kartu Pelajar</li>
          <li>Materai 10.000 rupiah</li>
          <li>Nomor HP Telkomsel terhubung dengan WhatsApp Aktif</li>
          <li>Membawa laptop pribadi</li>
          <li>Bersedia ditempatkan di unit manapun sesuai domisili</li>
        </ul>
      </div>
      
      <div class="contact-info">
        <strong>📞 Informasi & Bantuan:</strong>
        Orient (085316144454)
      </div>
    </div>
  </div>

  <div class="form-section">
    <div class="header-logo" style="text-align: center; margin-bottom: 25px;">
      <div class="logo-wrapper">
        <img src="assets/img/logo_telkom.png" alt="Telkom Indonesia" class="logo-telkom">
        <img src="assets/img/akhlak-removebg.png" alt="AKHLAK" class="logo-akhlak">
      </div>
    </div>
    
    <div class="form-header">
      <h2>Formulir Pendaftaran PKL</h2>
      <p>Lengkapi data dengan benar dan teliti</p>
    </div>
    
    <form id="regForm" method="POST" enctype="multipart/form-data" class="form-grid">
      <div class="form-group">
        <label>Nama Lengkap <span class="required">*</span></label>
        <input type="text" name="nama" required placeholder="Masukkan nama lengkap">
      </div>
      
      <div class="form-group">
        <label>Email <span class="required">*</span></label>
        <input type="email" name="email" required placeholder="contoh@email.com">
      </div>
      
      <div class="form-group">
        <label>NIS/NPM <span class="required">*</span></label>
        <input type="text" name="nis_npm" required placeholder="Nomor induk siswa/mahasiswa">
      </div>
      
      <div class="form-group">
        <label>Instansi Pendidikan <span class="required">*</span></label>
        <input type="text" name="instansi_pendidikan" required placeholder="Nama sekolah/universitas">
      </div>
      
      <div class="form-group">
        <label>Jurusan <span class="required">*</span></label>
        <input type="text" name="jurusan" required placeholder="Program studi/jurusan">
      </div>
      
      <div class="form-group">
        <label>Semester/Tingkat <span class="required">*</span></label>
        <input type="text" name="semester" required placeholder="Contoh: Semester 5">
      </div>
      
      <div class="form-group">
        <label>IPK/Nilai Rata-rata <span class="required">*</span></label>
        <input type="number" step="0.01" name="ipk_nilai_ratarata" required placeholder="Contoh: 3.50/85">
      </div>
      
      <div class="form-group">
        <label>Memiliki Laptop? <span class="required">*</span></label>
        <select name="memiliki_laptop" required>
          <option value="">-- Pilih --</option>
          <option value="Ya">Ya</option>
          <option value="Tidak">Tidak</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Bersedia di Unit Manapun? <span class="required">*</span></label>
        <select name="bersedia_unit_manapun" required>
          <option value="">-- Pilih --</option>
          <option value="Bersedia">Bersedia</option>
          <option value="Tidak Bersedia">Tidak Bersedia</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Unit Tujuan <span class="required">*</span></label>
        <select name="unit_id" required>
          <option value="">-- Pilih Unit --</option>
          <?php foreach ($units as $u): ?>
            <option value="<?= $u['id']; ?>"><?= safe($u['nama_unit']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Durasi PKL <span class="required">*</span></label>
        <select name="durasi" required>
          <option value="">-- Pilih Durasi --</option>
          <option value="2 Bulan">2 Bulan</option>
          <option value="3 Bulan">3 Bulan</option>
          <option value="4 Bulan">4 Bulan</option>
          <option value="5 Bulan">5 Bulan</option>
          <option value="6 Bulan">6 Bulan</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>No HP WhatsApp <span class="required">*</span></label>
        <input type="text" name="no_hp" required placeholder="08xxxxxxxxxx">
      </div>
      
      <div class="form-group">
        <label>Nomor Surat Permohonan <span class="required">*</span></label>
        <input type="text" name="nomor_surat_permohonan" required placeholder="Contoh: 001/PKL/2024">
      </div>
      
      <div class="form-group">
        <label>Tanggal Mulai <span class="required">*</span></label>
        <input type="date" name="tgl_mulai" required>
      </div>
      
      <div class="form-group">
        <label>Tanggal Selesai <span class="required">*</span></label>
        <input type="date" name="tgl_selesai" required>
      </div>
      
      <div class="form-group full-width">
        <label>Skill & Keahlian <span class="required">*</span></label>
        <input type="text" name="skill" required placeholder="Contoh: Microsoft Office, Desain Grafis, Programming">
      </div>
      
      <div class="form-group full-width">
        <label>Alamat Lengkap <span class="required">*</span></label>
        <textarea name="alamat" required placeholder="Masukkan alamat lengkap Anda"></textarea>
      </div>
      
      <div class="form-group">
        <label>Upload Surat Permohonan <span class="required">*</span></label>
        <input type="file" name="upload_surat_permohonan" required>
      </div>
      
      <div class="form-group">
        <label>Upload Foto Formal <span class="required">*</span></label>
        <input type="file" name="upload_foto" accept=".jpg,.jpeg,.png" required>
      </div>
      
      <div class="form-group">
        <label>Upload Kartu Pelajar/KTM <span class="required">*</span></label>
        <input type="file" name="upload_kartu_identitas" accept=".jpg,.jpeg,.png" required>
      </div>

      <button type="submit" class="submit-button">
        <span>🚀</span>
        <span>Kirim Pendaftaran</span>
      </button>
      
      <div class="form-footer">
        <p class="note">⚠️ Pastikan semua data yang diisi sudah benar sebelum mengirim formulir</p>
        <div class="login-link">
          Sudah memiliki akun? <a href="login.php">Login di sini</a>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById("regForm").addEventListener("submit", function(e) {
  e.preventDefault();
  Swal.fire({
    title: "Konfirmasi Pendaftaran",
    text: "Pastikan semua data yang Anda masukkan sudah benar dan sesuai.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Ya, Kirim Sekarang!",
    cancelButtonText: "Periksa Kembali",
    confirmButtonColor: "#ED1C24",
    cancelButtonColor: "#757575",
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) {
      this.submit();
    }
  });
});
</script>

<?php if ($showSuccess): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Pendaftaran Berhasil! 🎉',
  html: '<p style="line-height: 1.6;">Terima kasih telah mendaftar program PKL Telkom Indonesia.<br><br><strong>Tim HC Telkom akan segera menghubungi Anda melalui WhatsApp.</strong><br><br>Pastikan nomor HP Anda aktif dan dapat dihubungi. 📱✨</p>',
  confirmButtonColor: '#ED1C24',
  confirmButtonText: 'OKE',
  allowOutsideClick: false
}).then(() => {
  window.location = 'login.php';
});
</script>
<?php endif; ?>

</body>
</html>