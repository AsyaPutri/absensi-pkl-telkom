<?php
session_start();
require '../../config/database.php'; // file koneksi DB

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Absensi - PT Telkom Indonesia</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <style>
    :root {
      --telkom-red: #E31E24;
      --telkom-red-dark: #C41E3A;
      --telkom-gray: #F8F9FA;
    }

    body {
      background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-red-dark) 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .main-container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(227, 30, 36, 0.15);
      margin: 20px auto;
      overflow: hidden;
    }

    .logo-section {
      background: linear-gradient(135deg, white 0%, var(--telkom-gray) 100%);
      padding: 20px 40px;
      border-bottom: 3px solid var(--telkom-red);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      gap: 10px;
    }
    .logo-section img {
      max-height: 150px;
    }
    .company-info h2 {
      color: var(--telkom-red);
      margin: 0;
    }

    .profile-section {
      padding: 20px 30px;
      border-bottom: 2px solid var(--telkom-gray);
    }
    .profile-section h5 { color: var(--telkom-red-dark); }

    .table-container { padding: 30px; overflow-x: auto; }
    .table thead th {
      background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-red-dark) 100%);
      color: white;
      padding: 15px;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
    }
    .table tbody tr:hover { background-color: rgba(227,30,36,0.05); }
    .table tbody td { padding: 15px; vertical-align: middle; }

    .badge-status {
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.8rem;
    }
    .badge-sehat { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
    .badge-kurang-fit { background: linear-gradient(135deg, #ffc107, #fd7e14); color: #000; }
    .badge-sakit { background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white; }

    .foto-absen {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 8px;
      border: 2px solid #eee;
      cursor: pointer;
    }

    .loading {
      text-align: center;
      padding: 40px;
      color: #6c757d;
    }

    .no-data {
      text-align: center;
      padding: 60px;
      color: #6c757d;
    }

    /* Modal untuk foto */
    .photo-modal img {
      max-width: 100%;
      max-height: 80vh;
      object-fit: contain;
    }
  </style>
</head>
<body>
  <div class="container-fluid px-3">
    <div class="main-container">
      <!-- Logo + Judul -->
      <div class="logo-section">
        <img src="../assets/img/logo_telkom.png" alt="Logo Telkom">
        <div class="company-info">
          <h2><i class="bi bi-calendar-check me-2"></i>Riwayat Absensi - Magang</h2>
        </div>
      </div>

      <!-- Profil Mahasiswa -->
      <div class="profile-section">
        <h5>Informasi Peserta</h5>
        <div id="userProfile">
          <div class="loading">
            <i class="bi bi-hourglass-split"></i>
            <p>Memuat profil...</p>
          </div>
        </div>
      </div>


      <!-- Tombol Download -->
      <div class="text-end p-3">
        <button class="btn btn-danger" onclick="downloadPDF()">
          <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download Rekap PDF
        </button>
      </div>

      <!-- Table -->
      <div class="table-container">
        <div id="attendanceTable">
          <div class="loading">
            <i class="bi bi-hourglass-split"></i>
            <p>Memuat data absensi...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal untuk melihat foto -->
  <div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content photo-modal">
        <div class="modal-header">
          <h5 class="modal-title">Foto Absensi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <img id="modalPhoto" src="" alt="Foto Absensi">
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script>
    let attendanceData = [];
    let userProfileData = {};

    document.addEventListener('DOMContentLoaded', function() {
      loadUserProfile();
      loadAttendanceData();
    });

    // Load profil user
    async function loadUserProfile() {
      try {
        const response = await fetch('get_user_profile.php');
        const result = await response.json();
        
        const profileDiv = document.getElementById('userProfile');
        
        if (result.success) {
          userProfileData = result.data;
          profileDiv.innerHTML = `
            <p><strong>Nama:</strong> ${result.data.nama || '-'}</p>
            <p><strong>NIM:</strong> ${result.data.nim || '-'}</p>
            <p><strong>Asal Instansi:</strong> ${result.data.asal_instansi || '-'}</p>
            <p><strong>Unit Kerja:</strong> ${result.data.unit_kerja || '-'}</p>
          `;
        } else {
          profileDiv.innerHTML = `
            <p><strong>Nama:</strong> User</p>
            <p><strong>NIM:</strong> -</p>
            <p><strong>Asal Instansi:</strong> -</p>
            <p><strong>Unit Kerja:</strong> -</p>
          `;
        }
      } catch (error) {
        console.error('Error loading profile:', error);
        document.getElementById('userProfile').innerHTML = `
          <p class="text-danger">Error memuat profil</p>
        `;
      }
    }

    

async function loadAttendanceData() {
  try {
    const response = await fetch('get_attendance_history.php');
    const result = await response.json();
    
    if (result.success) {
      attendanceData = result.data;
      renderTable();
    } else {
      throw new Error(result.message || 'Gagal memuat data');
    }
    
  } catch (error) {
    console.error('Error loading attendance:', error);
    document.getElementById('attendanceTable').innerHTML = `
      <div class="no-data">
        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
        <h5>Error Memuat Data</h5>
        <p>${error.message}</p>
        <button class="btn btn-primary" onclick="loadAttendanceData()">
          <i class="bi bi-arrow-clockwise me-1"></i> Coba Lagi
        </button>
      </div>
    `;
  }
}

    // Render tabel
    function renderTable() {
      const tableDiv = document.getElementById('attendanceTable');
      
      if (attendanceData.length === 0) {
        tableDiv.innerHTML = `
          <div class="no-data">
            <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
            <h5>Tidak Ada Data</h5>
            <p>Belum ada data absensi</p>
          </div>
        `;
        return;
      }
      
      tableDiv.innerHTML = `
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th><i class="bi bi-calendar-date me-1"></i>Tanggal</th>
                <th><i class="bi bi-clock-history me-1"></i>Jam Masuk</th>
                <th><i class="bi bi-heart-pulse me-1"></i>Kondisi</th>
                <th><i class="bi bi-geo-alt me-1"></i>Lokasi Kerja</th>
                <th><i class="bi bi-clock me-1"></i>Jam Keluar</th>
                <th><i class="bi bi-stopwatch me-1"></i>Durasi Kerja</th>
                <th><i class="bi bi-image me-1"></i>Foto</th>
              </tr>
            </thead>
            <tbody>
              ${attendanceData.map(record => `
                <tr>
                  <td><strong>${formatDate(record.date)}</strong></td>
                  <td>${record.timeIn}</td>
                  <td>${getConditionBadge(record.condition)}</td>
                  <td>${getLocationBadge(record.location)}</td>
                  <td>${record.timeOut}</td>
                  <td>${record.durasi_kerja}</td>
                  <td>${getPhotoCell(record.photo)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    // Helper functions
    function getConditionBadge(condition) {
      const map = {
        'sehat': { class: 'badge-sehat', label: '😊 Sehat' },
        'kurang-fit': { class: 'badge-kurang-fit', label: '😐 Kurang Fit' },
        'sakit': { class: 'badge-sakit', label: '😷 Sakit' }
      };
      const config = map[condition] || map['sehat'];
      return `<span class="badge badge-status ${config.class}">${config.label}</span>`;
    }

    function getLocationBadge(location) {
      const map = {
        'office': '🏢 Office',
        'wfh': '🏠 WFH'
      };
      return map[location] || location;
    }

    function getPhotoCell(photo) {
  if (!photo) return '-';

  const photos = photo.split('|');
  return photos.map((p, index) => {
    const path = p.includes('../../uploads/') ? p : `../../uploads/absensi/${p}`;
    return `<img src="${path}" class="foto-absen me-1"
                 onclick="showPhoto('${path}')"
                 title="${index === 0 ? 'Check-in' : 'Check-out'}">`;
  }).join('');
}



    function formatDate(dateStr) {
      const options = { day: '2-digit', month: 'short', year: 'numeric' };
      return new Date(dateStr).toLocaleDateString('id-ID', options);
    }

    // Show photo modal
    function showPhoto(photoUrl) {
      document.getElementById('modalPhoto').src = photoUrl;
      new bootstrap.Modal(document.getElementById('photoModal')).show();
    }


    // Download PDF
    function downloadPDF() {
      if (attendanceData.length === 0) {
        alert('Tidak ada data untuk di-download');
        return;
      }
      
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      doc.setFontSize(14);
      doc.text("Rekap Absensi Magang - PT Telkom Indonesia", 14, 20);

      doc.setFontSize(11);
      doc.text(`Nama: ${userProfileData.nama || '-'}`, 14, 30);
      doc.text(`NIM: ${userProfileData.nim || '-'}`, 14, 37);
      doc.text(`Asal Instansi: ${userProfileData.asal_instansi || '-'}`, 14, 44);
      doc.text(`Unit Kerja: ${userProfileData.unit_kerja || '-'}`, 14, 51);

      const tableData = attendanceData.map(r => [
        formatDate(r.date),
        r.timeIn,
        r.condition.charAt(0).toUpperCase() + r.condition.slice(1).replace('_', ' '),
        r.location === 'office' ? 'Office' : 'WFH',
        r.timeOut,
        r.durasi_kerja
      ]);

      doc.autoTable({
        head: [['Tanggal', 'Jam Masuk', 'Kondisi', 'Lokasi', 'Jam Keluar', 'Durasi']],
        body: tableData,
        startY: 60,
      });

      doc.save(`rekap_absensi_${userProfileData.nama || 'user'}.pdf`);
    }
  </script>
</body>
</html>
