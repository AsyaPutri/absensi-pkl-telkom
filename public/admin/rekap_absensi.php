<?php
// ============================
// rekap_absensi.php (versi fix pakai kondisi_kesehatan)
// ============================
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
// Filter tambahan (unit & tanggal)
// ============================
$whereUnit = ($unit !== '' && $unit !== 'all') ? "AND p.unit_id = '{$unit_esc}'" : "";
$whereDate = "";

// filter periode PKL berdasarkan tgl_awal & tgl_akhir
if ($tgl_awal_esc && $tgl_akhir_esc) {
    $whereDate = "AND (
        (p.tgl_mulai BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc') 
        OR (p.tgl_selesai BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc')
    )";
}

// ============================
// Query utama: peserta + rekap absensi
// ============================
$sql = "
    SELECT 
        p.id AS user_id,
        p.nama,
        p.nis_npm,
        p.instansi_pendidikan,
        u.nama_unit AS unit,
        p.tgl_mulai,
        p.tgl_selesai,
        COALESCE(SUM(CASE WHEN a.kondisi_kesehatan IN ('sehat','kurang_fit_office') THEN 1 ELSE 0 END),0) AS hadir_office,
        COALESCE(SUM(CASE WHEN a.kondisi_kesehatan IN ('sakit','kurang_fit_wfh') THEN 1 ELSE 0 END),0) AS hadir_wfh,
        COALESCE(SUM(CASE WHEN a.kondisi_kesehatan = 'alpha' THEN 1 ELSE 0 END),0) AS alpha,
        COALESCE(COUNT(a.id),0) AS total_absen,
        GROUP_CONCAT(DISTINCT a.kondisi_kesehatan) AS kondisi_ditemukan
    FROM peserta_pkl p
    LEFT JOIN unit_pkl u ON p.unit_id = u.id
    LEFT JOIN absen a ON a.user_id = p.id
    WHERE 1=1
    $whereUnit
    $whereDate
    GROUP BY 
        p.id, p.nama, p.nis_npm, p.instansi_pendidikan, 
        u.nama_unit, p.tgl_mulai, p.tgl_selesai
    ORDER BY p.nama ASC
";

$result = $conn->query($sql);

// ============================
// Debug query kalau error
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
    // --------------------------
    // Hitung hari kerja (Senin–Jumat)
    // --------------------------
    $p_start = $row['tgl_mulai'] ? strtotime($row['tgl_mulai']) : null;
    $p_end   = $row['tgl_selesai'] ? strtotime($row['tgl_selesai']) : null;
    $hariKerja = 0;

    if ($p_start && $p_end) {
        $period_start = $p_start;
        $period_end   = $p_end;

        if ($tgl_awal_esc) {
            $filter_start = strtotime($tgl_awal_esc);
            if ($filter_start > $period_start) $period_start = $filter_start;
        }

        if ($tgl_akhir_esc) {
            $filter_end = strtotime($tgl_akhir_esc);
            if ($filter_end < $period_end) $period_end = $filter_end;
        }

        if ($period_end > $today) $period_end = $today;

        if ($period_start <= $period_end) {
            for ($d = $period_start; $d <= $period_end; $d = strtotime("+1 day", $d)) {
                $w = date("N", $d);
                if ($w >= 1 && $w <= 5) $hariKerja++;
            }
        }
    }

    // --------------------------
    // Ambil rekap hadir
    // --------------------------
    $hadir_office = (int) $row['hadir_office'];
    $hadir_wfh    = (int) $row['hadir_wfh'];
    $alpha        = (int) $row['alpha'];
    $total_hadir  = $hadir_office + $hadir_wfh;

    // --------------------------
    // Hitung persentase kehadiran
    // --------------------------
    $persen = ($hariKerja > 0) ? round(($total_hadir / $hariKerja) * 100, 2) : 0;

    // --------------------------
    // Simpan hasil untuk JSON
    // --------------------------
    $data[] = [
        'user_id'            => (int) $row['user_id'],
        'nama'               => $row['nama'],
        'nis_npm'            => $row['nis_npm'],
        'instansi_pendidikan'=> $row['instansi_pendidikan'],
        'unit'               => $row['unit'],
        'tgl_mulai'          => $row['tgl_mulai'],
        'tgl_selesai'        => $row['tgl_selesai'],
        'hari_kerja'         => $hariKerja,
        'hadir_office'       => $hadir_office,
        'hadir_wfh'          => $hadir_wfh,
        'alpha'              => $alpha,
        'total_hadir'        => $total_hadir,
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
