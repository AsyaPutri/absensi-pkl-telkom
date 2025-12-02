<?php
session_start();
// Koneksi database
include "../config/database.php";

// Ambil data unit dari database
$query = "SELECT id, nama_unit, lokasi, kuota, jurusan, jobdesk FROM unit_pkl ORDER BY nama_unit";
$result = $conn->query($query);

$positions = [];
$total_unit = 0;
$total_kuota = 0;

if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Hitung jumlah pendaftar pending untuk unit ini
            $unit_id = $row['id'];
            $count_query = "SELECT COUNT(*) as total_pendaftar FROM daftar_pkl WHERE unit_id = ? AND status = 'pending'";
            $stmt = $conn->prepare($count_query);
            $stmt->bind_param("i", $unit_id);
            $stmt->execute();
            $count_result = $stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            
            $row['total_pendaftar'] = $count_row['total_pendaftar'];
            
            $positions[] = $row;
            $total_unit++;
            $total_kuota += (int)$row['kuota'];
            
            $stmt->close();
        }
    }
} else {
    // Debug error jika query gagal
    die("Error query: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instep - Internship Program Telkom Witel Bekasi-Karawang</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #fee2e2 0%, #ffffff 50%, #f3f4f6 100%);
        }
        
        .gradient-red {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .progress-bar-animated {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        .navbar-logo {
            height: 85px;
            width: auto;
            margin-right: 12px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
        }

        .navbar-brand h5 {
            color: #dc2626 !important;
        }

        .navbar-brand small {
            color: #6b7280 !important;
        }

        .btn-login {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            border: none;
            color: white !important;
            padding: 8px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
            color: white !important;
        }

        /* Fix untuk mobile menu */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: white;
                padding: 1rem;
                border-radius: 8px;
                margin-top: 1rem;
            }
            
            .btn-login {
                width: 100%;
            }
        }

        /* Pastikan navbar items terlihat */
        .navbar-nav .nav-link {
            color: #374151 !important;
            padding: 0.5rem 1rem !important;
            font-weight: 500;
        }

        .navbar-nav .nav-link:hover {
            color: #dc2626 !important;
        }

        /* Hero Section Text Colors */
        .hero-badge {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626 !important;
            font-weight: 600;
        }

        .hero-title {
            color: #111827;
        }

        .hero-title .text-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            color: #4b5563;
        }

        /* Section Headers */
        .section-title {
            color: #111827;
        }

        .section-subtitle {
            color: #6b7280;
        }

        /* Footer customization */
        .footer-logo {
            height: 60px;
            width: auto;
            margin-right: 12px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-title {
            color: #ffffff;
            margin-bottom: 0;
        }

        .footer-text {
            color: #d1d5db;
        }
    </style>
</head>
<body class="gradient-bg">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/InStep.png" alt="InStep Logo" class="navbar-logo">
                <div>
                    <h5 class="mb-0 fw-bold">InStep</h5>
                    <small class="text-muted">Telkom Witel Bekasi-Karawang</small>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#positions">Lowongan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#process">Persyaratan</a>
                    </li>
                    <li class="nav-item mt-2 mt-lg-0 ms-lg-3">
                        <a class="btn btn-login" href="login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <span class="badge hero-badge mb-3 p-3">
                        🎓 Internship Program Telkom Witel Bekasi - Karawang
                    </span>
                    <h1 class="display-3 fw-bold mb-4 hero-title">
                        Bergabunglah dengan <span class="text-danger">InStep</span>
                    </h1>
                    <p class="lead mb-4 hero-description">
                        Program internship profesional di Telkom Witel Bekasi-Karawang. Kembangkan skill, bangun network, 
                        dan raih pengalaman kerja bersama mentor terbaik di industri telekomunikasi.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="register.php" class="btn btn-danger btn-lg px-4 gradient-red text-white">
                            Daftar Sekarang <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                        <a href="#positions" class="btn btn-outline-danger btn-lg px-4">
                            Lihat Lowongan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center border-0 shadow-sm card-hover transition">
                        <div class="card-body">
                            <i class="fas fa-briefcase text-danger fs-1 mb-3"></i>
                            <h3 class="fw-bold"><?= $total_unit ?></h3>
                            <p class="text-muted mb-0">Unit Tersedia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center border-0 shadow-sm card-hover transition">
                        <div class="card-body">
                            <i class="fas fa-users text-danger fs-1 mb-3"></i>
                            <h3 class="fw-bold"><?= $total_kuota ?></h3>
                            <p class="text-muted mb-0">Kuota Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center border-0 shadow-sm card-hover transition">
                        <div class="card-body">
                            <i class="fas fa-calendar text-danger fs-1 mb-3"></i>
                            <h3 class="fw-bold">3-6 Bulan</h3>
                            <p class="text-muted mb-0">Durasi</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center border-0 shadow-sm card-hover transition">
                        <div class="card-body">
                            <i class="fas fa-award text-danger fs-1 mb-3"></i>
                            <h3 class="fw-bold">Resmi</h3>
                            <p class="text-muted mb-0">Sertifikat</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Positions Section -->
    <section id="positions" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3 section-title">Lowongan Unit</h2>
                <p class="section-subtitle col-lg-6 mx-auto">
                    Pilih unit yang sesuai dengan minat dan keahlian Anda. Setiap unit menawarkan pengalaman berbeda dengan mentor profesional.
                </p>
            </div>

            <div class="row g-4">
                <?php foreach ($positions as $index => $position): 
                    $kuota = (int)$position['kuota'];
                    $pendaftar = (int)$position['total_pendaftar'];
                    $badgeClass = 'bg-success';
                    $badgeText = $pendaftar . ' Pendaftar';
                    
                    // Parse jobdesk (misal disimpan sebagai JSON atau dipisah dengan |||)
                    $jobdesk_array = [];
                    if (!empty($position['jobdesk'])) {
                        // Coba parse sebagai JSON dulu
                        $decoded = json_decode($position['jobdesk'], true);
                        if (is_array($decoded)) {
                            $jobdesk_array = $decoded;
                        } else {
                            // Jika bukan JSON, split dengan delimiter
                            $jobdesk_array = explode('|||', $position['jobdesk']);
                        }
                    }
                    
                    // Parse jurusan
                    $requirements = [];
                    if (!empty($position['jurusan'])) {
                        $requirements[] = 'Jurusan: ' . $position['jurusan'];
                    }
                ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card border-0 shadow-sm h-100 card-hover">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($position['nama_unit']) ?></h5>
                                        <small class="text-muted">📍 <?= htmlspecialchars($position['lokasi']) ?></small>
                                    </div>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= $badgeText ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-users text-danger me-1"></i>
                                        <strong>Kuota:</strong> <?= $kuota ?> orang
                                    </small>
                                </div>
                                
                                <button class="btn btn-outline-danger w-100" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#unitModal<?= $position['id'] ?>">
                                    Lihat Detail
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for each position -->
                    <div class="modal fade" id="unitModal<?= $position['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h4 class="modal-title fw-bold"><?= htmlspecialchars($position['nama_unit']) ?></h4>
                                        <p class="mb-0 text-muted">📍 <?= htmlspecialchars($position['lokasi']) ?></p>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Kuota Information -->
                                    <div class="alert alert-info d-flex align-items-center mb-4">
                                        <i class="fas fa-info-circle fs-4 me-3"></i>
                                        <div>
                                            <strong>Kuota:</strong> <?= $kuota ?> orang
                                        </div>
                                    </div>

                                    <?php if (!empty($jobdesk_array)): ?>
                                        <h5 class="fw-bold mb-3">
                                            <i class="fas fa-file-alt text-danger me-2"></i>Job Description
                                        </h5>
                                        <ul class="list-unstyled">
                                            <?php foreach ($jobdesk_array as $job): ?>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i><?= htmlspecialchars(trim($job)) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <?php if (!empty($requirements)): ?>
                                        <h5 class="fw-bold mb-3 mt-4">
                                            <i class="fas fa-users text-danger me-2"></i>Persyaratan
                                        </h5>
                                        <ul class="list-unstyled">
                                            <?php foreach ($requirements as $req): ?>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-primary me-2"></i><?= htmlspecialchars($req) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <a href="register.php?unit_id=<?= $position['id'] ?>" class="btn btn-danger gradient-red w-100">
                                        Daftar Posisi Ini <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 gradient-red text-white">
        <div class="container">
            <h2 class="display-5 fw-bold text-center mb-5">Tentang Instep</h2>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <i class="fas fa-bullseye fa-3x mb-3"></i>
                    <h4 class="fw-bold mb-3">Tujuan</h4>
                    <p class="text-white-50">Memberikan pengalaman kerja nyata di industri telekomunikasi</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h4 class="fw-bold mb-3">Mentorship</h4>
                    <p class="text-white-50">Bimbingan langsung dari profesional berpengalaman</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-award fa-3x mb-3"></i>
                    <h4 class="fw-bold mb-3">Sertifikat</h4>
                    <p class="text-white-50">Sertifikat resmi dari Telkom Indonesia</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Requirements & Process Section -->
    <section id="process" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3 section-title">Persyaratan Internship</h2>
                <p class="section-subtitle">Dokumen yang perlu disiapkan untuk mendaftar program Instep</p>
            </div>

            <div class="row g-4 mb-5">
                <?php
                $requirements = [
                    ['icon' => 'fa-image', 'title' => 'Pas Foto 3x4', 'desc' => '1 lembar pas foto formal ukuran 3x4'],
                    ['icon' => 'fa-file-alt', 'title' => 'Surat Permohonan', 'desc' => 'Surat permohonan resmi dari institusi pendidikan'],
                    ['icon' => 'fa-id-card', 'title' => 'Kartu Mahasiswa', 'desc' => 'Kartu Tanda Mahasiswa atau Kartu Pelajar yang masih berlaku'],
                    ['icon' => 'fa-stamp', 'title' => 'Materai 10.000', 'desc' => 'Materai 10.000 rupiah untuk pengesahan dokumen'],
                    ['icon' => 'fa-mobile-alt', 'title' => 'Nomor Telkomsel', 'desc' => 'Nomor HP Telkomsel aktif terhubung dengan WhatsApp'],
                    ['icon' => 'fa-laptop', 'title' => 'Laptop Pribadi', 'desc' => 'Membawa laptop pribadi untuk kegiatan internship']
                ];

                foreach ($requirements as $req): ?>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100 card-hover">
                            <div class="card-body d-flex">
                                <div class="me-3">
                                    <i class="fas <?= $req['icon'] ?> fa-3x text-danger"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2"><?= $req['title'] ?></h5>
                                    <p class="text-muted mb-0"><?= $req['desc'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Important Notes -->
            <div class="card border-0 shadow-sm mb-5 gradient-red text-white">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> Catatan Penting
                    </h4>
                    <ul class="mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2"></i>
                            Bersedia ditempatkan di unit manapun sesuai domisili
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2"></i>
                            Unit di area Witel Bekasi dapat diikuti oleh jenjang apapun
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Registration Process -->
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold section-title">Proses Pendaftaran</h2>
            </div>

            <div class="row g-4">
                <?php
                $process = [
                    ['step' => '1', 'title' => 'Registrasi Online', 'desc' => 'Isi formulir pendaftaran dan upload dokumen yang diperlukan'],
                    ['step' => '2', 'title' => 'Seleksi Administrasi', 'desc' => 'Tim HR akan melakukan verifikasi dokumen dan kelengkapan data'],
                    ['step' => '3', 'title' => 'Interview', 'desc' => 'Wawancara dengan tim HR dan calon mentor dari unit terkait'],
                    ['step' => '4', 'title' => 'Pengumuman', 'desc' => 'Pengumuman hasil seleksi via chat whatsapp dan dapat login ke sistem']
                ];

                foreach ($process as $p): ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm card-hover">
                            <div class="card-body d-flex align-items-start">
                                <div class="bg-danger gradient-red text-white rounded-circle d-flex align-items-center justify-content-center me-4" 
                                     style="width: 60px; height: 60px; min-width: 60px;">
                                    <h3 class="mb-0 fw-bold"><?= $p['step'] ?></h3>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2"><?= $p['title'] ?></h5>
                                    <p class="text-muted mb-0"><?= $p['desc'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="text-center">
                <p class="footer-text mb-4">
                    Telkom Indonesia | Witel Bekasi-Karawang<br>
                    Jalan Rawa Tembaga IV No.4, Marga Jaya, Bekasi Selatan, Kota Bekasi, Jawa Barat 17141, Indonesia
                </p>
                
                <div class="bg-secondary bg-opacity-25 rounded p-4 d-inline-block mb-4">
                    <p class="mb-0 footer-text">
                        <i class="fas fa-envelope me-2" style="color: #d32f2f; font-size: 18px;"></i>
                        tjslcwitelbekasi@gmail.com
                    </p>

                    <p class="mb-0 footer-text">
                        <i class="fab fa-instagram me-2" style="color: #d32f2f; font-size: 18px;"></i>
                        @telkombekasi
                    </p>
                </div>


                
                <hr class="border-secondary">
                
                <p class="footer-text mb-0">© 2025 Instep - Telkom Indonesia. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>