<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

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
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>