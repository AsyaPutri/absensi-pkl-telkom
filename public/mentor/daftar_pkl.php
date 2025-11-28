<?php
include "../../includes/auth.php";
include "../../config/database.php";

// Ambil unit mentor jika belum ada di session
if (!isset($_SESSION['unit_mentor_id']) || !isset($_SESSION['unit_mentor_nama'])) {
    $email = $_SESSION['email'];

    $q = $conn->query("
        SELECT cp_karyawan.unit_id, unit_pkl.nama_unit 
        FROM cp_karyawan 
        LEFT JOIN unit_pkl ON cp_karyawan.unit_id = unit_pkl.id
        WHERE cp_karyawan.email = '$email'
        LIMIT 1
    ");

    if ($q && $q->num_rows > 0) {
        $d = $q->fetch_assoc();
        $_SESSION['unit_mentor_id'] = $d['unit_id'];
        $_SESSION['unit_mentor_nama'] = $d['nama_unit'];
    } else {
        $_SESSION['unit_mentor_id'] = '';
        $_SESSION['unit_mentor_nama'] = 'Unit Tidak Ditemukan';
    }
}


// ✅ Ambil data PKL dengan JOIN ke tabel unit_pkl
$query = "
SELECT d.*, u.nama_unit 
FROM daftar_pkl d
LEFT JOIN unit_pkl u ON d.unit_id = u.id
WHERE d.status = 'pending'
ORDER BY d.id DESC
";
$result = mysqli_query($conn, $query);

// 🔹 Ambil daftar unit untuk dropdown rekomendasi
$unitQuery = mysqli_query($conn, "SELECT * FROM unit_pkl ORDER BY nama_unit ASC");

if (!$result) {
  die("<h3 style='color:red; text-align:center;'>Query gagal: " . mysqli_error($conn) . "</h3>");
}

// Ambil data kebutuhan unit berdasarkan unit mentor
$unit_id = $_SESSION['unit_mentor_id'];
$kebutuhan = mysqli_query($conn, "SELECT * FROM unit_pkl WHERE id='$unit_id'");
$data_kebutuhan = mysqli_fetch_assoc($kebutuhan);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Data Pendaftar InStep</title>
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
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .header img { height: 90px; }
    .header .title h4 { color: var(--telkom-red); font-weight: 700; margin-bottom: 4px; }
    .header .title small { color: #6c757d; }
    .back-btn {
      background: var(--telkom-red);
      color: #fff;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 500;
      transition: all .3s;
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

    .modal-header {
      background-color: var(--telkom-red);
      color: white;
      border-bottom: none;
    }
    .modal-body label { font-weight: 600; }
    .modal-body .row { margin-bottom: 8px; }

    .btn-outline-primary {
      border-color: var(--telkom-red);
      color: var(--telkom-red);
      font-weight: 600;
    }
    .btn-outline-primary:hover {
      background: var(--telkom-red);
      color: #fff;
    }

    .search-box {
      position: relative;
      width: 280px;
      margin-bottom: 10px;
    }
    .search-box input {
      border-radius: 30px;
      padding: 8px 35px 8px 35px;
      border: 1.5px solid #ddd;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
    }
    .search-box input:focus {
      border-color: var(--telkom-red);
      box-shadow: 0 0 6px rgba(204,0,0,0.3);
      outline: none;
    }
    .search-box i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--telkom-red);
      font-size: 1rem;
    }
    .no-data {
      text-align: center;
      color: #777;
      font-style: italic;
      background-color: #fff5f5;
    }

    /* =====================================================
              RESPONSIVE FIX — NO DESIGN CHANGE
===================================================== */

/* HP kecil */
@media (max-width: 576px) {

  /* Header rapi */
  .header {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }

  .header img {
    height: 70px !important;
  }

  .title h4 {
    font-size: 1.1rem !important;
  }

  /* Tombol kembali jadi full layar */
  .back-btn {
    width: 100%;
    justify-content: center;
  }

  /* Search box penuh */
  .search-box {
    width: 100% !important;
  }

  /* Tabel lebih kecil di HP */
  table {
    font-size: 0.8rem;
  }

  .table thead th,
  .table tbody td {
    white-space: nowrap;
    padding: 6px;
  }

  /* Modal supaya tidak keluar layar */
  .modal-dialog {
    max-width: 95% !important;
    margin: auto;
  }
}

/* Tablet kecil */
@media (max-width: 768px) {

  .header {
    flex-wrap: wrap;
    justify-content: center;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .search-box {
    width: 100%;
  }
}

/* Tablet sedang */
@media (max-width: 992px) {
  table {
    font-size: 0.9rem;
  }
}

/* Hindari geser kanan kiri */
html, body {
  overflow-x: hidden !important;
}

  </style>
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="d-flex align-items-center">
    <img src="../assets/img/InStep.png" alt="InStep Logo">
    <div class="title ms-3">
      <h4>Data Pendaftar Internship</h4>
      <small>Sistem Monitoring Internship | Telkom Witel Bekasi - Karawang</small>
    </div>
  </div>
  <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
</div>

<!-- Konten -->
<div class="container-fluid mt-4">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
      <h5><i class="bi bi-list-check me-2"></i> Data Pendaftar Internship</h5>

      <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Search...">
      </div>
      <button class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#kebutuhanModal">
        <i class="bi bi-gear"></i> Input Kebutuhan Unit
      </button>
    </div>

    <div class="card-body table-responsive">
      <table id="dataTable" class="table table-bordered table-hover align-middle text-center">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Instansi</th>
            <th>Jurusan</th>
            <th>Unit</th>
            <th>No HP</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($result) == 0): ?>
            <tr><td colspan="8" class="no-data py-4"><i class="bi bi-inbox" style="font-size: 1.5rem;"></i><br>Tidak ada data ditemukan.</td></tr>
          <?php else: $no=1; while($row=mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= htmlspecialchars($row['nama']); ?></td>
              <td><?= htmlspecialchars($row['instansi_pendidikan']); ?></td>
              <td><?= htmlspecialchars($row['jurusan']); ?></td>
              <td><?= htmlspecialchars($row['nama_unit'] ?? '-'); ?></td>
              <td><?= htmlspecialchars($row['no_hp']); ?></td>
              <td><span class="badge bg-warning text-dark"><?= ucfirst($row['status']); ?></span></td>
              <td>
                <!-- Tombol Detail -->
                <button class="btn btn-primary btn-sm me-1"
                  data-bs-toggle="modal"
                  data-bs-target="#rincianModal"
                  data-nama="<?= htmlspecialchars($row['nama']); ?>"
                  data-email="<?= htmlspecialchars($row['email']); ?>"
                  data-instansi="<?= htmlspecialchars($row['instansi_pendidikan']); ?>"
                  data-jurusan="<?= htmlspecialchars($row['jurusan']); ?>"
                  data-ipk="<?= htmlspecialchars($row['ipk_nilai_ratarata']); ?>"
                  data-semester="<?= htmlspecialchars($row['semester']); ?>"
                  data-nis="<?= htmlspecialchars($row['nis_npm']); ?>"
                  data-nomorsurat="<?= htmlspecialchars($row['nomor_surat_permohonan']); ?>"
                  data-skill="<?= htmlspecialchars($row['skill']); ?>"
                  data-unit="<?= htmlspecialchars($row['nama_unit']); ?>"
                  data-alamat="<?= htmlspecialchars($row['alamat']); ?>"
                  data-periode="<?= htmlspecialchars($row['tgl_mulai']); ?> - <?= htmlspecialchars($row['tgl_selesai']); ?>"
                  data-foto="<?= htmlspecialchars($row['upload_foto']); ?>"
                  data-ktm="<?= htmlspecialchars($row['upload_kartu_identitas']); ?>"
                  data-surat="<?= htmlspecialchars($row['upload_surat_permohonan']); ?>">
                  <i class="bi bi-eye"></i> Detail
                </button>

                <!-- Tombol Rekomendasi -->
                <button 
                  class="btn btn-success btn-sm btn-rekomendasi"
                  data-id="<?= $row['id']; ?>"
                  data-nama="<?= htmlspecialchars($row['nama']); ?>">
                  <i class="bi bi-hand-thumbs-up"></i> Rekomendasi
                </button>
              </td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Kebutuhan Unit -->
<div class="modal fade" id="kebutuhanModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <form action="proses_kebutuhan.php" method="POST">

        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Atur Kebutuhan Unit</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <!-- Unit -->
          <div class="mb-3">
            <label class="form-label">Unit</label>
            <input type="text" class="form-control" 
                value="<?= $_SESSION['unit_mentor_nama'] ?>" disabled>

            <input type="hidden" name="unit_id" 
                value="<?= $_SESSION['unit_mentor_id'] ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Kuota Dibutuhkan</label>
            <input type="number" class="form-control" name="kuota" 
                value="<?= $data_kebutuhan['kuota'] ?? '' ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Jurusan yang Diterima</label>
            <input type="text" class="form-control" name="jurusan"
                value="<?= $data_kebutuhan['jurusan'] ?? '' ?>"
                placeholder="Contoh: Sistem Informasi, Manajemen" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Jobdesk</label>
            <textarea class="form-control" name="jobdesk" rows="3" required><?= $data_kebutuhan['jobdesk'] ?? '' ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Lokasi</label>
            <input type="text" class="form-control" name="lokasi"
                value="<?= $data_kebutuhan['lokasi'] ?? '' ?>" 
                placeholder="Contoh: Witel Bekasi Karawang">
          </div>

        </div>

        <div class="modal-footer">
          <button class="btn btn-danger" type="submit">
            <i class="bi bi-save"></i> Simpan Kebutuhan
          </button>
        </div>

      </form>

    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Modal Detail
const rincianModal = document.getElementById('rincianModal');
rincianModal.addEventListener('show.bs.modal', event => {
  const b = event.relatedTarget;
  document.getElementById('r-nama').textContent = b.getAttribute('data-nama');
  document.getElementById('r-email').textContent = b.getAttribute('data-email');
  document.getElementById('r-instansi').textContent = b.getAttribute('data-instansi');
  document.getElementById('r-jurusan').textContent = b.getAttribute('data-jurusan');
  document.getElementById('r-ipk').textContent = b.getAttribute('data-ipk');
  document.getElementById('r-semester').textContent = b.getAttribute('data-semester');
  document.getElementById('r-nis').textContent = b.getAttribute('data-nis');
  document.getElementById('r-nomorsurat').textContent = b.getAttribute('data-nomorsurat');
  document.getElementById('r-skill').textContent = b.getAttribute('data-skill');
  document.getElementById('r-unit').textContent = b.getAttribute('data-unit');
  document.getElementById('r-alamat').textContent = b.getAttribute('data-alamat');
  document.getElementById('r-periode').textContent = b.getAttribute('data-periode');
  document.getElementById('r-foto').href = '../../uploads/Foto_daftarpkl/' + b.getAttribute('data-foto');
  document.getElementById('r-ktm').href = '../../uploads/Foto_Kartuidentitas/' + b.getAttribute('data-ktm');
  document.getElementById('r-surat').href = '../../uploads/Surat_Permohonan/' + b.getAttribute('data-surat');
});


// 🔍 Search
document.getElementById("searchInput").addEventListener("keyup", function() {
  const value = this.value.toLowerCase();
  const rows = document.querySelectorAll("#dataTable tbody tr");
  rows.forEach(row => {
    const nama = row.cells[1].textContent.toLowerCase();
    const instansi = row.cells[2].textContent.toLowerCase();
    const jurusan = row.cells[3].textContent.toLowerCase();
    const unit = row.cells[4].textContent.toLowerCase();
    row.style.display = (nama.includes(value) || instansi.includes(value) || jurusan.includes(value) || unit.includes(value)) ? "" : "none";
  });
});

document.querySelectorAll('.btn-rekomendasi').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('id_peserta').value = this.dataset.id;
        document.getElementById('nama_peserta').textContent = this.dataset.nama;
        new bootstrap.Modal(document.getElementById('rekomModal')).show();
    });
});
</script>
</body>
</html>
