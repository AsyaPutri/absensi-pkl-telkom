<?php
include "../../includes/auth.php";
checkRole('admin');
include "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $conn->begin_transaction();

  try {
    // 1. Ambil data lengkap peserta gabungan dari peserta_pkl + daftar_pkl
    $sql = "
      SELECT 
        p.id AS peserta_id,
        p.user_id,
        p.unit_id,
        p.nama,
        p.nis_npm,
        p.email,
        p.instansi_pendidikan,
        p.jurusan,
        p.no_hp,
        p.tgl_mulai,
        p.tgl_selesai,
        p.status AS status_peserta,
        p.nomor_surat,

        -- Data tambahan dari daftar_pkl
        d.ipk_nilai_ratarata,
        d.semester,
        d.memiliki_laptop,
        d.bersedia_unit_manapun,
        d.skill,
        d.durasi,
        d.upload_surat_permohonan,
        d.upload_foto,
        d.upload_kartu_identitas,
        d.nomor_surat_permohonan,
        d.created_at

      FROM peserta_pkl p
      LEFT JOIN daftar_pkl d ON p.user_id = d.id
      WHERE p.id = ?
      LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare gagal (SELECT): " . $conn->error);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $res = $stmt->get_result();
    if ($res->num_rows === 0) throw new Exception("Peserta tidak ditemukan.");

    $row = $res->fetch_assoc();

    // 2. Update status peserta jadi 'selesai'
    $update = $conn->prepare("UPDATE peserta_pkl SET status = 'selesai' WHERE id = ?");
    if (!$update) throw new Exception("Prepare gagal (UPDATE): " . $conn->error);
    $update->bind_param("i", $id);
    $update->execute();

    // Simpan status selesai ke array
    $row['status_peserta'] = 'selesai';

    // 3. Insert data ke riwayat_peserta_pkl
    $ins = $conn->prepare("
      INSERT INTO riwayat_peserta_pkl (
        id, user_id, unit_id, nama, nis_npm, email, instansi_pendidikan, jurusan,
        ipk_nilai_ratarata, semester, memiliki_laptop, bersedia_unit_manapun,
        nomor_surat_permohonan, skill, durasi, no_hp, tgl_mulai, tgl_selesai, status,
        upload_surat_permohonan, upload_foto, upload_kartu_identitas, created_at
      )
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    if (!$ins) throw new Exception("Prepare gagal (INSERT): " . $conn->error);

    $ins->bind_param(
      "iiissssssssssssssssssss",
      $row['peserta_id'],
      $row['user_id'],
      $row['unit_id'],
      $row['nama'],
      $row['nis_npm'],
      $row['email'],
      $row['instansi_pendidikan'],
      $row['jurusan'],
      $row['ipk_nilai_ratarata'],
      $row['semester'],
      $row['memiliki_laptop'],
      $row['bersedia_unit_manapun'],
      $row['nomor_surat_permohonan'],
      $row['skill'],
      $row['durasi'],
      $row['no_hp'],
      $row['tgl_mulai'],
      $row['tgl_selesai'],
      $row['status_peserta'],
      $row['upload_surat_permohonan'],
      $row['upload_foto'],
      $row['upload_kartu_identitas'],
      $row['created_at']
    );
    $ins->execute();

    // 4. Commit transaksi
    $conn->commit();
    header("Location: peserta.php?success=1");
    exit;

  } catch (Exception $e) {
    $conn->rollback();
    error_log("Error ubah_status: " . $e->getMessage());
    echo "<pre style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
  }
} else {
  header("Location: peserta.php");
  exit;
}
?>
