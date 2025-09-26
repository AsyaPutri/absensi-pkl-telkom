<?php
require '../../config/database.php';
date_default_timezone_set("Asia/Jakarta");

$tanggal = date("Y-m-d");

// 🚨 Masukkan semua user yang belum absen hari ini sebagai ALPHA
$sql = "
INSERT INTO absen (user_id, tanggal, status)
SELECT u.id, ?, 'alpha'
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM absen a 
    WHERE a.user_id = u.id AND a.tanggal = ?
)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $tanggal, $tanggal);

if ($stmt->execute()) {
    echo "✅ Mark alpha selesai untuk tanggal $tanggal\n";
} else {
    echo "❌ Gagal: " . $stmt->error;
}
$stmt->close();
  