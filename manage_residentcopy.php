<?php
// 1. Start session and enforce access control (Allow Officials & Admins in view mode)
session_start();

// Include central database connection
include 'db_connect.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? '';
$is_admin = ($user_role === 'admin' || isset($_SESSION['is_admin']));
$is_official = ($user_role === 'official');

// Setup CSV backup/export directory functionality matching admin design
$backup_dir = __DIR__ . '/../backups/';
if (!is_dir($backup_dir)) {
    @mkdir($backup_dir, 0755, true);
}

$csv_filename = 'residents_roster_' . date('Y-m-d') . '.csv';
$csv_filepath = $backup_dir . $csv_filename;

function generate_resident_csv($conn, $filepath) {
    $query = "SELECT resident_id, full_name, first_name, middle_name, last_name, sex, 
                     occupation, membership, precinct_number, religion, citizenship, 
                     voter_status, is_head_of_family, number_of_dependents, 
                     spouse_name, date_of_birth, civil_status, address, email, 
                     contact_no, status, created_at 
              FROM residents ORDER BY resident_id DESC";
              
    $result = $conn->query($query);
    if (!$result) return false;

    $file = fopen($filepath, 'w');
    if (!$file) return false;

    $headers = [
        'Resident ID', 'Full Name', 'First Name', 'Middle Name', 'Last Name', 'Sex', 
        'Occupation', 'Membership', 'Precinct Number', 'Religion', 'Citizenship', 
        'Voter Status', 'Is Head of Family', 'Number of Dependents', 
        'Spouse Name', 'Date of Birth', 'Civil Status', 'Address', 'Email', 
        'Contact No', 'Status', 'Created At'
    ];
    fputcsv($file, $headers);

    while ($row = $result->fetch_assoc()) {
        fputcsv($file, $row);
    }

    fclose($file);
    return true;
}

if (!file_exists($csv_filepath)) {
    @generate_resident_csv($conn, $csv_filepath);
}

if (isset($_GET['download_csv']) && $_GET['download_csv'] == '1') {
    $fresh_filename = 'residents_roster_fresh_' . date('Y-m-d_H-i-s') . '.csv';
    $temp_filepath = sys_get_temp_dir() . '/' . $fresh_filename;
    
    if (generate_resident_csv($conn, $temp_filepath)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fresh_filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($temp_filepath);
        @unlink($temp_filepath); 
        exit();
    }
}

// Fetch database records matching column definitions (Pending, Incomplete, Verified, and All Total)
$pending_query = "SELECT * FROM residents WHERE LOWER(status) IN ('pending', 'pending registration') ORDER BY resident_id DESC";
$incomplete_query = "SELECT * FROM residents WHERE LOWER(status) = 'incomplete' ORDER BY resident_id DESC";
$verified_query = "SELECT * FROM residents WHERE LOWER(status) = 'verified' ORDER BY resident_id DESC";
$total_query = "SELECT * FROM residents ORDER BY resident_id DESC";

$pending_result = $conn->query($pending_query);
$incomplete_result = $conn->query($incomplete_query);
$verified_result = $conn->query($verified_query);
$total_result = $conn->query($total_query);

$pending_count = $pending_result ? $pending_result->num_rows : 0;
$incomplete_count = $incomplete_result ? $incomplete_result->num_rows : 0;
$verified_count = $verified_result ? $verified_result->num_rows : 0;
$total_count = $total_result ? $total_result->num_rows : 0;

$official_name = $_SESSION['name'] ?? 'Barangay Official';
$official_email = $_SESSION['email'] ?? 'official@malitbog.gov.ph';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Residents (View Only) - Barangay Malitbog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #000066;
            --accent-gold: #ffcc00;
            --text-white: #ffffff;
            --white: #ffffff;
            --bg-light: #f4f7f6;
            --admin-red: #cc0000;
            --admin-orange: #d97706;
            --admin-green: #28a745;
            --admin-blue: #0284c7;
        }

        html, body {
            min-height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            font-family: 'Segoe UI', Arial, 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-light);
            -webkit-text-size-adjust: 100%;
        }

        /* Top Bar aligned edge-to-edge with the main-nav (5% padding) */
        .topbar {
            background: #1a1a8c;
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
        .rd-menu-symbol-trigger.custom-profile-style span,
        .rd-nav-menu-bar-trigger span {
            font-family: 'Poppins', sans-serif;
            font-size: 0.75rem;
            text-transform: capitalize;
            letter-spacing: 0.5px;
        }

        .rd-nav-item i, 
        .rd-menu-symbol-trigger i,
        .rd-nav-menu-bar-trigger i {
            font-size: 1.1rem;
        }

        .rd-nav-item:hover,
        .rd-menu-symbol-trigger:hover,
        .rd-nav-menu-bar-trigger:hover,
        .rd-menu-symbol-trigger.active {
            color: var(--accent-gold);
        }

        .rd-nav-link-dropdown {
            display: inline-flex;
            align-items: center;
            position: relative;
        }

        .rd-nav-menu-bar-trigger {
            cursor: pointer;
            position: relative;
        }

        .rd-nav-item.active {
            color: var(--accent-gold);
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

        .hero-banner {
            background: linear-gradient(rgba(0, 0, 102, 0.7), rgba(0, 0, 102, 0.7)), 
                        url('image/backgroundphoto1.jpg') no-repeat center center/cover;
            padding: 80px 10%;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-msg h1 { margin: 0; font-size: 2.0rem; }
        .welcome-msg p { font-size: 1.0rem; margin-top: 10px; opacity: 0.8; }
        .welcome-msg span { color: var(--accent-gold); }

        .container {
            max-width: 1200px;
            margin: -30px auto 40px;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .section-title {
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 25px;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: #f1f3f5;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            color: #555;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover { background: #e2e6ea; }
        .tab-btn.active { background: var(--primary-blue); color: white; }

        .tab-count {
            background: rgba(0, 0, 0, 0.15);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .tab-btn.active .tab-count { background: var(--accent-gold); color: #000; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 350px;
            font-family: inherit;
        }

        /* Ellipsis Dropdown Styles */
        .action-dropdown-container {
            position: relative;
            display: inline-block;
        }

        .btn-ellipsis {
            background: #f1f3f5;
            color: #333;
            padding: 12px 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-ellipsis:hover {
            background: #e2e6ea;
            color: var(--primary-blue);
        }

        .ellipsis-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 5px);
            background-color: white;
            min-width: 210px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            border-radius: 8px;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .ellipsis-menu a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }

        .ellipsis-menu a:hover {
            background: #f8f9fa;
            color: var(--primary-blue);
        }

        .ellipsis-menu a i {
            color: var(--admin-green);
            width: 16px;
            text-align: center;
        }

        .resident-table {
            width: 100%;
            border-collapse: collapse;
        }

        .resident-table th {
            background: #f8f9fa;
            color: var(--primary-blue);
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #eee;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .resident-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        .status-pill {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-incomplete { background: #feefc3; color: #b45309; }
        .status-active { background: #e6ffed; color: #28a745; }

        .action-btns i {
            margin-right: 12px;
            cursor: pointer;
            color: var(--primary-blue);
            transition: 0.2s;
        }

        .action-btns i:hover { transform: scale(1.2); }

        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 2000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            background-color: rgba(0, 0, 0, 0.5); 
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 15px;
            box-sizing: border-box;
        }

        .modal-content {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 650px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            box-sizing: border-box;
        }

        .close-btn {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close-btn:hover { color: #000; }

        .modal-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--primary-blue);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f4f4f4;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
            flex-shrink: 0;
            margin-right: 15px;
        }

        .modal-profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .profile-img-preview {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-blue);
        }

        .modal-footer {
            margin-top: 20px;
            text-align: right;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-modal-action { background: var(--primary-blue); color: white; padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; transition: 0.3s; }
        .btn-modal-action:hover { background: #1a1a8c; }
        .btn-modal-cancel { background: #ccc; color: #333; padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; transition: 0.3s; }
        .btn-modal-cancel:hover { background: #b3b3b3; }

        @media (max-width: 768px) {
            .topbar { display: none; }
            .main-nav { padding: 8px 12px; }
            .nav-links { gap: 15px; }
            .rd-nav-item span,
            .rd-menu-symbol-trigger span,
            .rd-nav-menu-bar-trigger span {
                display: none;
            }
            .menu-slide-drawer {
                max-width: 50% !important;
                width: 50% !important;
            }
            .hero-banner { padding: 40px 5%; flex-direction: column; text-align: center; gap: 20px; }
        }
    </style>
</head>
<body>

    <!-- Top bar matches dashboard edge alignment & social markup -->
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
                <span style="font-size: 0.8rem;"><i class="fa fa-id-badge"></i> Barangay Official Portal (View Only)</span>
            </div>
        </div>
    </div>

    <!-- Main Nav with functional back link to official_dashboard.php -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="official_dashboard.php" class="back-link" title="Go Back">
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
            <a href="barangay_profilecopy.php"><i class="fas fa-university"></i> Barangay Info</a>
            <a href="javascript:void(0);" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>RESIDENT <span>DIRECTORY</span></h1>
            <p>Viewing Registered Residents of Barangay Malitbog</p>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: bold; font-size: 1.2rem; color: var(--accent-gold);">
                TOTAL: <?php echo $total_count; ?>
            </div>
            <p>System Status: <span style="color: #00ff00;">Online</span></p>
        </div>
    </header>

    <div class="container">
        <div class="section-title">
            <span>Resident Directory (View Only)</span>
            <small style="font-weight: normal; font-size: 0.8rem; color: #666;">Database Records</small>
        </div>

        <div class="action-bar">
            <input type="text" id="searchInput" class="search-box" onkeyup="filterResidents()" placeholder="Search by name or ID">
            <div style="display: flex; gap: 10px; align-items: center;">
                <!-- Ellipsis Action Menu for Download -->
                <div class="action-dropdown-container">
                    <button type="button" class="btn-ellipsis" onclick="toggleEllipsisMenu(event)" title="More Options">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="ellipsis-menu" id="ellipsisDropdownMenu">
                        <a href="?download_csv=1">
                            <i class="fa fa-download"></i> Download Latest List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="category-tabs">
            <button class="tab-btn active" onclick="switchTab('total')">
                <i class="fa fa-users"></i> Total Resident <span class="tab-count"><?php echo $total_count; ?></span>
            </button>
            <button class="tab-btn" onclick="switchTab('pending')">
                <i class="fa fa-clock"></i> Pending <span class="tab-count"><?php echo $pending_count; ?></span>
            </button>
            <button class="tab-btn" onclick="switchTab('incomplete')">
                <i class="fa fa-exclamation-circle"></i> Incomplete <span class="tab-count"><?php echo $incomplete_count; ?></span>
            </button>
            <button class="tab-btn" onclick="switchTab('verified')">
                <i class="fa fa-check-circle"></i> Verified <span class="tab-count"><?php echo $verified_count; ?></span>
            </button>
        </div>

        <div id="total-tab" class="tab-content active">
            <table class="resident-table" id="totalTable">
                <thead>
                    <tr>
                        <th>Resident ID</th>
                        <th>Full Name</th>
                        <th>Birthdate</th>
                        <th>Civil Status</th>
                        <th>Address</th>
                        <th>Contact Info</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_result && $total_result->num_rows > 0): ?>
                        <?php while($row = $total_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['resident_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['date_of_birth']); ?></td>
                                <td><?php echo htmlspecialchars($row['civil_status']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td>
                                    <div><small><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?></small></div>
                                    <div><small><i class="fa fa-phone"></i> <?php echo htmlspecialchars($row['contact_no']); ?></small></div>
                                </td>
                                <td>
                                    <span class="status-pill <?php echo (strtolower($row['status']) === 'verified') ? 'status-active' : ((strtolower($row['status']) === 'incomplete') ? 'status-incomplete' : 'status-pending'); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-btns">
                                    <i class="fa fa-eye" title="View Details" onclick='viewResident(<?php echo json_encode($row); ?>)'></i>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-data-row">
                            <td colspan="8" style="text-align: center;">No resident records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="pending-tab" class="tab-content">
            <table class="resident-table" id="pendingTable">
                <thead>
                    <tr>
                        <th>Resident ID</th>
                        <th>Full Name</th>
                        <th>Birthdate</th>
                        <th>Civil Status</th>
                        <th>Address</th>
                        <th>Contact Info</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                        <?php while($row = $pending_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['resident_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['date_of_birth']); ?></td>
                                <td><?php echo htmlspecialchars($row['civil_status']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td>
                                    <div><small><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?></small></div>
                                    <div><small><i class="fa fa-phone"></i> <?php echo htmlspecialchars($row['contact_no']); ?></small></div>
                                </td>
                                <td>
                                    <span class="status-pill status-pending">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-btns">
                                    <i class="fa fa-eye" title="View Details" onclick='viewResident(<?php echo json_encode($row); ?>)'></i>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-data-row">
                            <td colspan="8" style="text-align: center;">No pending resident records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="incomplete-tab" class="tab-content">
            <table class="resident-table" id="incompleteTable">
                <thead>
                    <tr>
                        <th>Resident ID</th>
                        <th>Full Name</th>
                        <th>Birthdate</th>
                        <th>Civil Status</th>
                        <th>Address</th>
                        <th>Contact Info</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($incomplete_result && $incomplete_result->num_rows > 0): ?>
                        <?php while($row = $incomplete_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['resident_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['date_of_birth']); ?></td>
                                <td><?php echo htmlspecialchars($row['civil_status']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td>
                                    <div><small><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?></small></div>
                                    <div><small><i class="fa fa-phone"></i> <?php echo htmlspecialchars($row['contact_no']); ?></small></div>
                                </td>
                                <td>
                                    <span class="status-pill status-incomplete">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-btns">
                                    <i class="fa fa-eye" title="View Details" onclick='viewResident(<?php echo json_encode($row); ?>)'></i>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-data-row">
                            <td colspan="8" style="text-align: center;">No incomplete resident records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="verified-tab" class="tab-content">
            <table class="resident-table" id="verifiedTable">
                <thead>
                    <tr>
                        <th>Resident ID</th>
                        <th>Full Name</th>
                        <th>Birthdate</th>
                        <th>Civil Status</th>
                        <th>Address</th>
                        <th>Contact Info</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($verified_result && $verified_result->num_rows > 0): ?>
                        <?php while($row = $verified_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['resident_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['date_of_birth']); ?></td>
                                <td><?php echo htmlspecialchars($row['civil_status']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td>
                                    <div><small><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?></small></div>
                                    <div><small><i class="fa fa-phone"></i> <?php echo htmlspecialchars($row['contact_no']); ?></small></div>
                                </td>
                                <td>
                                    <span class="status-pill status-active">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-btns">
                                    <i class="fa fa-eye" title="View Details" onclick='viewResident(<?php echo json_encode($row); ?>)'></i>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-data-row">
                            <td colspan="8" style="text-align: center;">No verified resident records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Resident Details Modal (View Only) -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <button type="button" class="close-btn" onclick="closeModal('detailsModal')">&times;</button>
            <div class="modal-header">
                <h3>Resident Complete Details (View Only)</h3>
            </div>
            
            <div class="modal-profile-header">
                <img id="modal-img-profile" src="" alt="Profile Image" class="profile-img-preview">
                <div>
                    <h3 id="modal-name" style="margin: 0; color: var(--primary-blue);"></h3>
                    <small id="modal-id-badge" style="color: #666; font-weight: bold;"></small>
                </div>
            </div>

            <div class="detail-row"><span class="detail-label">Resident ID:</span><span id="modal-id"></span></div>
            <div class="detail-row"><span class="detail-label">First Name:</span><span id="modal-first-name"></span></div>
            <div class="detail-row"><span class="detail-label">Middle Name:</span><span id="modal-middle-name"></span></div>
            <div class="detail-row"><span class="detail-label">Last Name:</span><span id="modal-last-name"></span></div>
            <div class="detail-row"><span class="detail-label">Sex:</span><span id="modal-sex"></span></div>
            <div class="detail-row"><span class="detail-label">Birthdate:</span><span id="modal-dob"></span></div>
            <div class="detail-row"><span class="detail-label">Civil Status:</span><span id="modal-civil"></span></div>
            <div class="detail-row"><span class="detail-label">Religion:</span><span id="modal-religion"></span></div>
            <div class="detail-row"><span class="detail-label">Citizenship:</span><span id="modal-citizenship"></span></div>
            <div class="detail-row"><span class="detail-label">Occupation:</span><span id="modal-occupation"></span></div>
            <div class="detail-row"><span class="detail-label">Is Head of Family:</span><span id="modal-head-family"></span></div>
            <div class="detail-row"><span class="detail-label">Number of Dependents:</span><span id="modal-dependents"></span></div>
            <div class="detail-row"><span class="detail-label">Spouse Name:</span><span id="modal-spouse"></span></div>
            <div class="detail-row"><span class="detail-label">Precinct Number:</span><span id="modal-precinct"></span></div>
            <div class="detail-row"><span class="detail-label">Voter Status:</span><span id="modal-voter-status"></span></div>
            <div class="detail-row"><span class="detail-label">Membership:</span><span id="modal-membership"></span></div>
            <div class="detail-row"><span class="detail-label">Address:</span><span id="modal-address"></span></div>
            <div class="detail-row"><span class="detail-label">Email:</span><span id="modal-email"></span></div>
            <div class="detail-row"><span class="detail-label">Phone:</span><span id="modal-phone"></span></div>
            <div class="detail-row"><span class="detail-label">Status:</span><span id="modal-status"></span></div>
            <div class="detail-row"><span class="detail-label">Remarks / Notice:</span><span id="modal-remarks" style="white-space: pre-line;"></span></div>
            <div class="detail-row"><span class="detail-label">Created At:</span><span id="modal-created-at"></span></div>

            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('detailsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- System Notice Modal (Used for Logout) -->
    <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 360px;">
            <button type="button" class="close-btn" onclick="document.getElementById('logoutModal').style.display='none'" style="position: absolute; right: 15px; top: 12px;">&times;</button>
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content" style="padding: 5px 0; font-size: 0.9rem; color: #333;">
                <p style="margin: 0;">Are you sure you want to leave or log out?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="document.getElementById('logoutModal').style.display='none'">Cancel</button>
                <a href="index.php" style="text-decoration: none;">
                    <button type="button" class="btn-modal-action">Yes</button>
                </a>
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

        function toggleEllipsisMenu(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('ellipsisDropdownMenu');
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            } else {
                dropdown.style.display = 'block';
            }
        }

        function closeAllModals() {
            document.getElementById('menuSlideDrawer').style.display = 'none';
            document.getElementById('logoutModal').style.display = 'none';
            const ellipsisMenu = document.getElementById('ellipsisDropdownMenu');
            if (ellipsisMenu) ellipsisMenu.style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('menuSlideDrawer').style.display = 'none';
                logoutModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            });
        }

        function switchTab(tabName) {
            const tabs = document.querySelectorAll('.tab-btn');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            if (tabName === 'total') {
                tabs[0].classList.add('active');
                document.getElementById('total-tab').classList.add('active');
            } else if (tabName === 'pending') {
                tabs[1].classList.add('active');
                document.getElementById('pending-tab').classList.add('active');
            } else if (tabName === 'incomplete') {
                tabs[2].classList.add('active');
                document.getElementById('incomplete-tab').classList.add('active');
            } else {
                tabs[3].classList.add('active');
                document.getElementById('verified-tab').classList.add('active');
            }
            filterResidents();
        }

        function filterResidents() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const tables = [
                document.getElementById('totalTable'), 
                document.getElementById('pendingTable'), 
                document.getElementById('incompleteTable'), 
                document.getElementById('verifiedTable')
            ];

            tables.forEach(table => {
                if (!table) return;
                const tr = table.getElementsByTagName('tr');
                for (let i = 1; i < tr.length; i++) {
                    if (tr[i].classList.contains('no-data-row')) continue;

                    const tdId = tr[i].getElementsByTagName('td')[0];
                    const tdName = tr[i].getElementsByTagName('td')[1];
                    const tdAddress = tr[i].getElementsByTagName('td')[4];

                    if (tdId || tdName || tdAddress) {
                        const idValue = tdId ? (tdId.textContent || tdId.innerText) : '';
                        const nameValue = tdName ? (tdName.textContent || tdName.innerText) : '';
                        const addressValue = tdAddress ? (tdAddress.textContent || tdAddress.innerText) : '';

                        if (
                            idValue.toUpperCase().indexOf(filter) > -1 ||
                            nameValue.toUpperCase().indexOf(filter) > -1 ||
                            addressValue.toUpperCase().indexOf(filter) > -1
                        ) {
                            tr[i].style.display = "";
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                }
            });
        }

        function viewResident(resident) {
            document.getElementById('modal-id').textContent = resident.resident_id || 'N/A';
            document.getElementById('modal-id-badge').textContent = 'ID: ' + (resident.resident_id || 'N/A');
            document.getElementById('modal-name').textContent = resident.full_name || 'N/A';
            
            document.getElementById('modal-first-name').textContent = resident.first_name || 'N/A';
            document.getElementById('modal-middle-name').textContent = resident.middle_name || 'N/A';
            document.getElementById('modal-last-name').textContent = resident.last_name || 'N/A';
            document.getElementById('modal-sex').textContent = resident.sex || 'N/A';
            document.getElementById('modal-dob').textContent = resident.date_of_birth || 'N/A';
            document.getElementById('modal-civil').textContent = resident.civil_status || 'N/A';
            document.getElementById('modal-religion').textContent = resident.religion || 'N/A';
            document.getElementById('modal-citizenship').textContent = resident.citizenship || 'N/A';
            document.getElementById('modal-occupation').textContent = resident.occupation || 'N/A';
            
            document.getElementById('modal-head-family').textContent = (resident.is_head_of_family == 1) ? 'Yes' : 'No';
            document.getElementById('modal-dependents').textContent = resident.number_of_dependents !== null ? resident.number_of_dependents : '0';
            document.getElementById('modal-spouse').textContent = resident.spouse_name || 'N/A';
            
            document.getElementById('modal-precinct').textContent = resident.precinct_number || 'N/A';
            document.getElementById('modal-voter-status').textContent = resident.voter_status || 'N/A';
            document.getElementById('modal-membership').textContent = resident.membership || 'N/A';
            
            document.getElementById('modal-address').textContent = resident.address || 'N/A';
            document.getElementById('modal-email').textContent = resident.email || 'N/A';
            document.getElementById('modal-phone').textContent = resident.contact_no || 'N/A';
            document.getElementById('modal-status').textContent = resident.status || 'N/A';
            document.getElementById('modal-remarks').textContent = resident.remarks || 'None';
            document.getElementById('modal-created-at').textContent = resident.created_at || 'N/A';

            const uploadPath = 'uploads/'; 
            const profileImgElem = document.getElementById('modal-img-profile');
            if (resident.profile_image) {
                profileImgElem.src = uploadPath + resident.profile_image;
            } else {
                profileImgElem.src = uploadPath + 'default.png';
            }

            document.getElementById('detailsModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            const detailsModal = document.getElementById('detailsModal');
            const logoutModal = document.getElementById('logoutModal');
            const ellipsisDropdown = document.getElementById('ellipsisDropdownMenu');
            
            if (event.target === detailsModal) detailsModal.style.display = 'none';
            if (ellipsisDropdown && ellipsisDropdown.style.display === 'block') {
                ellipsisDropdown.style.display = 'none';
            }
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
                document.getElementById('globalOverlay').style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>