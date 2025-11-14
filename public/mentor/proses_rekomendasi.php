<?php
include "../../includes/auth.php";
include "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_peserta = $_POST['id_peserta'];
  $unit_tujuan = $_POST['unit_tujuan'];

  // Ambil nama unit dari tabel unit_pkl
  $query_unit = mysqli_query($conn, "SELECT nama_unit FROM unit_pkl WHERE id = '$unit_tujuan'");
  $unit_data = mysqli_fetch_assoc($query_unit);
  $nama_unit = $unit_data['nama_unit'];

  // Buat teks status baru
  $status_baru = "Direkomendasikan untuk Unit " . $nama_unit;

  // Update status
  $query_update = "UPDATE daftar_pkl 
                   SET status = '$status_baru' 
                   WHERE id = '$id_peserta'";

  if (mysqli_query($conn, $query_update)) {
    header("Location: daftar_pkl.php?msg=rekomendasi_berhasil");
    exit;
  } else {
    echo "Gagal update: " . mysqli_error($conn);
  }
}
?>
