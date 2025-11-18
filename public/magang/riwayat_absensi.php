<?php
session_start();
require '../../config/database.php';

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
  <title>Riwayat Absensi - InStep</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

  <style>
    :root {
      --telkom-red: #E31E24;
      --telkom-red-dark: #C41E3A;
      --light-gray: #f9f9f9;
    }

    body {
      background: var(--light-gray);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #333;
    }

    /* ===== Header Section ===== */
    .header-section {
      text-align: center;
      padding: 60px 20px 40px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 30px;
    }

    .header-section img {
      width: 150px;
      margin-bottom: 10px;
    }

    .header-section h2 {
      color: var(--telkom-red-dark);
      font-weight: 800;
      letter-spacing: 0.5px;
      text-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }

    .header-section p {
      color: #555;
      font-size: 15px;
      font-weight: 500;
      margin-top: 5px;
    }

    /* ===== Info Card ===== */
    .info-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.06);
      padding: 20px 25px;
      margin-bottom: 25px;
    }

    .info-card .title {
      font-weight: 700;
      color: var(--telkom-red);
      margin-bottom: 10px;
    }

    .info-card p {
      margin: 0;
      color: #555;
    }

    /* ===== Table Section ===== */
    .table-section {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.06);
      overflow: hidden;
    }

    .table-header {
      background: linear-gradient(90deg, var(--telkom-red), var(--telkom-red-dark));
      color: white;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-radius: 14px 14px 0 0;
    }

    .table-header h5 {
      margin: 0;
      font-weight: 600;
    }

    .table-header button {
      background: white;
      color: var(--telkom-red-dark);
      border: none;
      font-weight: 600;
      padding: 6px 14px;
      border-radius: 8px;
      transition: 0.3s;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .table-header button:hover {
      background: var(--telkom-red-dark);
      color: white;
    }

    .table-container {
      padding: 20px;
      overflow-x: auto;
    }

    table {
      border-radius: 10px;
      overflow: hidden;
    }

    table thead {
      background: var(--telkom-red);
      color: white;
      text-align: center;
    }

    table th, table td {
      vertical-align: middle !important;
      text-align: center;
      font-size: 14px;
    }

    tr:hover {
      background-color: rgba(227, 30, 36, 0.05);
    }

    /* ===== Foto kecil ===== */
    .foto-absen {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      border: 2px solid #eee;
      transition: 0.2s;
    }

    .foto-absen:hover {
      transform: scale(1.05);
      border-color: var(--telkom-red);
    }

    /* ===== Modal Foto ===== */
    .photo-modal img {
      max-width: 100%;
      max-height: 80vh;
      border-radius: 8px;
    }

    /* ===== No Data ===== */
    .no-data {
      text-align: center;
      padding: 60px;
      color: #777;
    }

    /* ===== Responsiveness ===== */
    @media (max-width: 768px) {
      .header-section img {
        width: 120px;
      }

      .header-section h2 {
        font-size: 1.5rem;
      }

      .info-card {
        padding: 15px;
      }

      .table-header h5 {
        font-size: 1rem;
      }

      .table-header button {
        font-size: 0.9rem;
      }
    }
  </style>
</head>

<body>
  <div class="container py-4">

    <!-- Header -->
    <div class="header-section">
      <img src="../assets/img/InStep.png" alt="Logo Telkom">
      <h2><i class="bi bi-calendar-check me-2"></i>Riwayat Absensi</h2>
      <p>Pencatatan Kehadiran Harian | Internship Telkom Witel Bekasi - Karawang</p>
    </div>

    <!-- Informasi Peserta -->
    <div class="info-card">
      <div class="title"><i class="bi bi-person-circle me-2"></i>Informasi Peserta</div>
      <div id="userProfile" class="row">
        <div class="col-12 text-center text-muted">Memuat profil...</div>
      </div>
    </div>

    <!-- Data Tabel -->
    <div class="table-section">
      <div class="table-header">
        <h5><i class="bi bi-table me-2"></i>Data Riwayat Absensi</h5>
        <button onclick="downloadPDF()">
          <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
        </button>
      </div>

      <div class="table-container" id="attendanceTable">
        <div class="no-data">
          <i class="bi bi-hourglass-split" style="font-size: 2rem;"></i>
          <p>Memuat data absensi...</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Foto -->
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

    document.addEventListener('DOMContentLoaded', () => {
      loadUserProfile();
      loadAttendanceData();
    });

    async function loadUserProfile() {
      try {
        const res = await fetch('get_user_profile.php');
        const result = await res.json();
        const div = document.getElementById('userProfile');

        if (result.success) {
          userProfileData = result.data;
          div.innerHTML = `
            <div class="col-md-3"><p><strong>Nama:</strong> ${result.data.nama}</p></div>
            <div class="col-md-3"><p><strong>NIM:</strong> ${result.data.nim || '-'}</p></div>
            <div class="col-md-3"><p><strong>Asal Instansi:</strong> ${result.data.asal_instansi}</p></div>
            <div class="col-md-3"><p><strong>Unit Kerja:</strong> ${result.data.unit_kerja}</p></div>
          `;
        } else {
          div.innerHTML = `<div class="col-12 text-center text-danger">Gagal memuat profil</div>`;
        }
      } catch (e) {
        document.getElementById('userProfile').innerHTML = `<div class="col-12 text-center text-danger">Error memuat profil</div>`;
      }
    }

    async function loadAttendanceData() {
      try {
        const res = await fetch('get_attendance_history.php');
        const result = await res.json();
        if (result.success) {
          attendanceData = result.data;
          renderTable();
        } else {
          throw new Error(result.message);
        }
      } catch (err) {
        document.getElementById('attendanceTable').innerHTML = `
          <div class="no-data text-danger">
            <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
            <p>${err.message}</p>
          </div>
        `;
      }
    }

    function renderTable() {
      const tableDiv = document.getElementById('attendanceTable');
      if (attendanceData.length === 0) {
        tableDiv.innerHTML = `<div class="no-data"><p>Belum ada data absensi</p></div>`;
        return;
      }

      tableDiv.innerHTML = `
        <table class="table table-bordered table-hover mb-0">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Jam Masuk</th>
              <th>Kondisi</th>
              <th>Lokasi</th>
              <th>Jam Keluar</th>
              <th>Durasi</th>
              <th>Foto</th>
            </tr>
          </thead>
          <tbody>
            ${attendanceData.map(r => `
              <tr>
                <td>${formatDate(r.date)}</td>
                <td>${r.timeIn || '-'}</td>
                <td>${getCondition(r.condition)}</td>
                <td>${r.location === 'office' ? '🏢 Office' : '🏠 WFH'}</td>
                <td>${r.timeOut || '-'}</td>
                <td>${r.durasi_kerja || '-'}</td>
                <td>${getPhoto(r.photo)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    function formatDate(dateStr) {
      const options = { day: '2-digit', month: 'short', year: 'numeric' };
      return new Date(dateStr).toLocaleDateString('id-ID', options);
    }

    function getCondition(c) {
      const map = { 'sehat': '😊 Sehat', 'kurang-fit': '😐 Kurang Fit', 'sakit': '😷 Sakit' };
      return map[c] || '-';
    }

    function getPhoto(photo) {
      if (!photo) return '-';
      const photos = photo.split('|');
      return photos.map((p, i) => {
        const path = p.includes('../../uploads/') ? p : `../../uploads/absensi/${p}`;
        return `<img src="${path}" class="foto-absen me-1" onclick="showPhoto('${path}')" title="${i === 0 ? 'Masuk' : 'Keluar'}">`;
      }).join('');
    }

    function showPhoto(url) {
      document.getElementById('modalPhoto').src = url;
      new bootstrap.Modal(document.getElementById('photoModal')).show();
    }

    function downloadPDF() {
      if (attendanceData.length === 0) return alert('Tidak ada data untuk diunduh.');

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.setFontSize(14);
      doc.text("Rekap Riwayat Absensi - Telkom Indonesia", 14, 20);
      doc.setFontSize(11);
      doc.text(`Nama: ${userProfileData.nama || '-'}`, 14, 30);
      doc.text(`Asal Instansi: ${userProfileData.asal_instansi || '-'}`, 14, 37);
      doc.text(`Unit Kerja: ${userProfileData.unit_kerja || '-'}`, 14, 44);

      const body = attendanceData.map(r => [
        formatDate(r.date),
        r.timeIn || '-',
        r.condition || '-',
        r.location || '-',
        r.timeOut || '-',
        r.durasi_kerja || '-'
      ]);

      doc.autoTable({
        head: [['Tanggal', 'Masuk', 'Kondisi', 'Lokasi', 'Keluar', 'Durasi']],
        body,
        startY: 55
      });

      doc.save(`riwayat_absensi_${userProfileData.nama || 'user'}.pdf`);
    }
  </script>
</body>
</html>
