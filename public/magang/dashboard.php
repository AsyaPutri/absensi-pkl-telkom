<?php
include "../../includes/auth.php";
include '../../config/database.php';
checkRole('magang');

// Get user data from session (auth.php already started the session)
$userName = isset($_SESSION['nama']) ? $_SESSION['nama'] : null;
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
    'nama'       => $userName,
    'unit_kerja' => 'IT Development'
];

if ($userId) {
    $sql = "SELECT nama, unit FROM peserta_pkl WHERE user_id = '$userId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userProfile['nama'] = $row['nama'];
        $userProfile['unit_kerja'] = $row['unit'];
    }
}
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
    
    <!-- Leaflet CSS untuk Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin=""/>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/attendance.css">
    
    <style>
        /* Custom styles untuk Leaflet Maps Integration */
        .map-container {
            height: 400px !important;
            width: 100% !important;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }

        #map {
            height: 100% !important;
            width: 100% !important;
            border-radius: 6px;
        }
        
        /* Leaflet popup customization */
        .leaflet-popup-content {
            font-size: 14px;
            line-height: 1.4;
            margin: 8px 12px;
        }
        
        .leaflet-popup-content h6 {
            margin: 0 0 8px 0;
            font-weight: 600;
        }
        
        /* Distance info styles */
        .distance-info {
            font-size: 13px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-in-range {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-out-range {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Location info alert */
        #location-info {
            margin-top: 15px;
            border-radius: 8px;
        }
        
        /* Button styles */
        .location-buttons {
            margin-top: 15px;
            gap: 10px;
        }
        
        .btn-location {
            border-radius: 6px;
            font-weight: 500;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Location option cards */
        .location-section .option-card.location-option {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .location-section .option-card.location-option:hover:not(.disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .location-section .option-card.location-option.selected {
            border-color: #007bff;
            background-color: rgba(0, 123, 255, 0.1);
        }
        
        .location-section .option-card.location-option.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Info cards styling */
        .info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-size: 14px;
            color: #6c757d;
        }
        
        .info-value {
            font-weight: 600;
            font-size: 14px;
        }
        
        .info-value.success { color: #28a745; }
        .info-value.warning { color: #ffc107; }
        .info-value.danger { color: #dc3545; }
        .info-value.info { color: #17a2b8; }
        
        /* Map controls styling */
        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .map-control-btn {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .map-control-btn:hover {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                <!-- <div class="col-auto">
                    <div class="d-flex align-items-center user-info">
                        <i class="bi bi-person-circle me-2"></i>
                        <span><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div> -->
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
    <div class="welcome-banner text-center">
        <div class="container">
            <i class="bi bi-clock me-2"></i>
                Current Time: <span id="currentTime"></span><br>
            <i class="bi bi-geo-alt me-2"></i>
                Location Status: <span id="locationStatus" class="fw-bold">Checking...</span>
        </div>
    </div>


    <!-- Main Content -->
    <div class="container my-4">
        <div class="row g-4">
            <!-- Left Panel - Map & Form Section -->
            <div class="col-lg-8">
                <div class="card card-custom">
                    <div class="card-body">
                        <!-- Map Section -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-map me-2"></i>
                                Peta Lokasi Absensi
                                <small class="text-muted fw-normal">(GPS akan mendeteksi lokasi secara otomatis)</small>
                            </h5>
                            
                            <!-- Leaflet Map Container -->
                            <div class="map-container">
                                <div id="map"></div>
                                <!-- Map controls will be added by maps.js -->
                            </div>

                            <!-- Location Info Alert - Will be updated by maps.js -->
                            <div id="mapStatus" class="alert alert-info">
                                <div class="d-flex align-items-center">
                                    <div class="loading-spinner me-2"></div>
                                    <strong>📍 Status:</strong> Memuat data lokasi kantor dan mendeteksi posisi Anda...
                                </div>
                            </div>

                            <!-- Location Action Buttons -->
                            <div class="d-flex flex-wrap location-buttons">
                                <button type="button" class="btn btn-primary btn-location me-2 mb-2" onclick="refreshUserLocation()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Refresh Lokasi
                                </button>
                                <button type="button" class="btn btn-info btn-location me-2 mb-2" onclick="centerToOffice()">
                                    <i class="bi bi-building me-1"></i>
                                    Ke Kantor
                                </button>
                                <button type="button" class="btn btn-success btn-location mb-2" onclick="checkLocation()">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Cek Jarak
                                </button>
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
                                        <small class="text-muted">Dalam radius kantor</small>
                                    </div>
                                </div>
                                <!-- WFH option -->
                                <div class="col-md-6 d-none" id="wfhOption">
                                    <div class="option-card text-center location-option <?php echo $currentStatus === 'completed' ? 'disabled' : ''; ?>" 
                                         data-type="location" data-value="wfh">
                                        <span class="option-icon">🏠</span>
                                        <h6 class="mb-0">Work From Home</h6>
                                        <small class="text-muted">Di luar radius kantor</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Info & Camera -->
            <div class="col-lg-4">
                <!-- Location Info Card -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-geo-alt me-2"></i>
                            Informasi Lokasi
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- Distance Info Detail - Will be populated by maps.js -->
                        <div id="distanceInfo" class="mt-3">
                            <!-- Distance details will be shown here -->
                        </div>
                    </div>
                </div>

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
            <video id="video" class="d-none" autoplay playsinline></video>
            <canvas id="canvas" class="d-none"></canvas>
            <img id="photo" class="d-none img-fluid rounded" alt="Captured photo">
            <div id="camera-placeholder" class="camera-placeholder text-center">
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

                <!-- Logbook & History -->
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="logbook.php" class="manual-link d-flex align-items-center py-2" id="logbookLink">
                                <i class="bi bi-journal-text me-2"></i>
                                Logbook Aktivitas
                            </a>
                            <a href="riwayat_absensi.php" class="manual-link d-flex align-items-center py-2" id="historyLink">
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
                    <button class="btn btn-logout btn-lg" id="logoutBtn" onclick="window.location.href='../logout.php';">
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
    
    <!-- Custom JavaScript - Load your existing files -->
    <script src="../assets/js/maps.js"></script>
    <script src="../assets/js/dashboard_pkl.js"></script>

    <!--camera</body> -->
    <script src="../assets/js/camera.js"></script>
    
    <script>
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
            document.getElementById('currentTime').textContent = now.toLocaleString('en-US', options);
        }
        
        // Update time immediately and then every second
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);
        
        // Integration dengan maps.js yang sudah ada
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard loaded, waiting for maps.js integration...');
            
            // Listen untuk update dari maps.js (jika maps.js mengirim event)
            document.addEventListener('locationUpdated', function(event) {
                const { distance, isInRange, accuracy } = event.detail;
                
                console.log('📍 Location updated from maps.js:', { distance, isInRange, accuracy });
                
                // Update GPS status
                document.getElementById('gpsStatus').textContent = 'Active';
                document.getElementById('gpsStatus').className = 'info-value success';
                
                // Update distance
                document.getElementById('distanceToOffice').textContent = Math.round(distance) + 'm';
                document.getElementById('distanceToOffice').className = isInRange ? 'info-value success' : 'info-value warning';
                
                // Update accuracy
                document.getElementById('gpsAccuracy').textContent = Math.round(accuracy) + 'm';
                document.getElementById('gpsAccuracy').className = 'info-value info';
                
                // Update location valid status
                document.getElementById('locationValid').textContent = isInRange ? 'Dalam Area' : 'Di Luar Area';
                document.getElementById('locationValid').className = isInRange ? 'info-value success' : 'info-value warning';
                
                // Update main location status
                document.getElementById('locationStatus').textContent = isInRange ? 'Dalam Area Kantor' : 'Di Luar Area Kantor';
                document.getElementById('locationStatus').className = isInRange ? 'fw-bold text-success' : 'fw-bold text-warning';
                
                // Update location options
                updateLocationOptions(isInRange);
                
                // Update location info alert
                updateLocationInfoAlert(isInRange, distance);
            });
            
            // Listen untuk error GPS dari maps.js
            document.addEventListener('locationError', function(event) {
                console.warn('❌ Location error from maps.js:', event.detail);
                
                document.getElementById('gpsStatus').textContent = 'Error';
                document.getElementById('gpsStatus').className = 'info-value danger';
                document.getElementById('locationStatus').textContent = 'GPS Tidak Tersedia';
                document.getElementById('locationStatus').className = 'fw-bold text-danger';
                document.getElementById('locationValid').textContent = 'Unknown';
                document.getElementById('locationValid').className = 'info-value danger';
                
                // Show error in location info
                document.getElementById('location-info').innerHTML = `
                    <div class="alert alert-danger">
                                            <strong>❌ GPS Error:</strong> ${event.detail.message || 'Tidak dapat mengakses lokasi'}
                        <button class="btn btn-sm btn-outline-danger mt-2 w-100" onclick="refreshUserLocation()">
                            🔄 Coba Lagi
                        </button>
                    </div>
                `;
            });
            
            // Listen untuk office data loaded dari maps.js
            document.addEventListener('officeDataLoaded', function(event) {
                console.log('🏢 Office data loaded from maps.js:', event.detail);
                
                // Update location info
                const office = event.detail;
                document.getElementById('location-info').innerHTML = `
                    <div class="alert alert-success">
                        <strong>🏢 ${office.name}</strong><br>
                        <small>📍 ${office.address}<br>
                        📏 Radius absensi: ${office.radius}m</small>
                    </div>
                `;
            });
        });
        
        // Update location options based on GPS
        function updateLocationOptions(isInRange) {
            const officeOption = document.getElementById('officeOption');
            const wfhOption = document.getElementById('wfhOption');
            
            if (isInRange) {
                // Dalam area kantor - show office option, hide WFH
                officeOption.classList.remove('d-none');
                wfhOption.classList.add('d-none');
                
                // Auto select office option
                const officeCard = officeOption.querySelector('.option-card');
                officeCard.classList.add('selected');
                
                // Remove selection from WFH
                const wfhCard = wfhOption.querySelector('.option-card');
                wfhCard.classList.remove('selected');
                
            } else {
                // Di luar area kantor - show both options
                officeOption.classList.remove('d-none');
                wfhOption.classList.remove('d-none');
                
                // Auto select WFH option
                const wfhCard = wfhOption.querySelector('.option-card');
                wfhCard.classList.add('selected');
                
                // Remove selection from office
                const officeCard = officeOption.querySelector('.option-card');
                officeCard.classList.remove('selected');
            }
        }
        
        // Update location info alert
        function updateLocationInfoAlert(isInRange, distance) {
            const locationInfo = document.getElementById('location-info');
            
            if (isInRange) {
                locationInfo.innerHTML = `
                    <div class="alert alert-success">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <strong>✅ Dalam Area Kantor</strong><br>
                                <small>📏 Jarak: ${Math.round(distance)}m dari kantor</small>
                            </div>
                            <div class="text-end">
                                <i class="bi bi-check-circle-fill" style="font-size: 2rem; color: #28a745;"></i>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                locationInfo.innerHTML = `
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <strong>⚠️ Di Luar Area Kantor</strong><br>
                                <small>📏 Jarak: ${Math.round(distance)}m dari kantor<br>
                                💼 Anda dapat memilih Work From Home</small>
                            </div>
                            <div class="text-end">
                                <i class="bi bi-exclamation-triangle-fill" style="font-size: 2rem; color: #ffc107;"></i>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
        
        // Handle location option clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('.location-option') && !e.target.closest('.disabled')) {
                const clickedCard = e.target.closest('.location-option');
                const allLocationCards = document.querySelectorAll('.location-option');
                
                // Remove selected class from all cards
                allLocationCards.forEach(card => card.classList.remove('selected'));
                
                // Add selected class to clicked card
                clickedCard.classList.add('selected');
                
                console.log('📍 Location option selected:', clickedCard.dataset.value);
            }
        });
        
        // Global functions untuk dipanggil dari button atau maps.js
        window.refreshUserLocation = function() {
            console.log('🔄 Refreshing user location from dashboard...');
            // Panggil function dari maps.js jika tersedia
            if (typeof getUserLocation === 'function') {
                getUserLocation();
            } else if (typeof refreshLocation === 'function') {
                refreshLocation();
            } else {
                console.warn('⚠️ No refresh function found in maps.js');
            }
        };
        
        window.centerToOffice = function() {
            console.log('🏢 Centering to office from dashboard...');
            // Panggil function dari maps.js jika tersedia
            if (typeof centerMapToOffice === 'function') {
                centerMapToOffice();
            } else if (typeof centerToOffice === 'function') {
                centerToOffice();
            } else {
                console.warn('⚠️ No center to office function found in maps.js');
            }
        };
        
        window.checkLocation = function() {
            console.log('📏 Checking location from dashboard...');
            // Panggil function dari maps.js jika tersedia
            if (typeof checkUserLocation === 'function') {
                checkUserLocation();
            } else if (typeof calculateDistance === 'function') {
                calculateDistance();
            } else {
                console.warn('⚠️ No check location function found in maps.js');
                alert('⚠️ Fungsi cek lokasi tidak tersedia. Pastikan maps.js sudah dimuat dengan benar.');
            }
        };
        
        // Helper function untuk debugging
        window.debugMapsIntegration = function() {
            console.log('🔍 Debug Maps Integration:');
            console.log('- maps.js loaded:', typeof map !== 'undefined');
            console.log('- getUserLocation available:', typeof getUserLocation === 'function');
            console.log('- centerToOffice available:', typeof centerToOffice === 'function');
            console.log('- checkLocation available:', typeof checkLocation === 'function');
            console.log('- Current location status:', document.getElementById('locationStatus').textContent);
            console.log('- GPS status:', document.getElementById('gpsStatus').textContent);
        };
        
        // Auto debug setelah 3 detik untuk memastikan maps.js sudah load
        setTimeout(() => {
            if (typeof console !== 'undefined' && console.log) {
                debugMapsIntegration();
            }
        }, 3000);
    </script>

    <script>
// Enhanced integration dengan maps.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Dashboard loaded, integrating with maps...');
    
    // Tunggu maps.js load
    setTimeout(function() {
        // Cek apakah maps.js sudah load
        if (typeof map !== 'undefined') {
            console.log('✅ Maps.js detected, setting up integration...');
            
            // Override atau extend fungsi di maps.js
            setupMapsIntegration();
        } else {
            console.warn('⚠️ Maps.js not detected, retrying...');
            // Retry setelah 2 detik
            setTimeout(arguments.callee, 2000);
        }
    }, 1000);
});

function setupMapsIntegration() {
    // Monitor perubahan lokasi
    if (typeof userLocation !== 'undefined' && userLocation) {
        updateLocationInfo(userLocation);
    }
    
    // Monitor perubahan jarak
    if (typeof distanceToOffice !== 'undefined' && distanceToOffice) {
        updateDistanceInfo(distanceToOffice);
    }
    
    // Set interval untuk update berkala
    setInterval(function() {
        if (typeof userLocation !== 'undefined' && typeof officeLocation !== 'undefined') {
            // Hitung jarak terbaru
            const distance = calculateDistanceFromCoords(
                userLocation.lat, userLocation.lng,
                officeLocation.lat, officeLocation.lng
            );
            
            const isInRange = distance <= (officeLocation.radius || 100);
            const accuracy = userLocation.accuracy || 0;
            
            // Update UI
            updateLocationUI(distance, isInRange, accuracy);
        }
    }, 3000); // Update setiap 3 detik
}

function updateLocationUI(distance, isInRange, accuracy) {
    // Update Status GPS
    const gpsStatus = document.getElementById('gpsStatus');
    if (gpsStatus) {
        gpsStatus.textContent = 'Active';
        gpsStatus.className = 'info-value success';
    }
    
    // Update Jarak ke Kantor
    const distanceElement = document.getElementById('distanceToOffice');
    if (distanceElement) {
        distanceElement.textContent = Math.round(distance) + 'm';
        distanceElement.className = isInRange ? 'info-value success' : 'info-value warning';
    }
    
    // Update Akurasi GPS
    const accuracyElement = document.getElementById('gpsAccuracy');
    if (accuracyElement) {
        accuracyElement.textContent = Math.round(accuracy) + 'm';
        accuracyElement.className = 'info-value info';
    }
    
    // Update Lokasi Valid
    const locationValid = document.getElementById('locationValid');
    if (locationValid) {
        locationValid.textContent = isInRange ? 'Dalam Area' : 'Di Luar Area';
        locationValid.className = isInRange ? 'info-value success' : 'info-value warning';
    }
    
    // Update Location Status di header
    const locationStatus = document.getElementById('locationStatus');
    if (locationStatus) {
        locationStatus.textContent = isInRange ? 'Dalam Area Kantor' : 'Di Luar Area Kantor';
        locationStatus.className = isInRange ? 'fw-bold text-success' : 'fw-bold text-warning';
    }
    
    // Update location options
    updateLocationOptions(isInRange);
    
    // Update location info alert
    updateLocationInfoAlert(isInRange, distance);
    
    console.log('📍 UI Updated:', { distance: Math.round(distance), isInRange, accuracy: Math.round(accuracy) });
}

function calculateDistanceFromCoords(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = lat1 * Math.PI/180;
    const φ2 = lat2 * Math.PI/180;
    const Δφ = (lat2-lat1) * Math.PI/180;
    const Δλ = (lon2-lon1) * Math.PI/180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // Distance in meters
}

// Override button functions
window.refreshUserLocation = function() {
    console.log('🔄 Refreshing location...');
    
    // Update status
    document.getElementById('gpsStatus').textContent = 'Refreshing...';
    document.getElementById('gpsStatus').className = 'info-value warning';
    
    // Panggil function dari maps.js
    if (typeof getUserLocation === 'function') {
        getUserLocation();
    } else if (typeof getLocation === 'function') {
        getLocation();
    } else {
        // Fallback: get location manually
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log('📍 Location refreshed:', position.coords);
                    // Update maps.js variables if available
                    if (typeof userLocation !== 'undefined') {
                        userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        };
                    }
                },
                function(error) {
                    console.error('❌ Location refresh failed:', error);
                    document.getElementById('gpsStatus').textContent = 'Error';
                    document.getElementById('gpsStatus').className = 'info-value danger';
                }
            );
        }
    }
};

window.centerToOffice = function() {
    console.log('🏢 Centering to office...');
    if (typeof map !== 'undefined' && typeof officeLocation !== 'undefined') {
        map.setView([officeLocation.lat, officeLocation.lng], 18);
    }
};

window.checkLocation = function() {
    console.log('📏 Checking location...');
    if (typeof userLocation !== 'undefined' && typeof officeLocation !== 'undefined') {
        const distance = calculateDistanceFromCoords(
            userLocation.lat, userLocation.lng,
            officeLocation.lat, officeLocation.lng
        );
        
        const isInRange = distance <= (officeLocation.radius || 100);
        
        alert(`📍 Jarak Anda ke kantor: ${Math.round(distance)}m\n${isInRange ? '✅ Dalam area kantor' : '❌ Di luar area kantor'}`);
        
        // Update UI
        updateLocationUI(distance, isInRange, userLocation.accuracy || 0);
    } else {
        alert('⚠️ Data lokasi belum tersedia. Coba refresh lokasi terlebih dahulu.');
    }
};

// Force update setelah 5 detik
setTimeout(function() {
    console.log('🔄 Force updating location info...');
    
    // Cek apakah ada data dari maps.js
    if (typeof userLocation !== 'undefined' && typeof officeLocation !== 'undefined') {
        const distance = calculateDistanceFromCoords(
            userLocation.lat, userLocation.lng,
            officeLocation.lat, officeLocation.lng
        );
        
        const isInRange = distance <= (officeLocation.radius || 100);
        const accuracy = userLocation.accuracy || 0;
        
        updateLocationUI(distance, isInRange, accuracy);
    } else {
        // Jika belum ada data, coba ambil dari elemen yang ada
        const mapAlert = document.querySelector('.leaflet-control-container');
        if (mapAlert) {
            console.log('📍 Map detected, trying to extract location data...');
            
            // Coba parse dari text yang ada
            const alertText = document.body.innerText;
            const distanceMatch = alertText.match(/(\d+)m\)/);
            
            if (distanceMatch) {
                const distance = parseInt(distanceMatch[1]);
                const isInRange = distance <= 100; // Asumsi radius 100m
                
                updateLocationUI(distance, isInRange, 50); // Asumsi akurasi 50m
            }
        }
    }
}, 5000);
</script>
</body>
</html>

