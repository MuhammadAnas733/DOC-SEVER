document.addEventListener('DOMContentLoaded', () => {
    // Determine which page we are on
    const isPatientPage = window.isPatientPage || false;

    if (isPatientPage) {
        initPatientPage();
    } else {
        initDashboard();
    }

    // Modals Close logic
    const modals = document.querySelectorAll('.modal');
    modals.forEach(m => {
        const closeBtn = m.querySelector('.close-modal, .close-modal-issue');
        if (closeBtn) closeBtn.onclick = () => m.style.display = 'none';
    });

    // Initialize Return Modal globally
    initReturnModal();
});


function initDashboard() {
    // Register Form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(registerForm);
            await submitForm('register', formData);
            registerForm.reset();
        });
    }

    // Append Form
    const appendForm = document.getElementById('append-form');
    if (appendForm) {
        appendForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(appendForm);

            const progressContainer = document.getElementById('upload-progress-container');
            const progressBar = document.getElementById('upload-progress-bar');
            const percentText = document.getElementById('upload-percent');
            const statusText = document.getElementById('upload-status');
            const submitBtn = appendForm.querySelector('button[type="submit"]');

            if (progressContainer) progressContainer.style.display = 'block';
            if (submitBtn) submitBtn.disabled = true;

            try {
                const result = await uploadFileWithProgress('append', formData, (percent) => {
                    if (progressBar) progressBar.style.width = percent + '%';
                    if (percentText) percentText.textContent = Math.round(percent) + '%';
                    if (statusText && percent === 100) statusText.textContent = 'Processing file...';
                });

                if (result.success) {
                    showToast(result.message, 'success');
                    appendForm.reset();

                    // Clear file preview
                    const filePreviewContainer = document.getElementById('file-preview-container');
                    if (filePreviewContainer) filePreviewContainer.style.display = 'none';

                    // Clear rack number after reset
                    const rackInput = document.getElementById('append_rack_number');
                    if (rackInput) rackInput.value = '';

                    // Reset file selection state
                    if (typeof selectedFiles !== 'undefined') {
                        selectedFiles = [];
                    }

                    // Auto-refresh page after 1.5 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(result.error, 'error');
                }
            } catch (err) {
                showToast('Upload failed', 'error');
            } finally {
                if (progressContainer) {
                    setTimeout(() => {
                        progressContainer.style.display = 'none';
                        if (progressBar) progressBar.style.width = '0%';
                        if (percentText) percentText.textContent = '0%';
                        if (statusText) statusText.textContent = 'Uploading...';
                    }, 2000);
                }
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        // Rack Lookup
        const appendMrnInput = document.getElementById('append_mrn');
        const appendRackInput = document.getElementById('append_rack_number');
        if (appendMrnInput && appendRackInput) {
            let lookupTimer;
            const performLookup = async () => {
                const val = appendMrnInput.value.trim();
                if (val.length === 0) {
                    appendRackInput.value = '';
                    return;
                }
                try {
                    console.log('Fetching rack for:', val);
                    const response = await fetch(`api.php?action=get_patient&mrn=${encodeURIComponent(val)}`);
                    const result = await response.json();
                    if (result.success && result.patient) {
                        appendRackInput.value = result.patient.rack_number || 'No Rack Assigned';
                    } else {
                        appendRackInput.value = 'Not found';
                    }
                } catch (err) {
                    console.error('Rack lookup error:', err);
                    appendRackInput.value = 'Error';
                }
            };

            appendMrnInput.addEventListener('input', () => {
                clearTimeout(lookupTimer);
                lookupTimer = setTimeout(performLookup, 500);
            });

            appendMrnInput.addEventListener('blur', performLookup);
        }
    }
}


function initPatientPage() {
    fetchPatients();
    initHistoryUpload();

    const searchInput = document.getElementById('search-input');
    const deptFilter = document.getElementById('dept-filter');
    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    const applyBtn = document.getElementById('apply-filters');
    const resetBtn = document.getElementById('reset-filters');

    let debounceTimer;
    window.triggerFilter = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const params = new URLSearchParams({
                action: 'list',
                search: searchInput ? searchInput.value : '',
                department: deptFilter ? deptFilter.value : '',
                start_date: startDate ? startDate.value : '',
                end_date: endDate ? endDate.value : ''
            });
            fetchPatients(params.toString());
        }, 300); // 300ms debounce
    };

    if (searchInput) {
        searchInput.addEventListener('input', triggerFilter);
    }

    if (deptFilter) {
        deptFilter.addEventListener('change', triggerFilter);
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            triggerFilter();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (deptFilter) deptFilter.value = '';
            fetchPatients();
        });
    }

    // Issue Form Handling
    const issueForm = document.getElementById('issue-form');
    if (issueForm) {
        issueForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = issueForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';

            try {
                const formData = new FormData(issueForm);
                const result = await submitForm('issue_file', formData);

                if (result && result.success) {
                    document.getElementById('issue-modal').style.display = 'none';
                    issueForm.reset();
                    triggerFilter(); // Refresh current view to show updated status
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    // Return Modal Logic (Global for both pages)
    initReturnModal();
}

function initReturnModal() {
    const modal = document.getElementById('return-modal');
    const closeBtn = document.querySelector('.close-modal-return');
    const cancelBtn = document.getElementById('cancel-return-btn');
    const confirmBtn = document.getElementById('confirm-return-btn');

    if (modal) {
        if (closeBtn) closeBtn.onclick = () => modal.style.display = 'none';
        if (cancelBtn) cancelBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; };

        let pendingReturnId = null;

        window.showReturnModal = function (id, name = '', phone = '', dept = '', issueDate = '', remarks = '') {
            pendingReturnId = id;

            // Populate fields
            const nameField = document.getElementById('return-issued-to');
            const phoneField = document.getElementById('return-phone');
            const deptField = document.getElementById('return-dept');
            const issueDateField = document.getElementById('return-issue-date');
            const remarksField = document.getElementById('return-remarks');
            const currentDateField = document.getElementById('return-current-date');

            if (nameField) nameField.value = name || 'Unknown';
            if (phoneField) phoneField.value = phone || '-';
            if (deptField) deptField.value = dept || '-';
            if (issueDateField) issueDateField.value = issueDate || 'N/A';
            if (remarksField) remarksField.value = remarks || 'No remarks recorded';

            // Set current date automatically
            if (currentDateField) {
                const now = new Date();
                currentDateField.value = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            modal.style.display = 'block';
        };

        if (confirmBtn) {
            confirmBtn.onclick = async () => {
                if (pendingReturnId) {
                    await processReturn(pendingReturnId);
                    modal.style.display = 'none';
                }
            };
        }
    }
}

async function processReturn(id) {
    try {
        const formData = new FormData();
        formData.append('id', id);

        const response = await fetch('api.php?action=return_file', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            showToast('File marked as returned', 'success');
            // Refresh data based on page
            if (window.location.pathname.includes('issued_files.php')) {
                if (typeof fetchIssuedFiles === 'function') fetchIssuedFiles();
            } else {
                fetchPatients();
            }
        } else {
            showToast(result.error || 'Failed to update status', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

async function fetchPatients(queryString = 'action=list') {
    const tbody = document.getElementById('patient-data');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">Loading...</td></tr>';

    try {
        const response = await fetch(`api.php?${queryString}`);
        const patients = await response.json();
        if (!tbody) return;

        tbody.innerHTML = '';
        if (patients.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">No patients found.</td></tr>';
            return;
        }

        patients.forEach(p => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-patient-id', p.id);

            let issueButton = '';
            if (p.active_issuance_id) {
                issueButton = `<span class="status-badge" style="background:#fee2e2; color:#ef4444; padding: 0.5rem 0.7rem; cursor: default;" title="File is Issued to ${p.active_issued_to}"><i class="fas fa-user-clock" style="font-size: 1.1rem;"></i></span>`;
            } else {
                issueButton = `<span class="status-badge" style="background:#dcfce7; color:#16a34a; padding: 0.5rem 0.7rem; cursor: default;" title="File Available in Rack"><i class="fas fa-check-circle" style="font-size: 1.1rem;"></i></span>`;
            }

            tr.innerHTML = `
                <td><strong>${p.mrn}</strong></td>
                <td><strong>${p.full_name}</strong></td>
                <td><span style="opacity: ${p.rack_number ? '1' : '0.5'}">${p.rack_number || '-'}</span></td>
                <td>${p.department || '-'}</td>
                <td>${p.registration_date || '-'}</td>
                <td>
                        <div class="action-cell">
                            <button onclick="showHistory('${p.mrn}', '${p.full_name}')" class="btn-small btn-view">View Files</button>
                            <a href="api.php?action=view&mrn=${p.mrn}&t=${new Date().getTime()}" target="_blank" class="btn-small btn-view">Master File</a>
                            ${issueButton}
                            ${window.isAdmin ? `<button onclick="deletePatient('${p.mrn}', '${p.full_name}')" class="btn-small" style="background:#fee2e2; color:#dc2626; border-color:#fecaca;"><i class="fas fa-trash"></i></button>` : ''}
                        </div>
                    </td>
                `;
            tbody.appendChild(tr);
        });

        // Save globally for export
        window.currentData = patients;
    } catch (err) {
        console.error('Failed to fetch patients', err);
    }
}

async function showIssueModal(mrn, name) {
    const modal = document.getElementById('issue-modal');
    const mrnInput = document.getElementById('issue-mrn');
    const nameInput = document.getElementById('issue-patient-name');
    const dateInput = document.getElementById('default-issue-date');
    const form = document.getElementById('issue-form');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Reset Form
    form.reset();
    if (mrnInput) mrnInput.value = mrn;
    if (nameInput) nameInput.value = name;
    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

    // Enable all inputs by default
    Array.from(form.elements).forEach(el => el.disabled = false);
    if (nameInput) nameInput.readOnly = true; // Always read-only
    if (submitBtn) {
        submitBtn.style.display = 'block';
        submitBtn.textContent = 'Confirm Issuance';
    }

    // Check if already issued
    try {
        const response = await fetch(`api.php?action=check_issuance&mrn=${mrn}`);
        const result = await response.json();

        if (result.status === 'Issued') {
            const data = result.data;
            // Populate fields
            form.elements['issued_to'].value = data.issued_to;
            form.elements['phone_number'].value = data.phone_number || '';
            form.elements['department'].value = data.department;
            form.elements['issue_date'].value = data.issue_date;
            form.elements['return_date'].value = data.return_date || '';
            form.elements['remarks'].value = data.remarks || '';

            // Disable all inputs
            Array.from(form.elements).forEach(el => el.disabled = true);

            // Allow closing modal
            const closeBtn = document.querySelector('.close-modal-issue');
            if (closeBtn) closeBtn.onclick = () => modal.style.display = 'none';

            // Update title or show alert
            showToast('File is currently issued to ' + data.issued_to, 'error');

            // Hide submit button since it's read-only
            if (submitBtn) submitBtn.style.display = 'none';
        }
    } catch (err) {
        console.error('Error checking issuance:', err);
    }

    if (modal) modal.style.display = 'block';
}

async function showHistory(mrn, name) {
    const modal = document.getElementById('history-modal');
    const title = document.getElementById('history-title');
    const container = document.getElementById('history-data');
    const uploadMrnField = document.getElementById('history-upload-mrn');
    const uploadForm = document.getElementById('history-upload-form');
    const countBadge = document.getElementById('history-count-badge');
    const syncStatus = document.getElementById('sync-status');

    if (window.historySortable) {
        window.historySortable.destroy();
        window.historySortable = null;
    }

    if (uploadMrnField) uploadMrnField.value = mrn;
    if (uploadForm) {
        uploadForm.reset();
        const hint = document.getElementById('upload-hint');
        if (hint) hint.textContent = 'Click or Drag to add new records/photos';
    }

    modal.dataset.currentMrn = mrn;
    modal.dataset.currentName = name;

    title.textContent = `Medical History - ${name} (${mrn})`;
    container.innerHTML = '<div style="text-align:center; padding: 2rem; color: #64748b;">Loading records...</div>';
    modal.style.display = 'block';

    try {
        const response = await fetch(`api.php?action=history&mrn=${mrn}&t=${new Date().getTime()}`);
        const history = await response.json();

        container.innerHTML = '';
        if (countBadge) countBadge.textContent = `${history.length} Record(s)`;

        if (history.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:3rem; color:#94a3b8; border:2px dashed #e2e8f0; border-radius:12px;">No records found for this patient.</div>';
            return;
        }

        history.forEach(item => {
            const card = document.createElement('div');
            card.className = 'history-card';
            card.setAttribute('data-id', item.id);
            const date = new Date(item.upload_date).toLocaleDateString() + ' ' + new Date(item.upload_date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            const isImage = /\.(jpg|jpeg|png|gif)$/i.test(item.filename);
            const icon = isImage ?
                `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="22" height="22" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>` :
                `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="22" height="22" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`;

            card.innerHTML = `
                <div class="card-drag-handle">☰</div>
                <div class="file-icon-box">${icon}</div>
                <div class="file-info">
                    <span class="file-name">${item.filename}</span>
                    <span class="file-meta">Uploaded on ${date}</span>
                </div>
                <div class="card-actions">
                    <a href="api.php?action=view_individual&id=${item.id}" target="_blank" class="btn-small btn-view" title="View">View</a>
                    <a href="api.php?action=view_individual&id=${item.id}" download="${item.filename}" class="btn-small btn-return" title="Download">↓</a>
                    ${window.isAdmin ? `<button onclick="deleteIndividualRecord(${item.id}, '${mrn}')" class="btn-small" style="background:#fee2e2; color:#dc2626; border:none; cursor:pointer; padding: 4px 8px; border-radius: 4px;" title="Delete Record">✕</button>` : ''}
                </div>
            `;
            container.appendChild(card);
        });

        if (typeof Sortable !== 'undefined') {
            window.historySortable = Sortable.create(container, {
                handle: '.card-drag-handle',
                animation: 250,
                forceFallback: true, // Better compatibility for Windows/IIS environments
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: async () => {
                    const cards = Array.from(container.querySelectorAll('.history-card'));
                    const newOrder = cards.map(c => c.getAttribute('data-id'));

                    if (syncStatus) syncStatus.style.display = 'flex';
                    container.style.opacity = '0.7'; // Dim while saving
                    container.style.pointerEvents = 'none';

                    const formData = new FormData();
                    formData.append('mrn', mrn);
                    formData.append('order', JSON.stringify(newOrder));

                    try {
                        const res = await fetch('api.php?action=reorder', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await res.json();

                        if (result.success) {
                            // Give server 500ms to finalize PDF rebuilding before refresh
                            setTimeout(async () => {
                                if (syncStatus) syncStatus.style.display = 'none';
                                container.style.opacity = '1';
                                container.style.pointerEvents = 'auto';
                                // Refresh to confirm order from server
                                showHistory(mrn, name);
                            }, 500);
                        } else {
                            showToast(result.error || 'Failed to update order', 'error');
                            if (syncStatus) syncStatus.style.display = 'none';
                            container.style.opacity = '1';
                            container.style.pointerEvents = 'auto';
                        }
                    } catch (err) {
                        showToast('Failed to sync order', 'error');
                        if (syncStatus) syncStatus.style.display = 'none';
                        container.style.opacity = '1';
                        container.style.pointerEvents = 'auto';
                    }
                }
            });
        }
    } catch (err) {
        container.innerHTML = '<div style="color:red; text-align:center; padding:2rem;">Failed to load history data.</div>';
    }
}

async function submitForm(action, formData) {
    try {
        const response = await fetch(`api.php?action=${action}`, {
            method: 'POST',
            body: formData
        });

        let result;
        const text = await response.text();
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Server response was not JSON:', text);
            showToast('Invalid server response format', 'error');
            return { success: false };
        }

        if (result.success) {
            showToast(result.message, 'success');
        } else {
            showToast(result.error || 'Request failed', 'error');
        }
        return result;
    } catch (err) {
        console.error('Submit form error:', err);
        showToast('Network communication failure', 'error');
        return { success: false, error: 'Communication error' };
    }
}



function showToast(message, type = 'info') {
    const container = document.getElementById('notifications');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    // Auto-remove after 4 seconds
    setTimeout(() => {
        toast.classList.add('fade-out');
        toast.addEventListener('animationend', () => {
            toast.remove();
        });
    }, 4000);
}

/**
 * Uploads a file with progress tracking using XHR
 */
function uploadFileWithProgress(action, formData, onProgress) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                onProgress(percentComplete);
            }
        });

        xhr.addEventListener('load', () => {
            try {
                const result = JSON.parse(xhr.responseText);
                resolve(result);
            } catch (err) {
                reject(new Error('Invalid server response'));
            }
        });

        xhr.addEventListener('error', () => reject(new Error('Network error')));
        xhr.addEventListener('abort', () => reject(new Error('Upload aborted')));

        xhr.open('POST', `api.php?action=${action}`);
        xhr.send(formData);
    });
}
async function deletePatient(mrn, name) {
    if (!confirm(`WARNING: Are you sure you want to delete patient "${name}" (MRN: ${mrn})? This will permanently delete the patient and all their medical record history.`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('mrn', mrn);
        const response = await fetch('api.php?action=delete_patient', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            fetchPatients();
        } else {
            showToast(result.error, 'error');
        }
    } catch (err) {
        showToast('Communication error', 'error');
    }
}

async function deleteIndividualRecord(id, mrn) {
    if (!confirm('Are you sure you want to delete this individual record? This will also update the Master File.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        const response = await fetch('api.php?action=delete_record', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            showHistory(mrn, ''); // Refresh history
        } else {
            showToast(result.error, 'error');
        }
    } catch (err) {
        showToast('Communication error', 'error');
    }
}

async function toggleIssuanceHistory(patientId, mainRow) {
    const existingHistoryRow = mainRow.nextElementSibling;
    if (existingHistoryRow && existingHistoryRow.classList.contains('issuance-history-row')) {
        existingHistoryRow.remove();
        return;
    }

    try {
        const response = await fetch(`api.php?action=get_issuance_history&patient_id=${patientId}`);
        const history = await response.json();

        const historyRow = document.createElement('tr');
        historyRow.classList.add('issuance-history-row');

        let historyHTML = '<td colspan="9" style="padding: 1.5rem; background: #f8fafc;"><div style="background: #ffffff; padding: 1.5rem; border-radius: 0.8rem; border: 1px solid var(--border-light); box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">';
        historyHTML += '<h3 style="margin-bottom: 1.5rem; color: var(--primary); font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;"><span style="font-size: 1.4rem;">📋</span> Detailed Issuance Tracker</h3>';

        if (history.length === 0) {
            historyHTML += '<p style="color: var(--text-muted); text-align: center; padding: 2rem; font-weight: 500;">This file has never been issued out.</p>';
        } else {
            historyHTML += '<table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0;">';
            historyHTML += '<thead><tr style="border-bottom: 2px solid #f1f5f9;"><th style="width: 15%; text-align: left; padding: 0.75rem;"><span class="header-pill bg-blue">ISSUED TO</span></th><th style="width: 15%; text-align: left; padding: 0.75rem;"><span class="header-pill bg-red">DEPARTMENT</span></th><th style="width: 12%; text-align: left; padding: 0.75rem;"><span class="header-pill bg-green">ISSUE DATE</span></th><th style="width: 12%; text-align: left; padding: 0.75rem;"><span class="header-pill bg-orange">RETURN DATE</span></th><th style="width: 11%; text-align: left; padding: 0.75rem;"><span class="header-pill bg-purple">STATUS</span></th><th style="width: 35%; text-align: left; padding: 0.75rem;"><span class="header-pill bg-slate">REMARKS</span></th></tr></thead><tbody>';

            history.forEach(h => {
                const statusStyle = h.status === 'Issued' ? 'background:#fee2e2; color:#ef4444;' : 'background:#dcfce7; color:#166534;';

                let returnDateDisplay = h.return_date;
                if (h.status === 'Issued') {
                    returnDateDisplay = '<span style="color:#ef4444; font-weight:bold">Still Out</span>';
                } else if (!h.return_date || h.return_date === '0000-00-00 00:00:00') {
                    returnDateDisplay = '<span style="color:var(--text-muted)">Date Not Recorded</span>';
                }

                historyHTML += `<tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 1rem 0.75rem; word-break: break-all;"><strong>${h.issued_to}</strong><br><small style="color:var(--text-muted)">${h.phone_number || '-'}</small></td>
                    <td style="padding: 1rem 0.75rem; font-size: 0.85rem;">${h.department}</td>
                    <td style="padding: 1rem 0.75rem; font-size: 0.8rem; white-space: nowrap;">${h.issue_date}</td>
                    <td style="padding: 1rem 0.75rem; font-size: 0.8rem; white-space: nowrap;">${returnDateDisplay}</td>
                    <td style="padding: 1rem 0.75rem;"><span style="padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; ${statusStyle}">${h.status.toUpperCase()}</span></td>
                    <td style="padding: 1rem 0.75rem; font-size: 0.85rem; color: var(--text-muted); word-break: break-all; white-space: normal; line-height: 1.5;">${h.remarks || '-'}</td>
                </tr>`;
            });
            historyHTML += '</tbody></table>';
        }

        historyHTML += '</div></td>';
        historyRow.innerHTML = historyHTML;
        mainRow.parentNode.insertBefore(historyRow, mainRow.nextSibling);
    } catch (err) {
        showToast('Failed to load tracking data', 'error');
    }
}

async function initHistoryUpload() {
    const form = document.getElementById('history-upload-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const mrn = document.getElementById('history-upload-mrn').value;
        const files = form.querySelector('input[type="file"]').files;

        if (files.length === 0) {
            showToast('Please select at least one file', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('mrn', mrn);
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        const progressContainer = document.getElementById('modal-upload-progress');
        const progressBar = document.getElementById('modal-upload-bar');
        const submitBtn = form.querySelector('button');

        if (progressContainer) progressContainer.style.display = 'block';
        submitBtn.disabled = true;

        try {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'api.php?action=append', true);

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable && progressBar) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    const percentText = document.getElementById('modal-upload-percent');
                    if (percentText) percentText.textContent = percent + '%';
                }
            };

            xhr.onload = function () {
                submitBtn.disabled = false;
                if (progressContainer) progressContainer.style.display = 'none';

                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        showToast(result.message, 'success');
                        form.reset();
                        // Reset hint
                        const hint = document.getElementById('upload-hint');
                        if (hint) hint.textContent = 'Click or Drag to add new records/photos';

                        // Refresh history
                        const modal = document.getElementById('history-modal');
                        showHistory(modal.dataset.currentMrn, modal.dataset.currentName);
                        // Also refresh main list to update updated_at if needed
                        if (window.triggerFilter) window.triggerFilter();
                    } else {
                        showToast(result.error || 'Upload failed', 'error');
                    }
                } catch (err) {
                    showToast('Invalid server response', 'error');
                }
            };

            xhr.onerror = function () {
                submitBtn.disabled = false;
                progressContainer.style.display = 'none';
                showToast('Network error during upload', 'error');
            };

            xhr.send(formData);

        } catch (err) {
            submitBtn.disabled = false;
            progressContainer.style.display = 'none';
            showToast('Upload failed', 'error');
        }
    });
}
