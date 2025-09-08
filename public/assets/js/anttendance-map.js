class AttendanceMap {
    constructor(mapId, officeLocation, officeRadius = 100) {
        console.log('🚀 Initializing AttendanceMap...');
        
        // Cek support browser
        if (!navigator.geolocation) {
            this.showError('❌ Browser tidak mendukung GPS. Gunakan Chrome/Firefox terbaru.');
            return;
        }
        
        // Cek HTTPS (kecuali localhost)
        if (location.protocol !== 'https:' && !location.hostname.includes('localhost')) {
            this.showError('❌ GPS memerlukan HTTPS. Hubungi admin untuk mengaktifkan SSL.');
            return;
        }
        
        this.mapId = mapId;
        this.officeLocation = officeLocation;
        this.officeRadius = officeRadius;
        this.watchId = null;
        this.userMarker = null;
        this.isInOffice = false;
        
        this.initializeMap();
        this.requestPermissionAndStart();
    }
    
    // FUNGSI BARU: Request Permission
    async requestPermissionAndStart() {
        console.log('📍 Requesting location permission...');
        
        try {
            // Test permission dulu
            const testOptions = {
                enableHighAccuracy: false,
                timeout: 5000,
                maximumAge: 60000
            };
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    console.log('✅ Permission granted, starting GPS...');
                    this.startWatching();
                },
                (error) => {
                    console.error('❌ Permission error:', error);
                    this.handleLocationError(error);
                },
                testOptions
            );
            
        } catch (error) {
            console.error('❌ Geolocation error:', error);
            this.showError('Error mengakses GPS: ' + error.message);
        }
    }
    
    initializeMap() {
        console.log('🗺️ Creating map...');
        
        // Inisialisasi peta
        this.map = L.map(this.mapId).setView(this.officeLocation, 16);
        
        // Tambah tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);
        
        // Tambah marker kantor
        this.officeMarker = L.marker(this.officeLocation)
            .addTo(this.map)
            .bindPopup('📍 Lokasi Kantor');
        
        // Tambah circle radius kantor
        this.officeCircle = L.circle(this.officeLocation, {
            color: 'blue',
            fillColor: '#3388ff',
            fillOpacity: 0.2,
            radius: this.officeRadius
        }).addTo(this.map);
        
        console.log('✅ Map initialized');
    }
    
    startWatching() {
        console.log('👀 Starting GPS tracking...');
        
        const options = {
            enableHighAccuracy: true,
            timeout: 15000, // 15 detik
            maximumAge: 10000 // Cache 10 detik
        };
        
        // Update status
        this.updateStatus('🔍 Mencari lokasi GPS...', 'info');
        
        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.handleLocationSuccess(position),
            (error) => this.handleLocationError(error),
            options
        );
    }
    
    handleLocationSuccess(position) {
        console.log('✅ Location found:', position.coords);
        
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        
        // Update posisi user
        this.updateUserLocation([lat, lng], accuracy);
        
        // Cek apakah dalam radius kantor
        this.checkOfficeRadius([lat, lng]);
        
        // Update status
        this.updateStatus(`📍 Lokasi ditemukan (akurasi: ${Math.round(accuracy)}m)`, 'success');
    }
    
    handleLocationError(error) {
        console.error('❌ GPS Error:', error);
        
        let errorMessage = '';
        let errorCode = error.code || 0;
        
        switch(errorCode) {
            case 1: // PERMISSION_DENIED
                errorMessage = '🚫 Izin GPS ditolak. Klik ikon 🔒 di address bar, pilih "Allow" untuk Location, lalu refresh halaman.';
                break;
                
            case 2: // POSITION_UNAVAILABLE
                errorMessage = '📡 Sinyal GPS tidak tersedia. Pastikan GPS device aktif dan coba di tempat terbuka.';
                break;
                
            case 3: // TIMEOUT
                errorMessage = '⏰ Timeout GPS. Koneksi lambat atau sinyal lemah. Coba refresh halaman.';
                break;
                
            default:
                errorMessage = `❌ Error GPS (${errorCode}): ${error.message}. Coba refresh halaman atau gunakan browser lain.`;
                break;
        }
        
        this.showError(errorMessage);
        
        // Tampilkan solusi
        this.showSolutions();
    }
    
    updateUserLocation(position, accuracy) {
        // Hapus marker lama
        if (this.userMarker) {
            this.map.removeLayer(this.userMarker);
        }
        
        // Tambah marker user baru
        this.userMarker = L.marker(position, {
            icon: L.divIcon({
                className: 'user-location-marker',
                html: '📍',
                iconSize: [30, 30]
            })
        }).addTo(this.map);
        
        // Tambah circle akurasi
        if (this.accuracyCircle) {
            this.map.removeLayer(this.accuracyCircle);
        }
        
        this.accuracyCircle = L.circle(position, {
            color: 'red',
            fillColor: '#ff0000',
            fillOpacity: 0.1,
            radius: accuracy
        }).addTo(this.map);
        
        // Center map ke user
        this.map.setView(position, 17);
        
        console.log('📍 User location updated');
    }
    
    checkOfficeRadius(userPosition) {
        const distance = this.calculateDistance(userPosition, this.officeLocation);
        const wasInOffice = this.isInOffice;
        this.isInOffice = distance <= this.officeRadius;
        
        console.log(`📏 Distance to office: ${Math.round(distance)}m`);
        
        if (this.isInOffice && !wasInOffice) {
            this.updateStatus('✅ Anda berada di area kantor', 'success');
            this.enableAttendanceButton();
        } else if (!this.isInOffice && wasInOffice) {
            this.updateStatus(`❌ Anda di luar area kantor (${Math.round(distance)}m dari kantor)`, 'warning');
            this.disableAttendanceButton();
        }
    }
    
    calculateDistance(pos1, pos2) {
        const R = 6371e3; // Earth radius in meters
        const φ1 = pos1[0] * Math.PI/180;
        const φ2 = pos2[0] * Math.PI/180;
        const Δφ = (pos2[0]-pos1[0]) * Math.PI/180;
        const Δλ = (pos2[1]-pos1[1]) * Math.PI/180;
        
        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        
        return R * c;
    }
    
    updateStatus(message, type = 'info') {
        const statusElement = document.getElementById('gps-status');
        if (statusElement) {
            statusElement.innerHTML = message;
            statusElement.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'}`;
        }
        console.log('📢 Status:', message);
    }
    
    showError(message) {
        const errorElement = document.getElementById('gps-error');
        if (errorElement) {
            errorElement.innerHTML = `
                <div class="alert alert-danger">
                    <strong>❌ Error GPS:</strong><br>
                    ${message}
                </div>
            `;
            errorElement.style.display = 'block';
        }
        console.error('❌ Error:', message);
    }
    
    showSolutions() {
        const solutionsHtml = `
            <div class="alert alert-info mt-3">
                <strong>🔧 Solusi yang bisa dicoba:</strong>
                <ol>
                    <li><strong>Refresh halaman</strong> (F5 atau Ctrl+R)</li>
                    <li><strong>Aktifkan GPS</strong> di device Anda</li>
                    <li><strong>Allow location</strong> di browser (klik 🔒 di address bar)</li>
                    <li><strong>Coba browser lain</strong> (Chrome/Firefox)</li>
                    <li><strong>Coba di tempat terbuka</strong> (tidak di dalam gedung)</li>
                    <li><strong>Restart browser</strong> atau device</li>
                </ol>
                <button onclick="location.reload()" class="btn btn-primary mt-2">
                    🔄 Refresh Halaman
                </button>
            </div>
        `;
        
        const errorElement = document.getElementById('gps-error');
        if (errorElement) {
            errorElement.innerHTML += solutionsHtml;
        }
    }
    
    enableAttendanceButton() {
        const button = document.getElementById('attendance-button');
        if (button) {
            button.disabled = false;
            button.innerHTML = '✅ Absen Sekarang';
            button.className = 'btn btn-success';
        }
    }
    
    disableAttendanceButton() {
        const button = document.getElementById('attendance-button');
        if (button) {
            button.disabled = true;
            button.innerHTML = '❌ Di Luar Area Kantor';
            button.className = 'btn btn-secondary';
        }
    }
    
    stopWatching() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
            console.log('⏹️ GPS tracking stopped');
        }
    }
}

// Export untuk digunakan di file lain
window.AttendanceMap = AttendanceMap;
