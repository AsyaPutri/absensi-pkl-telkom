<?php
include "../../includes/auth.php"; 
checkRole('admin');
include "../../config/database.php";

// Ambil semua unit
$units = $conn->query("SELECT * FROM unit_pkl ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Kelola Unit PKL</title>

<!-- TailwindCSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Warna custom Telkom -->
<style>
    :root {
        --telkom-red: #EE1C25;
        --telkom-dark: #B31217;
        --bg-light: #F5F5F5;
    }
</style>

</head>
<body class="bg-gray-100 min-h-screen">

<!-- HEADER -->
<header class="p-6 bg-white shadow flex justify-between items-center sticky top-0 z-40">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Kelola Unit PKL</h1>
        <p class="text-sm text-gray-500 mt-1">Admin • InStep Telkom Witel Bekasi</p>
    </div>

    <div class="flex items-center gap-3">
        <a href="dashboard.php" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 text-sm">
            ← Kembali
        </a>

        <button onclick="openModal('modalTambah')" 
            class="px-5 py-2 bg-[var(--telkom-red)] text-white rounded-lg hover:bg-[var(--telkom-dark)] shadow">
            + Tambah Unit
        </button>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="p-6">

    <!-- Notifikasi -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="mb-4 p-4 rounded bg-green-100 border border-green-300 text-green-900 shadow">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="mb-4 p-4 rounded bg-red-100 border border-red-300 text-red-900 shadow">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- GRID UNIT -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        <?php while ($row = $units->fetch_assoc()):
            $id        = $row['id'];
            $nama      = $row['nama_unit'] ?? '';
            $deskripsi = $row['deskripsi'] ?? '';
            $kuota     = $row['kuota'] ?? '';
            $jurusan   = $row['jurusan'] ?? '';
            $lokasi    = $row['lokasi'] ?? '';
            $jobdesk   = $row['jobdesk'] ?? '';
        ?>
        <div class="bg-white shadow-md rounded-2xl p-6 border border-gray-200 hover:shadow-xl transition">

            <h3 class="text-xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($nama); ?></h3>
            <p class="text-gray-600 text-sm mb-4">
                <?= $deskripsi !== '' ? htmlspecialchars($deskripsi) : '<span class="text-gray-400">Tidak ada deskripsi</span>'; ?>
            </p>

            <!-- Info kebutuhan -->
            <div class="text-sm space-y-2 mb-5">
                <p><span class="font-semibold">Kuota:</span> <?= $kuota !== '' ? htmlspecialchars($kuota) : "-" ?></p>
                <p><span class="font-semibold">Jurusan:</span> <?= $jurusan !== '' ? htmlspecialchars($jurusan) : "-" ?></p>
                <p><span class="font-semibold">Lokasi:</span> <?= $lokasi !== '' ? htmlspecialchars($lokasi) : "-" ?></p>
            </div>

            <div class="grid grid-cols-3 gap-2 text-sm">

                <button 
                    class="bg-yellow-400 text-black px-3 py-2 rounded-lg hover:bg-yellow-500"
                    onclick='openEditUnit(<?= json_encode([
                        "id"=>$id,
                        "nama_unit"=>$nama,
                        "deskripsi"=>$deskripsi
                    ], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'>
                    Edit
                </button>

                <button 
                    class="bg-[var(--telkom-red)] text-white px-3 py-2 rounded-lg hover:bg-[var(--telkom-dark)]"
                    onclick='openKebutuhan(<?= json_encode([
                        "id"=>$id,
                        "kuota"=>$kuota,
                        "jurusan"=>$jurusan,
                        "jobdesk"=>$jobdesk,
                        "lokasi"=>$lokasi
                    ], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'>
                    Kebutuhan
                </button>

                <button 
                    onclick='openDelete(<?= $row["id"] ?>, "<?= addslashes($row["nama_unit"]) ?>")'
                    class="px-3 py-1 bg-red-600 text-white rounded">
                    Hapus
                </button>

            </div>

        </div>
        <?php endwhile; ?>

    </div>
</div>

<!-- ==========================
        MODAL TAMBAH UNIT
========================== -->
<div id="modalTambah" class="hidden fixed inset-0 bg-black/40 z-50 flex justify-center items-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-lg">

        <h2 class="text-xl font-bold mb-4 text-gray-800">Tambah Unit PKL</h2>

        <form action="proses_unit.php" method="POST">
            <input type="hidden" name="action" value="tambah">

            <div class="mb-3">
                <label class="font-semibold block mb-1">Nama Unit</label>
                <input type="text" name="nama_unit" class="w-full border rounded-lg p-2" required>
            </div>

            <div class="mb-3">
                <label class="font-semibold block mb-1">Deskripsi</label>
                <textarea name="deskripsi" class="w-full border rounded-lg p-2" rows="3"></textarea>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('modalTambah')" class="px-4 py-2 bg-gray-300 rounded-lg">Batal</button>
                <button class="px-4 py-2 bg-[var(--telkom-red)] text-white rounded-lg hover:bg-[var(--telkom-dark)]">Simpan</button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================
        MODAL EDIT UNIT
========================== -->
<div id="modalEdit" class="hidden fixed inset-0 bg-black/40 z-50 flex justify-center items-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-lg">

        <h2 class="text-xl font-bold mb-4 text-gray-800">Edit Unit PKL</h2>

        <form action="proses_unit.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">

            <div class="mb-3">
                <label class="font-semibold block mb-1">Nama Unit</label>
                <input type="text" id="edit_nama" name="nama_unit" class="w-full border rounded-lg p-2" required>
            </div>

            <div class="mb-3">
                <label class="font-semibold block mb-1">Deskripsi</label>
                <textarea id="edit_deskripsi" name="deskripsi" class="w-full border rounded-lg p-2" rows="3"></textarea>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('modalEdit')" class="px-4 py-2 bg-gray-300 rounded-lg">Batal</button>
                <button class="px-4 py-2 bg-yellow-400 rounded-lg hover:bg-yellow-500">Update</button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================
      MODAL KELOLA KEBUTUHAN
========================== -->
<div id="modalKebutuhan" class="hidden fixed inset-0 bg-black/40 z-50 flex justify-center items-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-lg">

        <h2 class="text-xl font-bold mb-4 text-gray-800">Atur Kebutuhan Unit</h2>

        <form action="proses_kebutuhan.php" method="POST">

            <input type="hidden" id="kb_id" name="id">

            <div class="mb-3">
                <label class="font-semibold block mb-1">Kuota Dibutuhkan</label>
                <input type="number" id="kb_kuota" name="kuota" class="w-full border rounded-lg p-2" min="0" required>
            </div>

            <div class="mb-3">
                <label class="font-semibold block mb-1">Jurusan yang Diterima</label>
                <input type="text" id="kb_jurusan" name="jurusan" class="w-full border rounded-lg p-2" placeholder="Contoh: SI, TI, TKJ" required>
            </div>

            <div class="mb-3">
                <label class="font-semibold block mb-1">Jobdesk</label>
                <textarea id="kb_jobdesk" name="jobdesk" class="w-full border rounded-lg p-2" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label class="font-semibold block mb-1">Lokasi</label>
                <input type="text" id="kb_lokasi" name="lokasi" class="w-full border rounded-lg p-2">
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('modalKebutuhan')" class="px-4 py-2 bg-gray-300 rounded-lg">Batal</button>
                <button class="px-4 py-2 bg-[var(--telkom-red)] text-white rounded-lg hover:bg-[var(--telkom-dark)]">Simpan</button>
            </div>

        </form>

    </div>
</div>

<!-- Modal Delete -->
<div id="modalDelete" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-96 shadow-xl">
        <h2 class="text-xl font-semibold mb-3 text-red-600">Hapus Unit</h2>

        <p>Yakin ingin menghapus unit <span class="font-bold text-red-600" id="delete_name"></span>?</p>

        <form action="proses_unit.php" method="POST" class="mt-4">
            <input type="hidden" name="action" value="hapus">
            <input type="hidden" id="delete_id" name="id">

            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="closeModal('modalDelete')" class="px-4 py-2 bg-gray-300 rounded">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">
                    Hapus
                </button>
            </div>
        </form>
    </div>
</div>



<!-- ============================
     MODAL HANDLER JAVASCRIPT (FULL FIX)
================================ -->
<script>
// Buka modal (Tailwind-style)
function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) {
        console.error("Modal tidak ditemukan:", id);
        return;
    }
    modal.classList.remove('hidden');
}

// Tutup modal
function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) {
        console.error("Modal tidak ditemukan:", id);
        return;
    }
    modal.classList.add('hidden');
}

// ----------------------------
// EDIT UNIT
// ----------------------------
function openEditUnit(data) {
    if (!data) return console.error("Data edit unit kosong!");

    document.getElementById("edit_id").value = data.id ?? "";
    document.getElementById("edit_nama").value = data.nama_unit ?? "";
    document.getElementById("edit_deskripsi").value = data.deskripsi ?? "";

    openModal("modalEdit");
}

// ----------------------------
// KELOLA KEBUTUHAN UNIT
// ----------------------------
function openKebutuhan(data) {
    if (!data) return console.error("Data kebutuhan kosong!");

    document.getElementById("kb_id").value = data.id ?? "";
    document.getElementById("kb_kuota").value = data.kuota ?? "";
    document.getElementById("kb_jurusan").value = data.jurusan ?? "";
    document.getElementById("kb_jobdesk").value = data.jobdesk ?? "";
    document.getElementById("kb_lokasi").value = data.lokasi ?? "";

    openModal("modalKebutuhan");
}

// ----------------------------
// DELETE UNIT — FIXED 100%
// ----------------------------
function openDelete(id, name) {
    if (!id) {
        console.error("ID hapus kosong!");
        return;
    }

    document.getElementById("delete_id").value = id;
    document.getElementById("delete_name").innerText = name;

    openModal("modalDelete");
}
</script>

</body>
</html>
