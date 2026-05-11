<?php
require_once __DIR__ . '/../src/Auth.php';
Hospital\Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - AIH System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2.37">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <!-- Dynamic Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-layout">
        <div class="page-header">
            <h1 style="color: #0f172a; font-size: 1.5rem; opacity: 0.8;">Patient Directory</h1>
        </div>

        <div class="card">
            <div class="filter-section">
                <div class="form-group">
                    <label>Search MR# / Name</label>
                    <input type="text" id="search-input" placeholder="Type name or MR#...">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select id="dept-filter">
                        <option value="">All Departments</option>
                        <option value="OPD">OPD</option>
                        <option value="IPD">IPD</option>
                        <option value="EMERGENCY">EMERGENCY</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                    <button id="apply-filters" class="btn primary" style="padding: 0.8rem 1.5rem; width: auto;">Search</button>
                    <button id="reset-filters" class="btn" style="padding: 0.8rem; background: #f1f5f9; color: #475569; width: auto;">Reset</button>
                </div>
            </div>

            <div class="table-container">
                <table id="patients-table">
                    <thead>
                        <tr>
                            <th><span class="header-pill bg-red">MR#</span></th>
                            <th><span class="header-pill bg-blue">Patient Name</span></th>
                            <th><span class="header-pill bg-green">Rack</span></th>
                            <th><span class="header-pill bg-red">Department</span></th>
                            <th><span class="header-pill bg-blue">Admission Date</span></th>
                            <th style="min-width: 250px; text-align: right;"><span class="header-pill bg-slate">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="patient-data">
                        <!-- Data will be loaded via JS -->
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 0.9rem;">
                <div id="table-info">Showing 0 entries</div>
                <div id="pagination-controls" style="display: flex; gap: 0.5rem;">
                    <!-- Pagination will be added via JS -->
                </div>
            </div>
        </div>
    </main>

    <!-- History Modal -->
    <div id="history-modal" class="modal">
        <div class="modal-content" style="max-width: 850px; background: #f8fafc; padding: 0; overflow: hidden; border: none;">
            <!-- Modal Header -->
            <div style="background: white; padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 id="history-title" style="margin: 0; font-size: 1.4rem; color: #1e293b;">Medical History</h2>
                    <p id="history-subtitle" style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b; font-weight: 500;">Manage and reorder patient documents</p>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div id="sync-status" style="display: none; font-size: 0.8rem; font-weight: 700; color: var(--primary); background: #eff6ff; padding: 0.5rem 1rem; border-radius: 2rem; border: 1px solid #dbeafe;">
                        <span class="pulse" style="display: inline-block; width: 8px; height: 8px; background: var(--primary); border-radius: 50%; margin-right: 6px;"></span>
                        Syncing Changes...
                    </div>
                    <span class="close-modal">&times;</span>
                </div>
            </div>

            <div style="padding: 1.5rem 2rem; background: white; border-bottom: 1px solid #e2e8f0;">
                <form id="history-upload-form" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <input type="hidden" id="history-upload-mrn" name="mrn">
                        <div class="modern-upload-area" style="flex-grow: 1; position: relative;">
                            <input type="file" name="files[]" id="history-files" multiple required 
                                   style="position: absolute; inset: 0; opacity: 0; cursor: pointer; z-index: 2;"
                                   onchange="document.getElementById('upload-hint').textContent = this.files.length + ' files selected'">
                            <div style="background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 12px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#64748b" width="20" height="20" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <span id="upload-hint" style="font-size: 0.9rem; color: #475569; font-weight: 600;">Click or Drag to add new records/photos</span>
                            </div>
                        </div>
                        <button type="submit" class="btn primary" style="padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700;">
                            Upload
                        </button>
                    </div>

                    <!-- Progress Bar for Modal -->
                    <div id="modal-upload-progress" class="upload-progress-wrapper" style="display: none; margin-bottom: 0; margin-top: 0.5rem; padding: 0.75rem;">
                        <div class="progress-text-container">
                            <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary);">Syncing with Server...</span>
                            <span id="modal-upload-percent" style="font-size: 0.8rem; font-weight: 700; color: var(--text-dark);">0%</span>
                        </div>
                        <div class="progress-container" style="height: 6px;">
                            <div id="modal-upload-bar" class="progress-bar"></div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="history-card-container" id="history-data">
                <!-- Cards will be injected here -->
            </div>

            <div style="padding: 1rem 2rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <p style="margin: 0; font-size: 0.8rem; color: #94a3b8;">
                    Drag files to reorder the Master PDF
                </p>
                <div id="history-count-badge" style="font-size: 0.75rem; font-weight: 700; color: #64748b;">
                    0 Records
                </div>
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
                <button type="submit" class="btn primary" style="width: 100%;">Confirm Issuance</button>
            </form>
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

    <div id="notifications"></div>

    <script src="assets/app.js?v=2.38"></script>
    <script>
        window.isPatientPage = true;
        window.isAdmin = <?php echo Hospital\Auth::isAdmin() ? 'true' : 'false'; ?>;
    </script>
</body>
</html>
