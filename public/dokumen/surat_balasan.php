<?php
session_start();
require('../../config/database.php');    
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php'); 

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
$pdf->SetMargins(25, 20, 25);
$pdf->SetAutoPageBreak(true, 30); 
$pdf->AddPage();

// Logo Telkom (kop atas)
$logoTelkom = __DIR__ . '../../assets/img/logo_telkom.png';
if (file_exists($logoTelkom)) {
    $pdf->Image($logoTelkom, 160, 5, 40);
}

// Kop bawah (footer)
$kopBawah = __DIR__ . '/template/kop surat footer telkom (1).png';
if (file_exists($kopBawah)) {
    $pdf->Image($kopBawah, 0, 270, 210); 
}

// === Header Surat ===
$today = date("d F Y");
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,'Nomor: C.TEL.xxx/PD.xxx/2025',0,1,'L');
$pdf->Cell(0,6,"Bekasi, $today",0,1,'L');
$pdf->Ln(4);

$pdf->Cell(0,6,'Kepada Yth.',0,1,'L');
$pdf->Cell(0,6,'Dekan Fakultas Teknik Telekomunikasi',0,1,'L');
$pdf->Cell(0,6,$instansi,0,1,'L');
$pdf->Ln(6);

// === Isi surat ===
$isi = "Dengan hormat,\n\n"
     ."Sehubungan dengan surat permohonan PKL yang telah kami terima, "
     ."bersama ini kami sampaikan bahwa permohonan tersebut dapat kami terima. "
     ."Adapun mahasiswa yang dapat mengikuti PKL di PT Telkom Indonesia (Persero) Tbk Witel $unitName adalah sebagai berikut:\n\n";
$pdf->MultiCell(0,6,$isi,0,'J');

// === Tabel Peserta ===
$pdf->SetFont('Times','B',11);
$pdf->Cell(10,8,'No',1,0,'C');
$pdf->Cell(65,8,'Nama',1,0,'C');
$pdf->Cell(40,8,'NIM/NIS',1,0,'C');
$pdf->Cell(65,8,'Jurusan',1,1,'C');

$pdf->SetFont('Times','',11);
$pdf->Cell(10,8,'1',1,0,'C');
$pdf->Cell(65,8,$nama,1,0,'L');
$pdf->Cell(40,8,$nim,1,0,'C');
$pdf->Cell(65,8,$jurusan,1,1,'L');
$pdf->Ln(6);

// Paragraf lanjutan
$pdf->MultiCell(0,6,
 "Mahasiswa tersebut ditempatkan di Unit $unitName, dengan jadwal kegiatan terhitung mulai tanggal $tglMulai sampai dengan $tglSelesai.\n\n".
 "Dimohon kepada pihak instansi untuk menyampaikan pembekalan kepada mahasiswa yang bersangkutan mengenai peraturan dan tata tertib PKL di lingkungan PT Telkom Indonesia (Persero) Tbk.\n\n".
 "Demikian surat balasan ini kami sampaikan. Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.",
0,'J');
$pdf->Ln(12);

// === Tanda tangan QR ===
$pdf->Cell(0,6,"Bekasi, $today",0,1,'R');
$pdf->Cell(0,6,"Hormat Kami,",0,1,'R');
$pdf->Ln(4);

$tempDir = __DIR__ . "/temp/";
if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

$qrData = "Ditandatangani secara digital oleh:\n"
        . "ROSANA INTAN PERMATASARI\n"
        . "MANAGER SHARED SERVICE & GENERAL SUPPORT\n"
        . "Tanggal: $today";
$qrFile = $tempDir . "qr_ttd_" . time() . ".png";

QRcode::png($qrData, $qrFile, QR_ECLEVEL_H, 5);
$pdf->Image($qrFile, 130, $pdf->GetY(), 30); 
$pdf->Ln(35);

$pdf->SetFont('Times','B',12);
$pdf->Cell(0,6,'ROSANA INTAN PERMATASARI',0,1,'R');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,6,'MANAGER SHARED SERVICE &',0,1,'R');
$pdf->Cell(0,6,'GENERAL SUPPORT',0,1,'R');

// Output
$filenameSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama);
$pdf->Output('I', "surat_balasan_pkl_{$filenameSafe}.pdf");
exit;
