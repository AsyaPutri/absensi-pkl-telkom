<?php
session_start();

// ===========================
// 🔒 CEK LOGIN ROLE MENTOR
// ===========================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit;
}

// ===========================
// 🔌 Koneksi Database (gunakan config)
// ===========================
include "../../config/database.php";

// Pastikan koneksi variabelnya sama
$koneksi = $conn;

// ===========================
// 📋 Ambil Data User Login (tabel users)
// ===========================
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$user_id'");
$user_data = mysqli_fetch_assoc($user_query);

$email_user = $user_data['email'] ?? "-";
$nama_user = $user_data['nama'] ?? "Mentor Telkom";

// ===========================
// 📋 Ambil Data Lengkap dari cp_karyawan (join unit_pkl)
// ===========================
$mentor_query = mysqli_query($koneksi, "
    SELECT 
        cp_karyawan.*, 
        unit_pkl.nama_unit 
    FROM cp_karyawan
    LEFT JOIN unit_pkl ON cp_karyawan.unit_id = unit_pkl.id
    WHERE cp_karyawan.email = '$email_user'
");
$mentor_data = mysqli_fetch_assoc($mentor_query);

$nik = $mentor_data['nik'] ?? "-";
$nama_karyawan = $mentor_data['nama_karyawan'] ?? $nama_user;
$posisi = $mentor_data['posisi'] ?? "-";
$no_hp = $mentor_data['no_telepon'] ?? "-";
$email = $mentor_data['email'] ?? $email_user;
$unit = $mentor_data['nama_unit'] ?? "-";

// ===========================
// 📊 Hitung Data Statistik
// ===========================
function ambilJumlah($koneksi, $sql, $alias) {
    $result = mysqli_query($koneksi, $sql);
    if (!$result) return 0;
    $data = mysqli_fetch_assoc($result);
    return $data[$alias] ?? 0;
}

$total_pending = ambilJumlah($koneksi, "SELECT COUNT(*) AS total_pending FROM daftar_pkl WHERE status='pending'", "total_pending");
$total_berlangsung = ambilJumlah($koneksi, "SELECT COUNT(*) AS total_berlangsung FROM peserta_pkl WHERE status='berlangsung'", "total_berlangsung");
$tanggal_hari_ini = date("Y-m-d");
$total_hadir = ambilJumlah($koneksi, "SELECT COUNT(*) AS total_hadir FROM absen WHERE tanggal='$tanggal_hari_ini'", "total_hadir");
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Mentor Internship</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    * {margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif;}

    body {
      background: linear-gradient(135deg, #ffffff, #f9f9f9);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
    }

    /* HEADER */
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      max-width: 1100px;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 20px;
      animation: fadeInDown 1s ease;
    }
    .logo-area {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .logo-area img {
      height: 100px;
      object-fit: contain;
      animation: zoomIn 1s ease;
    }
    .header-text h1 {
      color: #c41e3a;
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 5px;
    }
    .header-text p { color: #555; font-size: 14px; }

    .logout-btn {
      background: #c41e3a;
      color: #fff;
      padding: 10px 22px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
    }
    .logout-btn:hover { background: #a3152d; transform: scale(1.05); }

    /* === DATA DIRI MENTOR === */
    .profile-box {
      width: 100%;
      max-width: 950px;
      background: white;
      border-radius: 16px;
      padding: 25px 30px;
      margin-bottom: 35px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      animation: fadeInUp 1s ease;
      position: relative;
      overflow: hidden;
    }
    .profile-box::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 5px;
      background: linear-gradient(90deg, #c41e3a, #ff4d4d);
    }
    .profile-box h2 {
      color: #c41e3a;
      margin-bottom: 20px;
      font-size: 22px;
      font-weight: 700;
    }

    .profile-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 15px;
    }
    .profile-item {
      background: #fff5f5;
      border-radius: 10px;
      padding: 14px 16px;
      border: 1px solid #ffe2e2;
      transition: all 0.3s ease;
    }
    .profile-item:hover {
      background: #fff0f0;
      transform: translateY(-2px);
      box-shadow: 0 3px 8px rgba(196,30,58,0.1);
    }
    .profile-item span {
      color: #777;
      font-size: 12px;
      display: block;
      margin-bottom: 4px;
    }
    .profile-item strong {
      color: #c41e3a;
      font-size: 15px;
      font-weight: 600;
    }

    /* DASHBOARD GRID */
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 25px;
      width: 100%;
      max-width: 950px;
      animation: fadeInUp 1s ease;
    }

    .card {
      background: white;
      border-radius: 14px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      border-top: 5px solid #c41e3a;
      transition: all 0.3s ease;
      cursor: pointer;
      opacity: 0;
      transform: translateY(30px);
      animation: slideUp 0.8s forwards;
    }
    .card:nth-child(1){animation-delay:0.2s;}
    .card:nth-child(2){animation-delay:0.4s;}
    .card:nth-child(3){animation-delay:0.6s;}
    .card:hover{transform:scale(1.05);box-shadow:0 10px 25px rgba(0,0,0,0.12);}
    .card .icon{
      width:60px; height:60px; margin:0 auto 15px;
      border-radius:50%; background:#fff5f5;
      display:flex; align-items:center; justify-content:center;
    }
    .card:hover .icon{background:#c41e3a;}
    .card .icon svg{width:32px; height:32px; fill:#c41e3a; transition:0.3s;}
    .card:hover .icon svg{fill:white;}
    .card-title{font-size:13px; color:#777; text-transform:uppercase; margin-bottom:6px;}
    .card-value{font-size:34px; font-weight:700; color:#c41e3a;}

    footer {margin-top:40px; color:#888; font-size:13px; text-align:center;}

    @keyframes fadeInDown {from{opacity:0; transform:translateY(-20px);} to{opacity:1; transform:translateY(0);} }
    @keyframes fadeInUp {from{opacity:0; transform:translateY(20px);} to{opacity:1; transform:translateY(0);} }
    @keyframes slideUp {to{opacity:1; transform:translateY(0);} }
    @keyframes zoomIn {from{opacity:0; transform:scale(0.9);} to{opacity:1; transform:scale(1);} }

    @media (max-width: 768px){
      header{flex-direction:column; align-items:center; gap:10px;}
      .header-text{text-align:center;}
      .logo-area{justify-content:center;}
      .logo-area img{height:80px;}
    }
  </style>
</head>
<body>

  <header>
    <div class="logo-area">
      <img src="../assets/img/instepterbaru.png" alt="Telkom Logo" />
      <div class="header-text">
        <h1>Dashboard Mentor Internship</h1>
        <p>Sistem Monitoring Internship | Telkom Witel Bekasi - Karawang</p>
      </div>
    </div>
    <a href="../logout.php" class="logout-btn">Logout</a>
  </header>

  <!-- PROFIL MENTOR -->
  <div class="profile-box">
    <h2>Data Diri Mentor</h2>
    <div class="profile-grid">
      <div class="profile-item"><span>Nama Lengkap</span><strong><?= htmlspecialchars($nama_karyawan); ?></strong></div>
      <div class="profile-item"><span>Email</span><strong><?= htmlspecialchars($email); ?></strong></div>
      <div class="profile-item"><span>NIK</span><strong><?= htmlspecialchars($nik); ?></strong></div>
      <div class="profile-item"><span>No. HP</span><strong><?= htmlspecialchars($no_hp); ?></strong></div>
      <div class="profile-item"><span>Posisi</span><strong><?= htmlspecialchars($posisi); ?></strong></div>
      <div class="profile-item"><span>Unit Kerja</span><strong><?= htmlspecialchars($unit !== '-' ? $unit : 'Belum Terdaftar'); ?></strong></div>
    </div>
  </div>

  <!-- CARD STATISTIK -->
  <div class="dashboard-grid">
    <div class="card" onclick="location.href='daftar_pkl.php'">
      <div class="icon"><svg viewBox="0 0 24 24"><path d="M19,3H14.82C14.4,1.84 13.3,1 12,1C10.7,1 9.6,1.84 9.18,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3Z"/></svg></div>
      <div class="card-title">Total Daftar Internship</div>
      <div class="card-value"><?= $total_pending; ?></div>
    </div>

    <div class="card" onclick="location.href='peserta_pkl.php'">
      <div class="icon"><svg viewBox="0 0 24 24"><path d="M16,17V19H2V17S2,13 9,13 16,17 16,17M12.5,7.5A3.5,3.5 0 0,1 9,11A3.5,3.5 0 0,1 5.5,7.5A3.5,3.5 0 0,1 9,4A3.5,3.5 0 0,1 12.5,7.5Z"/></svg></div>
      <div class="card-title">Peserta Aktif</div>
      <div class="card-value"><?= $total_berlangsung; ?></div>
    </div>

    <div class="card" onclick="location.href='rekap_absensi.php'">
      <div class="icon"><svg viewBox="0 0 24 24"><path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z"/></svg></div>
      <div class="card-title">Kehadiran Hari Ini</div>
      <div class="card-value"><?= $total_hadir; ?></div>
    </div>
  </div>

  <footer>© 2025 Telkom Indonesia | Witel Bekasi - Karawang</footer>
</body>
</html>
