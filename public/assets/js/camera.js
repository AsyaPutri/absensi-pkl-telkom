const startCameraBtn = document.getElementById("startCamera");
const captureBtn = document.getElementById("capture");
const retakeBtn = document.getElementById("retake");
const video = document.getElementById("video");
const canvas = document.getElementById("canvas");
const photo = document.getElementById("photo");
const photoData = document.getElementById("photoData");

let stream = null;

// Start camera
startCameraBtn.addEventListener("click", async () => {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;

        video.classList.remove("d-none");
        captureBtn.classList.remove("d-none");
        startCameraBtn.classList.add("d-none");
        document.getElementById("camera-placeholder").classList.add("d-none");

        console.log("📷 Kamera berhasil dinyalakan");
    } catch (err) {
        alert("Tidak bisa mengakses kamera: " + err.message);
        console.error("❌ Camera error:", err);
    }
});

// Capture
captureBtn.addEventListener("click", () => {
    const context = canvas.getContext("2d");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    const imageData = canvas.toDataURL("image/png"); // hasil base64
    photo.src = imageData;
    photo.classList.remove("d-none");

    // Simpan ke hidden input + variabel global
    photoData.value = imageData;
    window.capturedPhoto = imageData;

    console.log("✅ Foto tersimpan, panjang base64:", imageData.length);

    // Matikan kamera setelah ambil foto
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }

    video.classList.add("d-none");
    captureBtn.classList.add("d-none");
    retakeBtn.classList.remove("d-none");
});

// Retake
retakeBtn.addEventListener("click", async () => {
    // reset foto
    photo.classList.add("d-none");
    photo.src = "";
    photoData.value = "";
    window.capturedPhoto = "";

    retakeBtn.classList.add("d-none");
    startCameraBtn.classList.remove("d-none");
    document.getElementById("camera-placeholder").classList.remove("d-none");

    console.log("🔄 Foto direset, siap ambil ulang");
});
