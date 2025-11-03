<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// ============================
// Ambil parameter filter
// ============================
$tgl_awal  = isset($_GET['tgl_awal']) && $_GET['tgl_awal'] !== '' ? date('Y-m-d', strtotime($_GET['tgl_awal'])) : null;
$tgl_akhir = isset($_GET['tgl_akhir']) && $_GET['tgl_akhir'] !== '' ? date('Y-m-d', strtotime($_GET['tgl_akhir'])) : null;
$unit      = isset($_GET['unit']) ? $_GET['unit'] : 'all';
$search    = $_GET['search'] ?? '';

// ============================
// Escape input
// ============================
$tgl_awal_esc  = $tgl_awal ? $conn->real_escape_string($tgl_awal) : null;
$tgl_akhir_esc = $tgl_akhir ? $conn->real_escape_string($tgl_akhir) : null;
$unit_esc      = $conn->real_escape_string($unit);

// ============================
// Filter unit
// ============================
$where = "WHERE 1=1";
if ($unit !== '' && $unit !== 'all') {
    $where .= " AND p.unit_id = '{$unit_esc}'";
}

// 🔍 Filter pencarian
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (
        p.nama LIKE '%$s%'
        OR p.nis_npm LIKE '%$s%'
        OR p.instansi_pendidikan LIKE '%$s%'
        OR p.jurusan LIKE '%$s%'
        OR d.skill LIKE '%$s%'
    )";
}

// ============================
// Query utama: ambil peserta + jumlah absen
// ============================
$sql = "
SELECT 
    p.id AS peserta_id,
    p.user_id,
    p.nama,
    p.nis_npm,
    u.nama_unit AS unit,
    p.tgl_mulai,
    p.tgl_selesai,
    COUNT(a.id) AS total_hadir
FROM peserta_pkl p
LEFT JOIN unit_pkl u ON p.unit_id = u.id
LEFT JOIN daftar_pkl d ON p.email = d.email
LEFT JOIN absen a 
    ON a.user_id = p.user_id
    " . ($tgl_awal_esc && $tgl_akhir_esc ? "AND a.tanggal BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'" : "") . "
$where
GROUP BY 
    p.id, p.user_id, p.nama, p.nis_npm, u.nama_unit, p.tgl_mulai, p.tgl_selesai
ORDER BY p.nama ASC
";

$result = $conn->query($sql);
if (!$result) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $conn->error, 'sql' => $sql], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================
// Proses data hasil query
// ============================
$data = [];
$today = strtotime(date('Y-m-d'));

while ($row = $result->fetch_assoc()) {
    $start_date = $row['tgl_mulai'] ? strtotime($row['tgl_mulai']) : null;
    $end_date   = $row['tgl_selesai'] ? strtotime($row['tgl_selesai']) : null;
    $hariKerja  = 0;

    if ($start_date && $end_date) {
        // Hitung hari kerja (Senin - Jumat)
        $periode_start = $tgl_awal_esc ? max($start_date, strtotime($tgl_awal_esc)) : $start_date;
        $periode_end   = $tgl_akhir_esc ? min($end_date, strtotime($tgl_akhir_esc)) : $end_date;
        if ($periode_end > $today) $periode_end = $today;

        for ($d = $periode_start; $d <= $periode_end; $d = strtotime('+1 day', $d)) {
            $w = date('N', $d);
            if ($w >= 1 && $w <= 5) $hariKerja++;
        }
    }

    // Hitung tidak hadir & persentase
    $jumlahHadir = (int)$row['total_hadir'];
    $jumlahTidakHadir = max(0, $hariKerja - $jumlahHadir);
    $persenKehadiran = $hariKerja > 0 ? round(($jumlahHadir / $hariKerja) * 100, 2) : 0;

    $data[] = [
        'peserta_id' => (int)$row['peserta_id'],
        'user_id' => (int)$row['user_id'],
        'nama' => $row['nama'],
        'nis_npm' => $row['nis_npm'],
        'unit' => $row['unit'] ?: '-',
        'tgl_mulai' => $row['tgl_mulai'],
        'tgl_selesai' => $row['tgl_selesai'],
        'hari_kerja' => $hariKerja,
        'jumlah_hadir' => $jumlahHadir,
        'jumlah_tidak_hadir' => $jumlahTidakHadir,
        'persen' => $persenKehadiran
    ];
}

// ============================
// Output JSON
// ============================
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;