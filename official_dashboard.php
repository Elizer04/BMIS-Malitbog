<?php
include 'db_connect.php';

session_start();

// Security Check: Adjust according to official role session requirements
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'official') {
    // header("Location: login.php");
    // exit();
}

$official_name = $_SESSION['name'] ?? 'Barangay Official';
$official_email = $_SESSION['email'] ?? 'official@malitbog.gov.ph';

// Use the global $conn variable from db_connect.php instead of hardcoding localhost/root/eli
$connection = $conn ?? null;

$update_success = '';
$update_error = '';

// Handle form submission for updating Official Information linked to database columns
if ($connection && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_official_info'])) {
    $new_name = mysqli_real_escape_string($connection, $_POST['name']);
    $new_contact = mysqli_real_escape_string($connection, $_POST['contact']);
    $new_position = mysqli_real_escape_string($connection, $_POST['position']);
    $target_email = mysqli_real_escape_string($connection, $official_email);

    $update_query = "UPDATE official_information SET name = '$new_name', contact = '$new_contact', position = '$new_position' WHERE email = '$target_email'";
    if (mysqli_query($connection, $update_query)) {
        $update_success = "Official information updated successfully!";
        $_SESSION['name'] = $_POST['name'];
        $official_name = $_POST['name'];
    } else {
        $update_error = "Error updating record: " . mysqli_error($connection);
    }
}

try {
    // 1. Count All Residents directly from the residents table[cite: 1]
    $stmtResidents = $pdo->query("SELECT COUNT(*) FROM residents");
    $totalResidents = $stmtResidents->fetchColumn();

    // 2. Certification Metrics[cite: 1]
    $certUnionQuery = "
        SELECT status FROM cert_of_residency
        UNION ALL
        SELECT status FROM cert_of_indigency
        UNION ALL
        SELECT status FROM barangay_clearance
        UNION ALL
        SELECT status FROM business_certification
        UNION ALL
        SELECT status FROM certification_of_non_tenancy
        UNION ALL
        SELECT status FROM one_same_person
        UNION ALL
        SELECT status FROM livelihood_income
        UNION ALL
        SELECT status FROM property_agricultural_ownership
        UNION ALL
        SELECT status FROM road_widening_boundaries
        UNION ALL
        SELECT status FROM family_residency_history
        UNION ALL
        SELECT status FROM certificate_of_good_moral
        UNION ALL
        SELECT status FROM certificate_of_unemployment
        UNION ALL
        SELECT status FROM certificate_of_business_closure
        UNION ALL
        SELECT status FROM other_certifications
    ";
    $stmtCerts = $pdo->query($certUnionQuery);
    $allCerts = $stmtCerts->fetchAll(PDO::FETCH_ASSOC);

    $totalCertifications = count($allCerts);
    $pendingCert = 0;
    $approvedCert = 0;
    $releasedCert = 0;

    foreach ($allCerts as $cert) {
        $status = strtolower(trim($cert['status'] ?? ''));
        if ($status === 'pending') {
            $pendingCert++;
        } elseif ($status === 'approved') {
            $approvedCert++;
        } elseif (in_array($status, ['released', 'completed', 'claimed'])) {
            $releasedCert++;
        }
    }

    // 3. Count Active Cases[cite: 1]
    $stmtActiveCases = $pdo->query("SELECT COUNT(*) FROM my_cases WHERE TRIM(status) != 'Settled'");
    $activeCases = $stmtActiveCases->fetchColumn();

    // 4. Health Center & Appointments Metrics[cite: 1]
    $stmtTotalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments");
    $totalAppointments = $stmtTotalAppointments->fetchColumn();

    $stmtPendingAppts = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'");
    $pendingApptsCount = $stmtPendingAppts->fetchColumn();

    $stmtConfirmedAppts = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Confirmed'");
    $confirmedApptsCount = $stmtConfirmedAppts->fetchColumn();

    $stmtAppointmentsToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE DATE(appt_date) = CURDATE()");
    $appointmentsToday = $stmtAppointmentsToday->fetchColumn();

} catch (PDOException $e) {
    $totalResidents = 0;
    $totalCertifications = 0;
    $pendingCert = 0;
    $approvedCert = 0;
    $releasedCert = 0;
    $activeCases = 0;
    $totalAppointments = 0;
    $pendingApptsCount = 0;
    $confirmedApptsCount = 0;
    $appointmentsToday = 0;
}

$official_data = [];
if ($connection) {
    $safe_email = mysqli_real_escape_string($connection, $official_email);
    $query = "SELECT * FROM official_information WHERE email = '$safe_email' LIMIT 1";
    $result = mysqli_query($connection, $query);
    if ($result) {
        $official_data = mysqli_fetch_assoc($result);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Dashboard - Barangay Malitbog</title>
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
            --text-gray: #555;
            --primary-dark: #2c3e50;
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

        * {
            box-sizing: border-box;
        }

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
            max-width: 360px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); 
            position: relative; 
            box-sizing: border-box;
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
            position: relative;
            z-index: 10;
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

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .service-card {
            border: 1px solid #eee;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            transition: 0.3s;
            position: relative;
            background: #fff;
        }

        .service-card:hover { 
            border-color: var(--accent-gold); 
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .service-card i {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }

        .sub-counts {
            font-size: 0.75rem;
            color: #555;
            margin-top: 5px;
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .sub-counts span {
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .btn-track {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
            transition: background 0.3s;
        }

        .btn-track:hover {
            background: #000044;
        }

        .badge {
            background: var(--admin-red);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            position: absolute;
            top: 15px;
            right: 15px;
        }

        /* Official Account Information Panel */
        .panel-container {
            max-width: 1200px;
            margin: 0 auto 40px;
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .panel-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--bg-light);
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .panel-container h3 {
            margin: 0;
            color: var(--primary-blue);
            font-size: 1.1rem;
        }

        .btn-edit-toggle {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-edit-toggle:hover {
            background: #1a1a8c;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            font-size: 0.9rem;
        }

        .info-item label {
            display: block;
            color: var(--text-gray);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .info-item span, .info-item input {
            color: var(--primary-dark);
            font-weight: 600;
            width: 100%;
        }

        .info-item input {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .form-actions {
            margin-top: 15px;
            display: none;
            gap: 10px;
        }

        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-save:hover {
            background: #218838;
        }

        .btn-cancel-edit {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-cancel-edit:hover {
            background: #5a6268;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }

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
                <span style="font-size: 0.8rem;"><i class="fa fa-id-badge"></i> Barangay Official Portal</span>
            </div>
        </div>
    </div>

    <nav class="main-nav">
        <div class="logo-section">
            <a href="#" id="backBtn" class="back-link" title="Go Back">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Logo">
            <div class="logo-text">
                <h2>BARANGAY MALITBOG</h2>
                <small>MUNICIPALITY OF BONGABONG</small>
            </div>
        </div>
        <div class="nav-links">
            <a href="official_dashboard.php" class="rd-nav-item custom-home-style active" title="Official Home">
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

    <div class="rd-global-overlay" id="globalOverlay" onclick="closeAllModals()"></div>

    <div class="menu-slide-drawer" id="menuSlideDrawer">
        <div class="menu-drawer-header">
            <div class="admin-info-container">
                <i class="fas fa-user-circle admin-avatar"></i>
                <div class="admin-details">
                    <h4><?php echo htmlspecialchars($official_data['name'] ?? $official_name); ?></h4>
                    <p><?php echo htmlspecialchars($official_data['email'] ?? $official_email); ?></p>
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
            <h1>WELCOME <span><?php echo htmlspecialchars(strtoupper($official_data['name'] ?? $official_name)); ?></span></h1>
            <p>Official Portal - Barangay Malitbog</p>
        </div>
        <div style="text-align: right;">
            <div id="current-date" style="font-weight: bold; font-size: 1.2rem; color: var(--accent-gold);"></div>
            <p>System Status: <span style="color: #00ff00;">Online</span></p>
        </div>
    </header>

    <div class="container">
        <div class="section-title">
            <span>Management Overview</span>
            <small style="font-weight: normal; font-size: 0.8rem; color: #666;">Real-time Data</small>
        </div>
        
        <div class="services-grid">
            <!-- 1. Total Residents Card -->
            <div class="service-card">
                <i class="fa fa-users"></i>
                <div style="font-weight: bold; color: #666;">TOTAL RESIDENTS</div>
                <div class="stat-number"><?php echo number_format($totalResidents); ?></div>
                <small>Registered records</small>
                <a href="manage_residentcopy.php" style="text-decoration: none;">
                    <button class="btn-track">Manage List</button>
                </a>
            </div>

            <!-- 2. Certifications Requests Overview Card -->
            <div class="service-card">
                <?php if ($pendingCert > 0): ?>
                    <span class="badge"><?php echo $pendingCert; ?> PENDING</span>
                <?php endif; ?>
                <i class="fa fa-file-signature"></i>
                <div style="font-weight: bold; color: #666;">CERTIFICATIONS</div>
                <div class="stat-number"><?php echo number_format($totalCertifications); ?></div>
                <div class="sub-counts">
                    <span>App: <?php echo $approvedCert; ?></span>
                    <span>Pend: <?php echo $pendingCert; ?></span>
                    <span>Rel: <?php echo $releasedCert; ?></span>
                </div>
                <a href="admin_certificationcopy.php" style="text-decoration: none;">
                    <button class="btn-track">Review Requests</button>
                </a>
            </div>
            
            <!-- 3. Blotter Cases Card -->
            <div class="service-card">
                <i class="fa fa-gavel"></i>
                <div style="font-weight: bold; color: #666;">BLOTTER CASES</div>
                <div class="stat-number"><?php echo number_format($activeCases); ?></div>
                <small>Active Cases</small>
                <a href="admin_judicialcopy.php" style="text-decoration: none;">
                    <button class="btn-track">Case Records</button>
                </a>
            </div>

            <!-- 4. Health Center & Appointments Card -->
            <div class="service-card">
                <?php if ($pendingApptsCount > 0): ?>
                    <span class="badge"><?php echo $pendingApptsCount; ?> PENDING</span>
                <?php endif; ?>
                <i class="fa fa-notes-medical"></i>
                <div style="font-weight: bold; color: #666;">HEALTH CENTER</div>
                <div class="stat-number"><?php echo number_format($totalAppointments); ?></div>
                <div class="sub-counts">
                    <span>Conf: <?php echo $confirmedApptsCount; ?></span>
                    <span>Pend: <?php echo $pendingApptsCount; ?></span>
                    <span>Today: <?php echo $appointmentsToday; ?></span>
                </div>
                <a href="admin_healthcopy.php" style="text-decoration: none;">
                    <button class="btn-track">View Schedule</button>
                </a>
            </div>
        </div>
    </div>

    <!-- Official Account Information Panel -->
    <div class="panel-container">
        <div class="panel-header-flex">
            <h3><i class="fas fa-user-gear"></i> Official Account Information</h3>
            <button type="button" class="btn-edit-toggle" id="editToggleBtn" onclick="toggleEditMode()"><i class="fas fa-edit"></i> Edit Information</button>
        </div>

        <?php if (!empty($update_success)): ?>
            <div class="alert-success"><?php echo $update_success; ?></div>
        <?php endif; ?>
        <?php if (!empty($update_error)): ?>
            <div class="alert-error"><?php echo $update_error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name</label>
                    <span class="view-mode"><?php echo htmlspecialchars($official_data['name'] ?? $official_name); ?></span>
                    <input type="text" name="name" class="edit-mode" value="<?php echo htmlspecialchars($official_data['name'] ?? $official_name); ?>" style="display:none;" required>
                </div>
                <div class="info-item">
                    <label>Email Address</label>
                    <span><?php echo htmlspecialchars($official_data['email'] ?? $official_email); ?> (Non-editable)</span>
                </div>
                <div class="info-item">
                    <label>Contact Number</label>
                    <span class="view-mode"><?php echo htmlspecialchars($official_data['contact'] ?? 'Not Specified'); ?></span>
                    <input type="text" name="contact" class="edit-mode" value="<?php echo htmlspecialchars($official_data['contact'] ?? ''); ?>" style="display:none;">
                </div>
                <div class="info-item">
                    <label>Position / Role</label>
                    <span class="view-mode"><?php echo htmlspecialchars($official_data['position'] ?? 'Barangay Official'); ?></span>
                    <input type="text" name="position" class="edit-mode" value="<?php echo htmlspecialchars($official_data['position'] ?? 'Barangay Official'); ?>" style="display:none;" required>
                </div>
            </div>

            <div class="form-actions" id="formActions">
                <button type="submit" name="update_official_info" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                <button type="button" class="btn-cancel-edit" onclick="toggleEditMode()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- System Notice Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeModalBtn" onclick="document.getElementById('logoutModal').style.display='none'" style="position: absolute; right: 15px; top: 12px;">&times;</span>
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content">
                <p style="margin: 0;">Are you sure you want to leave or log out?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" id="cancelLogoutBtn" onclick="document.getElementById('logoutModal').style.display='none'">Cancel</button>
                <a href="index.php" style="text-decoration: none;">
                    <button type="button" class="btn-modal-action">Yes</button>
                </a>
            </div>
        </div>
    </div>

    <script>
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').innerHTML = new Date().toLocaleDateString(undefined, options);

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
            globalOverlay.style.display = 'none';
        }

        function toggleEditMode() {
            const viewElements = document.querySelectorAll('.view-mode');
            const editElements = document.querySelectorAll('.edit-mode');
            const formActions = document.getElementById('formActions');
            const editBtn = document.getElementById('editToggleBtn');

            let isEditing = editElements[0].style.display === 'block';

            if (isEditing) {
                viewElements.forEach(el => el.style.display = 'block');
                editElements.forEach(el => el.style.display = 'none');
                formActions.style.display = 'none';
                editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit Information';
            } else {
                viewElements.forEach(el => el.style.display = 'none');
                editElements.forEach(el => el.style.display = 'block');
                formActions.style.display = 'flex';
                editBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
            }
        }

        const backBtn = document.getElementById('backBtn');
        const logoutModal = document.getElementById('logoutModal');

        if (backBtn) {
            backBtn.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            });
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

        window.addEventListener('click', function(event) {
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
                globalOverlay.style.display = 'none';
            }
        });
    </script>
</body>
</html>