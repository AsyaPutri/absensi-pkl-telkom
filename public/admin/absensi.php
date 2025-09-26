<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php"; // koneksi DB

$unit = isset($_GET['unit']) ? $_GET['unit'] : 'all'; // default "all"

// Highlight menu aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil data unit dari database
$unitResult = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin PKL - Telkom Indonesia</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --telkom-red: #cc0000;
      --telkom-red-dark: #990000;
    }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }
    .sidebar {
      width: 280px;
      min-height: 100vh;
      background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-red-dark) 100%);
      color: #e0e0e0;
      padding: 1rem;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      box-shadow: 4px 0 15px rgba(0,0,0,0.15);
      transition: left 0.3s ease;
    }
    .sidebar a {
      color: #e0e0e0 !important;
      border-radius: 12px;
      padding: 12px 18px;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      text-decoration: none;
      transition: 0.3s;
    }
    .sidebar a.active,
    .sidebar a:hover {
      background-color: rgba(255,255,255,0.15);
      color: #fff !important;
      transform: translateX(6px);
    }
    .sidebar-overlay {
      display: none;
      position: fixed;
      top:0;
      left:0;
      width:100%;
      height:100%;
      background: rgba(0,0,0,0.5);
      z-index: 900;
    }
    .main-content {
      margin-left: 280px;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }
    .header {
      background: #fff;
      border-bottom: 1px solid #eee;
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .telkom-logo { height: 80px; width: auto; }
    .table-header-red th {
      background-color: #cc0000 !important;
      color: #fff !important;
    }
    @media (max-width: 768px) {
      .sidebar { left: -280px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .telkom-logo { height: 70px; }
    }
  </style>
</head>
<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebarMenu">
    <div class="text-center py-3">
      <i class="bi bi-person-circle fs-1 text-white"></i>
      <p class="fw-bold mb-0 text-white">Admin PKL</p>
      <small class="text-white-50">Telkom Witel Bekasi</small>
    </div>
    <hr class="text-white-50">
    <ul class="nav flex-column">
      <li><a href="dashboard.php" class="nav-link <?= ($current_page=='dashboard.php')?'active':'' ?>"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link <?= ($current_page=='daftar_pkl.php')?'active':'' ?>"><i class="bi bi-journal-text me-2"></i> Data Daftar PKL</a></li>
      <li><a href="peserta.php" class="nav-link <?= ($current_page=='peserta.php')?'active':'' ?>"><i class="bi bi-people me-2"></i> Data Peserta</a></li>
      <li><a href="absensi.php" class="nav-link <?= ($current_page=='absensi.php')?'active':'' ?>"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="riwayat_peserta.php" class="nav-link <?= ($current_page=='riwayat_peserta.php')?'active':'' ?>"><i class="bi bi-clock-history me-2"></i> Riwayat Peserta</a></li>
      <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="header">
      <div class="d-flex align-items-center">
        <button class="btn btn-outline-secondary d-md-none me-2" id="menuToggle">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h4 class="mb-0 fw-bold text-danger">Dashboard Admin PKL</h4>
          <small class="text-muted">Sistem Manajemen Praktik Kerja Lapangan</small>
        </div>
      </div>
      <img src="../assets/img/logo_telkom.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <!-- Filter -->
    <div class="card mb-3">
      <div class="card-body">
        <form id="filterForm" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Tanggal Awal</label>
            <input type="date" name="tgl_awal" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Tanggal Akhir</label>
            <input type="date" name="tgl_akhir" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Unit</label>
            <select name="unit" class="form-select">
              <option value="all" <?= $unit === 'all' ? 'selected' : '' ?>>Semua Unit</option>
              <?php
              $unitResult = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");
              while ($u = $unitResult->fetch_assoc()) {
                $sel = ($unit == $u['id']) ? 'selected' : '';
                echo "<option value='{$u['id']}' $sel>" . htmlspecialchars($u['nama_unit']) . "</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-danger"><i class="bi bi-filter"></i> Filter</button>
            <button type="button" id="resetBtn" class="btn btn-outline-secondary">Reset</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Rekap -->
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="fw-bold text-danger mb-0"><i class="bi bi-calendar-check me-2"></i> Data Rekap Absensi</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center align-middle" id="rekapTable">
            <thead class="table-header-red">
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIM</th>
                <th>Unit</th>
                <th>Hari Kerja</th>
                <th>Jumlah Hadir</th>
                <th>Jumlah Tidak Hadir</th>
                <th>% Kehadiran</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="rekapBody">
              <tr><td colspan="9" class="text-center text-muted">Silakan pilih <strong>Tanggal Awal</strong>, <strong>Tanggal Akhir</strong> dan <strong>Unit</strong> lalu tekan <em>Filter</em> untuk melihat data.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detail -->
  <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detail Rekap: <span id="modalNama"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="infoPeserta" class="mb-3"></div>
          <hr>
          <div class="table-responsive">
            <table class="table table-sm table-bordered text-center align-middle">
              <thead class="table-header-red">
                <tr>
                  <th>Tanggal</th>
                  <th>Jam Masuk</th>
                  <th>Aktivitas Masuk</th>
                  <th>Kendala Masuk</th>
                  <th>Kondisi</th>
                  <th>Lokasi Kerja</th>
                  <th>Aktivitas Keluar</th>
                  <th>Kendala Keluar</th>
                  <th>Jam Keluar</th>
                  <th>Foto</th>
                </tr>
              </thead>
              <tbody id="modalBody"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button class="btn btn-primary" id="exportPerPeserta">Export</button>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle
    document.getElementById("menuToggle").addEventListener("click", function(){
      document.getElementById("sidebarMenu").classList.toggle("active");
      document.getElementById("sidebarOverlay").style.display = "block";
    });
    document.getElementById("sidebarOverlay").addEventListener("click", function(){
      document.getElementById("sidebarMenu").classList.remove("active");
      this.style.display = "none";
    });

    // Load rekap data
    function loadRekap(params = {}){
      let url = new URL("rekap_absensi.php", window.location.href);
      Object.keys(params).forEach(k => {
        if(params[k]) url.searchParams.append(k, params[k]);
      });

      fetch(url)
        .then(res => res.json())
        .then(data => {
          console.log("DATA REKAP:", data);
          const tbody = document.getElementById("rekapBody");
          tbody.innerHTML = "";
          if(data.length > 0){
            data.forEach((row, i) => {
              let jumlahHadir = (parseInt(row.hadir_office) || 0) + (parseInt(row.hadir_wfh) || 0);
              tbody.innerHTML += `
                <tr>
                  <td>${i+1}</td>
                  <td>${row.nama}</td>
                  <td>${row.nis_npm ?? '-'}</td>
                  <td>${row.unit}</td>
                  <td>${row.hari_kerja}</td>
                  <td>${jumlahHadir}</td>
                  <td>${row.alpha}</td>
                  <td>${row.persen}%</td>
                  <td>
                    <button class="btn btn-sm btn-info" 
                            onclick="loadDetail(${row.user_id}, '${params.tgl_awal||''}', '${params.tgl_akhir||''}')">
                      <i class="bi bi-eye"></i> Detail
                    </button>
                  </td>
                </tr>`;
            });
          } else {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>`;
          }
        })
        .catch(err => console.error("FETCH ERROR rekap_absensi:", err));
    }

    // Filter form
    document.getElementById("filterForm").addEventListener("submit", function(e){
      e.preventDefault();
      const formData = new FormData(this);
      let params = {};
      formData.forEach((v,k) => params[k] = v);

      if (!params.tgl_awal || !params.tgl_akhir){
        alert("Silahkan isi Tanggal Awal dan Tanggal Akhir terlebih dahulu.");
        return;
      }
      loadRekap(params);
    });

    // Reset filter
    document.getElementById("resetBtn").addEventListener("click", function(){
      document.getElementById("filterForm").reset();
      document.getElementById("rekapBody").innerHTML = '<tr><td colspan="9" class="text-center text-muted">Silakan pilih <strong>Tanggal Awal</strong>, <strong>Tanggal Akhir</strong> dan <strong>Unit</strong> lalu tekan <em>Filter</em> untuk melihat data.</td></tr>';
    });

    // Load detail modal
    function loadDetail(user_id, tgl_awal, tgl_akhir){
      let url = `detail_absensi.php?user_id=${user_id}`;
      if (tgl_awal) url += `&tgl_awal=${encodeURIComponent(tgl_awal)}`;
      if (tgl_akhir) url += `&tgl_akhir=${encodeURIComponent(tgl_akhir)}`;

      fetch(url)
        .then(res => res.json())
        .then(data => {
          console.log("DATA DETAIL:", data);
          let peserta = null;
          let absensi = [];

          if (data.info) {
            peserta = data.info;
            absensi = data.absensi || [];
          } else if (Array.isArray(data) && data.length > 0) {
            peserta = {
              nama: data[0].nama,
              nis_npm: data[0].nis_npm || data[0].nim || '-',
              instansi_pendidikan: data[0].instansi_pendidikan || data[0].instansi || '-',
              unit: data[0].unit || '-',
              tgl_mulai: data[0].tgl_mulai || '-',
              tgl_selesai: data[0].tgl_selesai || '-'
            };
            absensi = data;
          }

          if (peserta) {
            document.getElementById("modalNama").innerText = peserta.nama || '-';
            document.getElementById("infoPeserta").innerHTML = `
              <div class="row">
                <div class="col-md-6">
                  <p><strong>Nama:</strong> ${peserta.nama || '-'}</p>
                  <p><strong>NIM:</strong> ${peserta.nis_npm || '-'}</p>
                  <p><strong>Instansi:</strong> ${peserta.instansi_pendidikan || '-'}</p>
                  <p><strong>Unit:</strong> ${peserta.unit || '-'}</p>
                  <p><strong>Periode PKL:</strong> ${peserta.tgl_mulai} s/d ${peserta.tgl_selesai}</p>
                </div>
              </div>`;
          }

          const tbody = document.getElementById("modalBody");
          tbody.innerHTML = "";
          if (absensi.length > 0) {
            absensi.forEach(row => {
              tbody.innerHTML += `
                <tr>
                  <td>${row.tanggal || '-'}</td>
                  <td>${row.jam_masuk || '-'}</td>
                  <td>${row.aktivitas_masuk || '-'}</td>
                  <td>${row.kendala_masuk || '-'}</td>
                  <td>${row.kondisi || '-'}</td>
                  <td>${row.lokasi_kerja || row.lokasi || '-'}</td>
                  <td>${row.aktivitas_keluar || '-'}</td>
                  <td>${row.kendala_keluar || '-'}</td>
                  <td>${row.jam_keluar || '-'}</td>
                  <td>${row.foto ? `<a href="${row.foto}" target="_blank" class="btn btn-sm btn-link">Check</a>` : '-'}</td>
                </tr>`;
            });
          } else {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">Tidak ada data absensi</td></tr>`;
          }

          new bootstrap.Modal(document.getElementById("detailModal")).show();
        })
        .catch(err => {
          console.error("Error fetch detail_absensi:", err);
          alert("Gagal memuat detail peserta.");
        });
    }
  </script>
</body>
</html>
