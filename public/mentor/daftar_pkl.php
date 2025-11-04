<?php
include "../../includes/auth.php";
include "../../config/database.php";

// ✅ Ambil data PKL dengan status pending
$query = "SELECT * FROM daftar_pkl WHERE status = 'pending' ORDER BY id DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
  die("<h3 style='color:red; text-align:center;'>Query gagal: " . mysqli_error($conn) . "</h3>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Data Pendaftar Internship - Mentor</title>
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
      margin: 0;
      padding: 0;
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
    .table thead th { background: var(--telkom-red); color: #fff; }
    .table-hover tbody tr:hover { background-color: #ffecec; }

    /* Modal styling */
    .modal-header {
      background-color: var(--telkom-red);
      color: white;
      border-bottom: none;
    }
    .modal-body label {
      font-weight: 600;
    }
    .modal-body .row {
      margin-bottom: 8px;
    }
  </style>
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="d-flex align-items-center">
    <img src="../assets/img/logo_telkom.png" alt="Telkom Logo">
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
    <div class="card-header">
      <h5><i class="bi bi-list-check me-2"></i> Daftar Pendaftar</h5>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Instansi</th>
            <th>Jurusan</th>
            <th>No HP</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1;
          while ($row = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['nama']); ?></td>
            <td><?= htmlspecialchars($row['instansi_pendidikan']); ?></td>
            <td><?= htmlspecialchars($row['jurusan']); ?></td>
            <td><?= htmlspecialchars($row['no_hp']); ?></td>
            <td><span class="badge bg-warning text-dark"><?= ucfirst($row['status']); ?></span></td>
            <td>
              <button class="btn btn-primary btn-sm" 
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
                data-unit="<?= htmlspecialchars($row['unit_id']); ?>"
                data-alamat="<?= htmlspecialchars($row['alamat']); ?>"
                data-periode="<?= htmlspecialchars($row['tgl_mulai']); ?> - <?= htmlspecialchars($row['tgl_selesai']); ?>"
                data-foto="<?= htmlspecialchars($row['upload_foto']); ?>"
                data-ktm="<?= htmlspecialchars($row['upload_kartu_identitas']); ?>"
                data-surat="<?= htmlspecialchars($row['upload_surat_permohonan']); ?>"
              >
                <i class="bi bi-eye"></i> Rincian
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Rincian -->
<div class="modal fade" id="rincianModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Rincian Peserta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <label>Nama:</label> <p id="r-nama"></p>
            <label>Email:</label> <p id="r-email"></p>
            <label>Instansi:</label> <p id="r-instansi"></p>
            <label>Jurusan:</label> <p id="r-jurusan"></p>
            <label>Semester:</label> <p id="r-semester"></p>
            <label>NIS/NPM:</label> <p id="r-nis"></p>
          </div>
          <div class="col-md-6">
            <label>IPK:</label> <p id="r-ipk"></p>
            <label>Nomor Surat:</label> <p id="r-nomorsurat"></p>
            <label>Skill:</label> <p id="r-skill"></p>
            <label>Unit:</label> <p id="r-unit"></p>
            <label>Alamat:</label> <p id="r-alamat"></p>
            <label>Periode:</label> <p id="r-periode"></p>
          </div>
        </div>
        <hr>
        <div class="d-flex justify-content-around mt-3">
          <a id="r-foto" class="btn btn-outline-primary" target="_blank">Foto Formal</a>
          <a id="r-ktm" class="btn btn-outline-primary" target="_blank">Kartu Pelajar / KTM</a>
          <a id="r-surat" class="btn btn-outline-primary" target="_blank">Surat Permohonan PKL</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const rincianModal = document.getElementById('rincianModal');
rincianModal.addEventListener('show.bs.modal', event => {
  const button = event.relatedTarget;
  document.getElementById('r-nama').textContent = button.getAttribute('data-nama');
  document.getElementById('r-email').textContent = button.getAttribute('data-email');
  document.getElementById('r-instansi').textContent = button.getAttribute('data-instansi');
  document.getElementById('r-jurusan').textContent = button.getAttribute('data-jurusan');
  document.getElementById('r-ipk').textContent = button.getAttribute('data-ipk');
  document.getElementById('r-semester').textContent = button.getAttribute('data-semester');
  document.getElementById('r-nis').textContent = button.getAttribute('data-nis');
  document.getElementById('r-nomorsurat').textContent = button.getAttribute('data-nomorsurat');
  document.getElementById('r-skill').textContent = button.getAttribute('data-skill');
  document.getElementById('r-unit').textContent = button.getAttribute('data-unit');
  document.getElementById('r-alamat').textContent = button.getAttribute('data-alamat');
  document.getElementById('r-periode').textContent = button.getAttribute('data-periode');
  document.getElementById('r-foto').href = '../../uploads/' + button.getAttribute('data-foto');
  document.getElementById('r-ktm').href = '../../uploads/' + button.getAttribute('data-ktm');
  document.getElementById('r-surat').href = '../../uploads/' + button.getAttribute('data-surat');
});
</script>

</body>
</html>
