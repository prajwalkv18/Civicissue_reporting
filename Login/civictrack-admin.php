<!DOCTYPE html>
<html lang="en">
<head>
<script>
(function(){
    var role = sessionStorage.getItem('ct_role');
    var id   = sessionStorage.getItem('ct_user_id');
    if (role !== 'admin' || !id) {
        window.location.replace('civictrack_login.php?msg=admin_only');
    }
})();
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – Admin Panel</title>
    <meta name="description" content="CivicTrack admin dashboard to manage civic issue reports, users, wards and analytics.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="civictrack-admin.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap&libraries=marker" async defer></script>
    <style>
        .exit-admin-link {
            margin: 8px 12px 4px;
            border-radius: 10px;
            background: linear-gradient(135deg, #e8f5e9, #f0fdf4);
            border: 1.5px solid #a7d7a0;
            color: #2D6A4F !important;
            font-weight: 600;
            transition: background .2s, box-shadow .2s;
        }
        .exit-admin-link:hover {
            background: linear-gradient(135deg, #c8e6c9, #dcf5e0);
            box-shadow: 0 2px 8px rgba(45,106,79,.18);
        }
        
        #tab-map { margin: -28px; }
        .map-shell {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 65px);
        }
        .map-topbar {
            background: #fff;
            padding: 12px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            z-index: 10;
        }
        .map-topbar h3 { font-size:15px; font-weight:700; color:#304a02; margin-right:auto; }
        #mapContainer {
            flex: 1;
            position: relative;
        }
        #gmap { width:100%; height:100%; }
        
        .map-float {
            position: absolute;
            z-index: 5;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.14);
            font-family: 'Poppins', sans-serif;
        }
        .map-stats-panel {
            top: 14px; left: 14px;
            padding: 14px 18px;
            min-width: 200px;
        }
        .map-stats-panel .sp-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6b7280; margin-bottom:10px; }
        .map-stat-row { display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px solid #f0f0f0; font-size:12px; }
        .map-stat-row:last-child { border:none; }
        .map-stat-val { font-weight:700; font-size:13px; }
        .map-legend {
            bottom: 28px; left: 14px;
            padding: 12px 16px;
        }
        .map-legend .sp-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6b7280; margin-bottom:8px; }
        .legend-row { display:flex; align-items:center; gap:8px; font-size:11px; font-weight:500; margin-bottom:5px; }
        .legend-row:last-child { margin:0; }
        .legend-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; border:2px solid rgba(0,0,0,0.15); }
        .map-key-panel {
            top: 14px; right: 14px;
            padding: 0;
            overflow: hidden;
            min-width: 260px;
            max-height: calc(100% - 28px);
            overflow-y: auto;
        }
        .map-key-header { padding:12px 16px; background:#f8f9fb; border-bottom:1px solid #e5e7eb; }
        .map-key-header h4 { font-size:13px; font-weight:700; }
        .map-issue-row {
            display: flex; align-items:center; gap:10px;
            padding: 10px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background .15s;
            font-size: 12px;
        }
        .map-issue-row:hover { background: #f4f9f0; }
        .map-issue-row:last-child { border:none; }
        .mir-emoji { font-size:18px; }
        .mir-title { font-weight:600; color:#1a1a1a; }
        .mir-sub { color:#6b7280; font-size:11px; }
        
        #noKeyBanner {
            display:none;
            position:absolute; inset:0;
            background:linear-gradient(135deg,#e0e7ff 0%,#f0fdf4 100%);
            flex-direction:column;
            align-items:center; justify-content:center;
            gap:12px; z-index:20; text-align:center; padding:20px;
        }
        #noKeyBanner.visible { display:flex; }
        #noKeyBanner .nb-icon { font-size:52px; }
        #noKeyBanner h2 { font-size:18px; font-weight:700; color:#304a02; }
        #noKeyBanner p { font-size:13px; color:#6b7280; max-width:400px; line-height:1.6; }
        #noKeyBanner code { background:#1e2d0e; color:#a3d977; padding:3px 8px; border-radius:5px; font-size:12px; }
        #noKeyBanner a { color:#1a73e8; font-weight:600; }
    </style>
</head>
<body>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <img src="logo.jpg" alt="CivicTrack">
        <span class="admin-badge">Admin</span>
    </div>

    <div class="sidebar-section">Overview</div>
    <a class="sidebar-link active" data-tab="tab-overview" onclick="switchTab(this,'tab-overview')">
        <span class="s-icon">📊</span><span>Dashboard</span>
    </a>
    <a class="sidebar-link" data-tab="tab-issues" onclick="switchTab(this,'tab-issues')">
        <span class="s-icon">📋</span><span>Issue Requests</span>
        <span class="s-badge" id="pendingBadge">7</span>
    </a>
    <a class="sidebar-link" data-tab="tab-map" onclick="switchTab(this,'tab-map')">
        <span class="s-icon">🗺️</span><span>Live Map</span>
    </a>

    <div class="sidebar-section">Management</div>
    <a class="sidebar-link" data-tab="tab-users" onclick="switchTab(this,'tab-users')">
        <span class="s-icon">👥</span><span>Users</span>
    </a>
    <a class="sidebar-link" data-tab="tab-engineers" onclick="switchTab(this,'tab-engineers')">
        <span class="s-icon">🔧</span><span>Field Engineers</span>
    </a>
    <a class="sidebar-link" data-tab="tab-wards" onclick="switchTab(this,'tab-wards')">
        <span class="s-icon">🏘️</span><span>Ward Management</span>
    </a>

    <div class="sidebar-section">Reporting</div>
    <a class="sidebar-link" data-tab="tab-analytics" onclick="switchTab(this,'tab-analytics')">
        <span class="s-icon">📈</span><span>Analytics</span>
    </a>
    <a class="sidebar-link" data-tab="tab-alerts" onclick="switchTab(this,'tab-alerts')">
        <span class="s-icon">🔔</span><span>System Alerts</span>
        <span class="s-badge">3</span>
    </a>
    <a class="sidebar-link" data-tab="tab-settings" onclick="switchTab(this,'tab-settings')">
        <span class="s-icon">⚙️</span><span>Settings</span>
    </a>

    <a class="sidebar-link exit-admin-link" href="civictrack-dashboard.php" id="exitAdminBtn">
        <span class="s-icon">🏠</span><span>Exit to Dashboard</span>
    </a>

    <div class="sidebar-footer">
        <div class="admin-avatar" id="adminAvatar">A</div>
        <div>
            <div class="admin-name" id="adminNameDisplay">Admin</div>
            <div class="admin-role">Super Administrator</div>
        </div>
        <button class="logout-btn" onclick="logout()">Sign out</button>
    </div>
</aside>

<main class="admin-main">

    
    <div class="admin-topbar">
        <div class="topbar-left">
            <h1 id="pageTitle">Dashboard Overview</h1>
            <p id="pageSubtitle">Welcome back, Admin · Last updated: <span id="lastUpdated"></span></p>
        </div>
        <div class="topbar-controls">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" id="globalSearch" placeholder="Search issues, users…" oninput="globalSearchFn(this.value)">
            </div>
            <button class="icon-btn" onclick="exportCSV()" title="Export CSV">📤</button>
            <button class="icon-btn" title="Notifications">
                🔔<span class="dot"></span>
            </button>
            <a href="civictrack-dashboard.php" class="outline-btn" id="topbarExitBtn" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">🏠 Dashboard</a>
            <button class="primary-btn" onclick="openAssignModal()">＋ Add Issue</button>
        </div>
    </div>

    
    <div class="admin-body">

        
        <div class="tab-bar">
            <button class="tab-btn active" data-tab="tab-overview"   onclick="switchTab(this,'tab-overview')">📊 Overview</button>
            <button class="tab-btn"        data-tab="tab-issues"     onclick="switchTab(this,'tab-issues')">📋 Requests <span class="tb" id="reqCount">7</span></button>
            <button class="tab-btn"        data-tab="tab-map"        onclick="switchTab(this,'tab-map')">🗺️ Live Map</button>
            <button class="tab-btn"        data-tab="tab-users"      onclick="switchTab(this,'tab-users')">👥 Users</button>
            <button class="tab-btn"        data-tab="tab-engineers"  onclick="switchTab(this,'tab-engineers')">🔧 Engineers</button>
            <button class="tab-btn"        data-tab="tab-wards"      onclick="switchTab(this,'tab-wards')">🏘️ Wards</button>
            <button class="tab-btn"        data-tab="tab-analytics"  onclick="switchTab(this,'tab-analytics')">📈 Analytics</button>
            <button class="tab-btn"        data-tab="tab-alerts"     onclick="switchTab(this,'tab-alerts')">🔔 Alerts</button>
            <button class="tab-btn"        data-tab="tab-settings"   onclick="switchTab(this,'tab-settings')">⚙️ Settings</button>
        </div>

        
        <div class="tab-content active" id="tab-overview">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon-box si-blue">📋</div>
                    <div>
                        <div class="s-num" id="s-total">127</div>
                        <div class="s-label">Total Reports</div>
                        <div class="s-delta up" id="s-total-d">↑ 12 this week</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-orange">⏳</div>
                    <div>
                        <div class="s-num" id="s-pending">34</div>
                        <div class="s-label">Pending Review</div>
                        <div class="s-delta down" id="s-pending-d">↑ 5 new today</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-purple">🔧</div>
                    <div>
                        <div class="s-num" id="s-progress">58</div>
                        <div class="s-label">In Progress</div>
                        <div class="s-delta up">↑ assigned today: 8</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-green">✅</div>
                    <div>
                        <div class="s-num" id="s-resolved">35</div>
                        <div class="s-label">Resolved</div>
                        <div class="s-delta up">↑ 6 this week</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-red">⚠️</div>
                    <div>
                        <div class="s-num" id="s-overdue">7</div>
                        <div class="s-label">Overdue</div>
                        <div class="s-delta down">⚠ needs attention</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-teal">👥</div>
                    <div>
                        <div class="s-num" id="s-users">1,240</div>
                        <div class="s-label">Registered Users</div>
                        <div class="s-delta up">↑ 40 this month</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-blue">🔧</div>
                    <div>
                        <div class="s-num">18</div>
                        <div class="s-label">Field Engineers</div>
                        <div class="s-delta up">12 active now</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-orange">⭐</div>
                    <div>
                        <div class="s-num">4.2</div>
                        <div class="s-label">Avg. Satisfaction</div>
                        <div class="s-delta up">↑ from 3.9</div>
                    </div>
                </div>
            </div>

            
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Issues Reported – Last 7 Days</h3>
                        <span style="font-size:12px;color:var(--text-muted);">Daily count</span>
                    </div>
                    <div class="chart-body"><canvas id="chartWeekly"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Issues by Category</h3>
                    </div>
                    <div class="chart-body"><canvas id="chartCategory"></canvas></div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h3>Recent Activity</h3>
                    <span style="font-size:12px;color:var(--text-muted);">Live feed</span>
                </div>
                <div class="timeline" id="activityFeed">
                    
                </div>
            </div>
        </div>

        
        <div class="tab-content" id="tab-issues">
            <div class="card">
                <div class="card-header">
                    <h3>All Issue Requests</h3>
                    <div class="card-tools">
                        <select class="filter-select" id="statusFilter" onchange="renderIssueTable()">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <select class="filter-select" id="typeFilter" onchange="renderIssueTable()">
                            <option value="all">All Types</option>
                            <option value="Pothole">Pothole</option>
                            <option value="Street Light">Street Light</option>
                            <option value="Garbage">Garbage</option>
                            <option value="Water Supply">Water Supply</option>
                            <option value="Road Damage">Road Damage</option>
                            <option value="Tree">Tree</option>
                        </select>
                        <select class="filter-select" id="priorityFilter" onchange="renderIssueTable()">
                            <option value="all">All Priority</option>
                            <option value="Urgent">Urgent</option>
                            <option value="High">High</option>
                            <option value="Normal">Normal</option>
                        </select>
                        <button class="outline-btn" onclick="exportCSV()">📤 Export</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="issueTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Issue</th>
                                <th>Reporter</th>
                                <th>Location</th>
                                <th>Ward</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="issueTableBody"></tbody>
                    </table>
                </div>
                <div id="issueTableEmpty" class="empty-state" style="display:none;">
                    <div class="e-icon">📭</div>
                    <p>No issues match the current filters.</p>
                </div>
            </div>
        </div>

        
        <div class="tab-content" id="tab-map">
            <div class="map-shell">

                
                <div class="map-topbar">
                    <h3>🗺️ Live Issue Map</h3>

                    <select class="filter-select" id="mapStatusFilter" onchange="filterMapMarkers()">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <select class="filter-select" id="mapTypeFilter" onchange="filterMapMarkers()">
                        <option value="all">All Types</option>
                        <option value="Pothole">Pothole</option>
                        <option value="Street Light">Street Light</option>
                        <option value="Garbage">Garbage</option>
                        <option value="Water Supply">Water Supply</option>
                        <option value="Road Damage">Road Damage</option>
                        <option value="Tree">Tree</option>
                    </select>

                    <button class="outline-btn" onclick="centerMap()">🎯 Re-center</button>
                    <button class="outline-btn" onclick="refreshMapMarkers()">🔄 Refresh</button>
                    <button class="primary-btn" onclick="exportCSV()">📤 Export</button>
                </div>

                
                <div id="mapContainer">

                    
                    <div id="gmap"></div>

                    
                    <div id="noKeyBanner">
                        <div class="nb-icon">🗺️</div>
                        <h2>Google Maps API Key Required</h2>
                        <p>
                            Open <strong>civictrack-admin.php</strong> and replace
                            <code>YOUR_GOOGLE_MAPS_API_KEY</code> in the
                            <code>&lt;script&gt;</code> tag with your real key.
                            <br><br>
                            <a href="https://console.cloud.google.com/google/maps-apis" target="_blank">→ Get a free API key at Google Cloud Console</a>
                        </p>
                        <p style="font-size:11px;background:#1e2d0e;color:#a3d977;padding:10px 16px;border-radius:8px;font-family:monospace;line-height:1.8;">
                            Enable: <strong>Maps JavaScript API</strong><br>
                            Restrict: HTTP referrers (your domain)
                        </p>
                    </div>

                    
                    <div class="map-float map-stats-panel" id="mapStatsPanel">
                        <div class="sp-title">📍 Map Summary</div>
                        <div class="map-stat-row">
                            <span>Visible Pins</span>
                            <span class="map-stat-val" id="mpVisible">10</span>
                        </div>
                        <div class="map-stat-row">
                            <span>🟠 Pending</span>
                            <span class="map-stat-val" style="color:#e67e22;" id="mpPending">0</span>
                        </div>
                        <div class="map-stat-row">
                            <span>🔵 In Progress</span>
                            <span class="map-stat-val" style="color:#1a73e8;" id="mpProgress">0</span>
                        </div>
                        <div class="map-stat-row">
                            <span>🟢 Resolved</span>
                            <span class="map-stat-val" style="color:#2D6A4F;" id="mpResolved">0</span>
                        </div>
                        <div class="map-stat-row">
                            <span>🔴 Rejected</span>
                            <span class="map-stat-val" style="color:#C0392B;" id="mpRejected">0</span>
                        </div>
                    </div>

                    
                    <div class="map-float map-legend">
                        <div class="sp-title">Legend</div>
                        <div class="legend-row"><div class="legend-dot" style="background:#e67e22;"></div>Pending</div>
                        <div class="legend-row"><div class="legend-dot" style="background:#1a73e8;"></div>In Progress</div>
                        <div class="legend-row"><div class="legend-dot" style="background:#2D6A4F;"></div>Resolved</div>
                        <div class="legend-row"><div class="legend-dot" style="background:#C0392B;"></div>Rejected</div>
                    </div>

                    
                    <div class="map-float map-key-panel" id="mapSideList">
                        <div class="map-key-header"><h4>📋 Issue List</h4></div>
                        <div id="mapIssueList"></div>
                    </div>

                </div>
            </div>
        </div>

        
        <div class="tab-content" id="tab-users">
            <div class="card">
                <div class="card-header">
                    <h3>Registered Users</h3>
                    <div class="card-tools">
                        <select class="filter-select" id="roleFilter" onchange="renderUsers()">
                            <option value="all">All Roles</option>
                            <option value="User">User</option>
                            <option value="Admin">Admin</option>
                        </select>
                        <button class="primary-btn" onclick="toast('User invite sent!')">＋ Invite User</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Phone</th>
                                <th>Ward</th>
                                <th>Reports</th>
                                <th>Points</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="tab-content" id="tab-engineers">
            <div class="card">
                <div class="card-header">
                    <h3>Field Engineers</h3>
                    <button class="primary-btn" onclick="openAddEngModal()">＋ Add Engineer</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Engineer</th>
                                <th>ID</th>
                                <th>Ward Assigned</th>
                                <th>Active Jobs</th>
                                <th>Resolved</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="engTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="tab-content" id="tab-wards">
            <div class="card">
                <div class="card-header">
                    <h3>Ward Management</h3>
                    <button class="primary-btn" onclick="toast('Add Ward form coming soon!')">＋ Add Ward</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Ward</th>
                                <th>Zone</th>
                                <th>Open Issues</th>
                                <th>In Progress</th>
                                <th>Resolved</th>
                                <th>Assigned Engineer</th>
                                <th>Health</th>
                            </tr>
                        </thead>
                        <tbody id="wardTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="tab-content" id="tab-analytics">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon-box si-green">⚡</div>
                    <div><div class="s-num">2.4d</div><div class="s-label">Avg. Resolution Time</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-blue">📣</div>
                    <div><div class="s-num">89%</div><div class="s-label">Citizen Satisfaction</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-orange">📍</div>
                    <div><div class="s-num">63%</div><div class="s-label">Geotagged Reports</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box si-red">🔁</div>
                    <div><div class="s-num">5.6%</div><div class="s-label">Re-open Rate</div></div>
                </div>
            </div>
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header"><h3>Monthly Trend (6 months)</h3></div>
                    <div class="chart-body"><canvas id="chartMonthly"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="chart-header"><h3>Resolution Rate by Category</h3></div>
                    <div class="chart-body"><canvas id="chartResolution"></canvas></div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-header"><h3>Ward-wise Issue Heatmap</h3></div>
                <div class="chart-body"><canvas id="chartWard" style="max-height:280px;"></canvas></div>
            </div>
        </div>

        
        <div class="tab-content" id="tab-alerts">
            <div class="card">
                <div class="card-header">
                    <h3>System Alerts &amp; Notifications</h3>
                    <button class="outline-btn" onclick="markAllRead()">✅ Mark All Read</button>
                </div>
                <div id="alertList">
                    
                </div>
            </div>
        </div>

        
        <div class="tab-content" id="tab-settings">
            <div class="settings-grid">
                <div class="card">
                    <div class="card-header"><h3>Platform Settings</h3></div>
                    <div style="padding:0 20px 10px;">
                        <div class="settings-row">
                            <div><div class="settings-label">Auto-assign to Engineer</div><div class="settings-desc">Automatically assign new issues to available engineers</div></div>
                            <label class="toggle"><input type="checkbox" checked id="st-autoassign"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="settings-row">
                            <div><div class="settings-label">Email Notifications</div><div class="settings-desc">Send status updates via email to reporters</div></div>
                            <label class="toggle"><input type="checkbox" checked id="st-email"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="settings-row">
                            <div><div class="settings-label">SMS Notifications</div><div class="settings-desc">Send OTP and status SMS to users</div></div>
                            <label class="toggle"><input type="checkbox" id="st-sms"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="settings-row">
                            <div><div class="settings-label">Public Issue Map</div><div class="settings-desc">Allow citizens to view the live issue map</div></div>
                            <label class="toggle"><input type="checkbox" checked id="st-pubmap"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="settings-row">
                            <div><div class="settings-label">Geotag Enforcement</div><div class="settings-desc">Require GPS location on every report</div></div>
                            <label class="toggle"><input type="checkbox" id="st-geotag"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="settings-row">
                            <div><div class="settings-label">Maintenance Mode</div><div class="settings-desc">Take the platform offline for maintenance</div></div>
                            <label class="toggle"><input type="checkbox" id="st-maint"><span class="toggle-slider"></span></label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Admin Profile</h3></div>
                    <div style="padding:20px;">
                        <div class="form-group">
                            <label class="form-label">Admin Name</label>
                            <input class="form-input" id="set-adminName" value="Super Admin" placeholder="Your name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input class="form-input" type="email" value="admin@civictrack.in" placeholder="Email">
                        </div>
                        <div class="form-group">
                            <label class="form-label">City / Municipality</label>
                            <input class="form-input" value="Mumbai Municipal Corporation" placeholder="City">
                        </div>
                        <div class="form-group">
                            <label class="form-label">SLA (hours to resolve)</label>
                            <input class="form-input" type="number" value="72" min="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input class="form-input" type="password" placeholder="Leave blank to keep current">
                        </div>
                        <button class="primary-btn" style="width:100%;justify-content:center;padding:11px;" onclick="saveSettings()">💾 Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<div class="modal-overlay" id="assignModal">
    <div class="modal">
        <div class="modal-head">
            <h3 id="modalTitle">Assign Issue</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-content">
            <div class="form-group">
                <label class="form-label">Issue ID</label>
                <input class="form-input" id="m-id" readonly style="background:#f0f2f5;">
            </div>
            <div class="form-group">
                <label class="form-label">Issue Title</label>
                <input class="form-input" id="m-title" readonly style="background:#f0f2f5;">
            </div>
            <div class="form-group">
                <label class="form-label">Assign to Engineer</label>
                <select class="form-select" id="m-engineer">
                    <option value="">— Select engineer —</option>
                    <option>Ravi Kumar (Ward 42)</option>
                    <option>Priya Sharma (Ward 42)</option>
                    <option>Deepak Nair (Ward 41)</option>
                    <option>Anjali Verma (Ward 43)</option>
                    <option>Suresh Menon (Ward 42)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Update Status</label>
                <select class="form-select" id="m-status">
                    <option value="pending">Pending</option>
                    <option value="progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Admin Note</label>
                <textarea class="form-textarea" id="m-note" placeholder="Optional note to engineer…"></textarea>
            </div>
            <div class="modal-actions">
                <button class="btn-full btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-full btn-confirm" onclick="saveAssignment()">✅ Save Assignment</button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="engModal">
    <div class="modal">
        <div class="modal-head">
            <h3>Add Field Engineer</h3>
            <button class="modal-close" onclick="closeEngModal()">✕</button>
        </div>
        <div class="modal-content">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input class="form-input" id="eng-name" placeholder="Engineer name">
            </div>
            <div class="form-group">
                <label class="form-label">Employee ID</label>
                <input class="form-input" id="eng-id" placeholder="e.g. ENG-019">
            </div>
            <div class="form-group">
                <label class="form-label">Assign Ward</label>
                <select class="form-select" id="eng-ward">
                    <option>Ward 41</option><option>Ward 42</option><option>Ward 43</option>
                    <option>Ward 44</option><option>Ward 45</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input class="form-input" id="eng-phone" placeholder="+91 9XXXXXXXXX">
            </div>
            <div class="modal-actions">
                <button class="btn-full btn-cancel" onclick="closeEngModal()">Cancel</button>
                <button class="btn-full btn-confirm" onclick="addEngineer()">Add Engineer</button>
            </div>
        </div>
    </div>
</div>

<div class="admin-toast" id="adminToast"></div>

<script src="civictrack-admin.js"></script>
</body>
</html>
