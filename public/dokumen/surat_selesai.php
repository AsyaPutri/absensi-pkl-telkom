<?php
session_start();
require('../../config/database.php');
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php');

/* =====================================================
   🧩 Helper Functions
===================================================== */
function getFirstValue(array $row, array $candidates, $default = '') {
    foreach ($candidates as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}

function formatDateNice($raw) {
    if (!$raw) return '-';
    $ts = strtotime($raw);
    return $ts === false ? '-' : date("d F Y", $ts);
}

/* =====================================================
   🔒 Pastikan user sudah login
===================================================== */
if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}

$role   = $_SESSION['role'] ?? '';
$userId = intval($_SESSION['user_id']);

/* =====================================================
   🧾 1️⃣ Ambil data peserta + nama unit langsung
===================================================== */
if ($role === 'admin') {
    // Admin bisa akses lewat ?user_id= atau ?id=
    if (isset($_GET['user_id'])) {
        $uid = intval($_GET['user_id']);
        $sqlP = "
            SELECT p.*, u.nama_unit 
            FROM peserta_pkl p
            LEFT JOIN unit_pkl u ON p.unit_id = u.id
            WHERE p.user_id = $uid
            LIMIT 1
        ";
    } elseif (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sqlP = "
            SELECT p.*, u.nama_unit 
            FROM peserta_pkl p
            LEFT JOIN unit_pkl u ON p.unit_id = u.id
            WHERE p.id = $id
            LIMIT 1
        ";
    } else {
        die("Parameter ID tidak ditemukan.");
    }
} else {
    // Jika user biasa (bukan admin)
    $sqlP = "
        SELECT p.*, u.nama_unit 
        FROM peserta_pkl p
        LEFT JOIN unit_pkl u ON p.unit_id = u.id
        WHERE p.user_id = $userId
        LIMIT 1
    ";
}

/* =====================================================
   🧾 2️⃣ Eksekusi Query Peserta
===================================================== */
$resP = mysqli_query($conn, $sqlP) or die("Query Error: " . mysqli_error($conn));
$part = mysqli_fetch_assoc($resP);

// Jika tidak ditemukan di peserta_pkl, coba cari di riwayat
if (!$part) {
    if ($role === 'admin' && isset($_GET['user_id'])) {
        $uid = intval($_GET['user_id']);
        $sqlR = "
            SELECT r.*, u.nama_unit 
            FROM riwayat_peserta_pkl r
            LEFT JOIN unit_pkl u ON r.unit_id = u.id
            WHERE r.user_id = $uid
            LIMIT 1
        ";
    } elseif ($role === 'admin' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sqlR = "
            SELECT r.*, u.nama_unit 
            FROM riwayat_peserta_pkl r
            LEFT JOIN unit_pkl u ON r.unit_id = u.id
            WHERE r.id = $id
            LIMIT 1
        ";
    } else {
        $sqlR = "
            SELECT r.*, u.nama_unit 
            FROM riwayat_peserta_pkl r
            LEFT JOIN unit_pkl u ON r.unit_id = u.id
            WHERE r.user_id = $userId
            LIMIT 1
        ";
    }

    $resR = mysqli_query($conn, $sqlR) or die("Query Error Riwayat: " . mysqli_error($conn));
    $part = mysqli_fetch_assoc($resR);

    if (!$part) {
        die("Data peserta_pkl atau riwayat_peserta_pkl tidak ditemukan.");
    }
}

/* 🔒 Cek status peserta */
$status = strtolower(trim($part['status'] ?? ''));

/* Jika status bukan 'selesai', tampilkan SweetAlert */
if ($status !== 'selesai') {
    echo "
    <html>
    <head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
        Swal.fire({
            icon: 'info',
            title: 'Tidak Bisa Mencetak Surat',
            text: 'Surat keterangan ini hanya bisa dicetak setelah periode magang Anda dinyatakan selesai.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d32f2f'
        }).then(() => {
            window.location.href='../magang/cetak_surat.php';
        });
        </script>
    </body>
    </html>
    ";
    exit;
}

/* === Ambil nomor surat dari peserta_pkl === */
$nomorSurat = isset($part['nomor_surat']) ? intval($part['nomor_surat']) : 0;
if ($nomorSurat <= 0) {
    $nomorSurat = 1;
}
$tahun = date('Y');
$noFormat = str_pad($nomorSurat, 3, '0', STR_PAD_LEFT);
$nomorSuratLengkap = "C.TEL.$noFormat/PD.000/R2W-2G10000/$tahun";

/* 2️⃣ Ambil data daftar_pkl */
$emailCandidate = getFirstValue($part, ['email','email_peserta','email_user'], null);
$daftar = [];
if ($emailCandidate) {
    $emailEsc = mysqli_real_escape_string($conn, $emailCandidate);
    $sqlD = "SELECT * FROM daftar_pkl WHERE email = '$emailEsc' LIMIT 1";
    $resD = mysqli_query($conn, $sqlD);
    if ($resD && mysqli_num_rows($resD) > 0) {
        $daftar = mysqli_fetch_assoc($resD);
    }
}

/* 3️⃣ Gabungkan data */
$combined = array_merge($part ?: [], $daftar ?: []);
$nama      = getFirstValue($combined, ['nama','nama_lengkap','full_name'], 'Nama Tidak Diketahui');
$nim       = getFirstValue($combined, ['nim','nis_npm','nis','npm','no_induk','nrp'], '-');
$jurusan   = getFirstValue($combined, ['jurusan','prodi','program_studi','jurusan_pendidikan'], '-');
$instansi  = getFirstValue($combined, ['instansi_pendidikan','instansi','asal_sekolah','kampus'], '-');
$tglMulai  = formatDateNice(getFirstValue($combined, ['tgl_mulai','tanggal_mulai','start_date'], null));
$tglSelesai= formatDateNice(getFirstValue($combined, ['tgl_selesai','tanggal_selesai','end_date'], null));
$unitName  = !empty($part['nama_unit']) ? $part['nama_unit'] : '-';

/* 4️⃣ Generate PDF */
$pdf = new FPDF('P','mm','A4');
$pdf->SetMargins(30.5, 20, 30);
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

/* Logo Telkom */
$logoTelkom = __DIR__ . '/../assets/img/logo_telkom.png';
if (file_exists($logoTelkom)) {
    $pdf->Image($logoTelkom, 160, 5, 45);
}

/* Kop bawah */
$kopBawah = __DIR__ . '/template/kop surat footer telkom (1).png';
if (file_exists($kopBawah)) {
    $pageHeight   = $pdf->GetPageHeight();
    $footerHeight = 20;
    $posY         = $pageHeight - $footerHeight;
    $pdf->Image($kopBawah, 0, $posY, 210, $footerHeight);
}

/* Judul */
$pdf->Ln(20);
$pdf->SetFont('Times','B',14);
$pdf->Cell(0,7,'SURAT KETERANGAN',0,1,'C');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,"Nomor: $nomorSuratLengkap",0,1,'C');
$pdf->Ln(8);

/* Data pemberi */
$pdf->SetFont('Times','',11);
$pdf->MultiCell(0,6,"Bersama surat ini menerangkan bahwa:",0,'L');
$pdf->Ln(3);
$pdf->Cell(50,6,"Nama",0,0,'L');    $pdf->Cell(0,6,": ROSANA INTAN PERMATASARI",0,1,'L');
$pdf->Cell(50,6,"NIK",0,0,'L');     $pdf->Cell(0,6,": 750065",0,1,'L');
$pdf->Cell(50,6,"Alamat Kantor",0,0,'L'); $pdf->Cell(0,6,": Jl. Rawa Tembaga No. 4 Bekasi",0,1,'L');
$pdf->Ln(6);

/* Data peserta */
$pdf->MultiCell(0,6,"Yang bertanda tangan di bawah ini menerangkan bahwa Siswa/Mahasiswa:",0,'L');
$pdf->Ln(2);
$pdf->Cell(50,6,"Nama",0,0,'L');    $pdf->Cell(0,6,": $nama",0,1,'L');
$pdf->Cell(50,6,"NIM/NIS",0,0,'L'); $pdf->Cell(0,6,": $nim",0,1,'L');
$pdf->Cell(50,6,"Program Studi",0,0,'L'); $pdf->Cell(0,6,": $jurusan",0,1,'L');
$pdf->Cell(50,6,"Instansi Pendidikan",0,0,'L'); $pdf->Cell(0,6,": $instansi",0,1,'L');
$pdf->Ln(6);

/* Isi PKL */
$isi = "Telah menyelesaikan kegiatan Praktik Kerja Lapangan (PKL) di unit $unitName, "
     . "terhitung mulai tanggal $tglMulai sampai dengan $tglSelesai. "
     . "Selama mengikuti kegiatan Kerja Praktek Lapangan, Siswa/Mahasiswa yang bersangkutan telah bekerja dengan sangat baik.";
$pdf->MultiCell(0, 6, $isi, 0, 'J');
$pdf->Ln(8);

/* Penutup */
$pdf->MultiCell(0,6,"Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya. Terima kasih.",0,'J');
$pdf->Ln(12);

/* Tanggal & TTD */
$today = date("d F Y");
$pdf->Cell(0,6,"Bekasi, $today",0,1,'L');
$pdf->Cell(0,6,"Mengetahui,",0,1,'L');
$pdf->Ln(4);

/* === QR Code === */
$tempDir = __DIR__ . "/temp/";
if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

$qrData = "Ditandatangani secara digital oleh:\n"
        . "ROSANA INTAN PERMATASARI\n"
        . "MANAGER SHARED SERVICE & GENERAL SUPPORT\n"
        . "Tanggal: $today";
$qrFile = $tempDir . "qr_ttd_" . time() . ".png";
QRcode::png($qrData, $qrFile, QR_ECLEVEL_H, 5);

$pdf->Image($qrFile, 30, $pdf->GetY(), 30);
$pdf->Ln(35);

/* Nama & Jabatan */
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,6,'ROSANA INTAN PERMATASARI',0,1,'L');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,'MANAGER SHARED SERVICE &',0,1,'L');
$pdf->Cell(0,6,'GENERAL SUPPORT',0,1,'L');

/* Output */
$filenameSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama);
$pdf->Output('I', "surat_selesai_{$filenameSafe}.pdf");
exit;
?>
