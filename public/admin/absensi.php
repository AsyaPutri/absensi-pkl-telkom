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
  <title>Dashboard Admin InStep - Telkom Witel Bekasi - Karawang </title>

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

    /* SIDEBAR */
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
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 900;
    }

    /* MAIN CONTENT */
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

    .telkom-logo {
      height: 125px;
      width: auto;
    }

    /* TABLE STYLE */
    .table-header-red th {
      background-color: #cc0000 !important;
      color: #fff !important;
      vertical-align: middle;
    }

    /* MODAL DETAIL TABLE */
    #detailModal table th, 
    #detailModal table td {
      vertical-align: middle;
      font-size: 13px;
    }

    #detailModal table {
      border-radius: 12px;
      overflow: hidden;
    }

    #detailModal .modal-body {
      background-color: #fafafa;
    }

    #infoPeserta p {
      margin-bottom: 6px;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .sidebar { left: -280px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .telkom-logo { height: 70px; }
      .card-number { font-size: 2rem; }
    }
    @media (max-width: 576px) {
      .stats-card .card-body { padding: 1rem; }
      .card-number { font-size: 1.8rem; }
      .header h4 { font-size: 1.2rem; }
      .header small { font-size: 0.8rem; }
      .telkom-logo { height: 50px; }
    }
  </style>
</head>

<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebarMenu">
    <div class="text-center py-3">
      <i class="bi bi-person-circle fs-1 text-white"></i>
      <p class="fw-bold mb-0 text-white">Admin Internship | InStep</p>
      <small class="text-white-50">Telkom Witel Bekasi - Karawang</small>
    </div>
    <hr class="text-white-50">
    <ul class="nav flex-column">
      <li><a href="dashboard.php" class="nav-link <?= ($current_page=='dashboard.php')?'active':'' ?>"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link <?= ($current_page=='daftar_pkl.php')?'active':'' ?>"><i class="bi bi-journal-text me-2"></i> Data Daftar Internship</a></li>
      <li><a href="peserta.php" class="nav-link <?= ($current_page=='peserta.php')?'active':'' ?>"><i class="bi bi-people me-2"></i> Data Peserta Internship</a></li>
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
          <h4 class="mb-0 fw-bold text-danger">Rekap Absensi Internship | InStep</h4>
          <small class="text-muted">Sistem Manajemen Internship</small>
        </div>
      </div>
      <img src="../assets/img/InStep.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <!-- Filter -->
    <div class="card mb-3 shadow-sm">
      <div class="card-body">
        <form id="filterForm" class="row g-3 align-items-end">

          <!-- Filter Unit -->
          <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
            <label class="form-label fw-semibold text-secondary">Unit</label>
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

          <!-- Pencarian -->
          <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <label for="q" class="form-label fw-semibold text-secondary">
              Cari (Nama / NIS )
            </label>
            <div class="input-group shadow-sm">
              <span class="input-group-text bg-danger text-white border-0">
                <i class="bi bi-search"></i>
              </span>
              <input 
                type="text" 
                name="search" 
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                class="form-control border-0 bg-light" 
                placeholder="Ketik kata kunci..."
              >
            </div>
          </div>

          <!-- Tombol -->
          <div class="col-xl-3 col-lg-3 col-md-12 col-sm-12 d-flex gap-2">
            <button type="submit" class="btn btn-danger flex-fill">
              <i class="bi bi-filter"></i> Filter
            </button>
            <button type="button" id="resetBtn" class="btn btn-outline-secondary flex-fill">
              Reset
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Rekap -->
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="fw-bold text-danger mb-0">
          <i class="bi bi-calendar-check me-2"></i> Data Rekap Absensi
        </h5>
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
              <tr>
                <td colspan="9" class="text-center text-muted">
                  Silakan pilih <strong>Tanggal Awal</strong>, <strong>Tanggal Akhir</strong> dan <strong>Unit</strong> lalu tekan <em>Filter</em> untuk melihat data.
                </td>
              </tr>
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
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title fw-bold">
            Detail Rekap: <span id="modalNama"></span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div id="infoPeserta" class="mb-3"></div>
          <hr>
          <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle shadow-sm">
              <thead class="table-header-red">
                <tr>
                  <th>No</th>
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

        <div class="modal-footer bg-light">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button class="btn btn-primary" id="exportPerPeserta">Export</button>
        </div>
      </div>
    </div>
  </div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
  // ======================================================
  // ========== SIDEBAR MENU TOGGLE =======================
  // ======================================================
  const menuToggleEl = document.getElementById("menuToggle");
  if (menuToggleEl) {
    menuToggleEl.addEventListener("click", function () {
      const sidebar = document.getElementById("sidebarMenu");
      if (sidebar) sidebar.classList.toggle("active");
      const overlay = document.getElementById("sidebarOverlay");
      if (overlay) overlay.style.display = "block";
    });
  }

  const overlayEl = document.getElementById("sidebarOverlay");
  if (overlayEl) {
    overlayEl.addEventListener("click", function () {
      const sidebar = document.getElementById("sidebarMenu");
      if (sidebar) sidebar.classList.remove("active");
      this.style.display = "none";
    });
  }

 // ======================================================
  // ========== LOAD REKAP ABSENSI ========================
  // ======================================================
  function loadRekap(params = {}) {
    const url = new URL("rekap_absensi.php", window.location.href);
    Object.keys(params).forEach(k => {
      if (params[k]) url.searchParams.append(k, params[k]);
    });

    fetch(url)
      .then(res => res.json())
      .then(data => {
        console.log("DATA REKAP:", data);
        const tbody = document.getElementById("rekapBody");
        if (!tbody) return console.warn("rekapBody element not found");
        tbody.innerHTML = "";

        if (Array.isArray(data) && data.length > 0) {
          data.forEach((row, i) => {
            const jumlahHadir = parseInt(row.jumlah_hadir) || 0;
            const jumlahTidakHadir = parseInt(row.jumlah_tidak_hadir) || 0;
            const idForDetail = row.user_id ?? row.id ?? row.peserta_id ?? null;

            const tr = document.createElement("tr");
            tr.innerHTML = `
              <td>${i + 1}</td>
              <td>${row.nama ?? "-"}</td>
              <td>${row.nis_npm ?? "-"}</td>
              <td>${row.unit ?? "-"}</td>
              <td>${row.hari_kerja ?? "-"}</td>
              <td>${jumlahHadir}</td>
              <td>${jumlahTidakHadir}</td>
              <td>${row.persen ?? 0}%</td>
            `;

            const tdBtn = document.createElement("td");
            if (idForDetail !== null) {
              const btn = document.createElement("button");
              btn.className = "btn btn-sm btn-info";
              btn.innerHTML = '<i class="bi bi-eye"></i> Detail';
              btn.addEventListener("click", () => {
                // 🟢 kirim data rekap ke modal detail
                loadDetail(
                  idForDetail,
                  params.tgl_awal || "",
                  params.tgl_akhir || "",
                  row.hari_kerja ?? 0,
                  jumlahHadir,
                  row.persen ?? 0
                );
              });
              tdBtn.appendChild(btn);
            } else {
              const btn = document.createElement("button");
              btn.className = "btn btn-sm btn-secondary";
              btn.disabled = true;
              btn.innerHTML = '<i class="bi bi-eye"></i> Detail';
              tdBtn.appendChild(btn);
            }

            tr.appendChild(tdBtn);
            tbody.appendChild(tr);
          });
        } else {
          tbody.innerHTML = `<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>`;
        }
      })
      .catch(err => console.error("FETCH ERROR rekap_absensi:", err));
  }

  // ======================================================
  // ========== FILTER FORM HANDLER =======================
  // ======================================================
  const filterForm = document.getElementById("filterForm");
  if (filterForm) {
    filterForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      let params = {};
      formData.forEach((v, k) => (params[k] = v));
      loadRekap(params);
    });
  }

  // ======================================================
  // ========== RESET BUTTON HANDLER ======================
  // ======================================================
  const resetBtn = document.getElementById("resetBtn");
  if (resetBtn) {
    resetBtn.addEventListener("click", function () {
      if (filterForm) filterForm.reset();
      loadRekap();
    });
  }

  // ======================================================
  // ========== AUTO LOAD SAAT HALAMAN DIBUKA =============
  // ======================================================
  document.addEventListener("DOMContentLoaded", function () {
    loadRekap();
  });

  // ======================================================
  // ========== DETAIL MODAL & EXPORT PDF =================
  // ======================================================
  function loadDetail(user_id, tgl_awal, tgl_akhir, hariKerja, jumlahHadir, persenKehadiran) {
    console.log("Request detail untuk user_id/peserta_id:", user_id);
    const url = new URL("detail_absensi.php", window.location.href);
    url.searchParams.append("user_id", user_id);
    if (tgl_awal) url.searchParams.append("tgl_awal", tgl_awal);
    if (tgl_akhir) url.searchParams.append("tgl_akhir", tgl_akhir);

    fetch(url)
      .then(res => res.text())
      .then(txt => {
        console.log("RAW response detail_absensi:", txt);
        let data;
        try {
          data = JSON.parse(txt);
        } catch (e) {
          console.error("JSON parse error:", e);
          alert("Response bukan JSON. Cek console (RAW response).");
          return;
        }

        console.log("PARSED DATA DETAIL:", data);
        let peserta = null;
        let absensi = [];

        if (data.info) {
          peserta = data.info;
          absensi = data.absensi || [];
        } else if (Array.isArray(data) && data.length > 0) {
          peserta = {
            nama: data[0].nama,
            nis_npm: data[0].nis_npm ?? data[0].nim ?? "-",
            instansi_pendidikan: data[0].instansi_pendidikan ?? data[0].instansi ?? "-",
            unit: data[0].unit ?? "-",
            tgl_mulai: data[0].tgl_mulai ?? "-",
            tgl_selesai: data[0].tgl_selesai ?? "-"
          };
          absensi = data;
        }

        // 🟢 simpan data statistik di peserta agar bisa dipakai di export
        peserta.hari_kerja = hariKerja;
        peserta.jumlah_hadir = jumlahHadir;
        peserta.persen = persenKehadiran;

        if (peserta) {
          document.getElementById("modalNama").innerText = peserta.nama ?? "-";
          document.getElementById("infoPeserta").innerHTML = `
            <div class="row">
              <div class="col-md-6">
                <p><strong>Nama:</strong> ${peserta.nama ?? "-"}</p>
                <p><strong>NIM:</strong> ${peserta.nis_npm ?? "-"}</p>
                <p><strong>Instansi:</strong> ${peserta.instansi_pendidikan ?? "-"}</p>
                <p><strong>Unit:</strong> ${peserta.unit ?? "-"}</p>
                <p><strong>Periode Internship:</strong> ${peserta.tgl_mulai ?? "-"} s/d ${peserta.tgl_selesai ?? "-"}</p>
                <hr>
                <p><strong>Jumlah Hari Kerja:</strong> ${hariKerja}</p>
                <p><strong>Jumlah Hadir:</strong> ${jumlahHadir}</p>
                <p><strong>Persentase Kehadiran:</strong> ${persenKehadiran}%</p>
              </div>
            </div>`;
        }

        const tbody = document.getElementById("modalBody");
        if (!tbody) return console.warn("modalBody element not found");
        tbody.innerHTML = "";

        if (absensi.length > 0) {
          absensi.forEach((row, index) => {
            const kondisi = row.kondisi ?? row.kondisi_kesehatan ?? "-";
            const lokasi = row.lokasi ?? row.lokasi_kerja ?? "-";
            const fotoRaw = row.foto ?? row.foto_absen ?? null;
            const fotoHref = fotoRaw
              ? fotoRaw.startsWith("http") || fotoRaw.startsWith("/")
                ? fotoRaw
                : `../../uploads/absensi/${fotoRaw}`
              : null;

            tbody.innerHTML += `
              <tr>
                <td>${index + 1}</td>
                <td>${row.tanggal ?? "-"}</td>
                <td>${row.jam_masuk ?? "-"}</td>
                <td>${row.aktivitas_masuk ?? "-"}</td>
                <td>${row.kendala_masuk ?? "-"}</td>
                <td>${kondisi}</td>
                <td>${lokasi}</td>
                <td>${row.aktivitas_keluar ?? "-"}</td>
                <td>${row.kendala_keluar ?? "-"}</td>
                <td>${row.jam_keluar ?? "-"}</td>
                <td>
                  ${
                    fotoHref
                      ? `<a href="${fotoHref}" target="_blank" class="btn btn-outline-primary btn-sm">Lihat</a>`
                      : "-"
                  }
                </td>
              </tr>`;
          });
        } else {
          tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">Tidak ada data absensi</td></tr>`;
        }

        // 🟢 Set tombol export
        const exportBtn = document.getElementById("exportPerPeserta");
        if (exportBtn)
          exportBtn.onclick = () =>
            downloadPDF(peserta, absensi, {
              totalHariKerja: hariKerja,
              jumlahHadir: jumlahHadir,
              persen: persenKehadiran,
            });

        new bootstrap.Modal(document.getElementById("detailModal")).show();
      })
      .catch(err => {
        console.error("Error fetch detail_absensi:", err);
        alert("Gagal memuat detail peserta. Cek console untuk detail.");
      });
  }

  // ======================================================
  // ========== EXPORT PDF DETAIL ABSENSI ================
  // ======================================================
  function downloadPDF(peserta, absensi, statistik = {}) {
    if (!absensi || absensi.length === 0) {
      alert("Tidak ada data absensi untuk diunduh.");
      return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: "landscape" });

    // Header
    doc.setFontSize(16);
    doc.text("REKAP DETAIL ABSENSI Internship", 150, 15, { align: "center" });
    doc.setFontSize(12);
    doc.text("PT TELKOM INDONESIA", 150, 22, { align: "center" });
    doc.line(14, 25, 283, 25);

    // Info Peserta
    doc.setFontSize(11);
    let y = 35;
    doc.text(`Nama: ${peserta.nama || "-"}`, 14, y);
    y += 7;
    doc.text(`NIM/NIS: ${peserta.nis_npm || "-"}`, 14, y);
    y += 7;
    doc.text(`Instansi: ${peserta.instansi_pendidikan || "-"}`, 14, y);
    y += 7;
    doc.text(`Unit: ${peserta.unit || "-"}`, 14, y);
    y += 7;
    doc.text(`Periode Internship: ${peserta.tgl_mulai || "-"} s/d ${peserta.tgl_selesai || "-"}`, 14, y);

    // 🟢 Tambahkan statistik di bawah periode
    y += 10;
    doc.text(`Jumlah Hari Kerja: ${statistik.totalHariKerja || "-"}`, 14, y);
    y += 7;
    doc.text(`Jumlah Hadir: ${statistik.jumlahHadir || "-"}`, 14, y);
    y += 7;
    doc.text(`Persentase Kehadiran: ${statistik.persen || 0}%`, 14, y);

    // Siapkan data tabel
    const tableData = absensi.map((r, i) => [
      i + 1,
      r.tanggal || "-",
      r.jam_masuk || "-",
      r.aktivitas_masuk || "-",
      r.kendala_masuk || "-",
      r.kondisi_kesehatan || "-",
      r.lokasi_kerja || "-",
      r.aktivitas_keluar || "-",
      r.kendala_keluar || "-",
      r.jam_keluar || "-"
    ]);

    // Tabel
    doc.autoTable({
      startY: y + 10,
      head: [[
        "No", "Tanggal", "Jam Masuk", "Aktivitas Masuk", "Kendala Masuk",
        "Kondisi Kesehatan", "Lokasi", "Aktivitas Keluar", "Kendala Keluar", "Jam Keluar"
      ]],
      body: tableData,
      styles: { fontSize: 8, cellPadding: 2, halign: "center", valign: "middle" },
      headStyles: { fillColor: [220, 53, 69], textColor: 255, halign: "center" },
      alternateRowStyles: { fillColor: [245, 245, 245] },
      margin: { left: 14, right: 14 }
    });

    const namaFile = `rekap_absensi_${(peserta.nama || "user").replace(/\s+/g, "_")}.pdf`;
    doc.save(namaFile);
  }
</script>
</body>
</html>

