<?php
session_start();
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php');

// ================== Data Surat ==================
$no_surat       = "C.TEL.232/PD.000/RW-301/0000/2025";
$tanggalSurat   = "26 Juni 2025";
$kepada         = "Kapordi Teknik Telekomunikasi\nTelkom University";
$no_permohonan  = "2994/AKD13/TE-WD/1.2025";
$nama_peserta   = "Ivan Saputra";
$nim_peserta    = "1101220164";
$jurusan        = "Teknik Telekomunikasi";
$tanggal        = date("d F Y");

// ================== PDF ==================
$pdf = new FPDF('P','mm','A4');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();

// === Logo Telkom ===
$logoTelkom = __DIR__ . '/../assets/img/logo_telkom.png';
if (file_exists($logoTelkom)) {
    $pdf->Image($logoTelkom, 160, 5, 40);
}
$pdf->Ln(0);

// === Header Surat ===
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,"Nomor   : $no_surat",0,1,'L');
$pdf->Cell(0,6,"Bekasi, $tanggalSurat",0,1,'L');
$pdf->Ln(4);

$pdf->MultiCell(0,6,"Kepada Yth,\n$kepada",0,'L');
$pdf->Ln(4);

// === Isi Surat ===
$pdf->SetFont('Times','',11);
$isi = "Dengan hormat,\n"
     . "Menjawab surat Saudara dengan nomor : 2994 /AKD13/TE-WD1/2025 yang telah kami terima tanggal 3 Juni 2025 "
     . "perihal Permohonan PKL/Magang Mahasiswa Telkom University dan untuk mewujudkan para peserta didik yang memiliki "
     . "kompetensi akademik serta memiliki wawasan praktis serta untuk memenuhi tugas. "
     . "Untuk dapat mewujudkan link and match dan pengalaman kerja di dunia usaha khusus di lokasi Kota Bekasi – Karawang, Jawa Barat ini, "
     . "maka dengan ini kami sampaikan bahwa pada prinsipnya dapat kami setujui atas nama:\n\n";
$pdf->MultiCell(0,5,$isi,0,'J');


// === Tabel Peserta ===
$pdf->SetFont('Times','B',11);
$pdf->Cell(70,6,"Nama",1,0,'C');
$pdf->Cell(40,6,"NIM/NIS",1,0,'C');
$pdf->Cell(60,6,"Jurusan",1,1,'C');

$pdf->SetFont('Times','',12);
$pdf->Cell(70,6,$nama_peserta,1,0,'C');
$pdf->Cell(40,6,$nim_peserta,1,0,'C');
$pdf->Cell(60,6,$jurusan,1,1,'C');
$pdf->Ln(5);

// === Paragraf Lanjutan ===
$isi2 = "Untuk itu siswa tersebut akan kami tempatkan di area Witel Bekasi Karawang di Unit "
      . "Telkom Daerah Kaliabang terhitung mulai tanggal 30 Juni 2025 s/d 30 Agustus 2025. "
      . "Untuk pelaksanaannya, 1 (satu) hari sebelum dimulai kegiatan praktik kerja lapangan, "
      . "siswa tersebut agar melapor terlebih dahulu ke unit HC Telkom Bekasi Karawang "
      . "Jl. Rawa Tembaga No.4 Bekasi, Tlp 02188895200 dengan membawa materai @10.000 "
      . "untuk menandatangani surat pernyataan yang berkaitan dengan pelaksanaan praktik kerja "
      . "lapangan di PT. TELKOM.\n\n"
      . "Adapun hak dan kewajiban peserta PKL adalah sebagai berikut:";
$pdf->MultiCell(0,5,$isi2,0,'J');

// === Poin-poin ===
$pdf->SetFont('Times','',11);
$poin = [
    "Menandatangani surat pernyataan praktik kerja lapangan bermaterai Rp.10.000",
    "Mematuhi dan melaksanakan peraturan yang berlaku di PT.Telkom",
    "Bersedia menggunakan alat komunikasi produk Telkom Group contohnya: Telkomsel",
    "Mendapatkan surat keterangan jika sudah selesai melaksanakan praktik kerja lapangan",
    "Semua biaya yang timbul selama melaksanakan praktik kerja lapangan ditanggung sendiri dan tidak diberikan kompensasi/uang makan dan transport.",
    "Mendapatkan tanda pengenal/nametag PKL dan memakainya selama masa praktek kerja lapangan.",
    "Mengembalikan nametag saat masa praktik kerja lapangan telah selesai. Jika nametag hilang, bersedia membayar denda sebesar Rp. 25.000.00."
];

foreach($poin as $i => $p) {
    $pdf->MultiCell(0,5,($i+1).". ".$p,0,'J');
}
$pdf->Ln(4);

// === Penutup ===
$penutup = "Demikian disampaikan, atas perhatian dan kerja samanya kami ucapkan terima kasih.";
$pdf->MultiCell(0,7,$penutup,0,'J');
$pdf->Ln(4);

// === TTD + QR ===
$pdf->Cell(0,6,"PT Telkom Indonesia (Persero) Tbk",0,1,align: 'L');
$pdf->Ln(0);

// === Buat QR ===
$qrData = "Ditandatangani secara digital oleh:\n" .
           "ROSANA INTAN PERMATASARI\n" .
           "Manager Shared Service & General Support\n" .
           "PT TELKOM INDONESIA (PERSERO) Tbk.\n" .
           "Tanggal: $tanggal";

ob_start();
QRcode::png($qrData, null, QR_ECLEVEL_H, 4);
$qrImage = ob_get_contents();
ob_end_clean();

$tempQR = tempnam(sys_get_temp_dir(), 'qr_') . ".png";
file_put_contents($tempQR, $qrImage);


// QR di kiri
$pdf->Image($tempQR, 25, $pdf->GetY(), 25);
unlink($tempQR);


$pdf->Ln(25);
$pdf->SetFont('Times','B',11);
$pdf->Cell(0,6,"ROSANA INTAN PERMATASARI",0,1,'L');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,"Manager Shared Service & General Support",0,1,'L');

// === Kop Bawah (Footer) ===
$kopBawah = __DIR__ . '/template/kop surat footer telkom (1).png';
if (file_exists($kopBawah)) {
    $pageHeight   = $pdf->GetPageHeight();
    $footerHeight = 20;
    $posY         = $pageHeight - $footerHeight;
    $pdf->Image($kopBawah, 0, $posY, 210, $footerHeight);
}

// ================== Output ==================
$pdf->Output('I', 'Surat_Balasan.pdf');
