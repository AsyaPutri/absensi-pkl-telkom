<?php
session_start();
include "../../config/database.php";
include "../../includes/auth.php";
checkRole('admin');

$action = $_POST['action'] ?? '';

/* =========================================
   TAMBAH UNIT
=========================================*/
if ($action === "tambah") {

    $nama   = $_POST['nama_unit'] ?? '';
    $kuota  = (int)($_POST['kuota'] ?? 0);
    $jur    = $_POST['jurusan'] ?? '';
    $job    = $_POST['jobdesk'] ?? '';
    $lok    = $_POST['lokasi'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO unit_pkl (nama_unit, kuota, jurusan, jobdesk, lokasi)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("SQL Error (Tambah): " . $conn->error);
    }

    $stmt->bind_param("sisss", $nama, $kuota, $jur, $job, $lok);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Unit berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambah unit: " . $stmt->error;
    }

    header("Location: kelola_unit.php");
    exit;
}

/* =========================================
   EDIT UNIT
=========================================*/
if ($action === "edit") {

    $id     = (int)($_POST['id'] ?? 0);
    $nama   = $_POST['nama_unit'] ?? '';
    $kuota  = (int)($_POST['kuota'] ?? 0);
    $jur    = $_POST['jurusan'] ?? '';
    $job    = $_POST['jobdesk'] ?? '';
    $lok    = $_POST['lokasi'] ?? '';

    $stmt = $conn->prepare("
        UPDATE unit_pkl 
        SET nama_unit=?, kuota=?, jurusan=?, jobdesk=?, lokasi=?
        WHERE id=?
    ");

    if (!$stmt) {
        die("SQL Error (Edit): " . $conn->error);
    }

    $stmt->bind_param("sisssi", $nama, $kuota, $jur, $job, $lok, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Unit berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Gagal memperbarui unit: " . $stmt->error;
    }

    header("Location: kelola_unit.php");
    exit;
}


/* =========================================
   HAPUS UNIT (FIX)
=========================================*/
if ($action === "hapus") {

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    // Debug jika ID tidak masuk
    if ($id <= 0) {
        die("ERROR: ID tidak valid. ID dikirim = " . htmlspecialchars($_POST['id'] ?? 'NULL'));
    }

    $stmt = $conn->prepare("DELETE FROM unit_pkl WHERE id=?");

    if (!$stmt) {
        die("SQL Error (Prepare): " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die("SQL Error (Execute): " . $stmt->error);
    }

    $_SESSION['success'] = "Unit berhasil dihapus!";
    header("Location: kelola_unit.php");
    exit;
}



// Jika action tidak sesuai
$_SESSION['error'] = "Aksi tidak valid.";
header("Location: kelola_unit.php");
exit;

?>
