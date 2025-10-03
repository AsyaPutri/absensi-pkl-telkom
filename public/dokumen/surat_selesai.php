<?php
session_start();
require('../../config/database.php');    
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php'); // ⬅️ tambahkan phpqrcode

/* helper */
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
    return $ts===false?'-':date("d F Y", $ts);
}

/* pastikan login */
if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}
$role   = $_SESSION['role'] ?? '';
$userId = intval($_SESSION['user_id']);

/* 1) ambil data peserta */
if ($role === 'admin' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sqlP = "SELECT p.* FROM peserta_pkl p WHERE p.id = $id LIMIT 1";
} else {
    $sqlP = "SELECT p.* FROM peserta_pkl p WHERE p.user_id = $userId LIMIT 1";
}
$resP = mysqli_query($conn, $sqlP) or die("Query Error: " . mysqli_error($conn));
$part = mysqli_fetch_assoc($resP);
if (!$part) die("Data peserta_pkl tidak ditemukan.");

/* 2) ambil data daftar_pkl */
$daftar = [];
$emailCandidate = getFirstValue($part, ['email','email_peserta','email_user'], null);
if ($emailCandidate) {
    $emailEsc = mysqli_real_escape_string($conn, $emailCandidate);
    $sqlD = "SELECT d.* FROM daftar_pkl d WHERE d.email = '$emailEsc' LIMIT 1";
    $resD = mysqli_query($conn, $sqlD);
    if ($resD && mysqli_num_rows($resD) > 0) {
        $daftar = mysqli_fetch_assoc($resD);
    }
}

/* 3) cari unit */
$unitColCandidates = ['unit','unit_witel','id_unit','unit_id','idunit','id_unit_pkl','id_unitw','id_witel','unit_kerja','unit_pkl'];
$unitCandidateValue = null;
foreach ($unitColCandidates as $c) {
    if (!empty($part[$c])) {
        $unitCandidateValue = $part[$c];
        break;
    }
}
function resolveUnitName($conn, $candidate) {
    if (!$candidate) return null;
    if (!is_numeric($candidate)) return trim($candidate);

    $val = intval($candidate);
    $tableCandidates = ['unit_pkl','unit','unit_kerja','units','unitmaster'];
    $nameCols = ['nama_unit','name','unit_name','nama','unit','title','nama_unit_pkl'];
    $idCols   = ['id','id_unit','unit_id','idunit','id_unit_pkl','id_unitw','id_witel'];

    foreach ($tableCandidates as $table) {
        foreach ($nameCols as $nameCol) {
            foreach ($idCols as $idCol) {
                $sql = "SELECT `$nameCol` AS uname FROM `$table` WHERE `$idCol` = $val LIMIT 1";
                $q = @mysqli_query($conn, $sql);
                if ($q && mysqli_num_rows($q) > 0) {
                    $row = mysqli_fetch_assoc($q);
                    if (!empty($row['uname'])) return $row['uname'];
                }
            }
        }
    }
    return null;
}
$unitName = $unitCandidateValue ? resolveUnitName($conn, $unitCandidateValue) : null;
if (!$unitName && !empty($daftar)) {
    $unitName = getFirstValue($daftar, ['unit','unit_witel','unit_kerja','unit_pkl'], null);
}
$unitName = $unitName ?: '-';

/* 4) gabungan data */
$combined = array_merge($part ?: [], $daftar ?: []);
$nama    = getFirstValue($combined, ['nama','nama_lengkap','full_name'], 'Nama Tidak Diketahui');
$nim     = getFirstValue($combined, ['nim','nis_npm','nis','npm','no_induk','nrp'], '-');
$jurusan = getFirstValue($combined, ['jurusan','prodi','program_studi','jurusan_pendidikan'], '-');
$instansi= getFirstValue($combined, ['instansi_pendidikan','instansi','asal_sekolah','kampus'], '-');
$tglMulai   = formatDateNice(getFirstValue($combined, ['tgl_mulai','tanggal_mulai','start_date'], null));
$tglSelesai = formatDateNice(getFirstValue($combined, ['tgl_selesai','tanggal_selesai','end_date'], null));

/* === Generate PDF === */
$pdf = new FPDF('P','mm','A4');
$pdf->SetMargins(30.5, 20, 30);
$pdf->SetAutoPageBreak(true, 30); 
$pdf->AddPage();

// Logo Telkom
$logoTelkom = __DIR__ . '../../assets/img/logo_telkom.png';
if (file_exists($logoTelkom)) {
    $pdf->Image($logoTelkom, 160, 5, 45);
}

// Kop bawah (footer)
$kopBawah = __DIR__ . '/template/kop surat footer telkom (1).png';
if (file_exists($kopBawah)) {
    $pageHeight = $pdf->GetPageHeight();  // tinggi halaman A4 (biasanya 297 mm)
    $footerHeight = 15;                   // tinggi gambar footer (atur sesuai proporsi)
    $posY = $pageHeight - $footerHeight - 5; // margin bawah 10 mm
    $pdf->Image($kopBawah, 15, $posY, 190, $footerHeight);
}


// Judul
$pdf->Ln(20);
$pdf->SetFont('Times','B',14);
$pdf->Cell(0,7,'SURAT KETERANGAN',0,1,'C');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,'Nomor: C.TEL.xxx/PD.xxx/2025',0,1,'C');
$pdf->Ln(8);

// Data pemberi
$pdf->SetFont('Times','',11);
$pdf->MultiCell(0,6,"Bersama surat ini menerangkan bahwa:",0,'L');
$pdf->Ln(3);
$pdf->Cell(50,6,"Nama",0,0,'L');    $pdf->Cell(0,6,": ROSANA INTAN PERMATASARI",0,1,'L');
$pdf->Cell(50,6,"NIK",0,0,'L');     $pdf->Cell(0,6,": 750065",0,1,'L');
$pdf->Cell(50,6,"Alamat Kantor",0,0,'L'); $pdf->Cell(0,6,": Jl. Rawa Tembaga No. 4 Bekasi",0,1,'L');
$pdf->Ln(6);

// Data peserta
$pdf->MultiCell(0,6,"Yang bertanda tangan di bawah ini menerangkan bahwa Siswa/Mahasiswa:",0,'L');
$pdf->Ln(2);
$pdf->Cell(50,6,"Nama",0,0,'L');    $pdf->Cell(0,6,": $nama",0,1,'L');
$pdf->Cell(50,6,"NIM/NIS",0,0,'L'); $pdf->Cell(0,6,": $nim",0,1,'L');
$pdf->Cell(50,6,"Program Studi",0,0,'L'); $pdf->Cell(0,6,": $jurusan",0,1,'L');
$pdf->Cell(50,6,"Instansi Pendidikan",0,0,'L'); $pdf->Cell(0,6,": $instansi",0,1,'L');
$pdf->Ln(6);

// Isi PKL
$isi = "Telah menyelesaikan kegiatan Praktik Kerja Lapangan (PKL) di unit $unitName, "
     . "terhitung mulai tanggal $tglMulai sampai dengan $tglSelesai.";
$isi .= " Selama mengikuti kegiatan Kerja Praktek Lapangan, Siswa/Mahasiswa yang bersangkutan telah bekerja dengan sangat baik.";
$pdf->MultiCell(0, 6, $isi, 0, 'J');
$pdf->Ln(8);

// Penutup
$pdf->MultiCell(0,6,"Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya. Terima kasih.",0,'J');
$pdf->Ln(12);

// Tanggal & TTD
$today = date("d F Y");
$pdf->Cell(0,6,"Bekasi, $today",0,1,'R');
$pdf->Cell(0,6,"Mengetahui,",0,1,'R');
$pdf->Ln(4);

/* === Generate QR Code sebagai pengganti TTD === */
$tempDir = __DIR__ . "/temp/";
if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

$qrData = "Ditandatangani secara digital oleh:\n"
        . "ROSANA INTAN PERMATASARI\n"
        . "MANAGER SHARED SERVICE & GENERAL SUPPORT\n"
        . "Tanggal: $today";
$qrFile = $tempDir . "qr_ttd_" . time() . ".png";

// buat QR
QRcode::png($qrData, $qrFile, QR_ECLEVEL_H, 5);

// masukkan ke PDF
$pdf->Image($qrFile, 130, $pdf->GetY(), 30); 
$pdf->Ln(35);

// Nama & Jabatan
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,6,'ROSANA INTAN PERMATASARI',0,1,'R');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,'MANAGER SHARED SERVICE &',0,1,'R');
$pdf->Cell(0,6,'GENERAL SUPPORT',0,1,'R');

// Output
$filenameSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama);
$pdf->Output('I', "surat_selesai_{$filenameSafe}.pdf");
exit;
