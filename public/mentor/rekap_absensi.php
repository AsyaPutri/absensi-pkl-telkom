<?php
include "../../includes/auth.php";
checkRole('mentor');
include "../../config/database.php";

// ============================
// Ambil data unit untuk filter
// ============================
$email = $_SESSION['email'];
$q = $conn->query("SELECT unit_id FROM cp_karyawan WHERE email = '$email' LIMIT 1");

if ($q && $q->num_rows > 0) {
  $mentor = $q->fetch_assoc();
  $unit_mentor = $mentor['unit_id'];
} else {
  echo "<div class='alert alert-warning text-center m-4'>
          Anda belum memiliki unit yang terdaftar. Hubungi admin untuk mengaitkan unit ke akun Anda.
        </div>";
  exit;
}

// ============================
// Ambil nama unit mentor
// ============================
$unitNama = $conn->query("SELECT nama_unit FROM unit_pkl WHERE id = '$unit_mentor'")
                 ->fetch_assoc()['nama_unit'] ?? 'Tidak diketahui';

// ============================
// Ambil parameter pencarian
// ============================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ============================
// Query peserta & absensi
// ============================
$where = "WHERE p.unit_id = '$unit_mentor'";
if ($search !== '') {
  $where .= "AND (p.nama LIKE '%search%' OR p.nis_npm LIKE '%$search%')";
}

$sql = "
SELECT 
  p.id AS peserta_id,
  p.user_id,
  p.nama,
  p.nis_npm,
  u.nama_unit AS unit,
  p.tgl_mulai,
  p.tgl_selesai,
  COUNT(a.id) AS total_hadir
FROM peserta_pkl p
LEFT JOIN unit_pkl u ON p.unit_id = u.id
LEFT JOIN absen a ON a.user_id = p.user_id
$where
GROUP BY p.id, p.user_id, p.nama, p.nis_npm, u.nama_unit, p.tgl_mulai, p.tgl_selesai
ORDER BY p.nama ASC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rekap Absensi Peserta Internship</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --telkom-red: #cc0000;
      --telkom-dark: #990000;
      --light-bg: #f8f9fc;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: var(--light-bg);
      min-height: 100vh;
    }

    .header {
      background: #fff;
      border-bottom: 2px solid var(--telkom-red);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .header img { height: 60px; }
    .header .title h4 { color: var(--telkom-red); font-weight: 700; margin-bottom: 4px; }
    .header .title small { color: #6c757d; }

    .back-btn {
      background: var(--telkom-red);
      color: #fff;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 500;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .back-btn:hover { background: var(--telkom-dark); color: #fff; }

    .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .card-header { background: #fff; border-bottom: 3px solid var(--telkom-red); padding: 1rem 1.5rem; }
    .card-header h5 { color: var(--telkom-red); font-weight: 700; margin: 0; }

    .table thead th { background: var(--telkom-red); color: #fff; text-align: center; }
    .table-hover tbody tr:hover { background-color: #ffecec; }

    .filter-section { display: flex; gap: 10px; margin-bottom: 1rem; align-items: center; flex-wrap: wrap; }
  </style>
</head>

<body>
  <div class="header">
    <div class="d-flex align-items-center">
      <img src="../assets/img/logo_telkom.png" alt="Telkom Logo">
      <div class="title ms-3">
        <h4>Data Absensi Peserta Internship</h4>
        <small>Sistem Monitoring Internship | Telkom Witel Bekasi - Karawang</small>
      </div>
    </div>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
  </div>

  <div class="container-fluid mt-4">
    <div class="card">
      <div class="card-header">
        <h5><i class="bi bi-calendar-check me-2"></i> Rekap Absensi Unit: <?= htmlspecialchars($unitNama); ?></h5>
      </div>

      <div class="card-body">
        <form method="GET" class="filter-section" id="filterForm">
          <input type="text" name="search" class="form-control"
                 placeholder="Cari Nama / NIS / NPM..."
                 value="<?= htmlspecialchars($search); ?>">
          <button type="submit" class="btn btn-danger"><i class="bi bi-search"></i> Cari</button>
          <button type="button" id="resetBtn" class="btn btn-secondary"><i class="bi bi-arrow-repeat"></i> Reset</button>
        </form>

        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle text-center">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIS/NPM</th>
                <th>Unit</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Hari Kerja</th>
                <th>Hadir</th>
                <th>Tidak Hadir</th>
                <th>% Kehadiran</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                  $tgl_mulai = new DateTime($row['tgl_mulai']);
                  $tgl_selesai_asli = new DateTime($row['tgl_selesai']);
                  $today = new DateTime();

                  if ($today < $tgl_mulai) {
                    $hari_kerja = 0;
                  } else {
                    $tgl_selesai = ($today < $tgl_selesai_asli) ? $today : $tgl_selesai_asli;
                    $hari_kerja = 0;
                    $periode = clone $tgl_mulai;
                    while ($periode <= $tgl_selesai) {
                      if ($periode->format('N') < 6) $hari_kerja++;
                      $periode->modify('+1 day');
                    }
                  }

                  $hadir = (int)$row['total_hadir'];
                  $tidak = max(0, $hari_kerja - $hadir);
                  $persen = $hari_kerja > 0 ? round(($hadir / $hari_kerja) * 100, 2) : 0;
              ?>
              <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['nama']); ?></td>
                <td><?= htmlspecialchars($row['nis_npm']); ?></td>
                <td><?= htmlspecialchars($row['unit']); ?></td>
                <td><?= htmlspecialchars($row['tgl_mulai']); ?></td>
                <td><?= htmlspecialchars($row['tgl_selesai']); ?></td>
                <td><?= $hari_kerja; ?></td>
                <td><?= $hadir; ?></td>
                <td><?= $tidak; ?></td>
                <td><?= $persen; ?>%</td>
                <td>
                  <button class="btn btn-primary btn-sm btn-detail" data-userid="<?= $row['user_id']; ?>">
                    <i class="bi bi-eye"></i> Rincian
                  </button>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="11" class="text-muted text-center py-3">Tidak ada data peserta di unit Anda.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Rincian -->
  <div class="modal fade" id="modalRincian" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header" style="background-color:#cc0000;color:white;">
          <h5 class="modal-title fw-bold">Detail Rekap: <span id="rincianNama"></span></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div id="rincianContent">
            <div class="mb-3">
              <p><strong>Nama:</strong> <span id="rNama"></span></p>
              <p><strong>NIM/NIS:</strong> <span id="rNim"></span></p>
              <p><strong>Instansi:</strong> <span id="rInstansi"></span></p>
              <p><strong>Unit:</strong> <span id="rUnit"></span></p>
              <p><strong>Periode PKL:</strong> <span id="rPeriode"></span></p>
              <p><strong>Total Hari Kerja:</strong> <span id="rHariKerja"></span></p>
              <p><strong>Jumlah Hadir:</strong> <span id="rHadir"></span></p>
              <p><strong>Persentase Kehadiran:</strong> <span id="rPersen"></span>%</p>
            </div>
            <hr>
            <div class="table-responsive">
              <table class="table table-bordered text-center align-middle">
                <thead class="table-danger">
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
                <tbody id="rincianTabel">
                  <tr><td colspan="11" class="text-muted">Memuat data...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button id="btnExportPdf" class="btn btn-primary">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
  const modal = new bootstrap.Modal(document.getElementById('modalRincian'));
  let currentUserId = null; // ✅ simpan userId di variabel global

  // Tombol Detail
  document.querySelectorAll(".btn-detail").forEach(btn => {
    btn.addEventListener("click", async () => {
      const userId = btn.dataset.userid;
      currentUserId = userId; // ✅ simpan userId ke variabel global

      const tbody = document.getElementById("rincianTabel");
      tbody.innerHTML = '<tr><td colspan="11">Memuat data...</td></tr>';

      fetch('get_rincian_absen.php?user_id=' + userId)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            alert("Error: " + data.error);
            return;
          }

          modal.show();
          document.getElementById("rNama").innerText = data.nama;
          document.getElementById("rNim").innerText = data.nis_npm;
          document.getElementById("rInstansi").innerText = data.instansi_pendidikan || "-";
          document.getElementById("rUnit").innerText = data.unit;
          document.getElementById("rPeriode").innerText = `${data.tgl_mulai} s/d ${data.tgl_selesai}`;
          document.getElementById("rHariKerja").innerText = data.hari_kerja || "-";
          document.getElementById("rHadir").innerText = data.hadir || "-";
          document.getElementById("rPersen").innerText = data.persentase || "0";

          tbody.innerHTML = "";
          data.absensi.forEach((a, i) => {
            tbody.innerHTML += `
              <tr>
                <td>${i + 1}</td>
                <td>${a.tanggal || '-'}</td>
                <td>${a.jam_masuk || '-'}</td>
                <td>${a.aktivitas_masuk || '-'}</td>
                <td>${a.kendala_masuk || '-'}</td>
                <td>${a.kondisi_kesehatan || '-'}</td>
                <td>${a.lokasi_kerja || '-'}</td>
                <td>${a.aktivitas_keluar || '-'}</td>
                <td>${a.kendala_keluar || '-'}</td>
                <td>${a.jam_keluar || '-'}</td>
                <td>${a.foto_absen 
                  ? `<img src="../../uploads/absensi/${a.foto_absen}" width="60" class="rounded">`
                  : '-'}</td>
              </tr>`;
          });
        })
        .catch(err => {
          console.error("Gagal ambil data:", err);
          alert("Terjadi kesalahan saat mengambil data rincian!");
        });
    });
  });

  // Tombol reset
  document.getElementById("resetBtn").addEventListener("click", () => {
    window.location.href = "rekap_absensi.php";
  });

  // ✅ Tombol Export PDF
  document.getElementById("btnExportPdf").addEventListener("click", function() {
    if (!currentUserId) {
      alert("User ID tidak ditemukan! Silakan buka rincian peserta dulu.");
      return;
    }

    // arahkan ke file export PDF
    window.open(`export_rincian_pdf.php?user_id=${currentUserId}`, "_blank");
  });
});

  </script>
</body>
</html>
