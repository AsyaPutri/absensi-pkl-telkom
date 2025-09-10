const startCameraBtn = document.getElementById('startCamera');
const captureBtn = document.getElementById('capture');
const retakeBtn = document.getElementById('retake');
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const photo = document.getElementById('photo');
const placeholder = document.getElementById('camera-placeholder');
let stream = null;

// Start Camera
startCameraBtn.addEventListener('click', async () => {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;

        placeholder.classList.add('d-none');
        video.classList.remove('d-none');
        captureBtn.classList.remove('d-none');
        startCameraBtn.classList.add('d-none');
    } catch (err) {
        alert('Tidak bisa mengakses kamera: ' + err.message);
    }
});

// Capture Photo
captureBtn.addEventListener('click', () => {
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    const imageData = canvas.toDataURL('image/png'); // hasil base64
    photo.src = imageData;

    video.classList.add('d-none');
    captureBtn.classList.add('d-none');
    retakeBtn.classList.remove('d-none');
    photo.classList.remove('d-none');
});

// Retake Photo
retakeBtn.addEventListener('click', () => {
    photo.classList.add('d-none');
    video.classList.remove('d-none');
    captureBtn.classList.remove('d-none');
    retakeBtn.classList.add('d-none');
});
