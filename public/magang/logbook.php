<?php
session_start();
require '../../config/database.php'; // file koneksi DB

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Ambil data profil user (join users + peserta_pkl)
$queryUser = mysqli_query($conn, "
    SELECT 
        p.nama,
        p.instansi_pendidikan AS instansi, 
        p.unit 
    FROM peserta_pkl p
    INNER JOIN users u ON u.id = p.user_id
    WHERE u.id = '$userId'
");

if (!$queryUser) {
    die("Query User Error: " . mysqli_error($conn));
}
$userProfile = mysqli_fetch_assoc($queryUser);

// Ambil data logbook/absen
$queryLogbook = mysqli_query($conn, "
    SELECT 
        id,
        tanggal,
        aktivitas_masuk,
        kendala_masuk,
        aktivitas_keluar,
        kendala_keluar
    FROM absen
    WHERE user_id = '$userId'
    ORDER BY tanggal ASC
");

if (!$queryLogbook) {
    die("Query Logbook Error: " . mysqli_error($conn));
}

$logbookData = [];
while ($row = mysqli_fetch_assoc($queryLogbook)) {
    $logbookData[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook Aktivitas - PT Telkom Indonesia</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root {
            --telkom-red: #e60012;
            --telkom-dark-red: #c40010;
            --telkom-light-red: #ff1a2e;
            --telkom-gray: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #e60012 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(230, 0, 18, 0.1);
            backdrop-filter: blur(10px);
            margin: 20px 0;
            padding: 30px;
            border: 2px solid rgba(230, 0, 18, 0.1);
        }
        
        /* Header Section - Putih dengan logo gambar */
        .header-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(230, 0, 18, 0.1);
            box-shadow: 0 8px 25px rgba(230, 0, 18, 0.08);
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(230, 0, 18, 0.02) 10px,
                rgba(230, 0, 18, 0.02) 20px
            );
            animation: slide 20s linear infinite;
        }
        
        @keyframes slide {
            0% { transform: translateX(-50px); }
            100% { transform: translateX(50px); }
        }
        
        .header-section .content {
            position: relative;
            z-index: 2;
        }
        
        .header-section h1 {
            margin: 0;
            font-weight: 700;
            color: #333;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            font-size: 2.2rem;
        }
        
        .header-section p {
            margin: 10px 0 0 0;
            opacity: 0.8;
            font-size: 1.1rem;
            color: #666;
        }
        
        /* Logo Telkom dari gambar */
        .telkom-logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            gap: 0;
        }
        
        .telkom-logo-img {
            height: 150px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
            transition: all 0.3s ease;
        }
        
        .telkom-logo-img:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 6px 12px rgba(230, 0, 18, 0.2));
        }

        /* Profile Section */
        .profile-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid rgba(230, 0, 18, 0.1);
            box-shadow: 0 8px 25px rgba(230, 0, 18, 0.08);
        }

        .profile-section h5 {
            color: var(--telkom-red);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .profile-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: rgba(230, 0, 18, 0.05);
            border-radius: 8px;
            border-left: 4px solid var(--telkom-red);
        }

        .profile-item strong {
            color: var(--telkom-red);
            min-width: 100px;
            margin-right: 10px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(230, 0, 18, 0.08);
            overflow-x: auto;
            border: 1px solid rgba(230, 0, 18, 0.1);
        }
        
        .table {
            margin: 0;
            border-radius: 10px;
            overflow: hidden;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            background: linear-gradient(45deg, var(--telkom-red), var(--telkom-dark-red));
            color: white;
            border: none;
            padding: 20px 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-size: 0.85rem;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        
        .table thead th:first-child {
            border-top-left-radius: 10px;
        }
        
        .table thead th:last-child {
            border-top-right-radius: 10px;
        }
        
        .table tbody td {
            padding: 18px 15px;
            vertical-align: top;
            border: 1px solid rgba(230, 0, 18, 0.08);
            border-top: none;
            transition: all 0.3s ease;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            line-height: 1.6;
            font-size: 0.9rem;
        }
        
        .table tbody tr:hover {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 0, 18, 0.12);
        }
        
        .table tbody tr:nth-child(even) {
            background-color: rgba(248, 249, 250, 0.6);
        }
        
        .table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }
        
        .table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }
        
        /* Pengaturan lebar kolom yang lebih proporsional dan rapi */
        .col-no { 
            width: 60px; 
            min-width: 60px;
            max-width: 60px;
        }
        .col-date { 
            width: 120px; 
            min-width: 120px;
            max-width: 120px;
        }
        .col-activity-in { 
            width: 35%; 
            min-width: 250px;
        }
        .col-constraint-in { 
            width: 20%; 
            min-width: 180px;
        }
        .col-activity-out { 
            width: 35%; 
            min-width: 250px;
        }
        .col-constraint-out { 
            width: 10%; 
            min-width: 120px;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-info {
            background: linear-gradient(45deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }
        
        .badge-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .badge-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }
        
        .badge-danger {
            background: linear-gradient(45deg, var(--telkom-red), var(--telkom-dark-red));
            color: white;
            box-shadow: 0 2px 8px rgba(230, 0, 18, 0.3);
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8);
        }
        
        .status-masuk { background-color: #28a745; }
        .status-keluar { background-color: var(--telkom-red); }
        .status-kendala { background-color: #ffc107; }
        
        .activity-text {
            font-size: 0.9rem;
            line-height: 1.5;
            color: #333;
            text-align: justify;
            text-justify: inter-word;
        }
        
        .constraint-text {
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .date-info {
            text-align: center;
            padding: 5px;
        }
        
        .date-main {
            font-weight: bold;
            color: #333;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }
        
        .date-day {
            color: #666;
            font-size: 0.75rem;
            font-style: italic;
        }
        
        .no-data-cell {
            color: #999;
            font-style: italic;
            font-size: 0.85rem;
            text-align: center;
        }
        
        .constraint-ok {
            color: #28a745;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .constraint-issue {
            color: #dc3545;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #fff 0%, rgba(230, 0, 18, 0.05) 100%);
            border-radius: 12px;
            border-left: 5px solid var(--telkom-red);
            box-shadow: 0 4px 15px rgba(230, 0, 18, 0.08);
        }
        
        .table-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-telkom {
            background: linear-gradient(45deg, var(--telkom-red), var(--telkom-dark-red));
            border: none;
            color: white;
            border-radius: 10px;
            padding: 10px 24px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(230, 0, 18, 0.2);
        }
        
        .btn-telkom:hover {
            background: linear-gradient(45deg, var(--telkom-dark-red), var(--telkom-red));
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(230, 0, 18, 0.4);
            color: white;
        }
        
        .number-cell {
            text-align: center;
            font-weight: bold;
            font-size: 1rem;
            color: var(--telkom-red);
            background: rgba(230, 0, 18, 0.05);
            border-radius: 8px;
            margin: 5px;
            padding: 8px;
        }
        
        @media (max-width: 1400px) {
            .col-activity-in,
            .col-activity-out {
                width: 32%;
                min-width: 220px;
            }
            .col-constraint-in {
                width: 18%;
                min-width: 160px;
            }
            .col-constraint-out {
                width: 12%;
                min-width: 110px;
            }
        }
        
        @media (max-width: 1200px) {
            .main-container {
                padding: 25px;
            }
            
            .table-container {
                padding: 20px;
            }
            
            .col-activity-in,
            .col-activity-out {
                width: 30%;
                min-width: 200px;
            }
            .col-constraint-in {
                width: 20%;
                min-width: 150px;
            }
            .col-constraint-out {
                width: 15%;
                min-width: 100px;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                padding: 20px;
            }
            
            .header-section {
                padding: 20px;
            }
            
            .header-section h1 {
                font-size: 1.8rem;
            }
            
            .table-container {
                padding: 15px;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-controls {
                justify-content: center;
            }
            
            .table thead th {
                font-size: 0.7rem;
                padding: 15px 10px;
            }
            
            .table tbody td {
                padding: 15px 10px;
                font-size: 0.8rem;
            }
            
            .col-no { 
                width: 50px; 
                min-width: 50px;
            }
            .col-date { 
                width: 100px; 
                min-width: 100px;
            }
            .col-activity-in { 
                width: 28%; 
                min-width: 180px;
            }
            .col-constraint-in { 
                width: 22%; 
                min-width: 140px;
            }
            .col-activity-out { 
                width: 28%; 
                min-width: 180px;
            }
            .col-constraint-out { 
                width: 18%; 
                min-width: 100px;
            }
            
            .telkom-logo-img {
                height: 60px;
            }

            .profile-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header -->
            <div class="header-section">
                <div class="content">
                    <div class="telkom-logo-container">
                        <img src="../assets/img/logo_telkom.png" class="telkom-logo-img">
                    </div>
                    <h1><i class="fas fa-clipboard-list me-3" style="color: var(--telkom-red);"></i>Logbook Aktivitas</h1>
                    <p>Sistem Pencatatan Aktivitas Harian</p>
                </div>
            </div>

            <!-- Profile -->
            <div class="profile-section">
                <h5><i class="fas fa-user-circle"></i> Informasi Peserta</h5>
                <div class="profile-info">
                    <div class="profile-item"><strong>Nama:</strong> <span id="profileName"><?= $userProfile['nama'] ?? '-' ?></span></div>
                    <div class="profile-item"><strong>Asal Instansi:</strong> <span id="profileInstitution"><?= $userProfile['instansi'] ?? '-' ?></span></div>
                    <div class="profile-item"><strong>Unit Kerja:</strong> <span id="profileUnit"><?= $userProfile['unit'] ?? '-' ?></span></div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0 text-dark"><i class="fas fa-table me-2 text-danger"></i> Data Logbook Aktivitas</h5>
                    <div class="header-controls">
                        <button class="btn btn-telkom btn-sm" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                        <span class="badge badge-info" id="totalRecords">
                            <i class="fas fa-database me-1"></i>Total: <?= count($logbookData) ?> Records
                        </span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th class="col-date">Tanggal</th>
                                <th class="col-activity-in">Aktivitas Masuk</th>
                                <th class="col-constraint-in">Kendala Masuk</th>
                                <th class="col-activity-out">Aktivitas Keluar</th>
                                <th class="col-constraint-out">Kendala Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logbookData)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                        <h5 class="text-muted">Tidak ada data yang ditemukan</h5>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logbookData as $i => $log): ?>
                                    <tr>
                                        <td><div class="number-cell"><?= $i + 1 ?></div></td>
                                        <td class="date-info">
                                            <div class="date-main"><?= date('d-m-Y', strtotime($log['tanggal'])) ?></div>
                                            <div class="date-day"><?= strftime('%A', strtotime($log['tanggal'])) ?></div>
                                        </td>
                                        <td><div class="activity-text"><span class="status-dot status-masuk"></span><?= $log['aktivitas_masuk'] ?: 'Tidak ada aktivitas' ?></div></td>
                                        <td><div class="constraint-text"><?= $log['kendala_masuk'] ?: '<div class="constraint-ok"><i class="fas fa-check-circle me-1"></i>Tidak ada kendala</div>' ?></div></td>
                                        <td><div class="activity-text"><span class="status-dot status-keluar"></span><?= $log['aktivitas_keluar'] ?: '<span class="no-data-cell">Belum ada aktivitas</span>' ?></div></td>
                                        <td><div class="constraint-text"><?= $log['kendala_keluar'] ?: '<div class="constraint-ok"><i class="fas fa-check-circle me-1"></i>OK</div>' ?></div></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script>
function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape', 'mm', 'a4');

    doc.setFontSize(16);
    doc.text("Logbook Aktivitas - PT Telkom Indonesia", 14, 20);

    doc.setFontSize(12);
    doc.text("Informasi Peserta", 14, 35);
    doc.text("Nama: <?= $userProfile['nama'] ?? '-' ?>", 14, 42);
    doc.text("Asal Instansi: <?= $userProfile['instansi'] ?? '-' ?>", 14, 48);
    doc.text("Unit Kerja: <?= $userProfile['unit'] ?? '-' ?>", 14, 54);

    const tableData = [
        <?php foreach ($logbookData as $i => $log): ?>
        [
            "<?= $i+1 ?>",
            "<?= date('d-m-Y', strtotime($log['tanggal'])) ?>",
            "<?= substr(addslashes($log['aktivitas_masuk']),0,100) ?>",
            "<?= substr(addslashes($log['kendala_masuk']),0,50) ?>",
            "<?= substr(addslashes($log['aktivitas_keluar']),0,100) ?>",
            "<?= substr(addslashes($log['kendala_keluar']),0,50) ?>"
        ],
        <?php endforeach; ?>
    ];

    doc.autoTable({
        head: [['No', 'Tanggal', 'Aktivitas Masuk', 'Kendala Masuk', 'Aktivitas Keluar', 'Kendala Keluar']],
        body: tableData,
        startY: 80,
        styles: { fontSize: 8 },
        headStyles: { fillColor: [230, 0, 18] }
    });

    doc.save("Logbook_Peserta.pdf");
}
</script>
</body>
</html>