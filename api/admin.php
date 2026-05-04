<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
session_start();

// Auth: accept EITHER PHP session role OR uid param (DB lookup)
$isAdmin = false;

// Method 1: PHP session (works when login flow sets it)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
}

// Method 2: uid param — DB lookup (works when sessionStorage is used)
if (!$isAdmin) {
    $requestedBy = intval($_GET['uid'] ?? $_POST['uid'] ?? 0);
    if ($requestedBy > 0) {
        $chk = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $chk->execute([$requestedBy]);
        $row = $chk->fetch();
        $isAdmin = ($row && $row['role'] === 'admin');
    }
}

if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in via admin_login.php']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'fetchStats') {
    try {
        $totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")->fetchColumn();
        $totalEngineers= $pdo->query("SELECT COUNT(*) FROM engineers")->fetchColumn();
        $totalIssues   = $pdo->query("SELECT COUNT(*) FROM issues")->fetchColumn();
        $pendingIssues = $pdo->query("SELECT COUNT(*) FROM issues WHERE status = 'Open'")->fetchColumn();
        $resolvedIssues= $pdo->query("SELECT COUNT(*) FROM issues WHERE status = 'Resolved'")->fetchColumn();
        echo json_encode(['success' => true, 'stats' => [
            'total_users'     => $totalUsers,
            'total_engineers' => $totalEngineers,
            'total_issues'    => $totalIssues,
            'pending'         => $pendingIssues,
            'resolved'        => $resolvedIssues
        ]]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

elseif ($action === 'fetchUsers') {
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.full_name, u.phone, u.ward_locality, u.role, u.created_at,
                   COUNT(i.id) as report_count
            FROM users u
            LEFT JOIN issues i ON u.id = i.user_id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

elseif ($action === 'fetchEngineers') {
    try {
        $stmt = $pdo->query("
            SELECT e.*, u.full_name, u.phone,
                (SELECT COUNT(*) FROM issues WHERE assigned_engineer_id = e.id AND status = 'In Progress') as active_issues,
                (SELECT COUNT(*) FROM issues WHERE assigned_engineer_id = e.id AND status = 'Resolved')    as resolved_issues
            FROM engineers e
            JOIN users u ON e.user_id = u.id
        ");
        echo json_encode(['success' => true, 'engineers' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

elseif ($action === 'addEngineer') {
    $fullName   = $_POST['full_name']   ?? '';
    $phone      = $_POST['phone']       ?? '';
    $employeeId = $_POST['employee_id'] ?? '';
    $specialty  = $_POST['specialty']   ?? 'General';
    $ward       = $_POST['ward']        ?? '';
    try {
        $pdo->beginTransaction();
        $s1 = $pdo->prepare("INSERT INTO users (full_name, phone, role, ward_locality) VALUES (?, ?, 'engineer', ?)");
        $s1->execute([$fullName, $phone, $ward]);
        $userId = $pdo->lastInsertId();
        $s2 = $pdo->prepare("INSERT INTO engineers (user_id, employee_id, specialty, assigned_ward) VALUES (?, ?, ?, ?)");
        $s2->execute([$userId, $employeeId, $specialty, $ward]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Engineer added successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
    }
}

elseif ($action === 'updateIssue') {
    $issueId    = intval($_POST['issue_id']    ?? 0);
    $status     = $_POST['status']             ?? '';
    $engineerId = !empty($_POST['engineer_id']) ? intval($_POST['engineer_id']) : null;
    $note       = trim($_POST['note']          ?? '');

    $statusMap = [
        'pending'    => 'Open',
        'open'       => 'Open',
        'progress'   => 'In Progress',
        'in progress'=> 'In Progress',
        'resolved'   => 'Resolved',
        'rejected'   => 'Open',
    ];
    $dbStatus   = $statusMap[strtolower($status)] ?? 'Open';
    $resolvedAt = ($dbStatus === 'Resolved') ? date('Y-m-d H:i:s') : null;

    try {
        if ($engineerId) {
            $pdo->prepare("UPDATE issues SET status=?, assigned_engineer_id=?, resolved_at=? WHERE id=?")
                ->execute([$dbStatus, $engineerId, $resolvedAt, $issueId]);
        } else {
            $pdo->prepare("UPDATE issues SET status=?, resolved_at=? WHERE id=?")
                ->execute([$dbStatus, $resolvedAt, $issueId]);
        }

        $desc = "Status updated to {$dbStatus}" . ($note ? ". Note: {$note}" : "");
        $pdo->prepare("INSERT INTO activity_logs (issue_id, action_description) VALUES (?, ?)")
            ->execute([$issueId, $desc]);

        $pdo->prepare("
            INSERT INTO notifications (user_id, issue_id, title, message)
            SELECT user_id, id, 'Issue Update', ? FROM issues WHERE id = ?
        ")->execute(["Your issue status has been updated to: {$dbStatus}", $issueId]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

elseif ($action === 'fetchActivityLog') {
    try {
        $stmt = $pdo->query("
            SELECT al.id, al.action_description, al.created_at,
                   i.issue_type, i.id as issue_id
            FROM activity_logs al
            JOIN issues i ON al.issue_id = i.id
            ORDER BY al.created_at DESC
            LIMIT 20
        ");
        echo json_encode(['success' => true, 'logs' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
