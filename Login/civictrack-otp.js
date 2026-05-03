let currentScreen = 1;
let isNewUser     = true;
let currentLang   = 'en';
let simulatedOtp  = '';
let resendInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('phoneInput').addEventListener('keypress', e => {
        if (e.key === 'Enter') sendOtp();
    });
    initOtpBoxes();
});

function initOtpBoxes() {
    const boxes = document.querySelectorAll('.otp-box');
    boxes.forEach((box, idx) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            if (box.value && idx < boxes.length - 1) boxes[idx + 1].focus();
        });
        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && idx > 0) boxes[idx - 1].focus();
            if (e.key === 'Enter') verifyOtp();
        });
        box.addEventListener('paste', e => {
            e.preventDefault();
            const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            digits.split('').forEach((d, i) => { if (boxes[i]) boxes[i].value = d; });
            const next = Math.min(digits.length, boxes.length - 1);
            boxes[next].focus();
        });
    });
}

function sendOtp() {
    const phone = document.getElementById('phoneInput').value.trim();
    const error = document.getElementById('phoneError');

    if (!/^\d{10}$/.test(phone)) {
        document.getElementById('phoneInput').classList.add('invalid');
        error.style.display = 'block';
        return;
    }

    document.getElementById('phoneInput').classList.remove('invalid');
    error.style.display = 'none';

    const btn = document.getElementById('sendOtpBtn');
    const label = currentLang === 'en' ? 'Sending…' : 'भेज रहे हैं…';
    btn.innerHTML = `<span>${label}</span>`;
    btn.disabled = true;

    // Check if user exists in the database
    const formData = new FormData();
    formData.append('action', 'checkUser');
    formData.append('phone', phone);

    fetch('../api/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            isNewUser = data.isNewUser;
            sessionStorage.setItem('ct_phone', '+91' + phone);
            if (!isNewUser && data.user) {
                sessionStorage.setItem('ct_name', data.user.full_name);
                sessionStorage.setItem('ct_role', data.user.role);
            }

            simulatedOtp = String(Math.floor(100000 + Math.random() * 900000));
            
            const subtitle = document.getElementById('otpSubtitle');
            const maskedPhone = phone.slice(0, 2) + 'XXXXXX' + phone.slice(-2);
            const enText = `Enter the 6-digit code sent to +91 ${maskedPhone}`;
            const hiText = `+91 ${maskedPhone} पर भेजा गया 6-अंकीय कोड दर्ज करें`;
            subtitle.setAttribute('data-en', enText);
            subtitle.setAttribute('data-hi', hiText);
            subtitle.innerText = currentLang === 'en' ? enText : hiText;

            btn.innerHTML = `<span data-en="Send OTP" data-hi="OTP भेजें">${currentLang === 'en' ? 'Send OTP' : 'OTP भेजें'}</span>`;
            btn.disabled = false;
            
            alert(`[Demo] Your OTP is: ${simulatedOtp}`);
            goToScreen(2);
            startResendTimer();
            setTimeout(() => {
                const firstOtpBox = document.getElementById('o1');
                if(firstOtpBox) firstOtpBox.focus();
            }, 100);
        } else {
            alert(data.message || "An error occurred");
            btn.innerHTML = `<span data-en="Send OTP" data-hi="OTP भेजें">${currentLang === 'en' ? 'Send OTP' : 'OTP भेजें'}</span>`;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = `<span data-en="Send OTP" data-hi="OTP भेजें">${currentLang === 'en' ? 'Send OTP' : 'OTP भेजें'}</span>`;
        btn.disabled = false;
    });
}

function verifyOtp() {
    const boxes = document.querySelectorAll('.otp-box');
    const entered = Array.from(boxes).map(b => b.value).join('');
    const error = document.getElementById('otpError');

    if (entered.length < 6) {
        boxes.forEach(b => b.classList.add('invalid'));
        error.style.display = 'block';
        return;
    }

    if (entered !== simulatedOtp) {
        boxes.forEach(b => { b.classList.add('invalid'); b.value = ''; });
        error.style.display = 'block';
        boxes[0].focus();
        return;
    }

    boxes.forEach(b => b.classList.remove('invalid'));
    error.style.display = 'none';
    clearInterval(resendInterval);

    const btn = document.getElementById('verifyOtpBtn');
    btn.innerHTML = `<span>${currentLang === 'en' ? 'Verified ✓' : 'सत्यापित ✓'}</span>`;
    btn.disabled = true;

    setTimeout(() => {
        if (isNewUser) {
            goToScreen(3);
        } else {
            finish('User');
        }
    }, 600);
}

function resendOtp() {
    simulatedOtp = String(Math.floor(100000 + Math.random() * 900000));
    alert(`[Demo] Your new OTP is: ${simulatedOtp}`);
    document.querySelectorAll('.otp-box').forEach(b => { b.value = ''; b.classList.remove('invalid'); });
    document.getElementById('otpError').style.display = 'none';
    document.getElementById('o1').focus();
    startResendTimer();
}

function startResendTimer() {
    clearInterval(resendInterval);
    let seconds = 30;
    const resendBtn = document.getElementById('resendBtn');
    const timerEl = document.getElementById('resendTimer');
    resendBtn.disabled = true;
    timerEl.textContent = seconds;

    resendInterval = setInterval(() => {
        seconds--;
        timerEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(resendInterval);
            resendBtn.disabled = false;
            timerEl.textContent = '';
        }
    }, 1000);
}

function validateName() {
    const n = document.getElementById('fullName');
    const e = document.getElementById('nameError');
    if (n.value.trim().length >= 2) {
        n.classList.remove('invalid');
        e.style.display = 'none';
        return true;
    }
    return false;
}

function goToScreen(num) {
    const cur = document.getElementById(`screen-${currentScreen}`);
    if (cur) {
        cur.classList.remove('active');
        cur.classList.toggle('slide-out-left', num > currentScreen);
    }

    currentScreen = num;

    const next = document.getElementById(`screen-${num}`);
    if (next) {
        next.classList.remove('slide-out-left');
        next.classList.add('active');
    }

    for (let i = 1; i <= 4; i++) {
        const d = document.getElementById(`dot-${i}`);
        if (d) {
            if (i < num)        d.className = 'dot completed';
            else if (i === num) d.className = 'dot active';
            else                d.className = 'dot';
        }
    }

    if (num === 3) setTimeout(() => document.getElementById('fullName').focus(), 420);
}

function submitRegistration() {
    const nameEl = document.getElementById('fullName');
    if (!validateName()) {
        nameEl.classList.add('invalid');
        document.getElementById('nameError').style.display = 'block';
        return;
    }
    const name = nameEl.value.trim();
    const ward = document.getElementById('locality').value.trim() || 'Ward 42';
    const city = document.getElementById('city').value.trim()     || 'Mumbai';
    const optIn = document.getElementById('whatsappOptIn') ? document.getElementById('whatsappOptIn').checked : false;
    
    // Get phone without +91 for DB
    let phone = sessionStorage.getItem('ct_phone') || '';
    if(phone.startsWith('+91')) phone = phone.substring(3);

    const formData = new FormData();
    formData.append('action', 'registerUser');
    formData.append('phone', phone);
    formData.append('full_name', name);
    formData.append('ward', ward);
    formData.append('city', city);
    formData.append('whatsapp_opt_in', optIn);

    fetch('../api/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            sessionStorage.setItem('ct_name', name);
            sessionStorage.setItem('ct_ward', ward);
            sessionStorage.setItem('ct_city', city);
            sessionStorage.setItem('ct_role', 'resident');

            const title = document.getElementById('successTitle');
            if (title) {
                title.innerText = currentLang === 'en' ? `Welcome, ${name}!` : `स्वागत है, ${name}!`;
                title.setAttribute('data-en', `Welcome, ${name}!`);
                title.setAttribute('data-hi', `स्वागत है, ${name}!`);
            }

            goToScreen(4);
            finish(name);
        } else {
            alert(data.message || "Registration failed");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("An error occurred during registration");
    });
}

function finish(name) {
    const progress = document.querySelector('.progress');
    const controls = document.querySelector('.controls');
    if (progress) progress.style.display = 'none';
    if (controls) controls.style.display = 'none';

    setTimeout(() => {
        window.location.href = 'civictrack-dashboard.php';
    }, 2000);
}

function toggleLanguage() {
    currentLang = currentLang === 'en' ? 'hi' : 'en';
    const btn = document.getElementById('langBtn');
    if (btn) btn.innerText = currentLang === 'en' ? 'हिन्दी' : 'English';

    document.querySelectorAll('[data-en]').forEach(el => {
        const v = el.getAttribute(`data-${currentLang}`);
        if (v) el.innerText = v;
    });
}
