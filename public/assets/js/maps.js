// maps.js - Leaflet Version
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

// Inisialisasi maps
function initMap() {
    console.log('🗺️ Initializing Leaflet map...');
    
    if (!officeData) {
        console.error('❌ No office data available for map initialization');
        return;
    }
    
    // Buat map dengan center di kantor
    map = L.map('map').setView([officeData.latitude, officeData.longitude], 16);
    
    // Tambahkan tile layer OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Tambahkan marker kantor
    addOfficeMarker();
    
    // Tambahkan circle radius kantor
    addOfficeCircle();
    
    // Coba dapatkan lokasi user
    getUserLocation();
    
    console.log('✅ Leaflet map initialized');
}

// Tambahkan marker untuk lokasi kantor
function addOfficeMarker() {
    console.log('🏢 Adding office marker...');
    
    officeMarker = L.marker([officeData.latitude, officeData.longitude], {
        icon: officeIcon
    }).addTo(map);
    
    // Popup untuk kantor
    const popupContent = `
        <div style="min-width: 200px;">
            <h6 style="margin: 0 0 8px 0; color: #dc3545;">
                🏢 ${officeData.name}
            </h6>
            <p style="margin: 0 0 5px 0; font-size: 13px;">
                📍 ${officeData.address}
            </p>
            <p style="margin: 0 0 5px 0; font-size: 12px; color: #666;">
                📏 Radius absensi: <strong>${officeData.radius}m</strong>
            </p>
            <p style="margin: 0; font-size: 11px; color: #999;">
                📍 ${officeData.latitude.toFixed(6)}, ${officeData.longitude.toFixed(6)}
            </p>
        </div>
    `;
    
    officeMarker.bindPopup(popupContent);
    
    // Auto show popup setelah 1 detik
    setTimeout(() => {
        officeMarker.openPopup();
    }, 1000);
}

// Tambahkan circle radius kantor
function addOfficeCircle() {
    console.log(`📏 Adding office radius circle: ${officeData.radius}m`);
    
    officeCircle = L.circle([officeData.latitude, officeData.longitude], {
        color: '#dc3545',
        fillColor: '#dc3545',
        fillOpacity: 0.1,
        radius: officeData.radius,
        weight: 2
    }).addTo(map);
    
    // Tooltip untuk circle
    officeCircle.bindTooltip(`Area Absensi: ${officeData.radius}m`, {
        permanent: false,
        direction: 'center'
    });
}

// Dapatkan lokasi user
function getUserLocation() {
    console.log('📍 Getting user location...');
    
    if (navigator.geolocation) {
        // Get current position
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                
                console.log('✅ User location found:', userLocation);
                addUserMarker();
                calculateDistance();
                adjustMapView();
            },
            (error) => {
                console.warn('⚠️ Geolocation error:', error.message);
                showLocationError(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5 menit
            }
        );
        
        // Watch position untuk real-time update
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
            (error) => {
                console.warn('⚠️ Watch position error:', error.message);
            },
            {
                enableHighAccuracy: true,
                timeout: 30000,
                maximumAge: 60000 // 1 menit
            }
        );
        
    } else {
        console.error('❌ Geolocation not supported');
        showLocationError({ message: 'Browser tidak mendukung geolocation' });
    }
}

// Tambahkan marker untuk lokasi user
function addUserMarker() {
    console.log('👤 Adding user marker...');
    
    if (userMarker) {
        map.removeLayer(userMarker);
    }
    
    userMarker = L.marker([userLocation.lat, userLocation.lng], {
        icon: userIcon
    }).addTo(map);
    
    // Popup untuk user
    const popupContent = `
        <div>
            <h6 style="margin: 0 0 5px 0; color: #007bff;">
                👤 Lokasi Anda
            </h6>
            <p style="margin: 0 0 3px 0; font-size: 12px;">
                📍 ${userLocation.lat.toFixed(6)}, ${userLocation.lng.toFixed(6)}
            </p>
            <p style="margin: 0; font-size: 11px; color: #666;">
                🎯 Akurasi: ±${Math.round(userLocation.accuracy)}m
            </p>
        </div>
    `;
    
    userMarker.bindPopup(popupContent);
    
    // Accuracy circle
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
}

// Update marker user (untuk watch position)
function updateUserMarker() {
    if (userMarker) {
        userMarker.setLatLng([userLocation.lat, userLocation.lng]);
        
        // Update popup content
        const popupContent = `
            <div>
                <h6 style="margin: 0 0 5px 0; color: #007bff;">
                    👤 Lokasi Anda
                </h6>
                <p style="margin: 0 0 3px 0; font-size: 12px;">
                    📍 ${userLocation.lat.toFixed(6)}, ${userLocation.lng.toFixed(6)}
                </p>
                <p style="margin: 0; font-size: 11px; color: #666;">
                    🎯 Akurasi: ±${Math.round(userLocation.accuracy)}m
                </p>
                <p style="margin: 0; font-size: 10px; color: #999;">
                    🕐 ${new Date().toLocaleTimeString('id-ID')}
                </p>
            </div>
        `;
        
        userMarker.getPopup().setContent(popupContent);
    }
}

// Adjust map view untuk menampilkan kedua marker
function adjustMapView() {
    if (userMarker && officeMarker) {
        const group = new L.featureGroup([officeMarker, userMarker]);
        map.fitBounds(group.getBounds().pad(0.1));
        
        // Set zoom maksimal
        setTimeout(() => {
            if (map.getZoom() > 18) {
                map.setZoom(18);
            }
        }, 100);
    }
}

// Hitung jarak antara user dan kantor
function calculateDistance() {
    if (!userLocation || !officeData) {
        console.warn('⚠️ Cannot calculate distance: missing location data');
        return null;
    }
    
    const officeLatLng = L.latLng(officeData.latitude, officeData.longitude);
    const userLatLng = L.latLng(userLocation.lat, userLocation.lng);
    
    // Hitung jarak dalam meter
    const distance = officeLatLng.distanceTo(userLatLng);
    const isInRange = distance <= officeData.radius;
    
    console.log(`📏 Distance: ${distance.toFixed(1)}m, In range: ${isInRange}`);
    
    // Update UI dengan info jarak
    updateDistanceInfo(distance, isInRange);
    
    return { distance, isInRange };
}

// Update info jarak di UI
function updateDistanceInfo(distance, isInRange) {
    const distanceElement = document.getElementById('distanceInfo');
    if (distanceElement) {
        const statusClass = isInRange ? 'status-in-range' : 'status-out-range';
        const statusIcon = isInRange ? '✅' : '❌';
        const statusText = isInRange ? 'Dalam jangkauan' : 'Di luar jangkauan';
        
        distanceElement.innerHTML = `
            <div class="alert ${isInRange ? 'alert-success' : 'alert-warning'} p-2 mb-2">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="status-badge ${statusClass}">
                        ${statusIcon} ${statusText}
                    </span>
                </div>
                <div class="distance-info mt-2">
                    <small>
                        📏 Jarak: <strong>${distance.toFixed(1)}m</strong><br>
                        📍 Radius: <strong>${officeData.radius}m</strong><br>
                        🎯 Selisih: <strong>${Math.abs(distance - officeData.radius).toFixed(1)}m</strong>
                    </small>
                </div>
            </div>
        `;
    }
}

// Handle error lokasi
function showLocationError(error) {
    console.error('❌ Location error:', error);
    
    const errorElement = document.getElementById('locationError');
    if (errorElement) {
        let errorMessage = 'Tidak dapat mengakses lokasi';
        
        if (error.code) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = 'Akses lokasi ditolak. Silakan izinkan akses lokasi.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = 'Informasi lokasi tidak tersedia.';
                    break;
                case error.TIMEOUT:
                    errorMessage = 'Timeout mendapatkan lokasi.';
                    break;
            }
        }
        
        errorElement.innerHTML = `
            <div class="alert alert-warning p-2">
                <small>⚠️ ${errorMessage}</small>
                <button class="btn btn-sm btn-outline-warning mt-1 w-100" onclick="getUserLocation()">
                    🔄 Coba Lagi
                </button>
            </div>
        `;
    }
}

// Load office data dan inisialisasi map
function loadOfficeData() {
    console.log('🏢 Loading office data from API...');
    
    fetch('get_office.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 API Response:', data);
            
            if (data.success && data.office) {
                officeData = data.office;
                console.log('✅ Office data loaded:', officeData.name);
                
                // Update office info di UI
                updateOfficeInfo(officeData);
                
                // Inisialisasi map setelah data dimuat
                initMap();
                
            } else {
                throw new Error(data.message || 'Invalid API response');
            }
        })
        .catch(error => {
            console.error('❌ API Error:', error.message);
            
            // Fallback ke hardcoded data
            console.warn('🔄 Using hardcoded emergency fallback...');
            officeData = {
                id: 1,
                name: 'Telkom Witel Bekasi Karawang',
                latitude: -6.237846687485902,
                longitude: 106.99415622140583,
                radius: 100,
                address: 'Jl. Rw. Tembaga IV No.4, RT.006/RW.005, Marga Jaya',
                is_active: true
            };
            
            updateOfficeInfo(officeData);
            initMap();
        });
}

// Update office info di UI
function updateOfficeInfo(office) {
    const officeNameElement = document.getElementById('officeName');
    if (officeNameElement) {
        officeNameElement.textContent = office.name;
    }
    
    const officeAddressElement = document.getElementById('officeAddress');
    if (officeAddressElement) {
        officeAddressElement.textContent = office.address;
    }
    
    const lastUpdatedElement = document.getElementById('lastUpdated');
    if (lastUpdatedElement && office.updated_at) {
        const date = new Date(office.updated_at);
        lastUpdatedElement.textContent = `Terakhir update: ${date.toLocaleDateString('id-ID')}`;
    }
}

// Fungsi untuk button
function refreshUserLocation() {
    console.log('🔄 Refreshing user location...');
    getUserLocation();
}

function checkLocation() {
    if (!userLocation) {
        alert('⚠️ Lokasi belum terdeteksi. Silakan tunggu atau refresh lokasi.');
        getUserLocation();
    } else {
        const result = calculateDistance();
        if (result) {
            const message = `📏 Jarak Anda: ${result.distance.toFixed(1)}m dari kantor\n\n${result.isInRange ? '✅ DALAM JANGKAUAN ABSENSI' : '❌ DI LUAR JANGKAUAN ABSENSI'}\n\nRadius absensi: ${officeData.radius}m`;
            alert(message);
        }
    }
}

function centerToOffice() {
    if (map && officeData) {
        map.setView([officeData.latitude, officeData.longitude], 17);
        if (officeMarker) {
            officeMarker.openPopup();
        }
    }
}

// Auto load saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM loaded, starting office data load...');
    loadOfficeData();
});

// Cleanup saat halaman ditutup
window.addEventListener('beforeunload', function() {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
    }
});

// Di maps.js, dispatch events seperti ini:
document.dispatchEvent(new CustomEvent('locationUpdated', {
    detail: { distance, isInRange, accuracy }
}));

document.dispatchEvent(new CustomEvent('locationError', {
    detail: { message: 'Error message' }
}));

document.dispatchEvent(new CustomEvent('officeDataLoaded', {
    detail: officeData
}));
