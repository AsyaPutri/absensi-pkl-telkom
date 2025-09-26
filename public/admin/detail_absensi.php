<?php
// ============================
// detail_absensi.php
// ============================

include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

header('Content-Type: application/json; charset=utf-8');

// ============================
// Ambil parameter dari request
// ============================
$user_id   = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$tgl_awal  = isset($_GET['tgl_awal']) && $_GET['tgl_awal'] !== '' ? date('Y-m-d', strtotime($_GET['tgl_awal'])) : null;
$tgl_akhir = isset($_GET['tgl_akhir']) && $_GET['tgl_akhir'] !== '' ? date('Y-m-d', strtotime($_GET['tgl_akhir'])) : null;

// Validasi user_id
if ($user_id <= 0) {
    echo json_encode([
        'error' => true,
        'message' => 'Parameter user_id tidak valid'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// Query info peserta PKL
// ============================
$sqlPeserta = "
    SELECT 
        p.id, 
        p.nama, 
        p.nis_npm, 
        p.instansi_pendidikan, 
        u.nama_unit AS unit,
        p.tgl_mulai,
        p.tgl_selesai
    FROM peserta_pkl p
    LEFT JOIN unit_pkl u ON p.unit_id = u.id
    WHERE p.id = $user_id
    LIMIT 1
";
$resPeserta = $conn->query($sqlPeserta);
$peserta = $resPeserta ? $resPeserta->fetch_assoc() : null;

if (!$peserta) {
    echo json_encode([
        'error' => true,
        'message' => 'Peserta tidak ditemukan'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// Filter tanggal (opsional)
// ============================
$whereDate = "";
if ($tgl_awal && $tgl_akhir) {
    $tgl_awal_esc  = $conn->real_escape_string($tgl_awal);
    $tgl_akhir_esc = $conn->real_escape_string($tgl_akhir);
    $whereDate = "AND a.tanggal BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
}

// ============================
// Query absensi peserta
// ============================
$sqlAbsensi = "
    SELECT 
        a.tanggal,
        DATE_FORMAT(a.tanggal, '%W') AS hari, -- contoh: Monday, Tuesday
        a.jam_masuk,
        a.jam_keluar,
        a.kondisi,
        a.keterangan,
        a.lokasi_kerja,
        a.foto,
        TIMEDIFF(a.jam_keluar, a.jam_masuk) AS durasi
    FROM absen a
    WHERE a.user_id = $user_id
    $whereDate
    ORDER BY a.tanggal ASC
";

$resAbsensi = $conn->query($sqlAbsensi);

$absensi = [];
if ($resAbsensi) {
    while ($row = $resAbsensi->fetch_assoc()) {
        $absensi[] = [
            'tanggal'     => $row['tanggal'],
            'hari'        => $row['hari'], // hasil bahasa Inggris, bisa diparsing ke Indo di frontend
            'jam_masuk'   => $row['jam_masuk'] ?: '-',
            'jam_keluar'  => $row['jam_keluar'] ?: '-',
            'kondisi'     => $row['kondisi'],
            'keterangan'  => $row['keterangan'] ?? '',
            'lokasi_kerja'=> $row['lokasi_kerja'] ?? '-',
            'durasi'      => $row['durasi'] ?? '-',
            'foto'        => $row['foto'] ?? null
        ];
    }
}

// ============================
// Output JSON
// ============================
echo json_encode([
    'info'    => $peserta,
    'absensi' => $absensi
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
