let currentScreen = 1;
let expectedOTP   = '';
let timerInterval;
let isNewUser     = true;
let currentLang   = 'en';
let activePhone   = '';

let phoneInput, phoneError, otpBoxes, otpError,
    otpGroup, timerEl, timerText, resendLink,
    devPanel, devOtpVal, displayNumber;

document.addEventListener('DOMContentLoaded', () => {
    phoneInput    = document.getElementById('phoneNumber');
    phoneError    = document.getElementById('phoneError');
    otpBoxes      = document.querySelectorAll('.otp-box');
    otpError      = document.getElementById('otpError');
    otpGroup      = document.getElementById('otpGroup');
    timerEl       = document.getElementById('timer');
    timerText     = document.getElementById('timerText');
    resendLink    = document.getElementById('resendLink');
    devPanel      = document.getElementById('devPanel');
    devOtpVal     = document.getElementById('devOtpVal');
    displayNumber = document.getElementById('displayNumber');
});

function generateOTP(length = 6) {
    if (window.crypto && window.crypto.getRandomValues) {
        const arr = new Uint32Array(length);
        window.crypto.getRandomValues(arr);
        return Array.from(arr, n => n % 10).join('');
    }
    return Array.from({ length }, () => Math.floor(Math.random() * 10)).join('');
}

function copyOTP() {
    if (!expectedOTP) return;
    const btn = document.querySelector('.dev-copy');
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(expectedOTP).then(() => {
            btn.textContent = '✓ Copied';
            setTimeout(() => (btn.textContent = 'Copy'), 1500);
        });
    } else {
        const tmp = document.createElement('input');
        tmp.value = expectedOTP;
        document.body.appendChild(tmp);
        tmp.select();
        document.execCommand('copy');
        document.body.removeChild(tmp);
        btn.textContent = '✓ Copied';
        setTimeout(() => (btn.textContent = 'Copy'), 1500);
    }
}

function validatePhone() {
    const v = phoneInput.value.trim();
    if (v.length === 10) {
        phoneInput.classList.remove('invalid');
        phoneError.style.display = 'none';
        return true;
    }
    return false;
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
    cur.classList.remove('active');
    cur.classList.toggle('slide-out-left', num > currentScreen);

    currentScreen = num;

    const next = document.getElementById(`screen-${num}`);
    next.classList.remove('slide-out-left');
    next.classList.add('active');

    if (num <= 3) {
        for (let i = 1; i <= 3; i++) {
            const d = document.getElementById(`dot-${i}`);
            if (i < num)        d.className = 'dot completed';
            else if (i === num) d.className = 'dot active';
            else                d.className = 'dot';
        }
    }

    if (num === 2) setTimeout(() => otpBoxes[0].focus(), 420);
    if (num === 3) setTimeout(() => document.getElementById('fullName').focus(), 420);
}

function sendOTP() {
    const phone = phoneInput.value.trim();
    if (phone.length !== 10) {
        phoneInput.classList.add('invalid');
        phoneError.style.display = 'block';
        return;
    }

    const cc = document.getElementById('countryCode').value;
    activePhone = cc + phone;
    displayNumber.innerText = `${cc} ${phone.slice(0, 2)}****${phone.slice(6)}`;

    expectedOTP = generateOTP(6);
    console.info('[CivicTrack DEV] Generated OTP:', expectedOTP);

    devOtpVal.textContent = expectedOTP;
    devPanel.classList.add('visible');

    otpBoxes.forEach(b => { b.value = ''; b.classList.remove('filled'); });
    otpError.style.display = 'none';

    startTimer(45);
    goToScreen(2);
}

function handleOtpInput(el, idx) {
    el.value = el.value.replace(/[^0-9]/g, '');
    if (el.value) {
        el.classList.add('filled');
        if (idx < 5) otpBoxes[idx + 1].focus();
        else         { el.blur(); verifyOTP(); }
    } else {
        el.classList.remove('filled');
    }
}

function handleOtpKeydown(e, idx) {
    if (e.key === 'Backspace' && !e.target.value && idx > 0) {
        otpBoxes[idx - 1].value = '';
        otpBoxes[idx - 1].classList.remove('filled');
        otpBoxes[idx - 1].focus();
    }
}

function handleOtpPaste(e) {
    e.preventDefault();
    const digits = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    digits.split('').forEach((ch, i) => {
        otpBoxes[i].value = ch;
        otpBoxes[i].classList.add('filled');
    });
    if (digits.length === 6)    { otpBoxes[5].focus(); verifyOTP(); }
    else if (digits.length < 6)   otpBoxes[digits.length].focus();
}

function verifyOTP() {
    const entered = [...otpBoxes].map(b => b.value).join('');
    if (entered.length !== 6) { showOtpError(); return; }

    if (entered === expectedOTP) {
        otpError.style.display = 'none';
        clearInterval(timerInterval);
        devPanel.classList.remove('visible');
        isNewUser ? goToScreen(3) : finish();
    } else {
        showOtpError();
    }
}

function showOtpError() {
    otpGroup.classList.remove('shake');
    void otpGroup.offsetWidth;
    otpGroup.classList.add('shake');
    otpError.style.display = 'block';
    otpBoxes.forEach(b => { b.value = ''; b.classList.remove('filled'); });
    otpBoxes[0].focus();
}

function submitRegistration() {
    const nameEl = document.getElementById('fullName');
    if (!validateName()) {
        nameEl.classList.add('invalid');
        document.getElementById('nameError').style.display = 'block';
        return;
    }
    const name     = nameEl.value.trim();
    const ward     = document.getElementById('locality').value.trim() || 'Ward 42';
    const city     = document.getElementById('city').value.trim()     || 'Mumbai';

    sessionStorage.setItem('ct_name',  name);
    sessionStorage.setItem('ct_ward',  ward);
    sessionStorage.setItem('ct_city',  city);
    sessionStorage.setItem('ct_phone', activePhone);

    document.getElementById('successTitle').innerText =
        currentLang === 'en' ? `Welcome, ${name}!` : `स्वागत है, ${name}!`;
    goToScreen(4);
    finish(name);
}

function finish(name) {
    document.querySelector('.progress').style.display = 'none';
    document.querySelector('.controls').style.display  = 'none';
    console.log('[CivicTrack] Flow complete – user authenticated:', name || 'returning user');
    setTimeout(() => {
        window.location.href = 'civictrack-dashboard.html';
    }, 2000);
}

function startTimer(sec) {
    timerText.style.display = 'inline';
    resendLink.style.display = 'none';
    let t = sec;
    tick(t);
    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        t--;
        tick(t);
        if (t <= 0) {
            clearInterval(timerInterval);
            timerText.style.display  = 'none';
            resendLink.style.display = 'inline';
        }
    }, 1000);
}

function tick(s) { timerEl.innerText = `0:${s < 10 ? '0' + s : s}`; }

function resendOTP() { sendOTP(); }

function toggleLanguage() {
    currentLang = currentLang === 'en' ? 'hi' : 'en';
    document.getElementById('langBtn').innerText =
        currentLang === 'en' ? 'हिन्दी' : 'English';
    document.querySelectorAll('[data-en]').forEach(el => {
        const v = el.getAttribute(`data-${currentLang}`);
        if (v) el.innerText = v;
    });
}
