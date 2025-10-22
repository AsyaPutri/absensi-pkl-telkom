<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Mulai transaksi agar aman kalau ada error
    $conn->begin_transaction();

    try {
        // ==================================================
        // 1️⃣ Ambil data peserta dari tabel peserta_pkl
        // ==================================================
        $sql = "
          SELECT 
              id AS peserta_id,
              user_id,
              unit_id,
              nama,
              nis_npm,
              email,
              instansi_pendidikan,
              jurusan,
              no_hp,
              tgl_mulai,
              tgl_selesai
          FROM peserta_pkl
          WHERE id = ?
          LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare gagal (SELECT): " . $conn->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new Exception("Peserta tidak ditemukan.");
        }

        // ==================================================
        // 2️⃣ Update status peserta di tabel peserta_pkl
        // ==================================================
        $status_selesai = 'selesai';

        $update = $conn->prepare("UPDATE peserta_pkl SET status = ? WHERE id = ?");
        if (!$update) {
            throw new Exception("Prepare gagal (UPDATE): " . $conn->error);
        }

        $update->bind_param("si", $status_selesai, $id);
        $update->execute();
        $update->close();

        // ==================================================
        // 3️⃣ Cek apakah sudah ada di riwayat, biar tidak duplikat
        // ==================================================
        $check = $conn->prepare("SELECT id FROM riwayat_peserta_pkl WHERE user_id = ?");
        $check->bind_param("i", $row['user_id']);
        $check->execute();
        $check_result = $check->get_result();
        $exists = $check_result->num_rows > 0;
        $check->close();

        // ==================================================
        // 4️⃣ Kalau belum ada, masukkan ke tabel riwayat_peserta_pkl
        // ==================================================
        if (!$exists) {
            $insert = $conn->prepare("
                INSERT INTO riwayat_peserta_pkl (
                    user_id, unit_id, nama, nis_npm, email, 
                    instansi_pendidikan, jurusan, no_hp, 
                    tgl_mulai, tgl_selesai, status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$insert) {
                throw new Exception("Prepare gagal (INSERT): " . $conn->error);
            }

            $insert->bind_param(
                "iisssssssss",
                $row['user_id'],
                $row['unit_id'],
                $row['nama'],
                $row['nis_npm'],
                $row['email'],
                $row['instansi_pendidikan'],
                $row['jurusan'],
                $row['no_hp'],
                $row['tgl_mulai'],
                $row['tgl_selesai'],
                $status_selesai
            );

            $insert->execute();
            $insert->close();
        }

        // ==================================================
        // 5️⃣ Commit transaksi
        // ==================================================
        $conn->commit();

        header("Location: peserta.php?success=1");
        exit;

    } catch (Exception $e) {
        // Jika ada error, rollback biar data tetap aman
        $conn->rollback();
        error_log("Error selesai_peserta: " . $e->getMessage());
        echo "<pre style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }
} else {
    // Akses langsung tanpa POST akan diarahkan balik
    header("Location: peserta.php");
    exit;
}
?>
