<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicTrack – Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="civictrack-dashboard.css">
    <!-- exifr: lightweight EXIF reader to extract GPS embedded in photos -->
    <script src="https://cdn.jsdelivr.net/npm/exifr@7.1.3/dist/lite.umd.js"></script>
</head>
<body>

    
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../Login/logo.jpg" alt="CivicTrack">
        </div>

        <div class="nav-section-label">Main</div>

        <a href="civictrack-dashboard.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            <span>Dashboard</span>
        </a>
        <a href="civictrack-my-reports.php" class="nav-item">
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
            <span class="nav-badge" id="notifBadge">0</span>
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

        <div class="nav-section-label">Administration</div>
        <a href="civictrack-admin.php" class="nav-item" style="color:rgba(244,144,12,0.85);">
            <span class="nav-icon">🛡️</span>
            <span>Admin Panel</span>
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
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-right">
                <div class="notif-bell" title="Notifications">
                    🔔
                    <span class="notif-dot"></span>
                </div>
                <button class="topbar-btn" onclick="openModal()">
                    ＋ Report Issue
                </button>
            </div>
        </div>

        
        <div class="page-body">

            
            <div class="welcome-banner">
                <div>
                    <h2 id="welcomeMsg">Welcome back!</h2>
                    <p id="welcomeSub">Here's what's happening in your ward today.</p>
                </div>
                <div class="welcome-icon">🏙️</div>
            </div>

            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">📝</div>
                    <div>
                        <div class="stat-num" id="statTotal">3</div>
                        <div class="stat-label">Issues Reported</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div>
                        <div class="stat-num" id="statResolved">0</div>
                        <div class="stat-label">Resolved</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">⏳</div>
                    <div>
                        <div class="stat-num" id="statInProgress">0</div>
                        <div class="stat-label">In Progress</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">⚠️</div>
                    <div>
                        <div class="stat-num" id="statOpen">0</div>
                        <div class="stat-label">Open</div>
                    </div>
                </div>
            </div>

            
            <div class="content-grid">

                
                <div class="section-card">
                    <div class="section-header">
                        <h3>Recent Reports</h3>
                        <button onclick="fetchIssues()" style="background:none;border:1.5px solid var(--border);padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;font-family:Poppins,sans-serif;" title="Refresh issues from server">🔄 Refresh</button>
                    </div>
                    <div class="issue-list" id="issueList">
                        <div class="issue-item" style="justify-content:center;color:#aaa;font-size:13px;padding:24px;">⏳ Loading your reports…</div>
                    </div>
                </div>

                
                <div style="display:flex; flex-direction:column; gap:20px;">

                    
                    <div class="section-card">
                        <div class="section-header">
                            <h3>Quick Report</h3>
                        </div>
                        <div class="quick-report">
                            <div class="form-group">
                                <label class="form-label">Issue Type</label>
                                <select class="form-select" id="quickIssueType">
                                    <option value="">Select type…</option>
                                    <option>🕳️ Pothole</option>
                                    <option>💡 Street Light</option>
                                    <option>🗑️ Garbage</option>
                                    <option>💧 Water Supply</option>
                                    <option>🌳 Tree / Fallen Branch</option>
                                    <option>🚧 Road Damage</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <div class="location-input-group">
                                    <input type="text" class="form-input" id="quickLocation" placeholder="e.g. MG Road, near post office">
                                    <button type="button" class="btn-icon" onclick="detectLocation('quickLocation')" title="Detect Location">🎯</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Photo (with Geotag)</label>
                                <div class="file-upload-box" onclick="document.getElementById('quickPhoto').click()">
                                    <span>📷 Tap to upload or take a photo</span>
                                    <input type="file" id="quickPhoto" accept="image/*" capture="environment" style="display:none;" onchange="handlePhotoSelect(this, 'quickPhotoPreview', 'quickLocation')">
                                </div>
                                <div id="quickPhotoPreview" class="photo-preview" style="display:none;">
                                    <img src="" id="quickPhotoImg" alt="Preview">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea class="form-textarea" id="quickDesc" placeholder="Brief description…"></textarea>
                            </div>
                            <button class="submit-btn" onclick="submitQuickReport()">Submit Report</button>
                        </div>
                    </div>

                    
                    <div class="section-card">
                        <div class="section-header">
                            <h3>Activity Feed</h3>
                            <a href="civictrack-notifications.php" class="see-all">See all →</a>
                        </div>
                        <div class="activity-list" id="activityFeed">
                            <div style="text-align:center;color:#aaa;font-size:13px;padding:20px;">Loading…</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    
    <div class="modal-overlay" id="reportModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Report a Civic Issue</h3>
                <button class="modal-close" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Issue Type</label>
                    <select class="form-select" id="modalIssueType">
                        <option value="">Select type…</option>
                        <option>🕳️ Pothole</option>
                        <option>💡 Street Light</option>
                        <option>🗑️ Garbage</option>
                        <option>💧 Water Supply</option>
                        <option>🌳 Tree / Fallen Branch</option>
                        <option>🚧 Road Damage</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <div class="location-input-group">
                        <input type="text" class="form-input" id="modalLocation" placeholder="Street address or landmark">
                        <button type="button" class="btn-icon" onclick="detectLocation('modalLocation')" title="Detect Location">🎯</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Photo (with Geotag)</label>
                    <div class="file-upload-box" onclick="document.getElementById('modalPhoto').click()">
                        <span>📷 Tap to upload or take a photo</span>
                        <input type="file" id="modalPhoto" accept="image/*" capture="environment" style="display:none;" onchange="handlePhotoSelect(this, 'modalPhotoPreview', 'modalLocation')">
                    </div>
                    <div id="modalPhotoPreview" class="photo-preview" style="display:none;">
                        <img src="" id="modalPhotoImg" alt="Preview">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Ward / Area</label>
                    <input type="text" class="form-input" id="modalWard" placeholder="e.g. Ward 42, Andheri West">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" id="modalDesc" placeholder="Describe the issue in detail…" style="min-height:90px;"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select class="form-select" id="modalPriority">
                        <option>Normal</option>
                        <option>High</option>
                        <option>Urgent</option>
                    </select>
                </div>
                <button class="modal-submit-btn" onclick="submitModalReport()">Submit Report</button>
            </div>
        </div>
    </div>

    
    <div class="toast" id="toast">✅ Report submitted successfully!</div>

    <script>
        const userName = sessionStorage.getItem('ct_name')  || 'Resident';
        const userWard = sessionStorage.getItem('ct_ward')  || 'Ward 42, Mumbai';
        const userCity = sessionStorage.getItem('ct_city')  || 'Mumbai';
        const userPhone= sessionStorage.getItem('ct_phone') || '';
        const currentUserId = parseInt(sessionStorage.getItem('ct_user_id') || '0');

        document.getElementById('welcomeMsg').textContent  = `Welcome, ${userName}!`;
        document.getElementById('welcomeSub').textContent  = `Here's what's happening in ${userWard} today.`;
        document.getElementById('sidebarName').textContent = userName;
        document.getElementById('sidebarWard').textContent = `${userWard}, ${userCity}`;
        document.getElementById('sidebarAvatar').textContent = userName.charAt(0).toUpperCase();

        let reportCount = 0;

        // Fetch issues and notifications on load + auto-refresh every 20s
        document.addEventListener('DOMContentLoaded', () => {
            fetchIssues();
            fetchActivityFeed();
            // Auto-refresh every 20 seconds so engineer/admin changes appear without reloading
            setInterval(() => { fetchIssues(); fetchActivityFeed(); }, 20000);
        });

        function setActive(el) {
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            el.classList.add('active');
        }

        function openModal() {
            document.getElementById('reportModal').classList.add('open');
        }
        function closeModal() {
            document.getElementById('reportModal').classList.remove('open');
        }

        async function handlePhotoSelect(input, previewId, locationInputId) {
            if (!input.files || !input.files[0]) return;

            // Show preview immediately
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById(previewId);
                preview.style.display = 'block';
                preview.querySelector('img').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);

            // Step 1: Try to extract GPS from the photo's EXIF data
            // This is the CORRECT location — where the photo was taken (the issue site)
            try {
                if (typeof exifr !== 'undefined') {
                    const gps = await exifr.gps(input.files[0]);
                    if (gps && gps.latitude != null && gps.longitude != null) {
                        const locationInput = document.getElementById(locationInputId);
                        locationInput.value = `Geo: ${gps.latitude.toFixed(6)}, ${gps.longitude.toFixed(6)}`;
                        showToast('📍 Location read from photo GPS data!');
                        return; // ✅ Done — use the photo's embedded GPS
                    }
                }
            } catch (exifErr) {
                console.warn('EXIF GPS read failed:', exifErr);
            }

            // Step 2: Photo has no GPS tag — ask the user if they want device GPS
            if (confirm('No GPS data found in this photo.\nWould you like to attach your current device location instead?')) {
                detectLocation(locationInputId);
            }
        }

        function detectLocation(inputId) {
            const input = document.getElementById(inputId);
            const originalPlaceholder = input.placeholder;
            input.placeholder = "Detecting location...";
            input.value = "";

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((position) => {
                    const lat = position.coords.latitude.toFixed(5);
                    const lng = position.coords.longitude.toFixed(5);
                    input.value = `Geo: ${lat}, ${lng}`;
                    showToast('📍 Location detected successfully!');
                }, (error) => {
                    input.placeholder = originalPlaceholder;
                    showToast('⚠️ Could not detect location: ' + error.message);
                });
            } else {
                input.placeholder = originalPlaceholder;
                showToast("⚠️ Geolocation is not supported.");
            }
        }
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // showToast defined below (supports colour parameter)

        function fetchIssues() {
            // Only fetch THIS user's own reports so stat cards are accurate
            const uid = currentUserId > 0 ? `&user_id=${currentUserId}` : '';
            fetch(`../api/issues.php?action=fetchIssues&filter=mine${uid}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const list = document.getElementById('issueList');
                        list.innerHTML = ''; // clear existing
                        reportCount = data.issues.length;
                        
                        let resolvedCount = 0;
                        let progressCount = 0;
                        let openCount = 0;

                        if (reportCount === 0) {
                            list.innerHTML = '<div class="issue-item" style="justify-content:center; color:#aaa; font-size:13px; padding:24px;">No reports yet. Use &ldquo;Report Issue&rdquo; to get started!</div>';
                        } else {
                            data.issues.forEach(issue => {
                                if (issue.status === 'Resolved') resolvedCount++;
                                else if (issue.status === 'In Progress') progressCount++;
                                else openCount++;
                                renderIssue(issue, list);
                            });
                        }

                        document.getElementById('statTotal').textContent = reportCount;
                        document.getElementById('statResolved').textContent = resolvedCount;
                        document.getElementById('statInProgress').textContent = progressCount;
                        document.getElementById('statOpen').textContent = openCount;
                        document.getElementById('myReportsBadge').textContent = reportCount;
                    }
                })
                .catch(error => console.error('Error fetching issues:', error));
        }

        function renderIssue(issue, container) {
            const icons = { '🕳️ Pothole':'🕳️', '💡 Street Light':'💡', '🗑️ Garbage':'🗑️', '💧 Water Supply':'💧', '🌳 Tree / Fallen Branch':'🌳', '🚧 Road Damage':'🚧', 'Other':'📌' };
            const iconColors = { '🕳️ Pothole':'#fef3e2', '💡 Street Light':'#e8f0fe', '🗑️ Garbage':'#fce8e6', '💧 Water Supply':'#e8f8ff', '🌳 Tree / Fallen Branch':'#e6f4ea', '🚧 Road Damage':'#fff3e0', 'Other':'#f3e8ff' };
            
            let typeKey = issue.issue_type;
            const icon  = icons[typeKey]  || '📌';
            const color = iconColors[typeKey] || '#f0f0f0';

            const statusClass = issue.status === 'Resolved'
                ? 'status-resolved'
                : (issue.status === 'In Progress'
                    ? 'status-progress'
                    : (issue.status === 'Rejected' ? 'status-rejected' : 'status-open'));
            let statusLabel = issue.status;

            // Approval action bar — only for this user's own resolved issues
            let actionsHtml = '';
            const isOwner = currentUserId > 0 && parseInt(issue.user_id) === currentUserId;
            if (isOwner && issue.status === 'Resolved' && parseInt(issue.is_citizen_approved) === 0) {
                if (parseInt(issue.can_reopen) === 1) {
                    actionsHtml = `
                        <div style="margin-top:12px;background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                            <div style="flex:1;font-size:13px;color:#7a5c00;"><strong>Work done?</strong> You have 24 hrs to verify.</div>
                            <button onclick="approveIssue(${issue.id})" style="background:#198754;color:#fff;border:none;padding:7px 14px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:500;">✅ Approve</button>
                            <button onclick="reopenIssue(${issue.id})" style="background:#dc3545;color:#fff;border:none;padding:7px 14px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:500;">❌ Not Done</button>
                        </div>`;
                }
            } else if (isOwner && issue.status === 'Resolved' && parseInt(issue.is_citizen_approved) === 1) {
                statusLabel = 'Approved & Closed';
            }

            const rejectionNoteHtml = issue.status === 'Rejected' && issue.rejection_remark
                ? `<div style="margin-top:10px;padding:10px 12px;border:1px solid #f5c2c7;background:#fff5f5;border-radius:8px;font-size:13px;color:#842029;"><strong>Rejection remark:</strong> ${issue.rejection_remark}</div>`
                : '';

            const item = document.createElement('div');
            item.className = 'issue-item';
            item.style.flexDirection = 'column';
            item.style.alignItems = 'stretch';
            item.innerHTML = `
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="issue-type-icon" style="background:${color};">${icon}</div>
                    <div class="issue-info">
                        <div class="issue-title">${issue.issue_type} – ${issue.location_text}</div>
                        <div class="issue-meta">${issue.ward || '—'} · Reported by ${issue.reported_by}</div>
                    </div>
                    <span class="issue-status ${statusClass}">${statusLabel}</span>
                </div>
                ${rejectionNoteHtml}
                ${actionsHtml}`;

            container.appendChild(item);
        }

        function submitIssueToDb(type, location, desc, ward, priority, photoInputId) {
            const formData = new FormData();
            formData.append('action', 'submitIssue');
            formData.append('type', type);
            formData.append('location', location);
            formData.append('description', desc);
            formData.append('ward', ward);
            formData.append('priority', priority);
            // Send user_id as a fallback in case the PHP session cookie expired
            const uid = sessionStorage.getItem('ct_user_id');
            if (uid) formData.append('user_id', uid);
            const photoInput = photoInputId ? document.getElementById(photoInputId) : null;
            if (photoInput && photoInput.files && photoInput.files[0]) {
                formData.append('photo', photoInput.files[0]);
            }

            fetch('../api/issues.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.photo_warning) {
                        showToast('✅ Report saved! ⚠️ ' + data.photo_warning, '#e67e22');
                    } else {
                        showToast('✅ Report submitted successfully!');
                    }
                    fetchIssues(); // Refresh list
                } else {
                    showToast('❌ ' + (data.message || 'Error submitting report'), '#C0392B');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('❌ Network error. Please try again.', '#C0392B');
            });
        }

        function submitQuickReport() {
            const type = document.getElementById('quickIssueType').value;
            const loc  = document.getElementById('quickLocation').value.trim();
            const desc = document.getElementById('quickDesc').value.trim();

            if (!type) { showToast('⚠️ Please select an issue type'); return; }
            if (!loc)  { showToast('⚠️ Please enter a location'); return; }

            submitIssueToDb(type, loc, desc, userWard, 'Normal', 'quickPhoto');
            
            document.getElementById('quickIssueType').value = '';
            document.getElementById('quickLocation').value  = '';
            document.getElementById('quickDesc').value      = '';
            document.getElementById('quickPhoto').value     = '';
            document.getElementById('quickPhotoPreview').style.display = 'none';
        }

        function submitModalReport() {
            const type = document.getElementById('modalIssueType').value;
            const loc  = document.getElementById('modalLocation').value.trim();
            const desc = document.getElementById('modalDesc').value.trim();
            const ward = document.getElementById('modalWard').value.trim() || userWard;
            const priority = document.getElementById('modalPriority').value;

            if (!type) { showToast('⚠️ Please select an issue type'); return; }
            if (!loc)  { showToast('⚠️ Please enter a location'); return; }

            submitIssueToDb(type, loc, desc, ward, priority, 'modalPhoto');
            closeModal();
            
            document.getElementById('modalIssueType').value  = '';
            document.getElementById('modalLocation').value   = '';
            document.getElementById('modalWard').value       = '';
            document.getElementById('modalDesc').value       = '';
            document.getElementById('modalPhoto').value      = '';
            document.getElementById('modalPhotoPreview').style.display = 'none';
        }

        function showToast(msg, color) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            if (color) t.style.background = color;
            t.classList.add('show');
            setTimeout(() => { t.classList.remove('show'); t.style.background = ''; }, 3200);
        }

        function approveIssue(id) {
            if (!confirm('Confirm the work has been completed. This will close the ticket.')) return;
            const fd = new FormData();
            fd.append('action', 'approveIssue');
            fd.append('issue_id', id);
            fetch('../api/issues.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { showToast('✅ Issue approved and closed!', '#198754'); fetchIssues(); fetchActivityFeed(); }
                    else alert(data.message || 'Action failed');
                });
        }

        function reopenIssue(id) {
            if (!confirm('Mark as not done? The issue will be re-raised with Urgent priority and the admin will be notified.')) return;
            const fd = new FormData();
            fd.append('action', 'reopenIssue');
            fd.append('issue_id', id);
            fetch('../api/issues.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { showToast('🔴 Issue re-raised as Urgent!', '#dc3545'); fetchIssues(); fetchActivityFeed(); }
                    else alert(data.message || 'Action failed');
                });
        }

        function fetchActivityFeed() {
            fetch('../api/issues.php?action=fetchNotifications')
                .then(r => r.json())
                .then(data => {
                    const feed = document.getElementById('activityFeed');
                    if (!data.success || data.notifications.length === 0) {
                        feed.innerHTML = '<div style="text-align:center;color:#aaa;font-size:13px;padding:20px;">No activity yet.</div>';
                        return;
                    }
                    // Update notification badge
                    const unread = data.unread_count || 0;
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.textContent = unread;

                    const dotClass = n => n.title.includes('Resolved') || n.title.includes('approved') ? 'dot-green'
                                       : n.title.includes('Urgent') || n.title.includes('reopen') ? 'dot-red' : 'dot-blue';

                    feed.innerHTML = data.notifications.slice(0, 6).map(n => {
                        const diff = (Date.now() - new Date(n.created_at).getTime()) / 1000;
                        const ago  = diff < 60 ? 'Just now' : diff < 3600 ? `${Math.floor(diff/60)}m ago`
                                   : diff < 86400 ? `${Math.floor(diff/3600)}h ago` : `${Math.floor(diff/86400)}d ago`;
                        return `
                        <div class="activity-item">
                            <div class="activity-dot ${dotClass(n)}"></div>
                            <div>
                                <div class="activity-text"><strong>${n.title}:</strong> ${n.message}</div>
                                <div class="activity-time">${ago}</div>
                            </div>
                        </div>`;
                    }).join('');
                })
                .catch(() => {
                    document.getElementById('activityFeed').innerHTML =
                        '<div style="text-align:center;color:#aaa;font-size:13px;padding:20px;">Log in to see activity.</div>';
                });
        }
    </script>
</body>
</html>
