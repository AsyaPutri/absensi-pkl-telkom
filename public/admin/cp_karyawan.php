<?php 
include "../../includes/auth.php"; 
checkRole('admin'); 
include "../../config/database.php";

// ================================
// Tambah / Edit CP Karyawan
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'] ?? '';
    $nama = $_POST['nama_karyawan'] ?? '';
    $posisi = $_POST['posisi'] ?? '';
    $nohp = $_POST['no_telepon'] ?? '';
    $unit_id = $_POST['unit_id'] ?? null;

    if (isset($_POST['edit_id']) && $_POST['edit_id'] != '') {
        // Update data
        $id = $_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE cp_karyawan 
                                SET nik=?, nama_karyawan=?, posisi=?, no_telepon=?, unit_id=? 
                                WHERE id=?");
        $stmt->bind_param("ssssii", $nik, $nama, $posisi, $nohp, $unit_id, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: cp_karyawan.php?updated=1");
        exit;
    } else {
        // Insert data baru
        $stmt = $conn->prepare("INSERT INTO cp_karyawan (nik, nama_karyawan, posisi, no_telepon, unit_id) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssssi", $nik, $nama, $posisi, $nohp, $unit_id);
        $stmt->execute();
        $stmt->close();
        header("Location: cp_karyawan.php?success=1");
        exit;
    }
}

// ================================
// Hapus Data
// ================================
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM cp_karyawan WHERE id=$id");
    header("Location: cp_karyawan.php?deleted=1");
    exit;
}

// ================================
// Ambil Data untuk Tabel
// ================================
$cpList = $conn->query("SELECT c.*, u.nama_unit 
                        FROM cp_karyawan c 
                        LEFT JOIN unit_pkl u ON c.unit_id = u.id 
                        ORDER BY c.id DESC");

// Ambil daftar unit yang belum dipakai CP (untuk dropdown)
$unitList = $conn->query("SELECT u.id, u.nama_unit 
                          FROM unit_pkl u 
                          LEFT JOIN cp_karyawan c ON u.id = c.unit_id 
                          WHERE c.unit_id IS NULL");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Manajemen CP Karyawan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #ffffff 0%, #ffe5e5 100%);
      font-family: 'Segoe UI', sans-serif;
    }
    .page-header {
      background-color: #cc0000;
      color: #fff;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
    }
    .card-header {
      font-weight: bold;
    }
    .btn-danger {
      background-color: #cc0000;
      border: none;
    }
    .btn-danger:hover {
      background-color: #990000;
    }
    .table thead {
      background-color: #cc0000;
      color: white;
    }
    .action-btn {
      display: flex;
      gap: 5px;
      justify-content: center;
    }
  </style>
</head>
<body>

<div class="container py-4">

  <!-- Header -->
  <div class="page-header d-flex justify-content-between align-items-center">
    <h3 class="mb-0"><i class="bi bi-telephone me-2"></i> Manajemen CP Karyawan</h3>
    <a href="dashboard.php" class="btn btn-light">
      <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Dashboard
    </a>
  </div>

  <!-- Notifikasi -->
  <?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Data CP berhasil ditambahkan.</div>
  <?php elseif(isset($_GET['updated'])): ?>
    <div class="alert alert-warning">✏️ Data CP berhasil diperbarui.</div>
  <?php elseif(isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">🗑️ Data CP berhasil dihapus.</div>
  <?php endif; ?>

  <!-- Form Tambah/Edit -->
  <div class="card mb-4 shadow">
    <div class="card-header bg-danger text-white">Tambah / Edit CP Karyawan</div>
    <div class="card-body">
      <form method="POST" id="cpForm">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="row g-3">
          <div class="col-md-2">
            <label class="form-label">NIK</label>
            <input type="text" name="nik" id="nik" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Nama Karyawan</label>
            <input type="text" name="nama_karyawan" id="nama_karyawan" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Posisi</label>
            <input type="text" name="posisi" id="posisi" class="form-control" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">No. Telepon</label>
            <input type="text" name="no_telepon" id="no_telepon" class="form-control" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Unit PKL</label>
            <select name="unit_id" id="unit_id" class="form-select" required>
              <option value="">-- Pilih Unit --</option>
              <?php while($u = $unitList->fetch_assoc()): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_unit']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="mt-3">
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-save me-1"></i> Simpan
          </button>
          <button type="reset" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i> Reset
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Data -->
  <div class="card shadow">
    <div class="card-header bg-danger text-white">Daftar CP Karyawan</div>
    <div class="card-body">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>NIK</th>
            <th>Nama</th>
            <th>Posisi</th>
            <th>No. Telepon</th>
            <th>Unit PKL</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $cpList->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nik']) ?></td>
            <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
            <td><?= htmlspecialchars($row['posisi']) ?></td>
            <td><?= htmlspecialchars($row['no_telepon']) ?></td>
            <td><?= htmlspecialchars($row['nama_unit'] ?? '-') ?></td>
            <td class="action-btn">
              <button class="btn btn-warning btn-sm" 
                      onclick="editData(<?= htmlspecialchars(json_encode($row)) ?>)">
                <i class="bi bi-pencil-square"></i>
              </button>
              <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin hapus data ini?')">
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

<script>
function editData(data) {
  document.getElementById('edit_id').value = data.id;
  document.getElementById('nik').value = data.nik;
  document.getElementById('nama_karyawan').value = data.nama_karyawan;
  document.getElementById('posisi').value = data.posisi;
  document.getElementById('no_telepon').value = data.no_telepon;
  document.getElementById('unit_id').value = data.unit_id;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

</body>
</html>
