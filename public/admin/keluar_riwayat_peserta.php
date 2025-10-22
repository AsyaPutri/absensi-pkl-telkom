<?php
session_start();
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    try {
        // Mulai transaksi biar aman
        $conn->begin_transaction();

        // 1. Ambil user_id dari tabel riwayat_peserta_pkl
        $stmt = $conn->prepare("SELECT user_id FROM riwayat_peserta_pkl WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare gagal (SELECT): " . $conn->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Data peserta tidak ditemukan di riwayat.");
        }

        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $stmt->close();

        // 2. Update status jadi 'keluar' di riwayat_peserta_pkl
        $update = $conn->prepare("UPDATE riwayat_peserta_pkl SET status = 'keluar' WHERE id = ?");
        if (!$update) {
            throw new Exception("Prepare gagal (UPDATE): " . $conn->error);
        }

        $update->bind_param("i", $id);
        $update->execute();
        $update->close();

        // 3. Hapus akun user dari tabel users
        $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
        if (!$delete) {
            throw new Exception("Prepare gagal (DELETE): " . $conn->error);
        }

        $delete->bind_param("i", $user_id);
        $delete->execute();
        $delete->close();

        // 4. Commit transaksi
        $conn->commit();

        // 5. Redirect dengan pesan sukses
        header("Location: riwayat_peserta.php?logout_success=1");
        exit;

    } catch (Exception $e) {
        // Rollback jika ada error
        $conn->rollback();
        echo "<pre style='color:red;'>Terjadi kesalahan: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }

} else {
    // Kalau tidak lewat POST, redirect balik
    header("Location: riwayat_peserta.php");
    exit;
}
?>
