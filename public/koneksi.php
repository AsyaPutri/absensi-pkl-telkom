<?php 
$conn = new mysqli("localhost", "root", "", "db_absensi");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

?>
