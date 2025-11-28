<?php
include "../../config/database.php";
include "../../includes/auth.php";
checkRole('admin');

$id      = $_POST['id'] ?? 0;
$kuota   = $_POST['kuota'] ?? null;
$jurusan = $_POST['jurusan'] ?? null;
$jobdesk = $_POST['jobdesk'] ?? null;
$lokasi  = $_POST['lokasi'] ?? null;

// Validasi ID
if ($id == 0) {
    $_SESSION['error'] = "ID unit tidak valid.";
    header("Location: kelola_unit.php");
    exit;
}

$stmt = $conn->prepare("
    UPDATE unit_pkl 
    SET kuota = ?, jurusan = ?, jobdesk = ?, lokasi = ?
    WHERE id = ?
");

$stmt->bind_param("isssi", $kuota, $jurusan, $jobdesk, $lokasi, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Kebutuhan unit berhasil diperbarui!";
} else {
    $_SESSION['error'] = "Gagal memperbarui kebutuhan unit.";
}

header("Location: kelola_unit.php");
exit;
?>
