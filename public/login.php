<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Telkom Indonesia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 50%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Background decorative elements */
        .bg-decoration {
            position: absolute;
            opacity: 0.1;
        }
        
        .decoration-1 {
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: #ff6666;
        }
        
        .decoration-2 {
            bottom: -150px;
            left: -150px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: #cc0000;
        }
        
        .decoration-3 {
            top: 20%;
            left: 10%;
            width: 100px;
            height: 100px;
            background: #ffffff;
            transform: rotate(45deg);
        }
        
        .main-container {
            display: flex;
            max-width: 1200px;
            width: 90%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        
        .left-panel {
            flex: 1;
            padding: 40px;
            background: #f8f9fa;
            position: relative;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .bumn-logo {
            width: 100px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            text-align: center;
        }
        
        /* .telkom-logo {
            width: 100px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: white;
            text-align: center;
            font-weight: bold;
        } */
        
        .security-awareness {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .security-title {
            color: #e74c3c;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .security-subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .security-list {
            list-style: none;
        }
        
        .security-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .security-number {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .support-info {
            margin-top: 30px;
            font-size: 13px;
            color: #666;
        }
        
        .support-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .contact-icon {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            background: #666;
            border-radius: 2px;
        }
        
        .right-panel {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-title {
            width: 200px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
            text-align: center;
            font-weight: bold;
            margin: 0 auto 20px auto;
            border-radius: 8px;
        }
        
        .login-subtitle {
            color: black;
            font-size: 25px;
        }
        
        .login-form {
            max-width: 350px;
            margin: 0 auto;
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #e74c3c;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #e74c3c;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .checkbox-group label {
            font-size: 14px;
            color: #666;
        }
        
        .recaptcha-container {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .recaptcha-mock {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .recaptcha-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #ccc;
            border-radius: 3px;
        }
        
        .login-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .powered-by {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                width: 95%;
            }
            
            .left-panel, .right-panel {
                padding: 30px 20px;
            }
            
            .logo-section {
                justify-content: center;
            }
        } /* Responsif untuk layar kecil */
@media (max-width: 768px) {
    body {
        padding: 20px;
    }

    .main-container {
        flex-direction: column;
        width: 100%;
        border-radius: 10px;
    }

    .left-panel, .right-panel {
        padding: 20px;
    }

    .logo-section {
        justify-content: center;
        margin-bottom: 20px;
    }

    .bumn-logo img,
    .telkom-logo img {
        width: 80px;
        height: auto;
    }

    .login-title img {
        width: 150px;
    }

    .login-subtitle {
        font-size: 20px;
    }

    .login-form {
        max-width: 100%;
    }

    .form-input,
    .form-select {
        font-size: 16px;
        padding: 14px;
    }

    .login-button {
        font-size: 18px;
        padding: 16px;
    }
}

@media (max-width: 480px) {
    .login-title img {
        width: 120px;
    }

    .login-subtitle {
        font-size: 18px;
    }

    .security-title {
        font-size: 20px;
    }

    .security-subtitle,
    .security-item {
        font-size: 12px;
    }
}
    </style>
</head>
<body>
    <div class="bg-decoration decoration-1"></div>
    <div class="bg-decoration decoration-2"></div>
    <div class="bg-decoration decoration-3"></div>
    
    <div class="main-container">
        <div class="left-panel">
            <div class="logo-section">
                <div class="bumn-logo">
                    <img src="assets/img/akhlak-removebg.png" width="120%">
                </div>
                <!-- <div class="telkom-logo">
                    <img src="assets/img/telkom-removebg.png" width="120%">
                </div> -->
            </div>
            
            <div class="security-awareness">
                <div class="security-title">SECURITY<br>AWARENESS</div>
                <div class="security-subtitle">Jaga <strong>aksimu anda aman</strong> dengan mengikuti tips praktis dibawah ini:</div>
                
                <ul class="security-list">
                    <li class="security-item">
                        <div class="security-number">1</div>
                        <div>Jangan tulis <strong>password</strong> anda dan meninggalkan di <strong>tempat yang dapat terlihat</strong></div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">2</div>
                        <div>Jangan <strong>menggunakan password yang sama</strong> untuk aktivitas pribadi dan pekerjaan</div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">3</div>
                        <div>Jangan <strong>menggunakan kembali password lama</strong> ketika diminta untuk ubah password</div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">4</div>
                        <div>Jangan <strong>membagi password anda</strong> dengan alasan apapun</div>
                    </li>
                    <li class="security-item">
                        <div class="security-number">5</div>
                        <div>Jangan <strong>mengaktifkan save password option</strong> ketika ditanya.</div>
                    </li>
                </ul>
                
                <div class="support-info">
                    <div class="support-title">Support dan Dukungan:</div>
                    <div class="support-title">TELKOM OPERATION CENTER</div>
                    <div>Command Center PT Telkom Indonesia</div>
                    <div style="margin-top: 10px;">
                        <div class="contact-item">
                            <div class="contact-icon"></div>
                            <span>ioc.itservices.inf@telkom.co.id</span>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"></div>
                            <span>ioc@telkom.co.id</span>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"></div>
                            <span>147</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="login-header">
                <div class="login-title">
                    <img src="assets/img/telkom-removebg.png" width="200">
                </div>
                <div class="login-subtitle">Sistem Absensi Magang</div>
            </div>
            
            <form class="login-form">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" class="form-input" placeholder="Masukkan username anda">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" class="form-input" placeholder="••••••••">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="role">Masuk Sebagai</label>
                    <select id="role" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="magang">Magang</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="showPassword">
                    <label for="showPassword">Show Password</label>
                </div>
                
                <div class="recaptcha-container">
                    <div class="recaptcha-mock">
                        <div class="recaptcha-checkbox"></div>
                        <span>I'm not a robot</span>
                        <div style="margin-left: 20px; font-size: 10px; color: #999;">reCAPTCHA<br>Privacy - Terms</div>
                    </div>
                </div>
                
                <button type="submit" class="login-button">LOGIN</button>
                
                <div class="powered-by">
                    Powered by IT Solution Development Telkom Indonesia
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Show/Hide password functionality
        document.getElementById('showPassword').addEventListener('change', function() {
            const passwordInput = document.getElementById('password');
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });
        
        // Form submission
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;
            
            if (!username || !password || !role) {
                alert('Mohon lengkapi semua field yang diperlukan');
                return;
            }
            
            alert(`Login berhasil!\nUsername: ${username}\nRole: ${role}`);
        });
    </script>
</body>
</html>

