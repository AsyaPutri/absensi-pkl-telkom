<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instep - Internship Program Telkom Witel Bekasi-Karawang</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #fee2e2 0%, #ffffff 50%, #f3f4f6 100%);
        }
        
        .gradient-red {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .progress-bar-animated {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
    </style>
</head>
<body class="gradient-bg">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <div class="bg-gradient-to-br from-red-600 to-red-700 p-2 rounded me-2">
                    <i class="fas fa-briefcase text-white"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">Instep</h5>
                    <small class="text-muted">Telkom Witel Bekasi-Karawang</small>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#positions">Lowongan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#process">Proses</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-secondary ms-2" href="login.php">Login Peserta</a>
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
                    <span class="badge bg-danger bg-opacity-10 text-danger mb-3 p-3">
                        🎓 Internship Program 2025
                    </span>
                    <h1 class="display-3 fw-bold mb-4">
                        Bergabunglah dengan <span class="text-danger">Instep</span>
                    </h1>
                    <p class="lead text-muted mb-4">
                        Program magang profesional di Telkom Witel Bekasi-Karawang. Kembangkan skill, bangun network, 
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
                            <h3 class="fw-bold">9</h3>
                            <p class="text-muted mb-0">Unit Tersedia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center border-0 shadow-sm card-hover transition">
                        <div class="card-body">
                            <i class="fas fa-users text-danger fs-1 mb-3"></i>
                            <h3 class="fw-bold">24</h3>
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
                <h2 class="display-5 fw-bold mb-3">Lowongan Unit</h2>
                <p class="text-muted col-lg-6 mx-auto">
                    Pilih unit yang sesuai dengan minat dan keahlian Anda. Setiap unit menawarkan pengalaman berbeda dengan mentor profesional.
                </p>
            </div>

            <div class="row g-4">
                <?php
                $positions = [
                    [
                        'unit' => 'Witel Business Service',
                        'location' => 'Rw. Tembaga',
                        'quota' => 3,
                        'remaining' => 2,
                        'jobdesk' => [
                            'Membuat Nomor Induk Berusaha pelanggan',
                            'Menginput data pelanggan kedalam website Indibiz'
                        ],
                        'requirements' => ['Jurusan: Manajemen, Ilmu Komunikasi, Ilmu Komputer']
                    ],
                    [
                        'unit' => 'Payment Collection',
                        'location' => 'Rw. Tembaga',
                        'quota' => 2,
                        'remaining' => 1,
                        'jobdesk' => [
                            'Membuat dan mengirimkan tagihan kepada pelanggan',
                            'Menghubungi atau menagih pelanggan yang menunggak',
                            'Melakukan update status pembayaran tagihan'
                        ],
                        'requirements' => ['Jurusan: Akuntansi, Ilmu Komunikasi']
                    ],
                    [
                        'unit' => 'Head of Representative Office',
                        'location' => 'BKS, PKY, KLA, PG, KRJ, CKR, CBT',
                        'quota' => 5,
                        'remaining' => 3,
                        'jobdesk' => [
                            'Berkomunikasi dengan pelanggan',
                            'Memberikan informasi produk yang ditawarkan',
                            'Merekomendasikan produk kepada konsumen',
                            'Update data pelanggan melalui My CX',
                            'Melakukan customer visit'
                        ],
                        'requirements' => ['Jurusan: Ilmu Komunikasi, Manajemen']
                    ],
                    [
                        'unit' => 'Performance Risk & QOS',
                        'location' => 'Rw. Tembaga',
                        'quota' => 2,
                        'remaining' => 1,
                        'jobdesk' => [
                            'Mendata alamat dan akun social media pelanggan di Excel'
                        ],
                        'requirements' => ['Jurusan: Telekomunikasi']
                    ],
                    [
                        'unit' => 'TJSL & Human Capital',
                        'location' => 'Rw. Tembaga',
                        'quota' => 3,
                        'remaining' => 2,
                        'jobdesk' => [
                            'Melakukan kegiatan rekrutmen karyawan PKL baru',
                            'Membuat surat keluar & masuk Karyawan PKL',
                            'Pencatatan data administrasi karyawan PKL',
                            'Menghubungi pelaku binaan UMKM Telkom'
                        ],
                        'requirements' => ['Jurusan: Manajemen SDM, Psikologi, Ilmu Komunikasi']
                    ],
                    [
                        'unit' => 'Access Data Management',
                        'location' => 'Area Witel',
                        'quota' => 2,
                        'remaining' => 1,
                        'jobdesk' => [
                            'Melakukan validasi data pelanggan (TV, Internet, Telepon)',
                            'Mengisi dan membantu penginputan barcode'
                        ],
                        'requirements' => ['Jurusan: Telekomunikasi']
                    ],
                    [
                        'unit' => 'Network Area & IS Operation - IP Engineer',
                        'location' => 'STO Bekasi',
                        'quota' => 2,
                        'remaining' => 2,
                        'gender' => 'Perempuan',
                        'jobdesk' => [
                            'Melakukan Operasi Harian (OPHAR) ke STO-STO',
                            'Membersihkan perangkat metro dan server client',
                            'Mengganti filter udara di perangkat metro',
                            'Maintenance'
                        ],
                        'requirements' => ['Jurusan: Sistem Informasi, Teknik Elektronika, Telekomunikasi', 'Khusus Perempuan']
                    ],
                    [
                        'unit' => 'Network Area & IS Operation - Transport',
                        'location' => 'STO Bekasi',
                        'quota' => 3,
                        'remaining' => 2,
                        'gender' => 'Laki-laki',
                        'jobdesk' => [
                            'Membersihkan perangkat BackBone',
                            'Maintenance perangkat Backbone (DWDM)',
                            'Pengawalan Kabel FO',
                            'Pengukuran kabel Optik',
                            'Perbaikan kabel FO cut',
                            'Bertest Link DWDM untuk Client'
                        ],
                        'requirements' => ['Jurusan: Sistem Informasi, Teknik Elektronika, Telekomunikasi', 'Khusus Laki-laki']
                    ],
                    [
                        'unit' => 'Access Service Operation',
                        'location' => 'Area Witel',
                        'quota' => 2,
                        'remaining' => 1,
                        'jobdesk' => [
                            'Input orderan Telkomsel'
                        ],
                        'requirements' => ['Semua Jurusan']
                    ]
                ];

                foreach ($positions as $index => $position) {
                    $percentage = round(($position['remaining'] / $position['quota']) * 100);
                    $badgeClass = $position['remaining'] > 0 ? 'bg-success' : 'bg-danger';
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card border-0 shadow-sm h-100 card-hover transition">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="fw-bold mb-1"><?= $position['unit'] ?></h5>
                                        <small class="text-muted">📍 <?= $position['location'] ?></small>
                                        <?php if (isset($position['gender'])): ?>
                                            <br><span class="badge bg-info text-dark mt-1"><?= $position['gender'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= $position['remaining'] > 0 ? $position['remaining'] . ' slot' : 'Penuh' ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between text-muted small mb-2">
                                        <span>Kuota: <?= $position['quota'] ?> orang</span>
                                        <span><?= $percentage ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar progress-bar-animated" role="progressbar" 
                                             style="width: <?= $percentage ?>%" 
                                             aria-valuenow="<?= $percentage ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                                
                                <button class="btn btn-outline-danger w-100" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#unitModal<?= $index ?>">
                                    Lihat Detail
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for each position -->
                    <div class="modal fade" id="unitModal<?= $index ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h4 class="modal-title fw-bold"><?= $position['unit'] ?></h4>
                                        <p class="mb-0 text-muted">📍 <?= $position['location'] ?></p>
                                        <?php if (isset($position['gender'])): ?>
                                            <span class="badge bg-info text-dark">Khusus <?= $position['gender'] ?></span>
                                        <?php endif; ?>
                                        <span class="badge <?= $badgeClass ?> ms-2">
                                            <?= $position['remaining'] ?> dari <?= $position['quota'] ?> slot tersisa
                                        </span>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <h5 class="fw-bold mb-3">
                                        <i class="fas fa-file-alt text-danger me-2"></i>Job Description
                                    </h5>
                                    <ul class="list-unstyled">
                                        <?php foreach ($position['jobdesk'] as $job): ?>
                                            <li class="mb-2">
                                                <i class="fas fa-check-circle text-success me-2"></i><?= $job ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>

                                    <h5 class="fw-bold mb-3 mt-4">
                                        <i class="fas fa-users text-danger me-2"></i>Persyaratan
                                    </h5>
                                    <ul class="list-unstyled">
                                        <?php foreach ($position['requirements'] as $req): ?>
                                            <li class="mb-2">
                                                <i class="fas fa-check-circle text-primary me-2"></i><?= $req ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <a href="register.php" class="btn btn-danger gradient-red w-100">
                                        Daftar Posisi Ini <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
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
                <h2 class="display-5 fw-bold mb-3">Persyaratan Internship</h2>
                <p class="text-muted">Dokumen yang perlu disiapkan untuk mendaftar program Instep</p>
            </div>

            <div class="row g-4 mb-5">
                <?php
                $requirements = [
                    ['icon' => 'fa-image', 'title' => 'Pas Foto 3x4', 'desc' => '1 lembar pas foto formal ukuran 3x4'],
                    ['icon' => 'fa-file-alt', 'title' => 'Surat Permohonan', 'desc' => 'Surat permohonan resmi dari institusi pendidikan'],
                    ['icon' => 'fa-id-card', 'title' => 'Kartu Mahasiswa', 'desc' => 'Kartu Tanda Mahasiswa atau Kartu Pelajar yang masih berlaku'],
                    ['icon' => 'fa-stamp', 'title' => 'Materai 10.000', 'desc' => 'Materai 10.000 rupiah untuk pengesahan dokumen'],
                    ['icon' => 'fa-mobile-alt', 'title' => 'Nomor Telkomsel', 'desc' => 'Nomor HP Telkomsel aktif terhubung dengan WhatsApp'],
                    ['icon' => 'fa-laptop', 'title' => 'Laptop Pribadi', 'desc' => 'Membawa laptop pribadi untuk kegiatan magang']
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
                        <li class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Tanyakan kepada PIC yang bertugas terkait unit yang tersedia
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Registration Process -->
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Proses Pendaftaran</h2>
            </div>

            <div class="row g-4">
                <?php
                $process = [
                    ['step' => '1', 'title' => 'Registrasi Online', 'desc' => 'Isi formulir pendaftaran dan upload dokumen yang diperlukan'],
                    ['step' => '2', 'title' => 'Seleksi Administrasi', 'desc' => 'Tim HR akan melakukan verifikasi dokumen dan kelengkapan data'],
                    ['step' => '3', 'title' => 'Interview', 'desc' => 'Wawancara dengan tim HR dan calon mentor dari unit terkait'],
                    ['step' => '4', 'title' => 'Pengumuman', 'desc' => 'Pengumuman hasil seleksi via email dan dapat login ke sistem']
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
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <div class="bg-danger rounded p-2 me-2">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h4 class="mb-0 fw-bold">Instep</h4>
                </div>
                <p class="text-white-50 mb-4">
                    Telkom Witel Bekasi-Karawang<br>
                    Jl. Ahmad Yani No. 123, Bekasi, Jawa Barat
                </p>
                <div class="bg-secondary bg-opacity-25 rounded p-4 d-inline-block mb-4">
                    <p class="mb-2 text-white-50">📞 Informasi & Bantuan:</p>
                    <a href="https://wa.me/6285316144454" class="text-success fw-bold fs-5 text-decoration-none">
                        085316144454 (Orient)
                    </a>
                </div>
                <hr class="border-secondary">
                <p class="text-white-50 mb-0">© 2025 Instep - Telkom Indonesia. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
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