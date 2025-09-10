// ==========================
// Global Variables
// ==========================
let map;
let userMarker;
let officeMarker;
let officeCircle;
let officeData = null;
let userLocation = null;
let watchId = null;

// Custom icons
const officeIcon = L.divIcon({
    html: '<div style="background-color: #dc3545; width: 25px; height: 25px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"><span style="color: white; font-size: 12px;">🏢</span></div>',
    className: 'custom-div-icon',
    iconSize: [30, 30],
    iconAnchor: [15, 15]
});

const userIcon = L.divIcon({
    html: '<div style="background-color: #007bff; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"><span style="color: white; font-size: 10px;">👤</span></div>',
    className: 'custom-div-icon',
    iconSize: [25, 25],
    iconAnchor: [12, 12]
});

// ==========================
// Clock Realtime
// ==========================
function updateClock() {
    const now = new Date();
    const options = { 
        year: 'numeric', month: 'numeric', day: 'numeric',
        hour: 'numeric', minute: 'numeric', second: 'numeric',
        hour12: true 
    };

    const currentTimeElement = document.getElementById('currentTime');
    if (currentTimeElement) {
        currentTimeElement.textContent = now.toLocaleString('en-US', options);
    }
}
setInterval(updateClock, 1000);
updateClock();

// ==========================
// Map Initialization
// ==========================
function initMap() {
    if (!officeData) return;

    map = L.map('map').setView([officeData.latitude, officeData.longitude], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    addOfficeMarker();
    addOfficeCircle();
    getUserLocation();
}

// ==========================
// Office Marker & Circle
// ==========================
function addOfficeMarker() {
    officeMarker = L.marker([officeData.latitude, officeData.longitude], { icon: officeIcon }).addTo(map);

    const popupContent = `
        <div style="min-width: 200px;">
            <h6 style="margin: 0 0 8px 0; color: #dc3545;">🏢 ${officeData.name}</h6>
            <p style="margin: 0 0 5px 0; font-size: 13px;">📍 ${officeData.address}</p>
            <p style="margin: 0 0 5px 0; font-size: 12px; color: #666;">📏 Radius absensi: <strong>${officeData.radius}m</strong></p>
            <p style="margin: 0; font-size: 11px; color: #999;">📍 ${officeData.latitude.toFixed(6)}, ${officeData.longitude.toFixed(6)}</p>
        </div>
    `;
    officeMarker.bindPopup(popupContent);
    setTimeout(() => officeMarker.openPopup(), 1000);
}

function addOfficeCircle() {
    officeCircle = L.circle([officeData.latitude, officeData.longitude], {
        color: '#dc3545',
        fillColor: '#dc3545',
        fillOpacity: 0.1,
        radius: officeData.radius,
        weight: 2
    }).addTo(map);
}

// ==========================
// User Location
// ==========================
function getUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                addUserMarker();
                calculateDistance();
                adjustMapView();
            },
            (error) => showLocationError(error),
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
        );

        watchId = navigator.geolocation.watchPosition(
            (position) => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                updateUserMarker();
                calculateDistance();
            },
            (error) => console.warn("⚠️ Watch position error:", error.message),
            { enableHighAccuracy: true, timeout: 30000, maximumAge: 60000 }
        );
    } else {
        showLocationError({ message: 'Browser tidak mendukung geolocation' });
    }
}

function addUserMarker() {
    if (userMarker) map.removeLayer(userMarker);

    userMarker = L.marker([userLocation.lat, userLocation.lng], { icon: userIcon }).addTo(map);
    updateUserPopup();

    if (userLocation.accuracy) {
        L.circle([userLocation.lat, userLocation.lng], {
            color: '#007bff',
            fillColor: '#007bff',
            fillOpacity: 0.1,
            radius: userLocation.accuracy,
            weight: 1,
            dashArray: '5, 5'
        }).addTo(map);
    }
    calculateDistance();
}

function updateUserMarker() {
    if (userMarker) {
        userMarker.setLatLng([userLocation.lat, userLocation.lng]);
        updateUserPopup();
    }
}

function updateUserPopup() {
    if (!userMarker) return;
    const popupContent = `
        <div>
            <h6 style="margin: 0 0 5px 0; color: #007bff;">👤 Lokasi Anda</h6>
            <p style="margin: 0 0 3px 0; font-size: 12px;">📍 ${userLocation.lat.toFixed(6)}, ${userLocation.lng.toFixed(6)}</p>
            <p style="margin: 0; font-size: 11px; color: #666;">🎯 Akurasi: ±${Math.round(userLocation.accuracy)}m</p>
        </div>
    `;
    userMarker.bindPopup(popupContent);
}

// ==========================
// Distance Calculation
// ==========================
function calculateDistance() {
    if (!userLocation || !officeData) return;

    const officeLatLng = L.latLng(officeData.latitude, officeData.longitude);
    const userLatLng = L.latLng(userLocation.lat, userLocation.lng);

    const distance = officeLatLng.distanceTo(userLatLng);
    const isInRange = distance <= officeData.radius;

    updateDistanceInfo(distance, isInRange);
    return { distance, isInRange };
}

function updateDistanceInfo(distance, isInRange) {
    const distanceElement = document.getElementById('distanceInfo');
    const locationStatusElement = document.getElementById('locationStatus');
    const mapStatusElement = document.getElementById('mapStatus');

    if (distanceElement) {
        distanceElement.innerHTML = `
            <div class="alert ${isInRange ? 'alert-success' : 'alert-warning'} p-2 mb-2">
                <span>${isInRange ? '✅ Dalam jangkauan' : '❌ Di luar jangkauan'}</span>
                <div class="mt-2"><small>
                    📏 Jarak: <strong>${distance.toFixed(1)}m</strong><br>
                    📍 Radius: <strong>${officeData.radius}m</strong><br>
                    🎯 Selisih: <strong>${Math.abs(distance - officeData.radius).toFixed(1)}m</strong>
                </small></div>
            </div>
        `;
    }

    if (locationStatusElement) {
        locationStatusElement.textContent = isInRange ? "✅ Dalam jangkauan" : "❌ Di luar jangkauan";
        locationStatusElement.className = isInRange ? "fw-bold text-success" : "fw-bold text-danger";
    }

    if (mapStatusElement) {
        mapStatusElement.innerHTML = `📍 Status: ${isInRange ? '<span class="text-success">Dalam jangkauan</span>' : '<span class="text-danger">Di luar jangkauan</span>'}`;
    }
}

// ==========================
// Helpers
// ==========================
function adjustMapView() {
    if (userMarker && officeMarker) {
        const group = new L.featureGroup([officeMarker, userMarker]);
        map.fitBounds(group.getBounds().pad(0.1));
        if (map.getZoom() > 18) map.setZoom(18);
    }
}

function showLocationError(error) {
    console.error('❌ Location error:', error);
    const mapStatusElement = document.getElementById('mapStatus');
    if (mapStatusElement) {
        mapStatusElement.innerHTML = `<span class="text-danger">⚠️ ${error.message}</span>`;
    }
}

// ==========================
// Load Office Data
// ==========================
function loadOfficeData() {
    fetch('get_office.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.office) {
                officeData = data.office;
                initMap();
            } else {
                throw new Error("Invalid API response");
            }
        })
        .catch(() => {
            officeData = {
                id: 1,
                name: 'Telkom Bekasi',
                latitude: -6.238047,
                longitude: 106.994139,
                radius: 100,
                address: 'Jl. Rw. Tembaga IV No.4, Marga Jaya, Bekasi',
                is_active: true
            };
            initMap();
        });
}

// ==========================
// Init when DOM ready
// ==========================
document.addEventListener('DOMContentLoaded', loadOfficeData);
window.addEventListener('beforeunload', () => {
    if (watchId) navigator.geolocation.clearWatch(watchId);
});
