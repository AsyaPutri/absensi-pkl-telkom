<?php
session_start();
require('../../config/database.php');
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php');

/* Helper */
function getFirstValue(array $row, array $candidates, $default = '') {
    foreach ($candidates as $k) {
        if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}

/* Pastikan login */
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}
$role   = $_SESSION['role'];
$userId = intval($_SESSION['user_id']);

/* Ambil data peserta + daftar_pkl + unit_pkl */
if ($role === 'admin') {
    if (!isset($_GET['id'])) die("ID peserta tidak ditemukan. Admin harus memanggil ?id=ID_PESERTA");
    $id = intval($_GET['id']);
    $sql = "
        SELECT p.*, d.tgl_mulai, d.tgl_selesai, d.durasi, u.nama_unit
        FROM peserta_pkl p
        LEFT JOIN daftar_pkl d ON p.email = d.email
        LEFT JOIN unit_pkl u ON p.unit_id = u.id
        WHERE p.id = $id
        LIMIT 1
    ";
} else {
    $sql = "
        SELECT p.*, d.tgl_mulai, d.tgl_selesai, d.durasi, u.nama_unit
        FROM peserta_pkl p
        LEFT JOIN daftar_pkl d ON p.email = d.email
        LEFT JOIN unit_pkl u ON p.unit_id = u.id
        WHERE p.user_id = $userId
        LIMIT 1
    ";
}
$result = mysqli_query($conn, $sql);
if (!$result) die("Query Error: " . mysqli_error($conn));
$data = mysqli_fetch_assoc($result);
if (!$data) die("Data peserta tidak ditemukan.");

/* === Ambil nomor surat dari database === */
$noSurat = $data['nomor_surat'] ?? null;

// Kalau belum ada, kasih pesan
if (!$noSurat) {
    die("<h3>Nomor surat belum dibuat. Harap admin menambahkan nomor surat terlebih dahulu.</h3>");
}

// Pastikan tampil 3 digit (misal 001, 010, dst)
$noSuratFormatted = str_pad($noSurat, 3, '0', STR_PAD_LEFT);

// Tahun otomatis sesuai waktu cetak
$tahunCetak = date('Y');

/* Ambil field peserta */
$nama   = getFirstValue($data, ['nama','nama_lengkap','full_name'], 'Nama Tidak Diketahui');
$unit   = !empty($data['nama_unit']) ? $data['nama_unit'] : 'Unit Tidak Diisi';
$durasi = getFirstValue($data, ['durasi'], '-');
$tglMulaiStr   = date("d F Y", strtotime(getFirstValue($data, ['tgl_mulai'], date('Y-m-d'))));
$tglSelesaiStr = date("d F Y", strtotime(getFirstValue($data, ['tgl_selesai'], date('Y-m-d'))));

/* === Generate PDF === */
$pdf = new FPDF('L','mm','A4');
$pdf->AddPage();

// Background sertifikat
$bgPath = __DIR__ . '/template/templatesertifikat.png';
if (file_exists($bgPath)) {
    $pdf->Image($bgPath, 0, 0, 297, 210);
}

// =============================
// NOMOR SERTIFIKAT (dari no_surat)
// =============================
$pdf->SetFont('Arial','B',20);
$pdf->SetTextColor(0,0,0);
$pdf->SetXY(30, 30);
$pdf->Cell(297, 12, "C.TEL.$noSuratFormatted/PD.000/R2W-2G10000/$tahunCetak", 0, 1, 'C');

// Nama Peserta
$pdf->SetFont('Times','B',36);
$pdf->SetTextColor(184,134,11);
$pdf->SetXY(0, 100);
$pdf->Cell(297, 12, mb_strtoupper($nama, 'UTF-8'), 0, 1, 'C');

// Keterangan
$pdf->SetFont('Arial','',14);
$pdf->SetTextColor(0,0,0);
$pdf->SetXY(25, 122);
$keterangan = "Yang telah menyelesaikan program Praktik Kerja Lapangan (PKL) di PT Telkom Indonesia 
              (Persero) Tbk, pada unit Witel $unit selama $durasi\n"."terhitung mulai tanggal $tglMulaiStr s/d $tglSelesaiStr\n" .
              "dengan hasil \"Sangat Baik\"";
$pdf->MultiCell(247, 8, $keterangan, 0, 'C');

// ===========================
// QR Code sebagai TTD
// ===========================
$dataTTD = "Ditandatangani oleh:\nROSANA INTAN PERMATASARI\nManager Shared Service & General Support\nTanggal: ".date("d-m-Y");
$qrFile = __DIR__ . "/qrcode_ttd.png";
QRcode::png($dataTTD, $qrFile, QR_ECLEVEL_H, 5);
$pdf->Image($qrFile, 35, 135, 35, 35);

// Nama & Jabatan
$pdf->SetFont('Arial','B',12);
$pdf->SetXY(30, 175);
$pdf->Cell(0, 6, 'ROSANA INTAN PERMATASARI', 0, 1, 'L');
$pdf->SetFont('Arial','',11);
$pdf->SetXY(30, 183);
$pdf->Cell(0, 6, 'MANAGER SHARED SERVICE & GENERAL SUPPORT', 0, 1, 'L');

if (file_exists($qrFile)) unlink($qrFile);

// Output PDF
$filenameSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama);
$pdf->Output('I', "sertifikat_{$filenameSafe}.pdf");
exit;
?>
