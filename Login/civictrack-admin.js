/* ============================================================
   CivicTrack – Admin Panel  |  civictrack-admin.js
   ============================================================ */

// ──────────────────────────────────────────────────────────────
// SAMPLE DATA
// ──────────────────────────────────────────────────────────────
const ISSUES = [
    { id:'#CT001', type:'Pothole',      emoji:'🕳️', reporter:'Ramesh Kumar',  phone:'+91 98200 11111', location:'MG Road, near bus stop',       ward:'Ward 42', priority:'High',   status:'pending',  date:'27 Apr 2026', icon_bg:'#fef3e2', lat:19.05950, lng:72.83540 },
    { id:'#CT002', type:'Street Light', emoji:'💡', reporter:'Anjali Singh',  phone:'+91 98200 22222', location:'Nehru Nagar junction',          ward:'Ward 42', priority:'Normal', status:'resolved', date:'22 Apr 2026', icon_bg:'#e6f4ea', lat:19.07280, lng:72.88260 },
    { id:'#CT003', type:'Garbage',      emoji:'🗑️', reporter:'Priya Menon',   phone:'+91 98200 33333', location:'Sector 7B, near temple',        ward:'Ward 43', priority:'Urgent', status:'pending',  date:'27 Apr 2026', icon_bg:'#fce8e6', lat:19.05540, lng:72.84420 },
    { id:'#CT004', type:'Water Supply', emoji:'💧', reporter:'Suresh Nair',   phone:'+91 98200 44444', location:'Lal Bahadur colony, tap 4',     ward:'Ward 41', priority:'Urgent', status:'progress', date:'25 Apr 2026', icon_bg:'#e8f0fe', lat:19.04300, lng:72.85530 },
    { id:'#CT005', type:'Road Damage',  emoji:'🚧', reporter:'Deepa Rao',     phone:'+91 98200 55555', location:'Link Road, flyover approach',   ward:'Ward 44', priority:'High',   status:'progress', date:'24 Apr 2026', icon_bg:'#fff3e0', lat:19.09180, lng:72.82980 },
    { id:'#CT006', type:'Tree',         emoji:'🌳', reporter:'Kiran Patil',   phone:'+91 98200 66666', location:'Shivaji Park, gate 2',          ward:'Ward 42', priority:'Normal', status:'resolved', date:'20 Apr 2026', icon_bg:'#e6f4ea', lat:19.02900, lng:72.83810 },
    { id:'#CT007', type:'Pothole',      emoji:'🕳️', reporter:'Mohan Das',     phone:'+91 98200 77777', location:'Station Road, opp. cinema',    ward:'Ward 43', priority:'High',   status:'pending',  date:'26 Apr 2026', icon_bg:'#fef3e2', lat:19.06800, lng:72.85760 },
    { id:'#CT008', type:'Garbage',      emoji:'🗑️', reporter:'Sunita Verma',  phone:'+91 98200 88888', location:'Market Lane, Dumpyard area',   ward:'Ward 42', priority:'Normal', status:'rejected', date:'21 Apr 2026', icon_bg:'#fce8e6', lat:19.05160, lng:72.83500 },
    { id:'#CT009', type:'Street Light', emoji:'💡', reporter:'Anil Sharma',   phone:'+91 98200 99999', location:'Garden Circle, lamp post 14',  ward:'Ward 41', priority:'Normal', status:'pending',  date:'27 Apr 2026', icon_bg:'#e6f4ea', lat:19.06500, lng:72.87000 },
    { id:'#CT010', type:'Water Supply', emoji:'💧', reporter:'Rekha Joshi',   phone:'+91 98200 10101', location:'Patel Colony, main pipeline',  ward:'Ward 45', priority:'Urgent', status:'progress', date:'23 Apr 2026', icon_bg:'#e8f0fe', lat:19.08200, lng:72.84800 },
];

const USERS = [
    { name:'Ramesh Kumar',  initials:'RK', color:'ub-blue',   phone:'+91 98200 11111', ward:'Ward 42', reports:8,  points:4520, role:'User',  joined:'Jan 2025' },
    { name:'Anjali Singh',  initials:'AS', color:'ub-green',  phone:'+91 98200 22222', ward:'Ward 42', reports:6,  points:3890, role:'User',  joined:'Feb 2025' },
    { name:'Priya Menon',   initials:'PM', color:'ub-orange', phone:'+91 98200 33333', ward:'Ward 43', reports:5,  points:3210, role:'User',  joined:'Mar 2025' },
    { name:'Super Admin',   initials:'SA', color:'ub-purple', phone:'+91 98765 00000', ward:'-',       reports:0,  points:0,    role:'Admin', joined:'Jan 2025' },
    { name:'Suresh Nair',   initials:'SN', color:'ub-blue',   phone:'+91 98200 44444', ward:'Ward 41', reports:4,  points:2800, role:'User',  joined:'Apr 2025' },
    { name:'Deepa Rao',     initials:'DR', color:'ub-orange', phone:'+91 98200 55555', ward:'Ward 44', reports:3,  points:1500, role:'User',  joined:'Mar 2025' },
];

const ENGINEERS = [
    { name:'Ravi Kumar',  id:'ENG-001', ward:'Ward 42', active:4, resolved:28, status:'Active' },
    { name:'Priya Iyer',  id:'ENG-002', ward:'Ward 42', active:2, resolved:45, status:'Active' },
    { name:'Deepak Nair', id:'ENG-003', ward:'Ward 41', active:6, resolved:19, status:'Active' },
    { name:'Anjali Verma',id:'ENG-004', ward:'Ward 43', active:0, resolved:32, status:'Off-duty' },
    { name:'Suresh Menon',id:'ENG-005', ward:'Ward 42', active:3, resolved:14, status:'Active' },
];

const WARDS = [
    { ward:'Ward 41', zone:'North', open:12, progress:8,  resolved:44, engineer:'Deepak Nair',  health:72 },
    { ward:'Ward 42', zone:'Central',open:9, progress:16, resolved:78, engineer:'Ravi Kumar',   health:87 },
    { ward:'Ward 43', zone:'East',  open:21, progress:5,  resolved:31, engineer:'Anjali Verma', health:54 },
    { ward:'Ward 44', zone:'West',  open:6,  progress:9,  resolved:60, engineer:'—',            health:79 },
    { ward:'Ward 45', zone:'South', open:14, progress:3,  resolved:22, engineer:'—',            health:45 },
];

const ALERTS = [
    { type:'error',   icon:'🚨', text:'7 issues are overdue by more than 72 hours. SLA breach detected.',   time:'5 mins ago', read:false },
    { type:'warning', icon:'⚠️', text:'Ward 43 has 21 open issues – highest in the city this week.',         time:'1 hour ago',  read:false },
    { type:'info',    icon:'ℹ️', text:'Engineer Anjali Verma is off-duty. Reassign her active jobs.',        time:'3 hours ago', read:false },
    { type:'success', icon:'✅', text:'Issue #CT002 (Streetlight) resolved by Ravi Kumar.',                  time:'5 hours ago', read:true  },
    { type:'success', icon:'✅', text:'40 new residents registered this month.',                             time:'1 day ago',   read:true  },
];

const ACTIVITY = [
    { color:'td-red',    text:'Issue <strong>#CT003</strong> (Garbage – Sector 7B) marked <strong>Urgent</strong>.', time:'Just now'   },
    { color:'td-green',  text:'Issue <strong>#CT002</strong> resolved by Ravi Kumar.',                              time:'2 hrs ago'  },
    { color:'td-blue',   text:'Issue <strong>#CT004</strong> assigned to Deepak Nair.',                             time:'3 hrs ago'  },
    { color:'td-orange', text:'New report: <strong>Road Damage</strong> at Link Road.',                             time:'5 hrs ago'  },
    { color:'td-green',  text:'Ward 42 satisfaction score updated to <strong>4.2 ⭐</strong>.',                     time:'8 hrs ago'  },
    { color:'td-blue',   text:'40 new residents registered this month.',                                            time:'1 day ago'  },
];

// ──────────────────────────────────────────────────────────────
// STATE
// ──────────────────────────────────────────────────────────────
let activeIssueId = null;
let engineerList  = [...ENGINEERS];
let issueList     = [...ISSUES];
let userList      = [...USERS];
let filteredIssues = [...ISSUES];

// ──────────────────────────────────────────────────────────────
// INIT
// ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    setTimestamp();
    renderActivityFeed();
    renderIssueTable();
    renderUsers();
    renderEngineers();
    renderWards();
    renderAlerts();
    buildCharts();
    updateCounters();
});

function setTimestamp() {
    document.getElementById('lastUpdated').textContent =
        new Date().toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit' });
}

// ──────────────────────────────────────────────────────────────
// TABS
// ──────────────────────────────────────────────────────────────
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
    // Deactivate all
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn, .sidebar-link').forEach(b => b.classList.remove('active'));

    // Activate new
    document.getElementById(tabId).classList.add('active');
    document.querySelectorAll(`[data-tab="${tabId}"]`).forEach(b => b.classList.add('active'));

    const [title, sub] = tabTitles[tabId];
    document.getElementById('pageTitle').textContent    = title;
    document.getElementById('pageSubtitle').textContent = sub;

    // Rebuild analytics charts when tab shown (sizing fix)
    if (tabId === 'tab-analytics') buildAnalyticsCharts();
    // Show / init map
    if (tabId === 'tab-map') showMap();
}

// ──────────────────────────────────────────────────────────────
// COUNTERS
// ──────────────────────────────────────────────────────────────
function updateCounters() {
    const pending = issueList.filter(i => i.status === 'pending').length;
    document.getElementById('pendingBadge').textContent = pending;
    document.getElementById('reqCount').textContent     = pending;
    document.getElementById('s-total').textContent      = issueList.length;
    document.getElementById('s-pending').textContent    = pending;
    document.getElementById('s-progress').textContent   = issueList.filter(i => i.status === 'progress').length;
    document.getElementById('s-resolved').textContent   = issueList.filter(i => i.status === 'resolved').length;
}

// ──────────────────────────────────────────────────────────────
// ACTIVITY FEED
// ──────────────────────────────────────────────────────────────
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

// ──────────────────────────────────────────────────────────────
// ISSUE TABLE
// ──────────────────────────────────────────────────────────────
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
                        <div style="font-size:11px;color:var(--text-muted);">${i.reporter}</div>
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
    const map = { pending:'b-pending Pending', progress:'b-progress In Progress', resolved:'b-resolved Resolved', rejected:'b-rejected Rejected' };
    const [cls, label] = (map[s] || 'b-pending Pending').split(' ');
    return `<span class="badge ${cls}">${label.replace('b-','')}</span>`;
}

function priorityBadge(p) {
    const map = { Normal:'b-normal', High:'b-high', Urgent:'b-urgent' };
    const dot = { Normal:'p-normal', High:'p-high', Urgent:'p-urgent' };
    return `<span class="badge ${map[p]||'b-normal'}"><span class="p-dot ${dot[p]||'p-normal'}"></span>${p}</span>`;
}

function quickStatus(id, newStatus) {
    const issue = issueList.find(i => i.id === id);
    if (!issue) return;
    issue.status = newStatus;
    renderIssueTable();
    updateCounters();
    toast(`✅ Issue ${id} → ${newStatus}`);
    ACTIVITY.unshift({ color: newStatus==='resolved'?'td-green':'td-blue', text:`Issue <strong>${id}</strong> marked <strong>${newStatus}</strong>.`, time:'Just now' });
    renderActivityFeed();
}

function viewIssue(id) {
    const issue = issueList.find(i => i.id === id);
    if (!issue) return;
    openAssignModal(id);
}

// ──────────────────────────────────────────────────────────────
// USERS TABLE
// ──────────────────────────────────────────────────────────────
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

// ──────────────────────────────────────────────────────────────
// ENGINEERS TABLE
// ──────────────────────────────────────────────────────────────
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

// ──────────────────────────────────────────────────────────────
// WARDS TABLE
// ──────────────────────────────────────────────────────────────
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

// ──────────────────────────────────────────────────────────────
// ALERTS
// ──────────────────────────────────────────────────────────────
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

// ──────────────────────────────────────────────────────────────
// ASSIGN MODAL
// ──────────────────────────────────────────────────────────────
function openAssignModal(id) {
    activeIssueId = id || null;
    if (id) {
        const issue = issueList.find(i => i.id === id);
        if (issue) {
            document.getElementById('m-id').value     = issue.id;
            document.getElementById('m-title').value  = `${issue.emoji} ${issue.type} at ${issue.location}`;
            document.getElementById('m-status').value = issue.status;
            document.getElementById('modalTitle').textContent = 'Assign / Update Issue';
        }
    } else {
        document.getElementById('m-id').value    = 'New Issue';
        document.getElementById('m-title').value = '';
        document.getElementById('m-status').value = 'pending';
        document.getElementById('modalTitle').textContent = 'Add Issue Manually';
    }
    document.getElementById('m-note').value = '';
    document.getElementById('assignModal').classList.add('open');
}

function closeModal() {
    document.getElementById('assignModal').classList.remove('open');
}

function saveAssignment() {
    const eng    = document.getElementById('m-engineer').value;
    const status = document.getElementById('m-status').value;
    const note   = document.getElementById('m-note').value.trim();

    if (activeIssueId) {
        const issue = issueList.find(i => i.id === activeIssueId);
        if (issue) {
            issue.status = status;
            renderIssueTable();
            updateCounters();
            ACTIVITY.unshift({ color:'td-blue', text:`Issue <strong>${activeIssueId}</strong> ${eng ? `assigned to <strong>${eng.split(' ')[0]}</strong>` : 'updated'} → <strong>${status}</strong>.${note?' Note added.':''}`, time:'Just now' });
            renderActivityFeed();
        }
    }
    closeModal();
    toast(eng
        ? `✅ Assigned to ${eng.split(' (')[0]} · Status: ${status}`
        : `✅ Status updated to ${status}`);
}

// ──────────────────────────────────────────────────────────────
// ENGINEER MODAL
// ──────────────────────────────────────────────────────────────
function openAddEngModal() {
    document.getElementById('engModal').classList.add('open');
}
function closeEngModal() {
    document.getElementById('engModal').classList.remove('open');
}
function addEngineer() {
    const name  = document.getElementById('eng-name').value.trim();
    const id    = document.getElementById('eng-id').value.trim();
    const ward  = document.getElementById('eng-ward').value;
    const phone = document.getElementById('eng-phone').value.trim();
    if (!name || !id) { toast('⚠️ Name and ID are required'); return; }
    engineerList.push({ name, id, ward, phone, active:0, resolved:0, status:'Active' });
    renderEngineers();
    closeEngModal();
    toast(`✅ Engineer ${name} added`);
    document.getElementById('eng-name').value = '';
    document.getElementById('eng-id').value   = '';
    document.getElementById('eng-phone').value = '';
}

// ──────────────────────────────────────────────────────────────
// GLOBAL SEARCH
// ──────────────────────────────────────────────────────────────
function globalSearchFn(q) {
    renderIssueTable();
}

// ──────────────────────────────────────────────────────────────
// EXPORT CSV
// ──────────────────────────────────────────────────────────────
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

// ──────────────────────────────────────────────────────────────
// SETTINGS
// ──────────────────────────────────────────────────────────────
function saveSettings() {
    const name = document.getElementById('set-adminName').value.trim() || 'Admin';
    document.getElementById('adminNameDisplay').textContent = name;
    document.getElementById('adminAvatar').textContent     = name.charAt(0).toUpperCase();
    toast('💾 Settings saved!');
}

// ──────────────────────────────────────────────────────────────
// CHARTS
// ──────────────────────────────────────────────────────────────
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

    // Weekly bar chart
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

    // Category doughnut
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

    // Monthly trend
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

    // Resolution rate horizontal bar
    new Chart(document.getElementById('chartResolution'), {
        type:'bar',
        data:{
            labels:['Pothole','Street Light','Garbage','Water Supply','Road Damage','Tree'],
            datasets:[{ label:'Resolution %', data:[78,92,65,88,70,95], backgroundColor:'rgba(45,106,79,0.75)', borderRadius:6 }]
        },
        options:{ indexAxis:'y', responsive:true, plugins:{ legend:{ display:false } }, scales:{ x:{ max:100, grid:{ color:'#f0f0f0' } } } }
    });

    // Ward heatmap (grouped bar)
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

// ──────────────────────────────────────────────────────────────
// TOAST
// ──────────────────────────────────────────────────────────────
function toast(msg) {
    const t = document.getElementById('adminToast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => t.classList.remove('show'), 3000);
}

// ──────────────────────────────────────────────────────────────
// LOGOUT
// ──────────────────────────────────────────────────────────────
function logout() {
    if (confirm('Sign out of admin panel?')) {
        sessionStorage.clear();
        window.location.href = 'civictrack_login.html';
    }
}

// Close modals on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('open');
        }
    });
});

// ──────────────────────────────────────────────────────────────
// GOOGLE MAPS INTEGRATION
// ──────────────────────────────────────────────────────────────
let gmap          = null;
let mapMarkers    = [];   // { marker, infoWindow, issue }
let activeInfoWin = null;
let mapInitialized = false;

// Colour per status
const MARKER_COLORS = {
    pending:  '#e67e22',
    progress: '#1a73e8',
    resolved: '#2D6A4F',
    rejected: '#C0392B',
};

// Called by Google Maps API callback=initMap
function initMap() {
    // Detect placeholder key – show banner instead
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

// Called when map tab becomes active
function showMap() {
    if (!mapInitialized) return;          // initMap not yet done – API still loading; it will call itself
    google.maps.event.trigger(gmap, 'resize');
    centerMap();
}

// Build an SVG pin element for a given status colour
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

// Place markers for a given set of issues
function placeMakers(issues) {
    // Clear old markers
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
            // Highlight sidebar row
            document.querySelectorAll('.map-issue-row').forEach(r => r.style.background = '');
            const row = document.getElementById('mir-' + issue.id.replace('#',''));
            if (row) { row.style.background = '#f4f9f0'; row.scrollIntoView({ behavior:'smooth', block:'nearest' }); }
        });

        mapMarkers.push({ marker, infoWindow, issue });
    });
}

// Sidebar list of visible issues
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

// Update the floating stats panel
function updateMapStats(issues) {
    const count = s => issues.filter(i => i.status === s).length;
    const el = id => document.getElementById(id);
    if (el('mpVisible'))  el('mpVisible').textContent  = issues.length;
    if (el('mpPending'))  el('mpPending').textContent  = count('pending');
    if (el('mpProgress')) el('mpProgress').textContent = count('progress');
    if (el('mpResolved')) el('mpResolved').textContent = count('resolved');
    if (el('mpRejected')) el('mpRejected').textContent = count('rejected');
}

// Filter markers based on toolbar dropdowns
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

// Re-center & reset zoom to Mumbai
function centerMap() {
    if (!gmap) return;
    gmap.panTo({ lat: 19.0638, lng: 72.8601 });
    gmap.setZoom(13);
}

// Refresh – re-place all markers
function refreshMapMarkers() {
    if (!mapInitialized) return;
    filterMapMarkers();
    toast('🔄 Map refreshed!');
}

// Quick accept/reject from info window
function mapQuickStatus(id, newStatus) {
    quickStatus(id, newStatus);
    if (activeInfoWin) activeInfoWin.close();
    // Give DOM time to update then re-filter
    setTimeout(() => filterMapMarkers(), 100);
}

