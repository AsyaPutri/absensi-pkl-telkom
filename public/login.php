<?php
session_start();
 // koneksi database MySQL

// Database configuration
$host = 'localhost';
$dbname = 'absensi';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Redirect jika sudah login
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    if ($_SESSION['role'] === 'magang') {
        header("Location: magang/dashboard.php");
    } elseif ($_SESSION['role'] === 'mentor') {
        header("Location: mentor/dashboard.php");
    } else {
        header("Location: admin/dashboard.php");
    }
    exit();
}

$error_message = '';
$success_message = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';
    $recaptcha = isset($_POST['recaptcha']);
    
    // Validasi input
    if (empty($email) || empty($password) || empty($role)) {
        $error_message = 'Mohon lengkapi semua field yang diperlukan';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format email tidak valid';
    } elseif (!$recaptcha) {
        $error_message = 'Mohon verifikasi reCAPTCHA';
    } else {
        try {
            // Query untuk mencari user berdasarkan email dan role
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = :role");
            $stmt->execute([
                ':email' => $email,
                ':role' => $role
            ]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Verifikasi password - cek apakah hash atau plain text
                $passwordMatch = false;
                
                // Jika password di database dalam format hash
                if (strlen($user['password']) >= 60 && substr($user['password'], 0, 4) === '$2y$') {
                    $passwordMatch = password_verify($password, $user['password']);
                } else {
                    // Jika password plain text, bandingkan langsung
                    $passwordMatch = ($password === $user['password']);
                }
                
                if ($passwordMatch) {
                    // Set session
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = isset($user['name']) ? $user['name'] : $user['email'];
                    $_SESSION['login_time'] = date('Y-m-d H:i:s');
                    if ($_SESSION['role'] === 'peserta') {
                        include __DIR__ . '/send_email.php';
                    }                    
                    
                    // Update last login jika kolom ada
                    try {
                        $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
                        if ($checkColumn->rowCount() > 0) {
                            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                            $updateStmt->execute([':id' => $user['id']]);
                        }
                    } catch(PDOException $e) {
                        // Ignore jika kolom last_login tidak ada
                    }
                    
                    // Redirect berdasarkan role
                    if ($role === 'magang') {
                        header("Location: magang/dashboard.php");
                    } elseif ($role === 'mentor') {
                        header("Location: mentor/dashboard.php");
                    } else {
                        header("Location: admin/dashboard.php");
                    }
                    exit();
                } else {
                    $error_message = 'Password salah';
                }
            } else {
                $error_message = 'Email tidak ditemukan atau role tidak sesuai';
            }
        } catch(PDOException $e) {
            $error_message = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Telkom Indonesia</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="bg-decoration decoration-1"></div>
    <div class="bg-decoration decoration-2"></div>
    <div class="bg-decoration decoration-3"></div>
    
    <div class="main-container">
        <div class="left-panel">
            <div class="logo-section">
                <div class="bumn-logo">
                    <img src="assets/img/akhlak-removebg.png" width="120%" alt="Logo AKHLAK">
                </div>
            </div>
            
            <div class="security-awareness">
                <div class="security-title">WORKPLACE WARENESS</div>
                <div class="security-subtitle">Jaga sikapmu,<strong>tetap profesional</strong> dengan mengikuti tips praktis dibawah ini:</div>
                
                <ul class="security-list">
                    <li class="security-item">
                        <div class="security-number">1</div>
                        <div>Datang <strong>tepat waktu sesuai jadwal</strong> yang telah ditentukan.</div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">2</div>
                        <div>Gunakan <strong>pakaian yang rapi dan sopan</strong> sesuai ketentuan perusahaan.</div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">3</div>
                        <div>Jaga <strong>kebersihan dan kerapihan</strong> di lingkungan kerja.</div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">4</div>
                        <div>Bersikaplah <strong>ramah, sopan, dan saling menghargai</strong> rekan kerja maupun atasan.</div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">5</div>
                        <div>Gunakan <strong>fasilitas kantor dengan bijak</strong> dan sesuai peruntukannya.</div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">6</div>
                         <div>Hindari <strong>penggunaan ponsel untuk kepentingan pribadi</strong> selama jam kerja.</div>
                    </li>
                     <li class="security-item">
                        <div class="security-number">7</div>
                        <div><strong>Laporkan ketidakhadiran atau keterlambatan</strong> kepada atasan dengan segera.</div>
                    </li>
                </ul>
                
                <div class="support-info">
                    <div class="support-title">Support dan Dukungan:</div>
                    <div class="support-title">TELKOM WITEL BEKASI - KARAWANG</div>
                    <div> PT Telkom Indonesia</div>
                    <div style="margin-top: 10px;">
                        <div class="contact-item">
                            <i class="fa-solid fa-envelope" style="color: #d32f2f; font-size: 18px; margin-right: 8px;"></i>
                            <span>tjslcwitelbekasi@gmail.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fa-brands fa-instagram" style="color: #d32f2f; font-size: 18px; margin-right: 8px;"></i>
                            <span>@telkombekasi</span>
                        </div>
                        <div class="contact-item">
                             <i class="fa-solid fa-location-dot" style="color: #d32f2f; font-size: 18px; margin-right: 8px;"></i>
                            <span>Jl. Rw. Tembaga IV No.4, RT.006/RW.005, Marga Jaya, Kec. Bekasi Selatan, Kota Bks, Jawa Barat 17141</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="login-header">
                <div class="login-title">
                    <img src="assets/img/telkom-removebg.png" width="200" alt="Logo Telkom">
                </div>
                <div class="login-subtitle">Sistem Absensi Internship</div>
            </div>
            
            <form class="login-form" method="POST" action="">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="Masukkan email anda" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="••••••••" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="role">Masuk Sebagai</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="magang" <?php echo (($_POST['role'] ?? '') === 'magang') ? 'selected' : ''; ?>>Magang</option>
                        <option value="mentor" <?php echo (($_POST['role'] ?? '') === 'mentor') ? 'selected' : ''; ?>>Mentor</option>
                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="showPassword">
                    <label for="showPassword">Show Password</label>
                </div>
                
                <div class="recaptcha-container">
                    <div class="recaptcha-mock" id="recaptcha-mock">
                        <div class="recaptcha-checkbox" id="recaptcha-checkbox"></div>
                        <span>I'm not a robot</span>
                        <div style="margin-left: 20px; font-size: 10px; color: #999;">reCAPTCHA<br>Privacy - Terms</div>
                    </div>
                    <input type="hidden" name="recaptcha" id="recaptcha-input" value="">
                </div>
                
                <button type="submit" class="login-button" id="login-button" disabled>LOGIN</button>

                <!-- Link Register -->
<div style="margin-top: 15px; text-align: center;">
   <a href="register.php" style="text-decoration: none;">
    <span style="color: #000;">Ingin bergabung dalam program Internship kami?</span>
    <strong style="color: #d32f2f;"> Daftar sekarang!</strong>
    </a>
</div>
                
                <div class="powered-by">
                    Powered by Telkom Indonesia, Witel Bekasi - Karawang
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html>