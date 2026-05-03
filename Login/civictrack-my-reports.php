<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – My Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="civictrack-dashboard.css">
    <style>
        .page-content { padding: 28px; }
        .report-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../Login/logo.jpg" alt="CivicTrack">
        </div>
        <div class="nav-section-label">Main</div>
        <a href="civictrack-dashboard.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            <span>Dashboard</span>
        </a>
        <a href="civictrack-my-reports.php" class="nav-item active">
            <span class="nav-icon">📋</span>
            <span>My Reports</span>
            <span class="nav-badge" id="myReportsBadge">3</span>
        </a>
        <a href="civictrack-nearby-issues.php" class="nav-item">
            <span class="nav-icon">🗺️</span>
            <span>Nearby Issues</span>
        </a>
        <a href="civictrack-notifications.php" class="nav-item">
            <span class="nav-icon">🔔</span>
            <span>Notifications</span>
            <span class="nav-badge">2</span>
        </a>
        <div class="nav-section-label">Community</div>
        <a href="civictrack-ward-stats.php" class="nav-item">
            <span class="nav-icon">📊</span>
            <span>Ward Stats</span>
        </a>
        <a href="civictrack-leaderboard.php" class="nav-item">
            <span class="nav-icon">🏆</span>
            <span>Leaderboard</span>
        </a>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar" id="sidebarAvatar">R</div>
                <div>
                    <div class="user-name" id="sidebarName">Resident</div>
                    <div class="user-ward" id="sidebarWard">Ward 42, Mumbai</div>
                </div>
            </div>
        </div>
    </aside>
    <main class="main">
        <div class="topbar">
            <div class="topbar-title">My Reports</div>
            <div class="topbar-right">
                <a href="civictrack-notifications.php" class="notif-bell" title="Notifications">
                    🔔<span class="notif-dot"></span>
                </a>
                <button class="topbar-btn" onclick="openModal()">＋ Report Issue</button>
            </div>
        </div>
        <div class="page-content">
            <!-- Dynamic content will be injected here -->
            <div id="loading" style="text-align: center; padding: 40px; color: #666;">Loading your reports...</div>
        </div>
    </main>
    <div class="modal-overlay" id="reportModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Report a Civic Issue</h3>
                <button class="modal-close" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 20px;">Use the Dashboard to quick report.</p>
                <button class="modal-submit-btn" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
    <script>
        const userName = sessionStorage.getItem('ct_name')  || 'Resident';
        const userWard = sessionStorage.getItem('ct_ward')  || 'Ward 42, Mumbai';
        const userCity = sessionStorage.getItem('ct_city')  || 'Mumbai';
        document.getElementById('sidebarName').textContent = userName;
        document.getElementById('sidebarWard').textContent = `${userWard}, ${userCity}`;
        document.getElementById('sidebarAvatar').textContent = userName.charAt(0).toUpperCase();

        function openModal() { document.getElementById('reportModal').classList.add('open'); }
        function closeModal() { document.getElementById('reportModal').classList.remove('open'); }

        document.addEventListener('DOMContentLoaded', fetchMyReports);

        function fetchMyReports() {
            fetch('../api/issues.php?action=fetchIssues&filter=mine')
            .then(res => res.json())
            .then(data => {
                const container = document.querySelector('.page-content');
                
                if (data.success) {
                    if (data.issues.length === 0) {
                        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">You haven\'t reported any issues yet.</div>';
                        document.getElementById('myReportsBadge').textContent = '0';
                        return;
                    }
                    
                    document.getElementById('myReportsBadge').textContent = data.issues.length;
                    
                    let html = '';
                    data.issues.forEach(issue => {
                        let statusClass = 'status-open';
                        if (issue.status === 'In Progress') statusClass = 'status-progress';
                        if (issue.status === 'Resolved') statusClass = 'status-resolved';
                        
                        let statusText = issue.status;
                        
                        // Action buttons for resolved issues
                        let actionsHtml = '';
                        if (issue.status === 'Resolved' && parseInt(issue.is_citizen_approved) === 0) {
                            statusText = 'Pending Approval';
                            if (parseInt(issue.can_reopen) === 1) {
                                actionsHtml = `
                                    <div style="margin-top: 15px; display: flex; gap: 10px; background: #fff3cd; padding: 12px; border-radius: 8px; border: 1px solid #ffe69c;">
                                        <div style="flex: 1;">
                                            <strong>Work Completed?</strong><br>
                                            <span style="font-size: 13px; color: #664d03;">You have 24 hours to verify.</span>
                                        </div>
                                        <button onclick="approveIssue(${issue.id})" style="background: #198754; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 500;">✅ Approve Fix</button>
                                        <button onclick="reopenIssue(${issue.id})" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 500;">❌ Not Done (Reopen)</button>
                                    </div>
                                `;
                            }
                        } else if (issue.status === 'Resolved' && parseInt(issue.is_citizen_approved) === 1) {
                            statusText = 'Approved & Closed';
                        }
                        
                        html += `
                        <div class="report-card">
                            <h3>${issue.issue_type}</h3>
                            <p><strong>Location:</strong> ${issue.location_text}</p>
                            <p>Status: <span class="issue-status ${statusClass}">${statusText}</span></p>
                            ${issue.description ? `<p style="color: #666; margin-top: 8px; font-size: 14px;">"${issue.description}"</p>` : ''}
                            <p class="issue-meta" style="margin-top: 10px;">Reported on ${new Date(issue.created_at).toLocaleDateString()}</p>
                            ${actionsHtml}
                        </div>`;
                    });
                    
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div style="color: red; text-align: center;">Failed to load reports.</div>';
                }
            })
            .catch(err => console.error(err));
        }

        function approveIssue(id) {
            if (!confirm("Are you sure you want to approve this work as complete?")) return;
            
            const formData = new FormData();
            formData.append('action', 'approveIssue');
            formData.append('issue_id', id);
            
            fetch('../api/issues.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert("Thank you! The issue is now closed.");
                    fetchMyReports();
                } else {
                    alert(data.message || "Action failed");
                }
            });
        }

        function reopenIssue(id) {
            if (!confirm("Are you sure the work is incomplete? This will reopen the ticket and notify the admin.")) return;
            
            const formData = new FormData();
            formData.append('action', 'reopenIssue');
            formData.append('issue_id', id);
            
            fetch('../api/issues.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert("Ticket reopened. We will investigate further.");
                    fetchMyReports();
                } else {
                    alert(data.message || "Action failed");
                }
            });
        }
    </script>
</body>
</html>
