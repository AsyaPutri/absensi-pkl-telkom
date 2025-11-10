<?php
include "../../includes/auth.php";
checkRole('mentor');
include "../../config/database.php";

// ============================
// Ambil unit mentor yang login
// ============================
$user_id = $_SESSION['user_id'];
$mentorQuery = $conn->query("SELECT unit_id FROM users WHERE id = '$user_id' LIMIT 1");
$mentorData = $mentorQuery->fetch_assoc();
$mentor_unit = $mentorData ? $mentorData['unit_id'] : '';

// ============================
// Ambil data unit (untuk filter jika mau tampil di dropdown)
// ============================
$units = [];
$unitQuery = $conn->query("SELECT id, nama_unit FROM unit_pkl ORDER BY nama_unit ASC");
while ($u = $unitQuery->fetch_assoc()) {
  $units[] = $u;
}

// ============================
// Ambil parameter filter
// ============================
$unit = isset($_GET['unit']) ? $_GET['unit'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ============================
// Query peserta & absensi (dibatasi unit mentor)
// ============================
$where = "WHERE p.unit_id = '$mentor_unit'";
if ($search !== '') $where .= " AND (p.nama LIKE '%$search%' OR p.nis_npm LIKE '%$search%')";

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

    .filter-section { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem; align-items: center; }
    .filter-section select, .filter-section input { max-width: 250px; }
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
        <h5><i class="bi bi-calendar-check me-2"></i> Data Absensi Internship</h5>
      </div>

      <div class="card-body">
        <form method="GET" class="filter-section" id="filterForm">
          <select name="unit" class="form-select">
            <option value="">-- Semua Unit --</option>
            <?php foreach ($units as $u): ?>
              <option value="<?= $u['id']; ?>" <?= ($unit == $u['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($u['nama_unit']); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <input type="text" name="search" class="form-control"
                 placeholder="Cari Nama / NIS / NPM..."
                 value="<?= htmlspecialchars($search); ?>"
                 onkeyup="if(event.key === 'Enter') this.form.submit();">

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
              if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):

                  // ===== Real-Time Perhitungan =====
                  $tgl_mulai = new DateTime($row['tgl_mulai']);
                  $tgl_selesai_asli = new DateTime($row['tgl_selesai']);
                  $today = new DateTime();

                  // jika hari ini belum melewati tanggal mulai
                  if ($today < $tgl_mulai) {
                    $hari_kerja = 0;
                  } else {
                    // ambil tanggal akhir = hari ini atau tanggal selesai (mana yang lebih dulu)
                    $tgl_selesai = ($today < $tgl_selesai_asli) ? $today : $tgl_selesai_asli;

                    // hitung hari kerja Senin-Jumat
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
              <tr><td colspan="11" class="text-muted text-center py-3">Tidak ada data ditemukan.</td></tr>
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
              <p><strong>Total Periode Kerja:</strong> <span id="rHariKerja"></span></p>
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

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const modal = new bootstrap.Modal(document.getElementById('modalRincian'));

  document.querySelectorAll(".btn-detail").forEach(btn => {
    btn.addEventListener("click", async () => {
      const userId = btn.dataset.userid;
      const tbody = document.getElementById("rincianTabel");
      tbody.innerHTML = '<tr><td colspan="11">Memuat data...</td></tr>';

      fetch('get_rincian_absen.php?user_id=' + userId)
        .then(response => response.text()) // ambil teks mentah untuk debug
        .then(text => {
          try {
            const data = JSON.parse(text);
            console.log("Data diterima:", data);

            if (data.error) {
              alert("Error: " + data.error);
              return;
            }

            if (!data.absensi || !Array.isArray(data.absensi)) {
              alert("Data absensi tidak ditemukan atau tidak valid!");
              console.error("Data tidak sesuai:", data);
              return;
            }

            // tampilkan modal
            modal.show();

            // isi data header
            document.getElementById("rNama").innerText = data.nama;
            document.getElementById("rNim").innerText = data.nis_npm;
            document.getElementById("rInstansi").innerText = data.instansi_pendidikan || "-";
            document.getElementById("rUnit").innerText = data.unit;
            document.getElementById("rPeriode").innerText = `${data.tgl_mulai} s/d ${data.tgl_selesai}`;
            document.getElementById("rHariKerja").innerText = data.hari_kerja || "-";
            document.getElementById("rHadir").innerText = data.hadir || "-";
            document.getElementById("rPersen").innerText = data.persentase || "0";

            // isi tabel rincian
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
                  <td>
                    ${a.foto_absen 
                      ? `<img src="../../uploads/absensi/${a.foto_absen}" width="60" class="rounded">`
                      : '-'}
                  </td>
                </tr>`;
            });
          } catch (err) {
            console.error("JSON Parse Error:", err, text);
            alert("Data dari server tidak valid JSON. Cek console untuk detail.");
          }
        })
        .catch(err => {
          console.error("Gagal memuat rincian:", err);
          alert("Terjadi kesalahan saat mengambil data rincian!");
        });
    });
  });

  // Tombol reset filter
  document.getElementById("resetBtn").addEventListener("click", () => {
    window.location.href = "rekap_absensi.php";
  });
});
</script>
</body>
</html>
