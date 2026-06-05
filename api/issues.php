<?php
// api/issues.php
header('Content-Type: application/json');
require_once 'db_connect.php';

session_start();

// Enable basic error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session fallback: if $_SESSION['user_id'] is missing but the client sent a
// user_id (stored in sessionStorage → sent as POST field), verify it in the DB
// and restore the session. This handles cases where the PHP session cookie expired.
if (empty($_SESSION['user_id'])) {
    $fallbackId = intval($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
    if ($fallbackId > 0) {
        $chk = $pdo->prepare("SELECT id, full_name, role FROM users WHERE id = ?");
        $chk->execute([$fallbackId]);
        $chkUser = $chk->fetch();
        if ($chkUser) {
            $_SESSION['user_id']   = $chkUser['id'];
            $_SESSION['full_name'] = $chkUser['full_name'];
            $_SESSION['role']      = $chkUser['role'];
        }
    }
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'fetchIssues') {
    try {
        // Auto-approve issues where 24 hours have passed since resolution
        $pdo->query("UPDATE issues SET is_citizen_approved = 1 WHERE status = 'Resolved' AND is_citizen_approved = 0 AND TIMESTAMPDIFF(HOUR, resolved_at, NOW()) > 24");

        $filter = $_GET['filter'] ?? '';
        
        $sql = "SELECT i.*, u.full_name as reported_by,
                (TIMESTAMPDIFF(HOUR, i.resolved_at, NOW()) <= 24) as can_reopen,
                CASE
                    WHEN i.status = 'Open' AND COALESCE(last_log.action_description, '') LIKE 'Issue rejected.%'
                    THEN 'Rejected'
                    ELSE i.status
                END as display_status,
                CASE
                    WHEN COALESCE(last_log.action_description, '') LIKE 'Issue rejected. Remark:%'
                    THEN TRIM(SUBSTRING(last_log.action_description, LENGTH('Issue rejected. Remark:') + 1))
                    ELSE ''
                END as rejection_remark
                FROM issues i
                JOIN users u ON i.user_id = u.id
                LEFT JOIN (
                    SELECT al.issue_id, al.action_description
                    FROM activity_logs al
                    INNER JOIN (
                        SELECT issue_id, MAX(id) as max_id
                        FROM activity_logs
                        GROUP BY issue_id
                    ) latest ON latest.max_id = al.id
                ) last_log ON last_log.issue_id = i.id ";
                
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
        foreach ($issues as &$issue) {
            $issue['status'] = $issue['display_status'];
        }
        unset($issue);
        
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
    $photoPath = null;
    $photoWarning = null;
    
    if (empty($type) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Type and location are required.']);
        exit;
    }

    // Parse latitude and longitude from location text if it is in "Geo: lat, lng" format
    $latitude = null;
    $longitude = null;
    if (preg_match('/^Geo:\s*(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/i', $location, $matches)) {
        $latitude = floatval($matches[1]);
        $longitude = floatval($matches[2]);
    }
    
    // Optional photo upload — failures are non-fatal; ticket is saved without photo
    if (isset($_FILES['photo']) && isset($_FILES['photo']['error']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        do { // use do-while(false) so we can break out on any photo error without aborting the ticket
            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $photoWarning = 'Photo could not be uploaded (upload error ' . $_FILES['photo']['error'] . ').';
                break;
            }
            
            $tmpPath = $_FILES['photo']['tmp_name'];
            $fileInfo = @getimagesize($tmpPath);
            if ($fileInfo === false) {
                $photoWarning = 'Photo skipped: only image files are allowed.';
                break;
            }
            
            $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = $fileInfo['mime'] ?? '';
            if (!isset($allowedMime[$mime])) {
                $photoWarning = 'Photo skipped: allowed formats are JPG, PNG, WEBP.';
                break;
            }
            
            // Resolve absolute path to uploads/issues relative to project root
            $uploadDir = dirname(__DIR__) . '/uploads/issues';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0775, true)) {
                    $photoWarning = 'Photo skipped: could not create upload directory.';
                    break;
                }
                @chmod($uploadDir, 0775);
            }
            
            $fileName = 'issue_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
            $targetPath = $uploadDir . '/' . $fileName;
            if (!move_uploaded_file($tmpPath, $targetPath)) {
                $photoWarning = 'Photo skipped: could not save file to server.';
                break;
            }
            
            // Store web path relative to project root
            $photoPath = 'uploads/issues/' . $fileName;

            // Try to extract GPS from photo EXIF data on the server side
            try {
                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($targetPath);
                    if ($exif && isset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef'])) {
                        $gpsRationalToFloat = function($rational) {
                            $parts = explode('/', $rational);
                            if (count($parts) === 2 && $parts[1] != 0) {
                                return (float) $parts[0] / (float) $parts[1];
                            }
                            return (float) $rational;
                        };
                        
                        $getGpsCoordinate = function($coordinate, $ref) use ($gpsRationalToFloat) {
                            if (!is_array($coordinate)) return null;
                            $degrees = count($coordinate) > 0 ? $gpsRationalToFloat($coordinate[0]) : 0;
                            $minutes = count($coordinate) > 1 ? $gpsRationalToFloat($coordinate[1]) : 0;
                            $seconds = count($coordinate) > 2 ? $gpsRationalToFloat($coordinate[2]) : 0;
                            
                            $flip = ($ref === 'W' || $ref === 'S') ? -1 : 1;
                            return $flip * ($degrees + ($minutes / 60.0) + ($seconds / 3600.0));
                        };

                        $exifLat = $getGpsCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
                        $exifLng = $getGpsCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']);

                        if ($exifLat !== null && $exifLng !== null) {
                            $latitude = $exifLat;
                            $longitude = $exifLng;
                            // Update location text to reflect the actual GPS coordinates from photo EXIF
                            $location = sprintf("Geo: %.6f, %.6f", $latitude, $longitude);
                        }
                    }
                }
            } catch (Exception $exifErr) {
                // Ignore EXIF parsing errors, fall back to whatever client sent
            }
        } while (false);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO issues (user_id, issue_type, location_text, latitude, longitude, ward, description, priority, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $location, $latitude, $longitude, $ward, $description, $priority, $photoPath]);
        
        // Add activity log
        $issueId = $pdo->lastInsertId();
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (issue_id, action_description) VALUES (?, ?)");
        $logStmt->execute([$issueId, "New report submitted – $type"]);
        
        $response = ['success' => true, 'message' => 'Issue submitted successfully', 'issue_id' => $issueId];
        if ($photoWarning) $response['photo_warning'] = $photoWarning;
        echo json_encode($response);
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
elseif ($action === 'fetchEngineerIssues') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        // Find engineer ID linked to this user
        $engStmt = $pdo->prepare("SELECT id FROM engineers WHERE user_id = ?");
        $engStmt->execute([$userId]);
        $engineer = $engStmt->fetch();
        
        if (!$engineer) {
            echo json_encode(['success' => false, 'message' => 'Engineer profile not found.']);
            exit;
        }
        $engineerId = $engineer['id'];
        
        // Fetch assigned issues
        $stmt = $pdo->prepare("
            SELECT i.*, u.full_name as reported_by
            FROM issues i
            JOIN users u ON i.user_id = u.id
            WHERE i.assigned_engineer_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$engineerId]);
        $issues = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'issues' => $issues]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
elseif ($action === 'engineerUpdateStatus') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $issueId = intval($_POST['issue_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');
    
    // Validate status
    if ($status !== 'In Progress' && $status !== 'Resolved') {
        echo json_encode(['success' => false, 'message' => 'Invalid status. Status must be "In Progress" or "Resolved".']);
        exit;
    }
    
    try {
        // Find engineer ID linked to this user
        $engStmt = $pdo->prepare("SELECT id FROM engineers WHERE user_id = ?");
        $engStmt->execute([$userId]);
        $engineer = $engStmt->fetch();
        
        if (!$engineer) {
            echo json_encode(['success' => false, 'message' => 'Engineer profile not found.']);
            exit;
        }
        $engineerId = $engineer['id'];
        
        // Verify the issue is assigned to this engineer
        $chkStmt = $pdo->prepare("SELECT id, status FROM issues WHERE id = ? AND assigned_engineer_id = ?");
        $chkStmt->execute([$issueId, $engineerId]);
        $issue = $chkStmt->fetch();
        
        if (!$issue) {
            echo json_encode(['success' => false, 'message' => 'Issue not found or not assigned to you.']);
            exit;
        }
        
        // Update issue
        $resolvedAt = ($status === 'Resolved') ? date('Y-m-d H:i:s') : null;
        $updateStmt = $pdo->prepare("UPDATE issues SET status = ?, resolved_at = ? WHERE id = ?");
        $updateStmt->execute([$status, $resolvedAt, $issueId]);
        
        // Add activity log
        $desc = "Engineer marked status as '{$status}'" . ($note !== '' ? ". Remark: {$note}" : "");
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (issue_id, action_description) VALUES (?, ?)");
        $logStmt->execute([$issueId, $desc]);
        
        // Add notification for the citizen
        $notifTitle = "Issue status update by engineer";
        $notifMsg = "Your reported issue has been updated to '{$status}' by the assigned engineer." . ($note !== '' ? " Remark: {$note}" : "");
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, issue_id, title, message) SELECT user_id, id, ?, ? FROM issues WHERE id = ?");
        $notifStmt->execute([$notifTitle, $notifMsg, $issueId]);
        
        echo json_encode(['success' => true, 'message' => 'Issue status updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
