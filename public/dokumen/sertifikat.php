<?php
session_start();
require_once __DIR__ . '/../../config/database.php'; 
require_once __DIR__ . '/fpdf/fpdf.php';

// Cek login
if (!isset($_SESSION['role'])) {
    die("Anda harus login dulu!");
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'] ?? null;

// Ambil data peserta
if ($role === 'admin') {
    if (!isset($_GET['id'])) {
        die("ID peserta tidak ditemukan!");
    }
    $id = $_GET['id'];
    $sql = "
        SELECT nama, instansi_pendidikan AS instansi, judul_pkl AS judul, tanggal_mulai, tanggal_selesai 
        FROM peserta_pkl 
        WHERE id = '$id'
    ";
} else {
    if (!$userId) {
        die("User ID tidak ditemukan!");
    }
    $sql = "
        SELECT nama, instansi_pendidikan AS instansi, judul_pkl AS judul, tanggal_mulai, tanggal_selesai 
        FROM peserta_pkl 
        WHERE user_id = '$userId'
    ";
}

$query = mysqli_query($conn, $sql) or die("Query Error: " . mysqli_error($conn));
$data  = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data peserta tidak ditemukan!");
}

// Buat sertifikat dengan FPDF
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// Background sertifikat (opsional, bisa pakai gambar template)
$pdf->Image(__DIR__ . '/template/cindy.png', 0, 0, 297, 210);

// Judul
$pdf->SetFont('Arial', 'B', 30);
$pdf->Cell(0, 40, 'SERTIFIKAT', 0, 1, 'C');

// Nama peserta
$pdf->Ln(20);
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(0, 10, strtoupper($data['nama']), 0, 1, 'C');

// Isi keterangan
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 14);
$pdf->MultiCell(0, 10, 
    "Telah melaksanakan Praktek Kerja Lapangan di " . $data['instansi'] . 
    " dengan judul \"" . $data['judul'] . "\"\n" .
    "Pada periode " . date('d M Y', strtotime($data['tanggal_mulai'])) . 
    " sampai dengan " . date('d M Y', strtotime($data['tanggal_selesai'])), 
    0, 'C'
);

// Tanda tangan (opsional)
$pdf->Ln(30);
$pdf->Cell(0, 10, 'Bekasi, ' . date('d M Y'), 0, 1, 'R');
$pdf->Ln(20);
$pdf->Cell(0, 10, 'Pimpinan', 0, 1, 'R');

// Output PDF
$pdf->Output('I', 'sertifikat_' . $data['nama'] . '.pdf');
?>
