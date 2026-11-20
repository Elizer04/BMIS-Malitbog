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

// Fetch all messages linked strictly to this resident's ID or email
$admin_messages = []; 
$unread_count = 0;

if ($resident_id || $resident_email) {
    $msg_query = "
        SELECT 'residency' AS src, claim_message AS remarks, status, created_at FROM cert_of_residency WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'indigency' AS src, claim_message AS remarks, status, created_at FROM cert_of_indigency WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'clearance' AS src, claim_message AS remarks, status, created_at FROM barangay_clearance WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'resident' AS src, remarks, 'General' AS status, created_at FROM residents WHERE (resident_id = ? OR email = ?) AND remarks IS NOT NULL AND remarks != ''
        UNION ALL
        SELECT 'appointment' AS src, remarks, status, created_at FROM appointments WHERE resident_id = ? AND remarks IS NOT NULL AND remarks != ''
        ORDER BY created_at DESC
    ";
    
    if ($stmt_msg = $conn->prepare($msg_query)) {
        $stmt_msg->bind_param("sssssssss", $resident_id, $resident_email, $resident_id, $resident_email, $resident_id, $resident_email, $resident_id, $resident_email, $resident_id);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Barangay Malitbog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="resident-dashboard.css">
    <style>
        :root {
            --primary-blue: #000066;
            --accent-gold: #ffcc00;
            --text-white: #ffffff;
            --white: #ffffff;
            --bg-light: #f4f7f6;
            --text-gray: #555;
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            font-family: 'Segoe UI', Arial, 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-light);
        }

        /* Topbar styles mirroring resident dashboard */
        .topbar {
            background: #1a1a8c;
            color: white;
            padding: 5px 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
        }

        .topbar .socials {
            display: flex;
            gap: 15px;
        }

        .topbar .socials a {
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            width: 20px;
            height: 20px;
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

        .logo-section { display: flex; align-items: center; gap: 15px; }
        .logo-section img { height: 50px; width: 50px; border-radius: 50%; }
        .logo-text h2 { margin: 0; color: var(--primary-blue); font-size: 1.1rem; letter-spacing: 1px; }
        .logo-text small { font-size: 0.7rem; color: #555; display: block; }

        .rd-back-link {
            font-size: 1.2rem;
            color: var(--primary-blue);
            margin-right: 5px;
            text-decoration: none;
            transition: color 0.2s;
        }
        .rd-back-link:hover {
            color: var(--accent-gold);
        }

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
        .rd-message-symbol-trigger i, 
        .rd-menu-symbol-trigger i,
        .rd-nav-menu-bar-trigger i {
            font-size: 1.1rem;
        }

        .rd-nav-item:hover,
        .rd-message-symbol-trigger:hover,
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

        .rd-message-symbol-trigger,
        .rd-nav-menu-bar-trigger {
            cursor: pointer;
            position: relative;
        }

        .rd-msg-badge { background-color: #ff3333; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.6rem; font-weight: bold; }
        .rd-msg-badge-top { position: absolute; top: -4px; right: -8px; }

        @keyframes pulseGlow {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 51, 51, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(255, 51, 51, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 51, 51, 0); }
        }
        .has-unread-pulse {
            animation: pulseGlow 1.8s infinite;
            border-radius: 50%;
        }

        /* Slide-in Drawers & Modals */
        .menu-slide-drawer, .message-modal {
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

        .menu-drawer-header, .message-header {
            background-color: var(--primary-blue);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-drawer-header h3, .message-header h3 { margin: 0; font-size: 1.1rem; }

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

        .message-body {
            padding: 15px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .admin-msg-card {
            background: #f9f9f9;
            border-left: 4px solid var(--accent-gold);
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.2s ease;
            position: relative;
        }

        .admin-msg-card:hover { transform: translateY(-2px); background: #f0f0f0; }
        .admin-msg-card.unread { background: #eef2ff; border-left-color: var(--primary-blue); }
        .admin-msg-card.read { background: #f9f9f9; border-left-color: #ccc; opacity: 0.85; }

        .msg-meta {
            font-size: 0.75rem;
            color: #777;
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            padding-right: 25px;
        }

        .msg-sender { font-weight: bold; color: var(--primary-blue); }

        .msg-content {
            font-size: 0.9rem;
            color: #333;
            margin: 0;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .msg-options-container {
            position: absolute;
            top: 10px;
            right: 12px;
        }

        .msg-options-btn {
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            font-size: 1rem;
            padding: 2px 6px;
            border-radius: 4px;
            display: none;
        }

        .admin-msg-card.read .msg-options-btn { display: block; }
        .msg-options-btn:hover { background: rgba(0,0,0,0.08); color: #000; }

        .msg-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 25px;
            background: white;
            border: 1px solid #ddd;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 6px;
            z-index: 20;
            min-width: 100px;
        }
        .msg-dropdown-menu.show { display: block; }
        .msg-dropdown-menu button {
            background: none;
            border: none;
            width: 100%;
            padding: 8px 12px;
            text-align: left;
            font-size: 0.85rem;
            color: #ff3333;
            cursor: pointer;
            border-radius: 6px;
        }
        .msg-dropdown-menu button:hover { background: #fff0f0; }

        .read-modal {
            display: none;
            position: fixed;
            z-index: 1400;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .read-modal-header {
            background: var(--primary-blue);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .read-modal-body {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        .read-modal-body p { margin: 0; line-height: 1.6; color: #333; white-space: pre-line; }

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
            padding: 40px 5%; 
            color: white;
            text-align: center;
        }

        .welcome-msg h1 { margin: 0; font-size: 1.8rem; }
        .welcome-msg span { color: var(--accent-gold); }
        .welcome-msg p { font-size: 0.95rem; margin-top: 8px; }

        .container {
            max-width: 600px;
            margin: -20px auto 40px;
            padding: 0 15px;
            position: relative;
            z-index: 10;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .section-title {
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 25px;
            color: #333;
            border-left: 5px solid var(--accent-gold);
            padding-left: 15px;
        }

        .info-group { display: flex; flex-direction: column; margin-bottom: 20px; }

        .info-label {
            font-size: 0.8rem;
            color: #777;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            box-sizing: border-box;
            background-color: #f9fbfd;
        }

        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display: none; }
        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-contacts-auto-fill-button {
            visibility: hidden;
            display: none !important;
            pointer-events: none;
            height: 0;
            width: 0;
            margin: 0;
        }

        .form-input:focus { outline: none; border-color: var(--primary-blue); background-color: #ffffff; }

        .password-wrapper { position: relative; }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .btn-track {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-track:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-track:disabled { background: #555588; cursor: not-allowed; transform: none; box-shadow: none; }

        .rd-back-dashboard {
            text-align: center;
            margin-top: 15px;
        }
        .rd-back-dashboard a {
            font-size: 0.85rem;
            color: #666;
            text-decoration: none;
            font-weight: 600;
        }
        .rd-back-dashboard a:hover {
            color: var(--primary-blue);
            text-decoration: underline;
        }

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
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            box-sizing: border-box;
        }

        .close-btn {
            position: absolute;
            right: 15px;
            top: 12px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close-btn:hover { color: #333; }

        .modal-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--primary-blue);
            font-size: 1.15rem;
        }

        .modal-body-content {
            padding: 5px 0;
            font-size: 0.9rem;
            color: #333;
            line-height: 1.4;
        }

        .modal-footer {
            margin-top: 15px;
            text-align: right;
            padding-top: 12px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn-modal-action {
            background: var(--primary-blue);
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
            transition: 0.3s;
        }
        .btn-modal-action:hover { background: #1a1a8c; }

        .btn-modal-cancel {
            background: #ccc;
            color: #333;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
            transition: 0.3s;
        }
        .btn-modal-cancel:hover { background: #b3b3b3; }

        @media (max-width: 768px) {
            .topbar { display: none; }
            .main-nav { padding: 8px 12px; }
            .logo-section img { height: 40px; width: 40px; }
            .logo-text h2 { font-size: 0.95rem; }
            .logo-text small { font-size: 0.6rem; }
            
            .nav-links { gap: 15px; }
            .rd-nav-item span,
            .rd-menu-symbol-trigger span,
            .rd-nav-menu-bar-trigger span,
            .rd-message-symbol-trigger span {
                display: none;
            }

            .menu-slide-drawer, 
            .message-modal {
                max-width: 50% !important;
                width: 50% !important;
            }
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
                <a href="barangay_profile.php" style="text-decoration: none; color: inherit;"></a>
            </div>
        </div>
    </div>

    <!-- Navigation bar aligned with resident dashboard and profile structure -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="resident_dashboard.php" class="rd-back-link" title="Back to Dashboard">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Logo">
            <div class="logo-text">
                <h2>BARANGAY MALITBOG</h2>
                <small>PROVINCE OF ORIENTAL MINDORO</small>
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

    <!-- Global Overlay & Slide-in Drawers -->
    <div class="rd-global-overlay" id="globalOverlay" onclick="closeAllModals()"></div>

    <!-- Slide-in Menu Drawer -->
    <div class="menu-slide-drawer" id="menuSlideDrawer">
        <div class="menu-drawer-header">
            <h3><i class="fas fa-user-circle"></i> <span id="fullName"><?php echo htmlspecialchars($full_name); ?></span></h3>
            <button type="button" class="close-btn" onclick="toggleNavBarMenu()">&times;</button>
        </div>
        <div class="menu-drawer-body">
            <a href="barangay_profile.php"><i class="fas fa-university"></i> Barangay Info</a>
            <a href="change_password.php"><i class="fas fa-key"></i> Change Password</a>
            <a href="javascript:void(0);" id="logoutBtnDrawer"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Message Inbox Drawer -->
    <div class="message-modal" id="messageDrawer">
        <div class="message-header">
            <h3><i class="fas fa-comments"></i> Admin Messages</h3>
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
                    <div class="admin-msg-card <?php echo $is_read ? 'read' : 'unread'; ?>" onclick='readMessage(this, <?php echo json_encode($msg_text); ?>, <?php echo json_encode($msg_time); ?>, "<?php echo $msg_hash; ?>")'>
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

    <!-- Read Message Details Modal -->
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

    <!-- System Notice Modal for Logout -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';">&times;</span>
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content">
                <p style="margin: 0;">Are you sure you want to leave or log out?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';">Cancel</button>
                <a href="index.php" style="text-decoration: none;">
                    <button type="button" class="btn-modal-action">Yes</button>
                </a>
            </div>
        </div>
    </div>

    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>SECURITY <span>SETTINGS</span></h1>
            <p>UPDATE YOUR PASSWORD - BARANGAY MALITBOG</p>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h3 class="section-title">Change Password</h3>
            
            <form id="updatePasswordForm" action="update_password.php" method="post">
                <div class="info-group">
                    <label class="info-label" for="old_password">Current Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-input" id="old_password" name="old_password" placeholder="Enter existing password" required style="padding-right: 40px;">
                        <i class="fa-solid fa-eye-slash toggle-password" data-target="old_password"></i>
                    </div>
                </div>
                
                <div class="info-group">
                    <label class="info-label" for="new_password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-input" id="new_password" name="new_password" placeholder="Enter new password" required style="padding-right: 40px;">
                        <i class="fa-solid fa-eye-slash toggle-password" data-target="new_password"></i>
                    </div>
                </div>

                <div class="info-group">
                    <label class="info-label" for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-input" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required style="padding-right: 40px;">
                        <i class="fa-solid fa-eye-slash toggle-password" data-target="confirm_password"></i>
                    </div>
                </div>

                <button type="submit" name="update" class="btn-track" id="submitBtn">
                    <i class="fas fa-lock-open mr-2"></i> Update Password
                </button>
            </form>
            
            <div class="rd-back-dashboard">
                <a href="resident_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Password Update Result Modal -->
    <div id="resultModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('resultModal')">&times;</span>
            <div class="modal-header">
                <h3 id="modalTitle">Password Update Notice</h3>
            </div>
            
            <div class="modal-body-content">
                <p id="modalMessageBody" style="margin: 0;">Processing your request...</p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modal-action" onclick="closeModal('resultModal')">OK</button>
            </div>
        </div>
    </div>

    <script>
        const globalOverlay = document.getElementById('globalOverlay');

        function toggleNavBarMenu() {
            const menuDrawer = document.getElementById('menuSlideDrawer');
            document.getElementById('messageDrawer').style.display = 'none';
            
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

        function toggleMessages() {
            const drawer = document.getElementById('messageDrawer');
            document.getElementById('menuSlideDrawer').style.display = 'none';
            
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

        window.addEventListener('click', function(event) {
            document.querySelectorAll('.msg-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
                globalOverlay.style.display = 'none';
            }
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
            document.getElementById('logoutModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        // Password Visibility Toggle Logic
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                
                if (passwordInput) {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                }
            });
        });

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Handle Password Form Submission via AJAX & Result Modal Display[cite: 1]
        const updatePasswordForm = document.getElementById('updatePasswordForm');
        const submitBtn = document.getElementById('submitBtn');
        const resultModal = document.getElementById('resultModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessageBody = document.getElementById('modalMessageBody');

        updatePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent standard page reload[cite: 1]

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                modalTitle.textContent = "Notice: Mismatch";
                modalMessageBody.textContent = "New password and confirm password do not match. Please check and try again.";
                resultModal.style.display = 'flex';
                return;
            }

            const formData = new FormData(updatePasswordForm);
            formData.append('update', 'true');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            const startTime = Date.now();

            fetch('update_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                const elapsedTime = Date.now() - startTime;
                const minDisplayTime = 500; 
                const delay = elapsedTime < minDisplayTime ? minDisplayTime - elapsedTime : 0;

                setTimeout(() => {
                    const cleanResponse = data ? data.trim() : '';

                    if (cleanResponse.toLowerCase().includes('success') || cleanResponse === '1') {
                        modalTitle.textContent = "Success: Password Updated";
                        modalMessageBody.textContent = "Your password has been successfully updated.";
                        updatePasswordForm.reset(); 
                    } else {
                        modalTitle.textContent = "Notice: Update Issue";
                        modalMessageBody.textContent = (cleanResponse && cleanResponse.length < 120 && !cleanResponse.startsWith('<')) 
                            ? cleanResponse 
                            : "Unable to update password. Please verify that your current password is correct and try again.";
                    }

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-lock-open mr-2"></i> Update Password';
                    resultModal.style.display = 'flex';
                }, delay);
            })
            .catch(error => {
                const elapsedTime = Date.now() - startTime;
                const minDisplayTime = 500;
                const delay = elapsedTime < minDisplayTime ? minDisplayTime - elapsedTime : 0;

                setTimeout(() => {
                    modalTitle.textContent = "System Error";
                    modalMessageBody.textContent = 'A network error occurred. Please check your connection and try again.';
                    console.error('Error:', error);

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-lock-open mr-2"></i> Update Password';
                    resultModal.style.display = 'flex';
                }, delay);
            });
        });
    </script>
</body>
</html>
<?php 
if (isset($conn)) {
    $conn->close(); 
}
?>