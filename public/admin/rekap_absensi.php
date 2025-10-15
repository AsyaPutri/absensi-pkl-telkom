<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// ============================
// Ambil filter dari request
// ============================
$tgl_awal  = isset($_GET['tgl_awal']) && $_GET['tgl_awal'] !== '' ? date('Y-m-d', strtotime($_GET['tgl_awal'])) : null;
$tgl_akhir = isset($_GET['tgl_akhir']) && $_GET['tgl_akhir'] !== '' ? date('Y-m-d', strtotime($_GET['tgl_akhir'])) : null;
$unit      = isset($_GET['unit']) ? $_GET['unit'] : 'all';

// Escape untuk keamanan
$tgl_awal_esc  = $tgl_awal ? $conn->real_escape_string($tgl_awal) : null;
$tgl_akhir_esc = $tgl_akhir ? $conn->real_escape_string($tgl_akhir) : null;
$unit_esc      = $conn->real_escape_string($unit);

// ============================
// Filter tambahan (unit & tanggal absen)
// ============================
$where = "WHERE 1=1";
if ($unit !== '' && $unit !== 'all') {
    $where .= " AND p.unit_id = '{$unit_esc}'";
}

// ============================
// Query utama: peserta + rekap absensi
// ============================
$sql = "
SELECT 
    p.id AS user_id,
    p.nama,
    p.nis_npm,
    u.nama_unit AS unit,
    p.tgl_mulai,
    p.tgl_selesai,
    -- Kehadiran office
    COALESCE(SUM(CASE WHEN LOWER(TRIM(a.kondisi_kesehatan)) IN ('sehat','hadir','masuk','kurang_fit_office') THEN 1 ELSE 0 END),0) AS hadir_office,
    -- Kehadiran wfh
    COALESCE(SUM(CASE WHEN LOWER(TRIM(a.kondisi_kesehatan)) IN ('sakit','wfh','work_from_home','kurang_fit_wfh') THEN 1 ELSE 0 END),0) AS hadir_wfh,
    -- Alpha
    COALESCE(SUM(CASE WHEN LOWER(TRIM(a.kondisi_kesehatan)) IN ('alpha','absen','tidak_hadir') THEN 1 ELSE 0 END),0) AS alpha,
    -- Jumlah hadir (semua yang bukan izin/alpha/cuti)
    COALESCE(SUM(CASE WHEN LOWER(TRIM(a.kondisi_kesehatan)) NOT IN ('alpha','izin','cuti','tidak_hadir') THEN 1 ELSE 0 END),0) AS jumlah_hadir,
    -- Total absen ter-record
    COALESCE(COUNT(a.id),0) AS total_absen,
    GROUP_CONCAT(DISTINCT LOWER(TRIM(a.kondisi_kesehatan))) AS kondisi_ditemukan
FROM peserta_pkl p
LEFT JOIN unit_pkl u ON p.unit_id = u.id
LEFT JOIN absen a ON a.user_id = p.id
    " . ($tgl_awal_esc && $tgl_akhir_esc ? "AND DATE(a.tanggal) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'" : "") . "
$where
GROUP BY p.id, p.nama, p.nis_npm, u.nama_unit, p.tgl_mulai, p.tgl_selesai
ORDER BY p.nama ASC
";

// ============================
// Eksekusi query
// ============================
$result = $conn->query($sql);

// ============================
// Debug jika error SQL
// ============================
if (!$result) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => $conn->error,
        'sql'   => $sql
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================
// Proses hasil query
// ============================
$data  = [];
$today = strtotime(date('Y-m-d'));

while ($row = $result->fetch_assoc()) {
    // Hitung hari kerja (Senin–Jumat)
    $p_start = $row['tgl_mulai'] ? strtotime($row['tgl_mulai']) : null;
    $p_end   = $row['tgl_selesai'] ? strtotime($row['tgl_selesai']) : null;
    $hariKerja = 0;

    if ($p_start && $p_end) {
        $period_start = $p_start;
        $period_end   = $p_end;

        // Sesuaikan dengan filter tanggal jika ada
        if ($tgl_awal_esc) {
            $filter_start = strtotime($tgl_awal_esc);
            if ($filter_start > $period_start) $period_start = $filter_start;
        }

        if ($tgl_akhir_esc) {
            $filter_end = strtotime($tgl_akhir_esc);
            if ($filter_end < $period_end) $period_end = $filter_end;
        }

        // Batasi sampai hari ini
        if ($period_end > $today) $period_end = $today;

        // Hitung hanya hari kerja (Senin–Jumat)
        if ($period_start <= $period_end) {
            for ($d = $period_start; $d <= $period_end; $d = strtotime("+1 day", $d)) {
                $w = date("N", $d);
                if ($w >= 1 && $w <= 5) $hariKerja++;
            }
        }
    }

    // Hitung statistik kehadiran
    $hadir_office = (int) $row['hadir_office'];
    $hadir_wfh    = (int) $row['hadir_wfh'];
    $alpha        = (int) $row['alpha'];
    $jumlahHadir  = (int) $row['jumlah_hadir'];
    $jumlahTidakHadir = max(0, $hariKerja - $jumlahHadir);
    $persen = ($hariKerja > 0) ? round(($jumlahHadir / $hariKerja) * 100, 2) : 0;

    $data[] = [
        'user_id'            => (int) $row['user_id'],
        'nama'               => $row['nama'],
        'nis_npm'            => $row['nis_npm'],
        'unit'               => $row['unit'],
        'tgl_mulai'          => $row['tgl_mulai'],
        'tgl_selesai'        => $row['tgl_selesai'],
        'hari_kerja'         => $hariKerja,
        'hadir_office'       => $hadir_office,
        'hadir_wfh'          => $hadir_wfh,
        'alpha'              => $alpha,
        'jumlah_hadir'       => $jumlahHadir,
        'jumlah_tidak_hadir' => $jumlahTidakHadir,
        'persen'             => $persen,
        'total_absen'        => (int) $row['total_absen'],
        'kondisi_ditemukan'  => $row['kondisi_ditemukan']
    ];
}

// ============================
// Output JSON
// ============================
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
