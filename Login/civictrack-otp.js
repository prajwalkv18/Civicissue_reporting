let currentScreen = 1;
let isNewUser     = true;
let currentLang   = 'en';

let emailInput, passwordInput, loginError;

document.addEventListener('DOMContentLoaded', () => {
    emailInput    = document.getElementById('emailInput');
    passwordInput = document.getElementById('passwordInput');
    loginError    = document.getElementById('loginError');

    // Add enter key pressed triggers
    emailInput.addEventListener('keypress', function(event) {
        if (event.key === "Enter") passwordInput.focus();
    });
    passwordInput.addEventListener('keypress', function(event) {
        if (event.key === "Enter") attemptLogin();
    });
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function attemptLogin() {
    const email = emailInput.value.trim();
    const pass  = passwordInput.value.trim();
    
    // Simple simulated backend validation
    if (!validateEmail(email) || pass.length < 6) {
        emailInput.classList.add('invalid');
        passwordInput.classList.add('invalid');
        loginError.style.display = 'block';
        return;
    }
    
    // In a real app, you would verify email/pass with a backend here.
    // For now, any well-formatted email and 6+ char password passes.
    emailInput.classList.remove('invalid');
    passwordInput.classList.remove('invalid');
    loginError.style.display = 'none';
    
    // Save email in session
    sessionStorage.setItem('ct_email', email);

    // Disable button during "loading"
    const btn = document.getElementById('signInBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = currentLang === 'en' ? 'Authenticating...' : 'प्रमाणित किया जा रहा है...';
    btn.disabled = true;

    // Simulate network delay
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        // If the email simulates an existing user, we could skip screen 2 (registration)
        // Here we default to new user flows.
        if (isNewUser) {
            goToScreen(2);
        } else {
            // Suppose returning users skip to dashboard immediately
            finish('Returning User');
        }
    }, 800);
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

    // Update progress dots (3 dots for 3 screens)
    if (num <= 3) {
        for (let i = 1; i <= 3; i++) {
            const d = document.getElementById(`dot-${i}`);
            if (d) {
                if (i < num)        d.className = 'dot completed';
                else if (i === num) d.className = 'dot active';
                else                d.className = 'dot';
            }
        }
    }

    // Auto focus the first input on the new screen
    if (num === 2) setTimeout(() => document.getElementById('fullName').focus(), 420);
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

    const title = document.getElementById('successTitle');
    if (title) {
        title.innerText = currentLang === 'en' ? `Welcome, ${name}!` : `स्वागत है, ${name}!`;
    }
    
    goToScreen(3);
    finish(name);
}

function finish(name) {
    const progress = document.querySelector('.progress');
    const controls = document.querySelector('.controls');
    if(progress) progress.style.display = 'none';
    if(controls) controls.style.display = 'none';
    
    console.log('[CivicTrack] Flow complete – user authenticated via Email:', name || 'returning user');
    setTimeout(() => {
        window.location.href = '../Dashboard/civictrack-dashboard.html';
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
