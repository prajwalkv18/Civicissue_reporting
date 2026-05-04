<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – Ward Stats</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="civictrack-dashboard.css">
    <style>
        .page-content { padding: 28px; }
        .ward-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
        .ward-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 20px 24px; }
        .ward-name { font-size: 16px; font-weight: 700; margin-bottom: 14px; }
        .ward-stats-row { display: flex; gap: 12px; margin-bottom: 14px; }
        .ws-item { flex: 1; text-align: center; background: #f9fafb; border-radius: 8px; padding: 10px 6px; }
        .ws-num { font-size: 20px; font-weight: 700; }
        .ws-label { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .progress-bar-wrap { display: flex; align-items: center; gap: 10px; }
        .progress-bar { flex: 1; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 4px; transition: width 0.6s; }
        .rate-pct { font-size: 13px; font-weight: 700; min-width: 38px; text-align: right; }
        .total-banner { background: var(--primary); color: #fff; border-radius: 14px; padding: 20px 28px; margin-bottom: 22px; display: flex; gap: 30px; flex-wrap: wrap; }
        .tb-stat { text-align: center; }
        .tb-num { font-size: 28px; font-weight: 700; }
        .tb-label { font-size: 12px; opacity: .8; }
        .empty-state { text-align: center; padding: 60px; color: #9ca3af; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo"><img src="../Login/logo.jpg" alt="CivicTrack"></div>
        <div class="nav-section-label">Main</div>
        <a href="civictrack-dashboard.php" class="nav-item"><span class="nav-icon">🏠</span><span>Dashboard</span></a>
        <a href="civictrack-my-reports.php" class="nav-item"><span class="nav-icon">📋</span><span>My Reports</span><span class="nav-badge" id="myReportsBadge">0</span></a>
        <a href="civictrack-nearby-issues.php" class="nav-item"><span class="nav-icon">🗺️</span><span>Nearby Issues</span></a>
        <a href="civictrack-notifications.php" class="nav-item"><span class="nav-icon">🔔</span><span>Notifications</span><span class="nav-badge" id="notifBadge">0</span></a>
        <div class="nav-section-label">Community</div>
        <a href="civictrack-ward-stats.php" class="nav-item active"><span class="nav-icon">📊</span><span>Ward Stats</span></a>
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
            <div class="topbar-title">Ward Stats</div>
            <div class="topbar-right">
                <a href="civictrack-notifications.php" class="notif-bell">🔔<span class="notif-dot" id="notifDot" style="display:none;"></span></a>
                <button class="topbar-btn" onclick="window.location.href='civictrack-dashboard.php'">＋ Report Issue</button>
            </div>
        </div>
        <div class="page-content">
            <div class="total-banner" id="totalBanner" style="display:none;">
                <div class="tb-stat"><div class="tb-num" id="tbTotal">0</div><div class="tb-label">Total Issues</div></div>
                <div class="tb-stat"><div class="tb-num" id="tbOpen">0</div><div class="tb-label">Open</div></div>
                <div class="tb-stat"><div class="tb-num" id="tbProgress">0</div><div class="tb-label">In Progress</div></div>
                <div class="tb-stat"><div class="tb-num" id="tbResolved">0</div><div class="tb-label">Resolved</div></div>
                <div class="tb-stat"><div class="tb-num" id="tbRate">0%</div><div class="tb-label">Resolution Rate</div></div>
            </div>
            <div class="ward-grid" id="wardGrid"><div class="empty-state">⏳ Loading ward statistics…</div></div>
        </div>
    </main>
    <script>
        const userName = sessionStorage.getItem('ct_name') || 'Resident';
        const userWard = sessionStorage.getItem('ct_ward') || 'Ward 42, Mumbai';
        const userCity = sessionStorage.getItem('ct_city') || 'Mumbai';
        document.getElementById('sidebarName').textContent   = userName;
        document.getElementById('sidebarWard').textContent   = `${userWard}, ${userCity}`;
        document.getElementById('sidebarAvatar').textContent = userName.charAt(0).toUpperCase();

        fetch('../api/issues.php?action=fetchWardStats')
            .then(r => r.json())
            .then(data => {
                const grid = document.getElementById('wardGrid');
                if (!data.success || data.wards.length === 0) {
                    grid.innerHTML = '<div class="empty-state">No ward data yet. Issues will appear here once reported with ward information.</div>';
                    return;
                }

                let totTotal = 0, totOpen = 0, totProgress = 0, totResolved = 0;
                data.wards.forEach(w => {
                    totTotal    += parseInt(w.total)        || 0;
                    totOpen     += parseInt(w.open_count)   || 0;
                    totProgress += parseInt(w.progress_count)|| 0;
                    totResolved += parseInt(w.resolved_count)|| 0;
                });
                const overallRate = totTotal > 0 ? Math.round(totResolved / totTotal * 100) : 0;

                document.getElementById('tbTotal').textContent    = totTotal;
                document.getElementById('tbOpen').textContent     = totOpen;
                document.getElementById('tbProgress').textContent = totProgress;
                document.getElementById('tbResolved').textContent = totResolved;
                document.getElementById('tbRate').textContent     = overallRate + '%';
                document.getElementById('totalBanner').style.display = 'flex';

                grid.innerHTML = data.wards.map(w => {
                    const rate  = parseInt(w.resolution_rate) || 0;
                    const color = rate >= 75 ? '#2D6A4F' : rate >= 50 ? '#e67e22' : '#C0392B';
                    const isMyWard = userWard && w.ward && userWard.toLowerCase().includes(w.ward.toLowerCase());
                    return `
                    <div class="ward-card" style="${isMyWard ? 'border:2px solid var(--primary);' : ''}">
                        <div class="ward-name">${isMyWard ? '📍 ' : ''}${w.ward}${isMyWard ? ' <small style="color:var(--primary);font-size:11px;">(Your ward)</small>' : ''}</div>
                        <div class="ward-stats-row">
                            <div class="ws-item"><div class="ws-num" style="color:#e67e22;">${w.open_count}</div><div class="ws-label">Open</div></div>
                            <div class="ws-item"><div class="ws-num" style="color:#1a73e8;">${w.progress_count}</div><div class="ws-label">In Progress</div></div>
                            <div class="ws-item"><div class="ws-num" style="color:#2D6A4F;">${w.resolved_count}</div><div class="ws-label">Resolved</div></div>
                            <div class="ws-item"><div class="ws-num">${w.total}</div><div class="ws-label">Total</div></div>
                        </div>
                        <div class="progress-bar-wrap">
                            <span style="font-size:12px;color:#6b7280;min-width:80px;">Resolution</span>
                            <div class="progress-bar"><div class="progress-fill" style="width:${rate}%;background:${color};"></div></div>
                            <span class="rate-pct" style="color:${color};">${rate}%</span>
                        </div>
                    </div>`;
                }).join('');
            });

        fetch('../api/issues.php?action=fetchNotifications')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.unread_count > 0) {
                    document.getElementById('notifBadge').textContent = data.unread_count;
                    document.getElementById('notifDot').style.display = '';
                }
            }).catch(() => {});

        fetch('../api/issues.php?action=fetchIssues&filter=mine')
            .then(r => r.json())
            .then(data => {
                if (data.success) document.getElementById('myReportsBadge').textContent = data.issues.length;
            }).catch(() => {});
    </script>
</body>
</html>
