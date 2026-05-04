<?php
require_once '../api/db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$error = '';
$adminUser = null;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $pass  = trim($_POST['pass']  ?? '');

    if ($phone && $pass === 'admin@civictrack') {
        $stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE phone = ? AND role = 'admin'");
        $stmt->execute([$phone]);
        $adminUser = $stmt->fetch();
        if (!$adminUser) $error = 'No admin user found with that phone. Make sure it exists in the DB with role=admin.';
    } else {
        $error = 'Phone and admin password are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CivicTrack – Admin Direct Login</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
    .card { background: #fff; border-radius: 16px; padding: 40px; max-width: 400px; width: 90%; box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
    h2 { margin: 0 0 6px; font-size: 22px; color: #1a1a1a; }
    p  { color: #6b7280; font-size: 13px; margin: 0 0 24px; }
    label  { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #374151; }
    input  { width: 100%; padding: 11px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box; margin-bottom: 14px; }
    button { width: 100%; padding: 13px; background: #1a73e8; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
    .error { background: #fce8e6; color: #C0392B; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 14px; }
    .note  { background: #e8f0fe; color: #1a73e8; padding: 10px 14px; border-radius: 8px; font-size: 12px; margin-top: 14px; }
</style>
</head>
<body>
<div class="card">
    <h2>🔐 Admin Direct Login</h2>
    <p>Use this page to bypass the OTP flow and access the admin panel directly.</p>

    <?php if ($error): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($adminUser): ?>
        <script>
            sessionStorage.setItem('ct_role',    'admin');
            sessionStorage.setItem('ct_user_id', '<?= $adminUser['id'] ?>');
            sessionStorage.setItem('ct_name',    '<?= addslashes($adminUser['full_name']) ?>');
            sessionStorage.setItem('ct_ward',    'Central');
            sessionStorage.setItem('ct_city',    'Mumbai');
            window.location.href = 'civictrack-admin.php';
        </script>
        <p>✅ Logged in as <strong><?= htmlspecialchars($adminUser['full_name']) ?></strong>. Redirecting…</p>
    <?php else: ?>
        <form method="POST">
            <label>Admin Phone Number</label>
            <input type="tel" name="phone" placeholder="9999999999" value="9999999999">
            <label>Admin Password</label>
            <input type="password" name="pass" placeholder="admin@civictrack">
            <button type="submit">Login as Admin →</button>
        </form>
        <div class="note">
            Default admin phone: <strong>9999999999</strong><br>
            Default admin password: <strong>admin@civictrack</strong>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
