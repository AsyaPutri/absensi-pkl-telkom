<?php
include "../../includes/auth.php";
checkRole('admin');
include "template/sidebar.php";
?>
<div style="margin-left:240px; padding:20px;">
  <h1>Beranda Admin</h1>
  <p>Halo, <?=$_SESSION['nama']?> 👋</p>
</div>
