<?php
session_start();
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php');

// ================== Data Surat ==================
$nomorSurat   = "C.TEL.232/PKL/000/RW-301/0000/2025";
$tanggal      = date("d F Y");
$nama_peserta = "Irwan Saputra";
$nim          = "1102210614";
$jurusan      = "S1-Teknik Telekomunikasi";
$universitas  = "Telkom University";
$lokasiPKL    = "UNIT TELKOM DAERAH KALIABANG";
$periode      = "30 Juni 2025 s/d 30 Agustus 2025";

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
$pdf->Cell(0,6,"Kepada : AZMI WICAKSONO",0,1,'L');
$pdf->Cell(0,6,"Jabatan : $lokasiPKL",0,1,'L');
$pdf->Cell(0,6,"Hal       : Konfirmasi Persetujuan Kegiatan PKL",0,1,'L');
$pdf->Ln(3);

// === Tabel Peserta ===
$pdf->SetFont('Times','B',11);
$pdf->Cell(70,8,"NAMA",1,0,'C');
$pdf->Cell(40,8,"NIM/NIS",1,0,'C');
$pdf->Cell(50,8,"JURUSAN",1,1,'C');

$pdf->SetFont('Times','',11);
$pdf->Cell(70,8,$nama_peserta,1,0,'C');
$pdf->Cell(40,8,$nim,1,0,'C');
$pdf->Cell(50,8,"Teknik Telekomunikasi",1,1,'C');
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

$pdf->SetX(25.4 + 39.6); // offset tengah
$pdf->Cell(40,8,"SETUJU",1,0,'C');
$pdf->Cell(40,8,"TIDAK SETUJU",1,1,'C');
$pdf->Ln(8);

// === Tempat & Tanggal ===
$pdf->SetFont('Times','',12);
$pdf->Cell(0,6,"Bekasi, $tanggal",0,1,'R');
$pdf->Ln(10);

// Simpan posisi awal tanda tangan
$y_ttd = $pdf->GetY();

// === TTD Sekolah (Kiri) ===
$pdf->SetXY(20, $y_ttd);
$pdf->SetFont('Times','',12);
$pdf->Cell(80,6,"UNIT TELKOM DAERAH KALIABANG",0,2,'C');

// Nama + NIK kiri
$pdf->SetXY(20, $y_ttd + 40);
$pdf->SetFont('Times','B',12);
$pdf->Cell(80,6,"(AZMI WICAKSONO)",0,2,'C');
$pdf->SetFont('Times','',11);
$pdf->Cell(80,6,"NIK: 580602",0,2,'C');

// === TTD Telkom (Kanan) ===
$tempDir = __DIR__ . "/temp/";
if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

$qrData = "Ditandatangani secara digital oleh:\n".
          "ROSANA INTAN PERMATASARI\n".
          "Manager Shared Service & General Support\n".
          "Tanggal: $tanggal";
$qrFile = $tempDir."qr_ttd_".time().".png";
QRcode::png($qrData,$qrFile,QR_ECLEVEL_H,4);

// Kepala bagian kanan
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


// === Kop Bawah (Footer) ===
$kopBawah = __DIR__ . '/template/kop surat footer telkom (1).png';
if (file_exists($kopBawah)) {
    $pageHeight   = $pdf->GetPageHeight();
    $footerHeight = 20;
    $posY         = $pageHeight - $footerHeight;
    $pdf->Image($kopBawah, 0, $posY, 210, $footerHeight);
}


// Output
$pdf->Output('I','Surat_Konfirmasi_PKL.pdf');
