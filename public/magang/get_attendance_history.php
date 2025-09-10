<?php
session_start();
require __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Cek user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua data absensi user
$query = "SELECT * FROM absen WHERE user_id = ? ORDER BY tanggal DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $durasi = null;
    if ($row['jam_masuk'] && $row['jam_keluar']) {
        $durasi = gmdate("H:i", strtotime($row['jam_keluar']) - strtotime($row['jam_masuk']));
    }

    $data[] = [
        'id'              => $row['id'],
        'date'            => $row['tanggal'],
        'timeIn'          => $row['jam_masuk'],
        'timeOut'         => $row['jam_keluar'],
        'condition'       => $row['kondisi_kesehatan'],
        'location'        => $row['lokasi_kerja'],
        'photo'           => $row['foto_absen'],
        'aktivitas_masuk' => $row['aktivitas_masuk'],
        'aktivitas_keluar'=> $row['aktivitas_keluar'],
        'kendala_masuk'   => $row['kendala_masuk'],
        'kendala_keluar'  => $row['kendala_keluar'],
        'durasi_kerja'    => $durasi
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
