<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – Leaderboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="civictrack-dashboard.css">
    <style>
        .page-content { padding: 28px; }
        .lb-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,0.08); overflow:hidden; }
        .lb-header { padding:18px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; }
        .lb-item { display:flex; align-items:center; padding:14px 24px; border-bottom:1px solid #f3f4f6; gap:16px; transition:background .2s; }
        .lb-item:last-child { border-bottom:none; }
        .lb-item:hover { background:#f9fafb; }
        .lb-item.me { background:#f0fdf4; }
        .rank { font-size:18px; font-weight:700; min-width:36px; text-align:center; }
        .lb-avatar { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; color:#fff; flex-shrink:0; }
        .lb-info { flex:1; }
        .lb-name { font-weight:600; font-size:14px; }
        .lb-ward { font-size:12px; color:#6b7280; }
        .lb-reports { font-size:12px; color:#9ca3af; text-align:center; min-width:50px; }
        .lb-pts { font-weight:700; font-size:15px; color:var(--primary); text-align:right; min-width:70px; }
        .medal-1 { color:#f59e0b; }
        .medal-2 { color:#94a3b8; }
        .medal-3 { color:#cd7f32; }
        .avatar-colors { --c1:#1a73e8;--c2:#2D6A4F;--c3:#7c3aed;--c4:#e67e22;--c5:#C0392B; }
        .empty-state { text-align:center; padding:60px; color:#9ca3af; }
    </style>
</head>
<body class="avatar-colors">
    <aside class="sidebar">
        <div class="sidebar-logo"><img src="../Login/logo.jpg" alt="CivicTrack"></div>
        <div class="nav-section-label">Main</div>
        <a href="civictrack-dashboard.php" class="nav-item"><span class="nav-icon">🏠</span><span>Dashboard</span></a>
        <a href="civictrack-my-reports.php" class="nav-item"><span class="nav-icon">📋</span><span>My Reports</span><span class="nav-badge" id="myReportsBadge">0</span></a>
        <a href="civictrack-nearby-issues.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Nearby Issues</span></a>
        <a href="civictrack-notifications.php" class="nav-item"><span class="nav-icon">🔔</span><span>Notifications</span><span class="nav-badge" id="notifBadge">0</span></a>
        <div class="nav-section-label">Community</div>
        <a href="civictrack-ward-stats.php" class="nav-item"><span class="nav-icon">📊</span><span>Ward Stats</span></a>
        <a href="civictrack-leaderboard.php" class="nav-item active"><span class="nav-icon">🏆</span><span>Leaderboard</span></a>
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
            <div class="topbar-title">Leaderboard</div>
            <div class="topbar-right">
                <a href="civictrack-notifications.php" class="notif-bell" title="Notifications">🔔<span class="notif-dot" id="notifDot" style="display:none;"></span></a>
                <button class="topbar-btn" onclick="window.location.href='civictrack-dashboard.php'">＋ Report Issue</button>
            </div>
        </div>
        <div class="page-content">
            <div class="lb-card">
                <div class="lb-header">
                    <span style="font-size:24px;">🏆</span>
                    <div>
                        <div style="font-weight:700;font-size:16px;">Civic Champions</div>
                        <div style="font-size:12px;color:#6b7280;">Points: 50 per resolved report · 10 per open report</div>
                    </div>
                </div>
                <div id="lbList"><div class="empty-state">⏳ Loading leaderboard…</div></div>
            </div>
        </div>
    </main>
    <script>
        const userName   = sessionStorage.getItem('ct_name')    || 'Resident';
        const userWard   = sessionStorage.getItem('ct_ward')    || 'Ward 42, Mumbai';
        const userCity   = sessionStorage.getItem('ct_city')    || 'Mumbai';
        const currentId  = parseInt(sessionStorage.getItem('ct_user_id') || '0');
        document.getElementById('sidebarName').textContent   = userName;
        document.getElementById('sidebarWard').textContent   = `${userWard}, ${userCity}`;
        document.getElementById('sidebarAvatar').textContent = userName.charAt(0).toUpperCase();

        const AVATAR_COLORS = ['#1a73e8','#2D6A4F','#7c3aed','#e67e22','#C0392B','#0e7490'];
        const MEDALS = ['🥇','🥈','🥉'];

        fetch('../api/issues.php?action=fetchLeaderboard')
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('lbList');
                if (!data.success || data.leaders.length === 0) {
                    el.innerHTML = '<div class="empty-state">No data yet. Be the first to report an issue!</div>';
                    return;
                }
                el.innerHTML = data.leaders.map((u, idx) => {
                    const isMe = currentId > 0 && parseInt(u.id) === currentId;
                    const color = AVATAR_COLORS[idx % AVATAR_COLORS.length];
                    const initials = u.full_name.split(' ').map(w => w[0]).join('').slice(0,2);
                    const medal = MEDALS[idx] || `${idx + 1}`;
                    return `
                    <div class="lb-item ${isMe ? 'me' : ''}">
                        <div class="rank ${idx === 0 ? 'medal-1' : idx === 1 ? 'medal-2' : idx === 2 ? 'medal-3' : ''}">${medal}</div>
                        <div class="lb-avatar" style="background:${color};">${initials}</div>
                        <div class="lb-info">
                            <div class="lb-name">${isMe ? `<strong>${u.full_name} (You)</strong>` : u.full_name}</div>
                            <div class="lb-ward">${u.ward_locality || '—'}</div>
                        </div>
                        <div class="lb-reports">${u.report_count} reports</div>
                        <div class="lb-pts">${parseInt(u.points).toLocaleString()} pts</div>
                    </div>`;
                }).join('');
            });

        // Load notification badge
        fetch('../api/issues.php?action=fetchNotifications')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.unread_count > 0) {
                    document.getElementById('notifBadge').textContent = data.unread_count;
                    document.getElementById('notifDot').style.display = '';
                }
            }).catch(() => {});

        // Load my reports badge
        fetch('../api/issues.php?action=fetchIssues&filter=mine')
            .then(r => r.json())
            .then(data => {
                if (data.success) document.getElementById('myReportsBadge').textContent = data.issues.length;
            }).catch(() => {});
    </script>
</body>
</html>
