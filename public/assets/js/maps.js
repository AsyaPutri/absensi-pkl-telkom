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
        this.accuracyCircle = null;
        this.isInOffice = false;
        this.currentLocation = null;
        this.officeName = 'Telkom Indonesia';
        this.map = null;
        
        this.initializeMap();
        this.requestPermissionAndStart();
    }
    
    async requestPermissionAndStart() {
        console.log('📍 Requesting location permission...');
        
        // Update UI: Loading state
        this.updateLocationInfo('🔍 Meminta izin akses lokasi...', 'info');
        this.updateLocationBadge('🔍 Requesting GPS...', 'info');
        
        try {
            const testOptions = {
                enableHighAccuracy: false,
                timeout: 8000,
                maximumAge: 60000
            };
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    console.log('✅ Permission granted, starting GPS tracking...');
                    this.updateLocationInfo('✅ Izin diberikan, memulai pelacakan GPS...', 'success');
                    this.updateLocationBadge('✅ GPS Active', 'success');
                    setTimeout(() => this.startWatching(), 1000);
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
        
        try {
            // Hapus placeholder loading
            this.removeMapPlaceholder();
            
            // Inisialisasi peta
            this.map = L.map(this.mapId, {
                zoomControl: true,
                attributionControl: true
            }).setView(this.officeLocation, 16);
            
            // Tambah tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                subdomains: ['a', 'b', 'c']
            }).addTo(this.map);
            
            // Tambah marker kantor dengan style CSS
            this.officeMarker = L.marker(this.officeLocation, {
                icon: L.divIcon({
                    className: 'custom-marker office-marker',
                    html: '<i class="fas fa-building" style="font-size: 24px;"></i>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 40],
                    popupAnchor: [0, -40]
                })
            }).addTo(this.map).bindPopup(`
                <div class="text-center">
                    <strong><i class="fas fa-building me-1"></i>${this.officeName}</strong><br>
                    <small class="text-muted">Radius: ${this.officeRadius}m</small>
                </div>
            `);
            
            // Tambah circle radius kantor
            this.officeCircle = L.circle(this.officeLocation, {
                color: '#007bff',
                fillColor: '#007bff',
                fillOpacity: 0.1,
                radius: this.officeRadius,
                weight: 2,
                dashArray: '5, 5'
            }).addTo(this.map);
            
            // Tambah location info badge
            this.addLocationBadge();
            
            console.log('✅ Map initialized successfully');
            
            // Update office name di UI
            this.updateOfficeName();
            
            // Fit map to show office and radius
            const bounds = this.officeCircle.getBounds();
            this.map.fitBounds(bounds, { padding: [20, 20] });
            
        } catch (error) {
            console.error('❌ Map initialization error:', error);
            this.showError('Error menginisialisasi peta: ' + error.message);
            this.showMapPlaceholder('❌ Error loading map');
        }
    }
    
    removeMapPlaceholder() {
        const mapElement = document.getElementById(this.mapId);
        if (mapElement) {
            mapElement.innerHTML = '';
            mapElement.classList.remove('map-loading');
        }
    }
    
    showMapPlaceholder(message = '🗺️ Loading map...') {
        const mapElement = document.getElementById(this.mapId);
        if (mapElement) {
            mapElement.innerHTML = `
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                    <p class="mb-0">${message}</p>
                </div>
            `;
            mapElement.classList.add('map-loading');
        }
    }
    
    addLocationBadge() {
        // Tambah location info badge ke map container
        const mapContainer = document.querySelector(`#${this.mapId}`).parentElement;
        if (mapContainer && !mapContainer.querySelector('.location-info')) {
            const badge = document.createElement('div');
            badge.className = 'location-info';
            badge.id = 'location-badge';
            badge.innerHTML = '<i class="fas fa-satellite-dish"></i>Initializing GPS...';
            mapContainer.appendChild(badge);
        }
    }
    
    updateLocationBadge(message, type = 'info') {
        const badge = document.getElementById('location-badge');
        if (badge) {
            const icons = {
                'success': 'fas fa-check-circle',
                'warning': 'fas fa-exclamation-triangle',
                'danger': 'fas fa-times-circle',
                'info': 'fas fa-satellite-dish'
            };
            
            badge.innerHTML = `<i class="${icons[type]}"></i>${message}`;
            
            // Update badge color
            badge.className = `location-info gps-status-${type === 'success' ? 'active' : type === 'danger' ? 'error' : 'warning'}`;
        }
    }
    
    startWatching() {
        console.log('👀 Starting GPS tracking...');
        
        const options = {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 5000
        };
        
        this.updateLocationInfo('🔍 Mencari sinyal GPS...', 'info');
        this.updateLocationBadge('🔍 Searching GPS...', 'warning');
        
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
        
        // Simpan lokasi current
        this.currentLocation = {
            latitude: lat,
            longitude: lng,
            accuracy: accuracy,
            timestamp: new Date()
        };
        
        // Update posisi user di peta
        this.updateUserLocation([lat, lng], accuracy);
        
        // Cek apakah dalam radius kantor
        this.checkOfficeRadius([lat, lng]);
        
        // Update UI dengan info lokasi
        const distance = this.calculateDistance([lat, lng], this.officeLocation);
        const isInRange = distance <= this.officeRadius;
        
        let statusMessage = `📍 Lokasi ditemukan (akurasi: ±${Math.round(accuracy)}m)`;
        let badgeMessage = '';
        
        if (isInRange) {
            statusMessage += ` - ✅ Dalam area kantor`;
            badgeMessage = `✅ In Office (${Math.round(distance)}m)`;
            this.updateLocationInfo(statusMessage, 'success');
            this.updateLocationBadge(badgeMessage, 'success');
        } else {
            statusMessage += ` - ⚠️ Di luar area kantor (${Math.round(distance)}m)`;
            badgeMessage = `⚠️ Outside Office (${Math.round(distance)}m)`;
            this.updateLocationInfo(statusMessage, 'warning');
            this.updateLocationBadge(badgeMessage, 'warning');
        }
    }
    
    handleLocationError(error) {
        console.error('❌ GPS Error:', error);
        
        let errorMessage = '';
        let badgeMessage = '❌ GPS Error';
        let errorCode = error.code || 0;
        
        switch(errorCode) {
            case 1: // PERMISSION_DENIED
                errorMessage = '🚫 Izin GPS ditolak. Klik ikon 🔒 di address bar, pilih "Allow" untuk Location, lalu refresh halaman.';
                badgeMessage = '🚫 Permission Denied';
                break;
                
            case 2: // POSITION_UNAVAILABLE
                errorMessage = '📡 Sinyal GPS tidak tersedia. Pastikan GPS device aktif dan coba di tempat terbuka.';
                badgeMessage = '📡 No Signal';
                break;
                
            case 3: // TIMEOUT
                errorMessage = '⏰ Timeout GPS. Koneksi lambat atau sinyal lemah. Coba refresh halaman.';
                badgeMessage = '⏰ Timeout';
                break;
                
            default:
                errorMessage = `❌ Error GPS (${errorCode}): ${error.message || 'Unknown error'}. Coba refresh halaman atau gunakan browser lain.`;
                badgeMessage = `❌ Error ${errorCode}`;
                break;
        }
        
        this.updateLocationInfo(errorMessage, 'danger');
        this.updateLocationBadge(badgeMessage, 'danger');
        this.showSolutions();
    }
    
    updateUserLocation(position, accuracy) {
        // Hapus marker lama
        if (this.userMarker) {
            this.map.removeLayer(this.userMarker);
        }
        
        if (this.accuracyCircle) {
            this.map.removeLayer(this.accuracyCircle);
        }
        
        // Tentukan icon berdasarkan lokasi
        const isInRange = this.calculateDistance(position, this.officeLocation) <= this.officeRadius;
        const markerIcon = isInRange ? 'fas fa-user-check' : 'fas fa-user';
        const markerClass = isInRange ? 'custom-marker user-marker gps-status-active' : 'custom-marker user-marker gps-status-warning';
        
        // Tambah marker user baru dengan style CSS
        this.userMarker = L.marker(position, {
            icon: L.divIcon({
                className: markerClass,
                html: `<i class="${markerIcon}" style="font-size: 20px;"></i>`,
                iconSize: [30, 30],
                iconAnchor: [15, 30],
                popupAnchor: [0, -30]
            })
        }).addTo(this.map).bindPopup(`
            <div class="text-center">
                <strong><i class="fas fa-user me-1"></i>Lokasi Anda</strong><br>
                <small class="text-muted">Akurasi: ±${Math.round(accuracy)}m</small><br>
                <small class="text-muted">${new Date().toLocaleTimeString()}</small>
            </div>
        `);
        
        // Tambah circle akurasi
        this.accuracyCircle = L.circle(position, {
            color: isInRange ? '#28a745' : '#ffc107',
            fillColor: isInRange ? '#28a745' : '#ffc107',
            fillOpacity: 0.1,
            radius: accuracy,
            weight: 1,
            dashArray: '3, 3'
        }).addTo(this.map);
        
        // Center map ke user location dengan smooth animation
        this.map.flyTo(position, Math.max(this.map.getZoom(), 17), {
            animate: true,
            duration: 1.5
        });
        
        console.log('📍 User location updated on map');
    }
    
    checkOfficeRadius(userPosition) {
        const distance = this.calculateDistance(userPosition, this.officeLocation);
        const wasInOffice = this.isInOffice;
        this.isInOffice = distance <= this.officeRadius;
        
        console.log(`📏 Distance to office: ${Math.round(distance)}m, In range: ${this.isInOffice}`);
        
        // Trigger location change event if status changed
        if (wasInOffice !== this.isInOffice) {
            this.onLocationStatusChange(this.isInOffice, distance);
        }
    }
    
    onLocationStatusChange(isInOffice, distance) {
        console.log(`🔄 Location status changed: ${isInOffice ? 'IN' : 'OUT'} office`);
        
        // Update location options dengan animasi
        this.updateLocationOptionsAnimated(isInOffice);
        
        // Dispatch custom event untuk dashboard
        const event = new CustomEvent('locationStatusChanged', {
            detail: {
                isInOffice: isInOffice,
                distance: distance,
                location: this.currentLocation
            }
        });
        
        document.dispatchEvent(event);
    }
    
    updateLocationOptionsAnimated(isInRange) {
        const officeOption = document.getElementById('officeOption');
        const wfhOption = document.getElementById('wfhOption');
        
        if (officeOption && wfhOption) {
            const officeCard = officeOption.querySelector('.option-card');
            const wfhCard = wfhOption.querySelector('.option-card');
            
            if (isInRange) {
                // Animate to office selection
                if (officeCard) {
                    officeCard.classList.add('selected');
                    officeCard.style.animation = 'pulse 0.5s ease-in-out';
                }
                if (wfhCard) {
                    wfhCard.classList.remove('selected');
                }
                
                // Hide WFH option with fade
                wfhOption.style.transition = 'opacity 0.3s ease';
                wfhOption.style.opacity = '0.5';
                setTimeout(() => {
                    wfhOption.classList.add('d-none');
                }, 300);
                
            } else {
                // Show both options
                wfhOption.classList.remove('d-none');
                wfhOption.style.opacity = '1';
                
                // Animate to WFH selection
                if (wfhCard) {
                    wfhCard.classList.add('selected');
                    wfhCard.style.animation = 'pulse 0.5s ease-in-out';
                }
                if (officeCard) {
                    officeCard.classList.remove('selected');
                }
            }
            
            // Clear animation after completion
            setTimeout(() => {
                if (officeCard) officeCard.style.animation = '';
                if (wfhCard) wfhCard.style.animation = '';
            }, 500);
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
    
    updateLocationInfo(message, type = 'info') {
        const locationInfo = document.getElementById('location-info');
        if (locationInfo) {
            const iconMap = {
                'success': 'fas fa-check-circle',
                'warning': 'fas fa-exclamation-triangle', 
                'danger': 'fas fa-times-circle',
                'info': 'fas fa-info-circle'
            };
            
            locationInfo.innerHTML = `
                <div class="alert alert-${type}">
                    <div class="d-flex align-items-center">
                        <i class="${iconMap[type]} me-2"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;
        }
    }
    
    updateOfficeName() {
        const officeNameElement = document.getElementById('office-name');
        if (officeNameElement) {
            officeNameElement.textContent = this.officeName;
            officeNameElement.classList.remove('loading-pulse');
        }
    }
    
    showError(message) {
        console.error('❌ Error:', message);
        this.updateLocationInfo(message, 'danger');
        this.updateLocationBadge('❌ Error', 'danger');
    }
    
    showSolutions() {
        const locationInfo = document.getElementById('location-info');
        if (locationInfo) {
            const currentContent = locationInfo.innerHTML;
            locationInfo.innerHTML = currentContent + `
                <div class="alert alert-info mt-2">
                    <strong>🔧 Solusi yang bisa dicoba:</strong>
                    <ol class="mb-2 mt-2">
                        <li><strong>Refresh halaman</strong> (F5 atau Ctrl+R)</li>
                        <li><strong>Aktifkan GPS</strong> di device Anda</li>
                        <li><strong>Allow location</strong> di browser (klik 🔒 di address bar)</li>
                        <li><strong>Coba browser lain</strong> (Chrome/Firefox)</li>
                        <li><strong>Coba di tempat terbuka</strong> (tidak di dalam gedung)</li>
                        <li><strong>Restart browser</strong> atau device</li>
                    </ol>
                    <button onclick="location.reload()" class="btn btn-primary btn-sm">
                        🔄 Refresh Halaman
                    </button>
                </div>
            `;
        }
    }
    
    // Method untuk dashboard mengambil data lokasi
    getCurrentLocationForAttendance() {
        if (!this.currentLocation) {
            return {
                latitude: null,
                longitude: null,
                accuracy: null,
                distance: null,
                isInRange: false,
                officeName: this.officeName,
                status: 'no_location'
            };
        }
        
        const distance = this.calculateDistance(
            [this.currentLocation.latitude, this.currentLocation.longitude], 
            this.officeLocation
        );
        
        return {
            latitude: this.currentLocation.latitude,
            longitude: this.currentLocation.longitude,
            accuracy: this.currentLocation.accuracy,
            distance: distance,
            isInRange: distance <= this.officeRadius,
            officeName: this.officeName,
            status: 'active',
            timestamp: this.currentLocation.timestamp
        };
    }
    
    stopWatching() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
            console.log('⏹️ GPS tracking stopped');
        }
    }
    
    destroy() {
        this.stopWatching();
        
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        
        // Remove location badge
        const badge = document.getElementById('location-badge');
        if (badge) {
            badge.remove();
        }
        
        console.log('🗑️ AttendanceMap destroyed');
    }
}

// Initialize map when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Initializing AttendanceMap from maps.js...');
    
    // Koordinat kantor Telkom Indonesia (GANTI SESUAI LOKASI KANTOR ANDA)
    const officeLocation = [-6.2088, 106.8456]; // Jakarta Pusat (contoh)
    const officeRadius = 100; // 100 meter radius
    
    // Show loading placeholder first
    const mapElement = document.getElementById('map');
    if (mapElement) {
        mapElement.innerHTML = `
            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                <p class="mb-0">🗺️ Loading map...</p>
                <small>Initializing GPS system...</small>
            </div>
        `;
        mapElement.classList.add('map-loading');
    }
    
    // Create global map instance with delay
    setTimeout(() => {
        window.attendanceMap = new AttendanceMap('map', officeLocation, officeRadius);
        console.log('✅ AttendanceMap initialized and available globally');
    }, 500);
});

// Export untuk digunakan di file lain
window.AttendanceMap = AttendanceMap;
