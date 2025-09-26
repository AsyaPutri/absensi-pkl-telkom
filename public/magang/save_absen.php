<?php
session_start();
require '../../config/database.php'; 

// ✅ Set timezone ke WIB
date_default_timezone_set("Asia/Jakarta");

// Response JSON
header('Content-Type: application/json');

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Anda harus login terlebih dahulu"]);
    exit;
}

$userId  = $_SESSION['user_id'];
$input   = json_decode(file_get_contents("php://input"), true);
$tanggal = date("Y-m-d");
$waktu   = date("H:i:s");

$response = ["success" => false, "message" => ""];

// ===================================================
// PROSES CHECKIN
// ===================================================
if ($input['action'] === "checkin") {
    $aktivitas  = $input['aktivitas'] ?? "";
    $kendala    = $input['kendala'] ?? "";
    $lokasi     = $input['lokasi'] ?? "";
    $kondisi    = $input['kondisi'] ?? "";
    $fotoBase64 = $input['foto'] ?? "";

    // 🚨 Batasi jam check-in sebelum jam 11:00:00
    $batasJam = "11:00:00";
    if ($waktu > $batasJam) {
        echo json_encode([
            "success" => false,
            "message" => "Check-in hanya bisa dilakukan sebelum jam 11 siang."
        ]);
        exit;
    }

    // 🚨 Foto wajib untuk checkin
    if (empty($fotoBase64)) {
        echo json_encode(["success" => false, "message" => "Foto wajib diambil untuk check-in"]);
        exit;
    }

    // ===================================================
    // 🚨 Auto Update Kemarin jika belum checkout
    // ===================================================
    $kemarin = date("Y-m-d", strtotime("-1 day"));
    $cekKemarin = $conn->prepare("SELECT id FROM absen WHERE user_id = ? AND tanggal = ? AND jam_keluar IS NULL");
    $cekKemarin->bind_param("is", $userId, $kemarin);
    $cekKemarin->execute();
    $hasilKemarin = $cekKemarin->get_result();
    if ($hasilKemarin->num_rows > 0) {
        $jamKeluarAuto = "23:59:00";
        $update = $conn->prepare("UPDATE absen SET jam_keluar = ? WHERE user_id = ? AND tanggal = ? AND jam_keluar IS NULL");
        $update->bind_param("sis", $jamKeluarAuto, $userId, $kemarin);
        $update->execute();
        $update->close();
    }
    $cekKemarin->close();

    // ===================================================
    // Simpan foto checkin
    // ===================================================
    $fotoPath = null;
    if (!empty($fotoBase64)) {
        $fotoData = explode(",", $fotoBase64);
        if (count($fotoData) > 1) {
            $decodedImage = base64_decode($fotoData[1]);
            $fotoName = "absen_" . $userId . "_" . time() . ".jpg";
            $savePath = __DIR__ . "/../../uploads/absensi/" . $fotoName;

            if (!is_dir(__DIR__ . "/../../uploads/absensi")) {
                mkdir(__DIR__ . "/../../uploads/absensi", 0755, true);
            }

            file_put_contents($savePath, $decodedImage);
            $fotoPath = $fotoName;
        }
    }

    // ===================================================
    // Simpan data absen (Check-in)
    // ===================================================
    $stmt = $conn->prepare("
        INSERT INTO absen 
        (user_id, tanggal, jam_masuk, aktivitas_masuk, kendala_masuk, lokasi_kerja, kondisi_kesehatan, foto_absen) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssssss", $userId, $tanggal, $waktu, $aktivitas, $kendala, $lokasi, $kondisi, $fotoPath);

    if ($stmt->execute()) {
        $response = ["success" => true, "message" => "Check-in berhasil"];
    } else {
        $response = ["success" => false, "message" => "Gagal check-in: " . $stmt->error];
    }
    $stmt->close();
}

// ===================================================
// PROSES CHECKOUT
// ===================================================
elseif ($input['action'] === "checkout") {
    $aktivitasKeluar = $input['aktivitas_keluar'] ?? "";
    $kendalaKeluar   = $input['kendala_keluar'] ?? "";

    $stmt = $conn->prepare("
        UPDATE absen 
        SET jam_keluar = ?, aktivitas_keluar = ?, kendala_keluar = ?
        WHERE user_id = ? AND tanggal = ? AND jam_keluar IS NULL
    ");
    $stmt->bind_param("sssis", $waktu, $aktivitasKeluar, $kendalaKeluar, $userId, $tanggal);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $response = ["success" => true, "message" => "Check-out berhasil"];
    } else {
        $response = ["success" => false, "message" => "Gagal check-out (mungkin belum check-in)"];
    }
    $stmt->close();
}

// ===================================================
// PROSES CHECKOUT PENDING
// ===================================================
elseif ($input['action'] === "checkout_pending") {
    $targetDate = $input['target_date'] ?? "";
    $aktivitasKeluar = $input['aktivitas_keluar'] ?? "";
    $kendalaKeluar   = $input['kendala_keluar'] ?? "";

    if (empty($targetDate)) {
        echo json_encode(["success" => false, "message" => "Tanggal pending tidak ditemukan"]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE absen 
        SET jam_keluar = ?, aktivitas_keluar = ?, kendala_keluar = ?
        WHERE user_id = ? AND tanggal = ? AND jam_keluar IS NULL
    ");
    $stmt->bind_param("sssis", $waktu, $aktivitasKeluar, $kendalaKeluar, $userId, $targetDate);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $response = ["success" => true, "message" => "Pending checkout berhasil"];
    } else {
        $response = ["success" => false, "message" => "Gagal pending checkout"];
    }
    $stmt->close();
}

// ===================================================
// AKSI TIDAK DIKENALI
// ===================================================
else {
    $response = ["success" => false, "message" => "Aksi tidak dikenali"];
}

echo json_encode($response);
