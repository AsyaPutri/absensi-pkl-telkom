// Email validation functionality
const emailInput = document.getElementById('email');
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Real-time email validation
emailInput.addEventListener('input', function() {
    const email = this.value.trim();
    
    if (email && !emailRegex.test(email)) {
        this.classList.add('invalid');
        this.classList.remove('valid');
    } else if (email && emailRegex.test(email)) {
        this.classList.add('valid');
        this.classList.remove('invalid');
    } else {
        this.classList.remove('invalid', 'valid');
    }
});

// Email blur validation
emailInput.addEventListener('blur', function() {
    const email = this.value.trim();
    
    if (email && !emailRegex.test(email)) {
        this.style.borderColor = '#e74c3c';
        showEmailError('Format email tidak valid');
    } else if (email) {
        this.style.borderColor = '#27ae60';
        hideEmailError();
    } else {
        this.style.borderColor = '#e0e0e0';
        hideEmailError();
    }
});

// Show email error function
function showEmailError(message) {
    let errorDiv = document.querySelector('.email-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'email-error';
        emailInput.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
    errorDiv.classList.add('show');
}

// Hide email error function
function hideEmailError() {
    const errorDiv = document.querySelector('.email-error');
    if (errorDiv) {
        errorDiv.classList.remove('show');
    }
}

// Show/Hide password functionality
document.getElementById('showPassword').addEventListener('change', function() {
    const passwordInput = document.getElementById('password');
    if (this.checked) {
        passwordInput.type = 'text';
    } else {
        passwordInput.type = 'password';
    }
});

// reCAPTCHA mock functionality
let recaptchaChecked = false;
const recaptchaMock = document.getElementById('recaptcha-mock');
const recaptchaCheckbox = document.getElementById('recaptcha-checkbox');
const recaptchaInput = document.getElementById('recaptcha-input');
const loginButton = document.getElementById('login-button');

recaptchaMock.addEventListener('click', function() {
    recaptchaChecked = !recaptchaChecked;
    
    if (recaptchaChecked) {
        recaptchaMock.classList.add('checked');
        recaptchaCheckbox.classList.add('checked');
        recaptchaInput.value = 'verified';
        updateLoginButtonState();
    } else {
        recaptchaMock.classList.remove('checked');
        recaptchaCheckbox.classList.remove('checked');
        recaptchaInput.value = '';
        updateLoginButtonState();
    }
});

// Update login button state based on form validation
function updateLoginButtonState() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const role = document.getElementById('role').value;
    
    const isEmailValid = email && emailRegex.test(email);
    const isFormComplete = isEmailValid && password && role && recaptchaChecked;
    
    loginButton.disabled = !isFormComplete;
}

// Add event listeners to form inputs for real-time validation
document.getElementById('email').addEventListener('input', updateLoginButtonState);
document.getElementById('password').addEventListener('input', updateLoginButtonState);
document.getElementById('role').addEventListener('change', updateLoginButtonState);

// Form validation before submit
document.querySelector('.login-form').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const role = document.getElementById('role').value;
    
    if (!email || !password || !role || !recaptchaChecked) {
        e.preventDefault();
        alert('Mohon lengkapi semua field dan verifikasi reCAPTCHA');
        return false;
    }
    
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Format email tidak valid. Contoh: user@example.com');
        emailInput.focus();
        return false;
    }
    
    // Show loading state
    loginButton.textContent = 'LOGGING IN...';
    loginButton.disabled = true;
});