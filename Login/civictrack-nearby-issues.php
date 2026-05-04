<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – Nearby Issues</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="civictrack-dashboard.css">
    <style>
        .page-content { padding: 28px; }
        .filters-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
        .filter-btn { padding:7px 16px; border-radius:20px; border:1.5px solid var(--border); background:#fff; font-family:Poppins,sans-serif; font-size:13px; cursor:pointer; transition:all .2s; }
        .filter-btn.active, .filter-btn:hover { background:var(--primary); color:#fff; border-color:var(--primary); }
        .issues-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(310px,1fr)); gap:16px; }
        .ni-card { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); padding:18px 20px; border-left:4px solid #e5e7eb; transition:transform .2s,box-shadow .2s; }
        .ni-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.12); }
        .ni-card.status-open     { border-left-color:#e67e22; }
        .ni-card.status-progress { border-left-color:#1a73e8; }
        .ni-card.status-resolved { border-left-color:#2D6A4F; }
        .ni-top { display:flex; align-items:flex-start; gap:12px; margin-bottom:12px; }
        .ni-emoji { font-size:26px; flex-shrink:0; }
        .ni-title { font-weight:600; font-size:14px; line-height:1.4; }
        .ni-location { font-size:12px; color:#6b7280; margin-top:2px; }
        .ni-meta { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .ni-badge { font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; }
        .b-open     { background:#fef3e2; color:#b45309; }
        .b-progress { background:#e8f0fe; color:#1a73e8; }
        .b-resolved { background:#e6f4ea; color:#2D6A4F; }
        .b-priority-urgent { background:#fce8e6; color:#C0392B; }
        .b-priority-high   { background:#fff3e0; color:#e67e22; }
        .ni-reporter { font-size:11px; color:#9ca3af; }
        .ni-date { font-size:11px; color:#9ca3af; margin-left:auto; }
        .search-bar { width:100%; padding:10px 16px; border:1.5px solid var(--border); border-radius:8px; font-family:Poppins,sans-serif; font-size:13px; margin-bottom:14px; outline:none; }
        .search-bar:focus { border-color:var(--primary); }
        .empty-state { text-align:center; padding:60px; color:#9ca3af; grid-column:1/-1; }
        .results-count { font-size:13px; color:#6b7280; margin-bottom:12px; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo"><img src="../Login/logo.jpg" alt="CivicTrack"></div>
        <div class="nav-section-label">Main</div>
        <a href="civictrack-dashboard.php" class="nav-item"><span class="nav-icon">🏠</span><span>Dashboard</span></a>
        <a href="civictrack-my-reports.php" class="nav-item"><span class="nav-icon">📋</span><span>My Reports</span><span class="nav-badge" id="myReportsBadge">0</span></a>
        <a href="civictrack-nearby-issues.php" class="nav-item active"><span class="nav-icon">🗺️</span><span>Nearby Issues</span></a>
        <a href="civictrack-notifications.php" class="nav-item"><span class="nav-icon">🔔</span><span>Notifications</span><span class="nav-badge" id="notifBadge">0</span></a>
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
            <div class="topbar-title">Nearby Issues</div>
            <div class="topbar-right">
                <a href="civictrack-notifications.php" class="notif-bell">🔔<span class="notif-dot" id="notifDot" style="display:none;"></span></a>
                <button class="topbar-btn" onclick="window.location.href='civictrack-dashboard.php'">＋ Report Issue</button>
            </div>
        </div>
        <div class="page-content">
            <input type="text" class="search-bar" id="searchInput" placeholder="🔍 Search by type, location or ward…" oninput="renderIssues()">
            <div class="filters-bar">
                <button class="filter-btn active" onclick="setFilter('all',this)" id="f-all">All</button>
                <button class="filter-btn" onclick="setFilter('Open',this)">🟠 Open</button>
                <button class="filter-btn" onclick="setFilter('In Progress',this)">🔵 In Progress</button>
                <button class="filter-btn" onclick="setFilter('Resolved',this)">🟢 Resolved</button>
            </div>
            <div class="results-count" id="resultsCount"></div>
            <div class="issues-grid" id="issuesGrid"><div class="empty-state">⏳ Loading issues…</div></div>
        </div>
    </main>
    <script>
        const userName = sessionStorage.getItem('ct_name') || 'Resident';
        const userWard = sessionStorage.getItem('ct_ward') || '';
        const userCity = sessionStorage.getItem('ct_city') || 'Mumbai';
        document.getElementById('sidebarName').textContent   = userName;
        document.getElementById('sidebarWard').textContent   = `${userWard}, ${userCity}`;
        document.getElementById('sidebarAvatar').textContent = userName.charAt(0).toUpperCase();

        const ICONS  = { '🕳️ Pothole':'🕳️','💡 Street Light':'💡','🗑️ Garbage':'🗑️','💧 Water Supply':'💧','🌳 Tree / Fallen Branch':'🌳','🚧 Road Damage':'🚧','Other':'📌' };
        let allIssues = [];
        let activeFilter = 'all';

        function timeAgo(d) {
            const diff = (Date.now() - new Date(d).getTime()) / 1000;
            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff/3600)}h ago`;
            return `${Math.floor(diff/86400)}d ago`;
        }

        function setFilter(f, btn) {
            activeFilter = f;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderIssues();
        }

        function renderIssues() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            const grid = document.getElementById('issuesGrid');
            let list = allIssues;

            if (activeFilter !== 'all') list = list.filter(i => i.status === activeFilter);
            if (q) list = list.filter(i =>
                (i.issue_type + i.location_text + (i.ward||'')).toLowerCase().includes(q)
            );

            document.getElementById('resultsCount').textContent = `Showing ${list.length} of ${allIssues.length} issues`;

            if (list.length === 0) {
                grid.innerHTML = '<div class="empty-state">No issues match your filter.</div>';
                return;
            }

            grid.innerHTML = list.map(i => {
                const icon = ICONS[i.issue_type] || '📌';
                const sc   = i.status === 'Open' ? 'status-open' : i.status === 'In Progress' ? 'status-progress' : 'status-resolved';
                const bc   = i.status === 'Open' ? 'b-open'    : i.status === 'In Progress' ? 'b-progress'    : 'b-resolved';
                const pc   = i.priority === 'Urgent' ? 'b-priority-urgent' : i.priority === 'High' ? 'b-priority-high' : '';
                const isMyWard = userWard && i.ward && userWard.toLowerCase().includes((i.ward||'').toLowerCase());
                return `
                <div class="ni-card ${sc}">
                    <div class="ni-top">
                        <div class="ni-emoji">${icon}</div>
                        <div style="flex:1;">
                            <div class="ni-title">${i.issue_type}</div>
                            <div class="ni-location">📍 ${i.location_text}${i.ward ? ` · ${i.ward}` : ''}${isMyWard ? ' <strong style="color:var(--primary);">★</strong>' : ''}</div>
                        </div>
                    </div>
                    <div class="ni-meta">
                        <span class="ni-badge ${bc}">${i.status}</span>
                        ${pc ? `<span class="ni-badge ${pc}">${i.priority}</span>` : ''}
                        <span class="ni-reporter">by ${i.reported_by}</span>
                        <span class="ni-date">${timeAgo(i.created_at)}</span>
                    </div>
                    ${i.description ? `<div style="font-size:12px;color:#6b7280;margin-top:10px;padding-top:10px;border-top:1px solid #f3f4f6;">"${i.description}"</div>` : ''}
                </div>`;
            }).join('');
        }

        fetch('../api/issues.php?action=fetchIssues')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    allIssues = data.issues;
                    renderIssues();
                } else {
                    document.getElementById('issuesGrid').innerHTML = '<div class="empty-state">⚠️ Could not load issues.</div>';
                }
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
