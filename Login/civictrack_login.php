<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – Login</title>
    <meta name="description" content="Sign in to CivicTrack using your mobile number to report and track civic issues.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="civictrack-login.css">

    <style>
        .otp-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 18px 0 6px;
        }
        .otp-group input {
            width: 46px;
            height: 54px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 600;
            border: 2px solid #ddd;
            border-radius: 12px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            font-family: 'Poppins', sans-serif;
            background: #f9f9fc;
        }
        .otp-group input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,.15);
            background: #fff;
        }
        .otp-group input.invalid {
            border-color: #e53e3e;
        }
        .resend-row {
            text-align: center;
            margin-top: 8px;
            font-size: .82rem;
            color: #888;
        }
        .resend-row button {
            background: none;
            border: none;
            color: #4f46e5;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            font-size: .82rem;
        }
        .resend-row button:disabled {
            color: #aaa;
            cursor: default;
        }
        .phone-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .country-code {
            padding: 14px 10px;
            border: 1.5px solid #ddd;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: .92rem;
            background: #f9f9fc;
            color: #333;
            min-width: 66px;
            text-align: center;
            flex-shrink: 0;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="controls">
        <button class="lang-toggle" onclick="toggleLanguage()" id="langBtn">हिन्दी</button>
    </div>

    <div class="card">

        <img src="logo.jpg" alt="CivicTrack Logo" class="logo">

        <div class="progress">
            <div class="dot active"    id="dot-1"></div>
            <div class="dot"           id="dot-2"></div>
            <div class="dot"           id="dot-3"></div>
            <div class="dot"           id="dot-4"></div>
        </div>

        <div class="screens-container" id="screens-container">

            <div class="screen active" id="screen-1">
                <h1 data-en="Report It. Track It. Fix It."
                    data-hi="रिपोर्ट करें। ट्रैक करें। समाधान करें।">
                    Report It. Track It. Fix It.
                </h1>
                <p data-en="Enter your mobile number to get a one-time password."
                   data-hi="वन-टाइम पासवर्ड पाने के लिए अपना मोबाइल नंबर दर्ज करें।">
                    Enter your mobile number to get a one-time password.
                </p>

                <div class="input-box" style="margin-top:18px;">
                    <div class="phone-row">
                        <span class="country-code">+91</span>
                        <input type="tel" id="phoneInput" placeholder=" " required
                               maxlength="10" autocomplete="tel-national"
                               oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)">
                    </div>
                    <label data-en="Mobile Number" data-hi="मोबाइल नंबर"
                           style="left:80px;">Mobile Number</label>
                </div>

                <div class="error-message" id="phoneError"
                     data-en="Please enter a valid 10-digit mobile number."
                     data-hi="कृपया 10 अंकों का वैध मोबाइल नंबर दर्ज करें।">
                    Please enter a valid 10-digit mobile number.
                </div>

                <button class="btn" id="sendOtpBtn" onclick="sendOtp()">
                    <span data-en="Send OTP" data-hi="OTP भेजें">Send OTP</span>
                </button>

                <div class="register-link mt-12">
                    <small style="color:#666;"
                           data-en="We'll send a 6-digit OTP to verify your number."
                           data-hi="आपके नंबर को सत्यापित करने के लिए हम 6 अंकों का OTP भेजेंगे।">
                        We'll send a 6-digit OTP to verify your number.
                    </small>
                </div>
            </div>

            <div class="screen" id="screen-2">
                <h2 data-en="Verify OTP" data-hi="OTP सत्यापित करें">Verify OTP</h2>
                <p id="otpSubtitle"
                   data-en="Enter the 6-digit code sent to +91 XXXXXXXXXX"
                   data-hi="+91 XXXXXXXXXX पर भेजा गया 6-अंकीय कोड दर्ज करें">
                    Enter the 6-digit code sent to +91 XXXXXXXXXX
                </p>

                <div class="otp-group" id="otpGroup">
                    <input type="tel" maxlength="1" id="o1" class="otp-box" inputmode="numeric">
                    <input type="tel" maxlength="1" id="o2" class="otp-box" inputmode="numeric">
                    <input type="tel" maxlength="1" id="o3" class="otp-box" inputmode="numeric">
                    <input type="tel" maxlength="1" id="o4" class="otp-box" inputmode="numeric">
                    <input type="tel" maxlength="1" id="o5" class="otp-box" inputmode="numeric">
                    <input type="tel" maxlength="1" id="o6" class="otp-box" inputmode="numeric">
                </div>

                <div class="error-message" id="otpError"
                     data-en="Incorrect OTP. Please try again."
                     data-hi="गलत OTP। कृपया पुनः प्रयास करें।">
                    Incorrect OTP. Please try again.
                </div>

                <button class="btn" id="verifyOtpBtn" onclick="verifyOtp()">
                    <span data-en="Verify & Continue" data-hi="सत्यापित करें और जारी रखें">Verify & Continue</span>
                </button>

                <div class="resend-row">
                    <span data-en="Didn't receive it?" data-hi="नहीं मिला?">Didn't receive it?</span>
                    <button id="resendBtn" onclick="resendOtp()" disabled>
                        <span data-en="Resend OTP" data-hi="OTP पुनः भेजें">Resend OTP</span>
                        (<span id="resendTimer">30</span>s)
                    </button>
                </div>
            </div>

            <div class="screen" id="screen-3">
                <h2 data-en="Complete Profile" data-hi="प्रोफ़ाइल पूरी करें">Complete Profile</h2>
                <p data-en="Looks like you're new here. Tell us a bit about yourself."
                   data-hi="लगता है आप नए हैं। अपने बारे में कुछ बताएं।">
                    Looks like you're new here. Tell us a bit about yourself.
                </p>

                <div class="input-box">
                    <input type="text" id="fullName" placeholder=" " required
                           autocomplete="off" oninput="validateName()">
                    <label data-en="Full Name" data-hi="पूरा नाम">Full Name</label>
                </div>
                <div class="error-message" id="nameError"
                     data-en="Please enter at least 2 characters."
                     data-hi="कृपया कम से कम 2 अक्षर दर्ज करें।">
                    Please enter at least 2 characters.
                </div>

                <div class="input-box">
                    <input type="text" id="locality" placeholder=" " required>
                    <label data-en="Ward / Locality" data-hi="वार्ड / मोहल्ला">Ward / Locality</label>
                </div>

                <div class="input-box">
                    <input type="text" id="city" placeholder=" " value="Mumbai" required>
                    <label data-en="City" data-hi="शहर">City</label>
                </div>

                <label class="checkbox-container">
                    <input type="checkbox" id="whatsappOptIn" checked>
                    <span class="checkmark"></span>
                    <span class="checkbox-text"
                          data-en="I want to receive civic updates on WhatsApp"
                          data-hi="मैं WhatsApp पर नागरिक अपडेट प्राप्त करना चाहता/चाहती हूँ">
                        I want to receive civic updates on WhatsApp
                    </span>
                </label>

                <button class="btn" onclick="submitRegistration()">
                    <span data-en="Create Account & Continue"
                          data-hi="खाता बनाएं और आगे बढ़ें">
                        Create Account &amp; Continue
                    </span>
                </button>
            </div>

            <div class="screen" id="screen-4">
                <div class="success-circle">
                    <svg class="success-icon" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="3">
                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="text-center" id="successTitle"
                    data-en="Welcome!" data-hi="स्वागत है!">
                    Welcome!
                </h2>
                <p class="text-center"
                   data-en="Redirecting to your dashboard…"
                   data-hi="आपके डैशबोर्ड पर पुनः निर्देशित हो रहे हैं…">
                    Redirecting to your dashboard…
                </p>
            </div>

        </div>
    </div>

    <div class="sms-banner">
        <span>🔔</span>
        <span data-en="System operates over secure HTTPS connections only."
              data-hi="सिस्टम केवल सुरक्षित HTTPS कनेक्शन पर काम करता है।">
            System operates over secure HTTPS connections only.
        </span>
    </div>

    <script src="civictrack-otp.js?v=<?php echo time(); ?>" defer></script>
</body>
</html>
