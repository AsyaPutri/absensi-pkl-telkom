<?php
include "../../includes/auth.php";
include '../../config/database.php';
include '../send_email.php';
checkRole('magang');

// Get user data from session (auth.php already started the session)
$userName = isset($_SESSION['nama']) ? $_SESSION['nama'] : null;
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// DATABASE-BASED FUNCTIONS (Replacing session-based)
function hasCheckedInToday($userId) {
    global $conn;
    $today = date('Y-m-d');
    
    $sql = "SELECT jam_masuk FROM absen WHERE user_id = ? AND tanggal = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return !empty($row['jam_masuk']);
    }
    
    return false;
}

function hasCheckedOutToday($userId) {
    global $conn;
    $today = date('Y-m-d');
    
    $sql = "SELECT jam_keluar FROM absen WHERE user_id = ? AND tanggal = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return !empty($row['jam_keluar']);
    }
    
    return false;
}

function getTodayAttendanceData($userId) {
    global $conn;
    $today = date('Y-m-d');
    
    $sql = "SELECT * FROM absen WHERE user_id = ? AND tanggal = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function hasPendingCheckout($userId) {
    global $conn;
    $sql = "SELECT tanggal, jam_masuk FROM absen WHERE user_id = ? AND jam_masuk IS NOT NULL AND jam_keluar IS NULL ORDER BY tanggal DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $pendingDate = $row['tanggal'];
        $today = date('Y-m-d');
        
        if ($pendingDate !== $today) {
            return [
                'has_pending' => true,
                'date' => $pendingDate,
                'checkin_time' => $row['jam_masuk']
            ];
        }
    }
    
    return ['has_pending' => false];
}

// Check attendance status
$hasCheckedIn = hasCheckedInToday($userId);
$hasCheckedOut = hasCheckedOutToday($userId);
$attendanceData = getTodayAttendanceData($userId);
$pendingCheckout = hasPendingCheckout($userId);

// Determine current status and button text with time validation
$currentTime = date('H:i:s');
$checkinDeadline = '11:00:00';
$canCheckin = $currentTime <= $checkinDeadline;

if ($pendingCheckout['has_pending']) {
    $currentStatus = 'pending_checkout';
    $buttonText = 'Checkout Tertunda';
    $buttonIcon = 'bi-exclamation-triangle';
    $statusMessage = 'Anda belum checkout dari tanggal ' . date('d/m/Y', strtotime($pendingCheckout['date']));
} elseif (!$hasCheckedIn) {
    $currentStatus = 'not_checked_in';
    if ($canCheckin) {
        $buttonText = 'Click to Check In';
        $buttonIcon = 'bi-box-arrow-in-right';
        $statusMessage = '';
    } else {
        $buttonText = 'Checkin Time Expired';
        $buttonIcon = 'bi-clock';
        $statusMessage = 'Waktu checkin sudah berakhir (batas: 11:00 WIB)';
    }
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

// Extended user data for profile
$userProfile = [
    'nama'       => $userName,
    'unit_kerja' => 'IT Development'
];

if ($userId) {
    $sql = "
        SELECT p.nama, u.nama_unit 
        FROM peserta_pkl p
        JOIN unit_pkl u ON p.unit_id = u.id
        WHERE p.user_id = ? 
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error . " | SQL: " . $sql);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userProfile['nama'] = $row['nama'];
        $userProfile['unit_kerja'] = $row['nama_unit']; // ✅ sudah pakai nama unit
    }
}


//  pengumuman aktif 
$stmt = $conn->prepare("SELECT * FROM pengumuman WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$pengumuman = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Internship - InStep</title>
    
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
        .text-telkom {
            color: #e60012 !important; 
            font-weight: 700;           
            letter-spacing: 0.5px;
        }

        .title-telkom {
            text-transform: uppercase;
            font-size: 1.4rem;
            text-shadow: 1px 1px 3px rgba(230, 0, 18, 0.15);
        }

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
        
        /* Status banner styles */
        .status-banner {
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: none;
        }

        .status-banner.checked-in {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .status-banner.completed {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .status-banner.pending-checkout {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Button states */
        .btn-check-in {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-check-in:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .btn-check-out {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            border: none;
            color: #212529;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-check-out:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            color: #212529;
        }

        .btn-completed {
            background: linear-gradient(135deg, #6c757d, #495057);
            border: none;
            color: white;
            font-weight: 600;
            cursor: not-allowed;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            border: none;
            color: #212529;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-warning:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            color: #212529;
        }

        /* Form validation styles */
        .form-control.is-valid {
            border-color: #28a745;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        /* Option cards */
        .option-card {
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
            padding: 1rem;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: white;
        }

        .option-card:hover:not(.disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .option-card.selected {
            border-color: #007bff !important;
            background-color: #f8f9ff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.15) !important;
        }

        .option-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .option-card.disabled::after {
            content: '🔒';
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 16px;
        }

        /* Alert animations */
        .conditional-alert {
            animation: slideInDown 0.3s ease-out;
        }

        @keyframes slideInDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Info card in right panel */
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

    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-telkom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="telkom-logo">
                        <img src="../assets/img/instepterbaru.png" width="200" height="150">
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-0 text-telkom fw-bold title-telkom">
                        Absensi Internship - Telkom Indonesia
                    </h4>
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

    <!-- Status Banner (Will be controlled by JavaScript) -->
    <div class="container">
        <div class="status-banner" id="statusBanner">
            <!-- Content will be dynamically updated -->
        </div>
    </div>

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
                        <!-- Map Section - KEPT ORIGINAL -->
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
                                    Aktivitas
                                    <span class="text-danger" id="activityRequired" style="display:none;">*</span>
                                </label>
                                <textarea class="form-control" id="activity" rows="3" 
                                    placeholder="Aktivitas akan diisi berdasarkan status absensi..."></textarea>
                                <small class="form-text text-muted" id="activityHelp"></small>
                            </div>
                        </div>

                        <!-- Kendala Section -->
                        <div class="mb-4">
                            <label for="kendala" class="form-label">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Kendala / Hambatan (Opsional)
                            </label>
                            <textarea class="form-control" id="kendala" rows="3" 
                                placeholder="Kendala akan diisi berdasarkan status absensi..."></textarea>
                            <small class="form-text text-muted" id="kendalaHelp"></small>
                        </div>

                        <!-- Health Condition -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="bi bi-heart-pulse me-2"></i>
                                Bagaimana kondisi anda hari ini?
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="option-card text-center" 
                                         data-type="condition" data-value="sakit">
                                        <span class="option-icon">🤒</span>
                                        <h6 class="mb-0">Sakit</h6>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="option-card text-center" 
                                         data-type="condition" data-value="kurang-fit">
                                        <span class="option-icon">😷</span>
                                        <h6 class="mb-0">Kurang Fit</h6>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="option-card text-center selected" 
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
                                    <div class="option-card text-center location-option selected" 
                                         data-type="location" data-value="office">
                                        <span class="option-icon">🏢</span>
                                        <h6 class="mb-0">Office</h6>
                                    </div>
                                </div>
                                <!-- WFH option -->
                                <div class="col-md-6 d-none" id="wfhOption">
                                    <div class="option-card text-center location-option" 
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

            <!-- Right Panel - Info & Camera -->
            <div class="col-lg-4">
                <!-- Location Info Card - KEPT EXACTLY ORIGINAL -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-geo-alt me-2"></i>
                            Informasi Lokasi
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- Distance Info Detail - Will be populated by maps.js - ORIGINAL -->
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
                            <button class="btn btn-camera" id="startCamera">
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
                    <input type="hidden" id="photoData" name="photo">
                </div>

                <!-- Logbook & History -->
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="logbook.php" class="manual-link d-flex align-items-center py-2">
                                <i class="bi bi-journal-text me-2"></i>
                                Logbook Aktivitas
                            </a>
                            <a href="riwayat_absensi.php" class="manual-link d-flex align-items-center py-2">
                                <i class="bi bi-clock-history me-2"></i>
                                Riwayat Absensi
                            </a>
                            <a href="cetak_surat.php" class="manual-link d-flex align-items-center py-2">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Cetak Surat
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Submit & Logout Buttons -->
                <div class="d-grid gap-3 mb-4">
                    <button class="btn btn-lg <?php 
                        echo $currentStatus === 'not_checked_in' && $canCheckin ? 'btn-check-in' : 
                            ($currentStatus === 'not_checked_in' && !$canCheckin ? 'btn-secondary' :
                            ($currentStatus === 'checked_in' ? 'btn-check-out' : 
                            ($currentStatus === 'pending_checkout' ? 'btn-warning' : 'btn-completed'))); 
                    ?>" 
                    id="submitAbsen" 
                    data-status="<?php echo $currentStatus; ?>"
                    data-pending-date="<?php echo $pendingCheckout['has_pending'] ? $pendingCheckout['date'] : ''; ?>"
                    <?php echo ($currentStatus === 'completed' || ($currentStatus === 'not_checked_in' && !$canCheckin)) ? 'disabled' : ''; ?>>
                        <i class="bi <?php echo $buttonIcon; ?> me-2"></i>
                        <?php echo $buttonText; ?>
                    </button>
                    <button class="btn btn-logout btn-lg" onclick="window.location.href='../logout.php';">
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

<!-- Modal Pengumuman -->
<div class="modal fade" id="pengumumanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"> <!-- pakai modal-xl -->
    <div class="modal-content">
      
      <!-- Tombol close -->
      <div class="modal-header border-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Isi modal -->
      <div class="modal-body p-0">
        <img src="../../uploads/pengumuman/<?= $pengumuman['gambar'] ?>" 
             class="img-fluid rounded" alt="Pengumuman">
      </div>
    </div>
  </div>
</div>

    <!-- JavaScript Libraries -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Load existing map files FIRST - ORIGINAL -->
    <script src="../assets/js/maps.js"></script>
    <script src="../assets/js/dashboard_pkl.js"></script>
    <script src="../assets/js/camera.js"></script>
    <script src="../assets/js/attendance.js"></script>
    <script src="../assets/js/simple_attendance_integration.js"></script>
    
    <script>
        // Global variables from PHP
        window.initialStatus = '<?php echo $currentStatus; ?>';
        window.canCheckin = <?php echo $canCheckin ? 'true' : 'false'; ?>;
        window.pendingDate = '<?php echo $pendingCheckout['has_pending'] ? $pendingCheckout['date'] : ''; ?>';
        
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
                hour12: false
            };
            document.getElementById('currentTime').textContent = now.toLocaleString('id-ID', options);
        }
        
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);
        
        // Show alert function
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert_' + Date.now();
            
            const alertHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" id="${alertId}" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            alertContainer.insertAdjacentHTML('beforeend', alertHTML);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
        
        // ORIGINAL MAP INTEGRATION - COMPLETELY PRESERVED
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard loaded, waiting for maps.js integration...');
            
            // Listen untuk update dari maps.js (jika maps.js mengirim event) - ORIGINAL
            document.addEventListener('locationUpdated', function(event) {
                const { distance, isInRange, accuracy } = event.detail;
                
                console.log('📍 Location updated from maps.js:', { distance, isInRange, accuracy });
                
                // Update main location status - ONLY THIS IS KEPT
                const locationStatus = document.getElementById('locationStatus');
                if (locationStatus) {
                    locationStatus.textContent = isInRange ? 'Dalam Area Kantor' : 'Di Luar Area Kantor';
                    locationStatus.className = isInRange ? 'fw-bold text-success' : 'fw-bold text-warning';
                }
                
                // Update location options - ORIGINAL
                updateLocationOptions(isInRange);
                
                // Update location info alert - ORIGINAL
                updateLocationInfoAlert(isInRange, distance);
            });
            
            // Listen untuk error GPS dari maps.js - COMPLETELY ORIGINAL
            document.addEventListener('locationError', function(event) {
                console.warn('❌ Location error from maps.js:', event.detail);
                
                const locationStatus = document.getElementById('locationStatus');
                if (locationStatus) {
                    locationStatus.textContent = 'GPS Tidak Tersedia';
                    locationStatus.className = 'fw-bold text-danger';
                }
                
                // Show error in location info - ORIGINAL
                const mapStatus = document.getElementById('mapStatus');
                if (mapStatus) {
                    mapStatus.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>❌ GPS Error:</strong> ${event.detail.message || 'Tidak dapat mengakses lokasi'}
                            <button class="btn btn-sm btn-outline-danger mt-2 w-100" onclick="refreshUserLocation()">
                                🔄 Coba Lagi
                            </button>
                        </div>
                    `;
                }
            });
            
            // Listen untuk office data loaded dari maps.js - COMPLETELY ORIGINAL
            document.addEventListener('officeDataLoaded', function(event) {
                console.log('🏢 Office data loaded from maps.js:', event.detail);
                
                // Update location info - ORIGINAL
                const office = event.detail;
                const mapStatus = document.getElementById('mapStatus');
                if (mapStatus) {
                    mapStatus.innerHTML = `
                        <div class="alert alert-success">
                            <strong>🏢 ${office.name}</strong><br>
                            <small>📍 ${office.address}<br>
                            📏 Radius absensi: ${office.radius}m</small>
                        </div>
                    `;
                }
            });
        });
        
        // ORIGINAL MAP FUNCTIONS - COMPLETELY PRESERVED
        function updateLocationOptions(isInRange) {
            const officeOption = document.getElementById('officeOption');
            const wfhOption = document.getElementById('wfhOption');
            
            if (isInRange) {
                // Dalam area kantor - show office option, hide WFH
                officeOption.classList.remove('d-none');
                wfhOption.classList.add('d-none');
                
                // Auto select office option
                const officeCard = officeOption.querySelector('.option-card');
                const wfhCard = wfhOption.querySelector('.option-card');
                if (officeCard) {
                    officeCard.classList.add('selected');
                }
                if (wfhCard) {
                    wfhCard.classList.remove('selected');
                }
            } else {
                // Di luar area kantor - show both options
                officeOption.classList.remove('d-none');
                wfhOption.classList.remove('d-none');
                
                // Auto select WFH option
                const wfhCard = wfhOption.querySelector('.option-card');
                const officeCard = officeOption.querySelector('.option-card');
                if (wfhCard) {
                    wfhCard.classList.add('selected');
                }
                if (officeCard) {
                    officeCard.classList.remove('selected');
                }
            }
        }
        
        // ORIGINAL LOCATION INFO UPDATE - COMPLETELY PRESERVED
        function updateLocationInfoAlert(isInRange, distance) {
            const mapStatus = document.getElementById('mapStatus');
            
            if (isInRange) {
                mapStatus.innerHTML = `
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
                mapStatus.innerHTML = `
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
        
        // ORIGINAL GLOBAL FUNCTIONS - COMPLETELY PRESERVED
        window.refreshUserLocation = function() {
            console.log('🔄 Refreshing user location from dashboard...');
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
            if (typeof checkUserLocation === 'function') {
                checkUserLocation();
            } else if (typeof calculateDistance === 'function') {
                calculateDistance();
            } else {
                console.warn('⚠️ No check location function found in maps.js');
                alert('⚠️ Fungsi cek lokasi tidak tersedia. Pastikan maps.js sudah dimuat dengan benar.');
            }
        };
        
        // ORIGINAL ENHANCED INTEGRATION - COMPLETELY PRESERVED
        setTimeout(function() {
            if (typeof map !== 'undefined') {
                console.log('✅ Maps.js detected, setting up integration...');
                setupMapsIntegration();
            } else {
                console.warn('⚠️ Maps.js not detected, retrying...');
                setTimeout(arguments.callee, 2000);
            }
        }, 1000);

        function setupMapsIntegration() {
            if (typeof userLocation !== 'undefined' && userLocation) {
                updateLocationInfo(userLocation);
            }
            
            setInterval(function() {
                if (typeof userLocation !== 'undefined' && typeof officeLocation !== 'undefined') {
                    const distance = calculateDistanceFromCoords(
                        userLocation.lat, userLocation.lng,
                        officeLocation.lat, officeLocation.lng
                    );
                    
                    const isInRange = distance <= (officeLocation.radius || 100);
                    const accuracy = userLocation.accuracy || 0;
                    
                    // Only update location status in header and options - NO info panel changes
                    const locationStatus = document.getElementById('locationStatus');
                    if (locationStatus) {
                        locationStatus.textContent = isInRange ? 'Dalam Area Kantor' : 'Di Luar Area Kantor';
                        locationStatus.className = isInRange ? 'fw-bold text-success' : 'fw-bold text-warning';
                    }
                    
                    updateLocationOptions(isInRange);
                    updateLocationInfoAlert(isInRange, distance);
                    
                    console.log('📍 UI Updated:', { distance: Math.round(distance), isInRange, accuracy: Math.round(accuracy) });
                }
            }, 3000);
        }

        function calculateDistanceFromCoords(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const φ1 = lat1 * Math.PI/180;
            const φ2 = lat2 * Math.PI/180;
            const Δφ = (lat2-lat1) * Math.PI/180;
            const Δλ = (lon2-lon1) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            return R * c;
        }
    </script>
</body>
</html> 
<script>
    // Update UI lokasi
    function updateLocationUI(isInRange, distance) {
        // Update GPS status
        const gpsStatus = document.getElementById('gpsStatus');
        if (gpsStatus) {
            gpsStatus.textContent = isInRange ? 'Akurat' : 'Tidak Akurat';
            gpsStatus.className = isInRange ? 'info-value success' : 'info-value warning';
        }

        // Update Location Status di header
        const locationStatus = document.getElementById('locationStatus');
        if (locationStatus) {
            locationStatus.textContent = isInRange ? 'Dalam Area Kantor' : 'Di Luar Area Kantor';
            locationStatus.className = isInRange ? 'fw-bold text-success' : 'fw-bold text-warning';
        }

        // Update map status alert
        const mapStatus = document.getElementById('mapStatus');
        if (mapStatus) {
            if (isInRange) {
                mapStatus.className = 'alert alert-success';
                mapStatus.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Dalam Area Kantor</strong> - Jarak: ${Math.round(distance)}m
                    </div>
                `;
            } else {
                mapStatus.className = 'alert alert-warning';
                mapStatus.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Di Luar Area Kantor</strong> - Jarak: ${Math.round(distance)}m
                    </div>
                `;
            }
        }

        // Update location options
        updateLocationOptions(isInRange);
    }

    // GPS Error handling
    function handleLocationError(error) {
        const gpsStatus = document.getElementById('gpsStatus');
        const locationStatus = document.getElementById('locationStatus');
        const mapStatus = document.getElementById('mapStatus');

        if (gpsStatus) {
            gpsStatus.textContent = 'Error';
            gpsStatus.className = 'info-value danger';
        }

        if (locationStatus) {
            locationStatus.textContent = 'GPS Tidak Tersedia';
            locationStatus.className = 'fw-bold text-danger';
        }

        if (mapStatus) {
            mapStatus.className = 'alert alert-danger';
            mapStatus.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <strong>GPS Error:</strong> ${error.message || 'Tidak dapat mengakses lokasi'}
                </div>
            `;
        }
    }

    // Expose functions globally untuk maps.js integration
    window.updateLocationUI = updateLocationUI;
    window.updateLocationOptions = updateLocationOptions;
    window.handleLocationError = handleLocationError;
    window.showAlert = showAlert;
  </script>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if ($pengumuman): ?>
  const pengumumanModal = new bootstrap.Modal(document.getElementById('pengumumanModal'));
  pengumumanModal.show();
<?php endif; ?>
</script>

</body>
</html>