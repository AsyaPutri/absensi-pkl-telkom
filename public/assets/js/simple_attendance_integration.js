// ========================================================
// Simple Attendance Integration
// Gabungan Check-in, Check-out, Capture Foto Absen
// ========================================================
document.addEventListener("DOMContentLoaded", () => {
    console.log("✅ simple_attendance_integration.js loaded");

    const btn = document.getElementById("submitAbsen");
    const cameraPreview = document.getElementById("cameraPreview");
    const captureBtn = document.getElementById("captureBtn");
    const photoPreview = document.getElementById("photoPreview");

    // simpan foto di window biar bisa dipakai waktu submit
    window.capturedPhoto = "";

    // ========================================================
    // CAMERA SECTION
    // ========================================================
    if (captureBtn) {
        captureBtn.addEventListener("click", async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                cameraPreview.srcObject = stream;

                const track = stream.getVideoTracks()[0];
                const imageCapture = new ImageCapture(track);
                const blob = await imageCapture.takePhoto();

                const reader = new FileReader();
                reader.onloadend = () => {
                    window.capturedPhoto = reader.result;
                    if (photoPreview) {
                        photoPreview.src = reader.result;
                        photoPreview.classList.remove("d-none");
                    }
                    console.log("📸 Foto berhasil ditangkap");
                };
                reader.readAsDataURL(blob);

                // stop kamera biar ga terus nyala
                setTimeout(() => {
                    track.stop();
                }, 1000);
            } catch (err) {
                console.error("❌ Kamera error:", err);
                alert("Tidak bisa mengakses kamera: " + err.message);
            }
        });
    }

    // ========================================================
    // SUBMIT ATTENDANCE
    // ========================================================
    if (!btn) {
        console.error("❌ Button #submitAbsen tidak ditemukan di DOM!");
        return;
    }

    btn.addEventListener("click", async () => {
        const status = btn.dataset.status;
        const pendingDate = btn.dataset.pendingDate || "";

        console.log("📌 Tombol ditekan, status:", status, "pendingDate:", pendingDate);

        let urlAction = "";
        let payload = {};

        if (status === "not_checked_in") {
            urlAction = "checkin";
            payload = collectCheckinData();

            // 🚨 Validasi: Checkin wajib ada foto
            if (!window.capturedPhoto) {
                alert("Ambil foto dulu sebelum check-in!");
                return;
            }

        } else if (status === "checked_in") {
            urlAction = "checkout";
            payload = collectCheckoutData();

        } else if (status === "pending_checkout") {
            urlAction = "checkout_pending";
            payload = { target_date: pendingDate, ...collectCheckoutData() };

        } else {
            console.warn("⚠️ Status tidak dikenali:", status);
            return;
        }

        console.log("📤 Data yang akan dikirim:", payload);

        try {
            const response = await fetch("save_absen.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: urlAction, ...payload })
            });

            console.log("📥 Response status:", response.status);

            const text = await response.text();
            console.log("📥 Response raw text:", text);

            let result;
            try {
                result = JSON.parse(text);
            } catch (err) {
                console.error("❌ Gagal parse JSON:", err);
                alert("Response server bukan JSON. Cek console.");
                return;
            }

            if (result.success) {
                console.log("✅ Success:", result);
                alert(result.message || "Berhasil!");

                if (urlAction === "checkin") {
                    disableConditionLocation(); // 🚀 langsung disable
                    setTimeout(() => location.reload(), 1000); // reload setelah 1 detik
                } else {
                    location.reload();
                }
            } else {
                console.warn("⚠️ Gagal:", result);
                alert("Gagal: " + (result.message || "Tidak diketahui"));
            }
        } catch (err) {
            console.error("❌ Fetch error:", err);
            alert("Terjadi error saat kirim data: " + err.message);
        }
    });

    // ========================================================
    // DATA COLLECTOR
    // ========================================================
    function collectCheckinData() {
    const aktivitas = document.getElementById("activity")?.value || "";
    const kendala   = document.getElementById("kendala")?.value || "";

    const kondisi = document.querySelector(".option-card[data-type='condition'].selected")?.dataset.value || "";
    const lokasi  = document.querySelector(".option-card[data-type='location'].selected")?.dataset.value || "";

    // Ambil foto dari hidden input kalau ada
    const photoData = document.getElementById("photoData")?.value || window.capturedPhoto || "";

    console.log("📋 Checkin data:", { aktivitas, kendala, kondisi, lokasi, hasPhoto: !!photoData });
    return { aktivitas, kendala, kondisi, lokasi, foto: photoData };
}


    function collectCheckoutData() {
        const aktivitas = document.getElementById("activity")?.value || "";
        const kendala = document.getElementById("kendala")?.value || "";

        console.log("📋 Checkout data:", { aktivitas, kendala });
        return { aktivitas_keluar: aktivitas, kendala_keluar: kendala };
    }

    // ========================================================
    // LOCK KONDISI & LOKASI SETELAH CHECKIN
    // ========================================================
    if (btn && btn.dataset.status === "checked_in") {
        disableConditionLocation();
        console.log("🔒 Kondisi & lokasi terkunci karena sudah checkin");
    }
});

// ========================================================
// FUNCTION: Disable Condition & Location
// ========================================================
function disableConditionLocation() {
    const conditionCards = document.querySelectorAll(".option-card[data-type='condition']");
    const locationCards = document.querySelectorAll(".option-card[data-type='location']");

    // Tambahkan class disabled + matikan event klik
    conditionCards.forEach(card => {
        card.classList.add("disabled");
        card.style.pointerEvents = "none";
        card.style.opacity = "0.6";
    });

    locationCards.forEach(card => {
        card.classList.add("disabled");
        card.style.pointerEvents = "none";
        card.style.opacity = "0.6";
    });
}
