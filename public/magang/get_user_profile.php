<?php
session_start();
header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Belum login'
    ]);
    exit;
}

require '../../config/database.php'; // sesuaikan path config db kamu

$user_id = $_SESSION['user_id'];

// Query join tabel users + peserta_pkl
$sql = "SELECT 
            u.id AS user_id, 
            u.nama AS nama_user, 
            p.nama AS nama, 
            p.nis_npm AS nim, 
            p.instansi_pendidikan AS asal_instansi, 
            p.unit AS unit_kerja
        FROM users u
        JOIN peserta_pkl p ON u.id = p.user_id
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Data peserta tidak ditemukan'
    ]);
}

$stmt->close();
$conn->close();
