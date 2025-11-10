<?php
include "../../config/database.php";
include "../../includes/auth.php";
checkRole('mentor');

header('Content-Type: application/json');

if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode(["error" => "Parameter user_id tidak ditemukan."]);
    exit;
}

$user_id = $conn->real_escape_string($_GET['user_id']);

// ==============================
// Ambil data peserta PKL
// ==============================
$qPeserta = $conn->query("
    SELECT 
        p.nama,
        p.nis_npm,
        p.instansi_pendidikan,
        u.nama_unit AS unit,
        p.tgl_mulai,
        p.tgl_selesai
    FROM peserta_pkl p
    LEFT JOIN unit_pkl u ON p.unit_id = u.id
    WHERE p.user_id = '$user_id'
    LIMIT 1
");

if (!$qPeserta) {
    echo json_encode(["error" => "Query peserta gagal: " . $conn->error]);
    exit;
}

if ($qPeserta->num_rows === 0) {
    echo json_encode(["error" => "Data peserta tidak ditemukan."]);
    exit;
}

$peserta = $qPeserta->fetch_assoc();

// ==============================
// Ambil data absensi peserta
// ==============================
$qAbsen = $conn->query("
    SELECT 
        tanggal,
        jam_masuk,
        aktivitas_masuk,
        kendala_masuk,
        kondisi_kesehatan,
        lokasi_kerja,
        aktivitas_keluar,
        kendala_keluar,
        jam_keluar,
        foto_absen
    FROM absen
    WHERE user_id = '$user_id'
    ORDER BY tanggal ASC
");

if (!$qAbsen) {
    echo json_encode(["error" => "Query absensi gagal: " . $conn->error]);
    exit;
}

$absensi = [];
while ($a = $qAbsen->fetch_assoc()) {
    $absensi[] = [
        "tanggal" => $a["tanggal"],
        "jam_masuk" => $a["jam_masuk"],
        "aktivitas_masuk" => $a["aktivitas_masuk"],
        "kendala_masuk" => $a["kendala_masuk"],
        "kondisi_kesehatan" => $a["kondisi_kesehatan"],
        "lokasi_kerja" => $a["lokasi_kerja"],
        "aktivitas_keluar" => $a["aktivitas_keluar"],
        "kendala_keluar" => $a["kendala_keluar"],
        "jam_keluar" => $a["jam_keluar"],
        "foto_absen" => $a["foto_absen"],
        // ✅ status dihapus dari query, diganti default string
        "status" => "hadir"
    ];
}

// ==============================
// Hitung statistik absensi
// ==============================
$tgl_mulai = new DateTime($peserta['tgl_mulai']);
$tgl_selesai_db = !empty($peserta['tgl_selesai']) ? new DateTime($peserta['tgl_selesai']) : new DateTime();
$tgl_selesai = new DateTime(); // real time

// pastikan periode tidak melebihi tgl_selesai_db
if ($tgl_selesai > $tgl_selesai_db) {
    $tgl_selesai = $tgl_selesai_db;
}

$periode = clone $tgl_mulai;
$hari_kerja = 0;
while ($periode <= $tgl_selesai) {
    if ($periode->format('N') < 6) $hari_kerja++;
    $periode->modify('+1 day');
}

$total_hadir = count($absensi);
$persen_kehadiran = $hari_kerja > 0 ? round(($total_hadir / $hari_kerja) * 100, 2) : 0;



// ==============================
// Kirim JSON ke frontend
// ==============================
echo json_encode([
    "nama" => $peserta["nama"],
    "nis_npm" => $peserta["nis_npm"],
    "instansi_pendidikan" => $peserta["instansi_pendidikan"],
    "unit" => $peserta["unit"],
    "tgl_mulai" => $peserta["tgl_mulai"],
    "tgl_selesai" => $peserta["tgl_selesai"],
    "hari_kerja" => $hari_kerja,
    "hadir" => $total_hadir,
    "persentase" => $persen_kehadiran,
    "absensi" => $absensi
]);
?>
