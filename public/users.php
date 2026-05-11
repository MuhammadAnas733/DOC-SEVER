<?php
require_once __DIR__ . '/../src/Auth.php';
Hospital\Auth::requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - AIH System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2.31">
</head>
<body>
    <!-- Sidebar -->
    <!-- Dynamic Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-layout">
        <div class="page-header">
            <h1 style="color: #0f172a; font-size: 1.5rem; opacity: 0.8;">User Management</h1>
        </div>

        <section style="display: grid; grid-template-columns: 1fr 2fr; gap: 2.5rem; align-items: start;">
            <div class="card">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <div style="width: 48px; height: 48px; background: var(--primary-light); color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="24" height="24" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    </div>
                    <h2 style="margin-bottom: 0;">Add New User</h2>
                </div>
                <form id="add-user-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="e.g. dr_smith" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            <option value="user">User (Record Entry)</option>
                            <option value="admin">Admin (System Manager)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn primary" style="width: 100%; margin-top: 1.5rem;">Create System Operator</button>
                </form>
            </div>

            <div class="card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                    <h2 style="margin-bottom: 0;">System Operators</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th><span class="header-pill bg-blue">Username</span></th>
                                <th><span class="header-pill bg-red">Role</span></th>
                                <th><span class="header-pill bg-green">Created At</span></th>
                                <th style="text-align: right;"><span class="header-pill bg-slate">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody id="user-list">
                            <!-- JS loaded -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <div id="update-password-modal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <span class="close-modal-password">&times;</span>
            <h2 style="margin-bottom: 1.5rem;">Update Password</h2>
            <form id="update-password-form">
                <input type="hidden" id="update-user-id">
                <div class="form-group">
                    <label>Updating User:</label>
                    <input type="text" id="update-username" readonly style="background: #f1f5f9; font-weight: 600;">
                </div>
                <div class="form-group">
                    <label style="color: var(--accent-red);">Your Admin Password:</label>
                    <input type="password" id="admin-password" name="admin_password" required placeholder="Verify identity">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>New Password:</label>
                        <input type="password" id="new-password" name="password" required placeholder="Min 4 chars">
                    </div>
                    <div class="form-group">
                        <label>Confirm:</label>
                        <input type="password" id="confirm-password" name="confirm_password" required placeholder="Repeat">
                    </div>
                </div>
                <button type="submit" class="btn primary" style="width: 100%; margin-top: 1rem;">Process Change</button>
            </form>
        </div>
    </div>

    <div id="delete-modal" class="modal">
        <div class="modal-content" style="max-width: 480px; text-align: center; padding: 2.5rem;">
            <span class="close-modal-delete">&times;</span>
            <div style="width: 80px; height: 80px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" width="40" height="40" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <h2 style="margin: 0 0 1rem 0; color: #1e293b; font-size: 1.5rem;">Delete User Account</h2>
            <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
                <strong id="delete-username"></strong>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button id="cancel-delete-btn" class="btn">Cancel</button>
                <button id="confirm-delete-btn" class="btn primary" style="background: #ef4444;">Delete User</button>
            </div>
        </div>
    </div>

    <div id="notifications"></div>

    <script src="assets/app.js?v=2.31"></script>
    <script>
        window.currentUserId = <?php echo $_SESSION['user_id']; ?>;
        document.addEventListener('DOMContentLoaded', () => {
            fetchUsers();
            const passwordModal = document.getElementById('update-password-modal');
            const closeBtn = document.querySelector('.close-modal-password');
            if (closeBtn) closeBtn.onclick = () => passwordModal.style.display = 'none';

            const deleteModal = document.getElementById('delete-modal');
            const closeDel = document.querySelector('.close-modal-delete');
            const cancelDel = document.getElementById('cancel-delete-btn');
            if (closeDel) closeDel.onclick = () => deleteModal.style.display = 'none';
            if (cancelDel) cancelDel.onclick = () => deleteModal.style.display = 'none';

            const userForm = document.getElementById('add-user-form');
            userForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(userForm);
                const res = await fetch('api.php?action=user_add', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.success) { showToast(result.message, 'success'); userForm.reset(); fetchUsers(); }
                else { showToast(result.error, 'error'); }
            });
        });

        async function fetchUsers() {
            const tbody = document.getElementById('user-list');
            const res = await fetch('api.php?action=user_list');
            const users = await res.json();
            tbody.innerHTML = '';
            users.forEach(u => {
                const tr = document.createElement('tr');
                let deleteBtn = '';
                if (u.id != window.currentUserId) {
                    deleteBtn = `<button onclick="deleteUser(${u.id}, '${u.username}')" class="btn-small btn-issue" style="background: #fee2e2; color: #ef4444; border:none; padding: 0.5rem 0.85rem;">Delete</button>`;
                }
                tr.innerHTML = `
                    <td><strong>${u.username}</strong></td>
                    <td><span class="status-badge status-issued">${u.role.toUpperCase()}</span></td>
                    <td>${new Date(u.created_at).toLocaleDateString()}</td>
                    <td>
                        <div class="action-cell" style="justify-content: flex-end;">
                            <button onclick="showUpdatePasswordModal(${u.id}, '${u.username}')" class="btn-small btn-view">Update Password</button>
                            ${deleteBtn}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function deleteUser(id, username) {
            document.getElementById('delete-username').textContent = username;
            document.getElementById('delete-modal').style.display = 'block';
            document.getElementById('confirm-delete-btn').onclick = async () => {
                const formData = new FormData(); formData.append('id', id);
                await fetch('api.php?action=user_delete', { method: 'POST', body: formData });
                document.getElementById('delete-modal').style.display = 'none';
                fetchUsers();
            };
        }

        function showUpdatePasswordModal(id, username) {
            document.getElementById('update-user-id').value = id;
            document.getElementById('update-username').value = username;
            document.getElementById('update-password-modal').style.display = 'block';
        }

        document.getElementById('update-password-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const password = document.getElementById('new-password').value;
            const confirm = document.getElementById('confirm-password').value;
            
            if (password !== confirm) {
                showToast('New passwords do not match!', 'error');
                return;
            }

            if (password.length < 4) {
                showToast('Password must be at least 4 characters.', 'error');
                return;
            }

            const formData = new FormData(e.target);
            formData.append('id', document.getElementById('update-user-id').value);
            
            try {
                const res = await fetch('api.php?action=user_update_password', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.success) { 
                    showToast(result.message, 'success'); 
                    document.getElementById('update-password-modal').style.display = 'none';
                    e.target.reset();
                } else { 
                    showToast(result.error, 'error'); 
                }
            } catch (err) {
                showToast('Communication error', 'error');
            }
        });
    </script>
</body>
</html>
