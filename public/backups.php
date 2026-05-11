<?php
require_once __DIR__ . '/../src/Auth.php';
\Hospital\Auth::requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Protection - AIH Records</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2.31">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .backup-hero {
            background: linear-gradient(135deg, #0060ac 0%, #004a85 100%);
            border-radius: 2rem;
            padding: 3rem;
            color: white;
            margin-bottom: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 96, 172, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-card {
            display: flex;
            gap: 20px;
            margin-bottom: 2rem;
        }

        .protection-btn {
            flex: 1;
            padding: 2.5rem;
            border-radius: 1.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .protection-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 96, 172, 0.1);
            border-color: #0060ac;
        }

        .protection-btn i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .protection-btn h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #1e293b; }
        .protection-btn p { font-size: 0.9rem; color: #64748b; }

        .backup-item {
            display: grid;
            grid-template-columns: 60px 1fr auto auto;
            align-items: center;
            gap: 1.5rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
        }

        .backup-item:hover { background: #f8fafc; }

        .backup-icon {
            width: 44px;
            height: 44px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .btn-restore {
            background: #f0fdf4 !important;
            color: #16a34a !important;
            border: 1px solid #dcfce7 !important;
        }

        .btn-restore:hover { background: #16a34a !important; color: white !important; }

        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 5000;
            backdrop-filter: blur(10px);
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #0060ac;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .badge-latest {
            background: #0060ac;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-left: 10px;
        }
    </style>
</head>
<body>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <p id="loadingText" style="font-weight: 800; color: #0060ac; font-size: 1.2rem;">Processing System Action...</p>
        <p style="color: #64748b; margin-top: 10px;">Please do not close this window or refresh the page.</p>
    </div>

    <!-- Dynamic Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-layout">
        <div class="backup-hero">
            <div>
                <h1 style="font-family: 'Outfit'; font-weight: 700; font-size: 2.5rem;">System Protection</h1>
                <p style="font-size: 1.1rem; opacity: 0.9;">Secure one-click backup and restoration for your medical database.</p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700; opacity: 0.8;">Status</div>
                <div style="font-size: 1.25rem; font-weight: 700;"><i class="fas fa-check-circle"></i> Fully Protected</div>
            </div>
        </div>

        <div class="action-card">
            <div class="protection-btn" onclick="createBackup()">
                <i class="fas fa-cloud-upload-alt" style="color: #0060ac;"></i>
                <h3>Backup Now</h3>
                <p>Instantly save all patient data & files</p>
            </div>
            <div class="protection-btn" onclick="document.getElementById('restoreFile').click()">
                <i class="fas fa-file-import" style="color: #16a34a;"></i>
                <h3>Restore System</h3>
                <p>Upload a .zip backup to restore data</p>
                <input type="file" id="restoreFile" style="display: none;" accept=".zip" onchange="uploadRestore(this)">
            </div>
                <div class="protection-btn" onclick="openScheduleModal()" style="border-radius: 2rem; border: 2px solid #0060ac; background: #f0f7ff;">
                    <i class="fas fa-history" style="color: #0060ac;"></i>
                    <h3 style="font-family: 'Outfit';">Auto-Protect</h3>
                    <p style="margin-bottom: 0.5rem;">Enabled: Runs Daily at <span id="current-schedule">...</span></p>
                    <div id="last-auto-status" style="font-size: 0.75rem; color: #059669; font-weight: 600; display: none;">
                        <i class="fas fa-check-circle"></i> Last Success: <span id="last-backup-time">Never</span>
                    </div>
                </div>
        </div>

        <div class="card" style="margin-top: 2.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-vault" style="color: #0060ac; font-size: 1.5rem;"></i>
                    <h2 style="font-family: 'Outfit'; margin-bottom: 0;">Restore Vault</h2>
                </div>
                <button onclick="loadBackups()" class="btn btn-small btn-view" style="font-size: 0.8rem; padding: 0.5rem 1rem;">
                    <i class="fas fa-sync-alt" id="refresh-icon"></i> Refresh List
                </button>
            </div>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">Select a point-in-time snapshot below to restore the entire system. <strong style="color: #dc2626;">Warning: Restore will overwrite current data.</strong></p>
            
            <div id="backupList">
                <div style="text-align: center; padding: 3rem;">
                    <div class="spinner" style="margin: 0 auto 1rem;"></div>
                    <p style="color: #64748b;">Loading restore points...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Schedule Modal -->
    <div id="schedule-modal" class="modal">
        <div class="modal-content" style="max-width: 400px; text-align: center; padding: 2.5rem;">
            <span class="close-modal-schedule" onclick="closeScheduleModal()">&times;</span>
            <div style="width: 70px; height: 70px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class="fas fa-clock" style="color: #2563eb; font-size: 2rem;"></i>
            </div>
            <h2 style="font-family: 'Outfit'; margin-bottom: 0.5rem;">Auto-Backup Schedule</h2>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">Set the daily automatic system snapshot time.</p>
            
            <div class="form-group" style="text-align: left;">
                <label style="font-weight: 700; color: #1e293b;">Daily Execution Time</label>
                <input type="time" id="backup-time-input" step="1" style="font-size: 1.5rem; text-align: center; padding: 1rem; border-radius: 12px; border: 2px solid #e2e8f0; width: 100%; font-family: 'Outfit';">
            </div>

            <button onclick="saveSchedule()" class="btn primary" style="width: 100%; margin-top: 2rem; padding: 1.25rem; font-size: 1.1rem; border-radius: 12px; box-shadow: 0 10px 20px rgba(0, 96, 172, 0.2);">
                Update Schedule
            </button>
        </div>
    </div>

    <div id="notifications"></div>

    <script>
        function showToast(msg, type = 'info') {
            const container = document.getElementById('notifications');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerText = msg;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }

        async function createBackup() {
            document.getElementById('loadingText').innerText = 'Taking System Snapshot...';
            document.getElementById('loadingOverlay').style.display = 'flex';
            try {
                const response = await fetch('api.php?action=backup_create');
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success') {
                        showToast('Full system backup successful.', 'success');
                        loadBackups();
                    } else {
                        showToast('Backup error: ' + (data.message || data.error), 'error');
                    }
                } catch (jsonErr) {
                    console.error("JSON Error:", text);
                    showToast('Server returned invalid data during backup.', 'error');
                }
            } catch (e) { 
                console.error("Fetch Error:", e);
                showToast('Connection failed during backup creation.', 'error'); 
            }
            finally { document.getElementById('loadingOverlay').style.display = 'none'; }
        }

        async function restoreSystem(filename) {
            if (!confirm(`CRITICAL ACTION: Are you sure you want to restore the entire system to the snapshot from ${filename}? This will overwrite all current patient records and data.`)) return;
            
            const pin = prompt("Please enter 'RESTORE' to confirm this action:");
            if (pin !== 'RESTORE') return;

            document.getElementById('loadingText').innerText = 'Restoring System Data...';
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            try {
                const formData = new FormData();
                formData.append('filename', filename);
                const response = await fetch('api.php?action=backup_restore', {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success') {
                        alert('System successfully restored! The page will now reload.');
                        window.location.reload();
                    } else {
                        showToast('Restore Failed: ' + (data.error || 'Server Error'), 'error');
                    }
                } catch (jsonErr) {
                    console.error("JSON Error:", text);
                    showToast('Server error during restoration processing.', 'error');
                }
            } catch (e) { 
                console.error("Fetch Error:", e);
                showToast('Connection failed during restoration.', 'error'); 
            }
            finally { document.getElementById('loadingOverlay').style.display = 'none'; }
        }

        async function uploadRestore(input) {
            if (!input.files || input.files.length === 0) return;
            
            if (!confirm(`CRITICAL ACTION: You are about to upload and restore the system from a manual backup file. This will PERMANENTLY overwrite all current database records and medical files.`)) {
                input.value = '';
                return;
            }

            const pin = prompt("Please type 'RESTORE' to confirm this system overwrite:");
            if (pin !== 'RESTORE') {
                input.value = '';
                return;
            }

            const file = input.files[0];
            const formData = new FormData();
            formData.append('backup_file', file);

            document.getElementById('loadingText').innerText = 'Uploading & Restoring System...';
            document.getElementById('loadingOverlay').style.display = 'flex';

            try {
                const response = await fetch('api.php?action=backup_upload_restore', {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success') {
                        alert('System successfully restored from file! The page will now reload.');
                        window.location.reload();
                    } else {
                        showToast('Upload Restore Failed: ' + (data.error || 'Check file format'), 'error');
                    }
                } catch (jsonErr) {
                    console.error("JSON Error:", text);
                    showToast('Server returned invalid data during upload.', 'error');
                }
            } catch (e) { 
                console.error("Upload/Fetch Error:", e);
                showToast('Upload failed due to connection error.', 'error'); 
            }
            finally { 
                document.getElementById('loadingOverlay').style.display = 'none';
                input.value = '';
            }
        }

        async function deleteBackup(filename) {
            if (!confirm(`Delete this backup point?`)) return;
            const formData = new FormData();
            formData.append('filename', filename);
            await fetch('api.php?action=backup_delete', { method: 'POST', body: formData });
            loadBackups();
        }

        async function loadBackups() {
            const list = document.getElementById('backupList');
            const icon = document.getElementById('refresh-icon');
            if (icon) icon.classList.add('fa-spin');

            try {
                const res = await fetch('api.php?action=backup_list');
                const data = await res.json();
                
                if (data.length === 0) {
                    list.innerHTML = '<div style="text-align: center; padding: 4rem; color: #94a3b8;">No restore points found. Start by creating a manual backup.</div>';
                    return;
                }

                // Update the Auto-Protect card with the latest backup time
                const latest = data[0];
                const lastStatus = document.getElementById('last-auto-status');
                const lastTime = document.getElementById('last-backup-time');
                if (lastStatus && lastTime) {
                    lastStatus.style.display = 'block';
                    lastTime.innerText = latest.date;
                }

                list.innerHTML = data.map((b, i) => `
                    <div class="backup-item" style="${i === 0 ? 'border-left: 4px solid #0060ac; background: #f0f7ff;' : ''}">
                        <div class="backup-icon" style="${i === 0 ? 'background: #0060ac; color: white;' : ''}"><i class="fas fa-file-shield"></i></div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <strong style="color: #1e293b; font-size: 1.05rem;">${b.name}</strong>
                                ${i === 0 ? '<span class="badge-latest">LATEST POINT</span>' : ''}
                            </div>
                            <div style="font-size: 0.85rem; color: #64748b; margin-top: 0.2rem;">
                                <i class="far fa-calendar-alt"></i> ${b.date} • <i class="fas fa-hdd"></i> ${b.size}
                            </div>
                        </div>
                        <div class="action-cell">
                            <button class="btn btn-small btn-restore" onclick="restoreSystem('${b.name}')">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <a href="api.php?action=backup_download&filename=${b.name}" class="btn btn-small btn-view" title="Download to PC" download>
                                <i class="fas fa-download"></i>
                            </a>
                            <button class="btn btn-small btn-issue" style="background:#fee2e2; color:#dc2626; border-color:#fecaca;" onclick="deleteBackup('${b.name}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            } catch (err) {
                list.innerHTML = '<div style="text-align: center; padding: 2rem; color: #ef4444;">Failed to load vault. Check connection.</div>';
            } finally {
                if (icon) setTimeout(() => icon.classList.remove('fa-spin'), 500);
            }
        }

        // Auto-refresh the list every 60 seconds to catch completed auto-backups
        setInterval(loadBackups, 60000);

        async function loadSchedule() {
            try {
                const res = await fetch('api.php?action=backup_get_schedule');
                const data = await res.json();
                if (data.status === 'success') {
                    const time = data.time;
                    document.getElementById('backup-time-input').value = time;
                    
                    // Format for display (e.g., 02:00 -> 2:00 AM)
                    // Format for display (e.g., 02:00:55 -> 2:00:55 AM)
                    const parts = time.split(':');
                    const h = parts[0];
                    const m = parts[1];
                    const s = parts[2] || '00';
                    const hour = parseInt(h);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    document.getElementById('current-schedule').innerText = `${displayHour}:${m}:${s} ${ampm}`;
                }
            } catch (e) { console.error("Failed to load schedule"); }
        }

        function openScheduleModal() {
            document.getElementById('schedule-modal').style.display = 'flex';
        }

        function closeScheduleModal() {
            document.getElementById('schedule-modal').style.display = 'none';
        }

        async function saveSchedule() {
            const time = document.getElementById('backup-time-input').value;
            if (!time) return;

            const formData = new FormData();
            formData.append('time', time);

            document.getElementById('loadingText').innerText = 'Updating Backup Schedule...';
            document.getElementById('loadingOverlay').style.display = 'flex';
            closeScheduleModal();

            try {
                const res = await fetch('api.php?action=backup_set_schedule', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    loadSchedule();
                } else if (data.status === 'partial_success') {
                    showToast(data.message, 'warning');
                    loadSchedule();
                } else {
                    showToast(data.error || 'Failed to update schedule', 'error');
                }
            } catch (e) { showToast('Connection failed.', 'error'); }
            finally { document.getElementById('loadingOverlay').style.display = 'none'; }
        }

        window.onload = () => {
            loadBackups();
            loadSchedule();
        };

        window.onclick = (e) => {
            if (e.target.classList.contains('modal')) e.target.style.display = 'none';
        }
    </script>
</body>
</html>
