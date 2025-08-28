<?php
include "../../includes/auth.php";
checkRole('magang');
?>
<h1>Dashboard Magang</h1>
<p>Halo, <?=$_SESSION['nama']?> 👋</p>
<a href="absensi.php">Absen Kehadiran</a> | <a href="riwayat.php">Riwayat Absensi</a>
