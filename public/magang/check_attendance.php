<?php
session_start();
require_once 'config.php'; // Sesuaikan dengan file koneksi database Anda

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    // Pastikan user sudah login
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User tidak terautentikasi');
    }
    
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    if ($action === 'check_today') {
        // Cek absensi hari ini
        $stmt = $pdo->prepare("
            SELECT id, user_id, tanggal, jam_masuk, jam_keluar, 
                   kondisi_kesehatan, lokasi_kerja, foto_absen,
                   aktivitas_masuk, aktivitas_keluar, kendala_masuk, kendala_keluar,
                   created_at, updated_at
            FROM absen 
            WHERE user_id = ? AND tanggal = ?
        ");
        
        $stmt->execute([$user_id, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $attendance ?: null
        ]);
        
    } else {
        throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
