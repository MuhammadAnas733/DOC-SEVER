<?php
require_once __DIR__ . '/../src/Auth.php';
Hospital\Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issued Files Oversight - AIH System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2.31">
</head>
<body>
    <!-- Sidebar -->
    <!-- Dynamic Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-layout">
        <div class="page-header">
            <h1 style="color: #0f172a; font-size: 1.5rem; opacity: 0.8;">
                <?php 
                    if (($_GET['filter'] ?? '') === 'weekly') echo 'Weekly Outgoing Files Oversight';
                    elseif (($_GET['filter'] ?? '') === 'monthly') echo 'Monthly Outgoing Files Oversight';
                    else echo 'Issued Files Oversight';
                ?>
            </h1>
        </div>

        <div class="card">
            <div class="action-bar" style="margin-bottom: 2rem;">
                <h2 style="margin-bottom: 0;">Issuance Tracker</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <input type="text" id="issue-search" placeholder="Search by name, MR#..." style="width: 320px;">
                    <button id="new-issue-btn" class="btn primary" style="width: auto; padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="18" height="18" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        Issue File
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table id="issued-table" style="width: 100%; table-layout: fixed;">
                    <thead>
                        <tr>
                            <th style="width: 15%;"><span class="header-pill bg-red">MR#</span></th>
                            <th style="width: 35%;"><span class="header-pill bg-blue">Patient Name</span></th>
                            <th style="width: 15%;"><span class="header-pill bg-orange">Status</span></th>
                            <th style="width: 35%; text-align: right;"><span class="header-pill bg-slate">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="issued-data">
                        <!-- JS loaded -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- View Details Modal -->
    <div id="view-details-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close-modal-view">&times;</span>
            <h2 style="margin-bottom: 2rem; color: #0f172a;">Issuance Details</h2>
            
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.5rem;">Patient Name</label>
                        <p id="view-patient-name" style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0;"></p>
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.5rem;">MR#</label>
                        <p id="view-mrn" style="font-size: 1.1rem; font-weight: 700; color: #0060ac; margin: 0;"></p>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Issued To</label>
                    <input type="text" id="view-issued-to" readonly style="background: #f1f5f9; font-weight: 600;">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" id="view-phone" readonly style="background: #f1f5f9;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" id="view-dept" readonly style="background: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <input type="text" id="view-status" readonly style="background: #f1f5f9; font-weight: 700;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Issue Date</label>
                    <input type="text" id="view-issue-date" readonly style="background: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Return Date</label>
                    <input type="text" id="view-return-date" readonly style="background: #f1f5f9;">
                </div>
            </div>

            <div class="form-group">
                <label>Remarks</label>
                <textarea id="view-remarks" readonly style="background: #f1f5f9;" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button class="btn" style="background: #f1f5f9; color: #475569; width: auto;" onclick="document.getElementById('view-details-modal').style.display='none'">Close</button>
            </div>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="return-modal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close-modal-return">&times;</span>
            <h2 style="margin-bottom: 1.5rem;">Confirm File Return</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label>File Currently Issued To:</label>
                    <input type="text" id="return-issued-to" readonly style="background: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Phone Number:</label>
                    <input type="text" id="return-phone" readonly style="background: #f1f5f9;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" id="return-dept" readonly style="background: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Issue Date:</label>
                    <input type="text" id="return-issue-date" readonly style="background: #f1f5f9;">
                </div>
            </div>

            <div class="form-group">
                <label>Remarks from Issuance:</label>
                <textarea id="return-remarks" readonly style="background: #f1f5f9;" rows="2"></textarea>
            </div>

            <div class="form-group" style="padding: 1rem; background: #f0fdf4; border-radius: 0.8rem; border: 1px solid #dcfce7; margin-bottom: 1.5rem;">
                <label style="color: #16a34a; font-weight: 700;">Automatic Return Date:</label>
                <input type="text" id="return-current-date" readonly style="background: transparent; border: none; font-weight: bold; color: #15803d; padding: 0;">
            </div>

            <p style="margin-bottom: 1.5rem; color: var(--text-muted); line-height: 1.5; font-size: 0.9rem;">
                Are you sure this file has been physically returned to the rack?
            </p>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button id="cancel-return-btn" class="btn" style="background: #f1f5f9; color: #475569; width: auto;">Cancel</button>
                <button id="confirm-return-btn" class="btn primary" style="width: auto;">Confirm Return</button>
            </div>
        </div>
    </div>

    <!-- Issue Modal -->
    <div id="issue-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal-issue">&times;</span>
            <h2 id="issue-title" style="margin-bottom: 2rem;">Issue Medical File</h2>
            <form id="issue-form">
                <input type="hidden" id="issue-mrn" name="mrn">
                <div class="form-group">
                    <label>Patient Name</label>
                    <input type="text" id="issue-patient-name" readonly style="background: #f1f5f9;">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Issued To</label>
                        <input type="text" name="issued_to" required placeholder="Enter name...">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone_number" placeholder="Enter phone number...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" required>
                            <option value="OPD">OPD</option>
                            <option value="IPD">IPD</option>
                            <option value="EMERGENCY">EMERGENCY</option>
                            <option value="Admin">Admin</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Issue Date</label>
                        <input type="date" name="issue_date" required id="default-issue-date">
                    </div>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" placeholder="Purpose..." rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" class="btn" style="background: #f1f5f9; color: #475569; width: auto;" onclick="document.getElementById('issue-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn primary" style="width: auto;">Confirm Issuance</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notifications"></div>

    <script src="assets/app.js?v=2.35"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
             const urlParams = new URLSearchParams(window.location.search);
             const filter = urlParams.get('filter') || '';
             fetchIssuedFiles('', filter);
             const searchInput = document.getElementById('issue-search');
             let st;
             if (searchInput) {
                 searchInput.addEventListener('input', (e) => {
                     clearTimeout(st);
                     st = setTimeout(() => { fetchIssuedFiles(e.target.value, filter); }, 300);
                 });
             }
        });

        let issuanceData = []; // Global storage

        async function fetchIssuedFiles(search = '', filter = '') {
            const tbody = document.getElementById('issued-data');
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem;">Loading data...</td></tr>';
            try {
                const response = await fetch(`api.php?action=list_issuances&search=${encodeURIComponent(search)}&filter=${filter}`);
                const data = await response.json();
                issuanceData = data; // Store globally
                tbody.innerHTML = '';
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem;">No results found.</td></tr>';
                    return;
                }
                data.forEach((item, index) => {
                    const tr = document.createElement('tr');
                    
                    const status = item.status || 'Available';
                    let statusClass = 'status-badge';
                    if (status === 'Issued') statusClass += ' status-issued';
                    else if (status === 'Returned') statusClass += ' status-returned';
                    else statusClass += ' status-available'; // custom style needed or use default
                    
                    let actions = '';
                    if (item.id) {
                        actions += `<button onclick="event.stopPropagation(); showViewModal(${index})" class="btn-small btn-view" style="font-size: 0.75rem; padding: 0.5rem 0.8rem;">View</button>`;
                        actions += `<button onclick="event.stopPropagation(); toggleIssuanceHistory(${item.patient_id}, this.closest('tr'))" class="btn-small btn-view" style="font-size: 0.75rem; padding: 0.5rem 0.8rem;">Tracker</button>`;
                    }

                    if (status === 'Issued') {
                        const rem = (item.remarks || '').replace(/'/g, "\\'").replace(/\n/g, ' ');
                        const phone = (item.phone_number || '').replace(/'/g, "\\'");
                        const dept = (item.department || '').replace(/'/g, "\\'");
                        actions += `<button onclick="event.stopPropagation(); showReturnModal(${item.id}, '${item.issued_to.replace(/'/g, "\\'")}', '${phone}', '${dept}', '${item.issue_date}', '${rem}')" class="btn-small btn-return" style="font-size: 0.75rem; padding: 0.5rem 0.8rem;">Return</button>`;
                    } else if (status === 'Returned' || status === 'Available') {
                        actions += `<button onclick="event.stopPropagation(); showIssueModal('${item.mrn}', '${item.full_name.replace(/'/g, "\\'")}')" class="btn-small btn-view" style="font-size: 0.75rem; padding: 0.5rem 0.8rem; background: #22c55e; color: white; border: none;">Issue</button>`;
                    }
                    
                    tr.innerHTML = `
                        <td><strong style="font-size: 0.9rem;">${item.mrn}</strong></td>
                        <td><strong style="font-size: 0.9rem;">${item.full_name}</strong></td>
                        <td><span class="${statusClass}" style="font-size: 0.75rem; padding: 0.4rem 0.7rem;">${status}</span></td>
                        <td>
                            <div class="action-cell" style="justify-content: flex-end; gap: 0.5rem;">
                                ${actions}
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (err) { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:red;">Connection error.</td></tr>'; }
        }

        function showIssueModal(mrn, name) {
            document.getElementById('issue-mrn').value = mrn;
            document.getElementById('issue-patient-name').value = name;
            document.getElementById('default-issue-date').value = new Date().toISOString().split('T')[0];
            document.getElementById('issue-modal').style.display = 'block';
        }

        // Handle Issue Form Submission
        document.getElementById('issue-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('api.php?action=issue_file', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    document.getElementById('issue-modal').style.display = 'none';
                    e.target.reset();
                    // Refresh listing
                    const searchInput = document.getElementById('issue-search');
                    const urlParams = new URLSearchParams(window.location.search);
                    fetchIssuedFiles(searchInput.value, urlParams.get('filter') || '');
                } else {
                    showToast(result.error, 'error');
                }
            } catch (err) {
                showToast('Request failed', 'error');
            }
        });

        // "Issue File" top button - focus search or something? 
        // User said they want "issue button on this page just"
        // I'll make it so the top button can open a search prompt or just focus search.
        document.getElementById('new-issue-btn').addEventListener('click', () => {
            document.getElementById('issue-search').focus();
            showToast('Search for a patient or MR# to issue their file.', 'info');
        });

        document.querySelector('.close-modal-issue').addEventListener('click', () => {
            document.getElementById('issue-modal').style.display = 'none';
        });

        function showViewModal(index) {
            const data = issuanceData[index];
            document.getElementById('view-patient-name').textContent = data.full_name;
            document.getElementById('view-mrn').textContent = data.mrn;
            document.getElementById('view-issued-to').value = data.issued_to;
            document.getElementById('view-phone').value = data.phone_number || '-';
            document.getElementById('view-dept').value = data.department;
            document.getElementById('view-status').value = data.status;
            document.getElementById('view-issue-date').value = data.issue_date;
            document.getElementById('view-return-date').value = data.return_date || '-';
            document.getElementById('view-remarks').value = data.remarks || 'No remarks';
            
            // Apply color to status field
            const statusField = document.getElementById('view-status');
            if (data.status === 'Issued') {
                statusField.style.color = '#ef4444';
            } else {
                statusField.style.color = '#22c55e';
            }
            
            document.getElementById('view-details-modal').style.display = 'block';
        }

        // Close modal handler
        document.querySelector('.close-modal-view')?.addEventListener('click', () => {
            document.getElementById('view-details-modal').style.display = 'none';
        });
    </script>
</body>
</html>
