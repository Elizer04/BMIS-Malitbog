<?php
session_start();

// Include the central database connection (which provides $conn and $pdo)
include 'db_connect.php';

// Security Check: Prevent direct access if not logged in or not an official
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'official') {
    header("Location: official_portal.php");
    exit();
}

$official_name = $_SESSION['name'];
$official_email = $_SESSION['email'];



// Fetch total count of cases for the hero banner counter
$stmtCount = $pdo->query("SELECT COUNT(*) AS total FROM my_cases");
$total_cases_count = $stmtCount->fetch()['total'] ?? 0;

// -------------------------------------------------------------
// FETCH CASES WITH FILTERS (Matching admin_judicial style)
// -------------------------------------------------------------
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT * FROM my_cases WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (case_id LIKE ? OR reporter_name LIKE ? OR respondent_name LIKE ? OR type_of_incident LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cases = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Blotter Report Management - Barangay Malitbog (Official View-Only)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #000066;
            --secondary-blue: #1a1a8c;
            --accent-gold: #ffcc00;
            --text-white: #ffffff;
            --white: #ffffff;
            --bg-light: #f4f7f6;
            --border-color: #e2e8f0;
            --text-dark: #1e293b;
            --admin-red: #cc0000;
        }

        html, body {
            min-height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            font-family: 'Segoe UI', Arial, 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-light);
            color: var(--text-dark);
            -webkit-text-size-adjust: 100%;
        }

        * { box-sizing: border-box; }

        /* Top Bar aligned edge-to-edge with the main-nav (5% padding) */
        .topbar {
            background: var(--secondary-blue);
            color: white;
            padding: 5px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
        }

        .topbar .socials {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar .socials a {
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            text-decoration: none;
            transition: color 0.2s;
        }

        .topbar .socials a:hover {
            color: var(--accent-gold);
        }

        .topbar .socials svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }

        .topbar .socials .instagram-mark {
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
        }

        .main-nav {
            background: white;
            padding: 10px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1100;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-link {
            color: var(--primary-blue);
            font-size: 1.2rem;
            text-decoration: none;
            margin-right: 5px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--accent-gold);
        }

        .logo-section img {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-gold);
        }

        .logo-text h2 {
            margin: 0;
            color: var(--primary-blue);
            font-size: 1.1rem;
            letter-spacing: 1px;
        }
        .logo-text small { font-size: 0.7rem; color: #555; display: block; }

        .nav-links { 
            display: flex; 
            align-items: center; 
            gap: 25px; 
        }

        .rd-nav-item, 
        .rd-nav-link-dropdown a, 
        .rd-nav-link-dropdown span {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #333;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .rd-nav-item.custom-home-style span,
        .rd-nav-menu-bar-trigger span {
            font-family: 'Poppins', sans-serif;
            font-size: 0.75rem;
            text-transform: capitalize;
            letter-spacing: 0.5px;
        }

        .rd-nav-item i, 
        .rd-nav-menu-bar-trigger i {
            font-size: 1.1rem;
        }

        .rd-nav-item:hover,
        .rd-nav-menu-bar-trigger:hover {
            color: var(--accent-gold);
        }

        .rd-nav-link-dropdown {
            display: inline-flex;
            align-items: center;
            position: relative;
        }

        .rd-nav-menu-bar-trigger {
            cursor: pointer;
        }

        /* Slide-in Menu Drawer Styles */
        .menu-slide-drawer {
            display: none;
            position: fixed;
            z-index: 1300;
            right: 0;
            top: 0;
            width: 100%;
            max-width: 380px;
            height: 100%;
            background-color: white;
            box-shadow: -5px 0 20px rgba(0,0,0,0.15);
            flex-direction: column;
            animation: slideInMenu 0.3s ease-out;
        }

        @keyframes slideInMenu {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        .menu-drawer-header {
            background-color: var(--primary-blue);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .admin-info-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            font-size: 2.2rem;
            color: var(--accent-gold);
        }

        .admin-details h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: white;
        }

        .admin-details p {
            margin: 3px 0 0 0;
            font-size: 0.8rem;
            color: #d1d5db;
        }

        .menu-drawer-body {
            padding: 15px;
            overflow-y: auto;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .menu-drawer-body a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            text-decoration: none;
            color: #333;
            font-size: 0.95rem;
            font-weight: 500;
            background: #f9f9f9;
            border-left: 4px solid var(--accent-gold);
            border-radius: 4px;
            transition: transform 0.15s ease, background 0.2s ease, color 0.2s ease;
        }

        .menu-drawer-body a:hover {
            transform: translateY(-2px);
            background: #f0f0f0;
            color: var(--primary-blue);
        }

        .menu-drawer-body a i {
            font-size: 1.1rem;
            color: var(--primary-blue);
            width: 20px;
            text-align: center;
        }

        .rd-global-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1050;
        }

        .close-btn { background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #aaa; cursor: pointer; }
        .close-btn:hover { color: #fff; }

        /* Hero Banner Styles */
        .hero-banner {
            background: linear-gradient(rgba(0, 0, 102, 0.7), rgba(0, 0, 102, 0.7)), url('image/backgroundphoto1.jpg') no-repeat center center/cover;
            padding: 80px 10%; 
            color: white; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        .welcome-msg h1 { margin: 0; font-size: 2.2rem; }
        .welcome-msg p { font-size: 1.2rem; margin-top: 10px; opacity: 0.9; }
        .welcome-msg span { color: var(--accent-gold); }

        /* Container Layout */
        .container {
            max-width: 1200px;
            margin: -30px auto 40px;
            padding: 30px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #0f172a;
        }

        /* Filter Section */
        .filter-card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-card input[type="text"], .filter-card select {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            background: white;
        }

        .filter-card input[type="text"] { flex: 1; min-width: 200px; }

        .btn-filter {
            background: var(--primary-blue);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-filter:hover { background: #000044; }

        /* Table Styling */
        .table-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        th {
            background: #f8fafc;
            color: #475569;
            padding: 14px 16px;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        tr:hover { background: #f8fafc; }

        .case-status-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e2e8f0;
            color: #334155;
            display: inline-block;
        }

        /* Action Buttons */
        .btn-action-icon {
            border: none;
            background: none;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 5px;
            color: var(--primary-blue);
            transition: color 0.2s;
        }
        .btn-action-icon:hover { color: var(--accent-gold); }

        /* Modal Styles (Comprehensive View-Only) */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 15px;
            box-sizing: border-box;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 25px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            box-sizing: border-box;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--primary-blue);
            font-size: 1.15rem;
        }

        .close-modal {
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            background: none;
            border: none;
        }
        .close-modal:hover { color: #333; }

        .modal-body-content {
            padding: 5px 0;
            font-size: 0.9rem;
            color: #333;
            line-height: 1.4;
        }

        .modal-footer {
            margin-top: 15px; text-align: right; padding-top: 12px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 8px;
        }
        .btn-modal-cancel { background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; transition: 0.3s; }
        .btn-modal-cancel:hover { background: #5a6268; }

        .detail-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary-blue);
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
            margin: 20px 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-group {
            margin-bottom: 12px;
        }

        .detail-group label {
            font-weight: 600;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            display: block;
        }

        .detail-group p {
            margin: 4px 0 0 0;
            font-size: 0.9rem;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        
        ul.modal-list {
            margin: 4px 0 0 0;
            padding-left: 20px;
            font-size: 0.9rem;
            background: #f8fafc;
            padding: 8px 20px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .evidence-item-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 10px;
        }
        .evidence-img-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .evidence-img-preview:hover {
            transform: scale(1.03);
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .topbar { display: none; }
            .main-nav { padding: 8px 12px; }
            .nav-links { gap: 15px; }
            .rd-nav-item span,
            .rd-nav-menu-bar-trigger span { display: none; }
            .menu-slide-drawer { max-width: 60% !important; width: 60% !important; }
            .hero-banner { padding: 40px 5%; flex-direction: column; text-align: center; gap: 20px; }
        }
    </style>
</head>
<body>

    <!-- Top Utility Bar -->
    <div class="topbar">
        <div class="socials" aria-label="Social media">
            <a href="https://www.facebook.com/share/1MqLQuYLdG/" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.6 21.9v-8.7h2.9l.4-3.4h-3.3V7.6c0-1 .3-1.6 1.7-1.6h1.8V2.9c-.3 0-1.4-.1-2.7-.1-2.7 0-4.5 1.6-4.5 4.6v2.5H7v3.4h2.9v8.7h3.7z"/></svg>
            </a>
            <a href="https://youtube.com/@officialpage-barangaymalitbog?si=eo-uBsODjPVwKpOn" aria-label="YouTube" target="_blank" rel="noopener noreferrer">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.7V8.3l6.3 3.7-6.3 3.7Z"/></svg>
            </a>
            <a href="https://www.instagram.com/barangaymalitbog2k26?mdxt=a29mcDJrNTdkNGR4" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                <svg class="instagram-mark" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.7" r=".8" fill="currentColor" stroke="none"/></svg>
            </a>
            <a href="#" aria-label="Twitter">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 2.25h3.3l-7.2 8.2 8.5 11.3h-6.6l-5.2-6.8-6 6.8H2.4l7.7-8.8L2 2.25h6.8l4.7 6.2 5.4-6.2Zm-1.2 17.5h1.8L7.1 4H5.2z"/></svg>
            </a>
        </div>
        <div>
            <div class="profile-section">
                <span style="font-size: 0.8rem;"><i class="fa fa-id-badge"></i> Barangay Official Portal (View-Only)</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="official_dashboard.php" class="back-link" title="Back to Dashboard">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Logo">
            <div class="logo-text">
                <h2>BARANGAY MALITBOG</h2>
                <small>MUNICIPALITY OF BONGABONG</small>
            </div>
        </div>
        <div class="nav-links">
            <a href="official_dashboard.php" class="rd-nav-item custom-home-style" title="Official Home">
                <i class="fas fa-home"></i>
                <span>home</span>
            </a>
            <div class="rd-nav-link-dropdown">
                <a href="javascript:void(0);" class="rd-nav-menu-bar-trigger" onclick="toggleNavBarMenu()" title="Menu Options">
                    <i class="fas fa-bars"></i>
                    <span>Menu</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Global Overlay & Slide-in Drawer -->
    <div class="rd-global-overlay" id="globalOverlay" onclick="closeAllModals()"></div>

    <div class="menu-slide-drawer" id="menuSlideDrawer">
        <div class="menu-drawer-header">
            <div class="admin-info-container">
                <i class="fas fa-user-circle admin-avatar"></i>
                <div class="admin-details">
                    <h4><?php echo htmlspecialchars($official_name); ?></h4>
                    <p><?php echo htmlspecialchars($official_email); ?></p>
                </div>
            </div>
            <button type="button" class="close-btn" onclick="toggleNavBarMenu()">&times;</button>
        </div>
        <div class="menu-drawer-body">
            <a href="official_dashboard.php"><i class="fas fa-house-chimney"></i> Dashboard</a>
            <a href="manage_residentcopy.php"><i class="fas fa-users"></i> Residents Record</a>
            <a href="admin_certificationcopy.php"><i class="fas fa-file-lines"></i> Certificates & Blotter</a>
            <a href="admin_judicialcopy.php"><i class="fas fa-gavel"></i> Case Records</a>
            <a href="admin_healthcopy.php"><i class="fas fa-notes-medical"></i> Health Center Schedule</a>
        </div>
    </div>

    <!-- Hero Banner -->
    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>JUDICIAL <span>SYSTEM</span></h1>
            <p>Judicial & Incident Reports of Barangay Malitbog (Read-Only Mode)</p>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: bold; font-size: 1.2rem; color: var(--accent-gold);">
                TOTAL CASES: <?php echo $total_cases_count; ?>
            </div>
            <p style="margin: 5px 0 0 0;">Access Level: <span style="color: #ffcc00; font-weight: bold;">View Only</span></p>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Blotter Reports & Case Directory</h2>
            <span style="font-size: 0.85rem; color: #64748b;"><i class="fa fa-info-circle"></i> Official Access: View Reports Only</span>
        </div>

        <!-- Filter / Search Form -->
        <form method="GET" class="filter-card">
            <input type="text" name="search" placeholder="Search Case ID, Reporter, Respondent, Incident Type..." value="<?= htmlspecialchars($search) ?>">
            <select name="status">
                <option value="">All Judicial Status</option>
                <option value="Mediation Stage" <?= $status_filter === 'Mediation Stage' ? 'selected' : '' ?>>Mediation (Punong Barangay)</option>
                <option value="Conciliation Stage" <?= $status_filter === 'Conciliation Stage' ? 'selected' : '' ?>>Conciliation (Pangkat)</option>
                <option value="Arbitration" <?= $status_filter === 'Arbitration' ? 'selected' : '' ?>>Arbitration</option>
                <option value="Resolved / Settled" <?= $status_filter === 'Resolved / Settled' ? 'selected' : '' ?>>Resolved / Settled</option>
                <option value="Certification to File Action" <?= $status_filter === 'Certification to File Action' ? 'selected' : '' ?>>Certification to File Action</option>
                <option value="Dismissed" <?= $status_filter === 'Dismissed' ? 'selected' : '' ?>>Dismissed / Withdrawn</option>
            </select>
            <button type="submit" class="btn-filter"><i class="fa fa-search"></i> Filter</button>
        </form>

        <!-- Blotter Records Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Complainant / Reporter</th>
                        <th>Respondent</th>
                        <th>Incident Type</th>
                        <th>Incident Date</th>
                        <th>Judicial Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($cases) > 0): ?>
                        <?php foreach ($cases as $case): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($case['case_id']) ?></strong></td>
                                <td><?= htmlspecialchars($case['reporter_name']) ?></td>
                                <td><?= htmlspecialchars($case['respondent_name'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($case['type_of_incident'] ?: ($case['nature_of_dispute'] ?? 'Unspecified')) ?></td>
                                <td><?= htmlspecialchars($case['incident_date'] ?: 'N/A') ?></td>
                                <td>
                                    <span class="case-status-badge">
                                        <?= htmlspecialchars($case['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-action-icon" title="View Full Details" onclick='openViewModal(<?= json_encode($case) ?>)'>
                                        <i class="fa fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">No blotter reports found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Details Modal (Comprehensive Read-Only Layout) -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalCaseId">Case Details</h3>
                <button type="button" class="close-modal" onclick="closeViewModal()">&times;</button>
            </div>
            <div id="modalBody" class="modal-body-content">
                <!-- Case details loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        const globalOverlay = document.getElementById('globalOverlay');

        function toggleNavBarMenu() {
            const menuDrawer = document.getElementById('menuSlideDrawer');
            if (menuDrawer.style.display === 'flex') {
                menuDrawer.style.display = 'none';
                globalOverlay.style.display = 'none';
            } else {
                globalOverlay.style.display = 'block';
                menuDrawer.style.display = 'flex';
            }
        }

        function closeAllModals() {
            document.getElementById('menuSlideDrawer').style.display = 'none';
            document.getElementById('viewModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        // Open View Details Modal with full schema parsing
        function openViewModal(data) {
            document.getElementById('modalCaseId').innerText = 'Case Details - ' + (data.case_id || 'N/A');

            let witnessesHtml = '';
            try {
                const witnesses = JSON.parse(data.witnesses || '[]');
                if (witnesses.length > 0) {
                    witnessesHtml = '<ul class="modal-list">' + witnesses.map(w => `<li><strong>${w.name || 'Unnamed'}</strong>${w.details ? ' - ' + w.details : ''}</li>`).join('') + '</ul>';
                } else {
                    witnessesHtml = '<p>None recorded</p>';
                }
            } catch(e) { witnessesHtml = '<p>None recorded</p>'; }

            let suspectsHtml = '';
            try {
                const suspects = JSON.parse(data.suspects || '[]');
                if (suspects.length > 0) {
                    suspectsHtml = '<ul class="modal-list">' + suspects.map(s => `<li><strong>${s.name || 'Unnamed'}</strong> (${s.gender || 'N/A'}, ${s.age || 'N/A'} yrs) - ${s.address || 'No address'}</li>`).join('') + '</ul>';
                } else {
                    suspectsHtml = '<p>None recorded</p>';
                }
            } catch(e) { suspectsHtml = '<p>None recorded</p>'; }

            let evidenceHtml = '';
            try {
                const evidence = JSON.parse(data.evidence || '[]');
                if (evidence.length > 0) {
                    evidenceHtml = '<div class="modal-list" style="padding: 10px;">';
                    evidence.forEach(ev => {
                        let desc = '';
                        let filePath = '';

                        if (typeof ev === 'string') {
                            if (ev.match(/\.(jpg|jpeg|png|gif|webp)$/i) || ev.includes('uploads/')) {
                                filePath = ev;
                            } else {
                                desc = ev;
                            }
                        } else if (typeof ev === 'object' && ev !== null) {
                            desc = ev.description || ev.name || '';
                            filePath = ev.file || ev.path || ev.image || ev.photo || '';
                        }

                        evidenceHtml += `<div class="evidence-item-container">`;
                        if (desc) {
                            evidenceHtml += `<span><strong>Description:</strong> ${desc}</span>`;
                        }
                        if (filePath) {
                            evidenceHtml += `<a href="${filePath}" target="_blank" title="Click to view full size">
                                                <img src="${filePath}" alt="Evidence Photo" class="evidence-img-preview">
                                             </a>`;
                        }
                        if (!desc && !filePath) {
                            evidenceHtml += `<span>${JSON.stringify(ev)}</span>`;
                        }
                        evidenceHtml += `</div>`;
                    });
                    evidenceHtml += '</div>';
                } else {
                    evidenceHtml = '<p>None recorded</p>';
                }
            } catch(e) { evidenceHtml = '<p>None recorded</p>'; }

            const modalBodyHtml = `
                <div class="detail-section-title">General Information</div>
                <div class="grid-3">
                    <div class="detail-group">
                        <label>Case ID</label>
                        <p>${data.case_id || 'N/A'}</p>
                    </div>
                    <div class="detail-group">
                        <label>Judicial Status</label>
                        <p>${data.status || 'N/A'}</p>
                    </div>
                    <div class="detail-group">
                        <label>Date Filed</label>
                        <p>${data.created_at || 'N/A'}</p>
                    </div>
                </div>

                <div class="detail-section-title">Incident Information</div>
                <div class="grid-2">
                    <div class="detail-group">
                        <label>Type of Incident / Nature</label>
                        <p>${data.type_of_incident || data.nature_of_dispute || 'Unspecified'}</p>
                    </div>
                    <div class="detail-group">
                        <label>Incident Date & Time</label>
                        <p>${data.incident_date || 'N/A'}</p>
                    </div>
                </div>
                <div class="detail-group">
                    <label>Incident Location</label>
                    <p>${data.incident_location || 'N/A'}</p>
                </div>

                <div class="detail-section-title">Complainant / Reporter Details</div>
                <div class="grid-2">
                    <div class="detail-group">
                        <label>Reporter Name</label>
                        <p>${data.reporter_name || 'N/A'}</p>
                    </div>
                    <div class="detail-group">
                        <label>Contact Number</label>
                        <p>${data.reporter_contact || 'N/A'}</p>
                    </div>
                </div>
                <div class="detail-group">
                    <label>Reporter Address</label>
                    <p>${data.reporter_address || 'N/A'}</p>
                </div>

                <div class="detail-section-title">Respondent Details</div>
                <div class="grid-2">
                    <div class="detail-group">
                        <label>Respondent Name</label>
                        <p>${data.respondent_name || 'N/A'}</p>
                    </div>
                    <div class="detail-group">
                        <label>Respondent Contact</label>
                        <p>${data.respondent_contact || 'N/A'}</p>
                    </div>
                </div>
                <div class="detail-group">
                    <label>Respondent Address</label>
                    <p>${data.respondent_address || 'N/A'}</p>
                </div>

                <div class="detail-section-title">Parties Involved</div>
                <div class="detail-group">
                    <label>Suspects / Persons Involved</label>
                    ${suspectsHtml}
                </div>
                <div class="detail-group" style="margin-top: 10px;">
                    <label>Witnesses</label>
                    ${witnessesHtml}
                </div>

                <div class="detail-section-title">Narrative & Evidence</div>
                <div class="detail-group">
                    <label>Narrative / Incident Details</label>
                    <p style="white-space: pre-wrap; line-height: 1.5;">${data.narrative || data.incident_details || data.details || 'No narrative provided.'}</p>
                </div>
                <div class="detail-group" style="margin-top: 10px;">
                    <label>Evidence Items</label>
                    ${evidenceHtml}
                </div>
            `;

            document.getElementById('modalBody').innerHTML = modalBodyHtml;
            document.getElementById('viewModal').style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target === modal) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>