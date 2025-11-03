<?php
// ===========================
// 1️⃣ Koneksi Database
// ===========================
$koneksi = mysqli_connect("localhost", "root", "", "absensi");
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ===========================
// 2️⃣ Query Data
// ===========================

// Total daftar PKL (status pending)
$query_pending = mysqli_query($koneksi, "SELECT COUNT(*) AS total_pending FROM daftar_pkl WHERE status='pending'");
$data_pending = mysqli_fetch_assoc($query_pending);
$total_pending = $data_pending['total_pending'];

// Total peserta aktif (status diterima)
$query_berlangsung = mysqli_query($koneksi, "SELECT COUNT(*) AS total_berlangsung FROM peserta_pkl WHERE status='berlangsung'");
$data_berlangsung = mysqli_fetch_assoc($query_berlangsung);
$total_berlangsung = $data_berlangsung['total_berlangsung'];

// Kehadiran hari ini
$tanggal_hari_ini = date("Y-m-d");
$query_hadir = mysqli_query($koneksi, "SELECT COUNT(*) AS total_hadir FROM absen WHERE tanggal='$tanggal_hari_ini'");
$data_hadir = mysqli_fetch_assoc($query_hadir);
$total_hadir = $data_hadir['total_hadir'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mentor PKL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 210px;
            background: #c41e3a;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header .icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }

        .sidebar-header .icon svg {
            width: 35px;
            height: 35px;
            fill: #c41e3a;
        }

        .sidebar-header h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 11px;
            opacity: 0.9;
        }

        .menu {
            margin-top: 30px;
        }

        .menu-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }

        .menu-item svg {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            fill: white;
        }

        .menu-item span {
            font-size: 14px;
        }

        .logout {
            position: absolute;
            bottom: 20px;
            width: 100%;
        }

        .main-content {
            margin-left: 210px;
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-left h1 {
            font-size: 28px;
            color: #c41e3a;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #666;
            font-size: 14px;
        }

        .header-right img {
            height: 50px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .card.green::before {
            background: #2ecc71;
        }

        .card.blue::before {
            background: #3498db;
        }

        .card.orange::before {
            background: #e67e22;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .card.green .card-icon {
            background: rgba(46, 204, 113, 0.1);
        }

        .card.blue .card-icon {
            background: rgba(52, 152, 219, 0.1);
        }

        .card.orange .card-icon {
            background: rgba(230, 126, 34, 0.1);
        }

        .card-icon svg {
            width: 30px;
            height: 30px;
        }

        .card.green .card-icon svg {
            fill: #2ecc71;
        }

        .card.blue .card-icon svg {
            fill: #3498db;
        }

        .card.orange .card-icon svg {
            fill: #e67e22;
        }

        .card-title {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-value {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }

        .info-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .info-section h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #c41e3a;
        }

        .info-item label {
            font-size: 12px;
            color: #7f8c8d;
            display: block;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .info-item value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 600;
        }

        .profile-card {
    display: flex;
    align-items: center;
    gap: 25px;
    padding: 25px;
    border-radius: 14px;
    background: white;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-top: 30px;
    border-left: 6px solid #c41e3a;
}

.profile-img {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: #fff;
    overflow: hidden;
    border: 3px solid #c41e3a;
}

.profile-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info h2 {
    font-size: 22px;
    margin-bottom: 6px;
    color: #c41e3a;
}

.profile-info p {
    margin: 2px 0;
    color: #555;
    font-size: 14px;
}

.profile-details {
    margin-top: 12px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.detail-box {
    background: #f7f8fa;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid #c41e3a;
}

.detail-box label {
    font-size: 11px;
    text-transform: uppercase;
    color: #888;
    display: block;
}

.detail-box span {
    font-size: 15px;
    color: #2c3e50;
    font-weight: 600;
}


        /* ✅ RESPONSIVE DESIGN */
@media (max-width: 992px) {
    .sidebar {
        width: 180px;
    }

    .main-content {
        margin-left: 180px;
        padding: 20px;
    }

    .header-left h1 {
        font-size: 22px;
    }

    .header-left p {
        font-size: 13px;
    }

    .info-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
}


/* ✅ Tablet & Mobile */
@media (max-width: 768px) {
    body {
        flex-direction: column;
    }

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    .menu {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    .menu-item {
        width: 50%;
        justify-content: center;
        border-left: none;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .menu-item svg {
        margin-right: 6px;
    }

    .main-content {
        margin-left: 0;
        padding: 20px;
    }

    .dashboard-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }

    .card {
        padding: 20px;
    }

    .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}


/* ✅ HP kecil */
@media (max-width: 480px) {

    .menu-item {
        width: 100%;
        font-size: 13px;
        padding: 12px;
    }

    .header-left h1 {
        font-size: 20px;
    }

    .header-left p {
        font-size: 12px;
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .card-value {
        font-size: 28px;
    }

    .card-title {
        font-size: 12px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .info-item {
        padding: 12px;
    }
}

/* === Profil Icon di Sidebar === */
.profile-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid white;
    margin: 0 auto 10px;
    box-shadow: 0 0 10px rgba(255,255,255,0.2);
    transition: transform 0.3s ease;
}

.profile-icon:hover {
    transform: scale(1.05);
}

.profile-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* === Efek Hover Sidebar lebih lembut === */
.menu-item {
    position: relative;
}

.menu-item:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(3px);
}

/* === Responsif Sidebar === */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-bottom: 20px;
    }

    .menu {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    .menu-item {
        width: 45%;
        margin: 5px;
        border-radius: 10px;
        justify-content: center;
    }

    .profile-icon {
        width: 60px;
        height: 60px;
    }
}


    </style>
</head>
<body>
    <!-- =========================
✨ SIDEBAR BARU - TEMA TETAP MERAH TELKOM
========================= -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="profile-icon">
            <img src="../assets/img/profile.png" alt="Mentor">
        </div>
        <h3>Mentor PKL</h3>
        <p>Telkom Witel Bekasi</p>
    </div>

    <div class="menu">
        <a href="dashboard.php" class="menu-item active" style="text-decoration:none; color:inherit;">
            <svg viewBox="0 0 24 24">
                <path d="M13,3V9H21V3M13,21H21V11H13M3,21H11V15H3M3,13H11V3H3V13Z"/>
            </svg>
            <span>Beranda</span>
        </a>

        <a href="daftar_pkl.php" class="menu-item" style="text-decoration:none; color:inherit;">
            <svg viewBox="0 0 24 24">
                <path d="M19,3H14.82C14.4,1.84 13.3,1 12,1C10.7,1 9.6,1.84 9.18,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3Z"/>
            </svg>
            <span>Data Daftar PKL</span>
        </a>

        <a href="peserta_pkl.php" class="menu-item" style="text-decoration:none; color:inherit;">
            <svg viewBox="0 0 24 24">
                <path d="M16,17V19H2V17S2,13 9,13 16,17 16,17M12.5,7.5A3.5,3.5 0 0,1 9,11A3.5,3.5 0 0,1 5.5,7.5A3.5,3.5 0 0,1 9,4A3.5,3.5 0 0,1 12.5,7.5Z"/>
            </svg>
            <span>Data Peserta</span>
        </a>

        <a href="rekap_absensi.php" class="menu-item" style="text-decoration:none; color:inherit;">
            <svg viewBox="0 0 24 24">
                <path d="M21,10.12H14.22L16.96,7.3C14.23,4.6 9.81,4.5 7.08,7.2C4.35,9.91 4.35,14.28 7.08,17C9.81,19.7 14.23,19.7 16.96,17C18.32,15.65 19,14.08 19,12.1H21C21,14.08 20.12,16.65 18.36,18.39C14.85,21.87 9.15,21.87 5.64,18.39Z"/>
            </svg>
            <span>Rekap Absensi</span>
        </a>
    </div>

    <a href="../logout.php" class="logout" style="text-decoration:none; color:inherit;">
        <div class="menu-item">
            <svg viewBox="0 0 24 24">
                <path d="M16,17V14H9V10H16V7L21,12L16,17M14,2A2,2 0 0,1 16,4V6H14V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14Z"/>
            </svg>
            <span>Logout</span>
        </div>
    </a>
</div>


   <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Dashboard Mentor PKL</h1>
                <p>Sistem Manajemen Praktik Kerja Lapangan</p>
            </div>
            <div class="header-right">
                <img src="../assets/img/logo_telkom.png" alt="Telkom Indonesia" 
                    style="height:120px; object-fit:contain;">
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card green">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M19,3H14.82C14.4,1.84 13.3,1 12,1C10.7,1 9.6,1.84 9.18,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M12,3A1,1 0 0,1 13,4A1,1 0 0,1 12,5A1,1 0 0,1 11,4A1,1 0 0,1 12,3Z"/>
                    </svg>
                </div>
                <div class="card-title">Total Daftar PKL</div>
                <div class="card-value"><?php echo $total_pending; ?></div>
            </div>

            <div class="card blue">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M16,17V19H2V17S2,13 9,13 16,17 16,17M12.5,7.5A3.5,3.5 0 0,1 9,11A3.5,3.5 0 0,1 5.5,7.5A3.5,3.5 0 0,1 9,4A3.5,3.5 0 0,1 12.5,7.5M15.94,13A5.32,5.32 0 0,1 18,17V19H22V17S22,13.37 15.94,13M15,4A3.39,3.39 0 0,0 13.07,4.59A5,5 0 0,1 13.07,10.41A3.39,3.39 0 0,0 15,11A3.5,3.5 0 0,0 18.5,7.5A3.5,3.5 0 0,0 15,4Z"/>
                    </svg>
                </div>
                <div class="card-title">Total Peserta Aktif</div>
                <div class="card-value"><?php echo $total_berlangsung; ?></div>
            </div>

            <div class="card orange">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z"/>
                    </svg>
                </div>
                <div class="card-title">Kehadiran Hari Ini</div>
                <div class="card-value"><?php echo $total_hadir; ?></div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-details">
                <div class="detail-box">
                    <label>Nama Mentor</label>
                    <span>Budi Santoso, S.T.</span>
                </div>
                <div class="detail-box">
                    <label>Divisi</label>
                    <span>Network Infrastructure</span>
                </div>
                <div class="detail-box">
                    <label>Lokasi</label>
                    <span>Telkom Witel Bekasi</span>
                </div>
                <div class="detail-box">
                    <label>Email</label>
                    <span>budi.santoso@telkom.co.id</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>