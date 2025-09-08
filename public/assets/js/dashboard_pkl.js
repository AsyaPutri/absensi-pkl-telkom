// ==================== CONDITIONAL LOGIC INTEGRATION ====================
// Application State dengan conditional rules
const ConditionalAttendanceSystem = {
    // Rules untuk lokasi berdasarkan kondisi kesehatan
    healthLocationRules: {
        'sehat': ['office'],                    // Sehat: hanya office
        'kurang-fit': ['office', 'wfh'],       // Kurang fit: office atau WFH  
        'sakit': ['wfh']                       // Sakit: hanya WFH
    },
    
    currentFormData: {
        condition: 'sehat',
        location: 'office',
        activity: '',
        kendala: ''
    },
    
    // Initialize conditional system
    init: function() {
        console.log('🎯 Initializing Conditional Attendance System...');
        this.bindHealthConditionEvents();
        this.bindLocationEvents();
        this.setDefaultSelections();
        this.initializeAutoSave();
    },
    
    // Bind health condition selection events
    bindHealthConditionEvents: function() {
        document.querySelectorAll('[data-type="condition"]').forEach(card => {
            card.addEventListener('click', (e) => {
                if (card.classList.contains('disabled')) return;
                
                const conditionValue = card.getAttribute('data-value');
                
                // Update UI selection
                document.querySelectorAll('[data-type="condition"]').forEach(c => 
                    c.classList.remove('selected'));
                card.classList.add('selected');
                
                // Update form data
                this.currentFormData.condition = conditionValue;
                
                // Apply conditional logic untuk lokasi
                this.updateLocationOptionsBasedOnHealth(conditionValue);
                
                // Show health-specific message
                this.showHealthConditionMessage(conditionValue);
                
                console.log('🏥 Health condition selected:', conditionValue);
            });
        });
    },
    
    // Update location options berdasarkan kondisi kesehatan
    updateLocationOptionsBasedOnHealth: function(healthCondition) {
        const allowedLocations = this.healthLocationRules[healthCondition] || ['office'];
        const officeOption = document.getElementById('officeOption');
        const wfhOption = document.getElementById('wfhOption');
        
        if (!officeOption || !wfhOption) return;
        
        // Reset semua location cards
        [officeOption, wfhOption].forEach(option => {
            const card = option.querySelector('.option-card');
            if (card) {
                card.classList.remove('selected', 'disabled');
                this.removeAvailabilityIndicator(card);
            }
        });
        
        // Apply conditional logic
        if (allowedLocations.includes('office')) {
            officeOption.classList.remove('d-none');
            this.addAvailabilityIndicator(officeOption.querySelector('.option-card'), 'available');
        } else {
            officeOption.classList.add('d-none');
        }
        
        if (allowedLocations.includes('wfh')) {
            wfhOption.classList.remove('d-none');
            this.addAvailabilityIndicator(wfhOption.querySelector('.option-card'), 'available');
        } else {
            wfhOption.classList.add('d-none');
        }
        
        // Auto-select jika hanya ada satu pilihan
        if (allowedLocations.length === 1) {
            const autoSelectValue = allowedLocations[0];
            const autoSelectOption = autoSelectValue === 'office' ? officeOption : wfhOption;
            const autoSelectCard = autoSelectOption.querySelector('.option-card');
            
            if (autoSelectCard) {
                autoSelectCard.classList.add('selected');
                this.currentFormData.location = autoSelectValue;
            }
            
            // Show auto-selection message
            this.showAutoSelectionMessage(healthCondition, autoSelectValue);
        } else {
            // Clear selection untuk multiple options
            this.currentFormData.location = '';
        }
    },
    
    // Add availability indicator
    addAvailabilityIndicator: function(card, type) {
        if (!card) return;
        
        this.removeAvailabilityIndicator(card);
        
        const indicator = document.createElement('div');
        indicator.className = `availability-indicator ${type}`;
        indicator.innerHTML = type === 'available' ? 
            '<i class="bi bi-check-circle-fill"></i>' : 
            '<i class="bi bi-x-circle-fill"></i>';
        
        // Style indicator
        indicator.style.cssText = `
            position: absolute;
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            z-index: 2;
            background: ${type === 'available' ? '#28a745' : '#dc3545'};
            color: white;
        `;
        
        card.style.position = 'relative';
        card.appendChild(indicator);
    },
    
    // Remove availability indicator
    removeAvailabilityIndicator: function(card) {
        if (!card) return;
        const indicator = card.querySelector('.availability-indicator');
        if (indicator) indicator.remove();
    },
    
    // Show health condition message
    showHealthConditionMessage: function(condition) {
        const messages = {
            'sehat': {
                text: '😊 Kondisi sehat! Siap bekerja dengan optimal di kantor.',
                type: 'success'
            },
            'kurang-fit': {
                text: '😐 Kurang fit? Anda bisa memilih bekerja di kantor atau dari rumah.',
                type: 'warning'
            },
            'sakit': {
                text: '🤒 Kondisi sakit. Sebaiknya bekerja dari rumah dan fokus pemulihan.',
                type: 'danger'
            }
        };
        
        const messageData = messages[condition];
        if (messageData) {
            this.showConditionalAlert(messageData.text, messageData.type);
        }
    },
    
    // Show auto-selection message
    showAutoSelectionMessage: function(condition, location) {
        const locationText = location === 'office' ? 'Kantor 🏢' : 'Work From Home 🏠';
        const conditionText = {
            'sehat': 'Sehat',
            'kurang-fit': 'Kurang Fit', 
            'sakit': 'Sakit'
        }[condition];
        
        let message = '';
        let alertType = 'info';
        
        if (condition === 'sakit') {
            message = `🏥 Karena kondisi Anda ${conditionText}, lokasi kerja otomatis dipilih: ${locationText}. Jaga kesehatan dan istirahat yang cukup!`;
            alertType = 'danger';
        } else if (condition === 'sehat') {
            message = `💪 Kondisi Anda ${conditionText}, lokasi kerja: ${locationText}. Semangat bekerja!`;
            alertType = 'success';
        }
        
        if (message) {
            setTimeout(() => {
                this.showConditionalAlert(message, alertType);
            }, 500);
        }
    },
    
    // Show conditional alert
    showConditionalAlert: function(message, type) {
        // Remove existing conditional alert
        const existingAlert = document.querySelector('.conditional-alert');
        if (existingAlert) existingAlert.remove();
        
        // Create new alert
        const alertHTML = `
            <div class="conditional-alert alert alert-${type} alert-dismissible fade show" role="alert" 
                 style="margin-top: 1rem; border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 
                        type === 'warning' ? 'exclamation-triangle' : 
                        type === 'danger' ? 'x-circle' : 'info-circle'} me-2"></i>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert after location section
        const locationSection = document.querySelector('.location-section');
        if (locationSection) {
            locationSection.insertAdjacentHTML('afterend', alertHTML);
        }
    },
    
    // Bind location events
    bindLocationEvents: function() {
        document.querySelectorAll('[data-type="location"]').forEach(card => {
            card.addEventListener('click', (e) => {
                if (card.classList.contains('disabled') || 
                    card.closest('.d-none')) return;
                
                const locationValue = card.getAttribute('data-value');
                
                // Update UI selection
                document.querySelectorAll('[data-type="location"]').forEach(c => 
                    c.classList.remove('selected'));
                card.classList.add('selected');
                
                // Update form data
                this.currentFormData.location = locationValue;
                
                console.log('📍 Location selected:', locationValue);
            });
        });
    },
    
    // Set default selections
    setDefaultSelections: function() {
        // Set default condition to 'sehat'
        const defaultConditionCard = document.querySelector('[data-type="condition"][data-value="sehat"]');
        if (defaultConditionCard && !defaultConditionCard.classList.contains('selected')) {
            defaultConditionCard.classList.add('selected');
            this.currentFormData.condition = 'sehat';
            this.updateLocationOptionsBasedOnHealth('sehat');
        }
    },
    
    // Initialize auto-save functionality
    initializeAutoSave: function() {
        const activityInput = document.getElementById('activity');
        const kendalaInput = document.getElementById('kendala');
        
        if (activityInput) {
            activityInput.addEventListener('input', this.debounce(() => {
                this.currentFormData.activity = activityInput.value;
                this.autoSaveData();
            }, 2000));
        }
        
        if (kendalaInput) {
            kendalaInput.addEventListener('input', this.debounce(() => {
                this.currentFormData.kendala = kendalaInput.value;
                this.autoSaveData();
            }, 2000));
        }
    },
    
    // Auto-save data
    autoSaveData: function() {
        const currentStatus = document.getElementById('submitAbsen')?.getAttribute('data-status');
        
        if (currentStatus === 'checked_in') {
            const saveData = {
                activity: this.currentFormData.activity,
                kendala: this.currentFormData.kendala,
                timestamp: new Date().toISOString()
            };
            
            // Save to localStorage as fallback
            localStorage.setItem('attendance_draft', JSON.stringify(saveData));
            
            // Show save indicator
            this.showSaveIndicator('Tersimpan otomatis', 'success');
            
            console.log('💾 Auto-saved:', saveData);
        }
    },
    
    // Show save indicator
    showSaveIndicator: function(message, type) {
        const existing = document.querySelector('.auto-save-indicator');
        if (existing) existing.remove();

        const indicator = document.createElement('div');
        indicator.className = 'auto-save-indicator';
        indicator.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-1"></i>
            ${message}
        `;
        
        // Style indicator
        indicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 8px 16px;
            font-size: 0.875rem;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            background: ${type === 'success' ? '#d4edda' : '#fff3cd'};
            color: ${type === 'success' ? '#155724' : '#856404'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : '#ffeaa7'};
            animation: slideInRight 0.3s ease-out;
        `;

        document.body.appendChild(indicator);

        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => indicator.remove(), 300);
            }
        }, 2000);
    },
    
    // Debounce utility
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Validate form dengan conditional logic
    validateForm: function() {
        const currentStatus = document.getElementById('submitAbsen')?.getAttribute('data-status');
        
        // Validate activity untuk checkout
        if (currentStatus === 'checked_in') {
            const activity = document.getElementById('activity')?.value.trim();
            if (!activity) {
                showAlert('Aktivitas wajib diisi untuk check out!', 'warning');
                document.getElementById('activity')?.focus();
                return false;
            }
        }
        
        // Validate health condition selection
        const selectedCondition = document.querySelector('[data-type="condition"].selected');
        if (!selectedCondition) {
            showAlert('Pilih kondisi kesehatan Anda!', 'warning');
            return false;
        }
        
        // Validate location selection
        const selectedLocation = document.querySelector('[data-type="location"].selected');
        if (!selectedLocation) {
            const condition = selectedCondition.getAttribute('data-value');
            const allowedLocations = this.healthLocationRules[condition];
            
            if (allowedLocations.length > 1) {
                showAlert('Pilih lokasi kerja Anda!', 'warning');
                return false;
            }
        }
        
        return true;
    },
    
    // Get form data untuk submission
    getFormData: function() {
        return {
            condition: this.currentFormData.condition,
            location: this.currentFormData.location,
            activity: document.getElementById('activity')?.value || '',
            kendala: document.getElementById('kendala')?.value || '',
            timestamp: new Date().toISOString()
        };
    }
};

// ==================== CSS ANIMATIONS ====================
// Add required CSS animations
const conditionalCSS = `
<style>
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

.option-card {
    transition: all 0.3s ease;
    position: relative;
}

.option-card.selected {
    border-color: #007bff !important;
    background-color: #f8f9ff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.15) !important;
}

.option-card:hover:not(.disabled) {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.conditional-alert {
    animation: slideInDown 0.3s ease-out;
}

@keyframes slideInDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
`;

// Inject CSS
document.head.insertAdjacentHTML('beforeend', conditionalCSS);

// ==================== INTEGRATION WITH EXISTING DASHBOARD ====================
// Override existing validateForm function
const originalValidateForm = window.validateForm;
window.validateForm = function() {
    return ConditionalAttendanceSystem.validateForm();
};

// Override existing initializeOptionCards function  
const originalInitializeOptionCards = window.initializeOptionCards;
window.initializeOptionCards = function() {
    if (originalInitializeOptionCards) {
        originalInitializeOptionCards();
    }
    ConditionalAttendanceSystem.init();
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait for existing dashboard to initialize
    setTimeout(() => {
        ConditionalAttendanceSystem.init();
        console.log('✅ Conditional Attendance System initialized');
    }, 1000);
});

// Export untuk debugging
window.ConditionalAttendanceSystem = ConditionalAttendanceSystem;

console.log('🎯 Conditional Logic Integration loaded successfully');
