<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";
include "daftar_pkl_action.php"; 

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar PKL - Admin</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{ --telkom-red:#cc0000; --telkom-dark:#990000; }
    body{ font-family:'Segoe UI',sans-serif; background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%); min-height:100vh; }
    .sidebar{ width:260px; min-height:100vh; background: linear-gradient(135deg,var(--telkom-red),var(--telkom-dark)); color:#fff; padding:1rem; position:fixed; top:0; left:0; z-index:1000; transition:left .3s;}
    .sidebar .nav-link{ color:#eee; border-radius:12px; padding:12px 16px; margin-bottom:6px; display:flex; align-items:center;}
    .sidebar .nav-link.active, .sidebar .nav-link:hover{ background:rgba(255,255,255,0.12); color:#fff !important; transform:translateX(5px); }
    .sidebar-overlay{ display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:900; }

    .main-content{ margin-left:260px; transition:margin-left .3s; min-height:100vh; }
    .header{ background:#fff; border-bottom:1px solid #eee; padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; }
    .telkom-logo{ height:80px; }

    .card{ border:none; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
    .table thead th{ background:var(--telkom-red); color:#fff; text-align:center; }
    .badge-pending{ background:#ffc107; color:#000; }
    .badge-berlangsung{ background:#28a745; }
    .badge-batal{ background:#dc3545; }

    @media(max-width:768px){
      .sidebar{ left:-260px; }
      .sidebar.active{ left:0; }
      .main-content{ margin-left:0; }
      .telkom-logo{ height:70px; }
    }
    @media (max-width: 576px) {
      form.d-flex select {
      flex: 1;
      min-width: 150px; }
    }
  </style>
</head>
<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="sidebar" id="sidebarMenu">
    <div class="text-center py-3">
      <i class="bi bi-person-circle fs-1 text-white"></i>
      <p class="fw-bold mb-0 text-white">Admin PKL</p>
      <small class="text-white-50">Telkom Witel Bekasi</small>
    </div>
    <hr class="text-white-50">
    <ul class="nav flex-column">
      <li><a href="dashboard.php" class="nav-link <?= ($current_page=='dashboard.php')?'active':'' ?>"><i class="bi bi-house-door me-2"></i> Beranda</a></li>
      <li><a href="daftar_pkl.php" class="nav-link <?= ($current_page=='daftar_pkl.php')?'active':'' ?>"><i class="bi bi-journal-text me-2"></i> Daftar PKL</a></li>
      <li><a href="peserta.php" class="nav-link <?= ($current_page=='peserta.php')?'active':'' ?>"><i class="bi bi-people me-2"></i> Data Peserta</a></li>
      <li><a href="absensi.php" class="nav-link <?= ($current_page=='absensi.php')?'active':'' ?>"><i class="bi bi-bar-chart-line me-2"></i> Rekap Absensi</a></li>
      <li><a href="riwayat_peserta.php" class="nav-link <?= ($current_page== 'riwayat_peserta.php') ?'active':'' ?> "><i class="bi bi-clock-history me-2"></i> Riwayat Peserta</a></li>
      <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </div>

  <div class="main-content">
    <div class="header">
      <div class="d-flex align-items-center">
        <button class="btn btn-outline-secondary d-md-none me-2" id="menuToggle"><i class="bi bi-list"></i></button>
        <div>
          <h4 class="mb-0 fw-bold text-danger">Data Daftar PKL</h4>
          <small class="text-muted">Sistem Manajemen Praktik kerja Lapangan</small>
        </div>
      </div>
      <img src="../assets/img/logo_telkom.png" class="telkom-logo" alt="Telkom Logo">
    </div>

    <div class="container-fluid p-4">
      <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= safe($_SESSION['success']); unset($_SESSION['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= safe($_SESSION['error']); unset($_SESSION['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

        <!-- Header: Tombol + Filter Status -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

        <!-- Tombol Tambah Pendaftar -->
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-person-plus me-2"></i> Tambah Pendaftar
          </button>

          <!-- Tombol Tambah Unit -->
          <button class="btn btn-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalTambahUnit">
            <i class="bi bi-building-add me-2"></i> Kelola Unit
          </button>
        </div>

        <!-- Filter + Pencarian -->
        <form method="get" class="d-flex flex-wrap align-items-center gap-2">
          <label for="filter_status" class="fw-semibold mb-0">Status:</label>
          <select name="filter_status" id="filter_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="all" <?= ($filter_status === 'all') ? 'selected' : '' ?>>Semua</option>
            <option value="pending" <?= ($filter_status === 'pending') ? 'selected' : '' ?>>Pending</option>
            <option value="diterima" <?= ($filter_status === 'diterima') ? 'selected' : '' ?>>Diterima</option>
            <option value="ditolak" <?= ($filter_status === 'ditolak') ? 'selected' : '' ?>>Ditolak</option>
          </select>

          <input type="text" name="search" class="form-control form-control-sm w-auto"
                placeholder="Cari Jurusan / Instansi"
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">

          <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-search"></i> Cari
          </button>

          <?php if ($filter_status !== 'all' || !empty($_GET['search'])): ?>
            <a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
          <?php endif; ?>
        </form>
      </div>

      <div class="card shadow-sm">
        <div class="card-header bg-white">
        <h5 class="fw-bold text-danger mb-0"><i class="bi bi-list-check me-2"></i> Daftar PKL</h5>
      </div>
      <div class="card">
        <div class="card-body table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead>
              <tr>
                <th style="width:60px">No</th>
                <th>Nama</th>
                <th>Instansi</th>
                <th>Jurusan</th>
                 <th>No HP</th>
                <th>Status</th>
                <th>Aksi</th>
                <th>Rincian</th>
                <th>Edit Data</th>
              </tr>
            </thead>
            <tbody>
              <?php if($result->num_rows>0){ $no=1; while($row=$result->fetch_assoc()): $id=(int)$row['id']; ?>
                <tr>
                  <td class="text-center"><?= $no++; ?></td>
                  <td><?= safe($row['nama']); ?></td>
                  <td><?= safe($row['instansi_pendidikan']); ?></td>
                  <td><?= safe($row['jurusan']); ?></td>
                  <td><?= safe($row['no_hp'] ?? $row['no_hp']); ?></td>
                  <td class="text-center">
                    <?php if($row['status']==='pending'): ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                      <?php elseif($row['status']==='diterima'): ?>
                        <span class="badge bg-success">Diterima</span>
                      <?php elseif($row['status']==='ditolak'): ?>
                        <span class="badge bg-danger">Ditolak</span>
                      <?php elseif($row['status']==='berlangsung'): ?>
                        <span class="badge bg-primary">Sedang Berlangsung</span>
                      <?php elseif($row['status']==='batal'): ?>
                        <span class="badge bg-secondary">Batal</span>
                      <?php else: ?>
                        <span class="badge bg-light text-dark">-</span>
                      <?php endif; ?>
                  </td>

                  <td class="text-center">
                    <!-- status buttons -->
                    <a href="daftar_pkl.php?id=<?= $row['id'] ?>&status=diterima<?= isset($filter_status) && $filter_status !== 'all' ? '&filter_status=' . urlencode($filter_status) : '' ?>" class="btn btn-success btn-sm">✔ Terima</a>
                    <a href="daftar_pkl.php?id=<?= $row['id'] ?>&status=ditolak<?= isset($filter_status) && $filter_status !== 'all' ? '&filter_status=' . urlencode($filter_status) : '' ?>" class="btn btn-danger btn-sm">❌ Tolak</a>
                  </td>
                  <!-- Detail -->
                  <td class="text-center">
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?= $id; ?>" title="Rincian">🔍</button>
                  </td>
                  <!-- Edit Data -->
                  <td class="text-center">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $id; ?>" title="Edit">✏️</button>
                    <a href="?delete=<?= $id; ?>" onclick="return confirm('Yakin hapus data ini?');" class="btn btn-dark btn-sm" title="Hapus">🗑️</a>
                  </td>
                </tr>

                <!-- Detail Modal -->
                <div class="modal fade" id="detailModal<?= $id; ?>" tabindex="-1">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Rincian Pendaftar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                          <div class="col-md-6">
                            <p><strong>Tanggal Daftar:</strong> <?= safe($row['tgl_daftar']); ?></p>
                            <p><strong>Nama:</strong> <?= safe($row['nama']); ?></p>
                            <p><strong>Email:</strong> <?= safe($row['email']); ?></p>
                            <p><strong>NIS/NPM:</strong> <?= safe($row['nis_npm']); ?></p>
                            <p><strong>No HP:</strong> <?= safe($row['no_hp']); ?></p>
                            <p><strong>Instansi:</strong> <?= safe($row['instansi_pendidikan']); ?></p>
                            <p><strong>Jurusan:</strong> <?= safe($row['jurusan']); ?></p>
                            <p><strong>IPK/Nilai Rata-Rata:</strong> <?= safe($row['ipk_nilai_ratarata']); ?></p>
                          </div>
                          <div class="col-md-6">
                            <p><strong>Semester/Tingkat Kelas:</strong> <?= safe($row['semester']); ?></p>
                            <p><strong>Skill:</strong> <?= safe($row['skill']); ?></p>
                            <p><strong>Unit:</strong> <?= safe($row['nama_unit']); ?></p>
                            <p><strong>Bersedia Unit Manapun:</strong> <?= safe($row['bersedia_unit_manapun']); ?></p>
                            <p><strong>Memiliki Laptop:</strong> <?= safe($row['memiliki_laptop']); ?></p>
                            <p><strong>Durasi:</strong> <?= safe($row['durasi']); ?></p>
                            <p><strong>Nomor Surat Permohonan PKL:</strong> <?= safe($row['nomor_surat_permohonan']); ?></p>
                          </div>
                        </div>
                        <hr>
                        <p><strong>Alamat:</strong> <?= safe($row['alamat']); ?></p>
                        <p><strong>Periode:</strong> <?= safe($row['tgl_mulai']); ?> — <?= safe($row['tgl_selesai']); ?></p>
                        <div class="row g-3">
                          <div class="col-md-4"><strong>Foto Formal</strong><br><?= $row['upload_foto'] ? '<a href="'. $foto_dir . safe($row['upload_foto']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
                          <div class="col-md-4"><strong>Kartu Peljar / KTM</strong><br><?= $row['upload_kartu_identitas'] ? '<a href="'. $ktm_dir . safe($row['upload_kartu_identitas']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
                          <div class="col-md-4"><strong>Surat Permohonan PKL </strong><br><?= $row['upload_surat_permohonan'] ? '<a href="'. $surat_dir . safe($row['upload_surat_permohonan']) .'" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat</a>' : '-' ; ?></div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $id; ?>" tabindex="-1">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $id; ?>">
                        <div class="modal-header bg-danger text-white">
                          <h5 class="modal-title">Edit Pendaftar</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="row g-3">
                            <div class="col-md-6">
                             <label class="form-label">Nama</label>
                             <input type="text" name="nama" class="form-control" value="<?= safe($row['nama']); ?>" required>
                            </div>
                          <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= safe($row['email']); ?>" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">NIS/NPM</label>
                            <input type="text" name="nis_npm" class="form-control" value="<?= safe($row['nis_npm']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= safe($row['no_hp']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Instansi Pendidikan</label>
                            <input type="text" name="instansi_pendidikan" class="form-control" value="<?= safe($row['instansi_pendidikan']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Jurusan</label>
                            <input type="text" name="jurusan" class="form-control" value="<?= safe($row['jurusan']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Semester/Tingkat Kelas</label>
                            <input type="text" name="semester" class="form-control" value="<?= safe($row['semester']); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">IPK / Nilai Rata-rata</label>
                            <input type="number" step="0.01" name="ipk_nilai_ratarata" class="form-control" value="<?= safe($row['ipk_nilai_ratarata'] ?? ''); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Memiliki Laptop?</label>
                            <select name="memiliki_laptop" class="form-select">
                              <option value="Ya" <?= $row['memiliki_laptop'] == 'Ya' ? 'selected' : ''; ?>>Ya</option>
                              <option value="Tidak" <?= $row['memiliki_laptop'] == 'Tidak' ? 'selected' : ''; ?>>Tidak</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Bersedia di Unit Manapun?</label>
                            <select name="bersedia_unit_manapun" class="form-select">
                              <option value="Bersedia" <?= $row['bersedia_unit_manapun'] == 'Bersedia' ? 'selected' : ''; ?>>Bersedia</option>
                              <option value="Tidak Bersedia" <?= $row['bersedia_unit_manapun'] == 'Tidak Bersedia' ? 'selected' : ''; ?>>Tidak Bersedia</option>
                            </select>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Nomor Surat Permohonan PKL</label>
                            <input type="text" name="nomor_surat_permohonan" class="form-control" value="<?= safe($row['nomor_surat_permohonan']); ?>">
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Skill</label>
                            <input type="text" name="skill" class="form-control" value="<?= safe($row['skill']); ?>">
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Durasi</label>
                            <select name="durasi" class="form-select" required>
                              <option value="">-- Pilih Durasi --</option>
                              <?php
                              $durasi_opsi = ["2 Bulan", "3 Bulan", "4 Bulan", "5 Bulan", "6 Bulan"];
                              foreach ($durasi_opsi as $dur) {
                                $selected = ($row['durasi'] == $dur) ? 'selected' : '';
                                echo "<option value='$dur' $selected>$dur</option>";
                              }
                              ?>
                            </select>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <div class="input-group">
                              <select name="unit_id" class="form-select">
                                <?php
                                $q_unit = $conn->query("SELECT * FROM unit_pkl ORDER BY nama_unit ASC");
                                while ($u = $q_unit->fetch_assoc()) {
                                  $selected = ($row['unit_id'] == $u['id']) ? 'selected' : '';
                                  echo "<option value='{$u['id']}' $selected>{$u['nama_unit']}</option>";
                                }
                                ?>
                              </select>
                            </div>
                          </div>

                          <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control"><?= safe($row['alamat']); ?></textarea>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control" value="<?= safe($row['tgl_mulai']); ?>">
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control" value="<?= safe($row['tgl_selesai']); ?>">
                          </div>

                          <div class="col-md-4">
                            <label class="form-label">Ganti Foto Formal (opsional)</label>
                            <input type="file" name="upload_foto" class="form-control">
                          </div>

                          <div class="col-md-4">
                            <label class="form-label">Ganti Kartu Pelajar / KTM (opsional)</label>
                            <input type="file" name="upload_kartu_identitas" class="form-control">
                          </div>

                          <div class="col-md-4">
                            <label class="form-label">Ganti Surat Permohonan PKL (opsional)</label>
                            <input type="file" name="upload_surat_permohonan" class="form-control">
                          </div>

                        </div>
                      </div>

                      <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      </div>

                    </form>
                  </div>
                </div>
              </div>


              <?php endwhile; } else { ?>
                <tr><td colspan="9" class="text-center text-muted">Belum ada data pendaftar</td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="insert">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i> Tambah Pendaftar PKL</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nama</label>
                <input type="text" name="nama" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">NIS / NPM</label>
                <input type="text" name="nis_npm" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">No HP</label>
                <input type="text" name="no_hp" class="form-control">
              </div>

              <div class="col-md-6">
                <label class="form-label">Instansi Pendidikan</label>
                <input type="text" name="instansi_pendidikan" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Jurusan</label>
                <input type="text" name="jurusan" class="form-control">
              </div>

              <div class="col-md-4">
                <label class="form-label">IPK / Nilai Rata-rata</label>
                <input type="number" step="0.01" name="ipk" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Semester</label>
                <input type="text" name="semester" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Nomor Surat Permohonan PKL</label>
                <input type="text" name="no_surat" class="form-control">
              </div>

              <div class="col-md-6">
                <label class="form-label">Skill</label>
                <input type="text" name="skill" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Durasi</label>
                  <select name="durasi" class="form-select" required>
                    <option value="">-- Pilih Durasi --</option>
                    <?php
                    $durasi_opsi = ["2 Bulan", "3 Bulan", "4 Bulan", "5 Bulan", "6 Bulan"];
                    foreach ($durasi_opsi as $dur) {
                      $selected = ($row['durasi'] == $dur) ? 'selected' : '';
                      echo "<option value='$dur' $selected>$dur</option>";
                    }
                    ?>
                  </select>
                </div>
              <div class="col-md-6">
                <label class="form-label">Mempunyai Laptop</label>
                <select name="memiliki_laptop" class="form-select">
                  <option value="">-- Pilih --</option>
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Bersedia Ditempatkan di Unit Mana Pun</label>
                <select name="bersedia_unit" class="form-select">
                  <option value="">-- Pilih --</option>
                  <option value="Bersedia">Bersedia</option>
                  <option value="Tidak Bersedia">Tidak Bersedia</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Unit</label>
                <div class="input-group">
                  <select name="unit_id" id="unitSelect" class="form-select" required>
                    <option value="">-- Pilih Unit --</option>
                    <?php
                    $q_unit = $conn->query("SELECT * FROM unit_pkl ORDER BY nama_unit ASC");
                    while ($u = $q_unit->fetch_assoc()) {
                      echo "<option value='{$u['id']}'>{$u['nama_unit']}</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control"></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="tgl_selesai" class="form-control" required>
              </div>

              <div class="col-md-4">
                <label class="form-label">Upload Foto Formal</label>
                <input type="file" name="upload_foto" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Upload Kartu Pelajar / KTM</label>
                <input type="file" name="upload_kartu_identitas" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Upload Surat Permohonan PKL</label>
                <input type="file" name="upload_surat_permohonan" class="form-control">
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<!-- ============================ -->
<!-- Modal Kelola Unit PKL -->
<!-- ============================ -->
<div class="modal fade" id="modalTambahUnit" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-building-add me-2"></i> Kelola Unit PKL</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- Form Tambah Unit -->
        <form method="POST" class="mb-4">
          <input type="hidden" name="action" value="insert_unit">
          <div class="input-group">
            <input type="text" name="nama_unit" class="form-control" placeholder="Nama Unit Baru" required>
            <button type="submit" class="btn btn-danger">
              <i class="bi bi-plus-circle"></i> Tambah
            </button>
          </div>
        </form>

        <!-- Daftar Unit -->
        <table class="table table-bordered align-middle">
          <thead class="table-danger text-center">
            <tr>
              <th style="width:50px;">No</th>
              <th>Nama Unit</th>
              <th style="width:150px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $unit_query = $conn->query("SELECT * FROM unit_pkl ORDER BY nama_unit ASC");
            $no = 1;
            while($u = $unit_query->fetch_assoc()):
            ?>
            <tr>
              <td class="text-center"><?= $no++; ?></td>
              <td><?= htmlspecialchars($u['nama_unit']); ?></td>
              <td class="text-center">
                <!-- Tombol Edit -->
                <button 
                  type="button" 
                  class="btn btn-primary btn-sm btn-edit"
                  data-id="<?= $u['id']; ?>"
                  data-nama="<?= htmlspecialchars($u['nama_unit']); ?>"
                  data-bs-toggle="modal"
                  data-bs-target="#editUnitModal">
                  <i class="bi bi-pencil"></i>
                </button>

                <!-- Tombol Hapus -->
                <a href="?delete_unit=<?= $u['id']; ?>" 
                   onclick="return confirm('Yakin ingin menghapus unit ini?');"
                   class="btn btn-dark btn-sm">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

      </div>
    </div>
  </div>
</div>

<!-- ============================ -->
<!-- Modal Edit Unit (Universal) -->
<!-- ============================ -->
<div class="modal fade" id="editUnitModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="update_unit">
        <input type="hidden" name="id" id="edit_id">

        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Edit Unit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <label for="edit_nama_unit" class="form-label">Nama Unit</label>
          <input type="text" name="nama_unit" id="edit_nama_unit" class="form-control" required>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================ -->
<!-- Script untuk Modal Edit -->
<!-- ============================ -->
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.getAttribute('data-id');
    const nama = btn.getAttribute('data-nama');
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_unit').value = nama;
  });
});
</script>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');

    if (menuToggle) {
      menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        menuToggle.style.display = sidebar.classList.contains('active') ? 'none' : 'inline-block';
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
        menuToggle.style.display = 'inline-block';
      });
    }

    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('active');
          overlay.style.display = 'none';
          menuToggle.style.display = 'inline-block';
        }
      });
    });
  </script>
</body>
</html>