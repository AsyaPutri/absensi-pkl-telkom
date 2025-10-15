<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

header('Content-Type: application/json; charset=utf-8');

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

$user_id   = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$tgl_awal  = isset($_GET['tgl_awal']) && $_GET['tgl_awal'] !== '' ? date('Y-m-d', strtotime($_GET['tgl_awal'])) : null;
$tgl_akhir = isset($_GET['tgl_akhir']) && $_GET['tgl_akhir'] !== '' ? date('Y-m-d', strtotime($_GET['tgl_akhir'])) : null;

if ($user_id <= 0) {
    echo json_encode(['error' => true, 'message' => 'Parameter user_id tidak valid'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- deteksi kolom peserta_pkl (pk candidate) ---------- */
$pkCandidates = ['id','user_id','peserta_id'];
$pkPeserta = null;
$colsPeserta = [];
$colRes = $conn->query("SHOW COLUMNS FROM `peserta_pkl`");
if ($colRes) {
    while ($c = $colRes->fetch_assoc()) $colsPeserta[] = $c['Field'];
    foreach ($pkCandidates as $cand) {
        if (in_array($cand, $colsPeserta)) { $pkPeserta = $cand; break; }
    }
}
if (!$pkPeserta) {
    echo json_encode(['error'=>true, 'message'=>'Tidak menemukan kolom primary key yang cocok pada tabel peserta_pkl','debug'=>['cols_peserta_pkl'=>$colsPeserta]], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- ambil info peserta — coba 2 cara: cari by pk atau by user_id (kalau param adalah users.id) ---------- */
$peserta = null;
$sqlP = "
    SELECT p.*, u.nama_unit
    FROM peserta_pkl p
    LEFT JOIN unit_pkl u ON p.unit_id = u.id
    WHERE p.$pkPeserta = {$user_id}
    LIMIT 1
";
$resP = $conn->query($sqlP);

if ($resP && $resP->num_rows > 0) {
    $peserta = $resP->fetch_assoc();
} else {
    // fallback cari berdasarkan user_id aja
    $sqlP2 = "
        SELECT p.*, u.nama_unit
        FROM peserta_pkl p
        LEFT JOIN unit_pkl u ON p.unit_id = u.id
        WHERE p.user_id = {$user_id}
        LIMIT 1
    ";
    $resP2 = $conn->query($sqlP2);
    if ($resP2 && $resP2->num_rows > 0) {
        $peserta = $resP2->fetch_assoc();
    }
}

if (!$peserta) {
    // tidak abort — kita tetap bisa mencoba mencari absen berdasarkan user_id langsung
    // tapi akan beri info bahwa peserta tidak ditemukan
    $infoPeserta = null;
} else {
    $infoPeserta = [
        'id' => $peserta[$pkPeserta] ?? null,
        'nama' => $peserta['nama'] ?? null,
        'nis_npm' => $peserta['nis_npm'] ?? ($peserta['nim'] ?? null),
        'instansi_pendidikan' => $peserta['instansi_pendidikan'] ?? ($peserta['instansi'] ?? null),
        'unit' => $peserta['nama_unit'] ?? '-',
        'tgl_mulai' => $peserta['tgl_mulai'] ?? null,
        'tgl_selesai' => $peserta['tgl_selesai'] ?? null,
        // tetap sertakan user_id jika ada
        'user_id' => $peserta['user_id'] ?? null
    ];
}

/* ---------- filter tanggal (jika ada) ---------- */
$whereDate = "";
if ($tgl_awal && $tgl_akhir) {
    $tgl_awal_esc  = $conn->real_escape_string($tgl_awal);
    $tgl_akhir_esc = $conn->real_escape_string($tgl_akhir);
    $whereDate = " AND a.tanggal BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
}

/* ---------- deteksi kolom di tabel absen ---------- */
$colsAbsen = [];
$colRes2 = $conn->query("SHOW COLUMNS FROM `absen`");
if ($colRes2) {
    while ($c = $colRes2->fetch_assoc()) $colsAbsen[] = $c['Field'];
}

/* ---------- bangun kondisi pencarian absen secara tegas ---------- */
$uid = intval($user_id);
$whereAbsen = "";

// Cek kolom mana yang dipakai di tabel absen
if (in_array('user_id', $colsAbsen)) {
    // Gunakan user_id langsung dari peserta (bukan pk peserta)
    $idAbsen = $peserta && !empty($peserta['user_id']) ? intval($peserta['user_id']) : $uid;
    $whereAbsen = "a.user_id = {$idAbsen}";
} elseif (in_array('peserta_id', $colsAbsen)) {
    // Jika tabel absen pakai peserta_id
    $idAbsen = $peserta && isset($peserta[$pkPeserta]) ? intval($peserta[$pkPeserta]) : $uid;
    $whereAbsen = "a.peserta_id = {$idAbsen}";
} else {
    // fallback terakhir
    $whereAbsen = "a.user_id = {$uid}";
}


/* ---------- query absen (gunakan COALESCE untuk menangani nama kolom alternatif) ---------- */
$sqlAbsen = "
    SELECT
        a.tanggal,
        DAYNAME(a.tanggal) AS hari,
        a.jam_masuk,
        a.jam_keluar,
        COALESCE(a.kondisi_kesehatan, a.kondisi_kesehatan) AS kondisi_kesehatan,
        COALESCE(a.lokasi_kerja, a.lokasi_kerja) AS lokasi_kerja,
        COALESCE(a.foto_absen, a.foto_absen) AS foto_absen,
        a.aktivitas_masuk,
        a.kendala_masuk,
        a.aktivitas_keluar,
        a.kendala_keluar,
        IFNULL(TIMEDIFF(a.jam_keluar, a.jam_masuk), '-') AS durasi
    FROM absen a
    WHERE {$whereAbsen}
    {$whereDate}
    ORDER BY a.tanggal ASC
";

$resAbsensi = $conn->query($sqlAbsen);
if (!$resAbsensi) {
    $out = ['error'=>true,'message'=>'Query absen gagal','debug'=>$conn->error];
    if ($debug) $out['debug'] = ['sqlAbsen'=>$sqlAbsen, 'conds'=>$conds, 'cols_absen'=>$colsAbsen, 'cols_peserta'=>$colsPeserta];
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

$absensi = [];
if ($resAbsensi->num_rows > 0) {
    while ($row = $resAbsensi->fetch_assoc()) {
        $absensi[] = [
            'tanggal' => $row['tanggal'],
            'hari'    => $row['hari'],
            'jam_masuk' => $row['jam_masuk'] ?: '-',
            'jam_keluar'=> $row['jam_keluar'] ?: '-',
            // sediakan beberapa key supaya frontend tidak bingung
            'kondisi_kesehatan' => $row['kondisi_kesehatan'] ?? '-',
            'lokasi_kerja' => $row['lokasi_kerja'] ?? '-',
            'lokasi' => $row['lokasi_kerja'] ?? '-',
            'foto_absen' => $row['foto_absen'] ?? null,
            'foto' => $row['foto_absen'] ?? null,
            'aktivitas_masuk' => $row['aktivitas_masuk'] ?? '',
            'kendala_masuk'   => $row['kendala_masuk'] ?? '',
            'aktivitas_keluar'=> $row['aktivitas_keluar'] ?? '',
            'kendala_keluar'  => $row['kendala_keluar'] ?? '',
            'durasi' => $row['durasi'] ?? '-'
        ];
    }
}

$output = [
    'info' => $infoPeserta,
    'absensi' => $absensi
];

if ($debug) {
    $output['debug'] = [
        'pkPeserta' => $pkPeserta,
        'conds' => $conds,
        'cols_absen' => $colsAbsen,
        'cols_peserta' => $colsPeserta,
        'sqlAbsen' => $sqlAbsen
    ];
}
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
