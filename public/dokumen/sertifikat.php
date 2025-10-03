<?php
session_start();
require('../../config/database.php');
require(__DIR__ . '/fpdf/fpdf.php');

function getFirstValue(array $row, array $candidates, $default = '') {
    foreach ($candidates as $k) {
        if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}

if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}
$role   = $_SESSION['role'];
$userId = intval($_SESSION['user_id']);

// --- Ambil data peserta + daftar_pkl + unit_pkl ---
if ($role === 'admin') {
    if (!isset($_GET['id'])) {
        die("ID peserta tidak ditemukan. Admin harus memanggil ?id=ID_PESERTA");
    }
    $id = intval($_GET['id']);
    $sql = "
        SELECT 
            p.*, 
            d.tgl_mulai, d.tgl_selesai, d.durasi,
            u.nama_unit
        FROM peserta_pkl p
        LEFT JOIN daftar_pkl d ON p.email = d.email
        LEFT JOIN unit_pkl u ON p.unit_id = u.id
        WHERE p.id = $id
        LIMIT 1
    ";
} else {
    $sql = "
        SELECT 
            p.*, 
            d.tgl_mulai, d.tgl_selesai, d.durasi,
            u.nama_unit
        FROM peserta_pkl p
        LEFT JOIN daftar_pkl d ON p.email = d.email
        LEFT JOIN unit_pkl u ON p.unit_id = u.id
        WHERE p.user_id = $userId
        LIMIT 1
    ";
}

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query Error: " . mysqli_error($conn) . " -- SQL: " . $sql);
}
$data = mysqli_fetch_assoc($result);
if (!$data) {
    die("Data peserta tidak ditemukan.");
}

// --- Cek status sertifikat ---
$status = getFirstValue($data, ['status_sertifikat','status','sertifikat_status'], 0);
if ($status == 0 && $role !== 'admin') {
    die("<h3>Sertifikat Anda belum tersedia. Silakan hubungi admin.</h3>");
}

// --- Ambil field ---
$nama   = getFirstValue($data, ['nama','nama_lengkap','full_name'], 'Nama Tidak Diketahui');
$unit   = !empty($data['nama_unit']) ? $data['nama_unit'] : 'Unit Tidak Diisi';
$durasi = getFirstValue($data, ['durasi'], '-');

$tglMulaiRaw   = getFirstValue($data, ['tgl_mulai'], null);
$tglSelesaiRaw = getFirstValue($data, ['tgl_selesai'], null);

try {
    $tglMulai   = $tglMulaiRaw   ? new DateTime($tglMulaiRaw)   : new DateTime();
    $tglSelesai = $tglSelesaiRaw ? new DateTime($tglSelesaiRaw) : new DateTime();
} catch (Exception $e) {
    $tglMulai   = new DateTime();
    $tglSelesai = new DateTime();
}

$tglMulaiStr   = $tglMulai->format("d F Y");
$tglSelesaiStr = $tglSelesai->format("d F Y");

// --- Generate PDF ---
$pdf = new FPDF('L','mm','A4');
$pdf->AddPage();

// background
$bgPath = __DIR__ . '/template/templatesertifikat.jpg';
if (file_exists($bgPath)) {
    $pdf->Image($bgPath, 0, 0, 297, 210);
}

// Nama
$pdf->SetFont('Times','B',36);
$pdf->SetTextColor(184,134,11);
$pdf->SetXY(0, 100);
$pdf->Cell(297, 12, mb_strtoupper($nama, 'UTF-8'), 0, 1, 'C');

// Keterangan
$pdf->SetFont('Arial','',14);
$pdf->SetTextColor(0,0,0);
$pdf->SetXY(25, 122);
$keterangan = "Yang telah menyelesaikan program Praktik Kerja Lapangan (PKL) di PT Telkom Indonesia 
              (Persero) Tbk, pada unit Witel $unit selama $durasi\n" .
              "terhitung mulai tanggal $tglMulaiStr s/d $tglSelesaiStr\n" .
              "dengan hasil \"Sangat Baik\"";
$pdf->MultiCell(247, 8, $keterangan, 0, 'C');

// TTD
$ttdPath = __DIR__ . '/template/ttdmanager.png';
if (file_exists($ttdPath)) {
    $pdf->Image($ttdPath, 35, 135, 45);
}

// Nama Manajer
$pdf->SetFont('Arial','B',12);
$pdf->SetXY(30, 170);
$pdf->Cell(0, 6, 'ROSANA INTAN PERMATASARI', 0, 1, 'L');

// Jabatan
$pdf->SetFont('Arial','',11);
$pdf->SetXY(30, 178);
$pdf->Cell(0, 6, 'MANAGER SHARED SERVICE & GENERAL SUPPORT', 0, 1, 'L');

// Output
$filenameSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama);
$pdf->Output('I', "sertifikat_{$filenameSafe}.pdf");
exit;
?>