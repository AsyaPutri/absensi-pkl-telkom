<?php
session_start();
require '../../config/database.php';

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
        up.nama_unit AS unit
    FROM peserta_pkl p
    INNER JOIN users u ON u.id = p.user_id
    LEFT JOIN unit_pkl up ON p.unit_id = up.id
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
            --telkom-dark-red: #b80000;
            --telkom-gray: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 60%, #ffe5e5 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(230, 0, 18, 0.15);
            margin: 30px auto;
            padding: 30px;
            border: 2px solid rgba(230, 0, 18, 0.1);
            max-width: 1300px;
        }

        /* Header */
        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .telkom-logo-img {
            height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .header-section h1 {
            color: var(--telkom-red);
            font-weight: 700;
        }

        .header-section p {
            color: #555;
            margin-top: 5px;
        }

        /* Profile Section */
        .profile-section {
            background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
            border: 1.5px solid rgba(230, 0, 18, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(230, 0, 18, 0.08);
        }

        .profile-section h5 {
            color: var(--telkom-red);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .profile-item {
            padding: 10px;
            background: rgba(230, 0, 18, 0.05);
            border-left: 4px solid var(--telkom-red);
            border-radius: 8px;
        }

        .profile-item strong {
            color: var(--telkom-red);
        }

        /* ===================== TELKOM THEMED TABLE STYLE ===================== */
        .table-container {
            background: linear-gradient(180deg, #ffffff 0%, #fff4f4 100%);
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(230, 0, 18, 0.15);
            border: 1.5px solid rgba(230, 0, 18, 0.15);
            overflow-x: auto;
            transition: all 0.3s ease-in-out;
        }

        .table-container:hover {
            box-shadow: 0 12px 35px rgba(230, 0, 18, 0.25);
            transform: translateY(-2px);
        }

        .table-header {
            background: linear-gradient(90deg, #e60012, #b80000);
            color: #fff;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(230, 0, 18, 0.25);
        }

        .table-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .table thead th {
            background: linear-gradient(90deg, #ff0028, #b80000);
            color: #fff;
            text-transform: uppercase;
            font-weight: 700;
            border: none;
            padding: 15px 10px;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            text-align: center;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.25s ease-in-out;
        }

        .table tbody tr:hover {
            background: rgba(230, 0, 18, 0.07);
            transform: scale(1.01);
            box-shadow: 0 6px 15px rgba(230, 0, 18, 0.1);
        }

        .table tbody td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(230, 0, 18, 0.1);
            color: #333;
            vertical-align: top;
            background-color: #fff;
        }

        .table tbody tr:nth-child(even) td {
            background-color: #fff9f9;
        }

        .number-cell {
            text-align: center;
            font-weight: bold;
            color: #e60012;
        }

        .activity-text {
            font-size: 0.9rem;
            color: #222;
            line-height: 1.6;
            border-left: 3px solid #e60012;
            padding-left: 8px;
        }

        .constraint-text {
            font-size: 0.85rem;
            color: #555;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .status-masuk { background-color: #28a745; }
        .status-keluar { background-color: #e60012; }

        .btn-telkom {
            background: linear-gradient(90deg, #e60012, #b80000);
            border: none;
            color: #fff;
            border-radius: 10px;
            padding: 10px 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(230, 0, 18, 0.3);
            transition: all 0.3s ease;
        }

        .btn-telkom:hover {
            background: linear-gradient(90deg, #b80000, #ff0028);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(230, 0, 18, 0.4);
        }

        .badge-info {
            background: #fff;
            color: #e60012;
            border: 1.5px solid #e60012;
            font-weight: 600;
            border-radius: 20px;
            padding: 6px 14px;
            box-shadow: 0 2px 8px rgba(230, 0, 18, 0.15);
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="main-container">
        <!-- Header -->
        <div class="header-section">
            <img src="../assets/img/logo_telkom.png" class="telkom-logo-img">
            <h1><i class="fas fa-clipboard-list me-2"></i> Logbook Aktivitas</h1>
            <p>Pencatatan Aktivitas Harian | Internship Telkom Witel Bekasi - Karawang</p>
        </div>

        <!-- Profile -->
        <div class="profile-section">
            <h5><i class="fas fa-user-circle me-2"></i> Informasi Peserta</h5>
            <div class="profile-info">
                <div class="profile-item"><strong>Nama:</strong> <?= $userProfile['nama'] ?? '-' ?></div>
                <div class="profile-item"><strong>Asal Instansi:</strong> <?= $userProfile['instansi'] ?? '-' ?></div>
                <div class="profile-item"><strong>Unit Kerja:</strong> <?= $userProfile['unit'] ?? '-' ?></div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <h5><i class="fas fa-table me-2"></i> Data Logbook Aktivitas</h5>
                <div class="header-controls">
                    <button class="btn btn-telkom btn-sm" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf me-2"></i> Export PDF
                    </button>
                    <span class="badge badge-info">
                        <i class="fas fa-database me-1"></i> Total: <?= count($logbookData) ?> Records
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Aktivitas Masuk</th>
                        <th>Kendala Masuk</th>
                        <th>Aktivitas Keluar</th>
                        <th>Kendala Keluar</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($logbookData)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Tidak ada data yang ditemukan</h5>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logbookData as $i => $log): ?>
                            <tr>
                                <td class="number-cell"><?= $i + 1 ?></td>
                                <td><?= date('d-m-Y', strtotime($log['tanggal'])) ?></td>
                                <td><div class="activity-text"><span class="status-dot status-masuk"></span><?= $log['aktivitas_masuk'] ?: 'Tidak ada aktivitas' ?></div></td>
                                <td><div class="constraint-text"><?= $log['kendala_masuk'] ?: '<i class="text-success">Tidak ada kendala</i>' ?></div></td>
                                <td><div class="activity-text"><span class="status-dot status-keluar"></span><?= $log['aktivitas_keluar'] ?: '<i class="text-muted">Belum ada aktivitas</i>' ?></div></td>
                                <td><div class="constraint-text"><?= $log['kendala_keluar'] ?: '<i class="text-success">OK</i>' ?></div></td>
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
    doc.text("Nama: <?= $userProfile['nama'] ?? '-' ?>", 14, 35);
    doc.text("Asal Instansi: <?= $userProfile['instansi'] ?? '-' ?>", 14, 42);
    doc.text("Unit Kerja: <?= $userProfile['unit'] ?? '-' ?>", 14, 49);

    const tableData = [
        <?php foreach ($logbookData as $i => $log): ?>
        [
            "<?= $i+1 ?>",
            "<?= date('d-m-Y', strtotime($log['tanggal'])) ?>",
            "<?= addslashes($log['aktivitas_masuk']) ?>",
            "<?= addslashes($log['kendala_masuk']) ?>",
            "<?= addslashes($log['aktivitas_keluar']) ?>",
            "<?= addslashes($log['kendala_keluar']) ?>"
        ],
        <?php endforeach; ?>
    ];

    doc.autoTable({
        head: [['No', 'Tanggal', 'Aktivitas Masuk', 'Kendala Masuk', 'Aktivitas Keluar', 'Kendala Keluar']],
        body: tableData,
        startY: 60,
        styles: { fontSize: 8 },
        headStyles: { fillColor: [230, 0, 18] }
    });

    doc.save("Logbook.pdf");
}
</script>
</body>
</html>
