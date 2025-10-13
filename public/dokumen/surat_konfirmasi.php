<?php
session_start();
require('../../config/database.php');
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php');

//  user login
if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}

$userId = $_SESSION['user_id'];

// ================== Ambil Data Peserta ==================
$query = "SELECT 
            p.nama AS nama_peserta,
            p.nis_npm AS nim,
            p.jurusan,
            p.instansi_pendidikan AS universitas,
            p.tgl_mulai,
            p.tgl_selesai,
            u.nama_unit,
            c.nama_karyawan,
            c.posisi
          FROM peserta_pkl p
          LEFT JOIN unit_pkl u ON p.unit_id = u.id
          LEFT JOIN cp_karyawan c ON u.id = c.unit_id
          WHERE p.user_id = ? LIMIT 1";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query gagal disiapkan: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

if (!$data) {
    die("Data peserta tidak ditemukan.");
}

// Variabel
$nama_peserta = $data['nama_peserta'];
$nim          = $data['nim'];
$jurusan      = $data['jurusan'];
$universitas  = $data['universitas'];
$tgl_mulai    = $data['tgl_mulai'];
$tgl_selesai  = $data['tgl_selesai'];
$lokasiPKL    = $data['nama_unit'] ?? "-";
$nama_cp      = $data['nama_karyawan'] ?? "-";
$posisi_cp    = $data['posisi'] ?? "-";
$periode      = date("d F Y", strtotime($tgl_mulai)) . " s/d " . date("d F Y", strtotime($tgl_selesai));

// ================== Ambil Data Surat ==================
$qSurat = "SELECT nomor_surat_permohonan, tgl_daftar 
           FROM daftar_pkl 
           WHERE email = (SELECT email FROM users WHERE id = ?)";
$stmt2 = $conn->prepare($qSurat);
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$res2 = $stmt2->get_result();
$row2 = $res2->fetch_assoc();

$nomorSurat = $row2['nomor_surat_permohonan'] ?? "C.TEL.232/PKL/000/RW-301/0000/2025";
$tanggal    = date("d F Y");

// ================== PDF ==================
$pdf = new FPDF('P','mm','A4');
$pdf->SetMargins(25.4, 25.4, 25.4);
$pdf->SetAutoPageBreak(true, 25.4);
$pdf->AddPage();

// === Logo Telkom ===
$logoTelkom = __DIR__ . '/../assets/img/logo_telkom.png';
if (file_exists($logoTelkom)) {
    $pdf->Image($logoTelkom, 160, 5, 40);
}
$pdf->Ln(0);

// === Judul Surat ===
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,6,"KONFIRMASI KEGIATAN PKL",0,1,'C');
$pdf->SetFont('Times','',12);
$pdf->Cell(0,6,"Nomor : $nomorSurat",0,1,'C');
$pdf->Ln(3);

// === Tujuan ===
$pdf->SetFont('Times','',12);
$pdf->Cell(0,6,"Kepada : $nama_cp",0,1,'L');
$pdf->Cell(0,6,"Bagian : $lokasiPKL",0,1,'L');
$pdf->Cell(0,6,"Hal      : Konfirmasi Persetujuan Kegiatan PKL",0,1,'L');
$pdf->Ln(3);

// === Tabel Peserta ===
$pdf->SetFont('Times','B',11);
$pdf->Cell(70,8,"NAMA",1,0,'C');
$pdf->Cell(40,8,"NIM/NIS",1,0,'C');
$pdf->Cell(50,8,"JURUSAN",1,1,'C');

$pdf->SetFont('Times','',11);
$pdf->Cell(70,8,$nama_peserta,1,0,'C');
$pdf->Cell(40,8,$nim,1,0,'C');
$pdf->Cell(50,8,$jurusan,1,1,'C');
$pdf->Ln(5);

// === Data Sekolah ===
$pdf->SetFont('Times','',12);
$labels = [
    "Asal Sekolah/Universitas" => $universitas,
    "Jurusan" => $jurusan,
    "Jenis Kegiatan" => "PRAKTEK KERJA LAPANGAN",
    "Waktu Pelaksanaan" => $periode
];

foreach ($labels as $label => $value) {
    $pdf->Cell(60,6,$label,0,0,'L');
    $pdf->Cell(5,6,":",0,0,'L');
    $pdf->Cell(0,6,$value,0,1,'L');
}
$pdf->Ln(5);

// === Persetujuan ===
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,8,"Konfirmasi Persetujuan",0,1,'C');

$pdf->SetX(25.4 + 39.6);
$pdf->Cell(40,8,"SETUJU",1,0,'C');
$pdf->Cell(40,8,"TIDAK SETUJU",1,1,'C');
$pdf->Ln(8);

// === Tempat & Tanggal ===
$pdf->SetFont('Times','',12);
$pdf->Cell(0,6,"Bekasi, $tanggal",0,1,'R');
$pdf->Ln(10);


$y_ttd = $pdf->GetY();

// === TTD karyawan ===
$pdf->SetXY(20, $y_ttd);
$pdf->SetFont('Times','',12);
$pdf->Cell(80,6,strtoupper($posisi_cp),0,2,'C');

$pdf->SetXY(20, $y_ttd + 40);
$pdf->SetFont('Times','B',12);
$pdf->Cell(80,6,"$nama_cp",0,2,'C');

// === TTD manager ===
$tempDir = __DIR__ . "/temp/";
if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

$qrData = "Ditandatangani secara digital oleh:\n".
          "ROSANA INTAN PERMATASARI\n".
          "Manager Shared Service & General Support\n".
          "Tanggal: $tanggal";
$qrFile = $tempDir."qr_ttd_".time().".png";
QRcode::png($qrData,$qrFile,QR_ECLEVEL_H,4);

$pdf->SetXY(115, $y_ttd);
$pdf->SetFont('Times','',12);
$pdf->Cell(80,6,"MANAGER SHARED SERVICE &",0,2,'C');
$pdf->Cell(80,6,"GENERAL SUPPORT",0,2,'C');

// QR Code
$pdf->Image($qrFile, 140, $y_ttd + 12, 28);
unlink($qrFile);

// Nama + NIK kanan
$pdf->SetXY(115, $y_ttd + 40);
$pdf->SetFont('Times','B',12);
$pdf->Cell(80,6,"ROSANA INTAN PERMATASARI",0,2,'C');
$pdf->SetFont('Times','',11);
$pdf->Cell(80,6,"NIK: 578805",0,2,'C');

// === Catatan ===
$pdf->Ln(8);
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,6,"Catatan:",0,1,'L');

$pdf->SetFont('Times','',12);
$pdf->MultiCell(0,5,
    "Yang termasuk rahasia perusahaan PT.TELKOM / data yang harus dirahasiakan yaitu:\n".
    "1. Data Pelanggan, Identitas Pelanggan, Besarnya pemakaian jastel.\n".
    "2. Data rencana jangka panjang & pendek, CSS, CAM, RKAP, data keuangan yang belum diumumkan ke masyarakat.\n".
    "3. Data pengadaan, pembangunan, operasional dan pemasaran yang belum dan tidak akan diumumkan.\n".
    "4. MOU, Kontrak / Perjanjian dan perubahan.\n".
    "5. Materi hasil pembahasan di Unit Performance, Risk & Quality.\n".
    "6. Informasi / data lainnya yang ditetapkan sebagai rahasia perusahaan oleh anggota direksi.\n\n"
,0,'J');

// === Kop Bawah ===
$kopBawah = __DIR__ . '/template/kop surat footer telkom (1).png';
if (file_exists($kopBawah)) {
    $pageHeight   = $pdf->GetPageHeight();
    $footerHeight = 20;
    $posY         = $pageHeight - $footerHeight;
    $pdf->Image($kopBawah, 0, $posY, 210, $footerHeight);
}

// Output
$pdf->Output('I','Surat_Konfirmasi_PKL.pdf');
?>
