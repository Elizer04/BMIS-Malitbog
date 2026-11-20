<?php
// admin_certificationcopy.php
session_start();
include 'db_connect.php'; 

// Security Check: Ensure only authorized official or admin access
if (!isset($_SESSION['email']) || ($_SESSION['role'] !== 'official' && $_SESSION['role'] !== 'admin')) {
    header("Location: official_portal.php");
    exit();
}

$user_name = $_SESSION['name'] ?? 'Barangay Official';
$official_email = $_SESSION['email'] ?? 'official@malitbog.gov.ph';

// Query total counts for all document types[cite: 6]
$residency_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cert_of_residency");
$total_residency = mysqli_fetch_assoc($residency_count_result)['total'] ?? 0;

$indigency_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cert_of_indigency");
$total_indigency = mysqli_fetch_assoc($indigency_count_result)['total'] ?? 0;

$clearance_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM barangay_clearance");
$total_clearance = mysqli_fetch_assoc($clearance_count_result)['total'] ?? 0;

$business_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM business_certification");
$total_business = mysqli_fetch_assoc($business_count_result)['total'] ?? 0;

$non_tenancy_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certification_of_non_tenancy");
$total_non_tenancy = mysqli_fetch_assoc($non_tenancy_count_result)['total'] ?? 0;

$same_person_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM one_same_person");
$total_same_person = mysqli_fetch_assoc($same_person_count_result)['total'] ?? 0;

$income_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM livelihood_income");
$total_income = mysqli_fetch_assoc($income_count_result)['total'] ?? 0;

$property_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM property_agricultural_ownership");
$total_property = mysqli_fetch_assoc($property_count_result)['total'] ?? 0;

$widening_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM road_widening_boundaries");
$total_widening = mysqli_fetch_assoc($widening_count_result)['total'] ?? 0;

$history_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM family_residency_history");
$total_history = mysqli_fetch_assoc($history_count_result)['total'] ?? 0;

$good_moral_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certificate_of_good_moral");
$total_good_moral = mysqli_fetch_assoc($good_moral_count_result)['total'] ?? 0;

$unemployment_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certificate_of_unemployment");
$total_unemployment = mysqli_fetch_assoc($unemployment_count_result)['total'] ?? 0;

$business_closure_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certificate_of_business_closure");
$total_business_closure = mysqli_fetch_assoc($business_closure_count_result)['total'] ?? 0;

$others_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM other_certifications");
$total_others = mysqli_fetch_assoc($others_count_result)['total'] ?? 0;

$total_cert_count = $total_residency + $total_indigency + $total_clearance + $total_business + $total_non_tenancy + $total_same_person + $total_income + $total_property + $total_widening + $total_history + $total_good_moral + $total_unemployment + $total_business_closure + $total_others;

// Combine records from all tables via UNION ALL[cite: 6]
$query = "
    SELECT resident_id, full_name, contact_no, email, 'residency' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM cert_of_residency
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'indigency' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM cert_of_indigency
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'clearance' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM barangay_clearance
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'business_cert' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM business_certification
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'non_tenancy' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM certification_of_non_tenancy
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'same_person' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM one_same_person
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'income' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM livelihood_income
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'property_agricultural' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM property_agricultural_ownership
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'widening_road' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM road_widening_boundaries
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'family_history' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM family_residency_history
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'good_moral' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM certificate_of_good_moral
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'unemployment' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM certificate_of_unemployment
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'business_closure' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM certificate_of_business_closure
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'others' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM other_certifications
    ORDER BY created_at DESC
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certification Records (View Only) - Barangay Malitbog</title>
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

        .back-link:hover { color: var(--accent-gold); }

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

        .rd-nav-item.active { color: var(--accent-gold); }

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

        /* Filter Cards Grid Style Integration */
        .filter-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .filter-card {
            background: #fdfdfd;
            border: 2px solid #e9ecef;
            border-radius: 14px;
            padding: 15px;
            text-decoration: none;
            color: #333;
            display: flex;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
        }

        .filter-card i {
            font-size: 1.3rem;
            color: var(--primary-blue);
            margin-bottom: 2px;
            transition: transform 0.2s ease;
        }

        .filter-card .card-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-card .card-count {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .filter-card:hover {
            border-color: var(--primary-blue);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,102,0.08);
        }

        .filter-card:hover i {
            transform: scale(1.1);
        }

        .filter-card.active {
            background: linear-gradient(135deg, var(--primary-blue), #1a1a8c);
            border-color: var(--primary-blue);
            color: white;
            box-shadow: 0 6px 15px rgba(0,0,102,0.2);
        }

        .filter-card.active i,
        .filter-card.active .card-label,
        .filter-card.active .card-count {
            color: white;
        }

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

        .request-table {
            width: 100%;
            border-collapse: collapse;
        }

        .request-table th {
            background: #f8f9fa;
            color: var(--primary-blue);
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #eee;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .request-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            display: inline-block;
            text-transform: capitalize;
        }

        .pending { background: #fff3e0; color: #ef6c00; }
        .approved { background: #e8f5e9; color: #2e7d32; }
        .rejected { background: #ffebee; color: #c62828; }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.75rem;
            text-transform: uppercase;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 3px;
        }

        .btn-details { background: var(--primary-blue); color: white; }
        .btn-details:hover { background: #000044; }
        .btn-file { background: #e3f2fd; color: #1565c0; margin-right: 5px; }

        .request-row { display: table-row; }

        /* Modal Styles matching Dashboard */
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
            padding: 20px; 
            border-radius: 12px; 
            width: 95%; 
            max-width: 450px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); 
            position: relative; 
            box-sizing: border-box;
            max-height: 90vh;
            overflow-y: auto;
        }
        .close-btn { 
            background: none; 
            border: none; 
            font-size: 1.5rem; 
            font-weight: bold; 
            color: #aaa; 
            cursor: pointer; 
        }
        .close-btn:hover { color: #333; }
        .modal-header { border-bottom: 2px solid #eee; padding-bottom: 8px; margin-bottom: 15px; }
        .modal-header h3 { margin: 0; color: var(--primary-blue); font-size: 1.15rem; }
        .modal-body-content { padding: 5px 0; font-size: 0.9rem; color: #333; line-height: 1.4; }
        .modal-footer {
            margin-top: 15px; text-align: right; padding-top: 12px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 8px;
        }
        .btn-modal-action { background: var(--primary-blue); color: white; padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; transition: 0.3s; }
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

    <!-- Top bar matches edge alignment -->
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

    <!-- Main Navigation Bar with back link to official_dashboard.php -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="official_dashboard.php" class="back-link" title="Go Back">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Logo">
            <div class="logo-text">
                <h2>BARANGAY MALITBOG</h2>
                <small>OFFICIAL PORTAL</small>
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
                    <h4><?php echo htmlspecialchars($user_name); ?></h4>
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

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 360px;">
            <span class="close-btn" id="closeModalBtn" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';" style="position: absolute; right: 15px; top: 12px;">&times;</span>
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content">
                <p style="margin: 0;">Are you sure you want to leave or log out?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" id="cancelLogoutBtn" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';">Cancel</button>
                <a href="index.php" style="text-decoration: none;">
                    <button type="button" class="btn-modal-action">Yes</button>
                </a>
            </div>
        </div>
    </div>

    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>CERTIFICATION <span>RECORDS</span></h1>
            <p>View official resident certification records</p>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: bold; font-size: 1.2rem; color: var(--accent-gold);">
                TOTAL: <?php echo $total_cert_count; ?>
            </div>
            <p>System Status: <span style="color: #00ff00;">Online</span></p>
        </div>
    </header>

    <div class="container">
        <div class="section-title">
            <span>Certification Records (View Only)</span>
            <small style="font-weight: normal; font-size: 0.8rem; color: #666;">Document Filter Queue</small>
        </div>

        <div class="action-bar">
            <input type="text" id="searchInput" class="search-box" onkeyup="filterCertifications()" placeholder="Search by name, ID, or purpose...">
        </div>

        <div class="filter-cards-grid">
            <div class="filter-card active" onclick="filterTab('all', this)">
                <i class="fas fa-layer-group"></i>
                <span class="card-label">All Requests</span>
                <span class="card-count"><?php echo $total_cert_count; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('residency', this)">
                <i class="fas fa-home"></i>
                <span class="card-label">Residency</span>
                <span class="card-count"><?php echo $total_residency; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('indigency', this)">
                <i class="fas fa-hands-helping"></i>
                <span class="card-label">Indigency</span>
                <span class="card-count"><?php echo $total_indigency; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('clearance', this)">
                <i class="fas fa-file-alt"></i>
                <span class="card-label">Clearance</span>
                <span class="card-count"><?php echo $total_clearance; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('business_cert', this)">
                <i class="fas fa-store"></i>
                <span class="card-label">Business Cert</span>
                <span class="card-count"><?php echo $total_business; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('non_tenancy', this)">
                <i class="fas fa-file-contract"></i>
                <span class="card-label">Non-Tenancy</span>
                <span class="card-count"><?php echo $total_non_tenancy; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('same_person', this)">
                <i class="fas fa-user-check"></i>
                <span class="card-label">Same Person</span>
                <span class="card-count"><?php echo $total_same_person; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('income', this)">
                <i class="fas fa-peso-sign"></i>
                <span class="card-label">Income / Livelihood</span>
                <span class="card-count"><?php echo $total_income; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('property_agricultural', this)">
                <i class="fas fa-tractor"></i>
                <span class="card-label">Property / Agri</span>
                <span class="card-count"><?php echo $total_property; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('widening_road', this)">
                <i class="fas fa-road"></i>
                <span class="card-label">Road Widening</span>
                <span class="card-count"><?php echo $total_widening; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('family_history', this)">
                <i class="fas fa-users"></i>
                <span class="card-label">Family History</span>
                <span class="card-count"><?php echo $total_history; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('good_moral', this)">
                <i class="fas fa-award"></i>
                <span class="card-label">Good Moral</span>
                <span class="card-count"><?php echo $total_good_moral; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('unemployment', this)">
                <i class="fas fa-user-slash"></i>
                <span class="card-label">Unemployment</span>
                <span class="card-count"><?php echo $total_unemployment; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('business_closure', this)">
                <i class="fas fa-store-slash"></i>
                <span class="card-label">Business Closure</span>
                <span class="card-count"><?php echo $total_business_closure; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('others', this)">
                <i class="fas fa-file-invoice"></i>
                <span class="card-label">Others</span>
                <span class="card-count"><?php echo $total_others; ?></span>
            </div>
        </div>

        <table class="request-table" id="certTable">
            <thead>
                <tr>
                    <th>Resident ID</th>
                    <th>Resident Name</th>
                    <th>Contact No</th>
                    <th>Document Type</th>
                    <th>Purpose</th>
                    <th>Requirement File</th>
                    <th>Status</th>
                    <th>Date Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $status = htmlspecialchars($row['status']);
                        $statusClass = strtolower($status);
                        $docCategory = strtolower($row['cert_type']); 
                        $residentEmail = htmlspecialchars($row['email'] ?? '');
                ?>
                <tr class="request-row" data-doc="<?php echo $docCategory; ?>">
                    <td>#<?php echo htmlspecialchars($row['resident_id']); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['contact_no']); ?></td>
                    <td><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($row['cert_type']))); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['purpose_cat']); ?></strong><br>
                        <small style="color: #666;"><?php echo htmlspecialchars($row['purpose_detail']); ?></small>
                    </td>
                    <td>
                        <?php if (!empty($row['requirement_file'])): ?>
                            <a href="<?php echo htmlspecialchars($row['requirement_file']); ?>" target="_blank" class="action-btn btn-file">
                                <i class="fa fa-eye"></i> View
                            </a>
                        <?php else: ?>
                            <span style="color: #999;">None</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <button type="button" class="action-btn btn-details" 
                                onclick="openDetailsModal('<?php echo $row['resident_id']; ?>', '<?php echo $docCategory; ?>', '<?php echo addslashes($row['full_name']); ?>', '<?php echo htmlspecialchars($row['contact_no']); ?>', '<?php echo $residentEmail; ?>', '<?php echo $status; ?>')">
                            <i class="fa fa-info-circle"></i> Details
                        </button>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr class="no-data-row">
                    <td colspan="9" style="text-align: center; color: #777;">No certification records found.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Details Modal (View Only) -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <button type="button" class="close-btn" onclick="closeDetailsModal()" style="position: absolute; right: 15px; top: 12px;">&times;</button>
            <div class="modal-header">
                <h3>Request Details (View Only)</h3>
            </div>
            
            <div style="margin-bottom: 15px; font-size: 0.9rem; color: #555;">
                <p><strong>Resident ID:</strong> <span id="detResidentId"></span></p>
                <p><strong>Name:</strong> <span id="detResidentName"></span></p>
                <p><strong>Contact No:</strong> <span id="detContactNo"></span></p>
                <p><strong>Email:</strong> <span id="detEmail"></span></p>
                <p><strong>Current Status:</strong> <span id="detStatusBadge" class="status-badge"></span></p>
            </div>

            <div style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 10px; display: block; color: #333;">Available Actions:</label>
                <div id="detActionButtonsContainer" style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <span style="color: #777; font-size: 0.85rem; font-style: italic;">Viewing mode only. Print and modification actions are disabled for this portal.</span>
                </div>
            </div>

            <div class="modal-footer" style="margin-top: 25px;">
                <button type="button" class="btn-modal-cancel" onclick="closeDetailsModal()">Close</button>
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
            document.getElementById('logoutModal').style.display = 'none';
            document.getElementById('detailsModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        const logoutBtn = document.getElementById('logoutBtn');
        const logoutModal = document.getElementById('logoutModal');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('menuSlideDrawer').style.display = 'none';
                logoutModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            });
        }

        let currentActiveDocType = 'all';

        function filterTab(docType, element) {
            const cards = document.querySelectorAll('.filter-card');
            cards.forEach(card => card.classList.remove('active'));
            element.classList.add('active');
            currentActiveDocType = docType;

            filterCertifications();
        }

        function filterCertifications() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('certTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                if (tr[i].classList.contains('no-data-row')) continue;

                const docAttr = tr[i].getAttribute('data-doc');
                const tdId = tr[i].getElementsByTagName('td')[0];
                const tdName = tr[i].getElementsByTagName('td')[1];
                const tdPurpose = tr[i].getElementsByTagName('td')[4];

                let matchesTab = (currentActiveDocType === 'all' || docAttr === currentActiveDocType);

                if (matchesTab) {
                    if (tdId || tdName || tdPurpose) {
                        const idVal = tdId ? (tdId.textContent || tdId.innerText) : '';
                        const nameVal = tdName ? (tdName.textContent || tdName.innerText) : '';
                        const purposeVal = tdPurpose ? (tdPurpose.textContent || tdPurpose.innerText) : '';

                        if (
                            idVal.toUpperCase().indexOf(filter) > -1 ||
                            nameVal.toUpperCase().indexOf(filter) > -1 ||
                            purposeVal.toUpperCase().indexOf(filter) > -1
                        ) {
                            tr[i].style.display = "";
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        function openDetailsModal(residentId, docType, residentName, contactNo, residentEmail, status) {
            document.getElementById('detResidentId').innerText = '#' + residentId;
            document.getElementById('detResidentName').innerText = residentName;
            document.getElementById('detContactNo').innerText = contactNo;
            document.getElementById('detEmail').innerText = residentEmail || 'N/A';
            
            const badge = document.getElementById('detStatusBadge');
            badge.className = 'status-badge ' + status.toLowerCase();
            badge.innerText = status;

            document.getElementById('detailsModal').style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        window.onclick = function(event) {
            const detailsModal = document.getElementById('detailsModal');
            if (event.target === detailsModal) closeDetailsModal();
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
                globalOverlay.style.display = 'none';
            }
            if (event.target === globalOverlay) {
                closeAllModals();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const activeCard = document.querySelector('.filter-card.active');
            if (activeCard) {
                filterTab('all', activeCard);
            }
        });
    </script>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>