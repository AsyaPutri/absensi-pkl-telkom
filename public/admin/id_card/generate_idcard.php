<?php
include "../../../includes/auth.php";
checkRole('admin');
include "../../../config/database.php";

if (!isset($_GET['id'])) {
  die("ID peserta tidak ditemukan.");
}

$user_id = intval($_GET['id']);

// Ambil data peserta
$sql_peserta = "
  SELECT 
    p.nama,
    p.email,
    u.nama_unit
  FROM peserta_pkl p
  LEFT JOIN unit_pkl u ON p.unit_id = u.id
  WHERE p.id = '$user_id'
";
$result_peserta = $conn->query($sql_peserta);

if (!$result_peserta || $result_peserta->num_rows == 0) {
  die("Data peserta tidak ditemukan.");
}

$data = $result_peserta->fetch_assoc();

// Ambil foto peserta berdasarkan email
$email = $conn->real_escape_string($data['email']);
$sql_foto = "SELECT upload_foto FROM daftar_pkl WHERE email = '$email' LIMIT 1";
$result_foto = $conn->query($sql_foto);
$row_foto = $result_foto->fetch_assoc();

$foto_path = "../../../uploads/Foto_daftarpkl/" . ($row_foto['upload_foto'] ?? '');
$foto_src = (file_exists($foto_path) && !empty($row_foto['upload_foto']))
  ? $foto_path
  : "../../assets/img/default_profile.png";

$template_path = "template/ID Card 3 Polosan.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>ID Card - <?= htmlspecialchars($data['nama']) ?></title>
  <style>
    @page {
      size: 54mm 90mm;
      margin: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: #f3f3f3;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      gap: 15px;
    }

    .id-card {
      width: 54mm;
      height: 90mm;
      position: relative;
      background: url('<?= $template_path ?>') no-repeat center center;
      background-size: cover;
      border-radius: 6px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.3);
      overflow: hidden;
    }

    .foto {
      position: absolute;
      top: 30mm;
      left: 60%;
      transform: translateX(-50%);
      width: 28mm;
      height: 28mm;
      border-radius: 100%;
      object-fit: cover;
      border: 2px solid #a00000;
      box-shadow: 0 0 6px rgba(107, 0, 0, 0.4);
    }
    
    /* ====== NAMA ====== */
    .nama {
      position: absolute;
      top: 62mm;
      left: 60%;
      transform: translateX(-50%);
      width: 100%;
      text-align: center;
      font-size: 2.5mm;
      font-weight: 600;
      color: #6b0000;
      text-transform: capitalize;
      line-height: 1.2;
      word-wrap: break-word;
      white-space: normal;
    }

    .garis {
      position: absolute;
      top: 67mm;
      left: 60%;
      transform: translateX(-50%);
      width: 29mm;
      height: 1.3px;
      background-color: #6b0000;
      border-radius: 2px;
      opacity: 0.9;
    }

    .unit {
      position: absolute;
      top: 68mm;
      left: 60%;
      transform: translateX(-50%);
      width: 40mm; /* batasi lebar supaya bisa turun ke baris baru */
      text-align: center;
      font-size: 2.5mm;
      font-weight: 700;
      color: #6b0000;
      text-transform: capitalize;
      line-height: 1.2;
      white-space: normal;
      word-wrap: break-word;
      overflow-wrap: break-word;
      hyphens: auto; /* bantu pemenggalan kata panjang */
    }

    .logo-telkom {
      position: absolute;
      top: 1mm;
      left: 56%;
      transform: translateX(-30%);
      width: 27mm; 
      height: auto;
    }

    .intern-title {
      position: absolute;
      top: 22mm; 
      left: 48.5%;
      transform: translateX(-26%);
      font-size: 4.5mm;
      font-weight: 800;
      color: #8B0000;
      text-align: center;
      text-transform: capitalize;
      letter-spacing: 0.2mm;
    }

    .bottom-text {
      position: absolute;
      bottom: 2mm; 
      left: 55%;
      transform: translateX(-45%);
      text-align: center;
      color: #8B0000;
      font-weight: 600;
      font-size: 9px;
      width: 100%;
    }

    #download-idcard {
      background-color: #6b0000;
      color: white;
      border: none;
      padding: 8px 16px;
      font-size: 14px;
      border-radius: 6px;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      transition: 0.2s;
      margin-top: 10px;
    }

    #download-idcard:hover {
      background-color: #a00000;
    }

    @media print {
      body {
        background: none;
        margin: 0;
      }
      .id-card {
        box-shadow: none;
      }
      #download-idcard {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="id-card" id="idCard">
    <img src="../../assets/img/logo_telkom.png" alt="Logo Telkom Indonesia" class="logo-telkom">
    <div class="intern-title"> I'm Intern!</div>
    <img src="<?= htmlspecialchars($foto_src) ?>" alt="Foto Peserta" class="foto">
    <div class="nama"><?= htmlspecialchars($data['nama']) ?></div>
    <div class="garis"></div>
    <div class="unit"><?= htmlspecialchars($data['nama_unit']) ?></div>
    <div class="bottom-text"> Witel Bekasi Karawang</div>
  </div>
  
  <button id="download-idcard">Download PNG</button>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script>
    document.querySelectorAll('.nama').forEach(el => {
      let nama = el.textContent.trim();
      const parts = nama.split(" ");
      if (parts.length > 3) {
        const last = parts.pop();
        const singkat = last.charAt(0).toUpperCase() + ".";
        nama = parts.join(" ") + " " + singkat;
      }
      el.textContent = nama;
    });

    // Fungsi download dengan kualitas HD
    document.getElementById('download-idcard').addEventListener('click', async function() {
      const el = document.getElementById('idCard');

      // Skala tinggi = hasil lebih tajam
      const scale = 8; // ubah ke 4 untuk hasil lebih HD

      const canvas = await html2canvas(el, {
        scale: scale,
        useCORS: true,
        allowTaint: false,
        backgroundColor: null,
        imageTimeout: 0,
        logging: false
      });

      const ctx = canvas.getContext("2d");
      ctx.imageSmoothingEnabled = true;
      ctx.imageSmoothingQuality = "high";

      const dataURL = canvas.toDataURL("image/png", 1.0);
      const link = document.createElement('a');
      const nama = (document.querySelector('.nama')?.textContent || 'idcard').trim().replace(/\s+/g, '-');
      link.href = dataURL;
      link.download = 'idcard-' + nama + '.png';
      link.click();
    });
  </script>
</body>
</html>