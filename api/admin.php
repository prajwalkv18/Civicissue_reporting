<?php
// api/admin.php
header('Content-Type: application/json');
require_once 'db_connect.php';

session_start();

$action = $_REQUEST['action'] ?? '';

// Normally, you would verify the admin session here.
// For the demo, we assume the user has access.
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

if ($action === 'fetchUsers') {
    try {
        // Fetch users and count their reports
        $stmt = $pdo->query("
            SELECT u.id, u.full_name, u.phone, u.ward_locality as ward, u.role, u.created_at,
                   COUNT(i.id) as reports_count
            FROM users u
            LEFT JOIN issues i ON u.id = i.user_id
            WHERE u.role IN ('resident', 'admin')
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'users' => $users]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
elseif ($action === 'fetchEngineers') {
    try {
        // Fetch engineers and count their assigned active/resolved jobs
        // Since we don't have a direct 'assigned_to' column in issues yet, 
        // we'll just show the total issues in their assigned ward for demonstration.
        $stmt = $pdo->query("
            SELECT u.id, u.full_name, u.phone, u.employee_id, u.ward_locality as assigned_ward,
                   (SELECT COUNT(*) FROM issues WHERE ward = u.ward_locality AND status IN ('Open', 'In Progress')) as active_jobs,
                   (SELECT COUNT(*) FROM issues WHERE ward = u.ward_locality AND status = 'Resolved') as resolved_jobs
            FROM users u
            WHERE u.role = 'engineer'
            ORDER BY u.full_name ASC
        ");
        $engineers = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'engineers' => $engineers]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
elseif ($action === 'addEngineer') {
    $name = $_POST['name'] ?? '';
    $empId = $_POST['emp_id'] ?? '';
    $ward = $_POST['ward'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if (empty($name) || empty($empId) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Name, Employee ID, and Phone are required.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, employee_id, ward_locality, phone, role) VALUES (?, ?, ?, ?, 'engineer')");
        $stmt->execute([$name, $empId, $ward, $phone]);
        
        echo json_encode(['success' => true, 'message' => 'Engineer added successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
elseif ($action === 'updateIssueStatus') {
    $issueId = $_POST['issue_id'] ?? 0;
    // Remove the '#' if present, e.g., '#CT001' or '1'
    $issueId = ltrim($issueId, '#CT');
    $issueId = (int)$issueId;
    
    $status = $_POST['status'] ?? ''; // pending, progress, resolved, rejected
    $engineer = $_POST['engineer'] ?? null;
    $note = $_POST['note'] ?? '';

    // Map UI statuses to DB ENUM ('Open', 'In Progress', 'Resolved')
    $dbStatus = 'Open';
    if ($status === 'progress') $dbStatus = 'In Progress';
    if ($status === 'resolved') $dbStatus = 'Resolved';
    if ($status === 'rejected') $dbStatus = 'Open'; // Or create a 'Rejected' status in DB

    try {
        if ($dbStatus === 'Resolved') {
            $stmt = $pdo->prepare("UPDATE issues SET status = ?, resolved_at = NOW() WHERE id = ?");
            $stmt->execute([$dbStatus, $issueId]);
        } else {
            $stmt = $pdo->prepare("UPDATE issues SET status = ?, resolved_at = NULL WHERE id = ?");
            $stmt->execute([$dbStatus, $issueId]);
        }
        
        // Log action
        $logDesc = "Issue marked as " . $dbStatus;
        if ($engineer) {
            $logDesc .= " and assigned to " . $engineer;
        }
        if ($note) {
            $logDesc .= " Note: " . $note;
        }
        
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (issue_id, action_description) VALUES (?, ?)");
        $logStmt->execute([$issueId, $logDesc]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
