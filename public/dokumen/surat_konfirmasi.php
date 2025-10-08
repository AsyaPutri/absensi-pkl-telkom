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
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();

// === Judul ===
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,6,"KONFIRMASI KEGIATAN PKL",0,1,'C');
$pdf->SetFont('Times','',10);
$pdf->Cell(0,6,"NOMOR : $nomorSurat",0,1,'C');
$pdf->Ln(5);

// === Tujuan ===
$pdf->SetFont('Times','',12);
$pdf->Cell(0,6,"Kepada : AZMI WICAKSONO",0,1,'L');
$pdf->Cell(0,6,"Jabatan : $lokasiPKL",0,1,'L');
$pdf->Cell(0,6,"Hal : Konfirmasi Persetujuan Kegiatan PKL",0,1,'L');
$pdf->Ln(5);

// === Tabel Peserta ===
$pdf->SetFont('Times','B',11);
$pdf->Cell(10,8,"NO",1,0,'C');
$pdf->Cell(70,8,"NAMA",1,0,'C');
$pdf->Cell(50,8,"NIM/NIDN",1,0,'C');
$pdf->Cell(40,8,"JURUSAN",1,1,'C');

$pdf->SetFont('Times','',11);
$pdf->Cell(10,8,"1",1,0,'C');
$pdf->Cell(70,8,$nama_peserta,1,0,'L');
$pdf->Cell(50,8,$nim,1,0,'C');
$pdf->Cell(40,8,"Teknik Telekomunikasi",1,1,'C');
$pdf->Ln(5);

// === Data Sekolah ===
$pdf->SetFont('Times','',12);
$pdf->MultiCell(0,6,
    "Asal Sekolah/Universitas : $universitas\n".
    "Jurusan : $jurusan\n".
    "Jenis Kegiatan : PRAKTEK KERJA LAPANGAN\n".
    "Waktu Pelaksanaan : $periode\n",0,'L');

// === Persetujuan ===
$pdf->Ln(3);
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,8,"Konfirmasi Persetujuan :",0,1,'L');

$pdf->Cell(40,10,"",1,0,'C');
$pdf->Cell(40,10,"SETUJU",1,0,'C');
$pdf->Cell(40,10,"TIDAK SETUJU",1,1,'C');
$pdf->Ln(8);

// === Tempat & Tanggal ===
$pdf->SetFont('Times','',12);
$pdf->Cell(0,6,"Bekasi, $tanggal",0,1,'R');

// === TTD Pihak Telkom ===
$pdf->Ln(2);
$pdf->Cell(110,6,"",0,0);
$pdf->Cell(0,6,"MANAGER SHARED SERVICE &",0,1,'L');
$pdf->Cell(110,6,"",0,0);
$pdf->Cell(0,6,"GENERAL SUPPORT",0,1,'L');
$pdf->Ln(15);

// === QR Code Telkom ===
$tempDir = __DIR__ . "/temp/";
if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

$qrData = "Ditandatangani digital oleh:\n".
          "ROSANA INTAN PERMATASARI\n".
          "Manager Shared Service & General Support\n".
          "Tanggal: $tanggal";
$qrFile = $tempDir."qr_ttd_".time().".png";
QRcode::png($qrData,$qrFile,QR_ECLEVEL_H,4);

$pdf->Image($qrFile, 135, $pdf->GetY()-20, 30);
unlink($qrFile);

// === Nama Penandatangan ===
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,6,"ROSANA INTAN PERMATASARI",0,1,'R');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,"NIK: 578805",0,1,'R');

$pdf->Ln(15);

// === TTD Pihak Sekolah ===
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,6,"(AZMI WICAKSONO)",0,1,'L');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,"NIK: 580602",0,1,'L');

// === Catatan ===
$pdf->Ln(8);
$pdf->SetFont('Times','',10);
$pdf->MultiCell(0,5,
    "Catatan:\n".
    "1. Peserta wajib melakukan test kesehatan (Buta Warna, Urine) sesuai ketentuan\n".
    "2. Peserta wajib mengikuti briefing peraturan pelaksanaan PKL (Safety, K3, CEM, AKHLAK) dan lainnya\n".
    "3. Membawa surat pengantar dari universitas\n".
    "4. Membawa materai Rp. 10.000\n".
    "5. Membawa fotocopy KTP dan KTM\n".
    "6. Mematuhi peraturan dan tata tertib di lingkungan PT. TELKOM\n\n".
    "Demikian konfirmasi ini dibuat sebagai dasar pelaksanaan PKL."
,0,'L');

// Output
$pdf->Output('I','Surat_Konfirmasi_PKL.pdf');
