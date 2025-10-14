<?php
session_start();
require('../../config/database.php');
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php');

function formatTanggalIndo($tanggal) {
    if (!$tanggal) return "-";
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecah = explode('-', $tanggal);
    if (count($pecah) < 3) return $tanggal;
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}

$userId = $_SESSION['user_id'];

// ================== Ambil Data Peserta + Nomor Surat Permohonan ==================
$query = "SELECT 
            p.nama, 
            p.nis_npm, 
            p.jurusan, 
            p.instansi_pendidikan, 
            p.tgl_mulai, 
            p.tgl_selesai, 
            p.unit_id,
            p.nomor_surat,
            u.nama_unit,
            d.nomor_surat AS nomor_surat_permohonan,
            d.tgl_daftar
          FROM peserta_pkl p
          LEFT JOIN unit_pkl u ON p.unit_id = u.id
          LEFT JOIN daftar_pkl d ON d.email = (SELECT email FROM users WHERE id = p.user_id)
          WHERE p.user_id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query peserta gagal disiapkan: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) die("Data peserta tidak ditemukan.");

// ===== Variabel dari database =====
$nama_peserta   = $data['nama'];
$nim_peserta    = $data['nis_npm'];
$jurusan        = $data['jurusan'];
$universitas    = $data['instansi_pendidikan'];
$tanggalMulai   = formatTanggalIndo($data['tgl_mulai']);
$tanggalSelesai = formatTanggalIndo($data['tgl_selesai']);
$lokasiPKL      = $data['nama_unit'] ?? '-';
$tanggal        = formatTanggalIndo(date("Y-m-d"));
$no_permohonan  = $data['nomor_surat_permohonan'] ?? "-";
$tanggalSurat   = isset($data['tgl_daftar']) ? formatTanggalIndo($data['tgl_daftar']) : "-";

// ===== Generate Nomor Surat Balasan Telkom =====
if (empty($data['nomor_surat'])) {
    // Ambil nomor terakhir
    $sqlNo = "SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(nomor_surat_balasan, '/', 1), '.', -1) AS UNSIGNED)) AS last_no 
              FROM peserta_pkl WHERE nomor_surat IS NOT NULL";
    $resultNo = $conn->query($sqlNo);
    $rowNo = $resultNo->fetch_assoc();
    $lastNo = $rowNo['last_no'] ? (int)$rowNo['last_no'] : 0;
    $newNo = str_pad($lastNo + 1, 3, "0", STR_PAD_LEFT); // 3 digit

    // Format tetap seperti contoh
    $no_surat_balasan = "C.TEL.$newNo/PD.000/R2W-2G10000/2025";

    // Simpan nomor ke database agar tidak berubah
    $update = $conn->prepare("UPDATE peserta_pkl SET nomor_surat = ? WHERE user_id = ?");
    $update->bind_param("si", $no_surat_balasan, $userId);
    $update->execute();
} else {
    $no_surat_balasan = $data['nomor_surat'];
}

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
$pdf->Cell(0, 6, "Nomor   : $no_surat_balasan", 0, 1, 'L');
$pdf->Cell(0, 6, "Bekasi, $tanggal", 0, 1, 'L');
$pdf->Ln(4);

$pdf->MultiCell(0, 6, "Kepada,\n$universitas", 0, 'L');
$pdf->Ln(4);

// === Isi Surat ===
$pdf->SetFont('Times', '', 11);
$isi = "Dengan hormat,\n"
     . "Menjawab surat Saudara dengan nomor : $no_permohonan yang telah kami terima tanggal $tanggalSurat "
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
