<?php
session_start();
require('../../config/database.php');
require('fpdf/fpdf.php');

// cek login
if (!isset($_SESSION['role'])) {
    die("Anda harus login dulu!");
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'] ?? null;

// ------------ Ambil data peserta ------------
if ($role === 'admin') {
    // admin bisa pilih id peserta via GET
    if (!isset($_GET['id'])) {
        die("ID peserta tidak ditemukan!");
    }
    $id = $_GET['id'];
    $query = mysqli_query($conn, "
        SELECT nama, unit_witel, tgl_mulai, tgl_selesai, status_sertifikat
        FROM peserta_pkl
        WHERE id = '$id'
    ");
} else {
    // peserta hanya bisa ambil datanya sendiri
    $query = mysqli_query($conn, "
        SELECT nama, unit_witel, tgl_mulai, tgl_selesai, status_sertifikat
        FROM peserta_pkl
        WHERE user_id = '$userId'
    ");
}

$data = mysqli_fetch_assoc($query);
if (!$data) {
    die("Data sertifikat tidak ditemukan!");
}

// ------------ Cek validasi sertifikat ------------
if ($data['status_sertifikat'] == 0 && $role !== 'admin') {
    die("<h3>Sertifikat Anda belum tersedia. Hubungi admin.</h3>");
}

// ------------ Ambil variabel ------------
$nama       = $data['nama'];
$unit       = $data['unit_witel'];
$tglMulai   = new DateTime($data['tgl_mulai']);
$tglSelesai = new DateTime($data['tgl_selesai']);

// hitung durasi
$interval    = $tglMulai->diff($tglSelesai);
$durasiBulan = $interval->m + ($interval->y * 12);

if ($durasiBulan > 0) {
    $durasi = $durasiBulan . " (" . ucwords(terbilang($durasiBulan)) . ") bulan";
} else {
    $durasi = $interval->d . " (" . ucwords(terbilang($interval->d)) . ") hari";
}

$tglMulaiStr   = $tglMulai->format("d F Y");
$tglSelesaiStr = $tglSelesai->format("d F Y");

// ------------ Generate PDF ------------
$pdf = new FPDF('L','mm','A4');
$pdf->AddPage();
$pdf->Image('cindy.png', 0, 0, 297, 210);

// nama
$pdf->SetFont('Times','B',32);
$pdf->SetTextColor(184,134,11);
$pdf->SetXY(0, 105);
$pdf->Cell(297,10,$nama,0,1,'C');

// keterangan
$pdf->SetFont('Arial','',14);
$pdf->SetTextColor(0,0,0);
$pdf->SetXY(20, 125);
$pdf->MultiCell(257,8,
    "Yang telah menyelesaikan program Praktik Kerja Lapangan (PKL) di PT Telkom Indonesia (Persero) Tbk, pada unit Witel $unit selama $durasi\n".
    "terhitung mulai tanggal $tglMulaiStr s/d $tglSelesaiStr\n".
    "dengan hasil \"Sangat Baik\"",
    0,'C'
);

$pdf->Output("I", "sertifikat_$nama.pdf");

// ------------ Fungsi terbilang ------------
function terbilang($angka) {
    $bilangan = ["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan","sepuluh","sebelas"];
    if ($angka < 12) {
        return $bilangan[$angka];
    } elseif ($angka < 20) {
        return $bilangan[$angka-10] . " belas";
    } elseif ($angka < 100) {
        return $bilangan[floor($angka/10)] . " puluh " . $bilangan[$angka%10];
    }
    return $angka;
}
?>
