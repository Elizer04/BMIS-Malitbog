<?php
include 'db_connect.php'; 
session_start();

if (!isset($_SESSION['resident_id'])) {
    header("Location: resident_portal.php");
    exit();
}

if (!isset($_SESSION['deleted_messages'])) { $_SESSION['deleted_messages'] = []; }
if (!isset($_SESSION['read_messages'])) { $_SESSION['read_messages'] = []; }

// Handle AJAX request to delete a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_msg_hash'])) {
    $msg_hash = $_POST['delete_msg_hash'];
    if (!in_array($msg_hash, $_SESSION['deleted_messages'])) {
        $_SESSION['deleted_messages'][] = $msg_hash;
    }
    echo json_encode(['status' => 'success']);
    exit();
}

// Handle AJAX request to mark a message as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_hash'])) {
    $msg_hash = $_POST['mark_read_hash'];
    if (!in_array($msg_hash, $_SESSION['read_messages'])) {
        $_SESSION['read_messages'][] = $msg_hash;
    }
    echo json_encode(['status' => 'success']);
    exit();
}

$resident_id = $_SESSION['resident_id'];
$resident_email = isset($_SESSION['email']) ? $_SESSION['email'] : null;
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Resident';

// 3. Fetch all messages linked strictly to this resident's ID or email securely across all tables (14 cert tables + residents + my_cases)
$admin_messages = []; 
$unread_count = 0;

if ($resident_id || $resident_email) {
    $msg_query = "
        SELECT 'residency' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM cert_of_residency WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'indigency' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM cert_of_indigency WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'clearance' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM barangay_clearance WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'business_cert' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM business_certification WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'non_tenancy' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM certification_of_non_tenancy WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'unemployment' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM certificate_of_unemployment WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'business_closure' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM certificate_of_business_closure WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'good_moral' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM certificate_of_good_moral WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'others' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM other_certifications WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'same_person' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM one_same_person WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'income' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM livelihood_income WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'property_agricultural' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM property_agricultural_ownership WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'widening_road' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM road_widening_boundaries WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'family_history' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM family_residency_history WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'resident' COLLATE utf8mb4_unicode_ci AS src, remarks COLLATE utf8mb4_unicode_ci AS remarks, 'General' COLLATE utf8mb4_unicode_ci AS status, created_at FROM residents WHERE (resident_id = ? OR email = ?) AND remarks IS NOT NULL AND remarks != ''
        UNION ALL
        SELECT 'judicial' COLLATE utf8mb4_unicode_ci AS src, remarks COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM my_cases WHERE resident_id = ? AND remarks IS NOT NULL AND remarks != ''
        ORDER BY created_at DESC
    ";
    
    if ($stmt_msg = $conn->prepare($msg_query)) {
        // 14 tables checking (resident_id OR email) = 28 parameters, plus residents (2) = 30, plus my_cases (1) = 31 parameters total.
        $stmt_msg->bind_param("sssssssssssssssssssssssssssssss", 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id
        );
        $stmt_msg->execute();
        $res_msg = $stmt_msg->get_result();
        while ($row_msg = $res_msg->fetch_assoc()) {
            $msg_text = isset($row_msg['remarks']) ? $row_msg['remarks'] : '';
            $msg_time = isset($row_msg['created_at']) ? $row_msg['created_at'] : 'Recently';
            $msg_source = isset($row_msg['src']) ? $row_msg['src'] : 'general';
            $msg_hash = md5($msg_source . $msg_text . $msg_time);
            
            if (!in_array($msg_hash, $_SESSION['deleted_messages'])) {
                $row_msg['msg_hash'] = $msg_hash;
                $row_msg['is_read'] = in_array($msg_hash, $_SESSION['read_messages']);
                
                if (!$row_msg['is_read']) {
                    $unread_count++;
                }
                
                $admin_messages[] = $row_msg;
            }
        }
        $stmt_msg->close();
    }
}

// 4. Fetch Certification Metrics & Request History (Fully synchronized with all certification tables)
$total_certifications = 0;
$approved_cert = 0;
$pending_cert = 0;
$released_cert = 0;
$cert_history = [];

if ($resident_id || $resident_email) {
    $union_cert_query = "
        SELECT 'residency' AS src, 'Certificate of Residency' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM cert_of_residency WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'indigency' AS src, 'Certificate of Indigency' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM cert_of_indigency WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'clearance' AS src, 'Barangay Clearance' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM barangay_clearance WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'business_cert' AS src, 'Business Certification' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM business_certification WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'non_tenancy' AS src, 'Certification of Non-Tenancy' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM certification_of_non_tenancy WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'unemployment' AS src, 'Certificate of Unemployment' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM certificate_of_unemployment WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'business_closure' AS src, 'Certificate of Business Closure' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM certificate_of_business_closure WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'good_moral' AS src, 'Certificate of Good Moral Character' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM certificate_of_good_moral WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'others' AS src, 'Other Certifications' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM other_certifications WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'same_person' AS src, 'One-and-the-Same Person' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM one_same_person WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'income' AS src, 'Livelihood & Income' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM livelihood_income WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'property_agricultural' AS src, 'Property & Agricultural Ownership' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM property_agricultural_ownership WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'widening_road' AS src, 'Road Widening / Boundaries' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM road_widening_boundaries WHERE resident_id = ? OR email = ?
        UNION ALL
        SELECT 'family_history' AS src, 'Family & Residency History' AS cert_type, status, purpose_cat, claim_message AS remarks, created_at FROM family_residency_history WHERE resident_id = ? OR email = ?
        ORDER BY created_at DESC
    ";

    if ($stmt_all = $conn->prepare($union_cert_query)) {
        // 14 tables * 2 parameters each (resident_id OR email) = 28 parameters total
        $stmt_all->bind_param("ssssssssssssssssssssssssssss", 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email, 
            $resident_id, $resident_email
        );
        $stmt_all->execute();
        $result_all = $stmt_all->get_result();
        
        while ($row = $result_all->fetch_assoc()) {
            $total_certifications++;
            $status = strtolower(trim($row['status']));
            
            if ($status === 'approved') {
                $approved_cert++;
            } elseif ($status === 'pending') {
                $pending_cert++;
            } elseif ($status === 'released' || $status === 'completed' || $status === 'claimed') {
                $released_cert++;
            }
            
            if (!empty($row['remarks'])) {
                $row['msg_hash'] = md5($row['src'] . $row['remarks'] . $row['created_at']);
            } else {
                $row['msg_hash'] = '';
            }
            
            $cert_history[] = $row;
        }
        $stmt_all->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certification Overview - Barangay Malitbog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style1.css">
   
    <style>
        .rd-dashboard-page .rd-hero-banner {
            scroll-margin-top: 80px;
            background: linear-gradient(rgba(26, 26, 140, 0.85), rgba(26, 26, 140, 0.85)), 
                        url('image/backgroundphoto1.jpg') no-repeat center center/cover;
            padding: 60px 10%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 30px;
            width: 100%;
            box-sizing: border-box;
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

        .logo-section { display: flex; align-items: center; gap: 15px; }
        .logo-section img { height: 50px; width: 50px; border-radius: 50%; }
        .logo-text h2 { margin: 0; color: #000066; font-size: 1.2rem; letter-spacing: 1px; }
        .logo-text small { font-size: 0.8rem; color: #555; display: block; }

        .rd-back-link {
            font-size: 1.2rem;
            color: #000066;
            margin-right: 5px;
            text-decoration: none;
            transition: color 0.2s;
        }
        .rd-back-link:hover { color: #ffcc00; }

        .nav-links { display: flex; align-items: center; gap: 25px; }

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
        .rd-message-symbol-trigger i, 
        .rd-menu-symbol-trigger i,
        .rd-nav-menu-bar-trigger i {
            font-size: 1.1rem;
        }

        .rd-nav-item:hover,
        .rd-message-symbol-trigger:hover,
        .rd-menu-symbol-trigger:hover,
        .rd-nav-menu-bar-trigger:hover,
        .rd-menu-symbol-trigger.active,
        .rd-nav-item.active {
            color: #ffcc00;
        }

        .rd-nav-link-dropdown { display: inline-flex; align-items: center; position: relative; }
        .rd-message-symbol-trigger, .rd-nav-menu-bar-trigger { cursor: pointer; position: relative; }

        .rd-msg-badge { background-color: #ff3333; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.6rem; font-weight: bold; }
        .rd-msg-badge-top { position: absolute; top: -4px; right: -8px; }

        .metric-value-display {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1a1a8c;
            margin: 5px 0;
        }

        /* Enhanced Certification Request Dropdown Styles */
        .cert-request-dropdown-wrapper {
            position: relative;
            display: inline-block;
        }

        .cert-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #ffffff;
            min-width: 320px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.15);
            z-index: 1200;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e1e8ed;
            margin-top: 8px;
            animation: dropdownFade 0.25s ease;
        }

        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cert-dropdown-content.show {
            display: block;
        }

        .cert-dropdown-header {
            background-color: #f4f6fc;
            padding: 10px 16px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000066;
            border-bottom: 1px solid #eef2f7;
        }

        .cert-dropdown-content a {
            color: #333;
            padding: 11px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f8f9fc;
        }

        .cert-dropdown-content a:last-child {
            border-bottom: none;
        }

        .cert-dropdown-content a:hover {
            background-color: #eef2ff;
            color: #1a1a8c;
            padding-left: 20px;
        }

        .cert-dropdown-content a i {
            color: #1a1a8c;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        /* History Table Styling */
        .cert-table-container {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 30px;
            overflow-x: auto;
        }

        .cert-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            text-align: left;
        }

        .cert-history-table th, .cert-history-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eaeaea;
            font-size: 0.9rem;
        }

        .cert-history-table th {
            background-color: #f8f9fa;
            color: #000066;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-approved { background-color: #d4edda; color: #155724; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-released { background-color: #cce5ff; color: #004085; }
        .badge-default { background-color: #e2e3e5; color: #383d41; }

        @media (max-width: 768px) {
            .main-nav { padding: 8px 12px; }
            .logo-section img { height: 40px; width: 40px; }
            .logo-text h2 { font-size: 0.95rem; }
            .logo-text small { font-size: 0.6rem; }
            .nav-links { gap: 15px; }
            .rd-nav-item span, .rd-menu-symbol-trigger span, .rd-nav-menu-bar-trigger span, .rd-message-symbol-trigger span { display: none; }
            .cert-dropdown-content { right: -50px; min-width: 280px; }
        }
    </style>
</head>
<body class="rd-dashboard-page">

    <!-- Top Bar -->
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
                <a href="barangay_profile.php" style="text-decoration: none; color: inherit;"></a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar with Resident Dashboard Layout -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="resident_dashboard.php" class="rd-back-link" title="Back to Dashboard">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Logo">
            <div class="logo-text">
                <h2>BARANGAY MALITBOG</h2>
                <small>MUNICIPALITY OF BONGABONG</small>
            </div>
        </div>
        
        <div class="nav-links">
            <a href="resident_dashboard.php" class="rd-nav-item custom-home-style" title="Home">
                <i class="fas fa-home"></i>
                <span>home</span>
            </a>

            <div class="rd-nav-link-dropdown">
                <a href="javascript:void(0);" class="rd-message-symbol-trigger <?php echo ($unread_count > 0) ? 'has-unread-pulse' : ''; ?>" onclick="toggleMessages()" title="Inbox / Messages">
                    <i class="fas fa-envelope"></i>
                    <span>Inbox</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="rd-msg-badge rd-msg-badge-top" id="msgBadgeTop"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="rd-nav-link-dropdown">
                <a href="my_profile.php" class="rd-menu-symbol-trigger custom-profile-style" title="My Profile">
                    <i class="fas fa-user-circle"></i>
                    <span>profile</span>
                </a>
            </div>

            <div class="rd-nav-link-dropdown">
                <a href="javascript:void(0);" class="rd-nav-menu-bar-trigger" onclick="toggleNavBarMenu()" title="Menu Options">
                    <i class="fas fa-bars"></i>
                    <span>Menu</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Global Overlay & Drawers -->
    <div class="rd-global-overlay" id="globalOverlay" onclick="closeAllModals()"></div>

    <div class="menu-slide-drawer" id="menuSlideDrawer">
        <div class="menu-drawer-header">
           <h3><i class="fas fa-user-circle"></i> <span id="fullName"><?php echo htmlspecialchars($full_name ?? 'full_name'); ?></span></h3>
            <button type="button" class="close-btn" onclick="toggleNavBarMenu()">&times;</button>
        </div>
        <div class="menu-drawer-body">
            <a href="certification.php"><i class="fas fa-file-contract"></i> Apply Certification</a>
            <a href="general_certification.php"><i class="fas fa-file-alt"></i> Apply General Certification</a>
            <a href="resident_dashboard.php"><i class="fas fa-home"></i> Dashboard Home</a>
            <a href="my_profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 5px 0;">
            <a href="javascript:void(0);" id="logoutBtnDrawer"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Admin Messages Drawer -->
    <div class="message-modal" id="messageDrawer">
        <div class="message-header">
            <h3><i class="fas fa-comments"></i> Messages</h3>
            <button type="button" class="close-btn" onclick="toggleMessages()">&times;</button>
        </div>
        <div class="message-body">
            <?php if (!empty($admin_messages)): ?>
                <?php foreach ($admin_messages as $msg): ?>
                    <?php 
                        $msg_text = isset($msg['remarks']) ? $msg['remarks'] : '';
                        $msg_time = isset($msg['created_at']) ? $msg['created_at'] : 'Recently';
                        $msg_source = isset($msg['src']) ? $msg['src'] : 'general';
                        $msg_hash = isset($msg['msg_hash']) ? $msg['msg_hash'] : md5($msg_source . $msg_text . $msg_time);
                        $is_read = $msg['is_read'];
                    ?>
                    <div class="admin-msg-card <?php echo $is_read ? 'read' : 'unread'; ?>" id="msg-card-<?php echo $msg_hash; ?>" onclick='readMessage(this, <?php echo json_encode($msg_text); ?>, <?php echo json_encode($msg_time); ?>, "<?php echo $msg_hash; ?>")'>
                        <div class="msg-meta">
                            <span class="msg-sender"><i class="fas fa-user-shield"></i> Barangay Admin</span>
                            <span><?php echo htmlspecialchars($msg_time); ?></span>
                        </div>
                        <p class="msg-content"><?php echo htmlspecialchars($msg_text); ?></p>
                        
                        <div class="msg-options-container" onclick="event.stopPropagation();">
                            <button type="button" class="msg-options-btn" onclick="toggleMsgOptions(event, this)" title="Options">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="msg-dropdown-menu">
                                <button type="button" onclick="deleteMessage(event, '<?php echo $msg_hash; ?>', this.closest('.admin-msg-card'))">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #888; margin-top: 20px;">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; color: #ccc;"></i>
                    <p>No messages from Admin.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="read-modal" id="readMessageModal">
        <div class="read-modal-header">
            <h3 style="margin: 0; font-size: 1rem;"><i class="fas fa-envelope-open-text"></i> Admin Message Details</h3>
            <button type="button" class="close-btn" onclick="closeReadModal()">&times;</button>
        </div>
        <div class="read-modal-body">
            <div class="msg-meta" style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                <span class="msg-sender"><i class="fas fa-user-shield"></i> Barangay Admin</span>
                <span id="readMsgTime">Recently</span>
            </div>
            <p id="readMsgContent"></p>
        </div>
    </div>

    <!-- Hero Banner matched to Resident Dashboard layout -->
    <header class="rd-hero-banner">
        <div class="rd-welcome-msg">
            <h1>WELCOME, <span><?php echo htmlspecialchars($full_name); ?></span></h1>
            <p>PROVINCE OF ORIENTAL MINDORO - BARANGAY MALITBOG</p>
        </div>
        <div class="rd-urgent-notices">
            <strong>CERTIFICATION OVERVIEW</strong>
            <ul>
                <li>Track your standard requests, general certifications, metrics, and history.</li>
                <li>Review clearance statuses and stay updated with admin messages.</li>
            </ul>
        </div>
    </header>

    <!-- Metrics Cards Grid Section -->
    <div class="rd-container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; margin-top: 25px;">
            <h2 class="rd-section-title" style="margin-bottom: 0;">Certification Metrics Summary</h2>
            
            <!-- Enhanced Request Certification Dropdown Button -->
            <div class="cert-request-dropdown-wrapper">
                <button type="button" class="rd-btn-track rd-btn-blue" id="certDropdownBtn" onclick="toggleCertDropdown(event)" style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                    <i class="fas fa-plus-circle"></i> Request Certification <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
                </button>
                <div class="cert-dropdown-content" id="certDropdownMenu">
                    <div class="cert-dropdown-header">Select Certification Type</div>
                    <a href="certification.php"><i class="fas fa-file-alt"></i> Standard Certifications & Clearances</a>
                    <a href="general_certification.php"><i class="fas fa-file-contract"></i> General Certification / Katunayan</a>
                </div>
            </div>
        </div>

        <div class="rd-services-grid">
            <!-- Total Certifications -->
            <div class="rd-service-card">
                <i class="fas fa-file-alt"></i>
                <div class="rd-card-title">TOTAL REQUESTS</div>
                <div class="metric-value-display"><?php echo $total_certifications; ?></div>
                <div class="rd-status-bar"><div class="rd-status-fill rd-fill-ready" style="width: 100%;"></div></div>
            </div>

            <!-- Approved Certifications -->
            <div class="rd-service-card">
                <i class="fas fa-check-circle"></i>
                <div class="rd-card-title">APPROVED</div>
                <div class="metric-value-display"><?php echo $approved_cert; ?></div>
                <div class="rd-status-bar"><div class="rd-status-fill rd-fill-health" style="width: 100%;"></div></div>
            </div>

            <!-- Pending Certifications -->
            <div class="rd-service-card">
                <i class="fas fa-clock"></i>
                <div class="rd-card-title">PENDING</div>
                <div class="metric-value-display"><?php echo $pending_cert; ?></div>
                <div class="rd-status-bar"><div class="rd-status-fill rd-fill-judicial" style="width: 100%;"></div></div>
            </div>

            <!-- Released / History Certifications -->
            <div class="rd-service-card">
                <i class="fas fa-paper-plane"></i>
                <div class="rd-card-title">RELEASED / HISTORY</div>
                <div class="metric-value-display"><?php echo $released_cert; ?></div>
                <div class="rd-status-bar"><div class="rd-status-fill rd-fill-ready" style="width: 100%;"></div></div>
            </div>
        </div>

        <!-- Detailed History Table -->
        <div class="cert-table-container">
            <h3 style="color: #000066; margin-top: 0; font-size: 1.1rem;"><i class="fas fa-history"></i> Certification History & Logs</h3>
            <?php if (!empty($cert_history)): ?>
                <table class="cert-history-table">
                    <thead>
                        <tr>
                            <th>Certification Type</th>
                            <th>Purpose</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cert_history as $row): ?>
                            <?php 
                                $status_val = strtolower(trim($row['status'])); 
                                $badge_class = 'badge-default';
                                if ($status_val === 'approved') { 
                                    $badge_class = 'badge-approved';
                                } elseif ($status_val === 'pending') { 
                                    $badge_class = 'badge-pending';
                                } elseif (in_array($status_val, ['released', 'completed', 'claimed'])) { 
                                    $badge_class = 'badge-released';
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['cert_type']); ?></strong></td>
                                <td><?php echo htmlspecialchars(!empty($row['purpose_cat']) ? $row['purpose_cat'] : 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['created_at']))); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars(!empty($row['status']) ? ucfirst($row['status']) : 'Pending'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($row['msg_hash'])): ?>
                                        <button type="button" class="rd-btn-track rd-btn-blue" style="padding: 5px 10px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;" onclick="openSpecificMessage('<?php echo $row['msg_hash']; ?>')" title="View Specific Message">
                                            <i class="fas fa-envelope-open-text"></i> View Message
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #aaa; font-size: 0.8rem; font-style: italic;">No message yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; color: #888; padding: 30px 0;">
                    <i class="fas fa-folder-open" style="font-size: 2.5rem; margin-bottom: 10px; color: #ccc;"></i>
                    <p>No certification requests found in your history yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeModalBtn" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';" style="position: absolute; right: 15px; top: 12px;">&times;</span>
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content">
                <p style="margin: 0;">Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" id="cancelLogoutBtn" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';">Cancel</button>
                <a href="index.php" style="text-decoration: none;">
                    <button type="button" class="btn-modal-action">Yes</button>
                </a>
            </div>
        </div>
    </div>

    <script>
        const globalOverlay = document.getElementById('globalOverlay');

        // Toggle Certificate Request Dropdown Menu
        function toggleCertDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('certDropdownMenu');
            document.querySelectorAll('.msg-dropdown-menu').forEach(menu => menu.classList.remove('show'));
            document.getElementById('menuSlideDrawer').style.display = 'none';
            
            dropdown.classList.toggle('show');
            if(dropdown.classList.contains('show')) {
                globalOverlay.style.display = 'block';
            }
        }

        function toggleNavBarMenu() {
            const menuDrawer = document.getElementById('menuSlideDrawer');
            document.getElementById('messageDrawer').style.display = 'none';
            document.getElementById('certDropdownMenu').classList.remove('show');
            
            if (menuDrawer.style.display === 'flex') {
                menuDrawer.style.display = 'none';
                globalOverlay.style.display = 'none';
            } else {
                globalOverlay.style.display = 'block';
                menuDrawer.style.display = 'flex';
            }
        }

        const logoutBtnDrawer = document.getElementById('logoutBtnDrawer');
        const logoutModal = document.getElementById('logoutModal');

        if (logoutBtnDrawer) {
            logoutBtnDrawer.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('menuSlideDrawer').style.display = 'none';
                logoutModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            });
        }

        window.onclick = function(event) {
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
                globalOverlay.style.display = 'none';
            }
        }

        function toggleMessages() {
            const drawer = document.getElementById('messageDrawer');
            document.getElementById('menuSlideDrawer').style.display = 'none';
            document.getElementById('certDropdownMenu').classList.remove('show');
            
            if (drawer.style.display === 'flex') {
                drawer.style.display = 'none';
                const readModalOpen = document.getElementById('readMessageModal').style.display === 'block';
                if (!readModalOpen) {
                    globalOverlay.style.display = 'none';
                }
            } else {
                globalOverlay.style.display = 'block';
                drawer.style.display = 'flex';
            }
        }

        function readMessage(cardElement, content, time, hash) {
            document.getElementById('readMsgContent').innerText = content;
            document.getElementById('readMsgTime').innerText = time;

            document.getElementById('readMessageModal').style.display = 'block';
            globalOverlay.style.display = 'block';

            if (cardElement.classList.contains('unread')) {
                cardElement.classList.remove('unread');
                cardElement.classList.add('read');

                const badgeTop = document.getElementById('msgBadgeTop');
                
                if (badgeTop) {
                    let currentCount = parseInt(badgeTop.innerText, 10);
                    if (currentCount > 1) {
                        badgeTop.innerText = currentCount - 1;
                    } else {
                        badgeTop.remove();
                        const inboxTrigger = document.querySelector('.rd-message-symbol-trigger');
                        if (inboxTrigger) {
                            inboxTrigger.classList.remove('has-unread-pulse');
                        }
                    }
                }

                const formData = new URLSearchParams();
                formData.append('mark_read_hash', hash);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).catch(err => console.error('Error marking message as read:', err));
            }
        }

        function openSpecificMessage(hash) {
            const drawer = document.getElementById('messageDrawer');
            document.getElementById('menuSlideDrawer').style.display = 'none';
            globalOverlay.style.display = 'block';
            drawer.style.display = 'flex';

            const cardElement = document.getElementById('msg-card-' + hash);
            if (cardElement) {
                cardElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                cardElement.style.transition = 'background-color 0.5s ease';
                const originalBg = cardElement.style.backgroundColor;
                cardElement.style.backgroundColor = '#fff9c4';
                setTimeout(() => {
                    cardElement.style.backgroundColor = originalBg;
                }, 1500);

                cardElement.click();
            }
        }

        function toggleMsgOptions(event, btn) {
            event.stopPropagation();
            document.querySelectorAll('.msg-dropdown-menu').forEach(menu => {
                if (menu !== btn.nextElementSibling) {
                    menu.classList.remove('show');
                }
            });
            const menu = btn.nextElementSibling;
            menu.classList.toggle('show');
        }

        window.addEventListener('click', function() {
            document.querySelectorAll('.msg-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            document.getElementById('certDropdownMenu').classList.remove('show');
        });

        function deleteMessage(event, hash, cardElement) {
            event.stopPropagation();

            const formData = new URLSearchParams();
            formData.append('delete_msg_hash', hash);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    cardElement.style.transition = 'all 0.3s ease';
                    cardElement.style.opacity = '0';
                    cardElement.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        cardElement.remove();
                        const messageBody = document.querySelector('.message-body');
                        if (!messageBody.querySelector('.admin-msg-card')) {
                            messageBody.innerHTML = `
                                <div style="text-align: center; color: #888; margin-top: 20px;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; color: #ccc;"></i>
                                    <p>No messages from Admin.</p>
                                </div>
                            `;
                        }
                    }, 300);
                }
            })
            .catch(err => console.error('Error deleting message:', err));
        }

        function closeReadModal() {
            document.getElementById('readMessageModal').style.display = 'none';
            const drawer = document.getElementById('messageDrawer');
            if (drawer.style.display !== 'flex') {
                globalOverlay.style.display = 'none';
            }
        }

        function closeAllModals() {
            document.getElementById('menuSlideDrawer').style.display = 'none';
            document.getElementById('messageDrawer').style.display = 'none';
            document.getElementById('readMessageModal').style.display = 'none';
            document.getElementById('certDropdownMenu').classList.remove('show');
            document.getElementById('logoutModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }
    </script>
</body>
</html>
<?php 
if (isset($conn)) { $conn->close(); }
?>