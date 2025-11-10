<?php
require_once "../../config/database.php";
require_once "../../includes/auth.php";
require_once "../../vendor/autoload.php";
checkRole('mentor');

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    die("Parameter user_id tidak ditemukan.");
}

$user_id = $conn->real_escape_string($_GET['user_id']);

// ==============================
// Ambil data peserta
// ==============================
$qPeserta = $conn->query("
    SELECT 
        p.nama,
        p.nis_npm,
        p.instansi_pendidikan,
        u.nama_unit AS unit,
        p.tgl_mulai,
        p.tgl_selesai
    FROM peserta_pkl p
    LEFT JOIN unit_pkl u ON p.unit_id = u.id
    WHERE p.user_id = '$user_id'
    LIMIT 1
");

if (!$qPeserta || $qPeserta->num_rows === 0) {
    die("Data peserta tidak ditemukan.");
}
$peserta = $qPeserta->fetch_assoc();

// ==============================
// Ambil data absensi
// ==============================
$qAbsen = $conn->query("
    SELECT 
        tanggal,
        jam_masuk,
        aktivitas_masuk,
        kendala_masuk,
        kondisi_kesehatan,
        lokasi_kerja,
        aktivitas_keluar,
        kendala_keluar,
        jam_keluar
    FROM absen
    WHERE user_id = '$user_id'
    ORDER BY tanggal ASC
");

$absensi = [];
while ($a = $qAbsen->fetch_assoc()) {
    $absensi[] = $a;
}

// ==============================
// Hitung kehadiran
// ==============================
$tgl_mulai = new DateTime($peserta['tgl_mulai']);
$tgl_selesai_db = !empty($peserta['tgl_selesai']) ? new DateTime($peserta['tgl_selesai']) : new DateTime();
$tgl_selesai = new DateTime();
if ($tgl_selesai > $tgl_selesai_db) {
    $tgl_selesai = $tgl_selesai_db;
}

$periode = clone $tgl_mulai;
$hari_kerja = 0;
while ($periode <= $tgl_selesai) {
    if ($periode->format('N') < 6) $hari_kerja++;
    $periode->modify('+1 day');
}

$total_hadir = count($absensi);
$persentase = $hari_kerja > 0 ? round(($total_hadir / $hari_kerja) * 100, 2) : 0;

// ==============================
// Buat HTML untuk PDF
// ==============================
$html = '
<!DOCTYPE html>
<html>
<head>
<style>
    body { 
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #333;
        margin: 20px;
    }
    
    /* HEADER */
    .header {
        text-align: center;
        border-bottom: 3px solid #c41e3a;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    
    .header img {
        width: 60px;
        margin-bottom: 8px;
    }
    
    .header h2 {
        margin: 5px 0;
        color: #c41e3a;
        font-size: 18px;
    }
    
    .header h3 {
        margin: 3px 0;
        color: #666;
        font-size: 14px;
        font-weight: normal;
    }
    
    .header p {
        margin: 8px 0 0 0;
        font-weight: bold;
        font-size: 12px;
    }
    
    /* INFO BOX */
    .info-box {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .info-title {
        font-weight: bold;
        color: #c41e3a;
        margin-bottom: 10px;
        font-size: 12px;
    }
    
    table.info-table {
        width: 100%;
        border: none;
    }
    
    table.info-table td {
        padding: 5px;
        border: none;
    }
    
    table.info-table td:first-child {
        width: 180px;
        font-weight: 600;
        color: #555;
    }
    
    table.info-table td:nth-child(2) {
        width: 20px;
        text-align: center;
    }
    
    /* STATS */
    .stats {
        display: table;
        width: 100%;
        margin-bottom: 20px;
        border-spacing: 10px 0;
    }
    
    .stat-item {
        display: table-cell;
        text-align: center;
        padding: 12px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 5px;
        width: 33.33%;
    }
    
    .stat-label {
        font-size: 10px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #c41e3a;
    }
    
    /* TABLE */
    .section-title {
        font-weight: bold;
        color: #333;
        margin: 20px 0 10px 0;
        font-size: 12px;
    }
    
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    
    table.data-table th {
        background: #c41e3a;
        color: white;
        padding: 10px 5px;
        text-align: center;
        font-size: 10px;
        font-weight: 600;
        border: 1px solid #a01528;
    }
    
    table.data-table td {
        padding: 8px 5px;
        text-align: center;
        font-size: 10px;
        border: 1px solid #ddd;
    }
    
    table.data-table td.text-left {
        text-align: left;
        font-size: 9px;
    }
    
    table.data-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    
    /* FOOTER */
    .footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
        font-size: 9px;
        color: #666;
    }
    
    .signature {
        text-align: right;
        margin-top: 50px;
    }
    
    .signature-line {
        margin-top: 60px;
        border-top: 1px solid #333;
        width: 200px;
        display: inline-block;
    }
</style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h2>TELKOM INDONESIA</h2>
    <h3>Witel Bekasi - Karawang</h3>
</div>

<!-- INFO PESERTA -->
<div class="info-box">
    <div class="info-title">Informasi Peserta</div>
    <table class="info-table">
        <tr>
            <td>Nama Lengkap</td>
            <td>:</td>
            <td><strong>'.htmlspecialchars($peserta['nama']).'</strong></td>
        </tr>
        <tr>
            <td>NIS/NPM</td>
            <td>:</td>
            <td>'.htmlspecialchars($peserta['nis_npm']).'</td>
        </tr>
        <tr>
            <td>Instansi Pendidikan</td>
            <td>:</td>
            <td>'.htmlspecialchars($peserta['instansi_pendidikan']).'</td>
        </tr>
        <tr>
            <td>Unit Penempatan</td>
            <td>:</td>
            <td>'.htmlspecialchars($peserta['unit']).'</td>
        </tr>
        <tr>
            <td>Periode</td>
            <td>:</td>
            <td>'.date('d/m/Y', strtotime($peserta['tgl_mulai'])).' s.d. '.date('d/m/Y', strtotime($peserta['tgl_selesai'])).'</td>
        </tr>
    </table>
</div>

<!-- STATISTIK -->
<div class="stats">
    <div class="stat-item">
        <div class="stat-label">Total Hari Kerja</div>
        <div class="stat-value">'.$hari_kerja.'</div>
    </div>
    <div class="stat-item">
        <div class="stat-label">Jumlah Hadir</div>
        <div class="stat-value">'.$total_hadir.'</div>
    </div>
    <div class="stat-item">
        <div class="stat-label">Persentase</div>
        <div class="stat-value">'.$persentase.'%</div>
    </div>
</div>

<!-- TABEL ABSENSI -->
<div class="section-title">Rincian Absensi</div>
<table class="data-table">
    <thead>
        <tr>
            <th width="3%">No</th>
            <th width="9%">Tanggal</th>
            <th width="6%">Masuk</th>
            <th width="6%">Keluar</th>
            <th width="20%">Aktivitas Masuk</th>
            <th width="20%">Aktivitas Keluar</th>
            <th width="12%">Kendala Masuk</th>
            <th width="12%">Kendala Keluar</th>
            <th width="7%">Kesehatan</th>
            <th width="5%">Lokasi</th>
        </tr>
    </thead>
    <tbody>';

$no = 1;
foreach ($absensi as $a) {
    $html .= '<tr>
        <td>'.$no++.'</td>
        <td>'.date('d/m/Y', strtotime($a['tanggal'])).'</td>
        <td>'.(!empty($a['jam_masuk']) ? date('H:i', strtotime($a['jam_masuk'])) : '-').'</td>
        <td>'.(!empty($a['jam_keluar']) ? date('H:i', strtotime($a['jam_keluar'])) : '-').'</td>
        <td class="text-left">'.htmlspecialchars(substr($a['aktivitas_masuk'] ?: '-', 0, 80)).'</td>
        <td class="text-left">'.htmlspecialchars(substr($a['aktivitas_keluar'] ?: '-', 0, 80)).'</td>
        <td class="text-left">'.htmlspecialchars(substr($a['kendala_masuk'] ?: '-', 0, 60)).'</td>
        <td class="text-left">'.htmlspecialchars(substr($a['kendala_keluar'] ?: '-', 0, 60)).'</td>
        <td>'.htmlspecialchars($a['kondisi_kesehatan'] ?: '-').'</td>
        <td>'.htmlspecialchars($a['lokasi_kerja'] ?: '-').'</td>
    </tr>';
}

if (empty($absensi)) {
    $html .= '<tr><td colspan="10" style="text-align:center; padding:20px; color:#999;">Belum ada data absensi</td></tr>';
}

$html .= '
    </tbody>
</table>

<!-- FOOTER -->
<div class="footer">
    <table width="100%">
        <tr>
            <td width="50%" style="vertical-align:top;">
                <small>Dicetak pada: '.date('d/m/Y H:i').' WIB<br>
                © '.date('Y').' PT Telkom Indonesia</small>
            </td>
            <td width="50%" style="text-align:right;">
                <div>Bekasi, '.date('d/m/Y').'</div>
                <div style="margin-top:5px;">Mentor Pembimbing,</div>
                <div class="signature-line"></div>
                <div style="margin-top:5px;">( __________________ )</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
';

// ==============================
// Generate PDF
// ==============================
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$nama_file = preg_replace('/[^A-Za-z0-9_\-]/', '_', $peserta['nama']);
$dompdf->stream("Rekap_Absensi_" . $nama_file . ".pdf", ["Attachment" => true]);
exit;
?>