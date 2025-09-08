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
      /* font-weight: bold; */
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
    .badge-kurangfit { background: linear-gradient(135deg, #ffc107, #fd7e14); color: #000; }
    .badge-sakit { background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white; }

    .foto-absen {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 8px;
      border: 2px solid #eee;
    }

    .download-btn {
      margin: 20px 30px;
      text-align: right;
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
        <p><strong>Nama:</strong> Asya Herawati Putri</p>
        <p><strong>NIM:</strong> 123456789</p>
        <p><strong>Asal Instansi:</strong> Universitas Bina Sarana Informatika</p>
        <p><strong>Unit Kerja:</strong> Finance dan HC</p>
      </div>

      <!-- Tombol Download -->
      <div class="download-btn">
        <button class="btn btn-danger" onclick="downloadPDF()">
          <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download Rekap PDF
        </button>
      </div>

      <!-- Table -->
      <div class="table-container">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th><i class="bi bi-calendar-date me-1"></i>Tanggal</th>
                <th><i class="bi bi-clock-history me-1"></i>Jam Masuk</th>
                <th><i class="bi bi-heart-pulse me-1"></i>Kondisi</th>
                <th><i class="bi bi-geo-alt me-1"></i>Lokasi Kerja</th>
                <th><i class="bi bi-clock me-1"></i>Jam Keluar</th>
                <th><i class="bi bi-image me-1"></i>Foto Absen</th>
              </tr>
            </thead>
            <tbody id="attendanceTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    const attendanceData = [
      { date: '2024-09-02', timeIn: '08:00', timeOut: '16:00', condition: 'sehat', location: 'Office', photo: 'https://via.placeholder.com/80' },
      { date: '2024-09-01', timeIn: '08:10', timeOut: '16:05', condition: 'kurang fit', location: 'WFH', photo: 'https://via.placeholder.com/80' },
      { date: '2024-08-31', timeIn: '-', timeOut: '-', condition: 'sakit', location: '-', photo: 'https://via.placeholder.com/80' },
      { date: '2024-08-30', timeIn: '07:55', timeOut: '15:50', condition: 'sehat', location: 'Office', photo: 'https://via.placeholder.com/80' }
    ];

    function getConditionBadge(condition) {
      const map = {
        'sehat': { class: 'badge-sehat', label: 'Sehat' },
        'kurang fit': { class: 'badge-kurangfit', label: 'Kurang Fit' },
        'sakit': { class: 'badge-sakit', label: 'Sakit' }
      };
      const config = map[condition.toLowerCase()] || map['sehat'];
      return `<span class="badge badge-status ${config.class}">${config.label}</span>`;
    }

    function formatDate(dateStr) {
      const options = { day: '2-digit', month: 'short', year: 'numeric' };
      return new Date(dateStr).toLocaleDateString('id-ID', options);
    }

    function renderTable() {
      const tbody = document.getElementById('attendanceTableBody');
      tbody.innerHTML = attendanceData.map(r => `
        <tr>
          <td><strong>${formatDate(r.date)}</strong></td>
          <td>${r.timeIn}</td>
          <td>${getConditionBadge(r.condition)}</td>
          <td>${r.location}</td>
          <td>${r.timeOut}</td>
          <td><img src="${r.photo}" class="foto-absen" alt="Foto"></td>
        </tr>
      `).join('');
    }

    function downloadPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      doc.setFontSize(14);
      doc.text("Rekap Absensi Magang - PT Telkom Indonesia", 14, 20);

      doc.setFontSize(11);
      doc.text("Nama: Asya Herawati Putri", 14, 30);
      doc.text("NIM: 123456789", 14, 37);
      doc.text("Asal Instansi: Universitas Bina Sarana Informatika", 14, 44);
      doc.text("Unit Kerja: Finance dan HC", 14, 51);

      const tableData = attendanceData.map(r => [
        formatDate(r.date),
        r.timeIn,
        r.condition.charAt(0).toUpperCase() + r.condition.slice(1),
        r.location,
        r.timeOut
      ]);

      doc.autoTable({
        head: [['Tanggal', 'Jam Masuk', 'Kondisi', 'Lokasi Kerja', 'Jam Keluar']],
        body: tableData,
        startY: 60,
      });

      doc.save("rekap_absensi.pdf");
    }

    document.addEventListener('DOMContentLoaded', renderTable);
  </script>
</body>
</html>
