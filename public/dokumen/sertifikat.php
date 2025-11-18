<?php
session_start();
require('../../config/database.php');
require(__DIR__ . '/fpdf/fpdf.php');
require(__DIR__ . '/phpqrcode/qrlib.php');

/* Helper (tetap) */
function getFirstValue(array $row, array $candidates, $default = '') {
    foreach ($candidates as $k) {
        if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}

/* Pastikan login */
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}
$role   = $_SESSION['role'];
$meId   = intval($_SESSION['user_id']);

// Ambil parameter dari URL (dukung user_id / riwayat_id / id)
$param_user_id  = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$param_riwayat  = isset($_GET['riwayat_id']) ? intval($_GET['riwayat_id']) : null;
$param_id       = isset($_GET['id']) ? intval($_GET['id']) : null;

$data = null;

/* Jika admin, prioritas: id -> user_id -> riwayat_id (tapi terima semua)
   Jika bukan admin, ambil dari user session (user_id) */
if ($role === 'admin') {
    // 1) coba id (peserta_pkl.id)
    if ($param_id) {
        $sql = "SELECT p.*, d.tgl_mulai, d.tgl_selesai, d.durasi, u.nama_unit
                FROM peserta_pkl p
                LEFT JOIN daftar_pkl d ON p.email = d.email
                LEFT JOIN unit_pkl u ON p.unit_id = u.id
                WHERE p.id = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $param_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        $stmt->close();
    }

    // 2) kalau belum, coba user_id => cari di peserta_pkl berdasarkan user_id
    if (!$data && $param_user_id) {
        $sql = "SELECT p.*, d.tgl_mulai, d.tgl_selesai, d.durasi, u.nama_unit
                FROM peserta_pkl p
                LEFT JOIN daftar_pkl d ON p.email = d.email
                LEFT JOIN unit_pkl u ON p.unit_id = u.id
                WHERE p.user_id = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $param_user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        $stmt->close();
    }

    // 3) kalau belum, fallback cari di riwayat_peserta_pkl berdasarkan riwayat_id atau user_id
    if (!$data) {
        if ($param_riwayat) {
            $sql = "SELECT r.*, u.nama_unit FROM riwayat_peserta_pkl r LEFT JOIN unit_pkl u ON r.unit_id=u.id WHERE r.id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $param_riwayat);
            $stmt->execute();
            $res = $stmt->get_result();
            $data = $res->fetch_assoc();
            $stmt->close();
        } elseif ($param_user_id) {
            $sql = "SELECT r.*, u.nama_unit FROM riwayat_peserta_pkl r LEFT JOIN unit_pkl u ON r.unit_id=u.id WHERE r.user_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $param_user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $data = $res->fetch_assoc();
            $stmt->close();
        }
    }

    if (!$data) {
        die("ID peserta tidak ditemukan. Admin harus memanggil ?id=ID_PESERTA atau ?user_id=USER_ID atau ?riwayat_id=ID_RIWAYAT");
    }

} else {
    // user biasa: hanya boleh cetak untuk dirinya sendiri (pakai session user_id)
    $sql = "SELECT p.*, d.tgl_mulai, d.tgl_selesai, d.durasi, u.nama_unit
            FROM peserta_pkl p
            LEFT JOIN daftar_pkl d ON p.email = d.email
            LEFT JOIN unit_pkl u ON p.unit_id = u.id
            WHERE p.user_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $meId);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if (!$data) die("Data peserta tidak ditemukan.");
}

/* 🔒 Cek status peserta */
$status = strtolower(trim($data['status'] ?? ''));

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
            title: 'Tidak Bisa Mencetak Sertifikat',
            text: 'Sertifikat ini hanya bisa dicetak setelah periode magang Anda dinyatakan selesai.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d32f2f'
        }).then(() => {
            window.location.href = '../magang/cetak_surat.php';
        });
        </script>
    </body>
    </html>
    ";
    exit;
}

/* === Ambil nomor surat dari database === */
$noSurat = $data['nomor_surat'] ?? null;
if (!$noSurat) {
    die("<h3>Nomor surat belum dibuat. Harap admin menambahkan nomor surat terlebih dahulu.</h3>");
}

$noSuratFormatted = str_pad($noSurat, 3, '0', STR_PAD_LEFT);
$tahunCetak = date('Y');

/* Ambil field peserta */
$nama   = getFirstValue($data, ['nama','nama_lengkap','full_name'], 'Nama Tidak Diketahui');
$unit   = !empty($data['nama_unit']) ? $data['nama_unit'] : 'Unit Tidak Diisi';
$durasi = getFirstValue($data, ['durasi'], '-');
$tglMulaiStr   = date("d F Y", strtotime(getFirstValue($data, ['tgl_mulai'], date('Y-m-d'))));
$tglSelesaiStr = date("d F Y", strtotime(getFirstValue($data, ['tgl_selesai'], date('Y-m-d'))));

/* === Generate PDF Sertifikat === */
$pdf = new FPDF('L','mm','A4');
$pdf->AddPage();

// Background sertifikat
$bgPath = __DIR__ . '/template/sertifikat.temp.png';
if (file_exists($bgPath)) {
    $pdf->Image($bgPath, 0, 0, 297, 210);
}

// Nomor Sertifikat
$pdf->SetFont('Arial','B',20);
$pdf->SetTextColor(100,100,100);
$pdf->SetXY(0,45);
$pdf->Cell(297, 12, "C.TEL.$noSuratFormatted/PD.000/R2W-2G10000/$tahunCetak", 0, 1, 'C');

// Nama Peserta
$pdf->SetFont('Times','B',36);
$pdf->SetTextColor(184,134,11);
$pdf->SetXY(0, 100);
$pdf->Cell(297, 12, mb_strtoupper($nama, 'UTF-8'), 0, 1, 'C');

// Keterangan
$pdf->SetFont('Arial','',14);
$pdf->SetTextColor(0,0,0);
$pdf->SetXY(25, 122);
$keterangan = "Yang telah menyelesaikan program Praktik Kerja Lapangan (PKL) di PT Telkom Indonesia 
              (Persero) Tbk, pada unit Witel $unit selama $durasi\n"."terhitung mulai tanggal $tglMulaiStr s/d $tglSelesaiStr\n" .
              "dengan hasil \"Sangat Baik\"";
$pdf->MultiCell(247, 7,$keterangan, 0, 'C');

// QR Code TTD
$dataTTD = "Ditandatangani oleh:\nROSANA INTAN PERMATASARI\nManager Shared Service & General Support\nTanggal: ".date("d-m-Y");
$qrFile = __DIR__ . "/qrcode_ttd.png";
QRcode::png($dataTTD, $qrFile, QR_ECLEVEL_H,4);
$pdf->Image($qrFile, 35, 135, 33, 35);

// Nama & Jabatan
$pdf->SetFont('Arial','B',12);
$pdf->SetXY(30, 175);
$pdf->Cell(0, 6, 'ROSANA INTAN PERMATASARI', 0, 1, 'L');
$pdf->SetFont('Arial','',11);
$pdf->SetXY(30, 183);
$pdf->Cell(0, 6, 'MANAGER SHARED SERVICE & GENERAL SUPPORT', 0, 1, 'L');

if (file_exists($qrFile)) unlink($qrFile);

// Output PDF
$filenameSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama);
$pdf->Output('I', "sertifikat_{$filenameSafe}.pdf");
exit;
?>