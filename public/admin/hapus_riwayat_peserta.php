<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);

  $conn->begin_transaction(); // mulai transaksi

  try {
    // 1️⃣ Ambil user_id dari tabel riwayat_peserta_pkl
    $stmt = $conn->prepare("SELECT user_id FROM riwayat_peserta_pkl WHERE id = ?");
    if (!$stmt) throw new Exception("Gagal prepare SELECT user_id: " . $conn->error);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) throw new Exception("Data riwayat peserta tidak ditemukan.");
    $row = $res->fetch_assoc();
    $user_id = $row['user_id'];
    $stmt->close();

    // 2️⃣ Hapus data dari tabel rekap_absensi
    $delAbsensi = $conn->prepare("DELETE FROM absen WHERE user_id = ?");
    if (!$delAbsensi) throw new Exception("Gagal prepare DELETE absen: " . $conn->error);
    $delAbsensi->bind_param("i", $user_id);
    $delAbsensi->execute();
    $delAbsensi->close();

    // 4️⃣ Hapus data dari tabel riwayat_peserta_pkl
    $delRiwayat = $conn->prepare("DELETE FROM riwayat_peserta_pkl WHERE id = ?");
    if (!$delRiwayat) throw new Exception("Gagal prepare DELETE riwayat_peserta_pkl: " . $conn->error);
    $delRiwayat->bind_param("i", $id);
    $delRiwayat->execute();
    $delRiwayat->close();

    // 5️⃣ Commit transaksi kalau semua berhasil
    $conn->commit();
    header("Location: riwayat_peserta.php?hapus_success=1");
    exit;

  } catch (Exception $e) {
    $conn->rollback();
    echo "<pre style='color:red;'>Gagal hapus data: " . htmlspecialchars($e->getMessage()) . "</pre>";
  }

} else {
  header("Location: riwayat_peserta.php");
  exit;
}
?>
