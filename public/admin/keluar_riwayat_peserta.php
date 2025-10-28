<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    try {
        // Mulai transaksi
        $conn->begin_transaction();

        // 1️⃣ Ambil user_id dan email peserta dari riwayat_peserta_pkl
        $stmt = $conn->prepare("SELECT user_id, email FROM riwayat_peserta_pkl WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Gagal menyiapkan query SELECT: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Data peserta tidak ditemukan di tabel riwayat_peserta_pkl.");
        }

        $row = $result->fetch_assoc();
        $user_id = intval($row['user_id']);
        $email   = $row['email'];
        $stmt->close();

        // 2️⃣ Update status jadi 'nonaktif' di tabel riwayat_peserta_pkl
        $updateRiwayat = $conn->prepare("
            UPDATE riwayat_peserta_pkl 
            SET status = 'nonaktif' 
            WHERE id = ?
        ");
        if (!$updateRiwayat) {
            throw new Exception("Gagal menyiapkan query UPDATE riwayat_peserta_pkl: " . $conn->error);
        }
        $updateRiwayat->bind_param("i", $id);
        $updateRiwayat->execute();
        $updateRiwayat->close();

        // 3️⃣ Update status di tabel daftar_pkl jadi 'nonaktif' berdasarkan email
        $updateDaftar = $conn->prepare("
            UPDATE daftar_pkl 
            SET status = 'nonaktif' 
            WHERE email = ?
        ");
        if (!$updateDaftar) {
            throw new Exception("Gagal menyiapkan query UPDATE daftar_pkl: " . $conn->error);
        }
        $updateDaftar->bind_param("s", $email);
        $updateDaftar->execute();
        $updateDaftar->close();

        // 4️⃣ Hapus akun user dari tabel users berdasarkan email
        $deleteUser = $conn->prepare("DELETE FROM users WHERE email = ?");
        if (!$deleteUser) {
            throw new Exception("Gagal menyiapkan query DELETE users: " . $conn->error);
        }
        $deleteUser->bind_param("s", $email);
        $deleteUser->execute();
        $deleteUser->close();

        // 5️⃣ Commit transaksi
        $conn->commit();

        // ✅ Sukses
        $_SESSION['success'] = "✅ Akun peserta berhasil dinonaktifkan dan dihapus dari sistem.";
        header("Location: riwayat_peserta.php?logout_success=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "❌ Gagal menonaktifkan peserta: " . $e->getMessage();
        header("Location: riwayat_peserta.php");
        exit;
    }
} else {
    header("Location: riwayat_peserta.php");
    exit;
}
?>
