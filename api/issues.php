<?php
// api/issues.php
header('Content-Type: application/json');
require_once 'db_connect.php';

session_start();

// Enable basic error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$action = $_REQUEST['action'] ?? '';

if ($action === 'fetchIssues') {
    try {
        // Auto-approve issues where 24 hours have passed since resolution
        $pdo->query("UPDATE issues SET is_citizen_approved = 1 WHERE status = 'Resolved' AND is_citizen_approved = 0 AND TIMESTAMPDIFF(HOUR, resolved_at, NOW()) > 24");

        $filter = $_GET['filter'] ?? '';
        
        $sql = "SELECT i.*, u.full_name as reported_by, 
                (TIMESTAMPDIFF(HOUR, i.resolved_at, NOW()) <= 24) as can_reopen
                FROM issues i 
                JOIN users u ON i.user_id = u.id ";
                
        if ($filter === 'mine' && isset($_SESSION['user_id'])) {
            $sql .= " WHERE i.user_id = :user_id ";
        }
        
        $sql .= " ORDER BY i.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        if ($filter === 'mine' && isset($_SESSION['user_id'])) {
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
        } else {
            $stmt->execute();
        }
        $issues = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'issues' => $issues]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} 
elseif ($action === 'submitIssue') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $type = $_POST['type'] ?? '';
    $location = $_POST['location'] ?? '';
    $ward = $_POST['ward'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'Normal';
    
    // For now, no file upload handling, just the core data
    
    if (empty($type) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Type and location are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO issues (user_id, issue_type, location_text, ward, description, priority) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $location, $ward, $description, $priority]);
        
        // Add activity log
        $issueId = $pdo->lastInsertId();
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (issue_id, action_description) VALUES (?, ?)");
        $logStmt->execute([$issueId, "New report submitted – $type"]);
        
        echo json_encode(['success' => true, 'message' => 'Issue submitted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
elseif ($action === 'approveIssue') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $issueId = $_POST['issue_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE issues SET is_citizen_approved = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$issueId, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (issue_id, action_description) VALUES (?, ?)");
            $logStmt->execute([$issueId, "Resolution approved by citizen"]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Issue not found or unauthorized']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
elseif ($action === 'reopenIssue') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $issueId = $_POST['issue_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE issues SET status = 'In Progress', resolved_at = NULL, priority = 'Urgent' WHERE id = ? AND user_id = ? AND TIMESTAMPDIFF(HOUR, resolved_at, NOW()) <= 24");
        $stmt->execute([$issueId, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (issue_id, action_description) VALUES (?, ?)");
            $logStmt->execute([$issueId, "Issue reopened by citizen (work not complete)"]);
            
            // Generate a notification for admin
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, issue_id, title, message) SELECT id, ?, 'Issue Reopened', 'Citizen reopened ticket as work was not complete' FROM users WHERE role = 'admin'");
            $notifStmt->execute([$issueId]);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cannot reopen: Time window expired or issue not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
elseif ($action === 'fetchNotifications') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$_SESSION['user_id']]);
        $notifs = $stmt->fetchAll();
        $unread = array_filter($notifs, fn($n) => !$n['is_read']);
        echo json_encode(['success' => true, 'notifications' => $notifs, 'unread_count' => count($unread)]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
elseif ($action === 'markNotificationRead') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    $notifId = intval($_POST['notif_id'] ?? 0);
    try {
        if ($notifId === 0) {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
                ->execute([$_SESSION['user_id']]);
        } else {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")
                ->execute([$notifId, $_SESSION['user_id']]);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
elseif ($action === 'fetchLeaderboard') {
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.full_name, u.ward_locality,
                   COUNT(i.id) as report_count,
                   SUM(CASE WHEN i.status='Resolved' THEN 50 ELSE 10 END) as points
            FROM users u
            LEFT JOIN issues i ON u.id = i.user_id
            WHERE u.role = 'resident'
            GROUP BY u.id
            HAVING report_count > 0
            ORDER BY points DESC
            LIMIT 20
        ");
        echo json_encode(['success' => true, 'leaders' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
elseif ($action === 'fetchWardStats') {
    try {
        $stmt = $pdo->query("
            SELECT ward,
                   COUNT(*) as total,
                   SUM(status='Open') as open_count,
                   SUM(status='In Progress') as progress_count,
                   SUM(status='Resolved') as resolved_count,
                   ROUND(SUM(status='Resolved') / COUNT(*) * 100) as resolution_rate
            FROM issues
            WHERE ward IS NOT NULL AND ward != ''
            GROUP BY ward
            ORDER BY total DESC
        ");
        echo json_encode(['success' => true, 'wards' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
