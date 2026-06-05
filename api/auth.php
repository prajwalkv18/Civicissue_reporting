<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
$config = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

$action = $_POST['action'] ?? '';

if ($action === 'checkUser') {
    $phone = $_POST['phone'] ?? '';
    
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, phone, full_name, role FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists, save to session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        echo json_encode(['success' => true, 'isNewUser' => false, 'user' => $user, 'user_id' => $user['id']]);
    } else {
        // User does not exist
        echo json_encode(['success' => true, 'isNewUser' => true]);
    }
} 
elseif ($action === 'registerUser') {
    $phone = $_POST['phone'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    $ward = $_POST['ward'] ?? '';
    $city = $_POST['city'] ?? '';
    $optIn = isset($_POST['whatsapp_opt_in']) && $_POST['whatsapp_opt_in'] === 'true' ? 1 : 0;

    if (empty($phone) || empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'Phone and Name are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (phone, full_name, ward_locality, city, whatsapp_opt_in) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$phone, $fullName, $ward, $city, $optIn]);
        $userId = $pdo->lastInsertId();

        // Save to session
        $_SESSION['user_id'] = $userId;
        $_SESSION['phone'] = $phone;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['role'] = 'resident';

        echo json_encode(['success' => true, 'message' => 'User registered successfully', 'user_id' => $userId]);
    } catch (PDOException $e) {
        // Check for duplicate entry (phone)
        if ($e->errorInfo[1] == 1062) {
            echo json_encode(['success' => false, 'message' => 'Phone number already registered.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
} 
elseif ($action === 'setSession') {
    // Called after OTP verification to sync JS sessionStorage → PHP $_SESSION
    $userId = intval($_POST['user_id'] ?? 0);
    $role   = $_POST['role'] ?? 'resident';

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user_id']);
        exit;
    }

    // Verify the user actually exists and the role matches (security check)
    $stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];

    echo json_encode(['success' => true, 'role' => $user['role']]);
}
elseif ($action === 'sendOtp') {
    $phone = trim($_POST['phone'] ?? '');
    
    if ($phone === '') {
        echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
        exit;
    }
    
    // Normalize phone into E.164 format. If user enters 10 digits, assume India (+91).
    if (preg_match('/^\d{10}$/', $phone)) {
        $phone = '+91' . $phone;
    } elseif (preg_match('/^91\d{10}$/', $phone)) {
        $phone = '+' . $phone;
    }
    
    if (!preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use E.164 (e.g. +919876543210).']);
        exit;
    }
    
    // Generate 6-digit OTP
    $otp = rand(100000, 999999);
    $_SESSION['pending_otp'] = $otp;
    $_SESSION['pending_phone'] = $phone;
    
    $otpMode = strtolower(trim($config['otp_mode'] ?? 'local'));
    
    // Localhost/dev mode: skip SMS provider and return OTP for testing.
    if ($otpMode === 'local') {
        echo json_encode([
            'success' => true,
            'message' => 'OTP generated in local mode',
            'otp' => (string)$otp
        ]);
        exit;
    }
    
    // Fast2SMS works with 10-digit Indian numbers.
    if (!preg_match('/^\+91\d{10}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Fast2SMS currently supports only Indian (+91) numbers in this setup.']);
        exit;
    }
    $phoneForFast2Sms = substr($phone, 3);
    
    $apiKey = trim(getenv('FAST2SMS_API_KEY') ?: ($config['fast2sms_api_key'] ?? ''));
    if ($apiKey === '') {
        echo json_encode(['success' => false, 'message' => 'Missing Fast2SMS API key. Set FAST2SMS_API_KEY or api/config.php']);
        exit;
    }
    if ($apiKey === 'PASTE_YOUR_FAST2SMS_API_KEY_HERE') {
        echo json_encode(['success' => false, 'message' => 'Please set your real Fast2SMS API key in api/config.php']);
        exit;
    }
    
    $url = "https://www.fast2sms.com/dev/bulkV2";
    $payload = json_encode([
        'route' => 'otp',
        'variables_values' => (string)$otp,
        'numbers' => $phoneForFast2Sms
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'authorization: ' . $apiKey,
        'accept: application/json',
        'content-type: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $json = json_decode($response, true);
    $apiSuccess = is_array($json) && (
        !empty($json['return']) ||
        (isset($json['message']) && stripos((string)$json['message'], 'sms sent') !== false)
    );
    
    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP via Fast2SMS: ' . $curlError]);
    } elseif ($httpCode >= 200 && $httpCode < 300 && $apiSuccess) {
        echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
    } else {
        $apiMessage = is_array($json) ? $json : ['raw' => $response];
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send OTP via Fast2SMS. Error code: ' . $httpCode,
            'provider_response' => $apiMessage
        ]);
    }
}
elseif ($action === 'verifyOtp') {
    $phone = trim($_POST['phone'] ?? '');
    $otp = $_POST['otp'] ?? '';
    
    if (preg_match('/^\d{10}$/', $phone)) {
        $phone = '+91' . $phone;
    } elseif (preg_match('/^91\d{10}$/', $phone)) {
        $phone = '+' . $phone;
    }
    
    if (isset($_SESSION['pending_otp']) && $_SESSION['pending_otp'] == $otp && $_SESSION['pending_phone'] == $phone) {
        unset($_SESSION['pending_otp']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>