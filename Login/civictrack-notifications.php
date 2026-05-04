<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – Notifications</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="civictrack-dashboard.css">
    <style>
        .page-content { padding: 28px; }
        .notif-card { background:#fff; padding:16px 20px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); margin-bottom:10px; display:flex; align-items:flex-start; gap:14px; cursor:pointer; transition:box-shadow .2s; }
        .notif-card:hover { box-shadow:0 4px 18px rgba(0,0,0,0.12); }
        .notif-card.unread { background:#e8f0fe; border-left:3px solid #1a73e8; }
        .notif-card.unread:hover { background:#d8e6fc; }
        .notif-icon { font-size:22px; flex-shrink:0; padding-top:2px; }
        .notif-body { flex:1; }
        .notif-title { font-weight:600; font-size:14px; margin-bottom:3px; }
        .notif-msg { font-size:13px; color:#4b5563; }
        .notif-time { font-size:11px; color:#9ca3af; margin-top:5px; }
        .notif-actions { display:flex; gap:10px; margin-top:20px; }
        .empty-state { text-align:center; padding:60px 20px; color:#9ca3af; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo"><img src="../Login/logo.jpg" alt="CivicTrack"></div>
        <div class="nav-section-label">Main</div>
        <a href="civictrack-dashboard.php" class="nav-item"><span class="nav-icon">🏠</span><span>Dashboard</span></a>
        <a href="civictrack-my-reports.php" class="nav-item"><span class="nav-icon">📋</span><span>My Reports</span><span class="nav-badge" id="myReportsBadge">0</span></a>
        <a href="civictrack-nearby-issues.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Nearby Issues</span></a>
        <a href="civictrack-notifications.php" class="nav-item active"><span class="nav-icon">🔔</span><span>Notifications</span><span class="nav-badge" id="notifBadge">0</span></a>
        <div class="nav-section-label">Community</div>
        <a href="civictrack-ward-stats.php" class="nav-item"><span class="nav-icon">📊</span><span>Ward Stats</span></a>
        <a href="civictrack-leaderboard.php" class="nav-item"><span class="nav-icon">🏆</span><span>Leaderboard</span></a>
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
            <div class="topbar-title">Notifications</div>
            <div class="topbar-right">
                <a href="civictrack-notifications.php" class="notif-bell" title="Notifications">🔔<span class="notif-dot" id="notifDot" style="display:none;"></span></a>
                <button class="topbar-btn" id="markAllBtn" onclick="markAllRead()">✔ Mark All Read</button>
            </div>
        </div>
        <div class="page-content">
            <div id="notifContainer"><div class="empty-state">⏳ Loading notifications…</div></div>
        </div>
    </main>
    <div class="toast" id="toast"></div>
    <script>
        const userName = sessionStorage.getItem('ct_name') || 'Resident';
        const userWard = sessionStorage.getItem('ct_ward') || 'Ward 42, Mumbai';
        const userCity = sessionStorage.getItem('ct_city') || 'Mumbai';
        document.getElementById('sidebarName').textContent = userName;
        document.getElementById('sidebarWard').textContent = `${userWard}, ${userCity}`;
        document.getElementById('sidebarAvatar').textContent = userName.charAt(0).toUpperCase();

        function timeAgo(dateStr) {
            const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
            if (diff < 60)   return 'Just now';
            if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
            if (diff < 86400)return `${Math.floor(diff/3600)}h ago`;
            return `${Math.floor(diff/86400)}d ago`;
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg; t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function loadNotifications() {
            fetch('../api/issues.php?action=fetchNotifications')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('notifContainer');
                    if (!data.success || data.notifications.length === 0) {
                        container.innerHTML = '<div class="empty-state">🔔 No notifications yet.<br><small>You\'ll see updates about your reports here.</small></div>';
                        return;
                    }
                    document.getElementById('notifBadge').textContent = data.unread_count || 0;
                    if (data.unread_count > 0) document.getElementById('notifDot').style.display = '';

                    container.innerHTML = data.notifications.map(n => `
                        <div class="notif-card ${n.is_read == 0 ? 'unread' : ''}" onclick="markRead(${n.id}, this)">
                            <div class="notif-icon">${n.is_read == 0 ? '🔔' : '🔕'}</div>
                            <div class="notif-body">
                                <div class="notif-title">${n.title}</div>
                                <div class="notif-msg">${n.message}</div>
                                <div class="notif-time">${timeAgo(n.created_at)}</div>
                            </div>
                        </div>`).join('');
                })
                .catch(() => {
                    document.getElementById('notifContainer').innerHTML = '<div class="empty-state" style="color:#e53e3e;">⚠️ Could not load notifications. Please log in again.</div>';
                });
        }

        function markRead(id, el) {
            if (!el.classList.contains('unread')) return;
            const fd = new FormData();
            fd.append('action', 'markNotificationRead');
            fd.append('notif_id', id);
            fetch('../api/issues.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(() => {
                    el.classList.remove('unread');
                    el.querySelector('.notif-icon').textContent = '🔕';
                    const badge = document.getElementById('notifBadge');
                    const cur = parseInt(badge.textContent) - 1;
                    badge.textContent = Math.max(0, cur);
                    if (cur <= 1) document.getElementById('notifDot').style.display = 'none';
                });
        }

        function markAllRead() {
            const fd = new FormData();
            fd.append('action', 'markNotificationRead');
            fd.append('notif_id', 0);
            fetch('../api/issues.php', { method:'POST', body:fd })
                .then(() => { loadNotifications(); showToast('✅ All marked as read'); });
        }

        document.addEventListener('DOMContentLoaded', loadNotifications);
    </script>
</body>
</html>
