<?php
session_start();
require_once 'config.php'; // Sesuaikan dengan file koneksi database Anda

header('Content-Type: application/json');

try {
    // Pastikan user sudah login
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User tidak terautentikasi');
    }
    
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'checkin') {
        // Proses Check-In
        
        // Validasi input
        $kondisi_kesehatan = $_POST['kondisi_kesehatan'] ?? '';
        $lokasi_kerja = $_POST['lokasi_kerja'] ?? '';
        $aktivitas_masuk = $_POST['aktivitas_masuk'] ?? '';
        $kendala_masuk = $_POST['kendala_masuk'] ?? '';
        $photo = $_POST['photo'] ?? '';
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        
        if (empty($kondisi_kesehatan) || empty($lokasi_kerja) || empty($aktivitas_masuk) || empty($photo)) {
            throw new Exception('Semua field wajib harus diisi');
        }
        
        // Cek apakah sudah check-in hari ini
        $stmt = $pdo->prepare("SELECT id FROM absen WHERE user_id = ? AND tanggal = ?");
        $stmt->execute([$user_id, $today]);
        
        if ($stmt->fetch()) {
            throw new Exception('Anda sudah melakukan check-in hari ini');
        }
        
        // Simpan foto
        $foto_filename = null;
        if ($photo) {
            $foto_filename = savePhoto($photo, 'checkin', $user_id);
        }
        
        // Insert data check-in
        $stmt = $pdo->prepare("
            INSERT INTO absen (
                user_id, tanggal, jam_masuk, kondisi_kesehatan, 
                lokasi_kerja, foto_absen, aktivitas_masuk, kendala_masuk,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $jam_masuk = date('H:i:s');
        
        $stmt->execute([
            $user_id,
            $today,
            $jam_masuk,
            $kondisi_kesehatan,
            $lokasi_kerja,
            $foto_filename,
            $aktivitas_masuk,
            $kendala_masuk
        ]);
        
        // Log lokasi (opsional - bisa disimpan di tabel terpisah)
        if ($latitude && $longitude) {
            logLocation($pdo, $user_id, 'checkin', $latitude, $longitude);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Check-in berhasil',
            'data' => [
                'jam_masuk' => $jam_masuk,
                'kondisi_kesehatan' => $kondisi_kesehatan,
                'lokasi_kerja' => $lokasi_kerja
            ]
        ]);
        
    } elseif ($action === 'checkout') {
        // Proses Check-Out
        
        // Validasi input
        $aktivitas_keluar = $_POST['aktivitas_keluar'] ?? '';
        $kendala_keluar = $_POST['kendala_keluar'] ?? '';
        $photo = $_POST['photo'] ?? '';
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        
        if (empty($aktivitas_keluar) || empty($photo)) {
            throw new Exception('Aktivitas keluar dan foto wajib diisi');
        }
        
        // Cek apakah sudah check-in hari ini
        $stmt = $pdo->prepare("
            SELECT id, jam_masuk, jam_keluar 
            FROM absen 
            WHERE user_id = ? AND tanggal = ?
        ");
        $stmt->execute([$user_id, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attendance) {
            throw new Exception('Anda belum melakukan check-in hari ini');
        }
        
        if ($attendance['jam_keluar']) {
            throw new Exception('Anda sudah melakukan check-out hari ini');
        }
        
        // Simpan foto checkout (bisa digabung dengan foto checkin atau terpisah)
        $foto_checkout = null;
        if ($photo) {
            $foto_checkout = savePhoto($photo, 'checkout', $user_id);
            
            // Update foto_absen dengan format: "checkin.jpg|checkout.jpg"
            $current_foto = $attendance['foto_absen'] ?? '';
            $new_foto = $current_foto ? $current_foto . '|' . $foto_checkout : $foto_checkout;
        }
        
        // Update data check-out
        $stmt = $pdo->prepare("
            UPDATE absen SET 
                jam_keluar = ?, 
                aktivitas_keluar = ?, 
                kendala_keluar = ?,
                foto_absen = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $jam_keluar = date('H:i:s');
        
        $stmt->execute([
            $jam_keluar,
            $aktivitas_keluar,
            $kendala_keluar,
            $new_foto ?? $attendance['foto_absen'],
            $attendance['id']
        ]);
        
        // Log lokasi checkout
        if ($latitude && $longitude) {
            logLocation($pdo, $user_id, 'checkout', $latitude, $longitude);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Check-out berhasil',
            'data' => [
                'jam_keluar' => $jam_keluar,
                'durasi_kerja' => calculateWorkDuration($attendance['jam_masuk'], $jam_keluar)
            ]
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

// Function untuk menyimpan foto
function savePhoto($photoData, $type, $user_id) {
    try {
        // Decode base64
        $photoData = str_replace('data:image/jpeg;base64,', '', $photoData);
        $photoData = str_replace(' ', '+', $photoData);
        $decodedPhoto = base64_decode($photoData);
        
        // Buat nama file
        $filename = $type . '_' . $user_id . '_' . date('Y-m-d_H-i-s') . '.jpg';
        $filepath = 'uploads/photos/' . $filename;
        
        // Pastikan folder ada
        if (!file_exists('uploads/photos/')) {
            mkdir('uploads/photos/', 0777, true);
        }
        
        // Simpan file
        if (file_put_contents($filepath, $decodedPhoto)) {
            return $filename;
        } else {
            throw new Exception('Gagal menyimpan foto');
        }
        
    } catch (Exception $e) {
        throw new Exception('Error menyimpan foto: ' . $e->getMessage());
    }
}

// Function untuk log lokasi (opsional)
function logLocation($pdo, $user_id, $type, $latitude, $longitude) {
    try {
        // Bisa buat tabel location_logs terpisah atau simpan di field lain
        // Untuk sekarang kita skip atau bisa ditambahkan nanti
        
        // Contoh jika ingin buat tabel terpisah:
        /*
        $stmt = $pdo->prepare("
            INSERT INTO location_logs (user_id, type, latitude, longitude, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $type, $latitude, $longitude]);
        */
        
    } catch (Exception $e) {
        // Log error tapi jangan stop proses utama
        error_log('Location log error: ' . $e->getMessage());
    }
}

// Function untuk hitung durasi kerja
function calculateWorkDuration($jam_masuk, $jam_keluar) {
    $masuk = new DateTime($jam_masuk);
    $keluar = new DateTime($jam_keluar);
    $diff = $keluar->diff($masuk);
    
    return $diff->format('%H jam %I menit');
}
?>
