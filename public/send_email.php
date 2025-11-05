<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';
include '../../config/database.php'; // koneksi ke database

// Pastikan folder logs tersedia
$logDir = '../../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// ===============================
// Ambil peserta yang masa PKL berakhir dalam 3 hari dan belum dikirim email
// ===============================
$query = "
    SELECT 
        p.id AS pkl_id,
        p.user_id,
        p.status_email,
        u.nama AS nama_peserta,
        up.nama_unit,
        p.tgl_selesai,
        ck.email AS email_mentor,
        ck.nama_karyawan AS nama_mentor
    FROM peserta_pkl p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN unit_pkl up ON p.unit_id = up.id
    LEFT JOIN cp_karyawan ck ON p.unit_id = ck.unit_id
    WHERE p.tgl_selesai <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)
      AND p.status_email = 'belum'
";

$result = $conn->query($query);

// ===============================
// Proses setiap peserta yang ditemukan
// ===============================
while ($peserta = $result->fetch_assoc()) {
    // Lewati jika tidak ada email mentor
    if (empty($peserta['email_mentor'])) {
        file_put_contents(
            $logDir . '/email_error.log',
            date('Y-m-d H:i:s') . " - SKIP: Tidak ada email mentor untuk unit {$peserta['nama_unit']} ({$peserta['nama_peserta']})\n",
            FILE_APPEND
        );
        continue;
    }

    $mail = new PHPMailer(true);

    try {
        // ===============================
        // Konfigurasi SMTP
        // ===============================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mfaisalsholeh@gmail.com'; // email pengirim
        $mail->Password   = 'qiae zqjd itlg fhwd';     // app password Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // ===============================
        // Penerima email
        // ===============================
        $mail->setFrom('mfaisalsholeh@gmail.com', 'Sistem PKL Telkom');
        $mail->addAddress($peserta['email_mentor'], $peserta['nama_mentor']); // mentor sesuai unit
        $mail->addCC('tjslhcwitelbekasi@gmail.com', 'Admin PKL'); // opsional

        // ===============================
        // Isi email
        // ===============================
        $mail->isHTML(true);
        $mail->Subject = 'Pemberitahuan: Masa Internship Peserta Akan Berakhir';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color:#222; line-height:1.6; background-color:#fafafa; padding:20px; border-radius:10px; border:1px solid #eee;'>
                <h2 style='color:#d60000; text-align:center; margin-bottom:15px;'>📢 Informasi Akhir Masa PKL</h2>
                
                <p><b>Peserta:</b> {$peserta['nama_peserta']}</p>
                <p><b>Unit Peserta:</b> {$peserta['nama_unit']}</p>
                <p><b>Tanggal Berakhir:</b> <span style='color:#d60000;'>{$peserta['tgl_selesai']}</span></p>

                <hr style='border:1px solid #ddd; margin:20px 0;'>

                <div style='margin-bottom:20px;'>
                    <h3 style='margin-bottom:5px;'>👨‍🏫 Kepada Mentor <b>{$peserta['nama_mentor']}</b></h3>
                    <p style='margin:0;'>Mohon untuk segera menyiapkan <b>penilaian</b> dan <b>laporan akhir</b> bagi peserta tersebut agar proses akhir <i>Internship</i> dapat berjalan lancar.</p>
                </div>

                <div style='background-color:#eef6ff; padding:15px; border-radius:8px; border-left:5px solid #007bff;'>
                    <h3 style='margin-bottom:5px;'>🧑‍💻 Kepada Admin PKL</h3>
                    <p style='margin:0;'>
                        Setelah masa <i>Internship</i> peserta selesai, silakan <b>klik tombol “Selesai”</b> pada data peserta di sistem InStep
                        untuk mengaktifkan fitur <b>cetak Sertifikat</b> dan <b>Surat Selesai Magang</b>.
                    </p>
                </div>
                <br>
                <p>Terima kasih atas kerja samanya 🙏</p>
                <p style='color:#555; font-size:0.9em; text-align:center;'>
                    <i>-- Sistem InStep Telkom Witel Bekasi - Karawang</i>
                </p>
            </div>
        ";

        // ===============================
        // Kirim email
        // ===============================
        $mail->send();

        // ===============================
        // Update status agar tidak dikirim ulang
        // ===============================
        $update = "UPDATE peserta_pkl SET status_email = 'terkirim' WHERE user_id = {$peserta['user_id']}";
        $conn->query($update);

        // Simpan log sukses (tidak tampil di browser)
        file_put_contents(
            $logDir . '/email_success.log',
            date('Y-m-d H:i:s') . " - SUKSES: Email ke {$peserta['email_mentor']} untuk peserta {$peserta['nama_peserta']}\n",
            FILE_APPEND
        );

    } catch (Exception $e) {
        // Simpan error ke file log
        file_put_contents(
            $logDir . '/email_error.log',
            date('Y-m-d H:i:s') . " - GAGAL: {$peserta['nama_peserta']} ({$peserta['email_mentor']}) - {$mail->ErrorInfo}\n",
            FILE_APPEND
        );
    }
}
