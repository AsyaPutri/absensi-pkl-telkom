<?php
include "../../includes/auth.php";
checkRole('magang');
?>
<h1>Form Absensi</h1>
<button onclick="getLocation()">Absen Sekarang</button>
<p id="status"></p>

<script>
function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(showPosition, showError);
  } else {
    document.getElementById("status").innerHTML = "Browser tidak mendukung GPS.";
  }
}

function showPosition(position) {
  let lat = position.coords.latitude;
  let lon = position.coords.longitude;

  document.getElementById("status").innerHTML = "Lokasi: " + lat + "," + lon;
}

function showError(error) {
  alert("Gagal ambil lokasi: " + error.message);
}
</script>
