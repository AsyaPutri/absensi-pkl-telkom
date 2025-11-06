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
// Ambil peserta yang masa PKL berakhir tepat H-3 (3 hari lagi) dan belum dikirim email
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
    WHERE DATE(p.tgl_selesai) = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
      AND p.status_email = 'belum'
";

$result = $conn->query($query);

// ===============================
// Proses setiap peserta
// ===============================
while ($peserta = $result->fetch_assoc()) {
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
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mfaisalsholeh@gmail.com';
        $mail->Password   = 'qiae zqjd itlg fhwd';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Penerima
        $mail->setFrom('mfaisalsholeh@gmail.com', 'Sistem PKL Telkom');
        $mail->addAddress($peserta['email_mentor'], $peserta['nama_mentor']);
        $mail->addCC('tjslhcwitelbekasi@gmail.com', 'Admin PKL');

        // Isi email
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = 'Pemberitahuan: Masa Internship Peserta Akan Berakhir (H-3)';
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin:0; padding:0; background-color:#f4f4f4;'>
            <div style='max-width:600px; margin:20px auto; background-color:#ffffff; border-radius:10px; padding:20px; font-family: Arial, sans-serif; color:#222; line-height:1.6; border:1px solid #eee;'>
                <h2 style='color:#d60000; text-align:center; margin-bottom:15px;'>📢 Informasi Akhir Masa Internship</h2>
                <p><b>Peserta:</b> {$peserta['nama_peserta']}</p>
                <p><b>Unit Peserta:</b> {$peserta['nama_unit']}</p>
                <p>
                    <b>Tanggal Berakhir:</b>
                    <span style='color:#d60000;'>{$peserta['tgl_selesai']}</span>
                    <span style='color:#555; font-size:0.9em;'>(H-3 Peserta Internship Berakhir)</span>
                </p>
                <hr style='border:1px solid #ddd; margin:20px 0;'>
                <div style='margin-bottom:20px;'>
                    <h3 style='margin-bottom:5px;'>🧑‍🏫 Kepada Mentor <b>{$peserta['nama_mentor']}</b></h3>
                    <p style='margin:0;'>
                        Mohon untuk segera menyiapkan <b>penilaian</b> dan <b>laporan akhir</b> bagi peserta tersebut agar proses akhir <i>Internship</i> dapat berjalan lancar.
                    </p>
                </div>
                <div style='background-color:#eef6ff; padding:15px; border-radius:8px; border-left:5px solid #007bff;'>
                    <h3 style='margin-bottom:5px;'>👩‍💼 Kepada Admin PKL</h3>
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
        </body>
        </html>
        ";

        // Kirim email
        $mail->send();

        // Update status
        $update = "UPDATE peserta_pkl SET status_email = 'terkirim' WHERE user_id = {$peserta['user_id']}";
        $conn->query($update);

        // Log sukses
        file_put_contents(
            $logDir . '/email_success.log',
            date('Y-m-d H:i:s') . " - SUKSES: Email ke {$peserta['email_mentor']} untuk peserta {$peserta['nama_peserta']}\n",
            FILE_APPEND
        );

    } catch (Exception $e) {
        file_put_contents(
            $logDir . '/email_error.log',
            date('Y-m-d H:i:s') . " - GAGAL: {$peserta['nama_peserta']} ({$peserta['email_mentor']}) - {$mail->ErrorInfo}\n",
            FILE_APPEND
        );
    }
}
