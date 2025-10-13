<?php
session_start();
require('../../config/database.php');
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php');

// Fungsi ubah format tanggal ke Indonesia
function formatTanggalIndo($tanggal) {
    if (!$tanggal) return "-";
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecah = explode('-', $tanggal); // [YYYY, MM, DD]
    if (count($pecah) < 3) return $tanggal; // fallback kalau format salah
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}

$userId = $_SESSION['user_id'];

// ================== Ambil Data Peserta ==================
$query = "SELECT 
            p.nama, 
            p.nis_npm, 
            p.jurusan, 
            p.instansi_pendidikan, 
            p.tgl_mulai, 
            p.tgl_selesai, 
            p.unit_id,
            u.nama_unit
          FROM peserta_pkl p
          LEFT JOIN unit_pkl u ON p.unit_id = u.id
          WHERE p.user_id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query peserta gagal disiapkan: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Data peserta tidak ditemukan.");
}

// Variabel dari tabel peserta_pkl
$nama_peserta   = $data['nama'];
$nim_peserta    = $data['nis_npm'];
$jurusan        = $data['jurusan'];
$universitas    = $data['instansi_pendidikan'];
$tanggalMulai   = formatTanggalIndo($data['tgl_mulai']);
$tanggalSelesai = formatTanggalIndo($data['tgl_selesai']);
$lokasiPKL      = $data['nama_unit'] ?? '-';
$tanggal        = formatTanggalIndo(date( "Y-m-d"));

// ================== Ambil Data dari daftar_pkl ==================
$sql2 = "SELECT nomor_surat_permohonan, tgl_daftar 
         FROM daftar_pkl 
         WHERE email = (SELECT email FROM users WHERE id = ?)";
$stmt2 = $conn->prepare($sql2);
if (!$stmt2) {
    die("Query daftar_pkl gagal disiapkan: " . $conn->error);
}
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$res2 = $stmt2->get_result();
$row2 = $res2->fetch_assoc();

$no_surat     = $row2['nomor_surat_permohonan'] ?? "C.TEL.232/PD.000/RW-301/0000/2025";
$tanggalSurat = isset($row2['tgl_daftar']) ? formatTanggalIndo($row2['tgl_daftar']) : formatTanggalIndo(date("Y-m-d"));

// ================== PDF ==================
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();

// === Logo Telkom ===
$logoTelkom = __DIR__ . '/../assets/img/logo_telkom.png';
if (file_exists($logoTelkom)) {
    $pdf->Image($logoTelkom, 160, 5, 40);
}
$pdf->Ln(0);

// === Header Surat ===
$pdf->SetFont('Times', '', 11);
$pdf->Cell(0, 6, "Nomor   : $no_surat", 0, 1, 'L');
$pdf->Cell(0, 6, "Bekasi, $tanggal", 0, 1, 'L');
$pdf->Ln(4);

$pdf->MultiCell(0, 6, "Kepada,\n$universitas", 0, 'L');
$pdf->Ln(4);

// === Isi Surat ===
$pdf->SetFont('Times', '', 11);
$isi = "Dengan hormat,\n"
     . "Menjawab surat Saudara dengan nomor : $no_surat yang telah kami terima tanggal $tanggalSurat "
     . "perihal Permohonan PKL/Magang Mahasiswa/siswa dari $universitas dan untuk mewujudkan para peserta didik yang memiliki "
     . "kompetensi akademik serta memiliki wawasan praktis serta untuk memenuhi tugas. "
     . "Untuk dapat mewujudkan link and match dan pengalaman kerja di dunia usaha khusus di lokasi Kota Bekasi - Karawang, Jawa Barat, "
     . "maka dengan ini kami sampaikan bahwa pada prinsipnya dapat kami setujui atas nama:\n\n";
$pdf->MultiCell(0, 5, $isi, 0, 'J');

// === Tabel Peserta ===
$pdf->SetFont('Times', 'B', 11);
$pdf->Cell(70, 6, "Nama", 1, 0, 'C');
$pdf->Cell(40, 6, "NIM/NIS", 1, 0, 'C');
$pdf->Cell(60, 6, "Jurusan", 1, 1, 'C');

$pdf->SetFont('Times', '', 11);
$pdf->Cell(70, 6, $nama_peserta, 1, 0, 'C');
$pdf->Cell(40, 6, $nim_peserta, 1, 0, 'C');
$pdf->Cell(60, 6, $jurusan, 1, 1, 'C');
$pdf->Ln(5);

// === Paragraf Lanjutan ===
$isi2 = "Untuk itu mahasiswa tersebut akan kami tempatkan di area Witel Bekasi Karawang di Unit "
      . "$lokasiPKL terhitung mulai tanggal $tanggalMulai s/d $tanggalSelesai. "
      . "Untuk pelaksanaannya, 1 (satu) hari sebelum dimulai kegiatan praktik kerja lapangan, "
      . "mahasiswa tersebut agar melapor terlebih dahulu ke unit HC Telkom Bekasi Karawang "
      . "Jl. Rawa Tembaga No.4 Bekasi, Tlp 02188895200 dengan membawa materai @10.000 "
      . "untuk menandatangani surat pernyataan yang berkaitan dengan pelaksanaan praktik kerja "
      . "lapangan di PT. TELKOM.\n\n"
      . "Adapun hak dan kewajiban peserta PKL adalah sebagai berikut:";
$pdf->MultiCell(0, 5, $isi2, 0, 'J');

// === Poin-poin ===
$poin = [
    "Menandatangani surat pernyataan praktik kerja lapangan bermaterai Rp.10.000",
    "Mematuhi dan melaksanakan peraturan yang berlaku di PT.Telkom",
    "Bersedia menggunakan alat komunikasi produk Telkom Group contohnya: Telkomsel",
    "Mendapatkan surat keterangan jika sudah selesai melaksanakan praktik kerja lapangan",
    "Semua biaya yang timbul selama melaksanakan praktik kerja lapangan ditanggung sendiri dan tidak diberikan kompensasi/uang makan dan transport.",
    "Mendapatkan tanda pengenal/nametag PKL dan memakainya selama masa praktek kerja lapangan.",
    "Mengembalikan nametag saat masa praktik kerja lapangan telah selesai. Jika nametag hilang, bersedia membayar denda sebesar Rp. 25.000.00."
];

foreach ($poin as $i => $p) {
    $pdf->MultiCell(0, 5, ($i + 1) . ". " . $p, 0, 'J');
}
$pdf->Ln(4);

// === Penutup ===
$pdf->MultiCell(0, 7, "Demikian disampaikan, atas perhatian dan kerja samanya kami ucapkan terima kasih.", 0, 'J');
$pdf->Ln(4);

// === TTD + QR ===
$pdf->Cell(0, 6, "PT Telkom Indonesia (Persero) Tbk", 0, 1, 'L');

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

// Tampilkan QR
$pdf->Image($tempQR, 25, $pdf->GetY(), 25);
unlink($tempQR);

$pdf->Ln(25);
$pdf->SetFont('Times', 'B', 11);
$pdf->Cell(0, 6, "ROSANA INTAN PERMATASARI", 0, 1, 'L');
$pdf->SetFont('Times', '', 11);
$pdf->Cell(0, 6, "Manager Shared Service & General Support", 0, 1, 'L');

// === Kop Bawah ===
$kopBawah = __DIR__ . '/template/kop surat footer telkom (1).png';
if (file_exists($kopBawah)) {
    $pageHeight   = $pdf->GetPageHeight();
    $footerHeight = 20;
    $posY         = $pageHeight - $footerHeight;
    $pdf->Image($kopBawah, 0, $posY, 210, $footerHeight);
}

// === Output PDF ===
$pdf->Output('I', 'Surat_Balasan.pdf');
?>
