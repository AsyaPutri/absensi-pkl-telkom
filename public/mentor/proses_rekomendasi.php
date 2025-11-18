<?php
// ========== proses_rekomendasi.php ==========
// Lokasi file: public/mentor/proses_rekomendasi.php
// Pastikan path include relatif benar terhadap file ini.

// ============================
// Include Auth & Database
// ============================
// Jangan panggil session_start() di sini kalau includes/auth.php sudah memanggilnya.
include "../../includes/auth.php";
include "../../config/database.php";

// ============================
// Validasi input POST
// ============================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Hanya terima POST
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['error'] = "ID peserta tidak ditemukan.";
    $back = $_SERVER['HTTP_REFERER'] ?? 'daftar_pkl.php';
    header("Location: $back");
    exit;
}

$id = (int) $_POST['id']; // sanitasi

// ============================
// Pastikan mentor sudah login
// (includes/auth.php biasanya sudah set $_SESSION['email'] atau user_id)
// ============================
if (!isset($_SESSION['email'])) {
    $_SESSION['error'] = "Session mentor tidak ditemukan. Silakan login ulang.";
    $back = $_SERVER['HTTP_REFERER'] ?? 'daftar_pkl.php';
    header("Location: $back");
    exit;
}

$mentor_email = $_SESSION['email'];

// ============================
// Ambil unit mentor dari cp_karyawan (menggunakan mysqli $conn)
// ============================
$stmt = $conn->prepare("SELECT unit_id FROM cp_karyawan WHERE email = ? LIMIT 1");
if (!$stmt) {
    $_SESSION['error'] = "Query gagal (prepare): " . $conn->error;
    $back = $_SERVER['HTTP_REFERER'] ?? 'daftar_pkl.php';
    header("Location: $back");
    exit;
}
$stmt->bind_param("s", $mentor_email);
$stmt->execute();
$res = $stmt->get_result();
$mentor = $res->fetch_assoc();
$stmt->close();

if (!$mentor) {
    $_SESSION['error'] = "Data mentor tidak ditemukan di tabel cp_karyawan.";
    $back = $_SERVER['HTTP_REFERER'] ?? 'daftar_pkl.php';
    header("Location: $back");
    exit;
}

$unitMentor = $mentor['unit_id'] !== null ? (int)$mentor['unit_id'] : null;

// ============================
// Update status peserta + unit rekomendasi (mysqli)
// ============================
$updateStmt = $conn->prepare("UPDATE daftar_pkl SET status = ?, unit_id = ? WHERE id = ?");
if (!$updateStmt) {
    $_SESSION['error'] = "Gagal menyiapkan query update: " . $conn->error;
    $back = $_SERVER['HTTP_REFERER'] ?? 'daftar_pkl.php';
    header("Location: $back");
    exit;
}

$status = 'request';

// jika unitMentor null, bind_param perlu integer; kita set 0 atau NULL.
// MySQLi tidak langsung mendukung binding NULL via bind_param dengan tipe 'i'.
// Kita akan gunakan conditional: jika null -> pakai prepared query yang meng-set unit_id = NULL

if ($unitMentor === null) {
    // gunakan query yang set unit_id = NULL
    $updateStmt->close();
    $updateStmt = $conn->prepare("UPDATE daftar_pkl SET status = ?, unit_id = NULL WHERE id = ?");
    if (!$updateStmt) {
        $_SESSION['error'] = "Gagal menyiapkan query update (NULL): " . $conn->error;
        $back = $_SERVER['HTTP_REFERER'] ?? 'daftar_pkl.php';
        header("Location: $back");
        exit;
    }
    $updateStmt->bind_param("si", $status, $id);
} else {
    $updateStmt->bind_param("sii", $status, $unitMentor, $id);
}

$ok = $updateStmt->execute();
if (!$ok) {
    $_SESSION['error'] = "Gagal mengupdate data: " . $updateStmt->error;
    $updateStmt->close();
    $back = $_SERVER['HTTP_REFERER'] ?? 'daftar_pkl.php';
    header("Location: $back");
    exit;
}
$updateStmt->close();

// ============================
// Sukses -> set pesan dan redirect kembali
// ============================
$_SESSION['success'] = "Rekomendasi terkirim. Peserta telah direkomendasikan ke unit mentor.";
$back = $_SERVER['HTTP_REFERER'] ?? 'daftar_pkl.php';
header("Location: $back");
exit;
