<?php
require_once "../../includes/auth.php"; // sudah ada session_start
require_once "../../config/database.php";
checkRole('mentor');

// Pastikan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak valid.");
}

$unit_id = $_POST['unit_id'] ?? null;
$kuota   = $_POST['kuota'] ?? null;
$jurusan = $_POST['jurusan'] ?? null;
$jobdesk = $_POST['jobdesk'] ?? null;
$lokasi  = $_POST['lokasi'] ?? null;

// Validasi
if (!$unit_id || !$kuota || !$jurusan || !$jobdesk) {
    die("Semua field wajib diisi.");
}

// UPDATE ke tabel unit_pkl
$query = "
    UPDATE unit_pkl SET
        kuota = '$kuota',
        jurusan = '$jurusan',
        jobdesk = '$jobdesk',
        lokasi = '$lokasi'
    WHERE id = '$unit_id'
";

if ($conn->query($query)) {
    echo "<script>
        alert('Kebutuhan unit berhasil disimpan!');
        window.location.href = 'daftar_pkl.php';
    </script>";
} else {
    echo "Error: " . $conn->error;
}
