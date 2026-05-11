<?php
require_once __DIR__ . '/../src/Auth.php';
Hospital\Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Dashboard - AIH System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2.31">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --medical-blue: #0060ac;
            --medical-green: #22c55e;
        }

        .dashboard-hero {
            background: linear-gradient(135deg, var(--medical-blue) 0%, #004a85 100%);
            border-radius: 2rem;
            padding: 3rem;
            color: white;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: visible; /* Changed to visible for search results */
            box-shadow: 0 20px 40px rgba(0, 96, 172, 0.2);
            z-index: 10;
        }

        .dashboard-hero::after {
            content: '';
            position: absolute;
            right: -5%;
            top: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            z-index: -1;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .professional-stat-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            border: 1px solid #f1f5f9;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .professional-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.05);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-icon-wrapper {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-main {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1e293b;
            font-family: 'Outfit', sans-serif;
            margin-top: 5px;
        }

        /* Search Styles */
        .rack-search-container {
            position: relative;
            max-width: 600px;
            margin-top: 2rem;
        }

        .rack-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-top: 10px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            padding: 1rem;
        }

        .search-result-item {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.2s;
            border-radius: 0.5rem;
            color: #0f172a;
        }

        .search-result-item:hover {
            background: #f8fafc;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        /* Charts Layout */
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .chart-card {
            background: white;
            padding: 2rem;
            border-radius: 1.5rem;
            border: 1px solid #f1f5f9;
            height: 100%;
        }

        .top-files-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-files-table th {
            text-align: left;
            padding: 1rem 0;
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid #f1f5f9;
        }

        .top-files-table td {
            padding: 1rem 0;
            border-bottom: 1px solid #f8fafc;
            font-size: 0.9rem;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <!-- Dynamic Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-layout">
        <div class="dashboard-hero">
            <div style="max-width: 800px; position: relative; z-index: 5;">
                <h1 style="font-size: 2.25rem; font-weight: 700; color: white;">AIH Centralized Intelligence</h1>
                <p style="font-size: 1.1rem; opacity: 0.9; margin-top: 0.75rem;">Global patient file oversight. Search by Patient Name, MRN, or Doctor/Issuer name to locate racks.</p>
                
                <!-- Expanded Rack Search -->
                <div class="rack-search-container">
                    <input type="text" id="rack-search-input" placeholder="Search for File Location (Name, MRN, Doctor)..." 
                           style="width: 100%; padding: 1.25rem 1.5rem; padding-left: 3.5rem; border-radius: 1rem; border: none; background: rgba(255,255,255,1); color: #0f172a; font-weight: 600; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" width="24" height="24" stroke-width="2.5" 
                            style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%);">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    
                    <div id="rack-search-results" class="rack-search-results">
                        <!-- JS Results -->
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Key Metrics -->
        <div class="stats-grid">
            <div class="professional-stat-card">
                <div class="stat-header">
                    <div class="stat-icon-wrapper" style="background: #eff6ff; color: #2563eb;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="24" height="24" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
                <div class="stat-main">
                    <span class="stat-label">Total Patient Archive</span>
                    <span class="stat-value" id="stat-total-patients">...</span>
                </div>
            </div>

            <div class="professional-stat-card">
                <div class="stat-header">
                    <div class="stat-icon-wrapper" style="background: #fdf2f8; color: #db2777;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="24" height="24" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                </div>
                <div class="stat-main">
                    <span class="stat-label">Documents in Circulation</span>
                    <span class="stat-value" id="stat-issued-files">...</span>
                </div>
            </div>

            <div class="professional-stat-card">
                <div class="stat-header">
                    <div class="stat-icon-wrapper" style="background: #f0fdf4; color: #16a34a;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="24" height="24" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    </div>
                </div>
                <div class="stat-main">
                    <span class="stat-label">Today Admissions</span>
                    <span class="stat-value" id="stat-today-admissions">...</span>
                </div>
            </div>

            <div class="professional-stat-card">
                <div class="stat-header">
                    <div class="stat-icon-wrapper" style="background: #fff7ed; color: #ea580c;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="24" height="24" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                </div>
                <div class="stat-main">
                    <span class="stat-label">Total Documents Issued</span>
                    <span class="stat-value" id="stat-total-issuances">...</span>
                </div>
            </div>
        </div>

        <!-- 2. Analytics & Insights -->
        <h2 style="font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem;">Analytics & File Flow</h2>
        <div class="analytics-grid">
            <!-- Left: Most Outgoing Files -->
            <div class="chart-card">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: 1.5rem;">Most Frequent Outgoing Files</h3>
                <div style="overflow-x: auto;">
                    <table class="top-files-table">
                        <thead>
                            <tr>
                                <th><span class="header-pill bg-blue">File Name / MRN</span></th>
                                <th><span class="header-pill bg-red">Department</span></th>
                                <th><span class="header-pill bg-green">Issuance Count</span></th>
                                <th><span class="header-pill bg-slate">Action</span></th>
                            </tr>
                        </thead>
                        <tbody id="top-files-list">
                            <tr><td colspan="4" style="text-align:center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Department Distribution -->
            <div class="chart-card">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: 1.5rem;">Patient Demographics</h3>
                <canvas id="deptChart" style="max-height: 250px;"></canvas>
            </div>
        </div>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            // 1. Fetch Basic Stats
            try {
                const response = await fetch('api.php?action=stats');
                const data = await response.json();
                document.getElementById('stat-total-patients').textContent = data.totalPatients;
                document.getElementById('stat-issued-files').textContent = data.issuedFiles;
                document.getElementById('stat-today-admissions').textContent = data.todayAdmissions;
                if (document.getElementById('stat-total-issuances')) {
                    document.getElementById('stat-total-issuances').textContent = data.totalIssuances;
                }
            } catch (err) { console.error(err); }

            // 2. Fetch Analytics
            try {
                const response = await fetch('api.php?action=dashboard_analytics');
                const data = await response.json();
                
                // Render Top Files
                const topList = document.getElementById('top-files-list');
                topList.innerHTML = '';
                if(data.topFiles.length === 0) {
                     topList.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem; color: #94a3b8;">No data available</td></tr>';
                } else {
                    data.topFiles.forEach(file => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>
                                <strong style="color:#0f172a;">${file.full_name}</strong>
                                <br><span style="color:#64748b; font-size:0.8rem;">${file.mrn}</span>
                            </td>
                            <td><span style="background:#f1f5f9; padding: 2px 8px; border-radius:4px; font-size:0.8rem; font-weight:600;">${file.department}</span></td>
                            <td><div style="background:#eff6ff; color:#2563eb; width: 30px; height: 30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;">${file.issuances}</div></td>
                            <td>
                                <a href="issued_files.php?search=${encodeURIComponent(file.mrn)}" style="text-decoration:none; color:#2563eb; font-weight:600; font-size:0.85rem;">View History &rarr;</a>
                            </td>
                        `;
                        topList.appendChild(tr);
                    });
                }

                // Render Chart
                const ctx = document.getElementById('deptChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.deptStats.map(d => d.department || 'General'),
                        datasets: [{
                            data: data.deptStats.map(d => d.count),
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, font: {family: "'Inter', sans-serif"} } }
                        },
                        cutout: '70%'
                    }
                });

            } catch (err) { console.error(err); }

            // 3. Search Logic
            const searchInput = document.getElementById('rack-search-input');
            const resultsBox = document.getElementById('rack-search-results');
            let debounce;

            searchInput.addEventListener('input', (e) => {
                const val = e.target.value.trim();
                clearTimeout(debounce);
                
                if (val.length < 2) {
                    resultsBox.style.display = 'none';
                    return;
                }

                debounce = setTimeout(async () => {
                    try {
                        const res = await fetch(`api.php?action=search_rack&term=${encodeURIComponent(val)}`);
                        const results = await res.json();
                        
                        resultsBox.innerHTML = '';
                        resultsBox.style.display = 'block';

                        if (results.length === 0) {
                            resultsBox.innerHTML = '<div style="padding:1rem; text-align:center; color:#64748b;">No matching records found.</div>';
                            return;
                        }

                        results.forEach(r => {
                            const div = document.createElement('div');
                            div.className = 'search-result-item';
                            div.innerHTML = `
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <div style="font-weight:700; font-size:1rem;">${r.full_name}</div>
                                        <div style="font-size:0.85rem; color:#64748b;">MRN: ${r.mrn} • ${r.department}</div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:0.75rem; color:#94a3b8; text-transform:uppercase; font-weight:600;">Rack Location</div>
                                        <div style="font-size:1.1rem; font-weight:700; color:#2563eb;">${r.rack_number || 'Not Assigned'}</div>
                                    </div>
                                </div>
                            `;
                            div.onclick = () => window.location.href = `patients.php?search=${r.mrn}`;
                            resultsBox.appendChild(div);
                        });

                    } catch (err) {
                        resultsBox.innerHTML = '<div style="padding:1rem; color:red;">Search error.</div>';
                    }
                }, 300);
            });

            // Close search on click outside
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                    resultsBox.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
