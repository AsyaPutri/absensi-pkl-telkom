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

<!-- Warna Telkom Custom -->
<style>
    :root {
        --telkom-red: #E60023;
        --telkom-dark: #B0001B;
        --telkom-black: #1A1A1A;
        --bg-light: #F8F9FB;
    }

    /* Smooth modal animation */
    .modal-show {
        animation: fadeIn 0.2s ease-out;
    }
    
    .telkom-logo {
      height: 80px;
      width: auto;
    }

    @media (max-width: 1024px) {
      .telkom-logo { height: 65px; }
    }

    @media (max-width: 768px) {
      .telkom-logo { height: 55px; }
    }
    
    @media (max-width: 640px) {
      .telkom-logo { height: 45px; }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.97); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }
</style>

</head>
<body class="bg-[var(--bg-light)] min-h-screen">

<!-- HEADER -->
<header class="bg-white shadow-lg sticky top-0 z-40 border-b border-gray-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            
            <!-- Logo & Title -->
            <div class="flex items-center gap-3 sm:gap-4">
                <img src="../assets/img/InStep.png" class="telkom-logo flex-shrink-0" alt="Telkom Logo">
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-[var(--telkom-black)] tracking-tight truncate">
                        Kelola Unit PKL
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5 sm:mt-1 truncate">
                        Admin • InStep Telkom Witel Bekasi
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                <a href="dashboard.php" 
                   class="px-3 sm:px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-xs sm:text-sm transition font-medium whitespace-nowrap">
                    ← Kembali
                </a>

                <button onclick="openModal('modalTambah')" 
                    class="px-3 sm:px-5 py-2 bg-[var(--telkom-red)] text-white rounded-lg shadow hover:bg-[var(--telkom-dark)] transition font-semibold text-xs sm:text-sm whitespace-nowrap">
                    + Tambah Unit
                </button>
            </div>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- Notifikasi -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-lg bg-green-100 border border-green-300 text-green-900 shadow-sm text-sm sm:text-base">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-lg bg-red-100 border border-red-300 text-red-900 shadow-sm text-sm sm:text-base">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- GRID UNIT -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6">

        <?php while ($row = $units->fetch_assoc()):
            $id        = $row['id'];
            $nama      = $row['nama_unit'] ?? '';
            $deskripsi = $row['deskripsi'] ?? '';
            $kuota     = $row['kuota'] ?? '';
            $jurusan   = $row['jurusan'] ?? '';
            $lokasi    = $row['lokasi'] ?? '';
            $jobdesk   = $row['jobdesk'] ?? '';
        ?>
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-md border border-gray-200 p-4 sm:p-5 lg:p-6 hover:shadow-xl transition-shadow duration-300 flex flex-col">

            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 line-clamp-2">
                <?= htmlspecialchars($nama); ?>
            </h3>

            <!-- Info -->
            <div class="text-xs sm:text-sm space-y-1.5 sm:space-y-2 mb-4 sm:mb-5 bg-gray-50 p-3 rounded-lg">
                <p class="flex justify-between">
                    <span class="font-semibold text-gray-700">Kuota:</span> 
                    <span class="text-gray-900"><?= $kuota !== '' ? htmlspecialchars($kuota) : "-" ?></span>
                </p>
                <p class="flex justify-between">
                    <span class="font-semibold text-gray-700">Jurusan:</span> 
                    <span class="text-gray-900 text-right ml-2 truncate"><?= $jurusan !== '' ? htmlspecialchars($jurusan) : "-" ?></span>
                </p>
                <p class="flex justify-between">
                    <span class="font-semibold text-gray-700">Lokasi:</span> 
                    <span class="text-gray-900 text-right ml-2 truncate"><?= $lokasi !== '' ? htmlspecialchars($lokasi) : "-" ?></span>
                </p>
            </div>

            <!-- Buttons -->
            <div class="grid grid-cols-3 gap-2 text-xs sm:text-sm mt-auto">

                <button 
                    class="bg-yellow-400 text-black px-2 sm:px-3 py-2 rounded-lg hover:bg-yellow-500 font-semibold transition-colors"
                    onclick='openEditUnit(<?= json_encode([
                        "id"=>$id,
                        "nama_unit"=>$nama,
                        "deskripsi"=>$deskripsi
                    ], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                >Edit</button>

                <button 
                    class="bg-[var(--telkom-red)] text-white px-2 sm:px-3 py-2 rounded-lg hover:bg-[var(--telkom-dark)] font-semibold transition-colors"
                    onclick='openKebutuhan(<?= json_encode([
                        "id"=>$id,
                        "kuota"=>$kuota,
                        "jurusan"=>$jurusan,
                        "jobdesk"=>$jobdesk,
                        "lokasi"=>$lokasi
                    ], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                >Kebutuhan</button>

                <button 
                    onclick='openDelete(<?= $row["id"] ?>, "<?= addslashes($row["nama_unit"]) ?>")'
                    class="px-2 sm:px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-colors"
                >Hapus</button>

            </div>

        </div>
        <?php endwhile; ?>

    </div>
</div>


<!-- ==============================
      MODAL TAMBAH
================================ -->
<div id="modalTambah" class="hidden fixed inset-0 bg-black/50 z-50 flex justify-center items-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl sm:rounded-2xl p-5 sm:p-6 w-full max-w-md shadow-2xl modal-show max-h-[90vh] overflow-y-auto">

        <h2 class="text-lg sm:text-xl font-bold mb-4 text-gray-800">Tambah Unit PKL</h2>

        <form action="proses_unit.php" method="POST">
            <input type="hidden" name="action" value="tambah">

            <div class="mb-3 sm:mb-4">
                <label class="font-semibold block mb-1.5 text-sm sm:text-base text-gray-700">Nama Unit</label>
                <input type="text" name="nama_unit" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm sm:text-base focus:ring-2 focus:ring-[var(--telkom-red)] focus:border-transparent transition" required>
            </div>

            <div class="flex justify-end gap-2 sm:gap-3 mt-5 sm:mt-6">
                <button type="button" onclick="closeModal('modalTambah')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm sm:text-base font-medium transition">Batal</button>
                <button class="px-4 py-2 bg-[var(--telkom-red)] text-white rounded-lg hover:bg-[var(--telkom-dark)] text-sm sm:text-base font-semibold transition">Simpan</button>
            </div>
        </form>

    </div>
</div>


<!-- ==============================
      MODAL EDIT UNIT
================================ -->
<div id="modalEdit" class="hidden fixed inset-0 bg-black/50 z-50 flex justify-center items-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl sm:rounded-2xl p-5 sm:p-6 w-full max-w-md shadow-2xl modal-show max-h-[90vh] overflow-y-auto">

        <h2 class="text-lg sm:text-xl font-bold mb-4 text-gray-800">Edit Unit PKL</h2>

        <form action="proses_unit.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">

            <div class="mb-3 sm:mb-4">
                <label class="font-semibold block mb-1.5 text-sm sm:text-base text-gray-700">Nama Unit</label>
                <input type="text" id="edit_nama" name="nama_unit" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm sm:text-base focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition" required>
            </div>

            <div class="flex justify-end gap-2 sm:gap-3 mt-5 sm:mt-6">
                <button type="button" onclick="closeModal('modalEdit')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm sm:text-base font-medium transition">Batal</button>
                <button class="px-4 py-2 bg-yellow-400 text-black rounded-lg hover:bg-yellow-500 text-sm sm:text-base font-semibold transition">Update</button>
            </div>
        </form>

    </div>
</div>


<!-- ==============================
      MODAL KEBUTUHAN UNIT
================================ -->
<div id="modalKebutuhan" class="hidden fixed inset-0 bg-black/50 z-50 flex justify-center items-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl sm:rounded-2xl p-5 sm:p-6 w-full max-w-md shadow-2xl modal-show max-h-[90vh] overflow-y-auto">

        <h2 class="text-lg sm:text-xl font-bold mb-4 text-gray-800">Atur Kebutuhan Unit</h2>

        <form action="proses_kebutuhan.php" method="POST">

            <input type="hidden" id="kb_id" name="id">

            <div class="mb-3 sm:mb-4">
                <label class="font-semibold block mb-1.5 text-sm sm:text-base text-gray-700">Kuota Dibutuhkan</label>
                <input type="number" id="kb_kuota" name="kuota" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm sm:text-base focus:ring-2 focus:ring-[var(--telkom-red)] focus:border-transparent transition" min="0" required>
            </div>

            <div class="mb-3 sm:mb-4">
                <label class="font-semibold block mb-1.5 text-sm sm:text-base text-gray-700">Jurusan yang Diterima</label>
                <input type="text" id="kb_jurusan" name="jurusan" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm sm:text-base focus:ring-2 focus:ring-[var(--telkom-red)] focus:border-transparent transition" placeholder="SI, TI, TKJ..." required>
            </div>

            <div class="mb-3 sm:mb-4">
                <label class="font-semibold block mb-1.5 text-sm sm:text-base text-gray-700">Jobdesk</label>
                <textarea id="kb_jobdesk" name="jobdesk" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm sm:text-base focus:ring-2 focus:ring-[var(--telkom-red)] focus:border-transparent transition" rows="3"></textarea>
            </div>

            <div class="mb-4 sm:mb-5">
                <label class="font-semibold block mb-1.5 text-sm sm:text-base text-gray-700">Lokasi</label>
                <input type="text" id="kb_lokasi" name="lokasi" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm sm:text-base focus:ring-2 focus:ring-[var(--telkom-red)] focus:border-transparent transition">
            </div>

            <div class="flex justify-end gap-2 sm:gap-3 mt-5 sm:mt-6">
                <button type="button" onclick="closeModal('modalKebutuhan')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm sm:text-base font-medium transition">Batal</button>
                <button class="px-4 py-2 bg-[var(--telkom-red)] text-white rounded-lg hover:bg-[var(--telkom-dark)] text-sm sm:text-base font-semibold transition">Simpan</button>
            </div>

        </form>

    </div>
</div>


<!-- ==============================
      MODAL DELETE
================================ -->
<div id="modalDelete" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl sm:rounded-2xl p-5 sm:p-6 w-full max-w-sm shadow-2xl modal-show">
        <h2 class="text-lg sm:text-xl font-semibold mb-3 text-red-600">Hapus Unit</h2>

        <p class="text-sm sm:text-base text-gray-700">Yakin ingin menghapus unit <span class="font-bold text-red-600" id="delete_name"></span>?</p>

        <form action="proses_unit.php" method="POST" class="mt-5 sm:mt-6">
            <input type="hidden" name="action" value="hapus">
            <input type="hidden" id="delete_id" name="id">

            <div class="flex justify-end gap-2 sm:gap-3">
                <button type="button" onclick="closeModal('modalDelete')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm sm:text-base font-medium transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm sm:text-base font-semibold transition">
                    Hapus
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ==============================
     MODAL JAVASCRIPT
================================ -->
<script>
function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove("hidden");
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add("hidden");
    document.body.style.overflow = 'auto';
}

// Edit
function openEditUnit(data) {
    document.getElementById("edit_id").value = data.id;
    document.getElementById("edit_nama").value = data.nama_unit;
    openModal("modalEdit");
}

// Kebutuhan
function openKebutuhan(data) {
    document.getElementById("kb_id").value = data.id;
    document.getElementById("kb_kuota").value = data.kuota;
    document.getElementById("kb_jurusan").value = data.jurusan;
    document.getElementById("kb_jobdesk").value = data.jobdesk;
    document.getElementById("kb_lokasi").value = data.lokasi;
    openModal("modalKebutuhan");
}

// Delete
function openDelete(id, name) {
    document.getElementById("delete_id").value = id;
    document.getElementById("delete_name").innerText = name;
    openModal("modalDelete");
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = ['modalTambah', 'modalEdit', 'modalKebutuhan', 'modalDelete'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && !modal.classList.contains('hidden')) {
                closeModal(modalId);
            }
        });
    }
});

// Close modal on backdrop click
document.querySelectorAll('[id^="modal"]').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});
</script>

</body>
</html>