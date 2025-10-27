

let currentSection = 'overview';
let isLoggedIn = false;
let currentAuthTab = 'login';
let resendTimer = null;
let registerResendTimer = null;

// Error display functions
function showError(inputId, message) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const parent = input.parentElement;
    
    // حذف خطای قبلی
    const oldError = parent.querySelector('.error-message');
    if (oldError) oldError.remove();
    
    // اضافه کردن border قرمز
    input.classList.add('border-red-500', 'border-2');
    input.classList.remove('border-gray-300');
    
    // ایجاد پیام خطا
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message text-red-600 text-sm mt-1';
    errorDiv.textContent = message;
    parent.appendChild(errorDiv);
}

function clearError(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const parent = input.parentElement;
    
    // حذف پیام خطا
    const errorMsg = parent.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.remove();
    }
    
    // حذف border قرمز
    input.classList.remove('border-red-500', 'border-2');
    input.classList.add('border-gray-300');
}

function clearAllErrors() {
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(error => error.remove());
    
    const redInputs = document.querySelectorAll('.border-red-500');
    redInputs.forEach(input => {
        input.classList.remove('border-red-500', 'border-2');
        input.classList.add('border-gray-300');
    });
}

function showSuccess(message) {
    // ایجاد toast notification ساده
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Persian to English number converter
function convertPersianToEnglish(text) {
    const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    let result = text;
    
    // Convert Persian numbers
    for (let i = 0; i < persianNumbers.length; i++) {
        result = result.replace(new RegExp(persianNumbers[i], 'g'), englishNumbers[i]);
    }
    
    // Convert Arabic numbers
    for (let i = 0; i < arabicNumbers.length; i++) {
        result = result.replace(new RegExp(arabicNumbers[i], 'g'), englishNumbers[i]);
    }
    
    return result;
}

// Auto-apply Persian to English conversion to all inputs
function initializePersianNumberConverter() {
    // Function to handle input events
    function handleInputConversion(event) {
        const input = event.target;
        const originalValue = input.value;
        const convertedValue = convertPersianToEnglish(originalValue);
        
        // Only update if conversion actually changed something
        if (originalValue !== convertedValue) {
            // Store cursor position
            const cursorPosition = input.selectionStart;
            
            // Update the value
            input.value = convertedValue;
            
            // Restore cursor position
            input.setSelectionRange(cursorPosition, cursorPosition);
        }
    }

    // Apply to all existing inputs
    const allInputs = document.querySelectorAll('input[type="text"], input[type="tel"], input[type="number"], input[type="email"], textarea');
    allInputs.forEach(input => {
        input.addEventListener('input', handleInputConversion);
        input.addEventListener('paste', function(event) {
            // Handle paste events with a slight delay to ensure content is pasted
            setTimeout(() => {
                handleInputConversion(event);
            }, 10);
        });
    });

    // Use MutationObserver to handle dynamically added inputs
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Check if the added node is an input
                    if (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA') {
                        node.addEventListener('input', handleInputConversion);
                        node.addEventListener('paste', function(event) {
                            setTimeout(() => {
                                handleInputConversion(event);
                            }, 10);
                        });
                    }
                    
                    // Check for inputs within the added node
                    const inputs = node.querySelectorAll ? node.querySelectorAll('input[type="text"], input[type="tel"], input[type="number"], input[type="email"], textarea') : [];
                    inputs.forEach(input => {
                        input.addEventListener('input', handleInputConversion);
                        input.addEventListener('paste', function(event) {
                            setTimeout(() => {
                                handleInputConversion(event);
                            }, 10);
                        });
                    });
                }
            });
        });
    });

    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
   
    
    // Initialize OTP inputs
    initializeOTPInputs();
    
    // Initialize Persian number converter
    initializePersianNumberConverter();
    
    // Add error clearing event listeners
    addErrorClearingListeners();
    
    // Initialize profile form
    initializeProfileForm();
});

// Add event listeners to clear errors when user types
function addErrorClearingListeners() {
    const inputs = [
        'loginMobileInput',
        'registerFirstName', 
        'registerLastName',
        'registerMobileInput',
        'registerNationalId'
    ];
    
    inputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', () => clearError(inputId));
        }
    });
    
    // For checkbox
    const termsCheckbox = document.getElementById('acceptRegisterTerms');
    if (termsCheckbox) {
        termsCheckbox.addEventListener('change', () => clearError('acceptRegisterTerms'));
    }
}

// Auth Screen Functions
function showAuthScreen() {
    const authScreen = document.getElementById('authScreen');
    const dashboard = document.getElementById('dashboard');
    
    if (authScreen) {
        authScreen.classList.remove('hidden');
    }
    if (dashboard) {
        dashboard.classList.add('hidden');
    }
}

function switchAuthTab(tab) {
    currentAuthTab = tab;
    
    // Update tab buttons
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (tab === 'login') {
        loginTab.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
        loginTab.classList.remove('text-gray-600');
        registerTab.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
        registerTab.classList.add('text-gray-600');
        
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
    } else {
        registerTab.classList.add('bg-white', 'text-green-600', 'shadow-sm');
        registerTab.classList.remove('text-gray-600');
        loginTab.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
        loginTab.classList.add('text-gray-600');
        
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
    }
    
    // Reset forms
    resetAuthForms();
}

function resetAuthForms() {
    // Reset login form
    document.getElementById('loginStep1').classList.remove('hidden');
    document.getElementById('loginStep2').classList.add('hidden');
    document.getElementById('loginMobileInput').value = '';
    clearOTPInputs('.otp-input');
    
    // Reset register form
    document.getElementById('registerStep1').classList.remove('hidden');
    document.getElementById('registerStep2').classList.add('hidden');
    document.getElementById('registerFirstName').value = '';
    document.getElementById('registerLastName').value = '';
    document.getElementById('registerMobileInput').value = '';
    document.getElementById('registerNationalId').value = '';
    document.getElementById('acceptRegisterTerms').checked = false;
    clearOTPInputs('.register-otp-input');
    
    // Clear timers
    if (resendTimer) clearInterval(resendTimer);
    if (registerResendTimer) clearInterval(registerResendTimer);
}

// Login Functions
function sendLoginOTP() {
    const mobile = document.getElementById('loginMobileInput').value;
    
    // Clear previous errors
    clearAllErrors();
    
    if (mobile.length < 11 || !mobile.startsWith('09')) {
        showError('loginMobileInput', 'لطفاً شماره موبایل معتبر وارد کنید (مثال: 09123456789)');
        return;
    }
    
    // Show loading
    showButtonLoading('loginSendBtn');
    
    // Send AJAX request
    jQuery.ajax({
        url: apl_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'apl_send_login_otp',
            mobile: mobile,
            nonce: apl_ajax.login_nonce
        },
        success: function(response) {
            hideButtonLoading('loginSendBtn');
            
            if (response.success) {
                // Only go to OTP step if successful
                document.getElementById('loginSentToNumber').textContent = mobile;
                document.getElementById('loginStep1').classList.add('hidden');
                document.getElementById('loginStep2').classList.remove('hidden');
                
                // Start resend timer
                startResendTimer();
                
                // Focus first OTP input
                document.querySelector('.otp-input').focus();
                
                // Show success message
                showSuccess(response.data.message);
            } else {
                // Show error in mobile input field
                showError('loginMobileInput', response.data.message);
            }
        },
        error: function() {
            hideButtonLoading('loginSendBtn');
            
            // Show error in mobile input field
            showError('loginMobileInput', 'خطا در ارسال درخواست');
        }
    });
}

function verifyLoginOTP() {
    const otp = getOTPValue('.otp-input');
    
    // Clear previous errors
    clearAllErrors();
    
    if (otp.length !== 6) {
        showError('otp-input-0', 'لطفاً کد ۶ رقمی را کامل وارد کنید');
        return;
    }
    
    const mobile = document.getElementById('loginSentToNumber').textContent;
    
    // Show loading
    showButtonLoading('loginVerifyBtn');
    
    // Send AJAX request
    jQuery.ajax({
        url: apl_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'apl_verify_login_otp',
            mobile: mobile,
            otp: otp,
            nonce: apl_ajax.login_nonce
        },
        success: function(response) {
            hideButtonLoading('loginVerifyBtn');
            
            if (response.success) {
                isLoggedIn = true;
                showSuccess(response.data.message);
                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    showDashboard();
                }
            } else {
                showError('otp-input-0', response.data.message);
                clearOTPInputs('.otp-input');
                document.querySelector('.otp-input').focus();
            }
        },
        error: function() {
            hideButtonLoading('loginVerifyBtn');
            showError('otp-input-0', 'خطا در ارسال درخواست');
        }
    });
}

function backToLoginMobile() {
    document.getElementById('loginStep1').classList.remove('hidden');
    document.getElementById('loginStep2').classList.add('hidden');
    clearOTPInputs('.otp-input');
    if (resendTimer) clearInterval(resendTimer);
}

// Register Functions
function sendRegisterOTP() {
    const firstName = document.getElementById('registerFirstName').value.trim();
    const lastName = document.getElementById('registerLastName').value.trim();
    const mobile = document.getElementById('registerMobileInput').value;
    const nationalId = document.getElementById('registerNationalId').value;
    const acceptTerms = document.getElementById('acceptRegisterTerms').checked;
    
    // Clear previous errors
    clearAllErrors();
    
    // Validation
    if (!firstName) {
        showError('registerFirstName', 'لطفاً نام را وارد کنید');
        return;
    }
    
    if (!lastName) {
        showError('registerLastName', 'لطفاً نام خانوادگی را وارد کنید');
        return;
    }
    
    if (mobile.length < 11 || !mobile.startsWith('09')) {
        showError('registerMobileInput', 'لطفاً شماره موبایل معتبر وارد کنید (مثال: 09123456789)');
        return;
    }
    
    if (nationalId.length !== 10) {
        showError('registerNationalId', 'لطفاً کد ملی ۱۰ رقمی معتبر وارد کنید');
        return;
    }
    
    if (!acceptTerms) {
        showError('acceptRegisterTerms', 'لطفاً قوانین و مقررات را مطالعه و تایید کنید');
        return;
    }
    
    // Show loading
    showButtonLoading('registerSendBtn');
    
    // Send AJAX request
    jQuery.ajax({
        url: apl_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'apl_send_register_otp',
            first_name: firstName,
            last_name: lastName,
            mobile: mobile,
            national_id: nationalId,
            nonce: apl_ajax.register_nonce
        },
        success: function(response) {
            hideButtonLoading('registerSendBtn');
            
            if (response.success) {
                // Only go to OTP step if successful
                document.getElementById('registerSentToNumber').textContent = mobile;
                document.getElementById('registerStep1').classList.add('hidden');
                document.getElementById('registerStep2').classList.remove('hidden');
                
                // Start resend timer
                startRegisterResendTimer();
                
                // Focus first OTP input
                document.querySelector('.register-otp-input').focus();
                
                // Show success message
                showSuccess(response.data.message);
            } else {
                // Show error in appropriate field based on the error type
                if (response.data.field) {
                    showError(response.data.field, response.data.message);
                } else {
                    // Default to mobile field if no specific field is provided
                    showError('registerMobileInput', response.data.message);
                }
            }
        },
        error: function() {
            hideButtonLoading('registerSendBtn');
            
            // Show error in mobile input field
            showError('registerMobileInput', 'خطا در ارسال درخواست');
        }
    });
}

function verifyRegisterOTP() {
    const otp = getOTPValue('.register-otp-input');
    
    // Clear previous errors
    clearAllErrors();
    
    if (otp.length !== 6) {
        showError('register-otp-input-0', 'لطفاً کد ۶ رقمی را کامل وارد کنید');
        return;
    }
    
    const mobile = document.getElementById('registerSentToNumber').textContent;
    
    // Show loading
    showButtonLoading('registerVerifyBtn');
    
    // Send AJAX request
    jQuery.ajax({
        url: apl_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'apl_verify_register_otp',
            mobile: mobile,
            otp: otp,
            nonce: apl_ajax.register_nonce
        },
        success: function(response) {
            hideButtonLoading('registerVerifyBtn');
            
            if (response.success) {
                isLoggedIn = true;
                showSuccess(response.data.message);
                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    showDashboard();
                }
            } else {
                showError('register-otp-input-0', response.data.message);
                clearOTPInputs('.register-otp-input');
                document.querySelector('.register-otp-input').focus();
            }
        },
        error: function() {
            hideButtonLoading('registerVerifyBtn');
            showError('register-otp-input-0', 'خطا در ارسال درخواست');
        }
    });
}

function backToRegisterInfo() {
    document.getElementById('registerStep1').classList.remove('hidden');
    document.getElementById('registerStep2').classList.add('hidden');
    clearOTPInputs('.register-otp-input');
    if (registerResendTimer) clearInterval(registerResendTimer);
}

// OTP Input Functions
function initializeOTPInputs() {
    // Login OTP inputs
    const loginOtpInputs = document.querySelectorAll('.otp-input');
    loginOtpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => handleOTPInput(e, loginOtpInputs, 'login'));
        input.addEventListener('keydown', (e) => handleOTPKeydown(e, loginOtpInputs));
        input.addEventListener('paste', (e) => handleOTPPaste(e, loginOtpInputs));
    });
    
    // Register OTP inputs
    const registerOtpInputs = document.querySelectorAll('.register-otp-input');
    registerOtpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => handleOTPInput(e, registerOtpInputs, 'register'));
        input.addEventListener('keydown', (e) => handleOTPKeydown(e, registerOtpInputs));
        input.addEventListener('paste', (e) => handleOTPPaste(e, registerOtpInputs));
    });
}

function handleOTPInput(e, inputs, type) {
    const input = e.target;
    const value = input.value;
    const index = parseInt(input.dataset.index);
    
    // Only allow numbers
    if (!/^\d*$/.test(value)) {
        input.value = '';
        return;
    }
    
    // Add filled class
    if (value) {
        input.classList.add('filled');
    } else {
        input.classList.remove('filled');
    }
    
    // Move to next input
    if (value && index < inputs.length - 1) {
        inputs[index + 1].focus();
    }
    
    // Auto-verify when all inputs are filled
    if (index === inputs.length - 1 && value) {
        const otp = getOTPValue(type === 'login' ? '.otp-input' : '.register-otp-input');
        if (otp.length === 6) {
            setTimeout(() => {
                if (type === 'login') {
                    verifyLoginOTP();
                } else {
                    verifyRegisterOTP();
                }
            }, 300);
        }
    }
}

function handleOTPKeydown(e, inputs) {
    const input = e.target;
    const index = parseInt(input.dataset.index);
    
    // Handle backspace
    if (e.key === 'Backspace' && !input.value && index > 0) {
        inputs[index - 1].focus();
        inputs[index - 1].value = '';
        inputs[index - 1].classList.remove('filled');
    }
    
    // Handle arrow keys
    if (e.key === 'ArrowLeft' && index > 0) {
        inputs[index - 1].focus();
    }
    if (e.key === 'ArrowRight' && index < inputs.length - 1) {
        inputs[index + 1].focus();
    }
}

function handleOTPPaste(e, inputs) {
    e.preventDefault();
    const paste = (e.clipboardData || window.clipboardData).getData('text');
    const digits = paste.replace(/\D/g, '').slice(0, 6);
    
    digits.split('').forEach((digit, index) => {
        if (inputs[index]) {
            inputs[index].value = digit;
            inputs[index].classList.add('filled');
        }
    });
    
    // Focus last filled input or next empty one
    const lastIndex = Math.min(digits.length - 1, inputs.length - 1);
    inputs[lastIndex].focus();
}

function getOTPValue(selector) {
    const inputs = document.querySelectorAll(selector);
    return Array.from(inputs).map(input => input.value).join('');
}

function clearOTPInputs(selector) {
    const inputs = document.querySelectorAll(selector);
    inputs.forEach(input => {
        input.value = '';
        input.classList.remove('filled');
    });
}

// Timer Functions
function startResendTimer() {
    let seconds = 59;
    const resendBtn = document.getElementById('resendBtn');
    const resendTimer = document.getElementById('resendTimer');
    
    resendBtn.disabled = true;
    
    const timer = setInterval(() => {
        resendTimer.textContent = `(${seconds})`;
        seconds--;
        
        if (seconds < 0) {
            clearInterval(timer);
            resendBtn.disabled = false;
            resendTimer.textContent = '';
        }
    }, 1000);
}

function startRegisterResendTimer() {
    let seconds = 59;
    const resendBtn = document.getElementById('registerResendBtn');
    const resendTimer = document.getElementById('registerResendTimer');
    
    resendBtn.disabled = true;
    
    const timer = setInterval(() => {
        resendTimer.textContent = `(${seconds})`;
        seconds--;
        
        if (seconds < 0) {
            clearInterval(timer);
            resendBtn.disabled = false;
            resendTimer.textContent = '';
        }
    }, 1000);
}

function resendOTP() {
    const mobile = document.getElementById('loginSentToNumber').textContent;
    showSuccess(`کد جدید به ${mobile} ارسال شد`);
    clearOTPInputs('.otp-input');
    document.querySelector('.otp-input').focus();
    startResendTimer();
}

function resendRegisterOTP() {
    const mobile = document.getElementById('registerSentToNumber').textContent;
    showSuccess(`کد جدید به ${mobile} ارسال شد`);
    clearOTPInputs('.register-otp-input');
    document.querySelector('.register-otp-input').focus();
    startRegisterResendTimer();
}

// Loading Functions
function showButtonLoading(buttonId) {
    const button = document.getElementById(buttonId);
    const btnText = button.querySelector('.btn-text');
    const btnLoading = button.querySelector('.btn-loading');
    
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    button.disabled = true;
}

function hideButtonLoading(buttonId) {
    const button = document.getElementById(buttonId);
    const btnText = button.querySelector('.btn-text');
    const btnLoading = button.querySelector('.btn-loading');
    
    btnText.classList.remove('hidden');
    btnLoading.classList.add('hidden');
    button.disabled = false;
}

// Dashboard Functions
function showDashboard() {
    const dashboard = document.getElementById('dashboard');
    const authScreen = document.getElementById('authScreen');
    
    if (dashboard) {
        dashboard.classList.remove('hidden');
    }
    if (authScreen) {
        authScreen.classList.add('hidden');
    }
    showSection('overview');
    
    // Initialize file upload when dashboard is shown
    setTimeout(initializeFileUpload, 100);
}

function logout() {
    // Send AJAX request to logout
    jQuery.ajax({
        url: apl_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'apl_logout',
            nonce: apl_ajax.login_nonce
        },
        success: function(response) {
            
          isLoggedIn = false;
            // INSERT_YOUR_CODE
          location.reload();
        },
        error: function() {
            // Even if AJAX fails, logout locally
            isLoggedIn = false;
            
        }
    });
}

function showSection(sectionName) {
    // Hide all sections
    const sections = document.querySelectorAll('.section');
    sections.forEach(section => section.classList.add('hidden'));
    
    // Show selected section
    document.getElementById(sectionName + 'Section').classList.remove('hidden');
    
    // Update navigation
    updateNavigation(sectionName);
    currentSection = sectionName;
    
    // Load invoices when invoices section is shown
    if (sectionName === 'invoices') {
        loadUserInvoices();
    }
}

function updateNavigation(activeSection) {
    // Desktop navigation
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('bg-blue-50', 'text-blue-700', 'font-medium');
        item.classList.add('text-gray-700', 'hover:bg-gray-100');
    });
    
    // Mobile navigation
    const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
    mobileNavItems.forEach(item => {
        item.classList.remove('bg-blue-50', 'text-blue-700', 'font-medium');
        item.classList.add('text-gray-700', 'hover:bg-gray-100');
    });
    
    // Activate current section for desktop
    const activeDesktopNavItem = document.querySelector(`.nav-item[onclick*="showSection('${activeSection}')"]`);
    if (activeDesktopNavItem) {
        activeDesktopNavItem.classList.remove('text-gray-700', 'hover:bg-gray-100');
        activeDesktopNavItem.classList.add('bg-blue-50', 'text-blue-700', 'font-medium');
    }
    
    // Activate current section for mobile
    const activeMobileNavItem = document.querySelector(`.mobile-nav-item[onclick*="showSection('${activeSection}')"]`);
    if (activeMobileNavItem) {
        activeMobileNavItem.classList.remove('text-gray-700', 'hover:bg-gray-100');
        activeMobileNavItem.classList.add('bg-blue-50', 'text-blue-700', 'font-medium');
    }
}

// Mobile menu functions
function toggleMobileMenu() {
    const overlay = document.getElementById('mobileMenuOverlay');
    const sidebar = document.getElementById('mobileSidebar');
    
    overlay.classList.remove('hidden');
    sidebar.classList.remove('translate-x-full');
    
    // Prevent body scroll when menu is open
    document.body.style.overflow = 'hidden';
}

function closeMobileMenu() {
    const overlay = document.getElementById('mobileMenuOverlay');
    const sidebar = document.getElementById('mobileSidebar');
    
    sidebar.classList.add('translate-x-full');
    
    // Hide overlay after animation completes
    setTimeout(() => {
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

// Form interactions
document.addEventListener('change', function(e) {
    if (e.target.type === 'radio') {
        // Handle service type selection
        if (e.target.name === 'serviceType') {
            const cards = document.querySelectorAll('.service-card');
            cards.forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
            });
            e.target.parentElement.querySelector('.service-card').classList.remove('border-gray-200');
            e.target.parentElement.querySelector('.service-card').classList.add('border-blue-500', 'bg-blue-50');
        }
        
        // Handle insurance selection
        if (e.target.name === 'insurance') {
            const cards = document.querySelectorAll('.insurance-card');
            cards.forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
            });
            e.target.parentElement.querySelector('.insurance-card').classList.remove('border-gray-200');
            e.target.parentElement.querySelector('.insurance-card').classList.add('border-blue-500', 'bg-blue-50');
        }
    }
});

// File upload functionality
let uploadedFiles = [];

// Initialize file upload
function initializeFileUpload() {
    const uploadArea = document.querySelector('.file-upload-mobile');
    const fileInput = document.getElementById('prescriptionFile');
    const selectFileBtn = document.querySelector('.file-upload-mobile');

    // Check if elements exist before adding event listeners
    if (!uploadArea || !fileInput) {
        console.warn('File upload elements not found');
        return;
    }

    // Click handlers
    uploadArea.addEventListener('click', () => fileInput.click());
    if (selectFileBtn) {
        selectFileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.click();
        });
    }

    // File input change
    fileInput.addEventListener('change', handleFileSelect);

    // Drag and drop
    uploadArea.addEventListener('dragover', handleDragOver);
    uploadArea.addEventListener('drop', handleFileDrop);
    uploadArea.addEventListener('dragleave', handleDragLeave);
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('border-blue-400', 'bg-blue-50');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('border-blue-400', 'bg-blue-50');
}

function handleFileDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('border-blue-400', 'bg-blue-50');
    const files = Array.from(e.dataTransfer.files);
    processFiles(files);
}

function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    processFiles(files);
}

function processFiles(files) {
    const validFiles = files.filter(file => {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!validTypes.includes(file.type)) {
            alert(`فایل ${file.name} نوع مجاز نیست. فقط JPG، PNG و PDF پذیرفته می‌شود.`);
            return false;
        }
        
        if (file.size > maxSize) {
            alert(`فایل ${file.name} بیش از ۵ مگابایت است.`);
            return false;
        }
        
        return true;
    });

    validFiles.forEach(file => {
        if (!uploadedFiles.find(f => f.name === file.name && f.size === file.size)) {
            uploadedFiles.push(file);
            createFilePreview(file);
        }
    });

    updateFilePreviewVisibility();
}

function createFilePreview(file) {
    const previewContainer = document.getElementById('uploadedFiles');
    const fileId = 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    const previewDiv = document.createElement('div');
    previewDiv.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4';
    previewDiv.id = fileId;

    const isImage = file.type.startsWith('image/');
    const isPDF = file.type === 'application/pdf';

    let previewContent = '';
    
    if (isImage) {
        const imageUrl = URL.createObjectURL(file);
        previewContent = `
            <div class="flex items-start space-x-4 space-x-reverse">
                <div class="flex-shrink-0">
                    <img src="${imageUrl}" alt="${file.name}" class="w-20 h-20 object-cover rounded-lg border border-gray-300">
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                            <p class="text-xs text-gray-500">تصویر • ${formatFileSize(file.size)}</p>
                        </div>
                        <button onclick="removeFile('${fileId}', '${imageUrl}')" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full w-full"></div>
                        </div>
                        <p class="text-xs text-green-600 mt-1">آپلود کامل</p>
                    </div>
                </div>
            </div>
        `;
    } else if (isPDF) {
        previewContent = `
            <div class="flex items-start space-x-4 space-x-reverse">
                <div class="flex-shrink-0">
                    <div class="w-20 h-20 bg-red-100 rounded-lg border border-gray-300 flex items-center justify-center">
                        <i class="fas fa-file-pdf text-red-600 text-2xl"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                            <p class="text-xs text-gray-500">PDF • ${formatFileSize(file.size)}</p>
                        </div>
                        <button onclick="removeFile('${fileId}')" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full w-full"></div>
                        </div>
                        <p class="text-xs text-green-600 mt-1">آپلود کامل</p>
                    </div>
                </div>
            </div>
        `;
    }

    previewDiv.innerHTML = previewContent;
    previewContainer.appendChild(previewDiv);
}

function removeFile(fileId, imageUrl = null) {
    const fileElement = document.getElementById(fileId);
    if (fileElement) {
        // Remove from uploaded files array
        const fileName = fileElement.querySelector('.text-gray-900').textContent;
        uploadedFiles = uploadedFiles.filter(file => file.name !== fileName);
        
        // Revoke object URL for images
        if (imageUrl) {
            URL.revokeObjectURL(imageUrl);
        }
        
        // Remove from DOM
        fileElement.remove();
        
        updateFilePreviewVisibility();
    }
}

function updateFilePreviewVisibility() {
    const previewArea = document.getElementById('uploadedFiles');
    
    if (uploadedFiles.length > 0) {
        previewArea.classList.remove('hidden');
    } else {
        previewArea.classList.add('hidden');
        previewArea.innerHTML = '';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 بایت';
    const k = 1024;
    const sizes = ['بایت', 'کیلوبایت', 'مگابایت'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// Initialize Persian DatePicker
function initializeDatePicker() {
    if (typeof $.fn.persianDatepicker !== 'undefined') {
        $('#persianDatePicker').persianDatepicker({
            format: 'YYYY/MM/DD',
            initialValue: false,
            autoClose: true,
            calendar: {
                persian: {
                    locale: 'fa',
                    showHint: true,
                    leapYearMode: 'algorithmic'
                }
            },
            navigator: {
                enabled: true,
                scroll: {
                    enabled: true
                },
                text: {
                    btnNextText: ">",
                    btnPrevText: "<"
                }
            },
            toolbox: {
                enabled: true,
                calendarSwitch: {
                    enabled: true,
                    format: 'MMMM'
                },
                todayButton: {
                    enabled: true,
                    text: {
                        fa: "امروز"
                    }
                },
                submitButton: {
                    enabled: true,
                    text: {
                        fa: "تایید"
                    }
                },
                calendarSwitchFormat: 'MMMM'
            },
            dayPicker: {
                enabled: true,
                titleFormat: 'YYYY MMMM'
            },
            responsive: true
        });
    } else {
        // Fallback if Persian datepicker doesn't load
        setTimeout(initializeDatePicker, 500);
    }
}

// Payment method switching
function switchPaymentMethod() {
    const cardForm = document.getElementById('cardPaymentForm');
    const walletForm = document.getElementById('walletPaymentForm');
    const selectedMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
    
    if (selectedMethod === 'card') {
        cardForm.classList.remove('hidden');
        walletForm.classList.add('hidden');
    } else {
        cardForm.classList.add('hidden');
        walletForm.classList.remove('hidden');
    }
}

// Toggle discount section
function toggleDiscountSection() {
    const content = document.getElementById('discountContent');
    const icon = document.getElementById('discountToggleIcon');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

// Submit request
function submitRequest() {
    const acceptTerms = document.getElementById('acceptTerms').checked;
    if (!acceptTerms) {
        showError('acceptRegisterTerms', 'لطفاً قوانین و مقررات را مطالعه و تایید کنید.');
        return;
    }

    // Simulate request processing
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i>در حال ثبت درخواست...';
    button.disabled = true;

    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Generate booking number
        const bookingNumber = 'RES-1403-' + String(Math.floor(Math.random() * 1000)).padStart(3, '0');
        document.getElementById('bookingNumber').textContent = bookingNumber;
        
        // Show success modal
        document.getElementById('successModal').classList.remove('hidden');
    }, 2000);
}

// Close success modal and go to orders
function closeSuccessModal() {
    document.getElementById('successModal').classList.add('hidden');
    showSection('orders');
}

// Close success modal and go to home
function closeSuccessModalToHome() {
    document.getElementById('successModal').classList.add('hidden');
    showSection('overview');
}



// Button click handlers
document.addEventListener('click', function(e) {
    const button = e.target.closest('button');
    if (!button) return;
    
    const buttonText = button.textContent.trim();
    
    if (buttonText.includes('رزرو و پرداخت')) {
        // This is handled by onclick="showSection('payment')" now
    } else if (buttonText.includes('پرداخت') && !buttonText.includes('امن')) {
        
    } else if (buttonText.includes('دانلود')) {
        showSuccess('دمو: دانلود فایل در اینجا شروع خواهد شد');
    } else if (buttonText.includes('مشاهده نتایج')) {
        showSection('results');
    }
});

// Payment method change handler
document.addEventListener('change', function(e) {
    if (e.target.name === 'paymentMethod') {
        switchPaymentMethod();
        
        // Update payment method cards
        const cards = document.querySelectorAll('.payment-method-card');
        cards.forEach(card => {
            card.classList.remove('border-blue-500', 'bg-blue-50');
            card.classList.add('border-gray-200');
        });
        e.target.parentElement.querySelector('.payment-method-card').classList.remove('border-gray-200');
        e.target.parentElement.querySelector('.payment-method-card').classList.add('border-blue-500', 'bg-blue-50');
    }
});



// Discount code functionality
function applyDiscount() {
    const discountCode = document.getElementById('discountCode').value.trim();
    const discountMessage = document.getElementById('discountMessage');
    const discountRow = document.getElementById('discountRow');
    const discountAmount = document.getElementById('discountAmount');
    const totalAmount = document.getElementById('totalAmount');
    
    if (!discountCode) {
        showDiscountMessage('لطفاً کد تخفیف را وارد کنید', 'error');
        return;
    }
    
    // Valid discount codes for demo
    const validCodes = {
        'WELCOME20': { amount: 140000, percentage: 20, description: 'تخفیف ۲۰٪ خوش‌آمدگویی' },
        'HEALTH50': { amount: 50000, percentage: null, description: 'تخفیف ۵۰ هزار تومانی' },
        'FAMILY30': { amount: 210000, percentage: 30, description: 'تخفیف ۳۰٪ خانوادگی' }
    };
    
    if (validCodes[discountCode.toUpperCase()]) {
        const discount = validCodes[discountCode.toUpperCase()];
        
        // Show discount row
        discountRow.classList.remove('hidden');
        discountAmount.textContent = `-${discount.amount.toLocaleString()} تومان`;
        
        // Update total amount (700,000 - discount)
        const newTotal = 700000 - discount.amount;
        totalAmount.textContent = `${newTotal.toLocaleString()} تومان`;
        
        // Show success message
        showDiscountMessage(`✓ ${discount.description} با موفقیت اعمال شد`, 'success');
        
        // Update payment button
        const paymentButton = document.querySelector('button[onclick="processPayment()"]');
        if (paymentButton) {
            paymentButton.innerHTML = `<i class="fas fa-lock ml-2"></i>پرداخت امن ${newTotal.toLocaleString()} تومان`;
        }
        
    } else {
        showDiscountMessage('کد تخفیف نامعتبر است. کدهای معتبر: WELCOME20, HEALTH50, FAMILY30', 'error');
        
        // Hide discount row if invalid code
        discountRow.classList.add('hidden');
        totalAmount.textContent = '۷۰۰,۰۰۰ تومان';
        
        // Reset payment button
        const paymentButton = document.querySelector('button[onclick="processPayment()"]');
        if (paymentButton) {
            paymentButton.innerHTML = '<i class="fas fa-lock ml-2"></i>پرداخت امن ۷۰۰,۰۰۰ تومان';
        }
    }
}

function showDiscountMessage(message, type) {
    const discountMessage = document.getElementById('discountMessage');
    discountMessage.classList.remove('hidden', 'text-red-600', 'text-green-600');
    
    if (type === 'error') {
        discountMessage.classList.add('text-red-600');
    } else {
        discountMessage.classList.add('text-green-600');
    }
    
    discountMessage.textContent = message;
}

// Service type configuration
const serviceTypeConfig = {
    'admission': {
        name: 'بخش پذیرش',
        types: ['lab']
    },
    'results': {
        name: 'بخش جوابدهی',
        types: ['lab']
    },
    'hormones': {
        name: 'بخش هورمون شناسی',
        types: ['home', 'lab']
    },
    'hematology': {
        name: 'بخش هماتولوژی',
        types: ['home', 'lab']
    },
    'sampling': {
        name: 'بخش نمونه گیری',
        types: ['home', 'lab']
    },
    'infertility': {
        name: 'بخش ناباروری',
        types: ['lab']
    },
    'microbiology': {
        name: 'بخش میکروب شناسی',
        types: ['home', 'lab', 'delivery']
    },
    'genetic-counseling': {
        name: 'بخش مشاوره ژنتیک',
        types: ['lab']
    },
    'mycology': {
        name: 'بخش قارچ شناسی',
        types: ['home', 'lab', 'delivery']
    },
    'screening': {
        name: 'بخش غربالگری',
        types: ['home', 'lab']
    },
    'cytology': {
        name: 'بخش سیتولوژی',
        types: ['lab', 'delivery']
    },
    'serology': {
        name: 'بخش سرولوژی',
        types: ['home', 'lab']
    },
    'genetics': {
        name: 'بخش ژنتیک',
        types: ['lab']
    },
    'quality-assurance': {
        name: 'بخش تضمین کیفیت',
        types: ['lab']
    },
    'molecular-diagnosis': {
        name: 'بخش تشخیص مولکولی',
        types: ['lab']
    },
    'prenatal-diagnosis': {
        name: 'بخش تشخیص پیش از تولد',
        types: ['lab']
    },
    'pathology': {
        name: 'بخش پاتولوژی',
        types: ['lab', 'delivery']
    },
    'blood-biochemistry': {
        name: 'بخش بیوشیمی خون',
        types: ['home', 'lab']
    },
    'urine-biochemistry': {
        name: 'بخش بیوشیمی ادرار',
        types: ['home', 'lab']
    },
    'immunology': {
        name: 'بخش ایمونولوژی',
        types: ['home', 'lab']
    },
    'immunoclones': {
        name: 'بخش ایمونوکلوس',
        types: ['lab']
    },
    'parasitology': {
        name: 'بخش انگل شناسی',
        types: ['home', 'lab', 'delivery']
    },
    'electrophoresis': {
        name: 'بخش الکتروفورز',
        types: ['lab']
    },
    'allergology': {
        name: 'بخش آلرژی شناسی',
        types: ['home', 'lab']
    }
};

// Service type templates
const serviceTypeTemplates = {
    'home': {
        icon: 'fas fa-home',
        title: 'نمونه‌گیری در منزل',
        description: 'ما پیش شما می‌آییم',
        color: 'blue'
    },
    'lab': {
        icon: 'fas fa-building',
        title: 'مراجعه به آزمایشگاه',
        description: 'مراجعه به مرکز ما',
        color: 'green'
    },
    'delivery': {
        icon: 'fas fa-truck',
        title: 'تحویل نمونه',
        description: 'ارسال نمونه به آزمایشگاه',
        color: 'purple'
    },
    'sample': {
        icon: 'fas fa-truck',
        title: 'ارسال نمونه',
        description: 'ارسال نمونه به آزمایشگاه',
        color: 'purple'
    }
};

// Update service types based on selected service
function updateServiceTypes() {
    const selectedService = document.getElementById('serviceSelect').value;
    const serviceTypeSection = document.getElementById('serviceTypeSection');
    const serviceTypeOptions = document.getElementById('serviceTypeOptions');
    
    if (!selectedService) {
        serviceTypeSection.classList.add('hidden');
        updateAddressVisibility();
        return;
    }
    
    const config = serviceTypeConfig[selectedService];
    if (!config) return;
    
    // Show service type section
    serviceTypeSection.classList.remove('hidden');
    
    // Clear existing options
    serviceTypeOptions.innerHTML = '';
    
    // Generate service type options
    config.types.forEach((type, index) => {
        const template = serviceTypeTemplates[type];
        const isFirst = index === 0;
        
        const optionHTML = `
            <label class="relative cursor-pointer">
                <input type="radio" name="serviceType" value="${type}" class="sr-only" ${isFirst ? 'checked' : ''} onchange="updateAddressVisibility()">
                <div class="service-card border-2 ${isFirst ? 'border-' + template.color + '-500 bg-' + template.color + '-50' : 'border-gray-200 hover:border-' + template.color + '-300'} rounded-lg p-4 text-center">
                    <i class="${template.icon} text-${isFirst ? template.color + '-600' : 'gray-600'} text-2xl mb-2"></i>
                    <h4 class="font-medium text-gray-900">${template.title}</h4>
                    <p class="text-gray-600 text-sm">${template.description}</p>
                </div>
            </label>
        `;
        
        serviceTypeOptions.innerHTML += optionHTML;
    });
    
    // Update address visibility after generating options
    setTimeout(updateAddressVisibility, 100);
}

// Update address section visibility based on service type
function updateAddressVisibility() {
    const selectedServiceType = document.querySelector('input[name="serviceType"]:checked');
    const addressSection = document.getElementById('addressSection');
    
    if (!selectedServiceType) {
        addressSection.classList.add('hidden');
        return;
    }
    
    const serviceType = selectedServiceType.value;
    
    // Show address section for home sampling, delivery services, and sample sending
    if (serviceType === 'home' || serviceType === 'delivery' || serviceType === 'sample') {
        addressSection.classList.remove('hidden');
    } else {
        addressSection.classList.add('hidden');
    }
}

// Authentication functions - OLD VERSIONS REMOVED (replaced with AJAX versions above)

function startResendTimer() {
    let timeLeft = 59;
    const resendBtn = document.getElementById('resendBtn');
    const resendText = document.getElementById('resendText');
    const resendTimer = document.getElementById('resendTimer');
    
    resendBtn.disabled = true;
    resendText.classList.add('hidden');
    resendTimer.classList.remove('hidden');
    
    const timer = setInterval(() => {
        resendTimer.textContent = `(${timeLeft})`;
        timeLeft--;
        
        if (timeLeft < 0) {
            clearInterval(timer);
            resendBtn.disabled = false;
            resendText.classList.remove('hidden');
            resendTimer.classList.add('hidden');
        }
    }, 1000);
}

function startRegisterResendTimer() {
    let timeLeft = 59;
    const resendBtn = document.getElementById('registerResendBtn');
    const resendText = document.getElementById('registerResendText');
    const resendTimer = document.getElementById('registerResendTimer');
    
    resendBtn.disabled = true;
    resendText.classList.add('hidden');
    resendTimer.classList.remove('hidden');
    
    const timer = setInterval(() => {
        resendTimer.textContent = `(${timeLeft})`;
        timeLeft--;
        
        if (timeLeft < 0) {
            clearInterval(timer);
            resendBtn.disabled = false;
            resendText.classList.remove('hidden');
            resendTimer.classList.add('hidden');
        }
    }, 1000);
}

function backToLoginMobile() {
    document.getElementById('loginStep2').classList.add('hidden');
    document.getElementById('loginStep1').classList.remove('hidden');
}

function backToRegisterInfo() {
    document.getElementById('registerStep2').classList.add('hidden');
    document.getElementById('registerStep1').classList.remove('hidden');
}

function switchAuthTab(tab) {
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (tab === 'login') {
        loginTab.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
        loginTab.classList.remove('text-gray-600');
        registerTab.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
        registerTab.classList.add('text-gray-600');
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
    } else {
        registerTab.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
        registerTab.classList.remove('text-gray-600');
        loginTab.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
        loginTab.classList.add('text-gray-600');
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
    }
}



// Multi-step form functionality
let currentStep = 1;
let selectedPackages = [];

// Navigate between steps
function goToStep(stepNumber) {
    // Hide all steps
    for (let i = 1; i <= 5; i++) {
        const step = document.getElementById(`step${i}`);
        if (step) {
            step.classList.add('hidden');
        }
    }

    // Show target step
    const targetStep = document.getElementById(`step${stepNumber}`);
    if (targetStep) {
        targetStep.classList.remove('hidden');
        currentStep = stepNumber;
    }

    // Update progress indicators
    updateProgressIndicators(stepNumber);

    // Populate order summary when going to step 5
    if (stepNumber === 5) {
        populateOrderSummary();
    }
}

// Update progress indicators
function updateProgressIndicators(activeStep) {
    for (let i = 1; i <= 5; i++) {
        const stepIndicator = document.querySelector(`#step${i} .w-8.h-8`);
        
        if (stepIndicator) {
            if (i <= activeStep) {
                stepIndicator.classList.remove('bg-gray-300');
                stepIndicator.classList.add('bg-blue-600', 'text-white');
            } else {
                stepIndicator.classList.remove('bg-blue-600', 'text-white');
                stepIndicator.classList.add('bg-gray-300');
            }
        }

        // Update progress dots for each step
        const stepProgressDots = document.querySelectorAll(`#step${i} .w-3.h-3.rounded-full`);
        stepProgressDots.forEach((dot) => {
            if (i <= activeStep) {
                dot.classList.remove('bg-gray-300');
                dot.classList.add('bg-blue-600');
            } else {
                dot.classList.remove('bg-blue-600');
                dot.classList.add('bg-gray-300');
            }
        });
    }
}

// Handle service type selection
function handleServiceTypeSelection() {
    const requestType = document.querySelector('input[name="requestType"]:checked');
    if (!requestType) return;

    const step2 = document.getElementById('step2');
    const fileUploadForm = document.getElementById('fileUploadForm');
    const ePrescriptionForm = document.getElementById('ePrescriptionForm');
    const testPackagesForm = document.getElementById('testPackagesForm');

    // Hide all forms
    fileUploadForm.classList.add('hidden');
    ePrescriptionForm.classList.add('hidden');
    testPackagesForm.classList.add('hidden');

    // Show relevant form based on selection
    switch (requestType.value) {
        case 'upload':
            fileUploadForm.classList.remove('hidden');
            break;
        case 'electronic':
            ePrescriptionForm.classList.remove('hidden');
            break;
        case 'packages':
            testPackagesForm.classList.remove('hidden');
            break;
    }
}

// Handle delivery method selection
function handleDeliveryMethodSelection() {
    const deliveryMethod = document.querySelector('input[name="deliveryMethod"]:checked');
    const serviceLocationSection = document.getElementById('serviceLocationSection');
    const labScheduleSection = document.getElementById('labScheduleSection');
    const labAddressSection = document.getElementById('labAddressSection');

    // Hide all sections first
    serviceLocationSection.classList.add('hidden');
    labScheduleSection.classList.add('hidden');
    labAddressSection.classList.add('hidden');

    if (deliveryMethod) {
        switch (deliveryMethod.value) {
            case 'home':
                serviceLocationSection.classList.remove('hidden');
                break;
            case 'lab':
                labScheduleSection.classList.remove('hidden');
                break;
            case 'sample':
                labAddressSection.classList.remove('hidden');
                break;
        }
    }
}

// Package data
const packageData = {
    'adult-female': {
        name: 'پکیج چکاپ خانم بالغ',
        price: '۲,۵۰۰,۰۰۰'
    },
    'adult-male': {
        name: 'پکیج چکاپ آقای بالغ',
        price: '۲,۳۰۰,۰۰۰'
    },
    'child': {
        name: 'پکیج چکاپ کودک',
        price: '۱,۸۰۰,۰۰۰'
    },
    'elderly-male': {
        name: 'پکیج چکاپ مرد مسن',
        price: '۳,۲۰۰,۰۰۰'
    },
    'elderly-female': {
        name: 'پکیج چکاپ زن مسن',
        price: '۳,۵۰۰,۰۰۰'
    },
    'work-medical-1': {
        name: 'پکیج طب کار ۱',
        price: '۱,۲۰۰,۰۰۰'
    },
    'work-medical-2': {
        name: 'پکیج طب کار ۲',
        price: '۲,۰۰۰,۰۰۰'
    },
    'pathology-sample': {
        name: 'نمونه پاتولوژی',
        price: '۱,۵۰۰,۰۰۰'
    },
    'pathology-consultation': {
        name: 'مشاوره جواب پاتولوژی',
        price: '۸۰۰,۰۰۰'
    }
};

// Toggle package selection
function togglePackage(packageId) {
    const packageCard = document.querySelector(`[onclick="togglePackage('${packageId}')"]`);
    const selectedIcon = packageCard.querySelector('.package-selected');
    const selectBtn = packageCard.querySelector('.package-select-btn');
    const selectText = packageCard.querySelector('.select-text');
    const deselectText = packageCard.querySelector('.deselect-text');
    const isSelected = !selectedIcon.classList.contains('hidden');
    
    if (isSelected) {
        // Remove from selection
        selectedIcon.classList.add('hidden');
        packageCard.classList.remove('border-blue-300', 'bg-blue-50');
        packageCard.classList.add('border-gray-200');
        
        // Update button text
        selectText.classList.remove('hidden');
        deselectText.classList.add('hidden');
        selectBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
        selectBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        
        // Remove from selectedPackages array
        selectedPackages = selectedPackages.filter(pkg => pkg.id !== packageId);
    } else {
        // Add to selection
        selectedIcon.classList.remove('hidden');
        packageCard.classList.remove('border-gray-200');
        packageCard.classList.add('border-blue-300', 'bg-blue-50');
        
        // Update button text
        selectText.classList.add('hidden');
        deselectText.classList.remove('hidden');
        selectBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        selectBtn.classList.add('bg-red-600', 'hover:bg-red-700');
        
        // Add to selectedPackages array
        const package = {
            id: packageId,
            name: packageData[packageId].name,
            price: packageData[packageId].price
        };
    selectedPackages.push(package);
    }
    
    updateSelectedPackages();
    updateOrderSummary();
}

// Update selected packages display
function updateSelectedPackages() {
    const selectedPackagesDiv = document.getElementById('selectedPackages');
    const selectedPackagesContainer = selectedPackagesDiv.querySelector('#selectedPackagesList');
    
    if (selectedPackages.length === 0) {
        selectedPackagesDiv.classList.add('hidden');
        return;
    }

    selectedPackagesDiv.classList.remove('hidden');
    selectedPackagesContainer.innerHTML = '';

    selectedPackages.forEach((pkg) => {
        const packageHTML = `
            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                <div>
                    <p class="font-medium text-gray-900">${pkg.name}</p>
                    <p class="text-sm text-gray-600">${pkg.price} تومان</p>
                </div>
                <button onclick="removePackageFromSelection('${pkg.id}')" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        selectedPackagesContainer.innerHTML += packageHTML;
    });
}

// Remove package from selection
function removePackageFromSelection(packageId) {
    // Remove from selectedPackages array
    selectedPackages = selectedPackages.filter(pkg => pkg.id !== packageId);
    
    // Update the package card visual state
    const packageCard = document.querySelector(`[onclick="togglePackage('${packageId}')"]`);
    const selectedIcon = packageCard.querySelector('.package-selected');
    const selectBtn = packageCard.querySelector('.package-select-btn');
    const selectText = packageCard.querySelector('.select-text');
    const deselectText = packageCard.querySelector('.deselect-text');
    
    selectedIcon.classList.add('hidden');
    packageCard.classList.remove('border-blue-300', 'bg-blue-50');
    packageCard.classList.add('border-gray-200');
    
    // Update button text
    selectText.classList.remove('hidden');
    deselectText.classList.add('hidden');
    selectBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
    selectBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
    
    updateSelectedPackages();
    updateOrderSummary();
}

// Update order summary
function updateOrderSummary() {
    const packageSummary = document.getElementById('packageSummary');
    const packageTotal = document.getElementById('packageTotal');
    const finalTotal = document.getElementById('finalTotal');

    if (selectedPackages.length > 0) {
        packageSummary.classList.remove('hidden');
        let total = 0;
        selectedPackages.forEach(pkg => {
            total += parseInt(pkg.price.replace(/,/g, ''));
        });
        packageTotal.textContent = `${total.toLocaleString()} تومان`;
        finalTotal.textContent = `${(total + 50000 - 200000).toLocaleString()} تومان`;
    } else {
        packageSummary.classList.add('hidden');
        finalTotal.textContent = '۵۰,۰۰۰ تومان';
    }
}

// Populate order summary with all form data
function populateOrderSummary() {
    // Service type information
    const requestType = document.querySelector('input[name="requestType"]:checked');
    const deliveryMethod = document.querySelector('input[name="deliveryMethod"]:checked');
    
    if (requestType) {
        const requestTypeText = getRequestTypeText(requestType.value);
        document.getElementById('summaryRequestType').textContent = requestTypeText;
    }
    
    if (deliveryMethod) {
        const deliveryText = getDeliveryMethodText(deliveryMethod.value);
        document.getElementById('summaryDeliveryMethod').textContent = deliveryText;
    }

    // Patient information
    const patientName = document.querySelector('#step3 input[type="text"]:first-of-type')?.value;
    const patientLastName = document.querySelector('#step3 input[type="text"]:nth-of-type(2)')?.value;
    const nationalId = document.querySelector('#step3 input[type="text"]:nth-of-type(3)')?.value;
    const mobile = document.querySelector('#step3 input[type="tel"]')?.value;
    
    // Get address information based on delivery method
    let addressText = '';
    const selectedDeliveryMethod = document.querySelector('input[name="deliveryMethod"]:checked');
    if (selectedDeliveryMethod && selectedDeliveryMethod.value === 'home') {
        const city = document.getElementById('citySelect')?.value;
        const address = document.getElementById('addressTextarea')?.value;
        if (city) {
            addressText = getCityText(city);
            if (address) {
                addressText += ' - ' + address;
            }
        }
    } else if (selectedDeliveryMethod && selectedDeliveryMethod.value === 'lab') {
        const labDate = document.getElementById('labDatePicker')?.value;
        const labTime = document.getElementById('labTimeSelect')?.value;
        if (labDate && labTime) {
            const timeText = document.querySelector(`#labTimeSelect option[value="${labTime}"]`)?.textContent;
            addressText = `مراجعه به آزمایشگاه - ${labDate} - ${timeText}`;
        }
    } else if (selectedDeliveryMethod && selectedDeliveryMethod.value === 'sample') {
        addressText = 'ارسال نمونه به آزمایشگاه - اردبیل، خیابان دانشگاه، پلاک ۱۲۳، کد پستی: ۵۶۱۳۸-۳۴۱۳۵';
    }

    if (patientName && patientLastName) {
        document.getElementById('summaryPatientName').textContent = `${patientName} ${patientLastName}`;
    }
    if (nationalId) {
        document.getElementById('summaryNationalId').textContent = nationalId;
    }
    if (mobile) {
        document.getElementById('summaryMobile').textContent = mobile;
    }
    if (addressText) {
        document.getElementById('summaryAddress').textContent = addressText;
    }

    // Insurance information
    const basicInsurance = document.querySelector('#step4 select:first-of-type')?.value;
    const supplementaryInsurance = document.querySelector('#step4 select:nth-of-type(2)')?.value;
    const trackingCode = document.querySelector('#step4 input[type="text"]')?.value;

    if (basicInsurance) {
        document.getElementById('summaryBasicInsurance').textContent = getInsuranceText(basicInsurance);
    }
    if (supplementaryInsurance) {
        document.getElementById('summarySupplementaryInsurance').textContent = getInsuranceText(supplementaryInsurance);
    }
    if (trackingCode) {
        document.getElementById('summaryTrackingCode').textContent = trackingCode;
    }

    // Services list
    updateServicesList();
}

// Get request type text
function getRequestTypeText(value) {
    const types = {
        'upload': 'بارگذاری نسخه',
        'electronic': 'نسخه الکترونیک',
        'packages': 'بسته‌های آزمایش'
    };
    return types[value] || value;
}

// Get delivery method text
function getDeliveryMethodText(value) {
    const methods = {
        'home': 'نمونه‌گیری در منزل',
        'lab': 'مراجعه به آزمایشگاه',
        'sample': 'ارسال نمونه'
    };
    return methods[value] || value;
}

// Get insurance text
function getInsuranceText(value) {
    const insurances = {
        'tamin': 'تأمین اجتماعی',
        'salamat': 'سلامت ایران',
        'mosalah': 'نیروهای مسلح',
        'other': 'سایر',
        'day': 'بیمه دی',
        'alborz': 'بیمه البرز',
        'hafez': 'بیمه حافظ',
        'hekmat': 'بیمه حکمت',
        'dana': 'بیمه دانا',
        'asia': 'بیمه آسیا',
        'iran': 'بیمه ایران',
        'parsian': 'بیمه پارسیان',
        'pasargad': 'بیمه پاسارگاد',
        'moalem': 'بیمه معلم',
        'saman': 'بیمه سامان',
        'sina': 'بیمه سینا',
        'karafarin': 'بیمه کارآفرین',
        'novin': 'بیمه نوین',
        'mellat': 'بیمه ملت'
    };
    return insurances[value] || value;
}

// Get city text
function getCityText(value) {
    const cities = {
        'ardabil': 'اردبیل',
        'namin': 'نمین',
        'astara': 'آستارا',
        'anbaran': 'عنبران',
        'abibiglu': 'ابی بیگلو'
    };
    return cities[value] || value;
}

// Update services list
function updateServicesList() {
    const servicesList = document.getElementById('servicesList');
    const requestType = document.querySelector('input[name="requestType"]:checked');
    
    servicesList.innerHTML = '';

    if (requestType) {
        switch (requestType.value) {
            case 'upload':
                const uploadedFiles = document.querySelectorAll('#uploadedFiles .space-y-3 > div');
                if (uploadedFiles.length > 0) {
                    uploadedFiles.forEach(file => {
                        const fileName = file.querySelector('p').textContent;
                        const serviceHTML = `
                            <div class="flex items-center justify-between py-2 border-b border-gray-200">
                                <div class="flex items-center">
                                    <i class="fas fa-file text-blue-600 ml-3"></i>
                                    <span class="text-gray-900">${fileName}</span>
                                </div>
                            </div>
                        `;
                        servicesList.innerHTML += serviceHTML;
                    });
                } else {
                    servicesList.innerHTML = '<p class="text-gray-600">فایل نسخه آپلود نشده</p>';
                }
                break;
            
            case 'electronic':
                const nationalId = document.querySelector('#ePrescriptionForm input[type="text"]:first-of-type')?.value;
                const doctorName = document.querySelector('#ePrescriptionForm input[type="text"]:nth-of-type(2)')?.value;
                const serviceHTML = `
                    <div class="flex items-center justify-between py-2 border-b border-gray-200">
                        <div class="flex items-center">
                            <i class="fas fa-laptop-medical text-green-600 ml-3"></i>
                            <div>
                                <span class="text-gray-900">نسخه الکترونیک</span>
                                <p class="text-sm text-gray-600">کد ملی: ${nationalId || 'وارد نشده'} - پزشک: ${doctorName || 'وارد نشده'}</p>
                            </div>
                        </div>
                    </div>
                `;
                servicesList.innerHTML = serviceHTML;
                break;
            
            case 'packages':
                if (selectedPackages.length > 0) {
                    selectedPackages.forEach(pkg => {
                        const serviceHTML = `
                            <div class="flex items-center justify-between py-2 border-b border-gray-200">
                                <div class="flex items-center">
                                    <i class="fas fa-box-open text-purple-600 ml-3"></i>
                                    <span class="text-gray-900">${pkg.name}</span>
                                </div>
                                <span class="font-medium text-gray-900">${pkg.price}</span>
                            </div>
                        `;
                        servicesList.innerHTML += serviceHTML;
                    });
                } else {
                    servicesList.innerHTML = '<p class="text-gray-600">هیچ بسته‌ای انتخاب نشده</p>';
                }
                break;
        }
    }
}

// Initialize multi-step form
function initializeMultiStepForm() {
    // Add event listeners for service type selection
    const requestTypeInputs = document.querySelectorAll('input[name="requestType"]');
    requestTypeInputs.forEach(input => {
        input.addEventListener('change', handleServiceTypeSelection);
    });

    // Add event listeners for delivery method selection
    const deliveryMethodInputs = document.querySelectorAll('input[name="deliveryMethod"]');
    deliveryMethodInputs.forEach(input => {
        input.addEventListener('change', handleDeliveryMethodSelection);
    });

    // File upload is handled by initializeFileUpload function

    // Initialize package selection buttons
    const packageButtons = document.querySelectorAll('#testPackagesForm button');
    packageButtons.forEach(button => {
        button.addEventListener('click', function() {
            const packageCard = this.closest('.bg-white');
            const packageName = packageCard.querySelector('h3').textContent;
            const packagePrice = packageCard.querySelector('.text-blue-600').textContent;
            addPackageToCart(packageName, packagePrice);
        });
    });

    // Initialize lab date picker
    const labDatePicker = document.getElementById('labDatePicker');
    if (labDatePicker && typeof $ !== 'undefined') {
        $(labDatePicker).persianDatepicker({
            format: 'YYYY/MM/DD',
            altField: '.observer-example-alt',
            altFormat: 'YYYY/MM/DD',
            observer: true,
            timePicker: {
                enabled: false
            }
        });
    }
}

// Handle file upload
function handleFileUpload(event) {
    const files = event.target.files;
    const uploadedFilesDiv = document.getElementById('uploadedFiles');
    const uploadedFilesContainer = uploadedFilesDiv.querySelector('.space-y-3');

    if (files.length > 0) {
        uploadedFilesDiv.classList.remove('hidden');
        uploadedFilesContainer.innerHTML = '';

        Array.from(files).forEach(file => {
            const fileHTML = `
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                    <div class="flex items-center">
                        <i class="fas fa-file text-blue-600 ml-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">${file.name}</p>
                            <p class="text-sm text-gray-600">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                        </div>
                    </div>
                    <button class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            uploadedFilesContainer.innerHTML += fileHTML;
        });
    } else {
        uploadedFilesDiv.classList.add('hidden');
    }
}

// Initialize datepicker and file upload when dashboard is shown
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery and Persian datepicker to load
    setTimeout(initializeDatePicker, 1000);

    // Initialize multi-step form
    setTimeout(initializeMultiStepForm, 1000);
});

// Toggle order details accordion
function toggleOrderDetails(orderId) {
    const detailsElement = document.getElementById(`${orderId}-details`);
    const button = document.querySelector(`[onclick="toggleOrderDetails('${orderId}')"]`);
    const icon = button.querySelector('i');
    
    if (detailsElement.classList.contains('hidden')) {
        // Show details
        detailsElement.classList.remove('hidden');
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        button.innerHTML = '<i class="fas fa-eye-slash ml-1"></i>بستن';
    } else {
        // Hide details
        detailsElement.classList.add('hidden');
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        button.innerHTML = '<i class="fas fa-eye ml-1"></i>مشاهده';
    }
}

// Profile Form Functions
function initializeProfileForm() {
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileSubmit);
        
        // Add error clearing listeners for profile form inputs
        const profileInputs = [
            'profileFirstName',
            'profileLastName',
            'profileEmail',
            'profileMobile',
            'profileNationalId',
            'profileAddress'
        ];
        
        profileInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', () => clearError(inputId));
            }
        });
    }
}

function handleProfileSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    // Clear previous errors
    clearAllErrors();
    hideProfileMessage();
    
    // Get form values
    const firstName = document.getElementById('profileFirstName').value.trim();
    const lastName = document.getElementById('profileLastName').value.trim();
    const email = document.getElementById('profileEmail').value.trim();
    const mobile = document.getElementById('profileMobile').value.trim();
    const nationalId = document.getElementById('profileNationalId').value.trim();
    const address = document.getElementById('profileAddress').value.trim();
    
    // Check if fields are readonly
    const emailInput = document.getElementById('profileEmail');
    const mobileInput = document.getElementById('profileMobile');
    const isEmailReadonly = emailInput.hasAttribute('readonly');
    const isMobileReadonly = mobileInput.hasAttribute('readonly');
    
    // Validation
    let hasError = false;
    
    // Check first name
    if (!firstName) {
        showError('profileFirstName', 'نام الزامی است');
        hasError = true;
    } else if (firstName.length < 2) {
        showError('profileFirstName', 'نام باید حداقل ۲ کاراکتر باشد');
        hasError = true;
    }
    
    // Check last name
    if (!lastName) {
        showError('profileLastName', 'نام خانوادگی الزامی است');
        hasError = true;
    } else if (lastName.length < 2) {
        showError('profileLastName', 'نام خانوادگی باید حداقل ۲ کاراکتر باشد');
        hasError = true;
    }
    
    // Check email (only if not readonly)
    if (!isEmailReadonly) {
        if (!email) {
            showError('profileEmail', 'ایمیل الزامی است');
            hasError = true;
        } else if (!isValidEmail(email)) {
            showError('profileEmail', 'فرمت ایمیل صحیح نیست');
            hasError = true;
        }
    }
    
    // Check mobile (only if not readonly)
    if (!isMobileReadonly) {
        if (!mobile) {
            showError('profileMobile', 'شماره موبایل الزامی است');
            hasError = true;
        } else if (!isValidMobile(mobile)) {
            showError('profileMobile', 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد');
            hasError = true;
        }
    }
    
    // Check national ID
    if (!nationalId) {
        showError('profileNationalId', 'کد ملی الزامی است');
        hasError = true;
    } else if (!/^\d{10}$/.test(nationalId)) {
        showError('profileNationalId', 'کد ملی باید دقیقاً ۱۰ رقم باشد');
        hasError = true;
    }
    
    // Check address
    if (!address) {
        showError('profileAddress', 'آدرس الزامی است');
        hasError = true;
    } else if (address.length < 10) {
        showError('profileAddress', 'آدرس باید حداقل ۱۰ کاراکتر باشد');
        hasError = true;
    }
    
    // If there are validation errors, stop submission
    if (hasError) {
        return;
    }
    
    // Show loading
    showButtonLoading('profileSubmitBtn');
    
    // Send AJAX request
    jQuery.ajax({
        url: apl_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'apl_update_profile',
            first_name: firstName,
            last_name: lastName,
            email: email,
            mobile: mobile,
            national_id: nationalId,
            address: document.getElementById('profileAddress').value.trim(),
            nonce: apl_ajax.profile_nonce
        },
        success: function(response) {
            hideButtonLoading('profileSubmitBtn');
            console.log(response);
            if (response.success) {
                showProfileMessage(response.data.message, 'success');
            } else {
                if (response.data && response.data.field) {
                    showError(response.data.field, response.data.message);
                } else {
                    console.log(response);
                    const errorMessage = (response.data && response.data.message) ? response.data.message : 'خطا در به‌روزرسانی پروفایل';
                    showProfileMessage(errorMessage, 'error');
                }
            }
        },
        error: function() {
            hideButtonLoading('profileSubmitBtn');
            showProfileMessage('خطا در ارسال درخواست. لطفاً دوباره تلاش کنید.', 'error');
        }
    });
}

function showProfileMessage(message, type) {
    const messageDiv = document.getElementById('profileMessage');
    if (!messageDiv) return;
    
    messageDiv.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
    
    if (type === 'success') {
        messageDiv.classList.add('bg-green-100', 'text-green-800');
    } else {
        messageDiv.classList.add('bg-red-100', 'text-red-800');
    }
    
    messageDiv.textContent = message;
    messageDiv.classList.remove('hidden');
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            hideProfileMessage();
        }, 5000);
    }
}

function hideProfileMessage() {
    const messageDiv = document.getElementById('profileMessage');
    if (messageDiv) {
        messageDiv.classList.add('hidden');
    }
}

// Validation helper functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidMobile(mobile) {
    const mobileRegex = /^09\d{9}$/;
    return mobileRegex.test(mobile);
}

// Test function for debugging (remove in production)
function testProfileForm() {
    console.log('Testing profile form...');
    const form = document.getElementById('profileForm');
    if (form) {
        console.log('Profile form found');
        console.log('Form elements:', form.elements);
    } else {
        console.log('Profile form not found');
    }
}

// Invoices Management Functions
function loadUserInvoices() {
    const loadingEl = document.getElementById('invoicesLoading');
    const emptyEl = document.getElementById('invoicesEmpty');
    const containerEl = document.getElementById('invoicesContainer');
    
    // Show loading state
    loadingEl.classList.remove('hidden');
    emptyEl.classList.add('hidden');
    containerEl.classList.add('hidden');
    
    // Make AJAX request
    jQuery.ajax({
        url: apl_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'apl_get_user_orders',
            nonce: apl_ajax.dashboard_nonce
        },
        success: function(response) {
            loadingEl.classList.add('hidden');
            
            if (response.success && response.data.orders.length > 0) {
                renderInvoices(response.data.orders);
                containerEl.classList.remove('hidden');
            } else {
                emptyEl.classList.remove('hidden');
            }
        },
        error: function(xhr, status, error) {
            loadingEl.classList.add('hidden');
            emptyEl.classList.remove('hidden');
            console.error('Error loading invoices:', error);
        }
    });
}

function renderInvoices(orders) {
    const container = document.getElementById('invoicesContainer');
    container.innerHTML = '';
    
    orders.forEach(order => {
        const invoiceCard = renderInvoiceCard(order);
        container.appendChild(invoiceCard);
    });
}

function renderInvoiceCard(order) {
    const statusConfig = getStatusConfig(order.status);
    
    const card = document.createElement('div');
    card.className = 'bg-white rounded-xl shadow-sm border border-gray-200 p-6';
    
    card.innerHTML = `
        <div class="flex flex-col lg:flex-row lg:items-center justify-between">
            <div class="flex items-center">
                <div class="w-12 h-12 ${statusConfig.bgColor} rounded-lg flex items-center justify-center ml-4">
                    <i class="fas ${statusConfig.icon} ${statusConfig.textColor} text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">فاکتور #${order.number}</h3>
                    <p class="text-gray-600">تاریخ: ${order.date}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xl font-bold text-gray-900 ml-3">${formatPrice(order.total)} ${order.currency_symbol}</span>
                        <span class="px-3 py-1 ${statusConfig.badgeBg} ${statusConfig.badgeText} text-xs font-medium rounded-full">
                            ${statusConfig.label}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex space-x-2 space-x-reverse mt-4 lg:mt-0">
                ${getActionButtons(order)}
            </div>
        </div>
    `;
    
    return card;
}

function getStatusConfig(status) {
    const statusMap = {
        'pending': {
            label: 'در انتظار پرداخت',
            icon: 'fa-clock',
            bgColor: 'bg-orange-100',
            textColor: 'text-orange-600',
            badgeBg: 'bg-orange-100',
            badgeText: 'text-orange-800'
        },
        'processing': {
            label: 'در حال انجام',
            icon: 'fa-cog',
            bgColor: 'bg-blue-100',
            textColor: 'text-blue-600',
            badgeBg: 'bg-blue-100',
            badgeText: 'text-blue-800'
        },
        'completed': {
            label: 'تکمیل شده',
            icon: 'fa-check-circle',
            bgColor: 'bg-green-100',
            textColor: 'text-green-600',
            badgeBg: 'bg-green-100',
            badgeText: 'text-green-800'
        },
        'cancelled': {
            label: 'لغو شده',
            icon: 'fa-times-circle',
            bgColor: 'bg-red-100',
            textColor: 'text-red-600',
            badgeBg: 'bg-red-100',
            badgeText: 'text-red-800'
        },
        'failed': {
            label: 'ناموفق',
            icon: 'fa-exclamation-circle',
            bgColor: 'bg-red-100',
            textColor: 'text-red-600',
            badgeBg: 'bg-red-100',
            badgeText: 'text-red-800'
        },
        'refunded': {
            label: 'مسترد شده',
            icon: 'fa-undo',
            bgColor: 'bg-purple-100',
            textColor: 'text-purple-600',
            badgeBg: 'bg-purple-100',
            badgeText: 'text-purple-800'
        },
        'on-hold': {
            label: 'در انتظار بررسی',
            icon: 'fa-pause-circle',
            bgColor: 'bg-yellow-100',
            textColor: 'text-yellow-600',
            badgeBg: 'bg-yellow-100',
            badgeText: 'text-yellow-800'
        },
        'checkout-draft': {
            label: 'پیش‌نویس',
            icon: 'fa-edit',
            bgColor: 'bg-gray-100',
            textColor: 'text-gray-600',
            badgeBg: 'bg-gray-100',
            badgeText: 'text-gray-800'
        }
    };
    
    return statusMap[status] || statusMap['pending'];
}

function getActionButtons(order) {
    let buttons = '';
    
    // Payment button for pending orders
    if (order.status === 'pending') {
        buttons += `
            <button onclick="window.open('${order.payment_url}', '_blank')" 
                    class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-200 font-medium">
                <i class="fas fa-credit-card ml-2"></i>پرداخت
            </button>
        `;
    }
    
    // Download button for completed and processing orders
    if (order.status === 'completed' || order.status === 'processing') {
        buttons += `
            <button onclick="downloadInvoicePDF(${order.id})" 
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                <i class="fas fa-download ml-2"></i>دانلود فاکتور
            </button>
        `;
    }
    
    return buttons;
}

function formatPrice(price) {
    return new Intl.NumberFormat('fa-IR').format(price);
}

function downloadInvoicePDF(orderId) {
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i>در حال تولید...';
    button.disabled = true;
    
    // Make AJAX request
    jQuery.ajax({
        url: apl_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'apl_download_invoice_pdf',
            order_id: orderId,
            nonce: apl_ajax.dashboard_nonce
        },
        success: function(response) {
            if (response.success) {
                // Open PDF in new tab
                window.open(response.data.pdf_url, '_blank');
            } else {
                alert('خطا در تولید فاکتور: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            alert('خطا در دانلود فاکتور');
            console.error('Error downloading PDF:', error);
        },
        complete: function() {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

// Profile Picture Upload Functionality
document.addEventListener('DOMContentLoaded', function() {
    const profilePictureInput = document.getElementById('profilePictureInput');
    const selectProfilePictureBtn = document.getElementById('selectProfilePictureBtn');
    const profilePictureActions = document.getElementById('profilePictureActions');
    const confirmUploadBtn = document.getElementById('confirmUploadBtn');
    const cancelUploadBtn = document.getElementById('cancelUploadBtn');
    const uploadStatus = document.getElementById('uploadStatus');
    const profilePictureMessage = document.getElementById('profilePictureMessage');
    const profilePictureMessageContent = document.getElementById('profilePictureMessageContent');
    const currentProfilePicture = document.getElementById('currentProfilePicture');
    const removeProfilePictureBtn = document.getElementById('removeProfilePicture');
    
    let selectedFile = null;
    
    // Click file input when button is clicked
    if (selectProfilePictureBtn) {
        selectProfilePictureBtn.addEventListener('click', function() {
            profilePictureInput.click();
        });
    }
    
    // Handle file selection
    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showProfilePictureMessage('فرمت فایل مجاز نیست. فقط JPG، PNG و GIF مجاز است', 'error');
                return;
            }
            
            // Validate file size (5MB max)
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                showProfilePictureMessage('حجم فایل بیش از 5 مگابایت است', 'error');
                return;
            }
            
            selectedFile = file;
            
            // Show preview in currentProfilePicture
            const reader = new FileReader();
            reader.onload = function(e) {
                // Update currentProfilePicture to show preview
                currentProfilePicture.innerHTML = `
                    <div class="relative inline-block">
                        <img src="${e.target.result}" 
                             alt="پیش‌نمایش" 
                             class="w-24 h-24 rounded-full object-cover border-2 border-blue-300">
                        <div class="absolute inset-0 rounded-full bg-black bg-opacity-20 flex items-center justify-center">
                            <span class="text-white text-xs font-medium">پیش‌نمایش</span>
                        </div>
                    </div>
                `;
                
                // Show action buttons
                profilePictureActions.classList.remove('hidden');
                hideProfilePictureMessage();
            };
            reader.readAsDataURL(file);
        });
    }
    
    // Handle confirm upload
    if (confirmUploadBtn) {
        confirmUploadBtn.addEventListener('click', function() {
            if (!selectedFile) return;
            
            // Show upload status
            uploadStatus.classList.remove('hidden');
            profilePictureActions.classList.add('hidden');
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'apl_upload_profile_picture');
            formData.append('profile_picture', selectedFile);
            formData.append('nonce', document.querySelector('input[name="profile_picture_nonce"]').value);
            
            // Upload file
            fetch(apl_ajax.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                uploadStatus.classList.add('hidden');
                
                if (data.success) {
                    // Update current profile picture
                    updateCurrentProfilePicture(data.data.image_url);
                    showProfilePictureMessage(data.data.message, 'success');
                    
                    // Reset file input
                    profilePictureInput.value = '';
                    selectedFile = null;
                } else {
                    showProfilePictureMessage(data.data.message, 'error');
                    profilePictureActions.classList.remove('hidden');
                }
            })
            .catch(error => {
                uploadStatus.classList.add('hidden');
                showProfilePictureMessage('خطا در آپلود فایل', 'error');
                profilePictureActions.classList.remove('hidden');
            });
        });
    }
    
    // Handle cancel upload
    if (cancelUploadBtn) {
        cancelUploadBtn.addEventListener('click', function() {
            // Reset to original state
            resetProfilePictureDisplay();
            profilePictureInput.value = '';
            selectedFile = null;
            profilePictureActions.classList.add('hidden');
            hideProfilePictureMessage();
        });
    }
    
    // Handle remove profile picture
    if (removeProfilePictureBtn) {
        removeProfilePictureBtn.addEventListener('click', function() {
            if (confirm('آیا مطمئن هستید که می‌خواهید عکس پروفایل را حذف کنید؟')) {
                const formData = new FormData();
                formData.append('action', 'apl_remove_profile_picture');
                formData.append('nonce', document.querySelector('input[name="profile_picture_nonce"]').value);
                
                fetch(apl_ajax.ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show default avatar
                        currentProfilePicture.innerHTML = `
                            <div class="w-24 h-24 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400 text-2xl"></i>
                            </div>
                        `;
                        showProfilePictureMessage(data.data.message, 'success');
                    } else {
                        showProfilePictureMessage(data.data.message, 'error');
                    }
                })
                .catch(error => {
                    showProfilePictureMessage('خطا در حذف عکس', 'error');
                });
            }
        });
    }
    
    // Helper functions
    function showProfilePictureMessage(message, type) {
        profilePictureMessageContent.textContent = message;
        profilePictureMessageContent.className = `text-sm p-3 rounded-lg ${
            type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`;
        profilePictureMessage.classList.remove('hidden');
    }
    
    function hideProfilePictureMessage() {
        profilePictureMessage.classList.add('hidden');
    }
    
    function resetProfilePictureDisplay() {
        // Reset to default avatar
        currentProfilePicture.innerHTML = `
            <div class="w-24 h-24 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center">
                <i class="fas fa-user text-gray-400 text-2xl"></i>
            </div>
        `;
    }
    
    function updateCurrentProfilePicture(imageUrl) {
        currentProfilePicture.innerHTML = `
            <div class="relative inline-block">
                <img src="${imageUrl}" 
                     alt="عکس پروفایل" 
                     class="w-24 h-24 rounded-full object-cover border-2 border-gray-300">
                <button type="button" 
                        id="removeProfilePicture" 
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition duration-200"
                        title="حذف عکس">
                    ×
                </button>
            </div>
        `;
        
        // Re-attach event listener to new remove button
        const newRemoveBtn = document.getElementById('removeProfilePicture');
        if (newRemoveBtn) {
            newRemoveBtn.addEventListener('click', function() {
                if (confirm('آیا مطمئن هستید که می‌خواهید عکس پروفایل را حذف کنید؟')) {
                    const formData = new FormData();
                    formData.append('action', 'apl_remove_profile_picture');
                    formData.append('nonce', document.querySelector('input[name="profile_picture_nonce"]').value);
                    
                    fetch(apl_ajax.ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show default avatar
                            currentProfilePicture.innerHTML = `
                                <div class="w-24 h-24 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                                </div>
                            `;
                            showProfilePictureMessage(data.data.message, 'success');
                        } else {
                            showProfilePictureMessage(data.data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showProfilePictureMessage('خطا در حذف عکس', 'error');
                    });
                }
            });
        }
    }
});