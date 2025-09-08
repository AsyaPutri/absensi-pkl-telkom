<?php
include "../../includes/auth.php";
checkRole('magang');

// Get user data from session (auth.php already started the session)
$userName = isset($_SESSION['nama']) ? $_SESSION['nama'] : "Guest";
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// Check if user has already checked in today
function hasCheckedInToday($userId) {
    // You should implement this function to check database
    // For now, we'll use session or you can modify to check from database
    $today = date('Y-m-d');
    return isset($_SESSION['check_in_' . $today]) ? $_SESSION['check_in_' . $today] : false;
}

// Check if user has already checked out today
function hasCheckedOutToday($userId) {
    // You should implement this function to check database
    // For now, we'll use session or you can modify to check from database
    $today = date('Y-m-d');
    return isset($_SESSION['check_out_' . $today]) ? $_SESSION['check_out_' . $today] : false;
}

$hasCheckedIn = hasCheckedInToday($userId);
$hasCheckedOut = hasCheckedOutToday($userId);

// Determine current status and button text
if (!$hasCheckedIn) {
    $currentStatus = 'not_checked_in';
    $buttonText = 'Click to Check In';
    $buttonIcon = 'bi-box-arrow-in-right';
    $statusMessage = '';
} elseif ($hasCheckedIn && !$hasCheckedOut) {
    $currentStatus = 'checked_in';
    $buttonText = 'Click to Check Out';
    $buttonIcon = 'bi-box-arrow-right';
    $statusMessage = 'Anda sudah check in, jangan lupa check out';
} else {
    $currentStatus = 'completed';
    $buttonText = 'Absensi Selesai';
    $buttonIcon = 'bi-check-circle';
    $statusMessage = 'Absensi hari ini sudah selesai';
}

// Extended user data for profile (you can fetch this from database)
$userProfile = [
    'nama' => $userName,
    'unit_kerja' => isset($_SESSION['unit_kerja']) ? $_SESSION['unit_kerja'] : 'IT Development',
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Magang - Telkom Indonesia</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Font Awesome untuk icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    
    <!-- Leaflet CSS untuk Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin=""/>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/attendance.css">
    
    <style>
        /* Custom styles untuk maps dan dashboard */
        
        /* Map Container */
        .map-container {
            position: relative;
            background: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-height: 400px;
        }
        
        /* Map Element */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            z-index: 1;
        }
        
        /* Location Info Panel */
        #location-info {
            margin-bottom: 1rem;
        }
        
        #location-info .alert {
            margin-bottom: 0;
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        #location-info .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }
        
        #location-info .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }
        
        #location-info .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }
        
        #location-info .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }
        
        /* Office Name Animation */
        #office-name {
            color: #e60012;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        /* Location status styles */
        .location-section .option-card.location-option {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .location-section .option-card.location-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .location-section .option-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        /* Button States */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-check-in {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-check-in:hover:not(:disabled) {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.3);
        }
        
        .btn-check-out {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-check-out:hover:not(:disabled) {
            background: linear-gradient(135deg, #c82333, #e8650e);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.3);
        }
        
        .btn-completed {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            color: white;
            font-weight: 600;
        }
        
        .btn-secondary:disabled {
            background: #6c757d;
            border-color: #6c757d;
        }
        
        /* Info Cards */
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        .info-card h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .info-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .info-value {
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        /* Status indicators */
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-success { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-danger { background-color: #dc3545; }
        .status-info { background-color: #17a2b8; }
        
        /* Loading animations */
        .loading-pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Welcome banner improvements */
        .welcome-banner {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #dee2e6;
        }
        
        /* Card improvements */
        .card-custom {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 12px;
        }
        
        .card-custom .card-body {
            padding: 1.5rem;
        }
        
        /* Form improvements */
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        /* Option cards improvements */
        .option-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }
        
        .option-card:hover {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .option-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .option-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .option-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            #map {
                height: 300px;
            }
            
            .map-container {
                min-height: 300px;
            }
            
            .card-custom .card-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-telkom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="telkom-logo">
                        <img src="../assets/img/telkom-removebg.png" width="80" height="80">
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-0 text-telkom">
                        Absensi Magang - Telkom Indonesia
                    </h4>
                </div>
                <div class="col-auto">
                    <div class="d-flex align-items-center user-info">
                        <i class="bi bi-person-circle me-2"></i>
                        <span><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Profile Header Section -->
    <div class="profile-header">
        <div class="container">
            <div class="profile-simple">
                <div class="profile-info-simple">
                    <div class="profile-name-simple">
                        <?php echo htmlspecialchars($userProfile['nama']); ?>
                    </div>
                    <div class="profile-unit-simple">
                        <i class="bi bi-briefcase me-1"></i>
                        <?php echo htmlspecialchars($userProfile['unit_kerja']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Banner -->
    <?php if($hasCheckedIn): ?>
    <div class="container">
        <div class="status-banner <?php echo $currentStatus === 'checked_in' ? 'checked-in' : ($currentStatus === 'completed' ? 'completed' : ''); ?>">
            <div class="d-flex align-items-center">
                <i class="bi <?php echo $buttonIcon; ?> me-2" style="font-size: 1.2rem;"></i>
                <strong><?php echo $statusMessage; ?></strong>
            </div>
            <small class="text-muted d-block mt-1">
                Check in: <?php echo date('H:i:s'); ?> | Lokasi: <span id="statusLocation">Office</span>
            </small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Welcome Banner -->
    <div class="container">
        <div class="welcome-banner text-center">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <i class="bi bi-clock me-2"></i>
                    Current Time: <span id="currentTime" class="fw-bold"><?php echo date('n/j/Y, g:i:s A'); ?></span>
                </div>
                <div class="col-md-6">
                    <i class="bi bi-geo-alt me-2"></i>
                    Location Status: <span id="locationStatus" class="fw-bold loading-pulse">Checking...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row g-4">
            <!-- Left Panel - Form Section -->
            <div class="col-lg-8">
                <div class="card card-custom">
                    <div class="card-body">
                        <!-- Map Section -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-map me-2"></i>
                                Peta Absensi - <span id="office-name" class="loading-pulse">Loading...</span>
                                <small class="text-muted fw-normal d-block mt-1">GPS akan mendeteksi lokasi secara otomatis</small>
                            </h5>
                            
                            <!-- Location Info Panel -->
                            <div id="location-info" class="mb-3">
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        <span>Memuat data kantor dan mencari lokasi GPS...</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Map Container -->
                            <div class="map-container">
                                <div id="map"></div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-clock-history me-1"></i>
                                    Shift
                                </label>
                                <div class="form-control bg-light text-muted" style="min-height: auto;">
                                    09:00 - 16:00
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="activity" class="form-label">
                                    <i class="bi bi-list-task me-1"></i>
                                    Aktivitas <?php echo $currentStatus === 'not_checked_in' ? 'Hari Ini' : 'yang Dilakukan'; ?>
                                    <?php if($currentStatus === 'checked_in'): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>
                                <textarea class="form-control" id="activity" rows="3" 
                                    placeholder="<?php 
                                        if($currentStatus === 'not_checked_in') {
                                            echo 'Aktivitas akan diisi saat checkout (opsional untuk checkin)';
                                        } else if($currentStatus === 'checked_in') {
                                            echo 'Tuliskan aktivitas yang sudah dilakukan hari ini...';
                                        } else {
                                            echo 'Aktivitas yang sudah dilakukan';
                                        }
                                    ?>" 
                                    <?php echo $currentStatus === 'completed' ? 'disabled' : ($currentStatus === 'checked_in' ? 'required' : ''); ?>></textarea>
                                <?php if($currentStatus === 'not_checked_in'): ?>
                                    <small class="text-muted">Aktivitas tidak wajib diisi untuk check in</small>
                                <?php elseif($currentStatus === 'checked_in'): ?>
                                    <small class="text-danger">Wajib diisi untuk check out</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Kendala Section -->
                        <div class="mb-4">
                            <label for="kendala" class="form-label">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Kendala / Hambatan (Opsional)
                                <?php if($currentStatus === 'checked_in'): ?>
                                    <span class="text-warning"> - Untuk Checkout</span>
                                <?php endif; ?>
                            </label>
                            <textarea class="form-control" id="kendala" rows="3" 
                                placeholder="<?php 
                                    if($currentStatus === 'not_checked_in') {
                                        echo 'Kendala akan diisi saat checkout (opsional untuk checkin)';
                                    } else if($currentStatus === 'checked_in') {
                                        echo 'Tuliskan kendala atau hambatan yang dialami hari ini (jika ada)...';
                                    } else {
                                        echo 'Kendala yang dialami';
                                    }
                                ?>"
                                <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>></textarea>
                            <?php if($currentStatus === 'not_checked_in'): ?>
                                <small class="text-muted">Kendala tidak perlu diisi untuk check in</small>
                            <?php elseif($currentStatus === 'checked_in'): ?>
                                <small class="text-info">Opsional - isi jika ada kendala yang dialami</small>
                            <?php endif; ?>
                        </div>

                        <!-- Health Condition -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="bi bi-heart-pulse me-2"></i>
                                Bagaimana kondisi anda hari ini?
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="option-card text-center <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>" 
                                         data-type="condition" data-value="sakit">
                                        <span class="option-icon">🤒</span>
                                        <h6 class="mb-0">Sakit</h6>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="option-card text-center <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>" 
                                         data-type="condition" data-value="kurang-fit">
                                        <span class="option-icon">😷</span>
                                        <h6 class="mb-0">Kurang Fit</h6>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="option-card text-center selected <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>" 
                                         data-type="condition" data-value="sehat">
                                        <span class="option-icon">😊</span>
                                        <h6 class="mb-0">Sehat</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Work Location -->
                        <div class="mb-4 location-section">
                            <h6 class="mb-3">
                                <i class="bi bi-geo-alt me-2"></i>
                                Lokasi Kerja
                                <small class="text-muted fw-normal">(Otomatis dipilih berdasarkan lokasi GPS)</small>
                            </h6>
                            <div class="row" id="locationOptions">
                                <!-- Office option -->
                                <div class="col-md-6" id="officeOption">
                                    <div class="option-card text-center location-option selected <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>" 
                                         data-type="location" data-value="office">
                                        <span class="option-icon">🏢</span>
                                        <h6 class="mb-0">Office</h6>
                                    </div>
                                </div>
                                <!-- WFH option -->
                                <div class="col-md-6 d-none" id="wfhOption">
                                    <div class="option-card text-center location-option <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>" 
                                         data-type="location" data-value="wfh">
                                        <span class="option-icon">🏠</span>
                                        <h6 class="mb-0">Work From Home</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Camera & Info -->
            <div class="col-lg-4">
                <!-- Camera Section -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="bi bi-camera me-2"></i>
                            Camera
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="camera-container mb-3">
                            <video id="video" class="d-none" autoplay></video>
                            <canvas id="canvas" class="d-none"></canvas>
                            <img id="photo" class="d-none" alt="Captured photo">
                            <div id="camera-placeholder" class="camera-placeholder">
                                <i class="bi bi-camera" style="font-size: 3rem; opacity: 0.7;"></i>
                                <p class="mt-2 mb-0">Klik "Start Camera"</p>
                                <small>untuk mengaktifkan kamera dan ambil foto</small>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-camera" id="startCamera" <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>>
                                <i class="bi bi-camera me-2"></i>
                                Start Camera
                            </button>
                            <button class="btn btn-camera d-none" id="capture">
                                <i class="bi bi-camera-fill me-2"></i>
                                Capture
                            </button>
                            <button class="btn btn-secondary d-none" id="retake">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Retake
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Location Info Card -->
                <div class="card card-custom mb-4">
                    <div class="card-body info-card">
                        <h6 class="card-title">
                            <i class="bi bi-info-circle me-2"></i>
                            Informasi Lokasi
                        </h6>
                        <div class="info-item">
                            <span class="info-label">Status GPS:</span>
                            <span id="gpsStatus" class="info-value text-warning">
                                <span class="status-indicator status-warning"></span>
                                Checking...
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Jarak ke Kantor:</span>
                            <span id="distanceToOffice" class="info-value">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Akurasi GPS:</span>
                            <span id="gpsAccuracy" class="info-value">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Koordinat:</span>
                            <span id="coordinates" class="info-value text-muted">-</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="#" class="manual-link d-flex align-items-center py-2" id="logbookLink">
                                <i class="bi bi-journal-text me-2"></i>
                                Logbook Aktivitas
                            </a>
                            <a href="#" class="manual-link d-flex align-items-center py-2" id="historyLink">
                                <i class="bi bi-clock-history me-2"></i>
                                Riwayat Absensi
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Submit & Logout Buttons -->
                <div class="d-grid gap-3 mb-4">
                    <button class="btn btn-lg <?php 
                        echo $currentStatus === 'not_checked_in' ? 'btn-check-in' : 
                            ($currentStatus === 'checked_in' ? 'btn-check-out' : 'btn-completed'); 
                    ?>" 
                    id="submitAbsen" 
                    data-status="<?php echo $currentStatus; ?>"
                    <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>>
                        <i class="bi <?php echo $buttonIcon; ?> me-2"></i>
                        <?php echo $buttonText; ?>
                    </button>
                    <button class="btn btn-logout btn-lg" id="logoutBtn">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div class="container">
        <div id="alertContainer"></div>
    </div>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="../assets/js/maps.js"></script>
    <script src="../assets/js/dashboard_pkl.js"></script>
    
    <script>
        // Global variables
        let locationUpdateInterval;
        let timeUpdateInterval;
        
        // Update current time every second
        function updateCurrentTime() {
            const now = new Date();
            const options = { 
                year: 'numeric', 
                month: 'numeric', 
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = now.toLocaleString('en-US', options);
            }
        }
        
                // Update location UI based on map data
        function updateLocationUI(locationData) {
            console.log('📊 Updating UI with location data:', locationData);
            
            // Update GPS status
            const gpsStatus = document.getElementById('gpsStatus');
            if (gpsStatus) {
                if (locationData.latitude && locationData.longitude) {
                    gpsStatus.innerHTML = `
                        <span class="status-indicator status-success"></span>
                        Active
                    `;
                    gpsStatus.className = 'info-value text-success';
                } else {
                    gpsStatus.innerHTML = `
                        <span class="status-indicator status-warning"></span>
                        Searching...
                    `;
                    gpsStatus.className = 'info-value text-warning';
                }
            }
            
            // Update distance
            const distanceElement = document.getElementById('distanceToOffice');
            if (distanceElement && locationData.distance !== null) {
                const distance = Math.round(locationData.distance);
                distanceElement.textContent = distance + 'm';
                distanceElement.className = locationData.isInRange ? 
                    'info-value text-success' : 'info-value text-warning';
            }
            
            // Update accuracy
            const accuracyElement = document.getElementById('gpsAccuracy');
            if (accuracyElement && locationData.accuracy) {
                accuracyElement.textContent = '±' + Math.round(locationData.accuracy) + 'm';
                accuracyElement.className = locationData.accuracy < 20 ? 
                    'info-value text-success' : 'info-value text-warning';
            }
            
            // Update coordinates
            const coordinatesElement = document.getElementById('coordinates');
            if (coordinatesElement && locationData.latitude && locationData.longitude) {
                coordinatesElement.textContent = 
                    `${locationData.latitude.toFixed(6)}, ${locationData.longitude.toFixed(6)}`;
                coordinatesElement.className = 'info-value text-muted';
            }
            
            // Update main location status
            const locationStatus = document.getElementById('locationStatus');
            if (locationStatus) {
                locationStatus.classList.remove('loading-pulse');
                
                if (locationData.isInRange) {
                    locationStatus.textContent = 'Dalam Area Kantor';
                    locationStatus.className = 'fw-bold text-success';
                } else if (locationData.distance !== null) {
                    locationStatus.textContent = 'Di Luar Area Kantor';
                    locationStatus.className = 'fw-bold text-warning';
                } else {
                    locationStatus.textContent = 'Mencari Lokasi...';
                    locationStatus.className = 'fw-bold text-info loading-pulse';
                }
            }
            
            // Update office name
            const officeName = document.getElementById('office-name');
            if (officeName && locationData.officeName) {
                officeName.textContent = locationData.officeName;
                officeName.classList.remove('loading-pulse');
            }
            
            // Update location options (Office vs WFH)
            updateLocationOptions(locationData.isInRange);
            
            // Update submit button state
            updateSubmitButton(locationData.isInRange);
        }
        
        // Update location options (Office/WFH)
        function updateLocationOptions(isInRange) {
            const officeOption = document.getElementById('officeOption');
            const wfhOption = document.getElementById('wfhOption');
            
            if (officeOption && wfhOption) {
                if (isInRange) {
                    // Show office option, hide WFH
                    officeOption.classList.remove('d-none');
                    wfhOption.classList.add('d-none');
                    
                    // Select office option
                    const officeCard = officeOption.querySelector('.option-card');
                    const wfhCard = wfhOption.querySelector('.option-card');
                    
                    if (officeCard) officeCard.classList.add('selected');
                    if (wfhCard) wfhCard.classList.remove('selected');
                } else {
                    // Show both options, select WFH
                    officeOption.classList.remove('d-none');
                    wfhOption.classList.remove('d-none');
                    
                    const officeCard = officeOption.querySelector('.option-card');
                    const wfhCard = wfhOption.querySelector('.option-card');
                    
                    if (officeCard) officeCard.classList.remove('selected');
                    if (wfhCard) wfhCard.classList.add('selected');
                }
            }
        }
        
        // Update submit button based on location
        function updateSubmitButton(isInRange) {
            const submitBtn = document.getElementById('submitAbsen');
            if (!submitBtn || submitBtn.disabled) return;
            
            const currentStatus = submitBtn.getAttribute('data-status');
            
            if (currentStatus !== 'completed') {
                // Remove all button classes
                submitBtn.classList.remove('btn-check-in', 'btn-check-out', 'btn-secondary');
                
                if (isInRange) {
                    // Enable button with appropriate style
                    submitBtn.disabled = false;
                    
                    if (currentStatus === 'not_checked_in') {
                        submitBtn.classList.add('btn-check-in');
                        submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Click to Check In';
                    } else if (currentStatus === 'checked_in') {
                        submitBtn.classList.add('btn-check-out');
                        submitBtn.innerHTML = '<i class="bi bi-box-arrow-right me-2"></i>Click to Check Out';
                    }
                } else {
                    // Disable button when out of range
                    submitBtn.disabled = true;
                    submitBtn.classList.add('btn-secondary');
                    submitBtn.innerHTML = '<i class="bi bi-ban me-2"></i>Di Luar Radius Kantor';
                }
            }
        }
        
        // Handle location error
        function handleLocationError(error) {
            console.error('❌ Location error:', error);
            
            // Update GPS status
            const gpsStatus = document.getElementById('gpsStatus');
            if (gpsStatus) {
                gpsStatus.innerHTML = `
                    <span class="status-indicator status-danger"></span>
                    Error
                `;
                gpsStatus.className = 'info-value text-danger';
            }
            
            // Update main location status
            const locationStatus = document.getElementById('locationStatus');
            if (locationStatus) {
                locationStatus.textContent = 'GPS Tidak Tersedia';
                locationStatus.className = 'fw-bold text-danger';
                locationStatus.classList.remove('loading-pulse');
            }
            
            // Show error in location info
            const locationInfo = document.getElementById('location-info');
            if (locationInfo) {
                let errorMessage = 'Terjadi kesalahan saat mengambil lokasi: ';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Izin lokasi ditolak. Silakan aktifkan GPS dan berikan izin lokasi.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Informasi lokasi tidak tersedia.';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'Permintaan lokasi timeout. Coba refresh halaman.';
                        break;
                    default:
                        errorMessage += 'Kesalahan tidak diketahui.';
                        break;
                }
                
                locationInfo.innerHTML = `
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Error GPS:</strong><br>
                                <small>${errorMessage}</small>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
        
        // Initialize dashboard
        function initializeDashboard() {
            console.log('🚀 Initializing dashboard...');
            
            // Start time updates
            updateCurrentTime();
            timeUpdateInterval = setInterval(updateCurrentTime, 1000);
            
            // Wait for map to be ready
            let mapCheckInterval = setInterval(() => {
                if (window.attendanceMap && window.attendanceMap.officeLocation) {
                    console.log('✅ AttendanceMap ready, starting location updates...');
                    clearInterval(mapCheckInterval);
                    
                    // Start location updates
                    locationUpdateInterval = setInterval(() => {
                        if (window.attendanceMap) {
                            const locationData = window.attendanceMap.getCurrentLocationForAttendance();
                            if (locationData) {
                                updateLocationUI(locationData);
                            }
                        }
                    }, 1000);
                    
                } else {
                    console.log('⏳ Waiting for AttendanceMap...');
                }
            }, 500);
            
            // Clear interval after 30 seconds if map doesn't load
            setTimeout(() => {
                clearInterval(mapCheckInterval);
                if (!window.attendanceMap) {
                    console.error('❌ AttendanceMap failed to load within 30 seconds');
                    handleLocationError({ code: 0, message: 'Map initialization timeout' });
                }
            }, 30000);
        }
        
        // Option card selection handlers
        function initializeOptionCards() {
            // Health condition selection
            document.querySelectorAll('[data-type="condition"]').forEach(card => {
                card.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;
                    
                    // Remove selected from all condition cards
                    document.querySelectorAll('[data-type="condition"]').forEach(c => 
                        c.classList.remove('selected'));
                    
                    // Add selected to clicked card
                    this.classList.add('selected');
                });
            });
            
            // Location selection (if manual selection is needed)
            document.querySelectorAll('[data-type="location"]').forEach(card => {
                card.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;
                    
                    // Remove selected from all location cards
                    document.querySelectorAll('[data-type="location"]').forEach(c => 
                        c.classList.remove('selected'));
                    
                    // Add selected to clicked card
                    this.classList.add('selected');
                });
            });
        }
        
        // Form validation
        function validateForm() {
            const currentStatus = document.getElementById('submitAbsen').getAttribute('data-status');
            
            if (currentStatus === 'checked_in') {
                const activity = document.getElementById('activity').value.trim();
                if (!activity) {
                    showAlert('Aktivitas harus diisi untuk check out!', 'warning');
                    return false;
                }
            }
            
            return true;
        }
        
        // Show alert function
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            if (!alertContainer) return;
            
            const alertId = 'alert-' + Date.now();
            const alertHTML = `
                <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 
                        type === 'warning' ? 'exclamation-triangle' : 
                        type === 'danger' ? 'x-circle' : 'info-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            alertContainer.insertAdjacentHTML('beforeend', alertHTML);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 DOM loaded, initializing dashboard...');
            
            // Initialize dashboard
            initializeDashboard();
            
            // Initialize option cards
            initializeOptionCards();
            
            // Submit button handler
            const submitBtn = document.getElementById('submitAbsen');
            if (submitBtn) {
                submitBtn.addEventListener('click', function() {
                    if (this.disabled) return;
                    
                    if (validateForm()) {
                        // Here you would normally submit the form
                        console.log('📝 Form submitted');
                        showAlert('Absensi berhasil disimpan!', 'success');
                    }
                });
            }
            
            // Logout button handler
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function() {
                    if (confirm('Apakah Anda yakin ingin logout?')) {
                        window.location.href = '../logout.php';
                    }
                });
            }
            
           // Quick action handlers - Direct navigation to PHP files
document.getElementById('logbookLink')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    // Show loading indicator
    this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Membuka Logbook...';
    this.style.opacity = '0.7';
    this.style.pointerEvents = 'none';
    
    // Redirect to logbook.php
    setTimeout(() => {
        window.location.href = 'logbook.php';
    }, 500);
});

document.getElementById('historyLink')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    // Show loading indicator
    this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Membuka Riwayat...';
    this.style.opacity = '0.7';
    this.style.pointerEvents = 'none';
    
    // Redirect to riwayat_absen.php
    setTimeout(() => {
        window.location.href = 'riwayat_absensi.php';
    }, 500);
});

        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (locationUpdateInterval) {
                clearInterval(locationUpdateInterval);
            }
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
            }
            
            if (window.attendanceMap && typeof window.attendanceMap.destroy === 'function') {
                window.attendanceMap.destroy();
            }
        });
        
        // Handle visibility change (when user switches tabs)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden, pause updates
                if (locationUpdateInterval) {
                    clearInterval(locationUpdateInterval);
                }
            } else {
                // Page is visible, resume updates
                if (window.attendanceMap && !locationUpdateInterval) {
                    locationUpdateInterval = setInterval(() => {
                        const locationData = window.attendanceMap.getCurrentLocationForAttendance();
                        if (locationData) {
                            updateLocationUI(locationData);
                        }
                    }, 1000);
                }
            }
        });
        
        console.log('✅ Dashboard script loaded successfully');
    </script>
</body>
</html>

