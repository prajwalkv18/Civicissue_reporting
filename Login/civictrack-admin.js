let activeIssueId = null;
let engineerList  = [];
let issueList     = [];
let userList      = [];
let filteredIssues = [];

const WARDS = [];
const ALERTS = [];
const ACTIVITY = [];

// Admin user ID from sessionStorage — sent with every admin API request for auth
function adminUid() { return sessionStorage.getItem('ct_user_id') || 0; }
function adminApi(action) { return `../api/admin.php?action=${action}&uid=${adminUid()}`; }
function adminPost(fd) { fd.append('uid', adminUid()); return fd; }

document.addEventListener('DOMContentLoaded', () => {
    setTimestamp();

    // Verify admin auth before doing anything
    const uid = adminUid();
    if (!uid || uid === '0' || uid === 0) {
        showAuthBanner('No admin session found.');
        return;
    }

    // Quick ping to verify the uid is actually an admin in DB
    fetch(adminApi('fetchStats'))
        .then(r => r.json())
        .then(data => {
            if (!data.success && data.message && data.message.includes('Unauthorized')) {
                showAuthBanner('Your session is not an admin account.');
            } else {
                fetchAdminData();
            }
        })
        .catch(() => fetchAdminData());
});

function showAuthBanner(reason) {
    document.body.insertAdjacentHTML('afterbegin', `
        <div id="authBanner" style="position:fixed;top:0;left:0;right:0;z-index:99999;background:#C0392B;color:#fff;padding:16px 24px;display:flex;align-items:center;gap:16px;font-family:Poppins,sans-serif;font-size:14px;">
            <span style="font-size:20px;">⚠️</span>
            <div style="flex:1;"><strong>Admin authentication required.</strong> ${reason} All actions will fail until you log in properly.</div>
            <a href="admin_login.php" style="background:#fff;color:#C0392B;padding:8px 18px;border-radius:6px;font-weight:700;text-decoration:none;white-space:nowrap;">🔐 Login as Admin →</a>
            <button onclick="document.getElementById('authBanner').remove();fetchAdminData();" style="background:rgba(255,255,255,0.2);border:none;color:#fff;padding:8px 14px;border-radius:6px;cursor:pointer;">Try Anyway</button>
        </div>
    `);
    fetchAdminData(); // Still load data (may work via PHP session)
}

async function fetchAdminData() {
    try {
        // 1. Fetch Stats
        const statsRes = await fetch(adminApi('fetchStats'));
        const statsData = await statsRes.json();
        if (statsData.success) {
            updateDashboardCounters(statsData.stats);
        }

        // 2. Fetch Issues
        const issuesRes = await fetch('../api/issues.php?action=fetchIssues');
        const issuesData = await issuesRes.json();
        if (issuesData.success) {
            issueList = issuesData.issues.map(i => ({
                id: '#' + i.id.toString().padStart(3, '0'),
                realId: i.id,
                type: i.issue_type,
                emoji: getEmoji(i.issue_type),
                reporter: i.reported_by || 'Unknown',
                location: i.location_text,
                ward: i.ward || '—',
                priority: i.priority,
                status: i.status === 'Open'
                    ? 'pending'
                    : i.status === 'In Progress'
                        ? 'progress'
                        : i.status === 'Rejected'
                            ? 'rejected'
                            : 'resolved',
                date: new Date(i.created_at).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }),
                icon_bg: '#fef3e2',
                lat: parseFloat(i.latitude),
                lng: parseFloat(i.longitude),
                photoPath: i.photo_path || '',
                rejectionRemark: i.rejection_remark || ''
            }));
            renderIssueTable();
            renderActivityFeed();
        }

        // 3. Fetch Users
        const usersRes = await fetch(adminApi('fetchUsers'));
        const usersData = await usersRes.json();
        if (usersData.success) {
            userList = usersData.users.map(u => ({
                name: u.full_name,
                initials: u.full_name.split(' ').map(w => w[0]).join('').slice(0, 2),
                color: 'ub-blue',
                phone: u.phone,
                ward: u.ward_locality || '-',
                reports: 0, // Placeholder or fetch separately
                points: 0,
                role: u.role.charAt(0).toUpperCase() + u.role.slice(1),
                joined: new Date(u.created_at).toLocaleDateString('en-IN', { month:'short', year:'numeric' })
            }));
            renderUsers();
        }

        // 4. Fetch Engineers
        const engRes = await fetch(adminApi('fetchEngineers'));
        const engData = await engRes.json();
        if (engData.success) {
            engineerList = engData.engineers.map(e => ({
                name: e.full_name,
                id: e.employee_id,
                dbId: e.id,
                ward: e.assigned_ward,
                active: parseInt(e.active_issues) || 0,
                resolved: parseInt(e.resolved_issues) || 0,
                status: e.status.charAt(0).toUpperCase() + e.status.slice(1)
            }));
            renderEngineers();
        }

        // 5. Fetch Activity Log
        const actRes = await fetch(adminApi('fetchActivityLog'));
        const actData = await actRes.json();
        if (actData.success) {
            actData.logs.forEach(log => {
                ACTIVITY.push({
                    color: log.action_description.includes('Resolved') ? 'td-green' :
                           log.action_description.includes('Urgent')  ? 'td-red'   : 'td-blue',
                    text: `<strong>#${log.issue_id}</strong> ${log.issue_type} — ${log.action_description}`,
                    time: new Date(log.created_at).toLocaleString('en-IN', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' })
                });
            });
            renderActivityFeed();
        }

        buildCharts();

        // Start 30-second auto-refresh so engineer updates appear without manual reload
        if (!fetchAdminData._refreshStarted) {
            fetchAdminData._refreshStarted = true;
            setInterval(refreshAdminStats, 30000);
        }
    } catch (error) {
        console.error('Error fetching admin data:', error);
    }
}

/**
 * Lightweight auto-refresh: re-fetches stats, issues list, and activity log
 * without reloading users/engineers or duplicating activity entries.
 * Called every 30 s so engineer status changes appear in real time.
 */
async function refreshAdminStats() {
    try {
        // Refresh stats counters
        const statsData = await (await fetch(adminApi('fetchStats'))).json();
        if (statsData.success) updateDashboardCounters(statsData.stats);

        // Refresh issue list (picks up engineer status changes)
        const issuesData = await (await fetch('../api/issues.php?action=fetchIssues')).json();
        if (issuesData.success) {
            issueList = issuesData.issues.map(i => ({
                id: '#' + i.id.toString().padStart(3, '0'),
                realId: i.id,
                type: i.issue_type,
                emoji: getEmoji(i.issue_type),
                reporter: i.reported_by || 'Unknown',
                location: i.location_text,
                ward: i.ward || '—',
                priority: i.priority,
                status: i.status === 'Open' ? 'pending'
                    : i.status === 'In Progress' ? 'progress'
                    : i.status === 'Rejected'    ? 'rejected'
                    : 'resolved',
                date: new Date(i.created_at).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }),
                icon_bg: '#fef3e2',
                lat: parseFloat(i.latitude),
                lng: parseFloat(i.longitude),
                photoPath: i.photo_path || '',
                rejectionRemark: i.rejection_remark || ''
            }));
            renderIssueTable();
        }

        // Refresh engineers table (updates active/resolved counts per engineer)
        const engData = await (await fetch(adminApi('fetchEngineers'))).json();
        if (engData.success) {
            engineerList = engData.engineers.map(e => ({
                name: e.full_name,
                id: e.employee_id,
                dbId: e.id,
                ward: e.assigned_ward,
                active: parseInt(e.active_issues) || 0,
                resolved: parseInt(e.resolved_issues) || 0,
                status: e.status.charAt(0).toUpperCase() + e.status.slice(1)
            }));
            renderEngineers();
        }

        // Refresh activity log — clear first to avoid duplicate entries
        const actData = await (await fetch(adminApi('fetchActivityLog'))).json();
        if (actData.success) {
            ACTIVITY.length = 0; // clear without breaking array reference
            actData.logs.forEach(log => {
                ACTIVITY.push({
                    color: log.action_description.includes('Resolved') ? 'td-green' :
                           log.action_description.includes('Urgent')  ? 'td-red'   : 'td-blue',
                    text: `<strong>#${log.issue_id}</strong> ${log.issue_type} — ${log.action_description}`,
                    time: new Date(log.created_at).toLocaleString('en-IN', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' })
                });
            });
            renderActivityFeed();
        }

        setTimestamp();
    } catch (e) {
        console.warn('Auto-refresh error:', e);
    }
}

function getEmoji(type) {
    const map = { 'Pothole':'🕳️', 'Street Light':'💡', 'Garbage':'🗑️', 'Water Supply':'💧', 'Road Damage':'🚧', 'Tree':'🌳' };
    return map[type] || '⚠️';
}

function updateDashboardCounters(stats) {
    document.getElementById('s-total').textContent = stats.total_issues;
    document.getElementById('s-pending').textContent = stats.pending;
    if (document.getElementById('s-progress')) document.getElementById('s-progress').textContent = stats.in_progress || 0;
    document.getElementById('s-resolved').textContent = stats.resolved;
    document.getElementById('s-users').textContent = stats.total_users;
    document.getElementById('pendingBadge').textContent = stats.pending;
    document.getElementById('reqCount').textContent = stats.pending;
}


function setTimestamp() {
    document.getElementById('lastUpdated').textContent =
        new Date().toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit' });
}

const tabTitles = {
    'tab-overview':  ['Dashboard Overview', 'Live summary of all civic activity'],
    'tab-issues':    ['Issue Requests',      'Review, accept or assign incoming reports'],
    'tab-map':       ['Live Issue Map',      'Geographic view of active civic issues'],
    'tab-users':     ['User Management',     'All registered citizens on CivicTrack'],
    'tab-engineers': ['Field Engineers',     'Manage your field workforce'],
    'tab-wards':     ['Ward Management',     'Ward health and engineer assignments'],
    'tab-analytics': ['Analytics & Reports', 'Data insights across all wards'],
    'tab-alerts':    ['System Alerts',       'Notifications and flag events'],
    'tab-settings':  ['Settings',            'Platform configuration and admin profile'],
};

function switchTab(el, tabId) {

    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn, .sidebar-link').forEach(b => b.classList.remove('active'));

    document.getElementById(tabId).classList.add('active');
    document.querySelectorAll(`[data-tab="${tabId}"]`).forEach(b => b.classList.add('active'));

    const [title, sub] = tabTitles[tabId];
    document.getElementById('pageTitle').textContent    = title;
    document.getElementById('pageSubtitle').textContent = sub;

    if (tabId === 'tab-analytics') buildAnalyticsCharts();

    if (tabId === 'tab-map') showMap();
}

function updateCounters() {
    const pending = issueList.filter(i => i.status === 'pending').length;
    document.getElementById('pendingBadge').textContent = pending;
    document.getElementById('reqCount').textContent     = pending;
    document.getElementById('s-total').textContent      = issueList.length;
    document.getElementById('s-pending').textContent    = pending;
    document.getElementById('s-progress').textContent   = issueList.filter(i => i.status === 'progress').length;
    document.getElementById('s-resolved').textContent   = issueList.filter(i => i.status === 'resolved').length;
}

function renderActivityFeed() {
    const feed = document.getElementById('activityFeed');
    feed.innerHTML = ACTIVITY.map(a => `
        <div class="t-item">
            <div class="t-dot ${a.color}"></div>
            <div>
                <div class="t-text">${a.text}</div>
                <div class="t-time">${a.time}</div>
            </div>
        </div>`).join('');
}

function renderIssueTable() {
    const sf = document.getElementById('statusFilter').value;
    const tf = document.getElementById('typeFilter').value;
    const pf = document.getElementById('priorityFilter').value;
    const q  = (document.getElementById('globalSearch').value || '').toLowerCase();

    filteredIssues = issueList.filter(i => {
        if (sf !== 'all' && i.status !== sf)          return false;
        if (tf !== 'all' && !i.type.includes(tf))     return false;
        if (pf !== 'all' && i.priority !== pf)        return false;
        if (q && !JSON.stringify(i).toLowerCase().includes(q)) return false;
        return true;
    });

    const tbody = document.getElementById('issueTableBody');
    const empty = document.getElementById('issueTableEmpty');

    if (!filteredIssues.length) {
        tbody.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    tbody.innerHTML = filteredIssues.map(i => `
        <tr id="row-${i.id.replace('#','')}">
            <td><code style="font-size:11px;color:var(--text-muted);">${i.id}</code></td>
            <td>
                <div class="td-issue">
                    <div class="issue-emoji" style="background:${i.icon_bg};">${i.emoji}</div>
                    <div>
                        <div style="font-weight:600;font-size:13px;">${i.type}</div>
                        <div style="font-size:11px;color:var(--text-muted);">${i.reporter}${i.photoPath ? ` · <a href="../${i.photoPath}" target="_blank" rel="noopener" style="color:#1a73e8;text-decoration:none;font-weight:600;">📷 Photo ↗</a>` : ''}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="user-pill">
                    <div class="user-ball ub-blue">${i.reporter.split(' ').map(w=>w[0]).join('').slice(0,2)}</div>
                    <span style="font-size:13px;">${i.reporter}</span>
                </div>
            </td>
            <td style="font-size:12px;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${i.location}</td>
            <td style="font-size:12px;">${i.ward}</td>
            <td>${priorityBadge(i.priority)}</td>
            <td>${statusBadge(i.status)}</td>
            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">${i.date}</td>
            <td>
                <div class="act-wrap">
                    ${i.status === 'pending' ? `
                    <button class="act-btn act-accept" onclick="quickStatus('${i.id}','progress')">✓ Accept</button>
                    <button class="act-btn act-reject" onclick="quickStatus('${i.id}','rejected')">✗ Reject</button>` : ''}
                    <button class="act-btn act-assign" onclick="openAssignModal('${i.id}')">🔧 Assign</button>
                    <button class="act-btn act-view"   onclick="viewIssue('${i.id}')">👁</button>
                </div>
            </td>
        </tr>`).join('');
}

function statusBadge(s) {
    const cls   = { pending:'b-pending', progress:'b-progress', resolved:'b-resolved', rejected:'b-rejected' };
    const label = { pending:'Pending',   progress:'In Progress', resolved:'Resolved',  rejected:'Rejected'   };
    const c = cls[s]   || 'b-pending';
    const l = label[s] || 'Pending';
    return `<span class="badge ${c}">${l}</span>`;
}

function priorityBadge(p) {
    const map = { Normal:'b-normal', High:'b-high', Urgent:'b-urgent' };
    const dot = { Normal:'p-normal', High:'p-high', Urgent:'p-urgent' };
    return `<span class="badge ${map[p]||'b-normal'}"><span class="p-dot ${dot[p]||'p-normal'}"></span>${p}</span>`;
}

function quickStatus(id, newStatus) {
    const issue = issueList.find(i => i.id === id);
    if (!issue) return;
    let note = '';
    if (newStatus === 'rejected') {
        note = (prompt('Enter rejection remark (required):') || '').trim();
        if (!note) {
            toast('⚠️ Rejection remark is required');
            return;
        }
    }
    const fd = new FormData();
    fd.append('action', 'updateIssue');
    fd.append('issue_id', issue.realId);
    fd.append('status', newStatus);
    if (note) fd.append('note', note);
    fetch('../api/admin.php?uid=' + adminUid(), { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                issue.status = newStatus;
                renderIssueTable();
                updateCounters();
                ACTIVITY.unshift({ color: newStatus==='resolved'?'td-green':'td-blue', text:`Issue <strong>${id}</strong> marked <strong>${newStatus}</strong>.`, time:'Just now' });
                renderActivityFeed();
                toast(`✅ Issue ${id} → ${newStatus}`);
            } else {
                toast('❌ ' + (data.message || 'Update failed'));
            }
        });
}

function viewIssue(id) {
    const issue = issueList.find(i => i.id === id);
    if (!issue) return;
    openAssignModal(id);
}

function renderUsers() {
    const rf = document.getElementById('roleFilter').value;
    const list = userList.filter(u => rf === 'all' || u.role === rf);
    document.getElementById('userTableBody').innerHTML = list.map(u => `
        <tr>
            <td>
                <div class="user-pill">
                    <div class="user-ball ${u.color}">${u.initials}</div>
                    <div>
                        <div style="font-weight:600;font-size:13px;">${u.name}</div>
                        <div style="font-size:11px;color:var(--text-muted);">${u.phone}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:12px;">${u.phone}</td>
            <td style="font-size:12px;">${u.ward}</td>
            <td style="font-weight:600;text-align:center;">${u.reports}</td>
            <td style="font-weight:600;text-align:center;color:var(--accent);">${u.points.toLocaleString()}</td>
            <td>${u.role === 'Admin' ? '<span class="badge b-admin">Admin</span>' : '<span class="badge b-user">User</span>'}</td>
            <td style="font-size:12px;color:var(--text-muted);">${u.joined}</td>
            <td>
                <div class="act-wrap">
                    <button class="act-btn act-assign" onclick="toast('Details for ${u.name}')">👁 View</button>
                    <button class="act-btn act-disable" onclick="toast('${u.name} disabled')">⛔ Disable</button>
                </div>
            </td>
        </tr>`).join('');
}

function renderEngineers() {
    document.getElementById('engTableBody').innerHTML = engineerList.map(e => `
        <tr>
            <td>
                <div class="user-pill">
                    <div class="user-ball ub-blue">${e.name.split(' ').map(w=>w[0]).join('').slice(0,2)}</div>
                    <span style="font-size:13px;font-weight:600;">${e.name}</span>
                </div>
            </td>
            <td><code style="font-size:12px;">${e.id}</code></td>
            <td style="font-size:12px;">${e.ward}</td>
            <td style="font-weight:600;text-align:center;">${e.active}</td>
            <td style="font-weight:600;text-align:center;color:var(--success);">${e.resolved}</td>
            <td>${e.status==='Active' ? '<span class="badge b-resolved">Active</span>' : '<span class="badge b-pending">Off-duty</span>'}</td>
            <td>
                <div class="act-wrap">
                    <button class="act-btn act-assign" onclick="toast('Viewing jobs for ${e.name}')">📋 Jobs</button>
                    <button class="act-btn act-disable" onclick="toggleEng('${e.id}')">🔄 Toggle</button>
                    <button class="act-btn act-delete" onclick="removeEng('${e.id}')">🗑</button>
                </div>
            </td>
        </tr>`).join('');
}

function toggleEng(id) {
    const eng = engineerList.find(e => e.id === id);
    if (!eng) return;
    eng.status = eng.status === 'Active' ? 'Off-duty' : 'Active';
    renderEngineers();
    toast(`🔄 ${eng.name} → ${eng.status}`);
}

function removeEng(id) {
    if (!confirm('Remove this engineer from the system?')) return;
    engineerList = engineerList.filter(e => e.id !== id);
    renderEngineers();
    toast('🗑️ Engineer removed');
}

function renderWards() {
    document.getElementById('wardTableBody').innerHTML = WARDS.map(w => {
        const h = w.health;
        const hColor = h >= 75 ? 'var(--success)' : h >= 50 ? 'var(--warning)' : 'var(--error)';
        return `
        <tr>
            <td style="font-weight:700;">${w.ward}</td>
            <td style="font-size:12px;">${w.zone}</td>
            <td style="font-weight:700;color:var(--warning);text-align:center;">${w.open}</td>
            <td style="font-weight:700;color:var(--info);text-align:center;">${w.progress}</td>
            <td style="font-weight:700;color:var(--success);text-align:center;">${w.resolved}</td>
            <td style="font-size:12px;">${w.engineer}</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex:1;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                        <div style="height:100%;width:${h}%;background:${hColor};border-radius:4px;transition:width 0.6s;"></div>
                    </div>
                    <span style="font-size:12px;font-weight:600;color:${hColor};min-width:34px;">${h}%</span>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function renderAlerts() {
    const colors = { error:'#fce8e6', warning:'#fef3e2', info:'#e8f0fe', success:'#e6f4ea' };
    document.getElementById('alertList').innerHTML = ALERTS.map((a,i) => `
        <div id="alert-${i}" style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-bottom:1px solid var(--border);background:${a.read?'#fff':colors[a.type]};">
            <span style="font-size:22px;flex-shrink:0;">${a.icon}</span>
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:${a.read?'400':'600'};line-height:1.5;">${a.text}</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">${a.time}</div>
            </div>
            ${!a.read ? `<button class="act-btn act-accept" onclick="readAlert(${i})">Mark Read</button>` : '<span style="font-size:11px;color:var(--text-muted);">Read</span>'}
        </div>`).join('');
}

function readAlert(i) {
    ALERTS[i].read = true;
    renderAlerts();
}

function markAllRead() {
    ALERTS.forEach(a => a.read = true);
    renderAlerts();
    toast('✅ All alerts marked as read');
}

function openAssignModal(id) {
    activeIssueId = id || null;
    if (id) {
        const issue = issueList.find(i => i.id === id);
        if (issue) {
            document.getElementById('m-id').value     = issue.id;
            document.getElementById('m-title').value  = `${issue.emoji} ${issue.type} at ${issue.location}`;
            document.getElementById('m-status').value = issue.status;
            document.getElementById('modalTitle').textContent = 'Assign / Update Issue';
            const wrap = document.getElementById('m-photo-wrap');
            const img = document.getElementById('m-photo-img');
            const link = document.getElementById('m-photo-link');
            if (issue.photoPath) {
                const normalizedPath = issue.photoPath.startsWith('/') ? issue.photoPath : `../${issue.photoPath}`;
                img.src = normalizedPath;
                link.href = normalizedPath;
                wrap.style.display = 'block';
            } else {
                img.src = '';
                link.href = '#';
                wrap.style.display = 'none';
            }
        }
    } else {
        document.getElementById('m-id').value    = 'New Issue';
        document.getElementById('m-title').value = '';
        document.getElementById('m-status').value = 'pending';
        document.getElementById('modalTitle').textContent = 'Add Issue Manually';
        document.getElementById('m-photo-wrap').style.display = 'none';
        document.getElementById('m-photo-img').src = '';
        document.getElementById('m-photo-link').href = '#';
    }
    document.getElementById('m-note').value = '';

    // Populate engineers dropdown using DB integer id as value
    const engSelect = document.getElementById('m-engineer');
    engSelect.innerHTML = '<option value="">— Select engineer —</option>' +
        engineerList.map(e => `<option value="${e.dbId}">${e.name} (${e.ward})</option>`).join('');

    document.getElementById('assignModal').classList.add('open');
}


function closeModal() {
    document.getElementById('assignModal').classList.remove('open');
}

function saveAssignment() {
    const engVal = document.getElementById('m-engineer').value;
    const status = document.getElementById('m-status').value;
    const note   = document.getElementById('m-note').value.trim();
    if (status === 'rejected' && !note) {
        toast('⚠️ Rejection remark is required');
        return;
    }

    if (activeIssueId) {
        const issue = issueList.find(i => i.id === activeIssueId);
        if (issue) {
            const fd = new FormData();
            fd.append('action',   'updateIssue');
            fd.append('issue_id', issue.realId);
            fd.append('status',   status);
            if (engVal) fd.append('engineer_id', engVal);
            if (note)   fd.append('note', note);
            fetch('../api/admin.php?uid=' + adminUid(), { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        issue.status = status;
                        renderIssueTable();
                        updateCounters();
                        const engName = engVal ? engineerList.find(e => String(e.dbId) === String(engVal))?.name || 'Engineer' : null;
                        ACTIVITY.unshift({ color:'td-blue', text:`Issue <strong>${activeIssueId}</strong> ${engName ? `assigned to <strong>${engName}</strong>` : 'updated'} → <strong>${status}</strong>.${note?' Note added.':''}`, time:'Just now' });
                        renderActivityFeed();
                        toast(engName ? `✅ Assigned to ${engName} · ${status}` : `✅ Status: ${status}`);
                    } else {
                        toast('❌ ' + (data.message || 'Update failed'));
                    }
                });
        }
    }
    closeModal();
}

function openAddEngModal() {
    document.getElementById('engModal').classList.add('open');
}
function closeEngModal() {
    document.getElementById('engModal').classList.remove('open');
}
async function addEngineer() {
    const name  = document.getElementById('eng-name').value.trim();
    const id    = document.getElementById('eng-id').value.trim();
    const ward  = document.getElementById('eng-ward').value;
    const phone = document.getElementById('eng-phone').value.trim();
    if (!name || !id) { toast('⚠️ Name and ID are required'); return; }
    
    const formData = new FormData();
    formData.append('action', 'addEngineer');
    formData.append('full_name', name);
    formData.append('phone', phone);
    formData.append('employee_id', id);
    formData.append('specialty', 'General'); // Placeholder
    formData.append('ward', ward);

    const res = await fetch('../api/admin.php?uid=' + adminUid(), { method:'POST', body:formData });
    const data = await res.json();
    
    if (data.success) {
        fetchAdminData();
        closeEngModal();
        toast(`✅ Engineer ${name} added`);
        document.getElementById('eng-name').value = '';
        document.getElementById('eng-id').value   = '';
        document.getElementById('eng-phone').value = '';
    } else {
        toast(`❌ Failed: ${data.message || 'Unknown error'}`);
    }
}


function globalSearchFn(q) {
    renderIssueTable();
}

function exportCSV() {
    const headers = ['ID','Type','Reporter','Location','Ward','Priority','Status','Date'];
    const rows    = filteredIssues.map(i =>
        [i.id, i.type, i.reporter, `"${i.location}"`, i.ward, i.priority, i.status, i.date].join(','));
    const csv = [headers.join(','), ...rows].join('\n');
    const blob = new Blob([csv], { type:'text/csv' });
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = `civictrack-issues-${Date.now()}.csv`;
    a.click();
    toast('📤 CSV exported!');
}

function saveSettings() {
    const name = document.getElementById('set-adminName').value.trim() || 'Admin';
    document.getElementById('adminNameDisplay').textContent = name;
    document.getElementById('adminAvatar').textContent     = name.charAt(0).toUpperCase();
    toast('💾 Settings saved!');
}

const CHART_COLORS = {
    green:  'rgba(45,106,79,0.8)',
    orange: 'rgba(244,144,12,0.8)',
    blue:   'rgba(26,115,232,0.8)',
    red:    'rgba(192,57,43,0.8)',
    purple: 'rgba(124,58,237,0.8)',
    teal:   'rgba(14,116,144,0.8)',
};

let chartsBuilt = false;
function buildCharts() {
    if (chartsBuilt) return;
    chartsBuilt = true;

    new Chart(document.getElementById('chartWeekly'), {
        type:'bar',
        data:{
            labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
            datasets:[{
                label:'Issues',
                data:[8,12,7,15,10,6,9],
                backgroundColor: Object.values(CHART_COLORS),
                borderRadius:6,
            }]
        },
        options:{ plugins:{ legend:{ display:false } }, responsive:true, scales:{ y:{ beginAtZero:true, grid:{ color:'#f0f0f0' } } } }
    });

    new Chart(document.getElementById('chartCategory'), {
        type:'doughnut',
        data:{
            labels:['Pothole','Street Light','Garbage','Water','Road Damage','Tree','Other'],
            datasets:[{
                data:[35,20,18,12,8,5,2],
                backgroundColor:['#F4900C','#1a73e8','#C0392B','#2D6A4F','#7c3aed','#0e7490','#6b7280'],
                borderWidth:2,
            }]
        },
        options:{ responsive:true, plugins:{ legend:{ position:'right', labels:{ font:{ family:'Poppins', size:11 } } } }, cutout:'60%' }
    });
}

let analyticsChartsBuilt = false;
function buildAnalyticsCharts() {
    if (analyticsChartsBuilt) return;
    analyticsChartsBuilt = true;

    new Chart(document.getElementById('chartMonthly'), {
        type:'line',
        data:{
            labels:['Nov','Dec','Jan','Feb','Mar','Apr'],
            datasets:[
                { label:'Reported', data:[45,62,58,74,80,92], borderColor:'#1a73e8', backgroundColor:'rgba(26,115,232,0.08)', fill:true, tension:0.4, borderWidth:2, pointRadius:4 },
                { label:'Resolved', data:[30,48,44,60,72,78], borderColor:'#2D6A4F', backgroundColor:'rgba(45,106,79,0.08)', fill:true, tension:0.4, borderWidth:2, pointRadius:4 },
            ]
        },
        options:{ responsive:true, plugins:{ legend:{ position:'top' } }, scales:{ y:{ beginAtZero:true, grid:{ color:'#f0f0f0' } } } }
    });

    new Chart(document.getElementById('chartResolution'), {
        type:'bar',
        data:{
            labels:['Pothole','Street Light','Garbage','Water Supply','Road Damage','Tree'],
            datasets:[{ label:'Resolution %', data:[78,92,65,88,70,95], backgroundColor:'rgba(45,106,79,0.75)', borderRadius:6 }]
        },
        options:{ indexAxis:'y', responsive:true, plugins:{ legend:{ display:false } }, scales:{ x:{ max:100, grid:{ color:'#f0f0f0' } } } }
    });

    new Chart(document.getElementById('chartWard'), {
        type:'bar',
        data:{
            labels:['Ward 41','Ward 42','Ward 43','Ward 44','Ward 45'],
            datasets:[
                { label:'Open',      data:[12,9,21,6,14], backgroundColor:'rgba(244,144,12,0.8)', borderRadius:4 },
                { label:'In Progress',data:[8,16,5,9,3],  backgroundColor:'rgba(26,115,232,0.8)', borderRadius:4 },
                { label:'Resolved',  data:[44,78,31,60,22],backgroundColor:'rgba(45,106,79,0.8)', borderRadius:4 },
            ]
        },
        options:{ responsive:true, plugins:{ legend:{ position:'top' } }, scales:{ x:{ stacked:false }, y:{ beginAtZero:true, grid:{ color:'#f0f0f0' } } } }
    });
}

function toast(msg) {
    const t = document.getElementById('adminToast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => t.classList.remove('show'), 3000);
}

function logout() {
    if (confirm('Sign out of admin panel?')) {
        sessionStorage.clear();
        window.location.href = 'civictrack_login.php';
    }
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('open');
        }
    });
});

let gmap          = null;
let mapMarkers    = [];
let activeInfoWin = null;
let mapInitialized = false;

const MARKER_COLORS = {
    pending:  '#e67e22',
    progress: '#1a73e8',
    resolved: '#2D6A4F',
    rejected: '#C0392B',
};

function initMap() {

    const scriptSrc = document.querySelector('script[src*="maps.googleapis.com"]')?.src || '';
    if (scriptSrc.includes('YOUR_GOOGLE_MAPS_API_KEY')) {
        document.getElementById('noKeyBanner').classList.add('visible');
        return;
    }

    const mumbaiCenter = { lat: 19.0638, lng: 72.8601 };

    gmap = new google.maps.Map(document.getElementById('gmap'), {
        center: mumbaiCenter,
        zoom: 13,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
        styles: [
            { featureType:'poi', stylers:[{ visibility:'off' }] },
            { featureType:'transit', stylers:[{ visibility:'simplified' }] },
        ],
    });

    placeMakers(issueList);
    renderMapSideList(issueList);
    updateMapStats(issueList);
    mapInitialized = true;
}

function showMap() {
    if (!mapInitialized) return;
    google.maps.event.trigger(gmap, 'resize');
    centerMap();
}

function makePinSvg(color, emoji) {
    const encodedEmoji = encodeURIComponent(emoji);
    const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="44" viewBox="0 0 36 44">
      <path d="M18 0C8.059 0 0 8.059 0 18c0 13.5 18 26 18 26S36 31.5 36 18C36 8.059 27.941 0 18 0z"
            fill="${color}" stroke="#fff" stroke-width="2"/>
      <circle cx="18" cy="18" r="10" fill="rgba(255,255,255,0.25)"/>
      <text x="18" y="23" text-anchor="middle" font-size="13">${emoji}</text>
    </svg>`;
    const blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
    return URL.createObjectURL(blob);
}

function placeMakers(issues) {

    mapMarkers.forEach(m => m.marker.setMap(null));
    mapMarkers = [];
    if (activeInfoWin) { activeInfoWin.close(); activeInfoWin = null; }

    issues.forEach(issue => {
        if (!issue.lat || !issue.lng) return;

        const color = MARKER_COLORS[issue.status] || '#555';
        const pinUrl = makePinSvg(color, issue.emoji);

        const marker = new google.maps.Marker({
            position : { lat: issue.lat, lng: issue.lng },
            map      : gmap,
            title    : `${issue.id} – ${issue.type}`,
            icon     : {
                url       : pinUrl,
                scaledSize: new google.maps.Size(32, 40),
                anchor    : new google.maps.Point(16, 40),
            },
            animation: google.maps.Animation.DROP,
        });

        const priorityColor = { Normal:'#1a73e8', High:'#e67e22', Urgent:'#C0392B' }[issue.priority] || '#555';

        const infoContent = `
            <div style="font-family:Poppins,sans-serif;min-width:240px;padding:2px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span style="font-size:22px;">${issue.emoji}</span>
                    <div>
                        <div style="font-weight:700;font-size:14px;color:#1a1a1a;">${issue.type}</div>
                        <div style="font-size:11px;color:#6b7280;">${issue.id} &middot; ${issue.ward}</div>
                    </div>
                </div>
                <div style="font-size:12px;color:#374151;margin-bottom:6px;">📍 ${issue.location}</div>
                <div style="font-size:12px;color:#374151;margin-bottom:6px;">👤 ${issue.reporter}</div>
                <div style="display:flex;gap:6px;align-items:center;margin-bottom:10px;">
                    <span style="background:${MARKER_COLORS[issue.status]};color:#fff;font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;text-transform:capitalize;">${issue.status}</span>
                    <span style="background:${priorityColor};color:#fff;font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;">${issue.priority}</span>
                </div>
                <div style="display:flex;gap:6px;">
                    ${issue.status === 'pending' ? `
                    <button onclick="mapQuickStatus('${issue.id}','progress')" style="flex:1;padding:5px;background:#e8f0fe;color:#1a73e8;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">✓ Accept</button>
                    <button onclick="mapQuickStatus('${issue.id}','rejected')" style="flex:1;padding:5px;background:#fce8e6;color:#C0392B;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">✗ Reject</button>
                    ` : ''}
                    <button onclick="openAssignModal('${issue.id}')" style="flex:1;padding:5px;background:#f3e8ff;color:#7c3aed;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">🔧 Assign</button>
                </div>
            </div>`;

        const infoWindow = new google.maps.InfoWindow({ content: infoContent, maxWidth: 300 });

        marker.addListener('click', () => {
            if (activeInfoWin) activeInfoWin.close();
            infoWindow.open(gmap, marker);
            activeInfoWin = infoWindow;

            document.querySelectorAll('.map-issue-row').forEach(r => r.style.background = '');
            const row = document.getElementById('mir-' + issue.id.replace('#',''));
            if (row) { row.style.background = '#f4f9f0'; row.scrollIntoView({ behavior:'smooth', block:'nearest' }); }
        });

        mapMarkers.push({ marker, infoWindow, issue });
    });
}

function renderMapSideList(issues) {
    const el = document.getElementById('mapIssueList');
    if (!el) return;
    el.innerHTML = issues.map(i => `
        <div class="map-issue-row" id="mir-${i.id.replace('#','')}" onclick="panToIssue('${i.id}')">
            <span class="mir-emoji">${i.emoji}</span>
            <div style="flex:1;min-width:0;">
                <div class="mir-title" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${i.type} – ${i.ward}</div>
                <div class="mir-sub" style="display:flex;align-items:center;gap:4px;">
                    <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:${MARKER_COLORS[i.status]};"></span>
                    ${i.status.charAt(0).toUpperCase()+i.status.slice(1)} &middot; ${i.priority}
                </div>
            </div>
        </div>`).join('');
}

function panToIssue(id) {
    const entry = mapMarkers.find(m => m.issue.id === id);
    if (!entry) return;
    gmap.panTo(entry.marker.getPosition());
    gmap.setZoom(16);
    if (activeInfoWin) activeInfoWin.close();
    entry.infoWindow.open(gmap, entry.marker);
    activeInfoWin = entry.infoWindow;
}

function updateMapStats(issues) {
    const count = s => issues.filter(i => i.status === s).length;
    const el = id => document.getElementById(id);
    if (el('mpVisible'))  el('mpVisible').textContent  = issues.length;
    if (el('mpPending'))  el('mpPending').textContent  = count('pending');
    if (el('mpProgress')) el('mpProgress').textContent = count('progress');
    if (el('mpResolved')) el('mpResolved').textContent = count('resolved');
    if (el('mpRejected')) el('mpRejected').textContent = count('rejected');
}

function filterMapMarkers() {
    if (!mapInitialized) return;
    const sf = document.getElementById('mapStatusFilter').value;
    const tf = document.getElementById('mapTypeFilter').value;
    const visible = issueList.filter(i => {
        if (sf !== 'all' && i.status !== sf)      return false;
        if (tf !== 'all' && !i.type.includes(tf)) return false;
        return true;
    });
    placeMakers(visible);
    renderMapSideList(visible);
    updateMapStats(visible);
}

function centerMap() {
    if (!gmap) return;
    gmap.panTo({ lat: 19.0638, lng: 72.8601 });
    gmap.setZoom(13);
}

function refreshMapMarkers() {
    if (!mapInitialized) return;
    filterMapMarkers();
    toast('🔄 Map refreshed!');
}

function mapQuickStatus(id, newStatus) {
    quickStatus(id, newStatus);
    if (activeInfoWin) activeInfoWin.close();

    setTimeout(() => filterMapMarkers(), 100);
}
