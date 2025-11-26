<?php
include "../../includes/auth.php";
checkRole('magang');
include "../../config/database.php";

$peserta_id = $_SESSION['user_id'];

// Ambil data peserta
$q = $conn->query("SELECT id, laporan_pkl, pesan_admin 
                   FROM peserta_pkl 
                   WHERE user_id = '$peserta_id' LIMIT 1");

if (!$q) {
    die("Error database: " . $conn->error);
}

if ($q->num_rows == 0) {
    die("Data peserta PKL tidak ditemukan.");
}

$data = $q->fetch_assoc();
$pk_id        = $data['id'];
$laporan_lama = $data['laporan_pkl'];
$pesan_admin  = $data['pesan_admin'] ?? '';

$error = '';
$success = '';

/* =====================================
   FIX PENTING 1:
   Decode newline dari database SEBELUM
   dipakai ke textarea
===================================== */
$pesan_admin = str_replace("\\n", "\n", $pesan_admin);

/* =====================================
   siapkan nilai default textarea
===================================== */
$pesan_textarea = $pesan_admin;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---------- Upload Laporan ---------- */
    if (isset($_POST['upload_laporan'])) {

        if (isset($_FILES['laporan']) && $_FILES['laporan']['error'] === 0) {

            $ext = strtolower(pathinfo($_FILES['laporan']['name'], PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $error = "File harus berformat PDF.";
            } else {

                $nama_file = "laporan_" . $pk_id . "_" . time() . ".pdf";
                $tujuan = "../../uploads/laporan/" . $nama_file;

                if (!file_exists("../../uploads/laporan")) {
                    mkdir("../../uploads/laporan", 0777, true);
                }

                if (move_uploaded_file($_FILES['laporan']['tmp_name'], $tujuan)) {

                    if ($laporan_lama && file_exists("../../uploads/laporan/" . $laporan_lama)) {
                        unlink("../../uploads/laporan/" . $laporan_lama);
                    }

                    $conn->query("UPDATE peserta_pkl SET laporan_pkl = '$nama_file' WHERE id = '$pk_id'");
                    $success = "Laporan berhasil diupload.";
                    $laporan_lama = $nama_file;

                } else {
                    $error = "Gagal mengupload file.";
                }
            }
        } else {
            $error = "Tidak ada file yang diupload.";
        }
    }


    /* ---------- Simpan pesan ---------- */
    if (isset($_POST['simpan_catatan'])) {

        $pesan = trim($_POST['catatan_biodata']);

        // Normalisasi newline
        $pesan = str_replace(["\r\n", "\r"], "\n", $pesan);

        $pesan_db = $conn->real_escape_string($pesan);

        $update = $conn->query("UPDATE peserta_pkl 
                                SET pesan_admin = '$pesan_db' 
                                WHERE id = '$pk_id'");

        if ($update) {
            $success = !empty($pesan) ? "Pesan berhasil dikirim ke admin." : "Pesan berhasil dihapus.";
            $pesan_admin = $pesan; // Load ulang ke preview
            $pesan_textarea = $pesan;
        } else {
            $error = "Gagal menyimpan pesan: " . $conn->error;
        }
    }
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Laporan & Pesan - Telkom Indonesia</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #F5F5F5;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            color: #333;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-left: 6px solid #E31E24;
            animation: slideDown 0.5s ease-out;
            position: relative;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #E31E24 0%, #C4161C 50%, #E31E24 100%);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header h3 {
            margin: 0 0 8px 0;
            font-weight: 700;
            font-size: 1.75rem;
            color: #333;
        }

        .page-header p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }

        .page-header i {
            color: #E31E24;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .card-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #E31E24 0%, #C4161C 100%);
            color: white;
            padding: 20px 25px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-header-custom i {
            margin-right: 8px;
        }

        .card-body-custom {
            padding: 25px;
        }

        .info-box {
            background: linear-gradient(135deg, #FFF5F5 0%, #FFE8E8 100%);
            border-left: 4px solid #E31E24;
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .info-box i {
            color: #E31E24;
            margin-right: 6px;
        }

        .info-box strong {
            color: #E31E24;
        }

        .info-box p {
            margin: 8px 0 0 0;
            color: #555;
            line-height: 1.5;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-danger {
            background: #FFE8E8;
            color: #C4161C;
            border-left: 4px solid #E31E24;
        }

        .alert-success {
            background: #D4EDDA;
            color: #155724;
            border-left: 4px solid #28A745;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #E5E5E5;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #E31E24;
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.15);
            outline: none;
        }

        textarea.form-control {
            min-height: 180px;
            resize: vertical;
        }

        .btn-telkom {
            background: linear-gradient(135deg, #E31E24 0%, #C4161C 100%);
            border: none;
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(227, 30, 36, 0.3);
            width: 100%;
        }

        .btn-telkom:hover {
            background: linear-gradient(135deg, #C4161C 0%, #A01419 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 30, 36, 0.4);
            color: white;
        }

        .btn-outline-telkom {
            background: white;
            border: 2px solid #E31E24;
            color: #E31E24;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-outline-telkom:hover {
            background: #E31E24;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 30, 36, 0.3);
        }

        .btn-secondary {
            background: #6C757D;
            border: none;
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-secondary:hover {
            background: #5A6268;
            transform: translateY(-2px);
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #E5E5E5, transparent);
            margin: 20px 0;
        }

        .file-info {
            background: #F8F9FA;
            border-radius: 10px;
            padding: 18px;
            margin-top: 15px;
        }

        .file-info-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .back-button-container {
            margin-top: 30px;
            text-align: center;
        }

        .back-button-container .btn-secondary {
            max-width: 300px;
            margin: 0 auto;
        }

        .form-control[type="file"]::file-selector-button {
            background: #E31E24;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            margin-right: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .form-control[type="file"]::file-selector-button:hover {
            background: #C4161C;
        }

        .notes-info {
            background: #FFF9E6;
            border-left: 4px solid #FFC107;
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .notes-info i {
            color: #FFC107;
            margin-right: 6px;
        }

        .notes-info strong {
            color: #856404;
        }

        .notes-info ul {
            margin: 8px 0 0 20px;
            color: #555;
            line-height: 1.6;
        }

        .message-preview {
            background: #E8F5E9;
            border-left: 4px solid #28A745;
            padding: 15px 18px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .message-preview strong {
            color: #155724;
            display: block;
            margin-bottom: 8px;
        }

        .message-preview p {
            color: #555;
            margin: 0;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .message-preview small {
            color: #666;
            font-style: italic;
            display: block;
            margin-top: 8px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header > div {
                flex-direction: column !important;
                gap: 20px !important;
            }
            
            .page-header > div > div:first-child {
                width: 100%;
            }
            
            .page-header .btn-outline-telkom {
                width: 100% !important;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 15px;
            }

            .page-header {
                padding: 20px;
            }

            .page-header h3 {
                font-size: 1.35rem;
            }

            .card-body-custom {
                padding: 20px;
            }

            .btn-telkom,
            .btn-outline-telkom,
            .btn-secondary {
                padding: 11px 16px;
                font-size: 0.9rem;
            }
        }

        .mb-3 { margin-bottom: 1rem !important; }
        .mb-2 { margin-bottom: 0.5rem !important; }
        .me-1 { margin-right: 0.25rem !important; }
        .me-2 { margin-right: 0.5rem !important; }
    </style>
</head>

<body>

<div class="main-container">
    
    <!-- Header -->
    <div class="page-header">
        <div style="display: flex; align-items: center; gap: 15px; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <!-- Logo Telkom -->
                <img src="../assets/img/instepterbaru.png" alt="Telkom Indonesia" style="width: 120px; height: 120px; object-fit: contain;">
                <div>
                    <h3><i class="bi bi-file-earmark-text me-2"></i>Upload Laporan & Pesan Perbaikan</h3>
                    <p>Telkom Indonesia — Witel Bekasi Karawang</p>
                </div>
            </div>
            <!-- Tombol Kembali -->
            <a href="dashboard.php" class="btn btn-outline-telkom" style="width: auto; white-space: nowrap;">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
        </div>
    <?php } ?>

    <?php if (!empty($success)) { ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
        </div>
    <?php } ?>

    <!-- Content Grid -->
    <div class="content-grid">

        <!-- Kolom Kiri: Upload Laporan -->
        <div class="card-section">
            <div class="card-header-custom">
                <i class="bi bi-cloud-upload"></i>Upload Laporan PKL
            </div>
            <div class="card-body-custom">
                
                <div class="info-box">
                    <div>
                        <i class="bi bi-info-circle-fill"></i>
                        <strong>Informasi</strong>
                    </div>
                    <p>Upload laporan PKL Anda dalam format PDF ketika kegiatan sudah mendekati selesai.</p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <label class="form-label">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Pilih File Laporan (PDF)
                    </label>
                    <input type="file" name="laporan" class="form-control mb-3" accept=".pdf" required>

                    <button type="submit" name="upload_laporan" class="btn btn-telkom">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload Laporan
                    </button>
                </form>

                <?php if ($laporan_lama) { ?>
                <div class="divider"></div>
                
                <div class="file-info">
                    <p class="file-info-title">
                        <i class="bi bi-file-earmark-check me-1"></i>Laporan yang Sudah Diupload
                    </p>
                    <a href="../../uploads/laporan/<?= $laporan_lama ?>" target="_blank" 
                       class="btn btn-outline-telkom">
                        <i class="bi bi-eye me-1"></i> Lihat Laporan PKL
                    </a>
                </div>
                <?php } ?>

            </div>
        </div>

        <!-- Kolom Kanan: Pesan untuk Admin -->
        <div class="card-section">
            <div class="card-header-custom">
                <i class="bi bi-chat-left-text"></i>Pesan Perbaikan (Jika Ada)
            </div>
            <div class="card-body-custom">
                
                <div class="notes-info">
                    <div>
                        <i class="bi bi-envelope-heart-fill"></i>
                        <strong>Sampaikan Pesan Anda</strong>
                    </div>
                    <ul>
                        <li>Koreksi data diri (jika ada kesalahan)</li>
                        <li>Perubahan informasi untuk surat dan sertifikat</li>
                        <li>Perpanjangan masa PKL (sertakan durasi dan tanggal berakhir yang diinginkan)</li>
                    </ul>
                </div>

                <form method="POST">
                    <label class="form-label">
                        <i class="bi bi-pencil-fill me-1"></i>Tulis Pesan Anda
                    </label>
                    <textarea name="catatan_biodata"
                        class="form-control mb-3"
                        placeholder="Tulis disini..."><?php echo htmlspecialchars($pesan_textarea, ENT_QUOTES, 'UTF-8'); ?></textarea>

                    <button type="submit" name="simpan_catatan" class="btn btn-telkom">
                        <i class="bi bi-send-fill me-1"></i> <?= !empty($pesan_admin) ? 'Update Pesan' : 'Kirim Pesan' ?>
                    </button>
                </form>

                <?php if (!empty($pesan_admin)) { 
                    $pesan_admin = str_replace("\\n", "\n", $pesan_admin);  // FIX PENTING
                    $pesan_clean = htmlspecialchars($pesan_admin, ENT_QUOTES, 'UTF-8');
                    $pesan_display = nl2br($pesan_clean, false);
                ?>
                <div class="message-preview">
                    <strong><i class="bi bi-check-circle-fill me-1"></i>Pesan Terkirim:</strong>
                    <p><?php echo $pesan_display; ?></p>
                    <small>Anda dapat mengedit pesan ini kapan saja.</small>
                </div>
                <?php } ?>

            </div>
        </div>

    </div>

</div>

</body>
</html>