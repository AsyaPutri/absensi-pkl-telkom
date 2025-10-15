<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

// pastikan tombol diklik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);

  // ubah status peserta jadi selesai
  $sql = "UPDATE peserta_pkl SET status = 'selesai' WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    // kalau sukses, arahkan balik ke halaman peserta dengan pesan sukses
    header("Location: peserta.php?success=1");
    exit;
  } else {
    // kalau gagal
    header("Location: peserta.php?error=1");
    exit;
  }
} else {
  header("Location: peserta.php");
  exit;
}
