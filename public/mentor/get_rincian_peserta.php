<?php
include "../../config/database.php";

$email = $_GET['email'] ?? '';
if (!$email) {
  echo "<div class='text-danger text-center py-4'>Email tidak ditemukan.</div>";
  exit;
}

$q = "
SELECT d.*, u.nama_unit 
FROM daftar_pkl d
LEFT JOIN unit_pkl u ON d.unit_id = u.id
WHERE d.email = '$email'
LIMIT 1
";
$res = mysqli_query($conn, $q);

if (!$res || mysqli_num_rows($res) == 0) {
  echo "<div class='text-muted text-center py-4'>Data Detail tidak ditemukan.</div>";
  exit;
}

$data = mysqli_fetch_assoc($res);
?>

<div class="row">
  <div class="col-md-6">
    <p><b>Nama:</b> <?= htmlspecialchars($data['nama']); ?></p>
    <p><b>Email:</b> <?= htmlspecialchars($data['email']); ?></p>
    <p><b>Instansi:</b> <?= htmlspecialchars($data['instansi_pendidikan']); ?></p>
    <p><b>Jurusan:</b> <?= htmlspecialchars($data['jurusan']); ?></p>
    <p><b>Semester:</b> <?= htmlspecialchars($data['semester']); ?></p>
    <p><b>NIS/NPM:</b> <?= htmlspecialchars($data['nis_npm']); ?></p>
  </div>
  <div class="col-md-6">
    <p><b>IPK/ Nilai Rata-Rata:</b> <?= htmlspecialchars($data['ipk_nilai_ratarata']); ?></p>
    <p><b>Nomor Surat:</b> <?= htmlspecialchars($data['nomor_surat_permohonan']); ?></p>
    <p><b>Skill:</b> <?= htmlspecialchars($data['skill']); ?></p>
    <p><b>Unit:</b> <?= htmlspecialchars($data['nama_unit']); ?></p>
    <p><b>Alamat:</b> <?= htmlspecialchars($data['alamat']); ?></p>
    <p><b>Periode:</b> <?= htmlspecialchars($data['tgl_mulai']); ?> - <?= htmlspecialchars($data['tgl_selesai']); ?></p>
  </div>
</div>
<hr>
<div class="d-flex justify-content-around mt-3">
  <a href="../../uploads/Foto_daftarpkl/<?= htmlspecialchars($data['upload_foto']); ?>" target="_blank" class="btn btn-outline-primary">Foto Formal</a>
  <a href="../../uploads/Foto_Kartuidentitas/<?= htmlspecialchars($data['upload_kartu_identitas']); ?>" target="_blank" class="btn btn-outline-primary">Kartu Pelajar / KTM</a>
  <a href="../../uploads/Surat_Permohonan/<?= htmlspecialchars($data['upload_surat_permohonan']); ?>" target="_blank" class="btn btn-outline-primary">Surat Permohonan PKL</a>
</div>
