<?php
require_once __DIR__ . '/../src/Auth.php';
Hospital\Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission - AIH Medical Records System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2.31">
</head>
<body>
    <!-- Sidebar -->
    <!-- Dynamic Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-layout">
        <div class="page-header">
            <h1 style="color: #0f172a; font-size: 1.5rem; opacity: 0.8;">Patient Admission</h1>
        </div>

        <div class="card" style="margin-bottom: 2.5rem; background: var(--sidebar-bg); color: white; border: none; overflow: hidden; position: relative;">
            <div style="position: relative; z-index: 2;">
                <h2 style="color: white; margin-bottom: 0.5rem; font-size: 2rem;">Welcome to AIH Medical Records</h2>
                <p style="opacity: 0.9; font-size: 1.1rem; max-width: 600px;">Manage patient registrations, medical file history, and issuance tracking from a single secure portal.</p>
            </div>
            <div style="position: absolute; right: -50px; top: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
        </div>

        <section class="patient-actions" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2.5rem;">
            <div class="card">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <div style="width: 48px; height: 48px; background: var(--primary-light); color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="24" height="24" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    </div>
                    <h2 style="margin-bottom: 0;">Register New Patient</h2>
                </div>
                <form id="register-form" action="api.php?action=register" method="POST">
                    <div class="form-group">
                        <label for="mrn">MR# (Medical Record Number)</label>
                        <input type="text" id="mrn" name="mrn" placeholder="e.g. 12345" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Patient Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter full name..." required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="OPD">OPD</option>
                                <option value="IPD">IPD</option>
                                <option value="EMERGENCY">EMERGENCY</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reg_date">Admission Date</label>
                            <input type="date" id="reg_date" name="reg_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rack_number">Rack Number / Location</label>
                        <input type="text" id="rack_number" name="rack_number" placeholder="e.g. Rack-A-01">
                    </div>
                    <button type="submit" class="btn primary" style="width: 100%;">Register & Create Master File</button>
                </form>
            </div>

            <div style="display: flex; flex-direction: column; gap: 2.5rem;">
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                        <div style="width: 48px; height: 48px; background: var(--secondary-light); color: var(--secondary); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="24" height="24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        </div>
                        <h2 style="margin-bottom: 0;">Append Record</h2>
                    </div>

                    <!-- Upload Progress Bar -->
                    <div id="upload-progress-container" class="upload-progress-wrapper" style="display: none;">
                        <div class="progress-text-container">
                            <span id="upload-status">Uploading...</span>
                            <span id="upload-percent">0%</span>
                        </div>
                        <div class="progress-container">
                            <div id="upload-progress-bar" class="progress-bar"></div>
                        </div>
                    </div>

                    <form id="append-form" action="api.php?action=append" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="append_mrn">MR# / File Number</label>
                            <input type="text" id="append_mrn" name="mrn" placeholder="Search Patient..." required>
                        </div>
                        <div class="form-group">
                            <label for="append_rack_number">Location</label>
                            <input type="text" id="append_rack_number" placeholder="Automatic lookup..." disabled style="background: #f1f5f9;">
                        </div>
                        <div class="form-group">
                            <label>Selection Scan / Report</label>
                            <div class="file-upload" onclick="document.getElementById('files').click()">
                                <input type="file" id="files" name="files[]" multiple onchange="handleMultipleFiles(this)">
                                <div class="upload-mask">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#64748b" width="32" height="32" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <br><span id="files-label">Choose Files</span>
                                </div>
                            </div>
                        </div>

                        <div id="file-preview-container" style="display: none; margin-top: 1rem;">
                            <div id="file-preview-list"></div>
                        </div>

                        <button type="submit" class="btn primary" style="width: 100%; background: #64748b; margin-top: 1rem;">Upload to File</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <div id="notifications"></div>

    <script src="assets/app.js?v=2.31"></script>
    <script>
        function handleMultipleFiles(input) {
            const label = document.getElementById('files-label');
            label.textContent = input.files.length + ' files selected';
        }
    </script>
</body>
</html>
