<?php
require_once 'db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$result = '';

// Test: List all issues with current status
$issues = $pdo->query("SELECT id, issue_type, status, user_id FROM issues ORDER BY id DESC LIMIT 10")->fetchAll();

// Test admin user
$admin = $pdo->query("SELECT id, full_name, role FROM users WHERE role='admin' LIMIT 1")->fetch();

// Handle manual update test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_update'])) {
    $issueId = intval($_POST['issue_id']);
    $newStatus = $_POST['new_status'];
    $pdo->prepare("UPDATE issues SET status=? WHERE id=?")->execute([$newStatus, $issueId]);
    $result = "✅ Updated issue #{$issueId} to '{$newStatus}'";
    // Re-fetch
    $issues = $pdo->query("SELECT id, issue_type, status, user_id FROM issues ORDER BY id DESC LIMIT 10")->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head><title>DB Diagnostic</title>
<style>body{font-family:monospace;padding:20px;} table{border-collapse:collapse;width:100%;} td,th{border:1px solid #ccc;padding:8px;text-align:left;} .ok{color:green;} .err{color:red;}</style>
</head>
<body>
<h2>CivicTrack DB Diagnostic</h2>

<h3>Admin User</h3>
<?php if($admin): ?>
    <p class="ok">✅ Admin found: <b><?= htmlspecialchars($admin['full_name']) ?></b> (ID: <?= $admin['id'] ?>, Role: <?= $admin['role'] ?>)</p>
<?php else: ?>
    <p class="err">❌ NO ADMIN USER FOUND with role='admin'. Run the setup SQL.</p>
<?php endif; ?>

<h3>Last 10 Issues (current DB status)</h3>
<table>
<tr><th>ID</th><th>Type</th><th>Status</th><th>User ID</th></tr>
<?php foreach($issues as $i): ?>
<tr>
    <td>#<?= $i['id'] ?></td>
    <td><?= htmlspecialchars($i['issue_type']) ?></td>
    <td><b><?= $i['status'] ?></b></td>
    <td><?= $i['user_id'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<h3>Test Direct DB Update</h3>
<?php if($result): ?><p class="ok"><?= $result ?></p><?php endif; ?>
<form method="POST">
    <input type="hidden" name="test_update" value="1">
    Issue ID: <input name="issue_id" type="number" style="width:60px;">
    New Status: <select name="new_status">
        <option>Open</option>
        <option>In Progress</option>
        <option>Resolved</option>
    </select>
    <button type="submit">Update Directly in DB</button>
</form>

<h3>Test Admin API Call</h3>
<p>Admin ID: <b><?= $admin ? $admin['id'] : 'N/A' ?></b> — Use this in the URL below:</p>
<p>Open in browser: <a href="../api/admin.php?action=fetchStats&uid=<?= $admin ? $admin['id'] : '1' ?>" target="_blank">Test fetchStats API</a></p>
<p>If it returns JSON with success:true, the API auth works. If "Unauthorized", the admin ID is wrong.</p>

<h3>Check notifications table</h3>
<?php
try {
    $n = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    echo "<p class='ok'>✅ notifications table exists — $n rows</p>";
} catch(Exception $e) {
    echo "<p class='err'>❌ notifications table missing: " . $e->getMessage() . "</p>";
}
try {
    $a = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
    echo "<p class='ok'>✅ activity_logs table exists — $a rows</p>";
} catch(Exception $e) {
    echo "<p class='err'>❌ activity_logs table missing: " . $e->getMessage() . "</p>";
}
?>
</body>
</html>
