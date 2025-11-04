<?php
// ===============================================
// File: send_email.php
// Fungsi: Mengirim notifikasi ke mentor & admin
//         jika masa PKL peserta akan segera berakhir
// ===============================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ======================================================
// Load library PHPMailer dan koneksi database
// ======================================================
require '../vendor/autoload.php';
include '../config/database.php'; // File koneksi database MySQL

// ======================================================
// Ambil peserta yang masa PKL-nya akan berakhir dalam 3 hari
// dan status_email masih 'belum'
// ======================================================
$query = "
    SELECT 
        p.id AS pkl_id,
        p.user_id,
        p.status_email,
        u.nama AS nama_peserta,
        up.nama_unit,
        p.tgl_selesai
    FROM peserta_pkl p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN unit_pkl up ON p.unit_id = up.id
    WHERE p.tgl_selesai <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
      AND p.status_email = 'belum'
";

$result = $conn->query($query);

// ======================================================
// Loop setiap peserta dan kirim email pemberitahuan
// ======================================================
while ($peserta = $result->fetch_assoc()) {

    $mail = new PHPMailer(true);

    try {
        // -----------------------------------------------
        // Konfigurasi SMTP (Gunakan akun Gmail)
        // -----------------------------------------------
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mfaisalsholeh@gmail.com';       // Ganti dengan email pengirim
        $mail->Password   = 'qiae zqjd itlg fhwd';           // Gunakan App Password Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // -----------------------------------------------
        // Penerima Email
        // -----------------------------------------------
        $mail->addAddress('sahlarizki40@gmail.com', 'Admin PKL');
        $mail->addAddress('asyaherawatiputri.08@gmail.com', 'Mentor PKL');
        // $mail->addCC('admin@pkl.id', 'Admin PKL'); // (Opsional)

        // -----------------------------------------------
        // Konten Email
        // -----------------------------------------------
        $mail->isHTML(true);
        $mail->Subject = 'PESERTA PKL AKAN BERAKHIR';
        $mail->Body = "
            <h3>Informasi Akhir Masa PKL</h3>
            <p>Peserta: <b>{$peserta['nama_peserta']}</b></p>
            <p>Unit Peserta: {$peserta['nama_unit']}</p>
            <p>Tanggal Berakhir: <b>{$peserta['tgl_selesai']}</b></p>
            <p>Mohon mentor mempersiapkan penilaian dan laporan akhir.</p>
        ";

        // -----------------------------------------------
        // Kirim Email
        // -----------------------------------------------
        $mail->send();

        // -----------------------------------------------
        // Update status_email agar tidak dikirim ulang
        // -----------------------------------------------
        $update = "
            UPDATE peserta_pkl 
            SET status_email = 'terkirim' 
            WHERE user_id = {$peserta['user_id']}
        ";
        $conn->query($update);

        // -----------------------------------------------
        // Output hasil
        // -----------------------------------------------
        echo "✅ Email terkirim ke mentor untuk peserta {$peserta['nama_peserta']}<br>";

    } catch (Exception $e) {
        // -----------------------------------------------
        // Jika gagal kirim email
        // -----------------------------------------------
        echo "❌ Gagal mengirim email untuk {$peserta['nama_peserta']}: {$mail->ErrorInfo}<br>";
    }
}
