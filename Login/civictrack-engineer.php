<?php
session_start();
require_once '../api/db_connect.php';

// Auth guard - only engineers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: civictrack_login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch engineer profile info
$engStmt = $pdo->prepare("
    SELECT e.*, u.full_name, u.phone
    FROM engineers e
    JOIN users u ON e.user_id = u.id
    WHERE e.user_id = ?
");
$engStmt->execute([$userId]);
$engineer = $engStmt->fetch();

$engineerName     = $engineer['full_name']     ?? 'Engineer';
$engineerSpecialty = $engineer['specialty']    ?? 'General';
$engineerWard     = $engineer['assigned_ward'] ?? 'All Wards';
$employeeId       = $engineer['employee_id']   ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – Engineer Portal</title>
    <meta name="description" content="CivicTrack Engineer Portal – view and update status of assigned civic issues.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="civictrack-engineer.css">
</head>
<body>

<!-- ══════════════════════════════
     SIDEBAR
══════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="../Login/logo.jpg" alt="CivicTrack">
    </div>
    <span class="sidebar-role-badge">⚙️ Engineer</span>

    <div class="nav-section-label">Workspace</div>
    <a href="civictrack-engineer.php" class="nav-item active" id="navTasks">
        <span class="nav-icon">📋</span>
        <span>My Tasks</span>
        <span class="nav-badge" id="taskBadge">0</span>
    </a>

    <div class="nav-section-label">Info</div>
    <a href="#" class="nav-item" onclick="showProfileModal(); return false;">
        <span class="nav-icon">👤</span>
        <span>My Profile</span>
    </a>
    <a href="civictrack_login.php" class="nav-item">
        <span class="nav-icon">🚪</span>
        <span>Log Out</span>
    </a>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" id="sidebarAvatar"><?= strtoupper(substr($engineerName, 0, 1)) ?></div>
            <div>
                <div class="user-name" id="sidebarName"><?= htmlspecialchars($engineerName) ?></div>
                <div class="user-ward" id="sidebarWard"><?= htmlspecialchars($engineerWard) ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- ══════════════════════════════
     MAIN
══════════════════════════════ -->
<main class="main">
    <div class="topbar">
        <div class="topbar-title">Engineer Portal</div>
        <div class="topbar-right">
            <div class="topbar-info" id="topbarInfo">
                🪪 ID: <?= htmlspecialchars($employeeId) ?> &nbsp;|&nbsp; 🔧 <?= htmlspecialchars($engineerSpecialty) ?>
            </div>
        </div>
    </div>

    <div class="page-body">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div>
                <h2 id="bannerTitle">Welcome back, <?= htmlspecialchars(explode(' ', $engineerName)[0]) ?>! 👋</h2>
                <p>Here are your assigned civic issues. Update statuses to keep citizens informed.</p>
            </div>
            <div class="welcome-icon">🦺</div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orange">📋</div>
                <div>
                    <div class="stat-num" id="statTotal">0</div>
                    <div class="stat-label">Total Assigned</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">🔧</div>
                <div>
                    <div class="stat-num" id="statActive">0</div>
                    <div class="stat-label">Active Tasks</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div>
                    <div class="stat-num" id="statResolved">0</div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>
        </div>

        <!-- Task Board Tabs -->
        <div class="board-tabs">
            <div class="board-tab active" id="tabActive" onclick="switchTab('active')">
                🔧 Active Tasks
                <span class="tab-count" id="tabCountActive">0</span>
            </div>
            <div class="board-tab" id="tabResolved" onclick="switchTab('resolved')">
                ✅ Resolved
                <span class="tab-count" id="tabCountResolved">0</span>
            </div>
        </div>

        <!-- Active Tasks Panel -->
        <div class="board-panel active" id="panelActive">
            <div class="task-grid" id="activeGrid">
                <div class="empty-board">
                    <div class="empty-icon">⏳</div>
                    <p>Loading tasks…</p>
                </div>
            </div>
        </div>

        <!-- Resolved Tasks Panel -->
        <div class="board-panel" id="panelResolved">
            <div class="task-grid" id="resolvedGrid">
                <div class="empty-board">
                    <div class="empty-icon">⏳</div>
                    <p>Loading…</p>
                </div>
            </div>
        </div>

    </div><!-- /page-body -->
</main>

<!-- ══════════════════════════════
     DETAIL & UPDATE MODAL
══════════════════════════════ -->
<div class="modal-overlay" id="detailModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Issue Details</h3>
            <button class="modal-close" onclick="closeModal()" aria-label="Close">✕</button>
        </div>
        <div class="modal-body">

            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Type</div>
                    <div class="detail-value" id="dType">–</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Current Status</div>
                    <div class="detail-value" id="dStatus">–</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Priority</div>
                    <div class="detail-value" id="dPriority">–</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Ward</div>
                    <div class="detail-value" id="dWard">–</div>
                </div>
                <div class="detail-item full-width">
                    <div class="detail-label">Location</div>
                    <div class="detail-value" id="dLocation">–</div>
                    <a id="dMapLink" href="#" target="_blank" class="map-link" style="display:none;">
                        🗺️ Open in Google Maps
                    </a>
                </div>
                <div class="detail-item full-width">
                    <div class="detail-label">Description</div>
                    <div class="detail-value" id="dDesc" style="color: #6b7280; font-style: italic;">–</div>
                </div>
                <div class="detail-item full-width">
                    <div class="detail-label">Reported By</div>
                    <div class="detail-value" id="dReporter">–</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Reported On</div>
                    <div class="detail-value" id="dDate">–</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Issue #</div>
                    <div class="detail-value" id="dIssueId">–</div>
                </div>
            </div>

            <div id="dPhotoWrap" class="issue-photo-preview" style="display:none;">
                <img id="dPhoto" src="" alt="Issue Photo">
            </div>

            <hr class="modal-divider" id="actionDivider">

            <!-- Update Action Box (hidden for resolved) -->
            <div class="action-box" id="updateActionBox">
                <div class="action-box-title">
                    🔄 Update Work Status
                </div>
                <div class="form-group">
                    <label class="form-label" for="statusSelect">New Status</label>
                    <select class="form-select" id="statusSelect">
                        <option value="In Progress">🔧 In Progress – Work underway</option>
                        <option value="Resolved">✅ Resolved – Work completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="remarkInput">Work Remark <span style="color:#9ca3af;font-weight:400;text-transform:none;">(optional)</span></label>
                    <textarea class="form-textarea" id="remarkInput" placeholder="e.g. Repaired the pothole, concrete laid on 4-Jun-2026…"></textarea>
                </div>
                <button class="modal-submit-btn" id="submitUpdateBtn" onclick="submitUpdate()">
                    ✅ Submit Status Update
                </button>
            </div>

            <!-- Already-resolved message -->
            <div class="action-box" id="resolvedBox" style="display:none; background:#ecfdf5; border-color:#6ee7b7;">
                <div class="action-box-title" style="color:#065f46;">
                    ✅ This issue is marked as Resolved
                </div>
                <p style="font-size:13px; color:#047857;">
                    This task has been completed. The citizen will be prompted to verify and approve the resolution.
                </p>
            </div>

        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal-overlay" id="profileModal">
    <div class="modal">
        <div class="modal-header">
            <h3>My Engineer Profile</h3>
            <button class="modal-close" onclick="closeProfileModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item full-width">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value"><?= htmlspecialchars($engineerName) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Employee ID</div>
                    <div class="detail-value"><?= htmlspecialchars($employeeId) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Specialty</div>
                    <div class="detail-value"><?= htmlspecialchars($engineerSpecialty) ?></div>
                </div>
                <div class="detail-item full-width">
                    <div class="detail-label">Assigned Ward</div>
                    <div class="detail-value"><?= htmlspecialchars($engineerWard) ?></div>
                </div>
                <div class="detail-item full-width">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?= htmlspecialchars($engineer['phone'] ?? 'N/A') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<!-- ══════════════════════════════
     JAVASCRIPT
══════════════════════════════ -->
<script>
    /* ─── State ─── */
    let allIssues = [];
    let currentIssueId = null;

    /* ─── Issue type icons ─── */
    const ICONS = {
        '🕳️ Pothole': '🕳️',
        '💡 Street Light': '💡',
        '🗑️ Garbage': '🗑️',
        '💧 Water Supply': '💧',
        '🌳 Tree / Fallen Branch': '🌳',
        '🚧 Road Damage': '🚧',
        'Other': '📌'
    };

    /* ─── Helpers ─── */
    function timeAgo(d) {
        const diff = (Date.now() - new Date(d).getTime()) / 1000;
        if (diff < 60)    return 'Just now';
        if (diff < 3600)  return `${Math.floor(diff/60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff/3600)}h ago`;
        return `${Math.floor(diff/86400)}d ago`;
    }

    function priorityClass(p) {
        if (p === 'Urgent') return 'p-urgent';
        if (p === 'High')   return 'p-high';
        return 'p-normal';
    }

    function statusPillClass(s) {
        if (s === 'In Progress') return 'sp-progress';
        if (s === 'Resolved')    return 'sp-resolved';
        return 'sp-open';
    }

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
        t.className = `toast ${type} show`;
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    /* ─── Tab switching ─── */
    function switchTab(tab) {
        document.querySelectorAll('.board-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.board-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
        document.getElementById('panel' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
    }

    /* ─── Render task card ─── */
    function renderCard(issue) {
        const icon = ICONS[issue.issue_type] || '📌';
        const isResolved = issue.status === 'Resolved';
        const loc = issue.location_text || '–';
        const shortLoc = loc.length > 30 ? loc.substring(0, 30) + '…' : loc;
        const desc = issue.description || 'No description provided.';
        const pCls = priorityClass(issue.priority);
        const sCls = statusPillClass(issue.status);

        return `
        <div class="task-card ${isResolved ? 'resolved' : ''}" onclick="openDetail(${issue.id})" id="card-${issue.id}">
            <div class="task-header">
                <div class="task-type">
                    <span class="task-emoji">${icon}</span>
                    <span class="task-title">${issue.issue_type.replace(/^[^\w]+/, '')}</span>
                </div>
                <span class="task-priority-badge ${pCls}">${issue.priority}</span>
            </div>
            <div class="task-desc">${desc}</div>
            <div class="task-footer">
                <span class="task-location">📍 ${shortLoc}</span>
                <span class="task-reporter">by ${issue.reported_by}</span>
            </div>
            <span class="task-status-pill ${sCls}">${issue.status}</span>
        </div>`;
    }

    /* ─── Render all tasks ─── */
    function renderTasks() {
        const active   = allIssues.filter(i => i.status !== 'Resolved');
        const resolved = allIssues.filter(i => i.status === 'Resolved');

        // Update counts
        document.getElementById('statTotal').textContent    = allIssues.length;
        document.getElementById('statActive').textContent   = active.length;
        document.getElementById('statResolved').textContent = resolved.length;
        document.getElementById('tabCountActive').textContent   = active.length;
        document.getElementById('tabCountResolved').textContent = resolved.length;
        document.getElementById('taskBadge').textContent = active.length;

        // Active grid
        const activeGrid = document.getElementById('activeGrid');
        if (active.length === 0) {
            activeGrid.innerHTML = `
                <div class="empty-board">
                    <div class="empty-icon">🎉</div>
                    <p>No active tasks! All caught up.</p>
                </div>`;
        } else {
            activeGrid.innerHTML = active.map(renderCard).join('');
        }

        // Resolved grid
        const resolvedGrid = document.getElementById('resolvedGrid');
        if (resolved.length === 0) {
            resolvedGrid.innerHTML = `
                <div class="empty-board">
                    <div class="empty-icon">📋</div>
                    <p>No resolved tasks yet.</p>
                </div>`;
        } else {
            resolvedGrid.innerHTML = resolved.map(renderCard).join('');
        }
    }

    /* ─── Fetch issues from API ─── */
    function loadIssues() {
        fetch('../api/issues.php?action=fetchEngineerIssues')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    allIssues = data.issues;
                    renderTasks();
                } else {
                    document.getElementById('activeGrid').innerHTML =
                        `<div class="empty-board"><div class="empty-icon">⚠️</div><p>${data.message || 'Failed to load tasks.'}</p></div>`;
                }
            })
            .catch(() => {
                document.getElementById('activeGrid').innerHTML =
                    `<div class="empty-board"><div class="empty-icon">⚠️</div><p>Network error. Please refresh.</p></div>`;
            });
    }

    /* ─── Open detail modal ─── */
    function openDetail(issueId) {
        const issue = allIssues.find(i => i.id === issueId);
        if (!issue) return;
        currentIssueId = issueId;

        const isResolved = issue.status === 'Resolved';

        document.getElementById('modalTitle').textContent  = issue.issue_type;
        document.getElementById('dType').textContent       = issue.issue_type;
        document.getElementById('dStatus').textContent     = issue.status;
        document.getElementById('dPriority').textContent   = issue.priority;
        document.getElementById('dWard').textContent       = issue.ward     || '–';
        document.getElementById('dLocation').textContent   = issue.location_text || '–';
        document.getElementById('dDesc').textContent       = issue.description   || 'No description.';
        document.getElementById('dReporter').textContent   = issue.reported_by   || '–';
        document.getElementById('dDate').textContent       = new Date(issue.created_at).toLocaleDateString('en-IN', {day:'numeric', month:'short', year:'numeric'});
        document.getElementById('dIssueId').textContent    = '#' + issue.id;

        // Map link
        const mapLink = document.getElementById('dMapLink');
        if (issue.latitude && issue.longitude) {
            mapLink.href = `https://www.google.com/maps?q=${issue.latitude},${issue.longitude}`;
            mapLink.style.display = 'inline-flex';
        } else if (issue.location_text && issue.location_text.startsWith('Geo:')) {
            const parts = issue.location_text.replace('Geo:', '').trim().split(',');
            if (parts.length === 2) {
                mapLink.href = `https://www.google.com/maps?q=${parts[0].trim()},${parts[1].trim()}`;
                mapLink.style.display = 'inline-flex';
            }
        } else {
            mapLink.style.display = 'none';
        }

        // Photo
        const photoWrap = document.getElementById('dPhotoWrap');
        if (issue.photo_path) {
            document.getElementById('dPhoto').src = `../${issue.photo_path}`;
            photoWrap.style.display = 'block';
        } else {
            photoWrap.style.display = 'none';
        }

        // Show/hide update box vs resolved box
        document.getElementById('updateActionBox').style.display = isResolved ? 'none' : 'block';
        document.getElementById('resolvedBox').style.display     = isResolved ? 'block' : 'none';

        // Pre-select status in dropdown
        const sel = document.getElementById('statusSelect');
        sel.value = issue.status === 'In Progress' ? 'In Progress' : 'In Progress';
        document.getElementById('remarkInput').value = '';
        document.getElementById('submitUpdateBtn').disabled = false;
        document.getElementById('submitUpdateBtn').textContent = '✅ Submit Status Update';

        document.getElementById('detailModal').classList.add('open');
    }

    /* ─── Close modal ─── */
    function closeModal() {
        document.getElementById('detailModal').classList.remove('open');
        currentIssueId = null;
    }

    /* ─── Submit status update ─── */
    function submitUpdate() {
        if (!currentIssueId) return;

        const status = document.getElementById('statusSelect').value;
        const note   = document.getElementById('remarkInput').value.trim();
        const btn    = document.getElementById('submitUpdateBtn');

        btn.disabled = true;
        btn.textContent = '⏳ Saving…';

        const fd = new FormData();
        fd.append('action',   'engineerUpdateStatus');
        fd.append('issue_id', currentIssueId);
        fd.append('status',   status);
        fd.append('note',     note);

        fetch('../api/issues.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update local state immediately
                    const idx = allIssues.findIndex(i => i.id === currentIssueId);
                    if (idx !== -1) {
                        allIssues[idx].status = status;
                        if (status === 'Resolved') {
                            allIssues[idx].resolved_at = new Date().toISOString();
                        }
                    }
                    closeModal();
                    renderTasks();
                    showToast(`Issue #${currentIssueId} updated to "${status}" successfully!`, 'success');
                } else {
                    btn.disabled = false;
                    btn.textContent = '✅ Submit Status Update';
                    showToast(data.message || 'Update failed. Please try again.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.textContent = '✅ Submit Status Update';
                showToast('Network error. Please try again.', 'error');
            });
    }

    /* ─── Profile modal ─── */
    function showProfileModal() {
        document.getElementById('profileModal').classList.add('open');
    }
    function closeProfileModal() {
        document.getElementById('profileModal').classList.remove('open');
    }

    // Close modals on backdrop click
    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    document.getElementById('profileModal').addEventListener('click', function(e) {
        if (e.target === this) closeProfileModal();
    });

    // Load on start
    loadIssues();
</script>

</body>
</html>
