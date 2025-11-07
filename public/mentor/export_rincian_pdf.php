<?php
require "../../config/database.php";
require "../../includes/auth.php";
checkRole('mentor');

// library PDF
require_once "../../vendor/autoload.php"; // pastikan sudah install dompdf
use Dompdf\Dompdf;

if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    die("Parameter user_id tidak ditemukan.");
}

$user_id = $conn->real_escape_string($_GET['user_id']);

// ==============================
// Ambil data peserta PKL
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

if ($qPeserta->num_rows === 0) {
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
        jam_keluar,
        aktivitas_masuk,
        aktivitas_keluar,
        kondisi_kesehatan,
        lokasi_kerja,
        status
    FROM absen
    WHERE user_id = '$user_id'
    ORDER BY tanggal ASC
");

$absensi = [];
while ($row = $qAbsen->fetch_assoc()) {
    $absensi[] = $row;
}

// ==============================
// Hitung statistik
// ==============================
$total_hadir = 0;
foreach ($absensi as $a) {
    if ($a['status'] === 'hadir') $total_hadir++;
}

$tgl_mulai = new DateTime($peserta['tgl_mulai']);
$tgl_selesai = new DateTime($peserta['tgl_selesai']);
$periode = clone $tgl_mulai;

$hari_kerja = 0;
while ($periode <= $tgl_selesai) {
    if ($periode->format('N') < 6) $hari_kerja++;
    $periode->modify('+1 day');
}

$persen_kehadiran = $hari_kerja > 0 ? round(($total_hadir / $hari_kerja) * 100, 2) : 0;

// ==============================
// Buat tampilan PDF
// ==============================
$html = '
<style>
body { font-family: Arial, sans-serif; font-size: 12px; }
h2, h3 { text-align: center; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #000; padding: 6px; text-align: center; }
th { background: #f2f2f2; }
.info { margin-bottom: 15px; }
</style>

<h2>RINCIAN ABSENSI PESERTA PKL</h2>

<div class="info">
    <strong>Nama:</strong> ' . htmlspecialchars($peserta['nama']) . '<br>
    <strong>NIS/NPM:</strong> ' . htmlspecialchars($peserta['nis_npm']) . '<br>
    <strong>Instansi:</strong> ' . htmlspecialchars($peserta['instansi_pendidikan']) . '<br>
    <strong>Unit:</strong> ' . htmlspecialchars($peserta['unit']) . '<br>
    <strong>Periode:</strong> ' . htmlspecialchars($peserta['tgl_mulai']) . ' s/d ' . htmlspecialchars($peserta['tgl_selesai']) . '<br>
    <strong>Hari Kerja:</strong> ' . $hari_kerja . '<br>
    <strong>Hadir:</strong> ' . $total_hadir . '<br>
    <strong>Persentase Kehadiran:</strong> ' . $persen_kehadiran . '%<br>
</div>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Jam Masuk</th>
            <th>Jam Keluar</th>
            <th>Kegiatan Masuk</th>
            <th>Kegiatan Keluar</th>
            <th>Kondisi</th>
            <th>Lokasi</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>';

$no = 1;
foreach ($absensi as $a) {
    $html .= '
    <tr>
        <td>' . $no++ . '</td>
        <td>' . htmlspecialchars($a['tanggal']) . '</td>
        <td>' . htmlspecialchars($a['jam_masuk']) . '</td>
        <td>' . htmlspecialchars($a['jam_keluar']) . '</td>
        <td>' . htmlspecialchars($a['aktivitas_masuk']) . '</td>
        <td>' . htmlspecialchars($a['aktivitas_keluar']) . '</td>
        <td>' . htmlspecialchars($a['kondisi_kesehatan']) . '</td>
        <td>' . htmlspecialchars($a['lokasi_kerja']) . '</td>
        <td>' . htmlspecialchars($a['status']) . '</td>
    </tr>';
}

$html .= '
    </tbody>
</table>
<p style="text-align:center; margin-top:30px;">Dicetak otomatis oleh sistem INSTEP - ' . date('d/m/Y H:i') . '</p>
';

// ==============================
// Generate PDF
// ==============================
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Rincian_Absensi_" . $peserta['nama'] . ".pdf", ["Attachment" => false]);
?>
