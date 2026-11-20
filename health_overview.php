<?php
include 'db_connect.php'; 
session_start();

// Determine if accessed by an admin via URL parameter or by a logged-in resident
$is_admin_mode = false;
$resident_id = '';

if (isset($_GET['resident_id']) && !empty($_GET['resident_id'])) {
    // Check if user is logged in as admin
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $resident_id = trim($_GET['resident_id']);
        $is_admin_mode = true;
    } else {
        header("Location: admin_login.php");
        exit();
    }
} else {
    if (!isset($_SESSION['resident_id'])) {
        header("Location: resident_portal.php");
        exit();
    }
    $resident_id = $_SESSION['resident_id'];
}

if (!isset($_SESSION['deleted_messages'])) { $_SESSION['deleted_messages'] = []; }
if (!isset($_SESSION['read_messages'])) { $_SESSION['read_messages'] = []; }

$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Resident';
$update_success = false;
$update_error = '';

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

// Handle Profile Update Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_form'])) {
    $blood_type          = $_POST['blood_type'] ?? '';
    $conditions          = $_POST['conditions'] ?? '';
    $allergy_status      = $_POST['allergy_status'] ?? '';
    $current_medications = $_POST['current_medications'] ?? '';
    $disability_status   = $_POST['disability_status'] ?? '';
    $height              = $_POST['height'] ?? '';
    $weight              = $_POST['weight'] ?? '';
    $blood_pressure      = $_POST['blood_pressure'] ?? '';
    $temperature         = $_POST['temperature'] ?? '';
    $heart_rate          = $_POST['heart_rate'] ?? '';
    $health_status       = $_POST['health_status'] ?? '';
    $immunization_status = $_POST['immunization_status'] ?? '';
    $philhealth          = $_POST['philhealth'] ?? '';
    $preferred_center    = $_POST['preferred_health_center'] ?? '';
    $smoking_status      = $_POST['smoking_status'] ?? '';
    $alcohol_consumption = $_POST['alcohol_consumption'] ?? '';
    $relationship        = $_POST['relationship'] ?? '';
    $emergency_contact   = $_POST['emergency_contact'] ?? '';
    $remarks             = $_POST['remarks'] ?? '';

    try {
        $update_stmt = $conn->prepare("UPDATE residents SET 
            blood_type = ?, 
            conditions = ?, 
            allergy_status = ?, 
            current_medications = ?, 
            disability_status = ?, 
            height = ?, 
            weight = ?, 
            blood_pressure = ?, 
            temperature = ?, 
            heart_rate = ?, 
            health_status = ?, 
            immunization_status = ?, 
            philhealth = ?, 
            preferred_health_center = ?, 
            smoking_status = ?, 
            alcohol_consumption = ?, 
            relationship = ?, 
            emergency_contact = ?, 
            remarks = ? 
            WHERE resident_id = ?");

        if ($update_stmt) {
            $update_stmt->bind_param("ssssssssssssssssssss", 
                $blood_type, $conditions, $allergy_status, $current_medications, $disability_status, 
                $height, $weight, $blood_pressure, $temperature, $heart_rate, $health_status, 
                $immunization_status, $philhealth, $preferred_center, $smoking_status, $alcohol_consumption, 
                $relationship, $emergency_contact, $remarks, $resident_id
            );
            if ($update_stmt->execute()) {
                if ($is_admin_mode) {
                    header("Location: manage_residents.php?update=success");
                    exit();
                } else {
                    $update_success = true;
                }
            } else {
                $update_error = "Failed to update profile information.";
            }
            $update_stmt->close();
        }
    } catch (Exception $e) {
        $update_error = "An error occurred: " . $e->getMessage();
    }
}

// 1. Fetch Health Metrics, Appointment History, & Resident Info
$total_appointments = 0;
$upcoming_appointments = 0;
$completed_checkups = 0;
$health_records = [];
$upcoming_records = [];
$completed_records = [];
$resident_data = [];

try {
    $stmtResident = $conn->prepare("SELECT * FROM residents WHERE resident_id = ?");
    if ($stmtResident) {
        $stmtResident->bind_param("s", $resident_id);
        $stmtResident->execute();
        $resResult = $stmtResident->get_result();
        if ($resResult->num_rows > 0) {
            $resident_data = $resResult->fetch_assoc();
            if (!empty($resident_data['full_name'])) {
                $full_name = $resident_data['full_name'];
            }
        }
        $stmtResident->close();
    }

    $stmtTotal = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE resident_id = ?");
    $stmtTotal->bind_param("s", $resident_id);
    $stmtTotal->execute();
    $total_appointments = $stmtTotal->get_result()->fetch_row()[0];
    $stmtTotal->close();

    $stmtUpcoming = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE resident_id = ? AND appt_date >= CURDATE()");
    $stmtUpcoming->bind_param("s", $resident_id);
    $stmtUpcoming->execute();
    $upcoming_appointments = $stmtUpcoming->get_result()->fetch_row()[0];
    $stmtUpcoming->close();

    $stmtCompleted = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE resident_id = ? AND (status = 'Completed' OR status = 'Done')");
    $stmtCompleted->bind_param("s", $resident_id);
    $stmtCompleted->execute();
    $completed_checkups = $stmtCompleted->get_result()->fetch_row()[0];
    $stmtCompleted->close();

    $stmtHistory = $conn->prepare("SELECT id, appt_date, service, status, notes, remarks, created_at FROM appointments WHERE resident_id = ? ORDER BY appt_date DESC");
    $stmtHistory->bind_param("s", $resident_id);
    $stmtHistory->execute();
    $resHistory = $stmtHistory->get_result();
    while ($row = $resHistory->fetch_assoc()) {
        $health_records[] = $row;
        $st = strtolower(trim($row['status']));
        if (strtotime($row['appt_date']) >= strtotime(date('Y-m-d'))) {
            $upcoming_records[] = $row;
        }
        if ($st === 'completed' || $st === 'done') {
            $completed_records[] = $row;
        }
    }
    $stmtHistory->close();

} catch (Exception $e) {
    // Fallback defaults
}

// 2. Fetch messages
$admin_messages = []; 
$unread_count = 0;

if ($resident_id) {
    $msg_query = "
        SELECT 'appointment' AS src, remarks, status, created_at FROM appointments WHERE resident_id = ? AND remarks IS NOT NULL AND remarks != ''
        UNION ALL
        SELECT 'resident' AS src, remarks, 'General' AS status, created_at FROM residents WHERE resident_id = ? AND remarks IS NOT NULL AND remarks != ''
        ORDER BY created_at DESC
    ";
    
    if ($stmt_msg = $conn->prepare($msg_query)) {
        $stmt_msg->bind_param("ss", $resident_id, $resident_id);
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
                
                if (!$row_msg['is_read']) { $unread_count++; }
                
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
    <title>Health Center Overview - Barangay Malitbog</title>
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

        .metrics-top-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 4px solid #1a1a8c;
        }

        .metric-card-box.clickable-metric-card {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
        }

        .metric-card-box.clickable-metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(26, 26, 140, 0.12);
            border-top-color: #ffcc00;
        }

        .metric-card-box.clickable-metric-card.active-card {
            background: #f8fafc;
            border-top-color: #ffcc00;
            box-shadow: 0 0 0 2px rgba(26, 26, 140, 0.15);
        }

        .metric-card-box i {
            font-size: 1.8rem;
            color: #1a1a8c;
            margin-bottom: 10px;
        }

        .metric-value-display {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a8c;
            margin: 5px 0;
        }

        .health-list-two-divisions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            align-items: start;
        }

        .health-list-division {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
        }

        .health-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: background-color 0.2s, border-color 0.2s;
        }

        .health-list-item:hover {
            background: #f1f5f9;
        }

        .health-list-label-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            font-size: 0.85rem;
            color: #475569;
            font-weight: 600;
        }

        .health-list-label-group i {
            color: #1a1a8c;
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        .health-list-input-wrapper {
            flex: 1;
            max-width: 52%;
        }

        .form-input.profile-field {
            width: 100%;
            padding: 7px 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #f8fafc;
            color: #1e293b;
            font-size: 0.88rem;
            box-sizing: border-box;
            transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s;
        }

        .form-input.profile-field:not(:disabled) {
            background-color: #ffffff;
            border-color: #1a1a8c;
            box-shadow: 0 0 0 3px rgba(26, 26, 140, 0.1);
        }

        select.form-input.profile-field:not(:disabled) {
            cursor: pointer;
        }

        .health-list-section-header {
            font-family: 'Poppins', sans-serif;
            font-size: 0.92rem;
            color: #1a1a8c;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
            margin: 14px 0 4px 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .health-list-section-header:first-child {
            margin-top: 0;
        }

        @media (max-width: 960px) {
            .health-list-two-divisions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .health-list-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 12px;
            }
            .health-list-input-wrapper {
                max-width: 100%;
                width: 100%;
            }
        }

        .report-table-container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.06);
            margin-top: 30px;
            overflow-x: auto;
            border: 1px solid #edf2f7;
        }

        .report-history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            text-align: left;
            table-layout: auto;
        }

        .report-history-table th {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .report-history-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #334155;
            vertical-align: middle;
        }

        .report-history-table th:nth-child(5),
        .report-history-table td:nth-child(5) {
            min-width: 240px;
            max-width: 380px;
            word-break: break-word;
            line-height: 1.45;
        }

        .report-history-table tbody tr {
            transition: background-color 0.15s ease;
        }

        .report-history-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .scrollable-info-container {
            max-height: 520px;
            overflow-y: auto;
            padding-right: 6px;
            margin-bottom: 15px;
        }

        .scrollable-info-container::-webkit-scrollbar { width: 6px; }
        .scrollable-info-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .scrollable-info-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .scrollable-info-container::-webkit-scrollbar-thumb:hover { background: #1a1a8c; }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
            text-align: center;
            white-space: nowrap;
        }

        .badge-resolved { background-color: #dcfce7; color: #166534; }
        .badge-pending { background-color: #fef9c3; color: #854d0e; }
        .badge-active { background-color: #e0f2fe; color: #0369a1; }
        .badge-default { background-color: #f1f5f9; color: #475569; }

        @media (max-width: 768px) {
            .main-nav { padding: 8px 12px; }
            .logo-section img { height: 40px; width: 40px; }
            .logo-text h2 { font-size: 0.95rem; }
            .logo-text small { font-size: 0.6rem; }
            .nav-links { gap: 15px; }
            .rd-nav-item span, .rd-menu-symbol-trigger span, .rd-nav-menu-bar-trigger span, .rd-message-symbol-trigger span { display: none; }
        }
    </style>
</head>
<body class="rd-dashboard-page">
    
    <div class="topbar">
        <div class="socials" aria-label="Social media">
            <a href="https://www.facebook.com/share/1MqLQuYLdG/" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24"><path d="M13.6 21.9v-8.7h2.9l.4-3.4h-3.3V7.6c0-1 .3-1.6 1.7-1.6h1.8V2.9c-.3 0-1.4-.1-2.7-.1-2.7 0-4.5 1.6-4.5 4.6v2.5H7v3.4h2.9v8.7h3.7z"/></svg></a>
            <a href="https://youtube.com/@officialpage-barangaymalitbog?si=eo-uBsODjPVwKpOn" aria-label="YouTube" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.7V8.3l6.3 3.7-6.3 3.7Z"/></svg></a>
            <a href="https://www.instagram.com/barangaymalitbog2k26?mdxt=a29mcDJrNTdkNGR4" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><svg class="instagram-mark" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.7" r=".8" fill="currentColor"/></svg></a>
            <a href="#" aria-label="Twitter"><svg viewBox="0 0 24 24"><path d="M18.9 2.25h3.3l-7.2 8.2 8.5 11.3h-6.6l-5.2-6.8-6 6.8H2.4l7.7-8.8L2 2.25h6.8l4.7 6.2 5.4-6.2Zm-1.2 17.5h1.8L7.1 4H5.2z"/></svg></a>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="<?php echo $is_admin_mode ? 'manage_residents.php' : 'resident_dashboard.php'; ?>" class="rd-back-link" title="Go Back">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Logo">
            <div class="logo-text">
                <h2>BARANGAY MALITBOG</h2>
                <small>MUNICIPALITY OF BONGABONG</small>
            </div>
        </div>
        
        <div class="nav-links">
            <a href="<?php echo $is_admin_mode ? 'manage_residents.php' : 'resident_dashboard.php'; ?>" class="rd-nav-item custom-home-style" title="Home">
                <i class="fas fa-home"></i>
                <span>home</span>
            </a>

            <?php if (!$is_admin_mode): ?>
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
            <?php endif; ?>

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

    <!-- Menu Slide Drawer -->
    <div class="menu-slide-drawer" id="menuSlideDrawer">
         <div class="menu-drawer-header">
           <h3><i class="fas fa-user-circle"></i> <span id="fullName"><?php echo htmlspecialchars($full_name ?? 'Resident'); ?></span></h3>
            <button type="button" class="close-btn" onclick="toggleNavBarMenu()">&times;</button>
        </div>
        <div class="menu-drawer-body">
            <?php if ($is_admin_mode): ?>
                <a href="manage_residents.php"><i class="fas fa-arrow-left"></i> Back to Manage Residents</a>
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Admin Dashboard</a>
            <?php else: ?>
                <a href="resident_dashboard.php"><i class="fas fa-home"></i> Dashboard Home</a>
                <a href="certification.php"><i class="fas fa-file-contract"></i> Apply Certification</a>
                <a href="my_profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            <?php endif; ?>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 5px 0;">
            <a href="javascript:void(0);" id="logoutBtnDrawer"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <?php if (!$is_admin_mode): ?>
    <!-- Message Drawer Modal -->
    <div class="message-modal" id="messageDrawer">
        <div class="message-header">
            <h3><i class="fas fa-comments"></i> Health Messages & Remarks</h3>
            <button type="button" class="close-btn" onclick="toggleMessages()">&times;</button>
        </div>
        <div class="message-body">
            <?php if (!empty($admin_messages)): ?>
                <?php foreach ($admin_messages as $msg): ?>
                    <?php 
                        $msg_text = isset($msg['remarks']) ? $msg['remarks'] : '';
                        $msg_time = isset($msg['created_at']) ? $msg['created_at'] : 'Recently';
                        $msg_source = isset($msg['src']) ? $msg['src'] : 'general';
                        $msg_hash = md5($msg_source . $msg_text . $msg_time);
                        $is_read = $msg['is_read'];
                    ?>
                    <div class="admin-msg-card <?php echo $is_read ? 'read' : 'unread'; ?>" onclick='readMessage(this, <?php echo json_encode($msg_text); ?>, <?php echo json_encode($msg_time); ?>, "<?php echo $msg_hash; ?>")'>
                        <div class="msg-meta">
                            <span class="msg-sender"><i class="fas fa-user-shield"></i> Health Center Admin</span>
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
                    <p>No health updates or messages.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="read-modal" id="readMessageModal">
        <div class="read-modal-header">
            <h3 style="margin: 0; font-size: 1rem;"><i class="fas fa-envelope-open-text"></i> Message Details</h3>
            <button type="button" class="close-btn" onclick="closeReadModal()">&times;</button>
        </div>
        <div class="read-modal-body">
            <div class="msg-meta" style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                <span class="msg-sender"><i class="fas fa-user-shield"></i> Health Center Admin</span>
                <span id="readMsgTime">Recently</span>
            </div>
            <p id="readMsgContent"></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeAllModals()" style="position: absolute; right: 15px; top: 12px;">&times;</span>
            <div class="modal-header"><h3>System Notice</h3></div>
            <div class="modal-body-content"><p style="margin: 0;">Are you sure you want to log out?</p></div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeAllModals()">Cancel</button>
                <a href="index.php" style="text-decoration: none;"><button type="button" class="btn-modal-action">Yes</button></a>
            </div>
        </div>
    </div>

    <!-- Hero Banner -->
    <header class="rd-hero-banner">
        <div class="rd-welcome-msg">
            <h1>HEALTH CENTER, <span><?php echo htmlspecialchars($full_name); ?></span></h1>
            <p>BARANGAY MALITBOG HEALTH SERVICES & APPOINTMENTS <?php echo $is_admin_mode ? '(ADMIN VIEW MODE)' : ''; ?></p>
        </div>
        <div class="rd-urgent-notices">
            <strong>HEALTH OVERVIEW</strong>
            <ul>
                <li>View your scheduled check-ups, medical appointments, and vaccination histories.</li>
                <li>Request or book new health appointments through the barangay center portal.</li>
            </ul>
        </div>
    </header>

    <!-- Main Content Container -->
    <div class="rd-container" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
        
        <?php if ($update_success): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle"></i> Your health profile has been successfully updated in the database!
            </div>
        <?php elseif (!empty($update_error)): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($update_error); ?>
            </div>
        <?php endif; ?>

        <!-- Top Action Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
            <h2 class="rd-section-title" style="margin-bottom: 0; color: #1a1a8c; font-family: 'Poppins', sans-serif;">Medical & Appointment Metrics</h2>
            <?php if (!$is_admin_mode): ?>
            <a href="health_center.php" style="text-decoration: none;">
                <button type="button" class="rd-btn-track rd-btn-blue" style="display: inline-flex; align-items: center; gap: 8px; background: #1a1a8c; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-calendar-plus"></i> Request Appointment
                </button>
            </a>
            <?php endif; ?>
        </div>

        <!-- Metrics Top Grid Cards -->
        <div class="metrics-top-grid">
            <div class="metric-card-box clickable-metric-card" id="totalApptCard" onclick="toggleSection('appointmentHistorySection', 'totalApptCard', 'toggleIconIndicator1')" title="Click to view all appointments">
                <i class="fas fa-calendar-alt"></i>
                <div class="rd-card-title" style="font-size: 0.8rem; color: #555; font-weight: bold;">TOTAL APPOINTMENTS <i class="fas fa-chevron-down" id="toggleIconIndicator1" style="font-size: 0.7rem; margin-left: 4px;"></i></div>
                <div class="metric-value-display"><?= $total_appointments ?></div>
            </div>

            <div class="metric-card-box clickable-metric-card" id="upcomingApptCard" onclick="toggleSection('upcomingHistorySection', 'upcomingApptCard', 'toggleIconIndicator2')" title="Click to view upcoming appointments">
                <i class="fas fa-clock"></i>
                <div class="rd-card-title" style="font-size: 0.8rem; color: #555; font-weight: bold;">UPCOMING <i class="fas fa-chevron-down" id="toggleIconIndicator2" style="font-size: 0.7rem; margin-left: 4px;"></i></div>
                <div class="metric-value-display"><?= $upcoming_appointments ?></div>
            </div>

            <div class="metric-card-box clickable-metric-card" id="completedApptCard" onclick="toggleSection('completedHistorySection', 'completedApptCard', 'toggleIconIndicator3')" style="border-top-color: #28a745;" title="Click to view completed checkups">
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                <div class="rd-card-title" style="font-size: 0.8rem; color: #555; font-weight: bold;">COMPLETED CHECKUPS <i class="fas fa-chevron-down" id="toggleIconIndicator3" style="font-size: 0.7rem; margin-left: 4px;"></i></div>
                <div class="metric-value-display" style="color: #28a745;"><?= $completed_checkups ?></div>
            </div>
        </div>

        <!-- Resident Profile Form Section -->
        <div class="report-table-container" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <h3 style="color: #000066; margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-user-circle"></i> Resident Health Profile Info
                </h3>
                <button type="button" id="editToggleBtn" onclick="toggleEditMode()" style="background: #e2e8f0; color: #334155; border: none; padding: 7px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                    <i class="fas fa-edit" id="editIcon"></i> <span id="editToggleText">Edit Profile</span>
                </button>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="update_profile_form" value="1">
                
                <div class="scrollable-info-container">
                    <div class="health-list-two-divisions">
                        
                        <!-- DIVISION 1 -->
                        <div class="health-list-division">
                            <div class="health-list-section-header">
                                <i class="fas fa-id-card"></i> Section 1: Resident Information
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-fingerprint"></i> Resident ID</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field" value="<?php echo htmlspecialchars($resident_data['resident_id'] ?? $resident_id); ?>" disabled>
                                </div>
                            </div>

                            <div class="health-list-section-header">
                                <i class="fas fa-notes-medical"></i> Section 2: Medical Information
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-tint"></i> Blood Type</div>
                                <div class="health-list-input-wrapper">
                                    <?php 
                                        $bt = $resident_data['blood_type'] ?? ($resident_data['Blood Type'] ?? ''); 
                                    ?>
                                    <select class="form-input profile-field profile-input" name="blood_type" disabled>
                                        <option value="">Select Blood Type</option>
                                        <option value="A+" <?php echo ($bt === 'A+') ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo ($bt === 'A-') ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo ($bt === 'B+') ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo ($bt === 'B-') ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo ($bt === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo ($bt === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo ($bt === 'O+') ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo ($bt === 'O-') ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-stethoscope"></i> Existing Medical Conditions</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="conditions" value="<?php echo htmlspecialchars($resident_data['conditions'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-allergies"></i> Allergy Status</div>
                                <div class="health-list-input-wrapper">
                                    <?php $alg = $resident_data['allergy_status'] ?? ''; ?>
                                    <select class="form-input profile-field profile-input" name="allergy_status" disabled>
                                        <option value="">Select Allergy Status</option>
                                        <option value="None" <?php echo ($alg === 'None') ? 'selected' : ''; ?>>None</option>
                                        <option value="Food Allergies" <?php echo ($alg === 'Food Allergies') ? 'selected' : ''; ?>>Food Allergies</option>
                                        <option value="Drug / Medication Allergies" <?php echo ($alg === 'Drug / Medication Allergies') ? 'selected' : ''; ?>>Drug / Medication Allergies</option>
                                        <option value="Environmental Allergies" <?php echo ($alg === 'Environmental Allergies') ? 'selected' : ''; ?>>Environmental Allergies</option>
                                        <option value="Multiple Allergies" <?php echo ($alg === 'Multiple Allergies') ? 'selected' : ''; ?>>Multiple Allergies</option>
                                    </select>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-pills"></i> Current Medications</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="current_medications" value="<?php echo htmlspecialchars($resident_data['current_medications'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-wheelchair"></i> Disability Status</div>
                                <div class="health-list-input-wrapper">
                                    <?php $dsb = $resident_data['disability_status'] ?? ''; ?>
                                    <select class="form-input profile-field profile-input" name="disability_status" disabled>
                                        <option value="">Select Disability Status</option>
                                        <option value="None" <?php echo ($dsb === 'None') ? 'selected' : ''; ?>>None</option>
                                        <option value="Physical Disability" <?php echo ($dsb === 'Physical Disability') ? 'selected' : ''; ?>>Physical Disability</option>
                                        <option value="Visual Impairment" <?php echo ($dsb === 'Visual Impairment') ? 'selected' : ''; ?>>Visual Impairment</option>
                                        <option value="Hearing Impairment" <?php echo ($dsb === 'Hearing Impairment') ? 'selected' : ''; ?>>Hearing Impairment</option>
                                        <option value="Intellectual / Learning Disability" <?php echo ($dsb === 'Intellectual / Learning Disability') ? 'selected' : ''; ?>>Intellectual / Learning Disability</option>
                                        <option value="Other" <?php echo ($dsb === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="health-list-section-header">
                                <i class="fas fa-heartbeat"></i> Section 3: Physical Assessment
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-ruler-vertical"></i> Height (cm)</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="height" value="<?php echo htmlspecialchars($resident_data['height'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-weight"></i> Weight (kg)</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="weight" value="<?php echo htmlspecialchars($resident_data['weight'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-tachometer-alt"></i> Blood Pressure (mmHg)</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="blood_pressure" value="<?php echo htmlspecialchars($resident_data['blood_pressure'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-thermometer-half"></i> Temperature</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="temperature" value="<?php echo htmlspecialchars($resident_data['temperature'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-heart"></i> Heart Rate (bpm)</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="heart_rate" value="<?php echo htmlspecialchars($resident_data['heart_rate'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-shield-alt"></i> General Health Status</div>
                                <div class="health-list-input-wrapper">
                                    <?php $hst = $resident_data['health_status'] ?? ''; ?>
                                    <select class="form-input profile-field profile-input" name="health_status" disabled>
                                        <option value="">Select Health Status</option>
                                        <option value="Good / Healthy" <?php echo ($hst === 'Good / Healthy') ? 'selected' : ''; ?>>Good / Healthy</option>
                                        <option value="Fair" <?php echo ($hst === 'Fair') ? 'selected' : ''; ?>>Fair</option>
                                        <option value="Under Treatment" <?php echo ($hst === 'Under Treatment') ? 'selected' : ''; ?>>Under Treatment</option>
                                        <option value="Critical / Chronically Ill" <?php echo ($hst === 'Critical / Chronically Ill') ? 'selected' : ''; ?>>Critical / Chronically Ill</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DIVISION 2 -->
                        <div class="health-list-division">
                            <div class="health-list-section-header">
                                <i class="fas fa-hospital-user"></i> Section 4: Health Services & Habits
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-syringe"></i> Immunization Status</div>
                                <div class="health-list-input-wrapper">
                                    <?php $imm = $resident_data['immunization_status'] ?? ''; ?>
                                    <select class="form-input profile-field profile-input" name="immunization_status" disabled>
                                        <option value="">Select Immunization Status</option>
                                        <option value="Fully Immunized" <?php echo ($imm === 'Fully Immunized') ? 'selected' : ''; ?>>Fully Immunized</option>
                                        <option value="Partially Immunized" <?php echo ($imm === 'Partially Immunized') ? 'selected' : ''; ?>>Partially Immunized</option>
                                        <option value="Not Immunized" <?php echo ($imm === 'Not Immunized') ? 'selected' : ''; ?>>Not Immunized</option>
                                        <option value="Unknown" <?php echo ($imm === 'Unknown') ? 'selected' : ''; ?>>Unknown</option>
                                    </select>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-id-badge"></i> PhilHealth Membership Status</div>
                                <div class="health-list-input-wrapper">
                                    <?php $ph = $resident_data['philhealth'] ?? ''; ?>
                                    <select class="form-input profile-field profile-input" name="philhealth" disabled>
                                        <option value="">Select PhilHealth Status</option>
                                        <option value="Member" <?php echo ($ph === 'Member') ? 'selected' : ''; ?>>Member</option>
                                        <option value="Dependent" <?php echo ($ph === 'Dependent') ? 'selected' : ''; ?>>Dependent</option>
                                        <option value="Non-Member" <?php echo ($ph === 'Non-Member') ? 'selected' : ''; ?>>Non-Member</option>
                                        <option value="Indigent / Sponsored" <?php echo ($ph === 'Indigent / Sponsored') ? 'selected' : ''; ?>>Indigent / Sponsored</option>
                                    </select>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-clinic-medical"></i> Preferred Health Center</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="preferred_health_center" value="<?php echo htmlspecialchars($resident_data['preferred_health_center'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-smoking"></i> Smoking Status</div>
                                <div class="health-list-input-wrapper">
                                    <?php $smk = $resident_data['smoking_status'] ?? ''; ?>
                                    <select class="form-input profile-field profile-input" name="smoking_status" disabled>
                                        <option value="">Select Smoking Status</option>
                                        <option value="Non-Smoker" <?php echo ($smk === 'Non-Smoker') ? 'selected' : ''; ?>>Non-Smoker</option>
                                        <option value="Current Smoker" <?php echo ($smk === 'Current Smoker') ? 'selected' : ''; ?>>Current Smoker</option>
                                        <option value="Former Smoker" <?php echo ($smk === 'Former Smoker') ? 'selected' : ''; ?>>Former Smoker</option>
                                    </select>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-wine-bottle"></i> Alcohol Consumption</div>
                                <div class="health-list-input-wrapper">
                                    <?php $alc = $resident_data['alcohol_consumption'] ?? ''; ?>
                                    <select class="form-input profile-field profile-input" name="alcohol_consumption" disabled>
                                        <option value="">Select Alcohol Consumption</option>
                                        <option value="Non-Drinker" <?php echo ($alc === 'Non-Drinker') ? 'selected' : ''; ?>>Non-Drinker</option>
                                        <option value="Occasional Drinker" <?php echo ($alc === 'Occasional Drinker') ? 'selected' : ''; ?>>Occasional Drinker</option>
                                        <option value="Regular Drinker" <?php echo ($alc === 'Regular Drinker') ? 'selected' : ''; ?>>Regular Drinker</option>
                                        <option value="Heavy Drinker" <?php echo ($alc === 'Heavy Drinker') ? 'selected' : ''; ?>>Heavy Drinker</option>
                                    </select>
                                </div>
                            </div>

                            <div class="health-list-section-header">
                                <i class="fas fa-phone-alt"></i> Section 5: Emergency Contact
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-user-friends"></i> Relationship to Resident</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="relationship" value="<?php echo htmlspecialchars($resident_data['relationship'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-phone-square-alt"></i> Emergency Contact Number</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="emergency_contact" value="<?php echo htmlspecialchars($resident_data['emergency_contact'] ?? ''); ?>" disabled>
                                </div>
                            </div>

                            <div class="health-list-section-header">
                                <i class="fas fa-clipboard-list"></i> Section 6: Remarks and Records Details
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-comment-medical"></i> Additional Health Remarks</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field profile-input" name="remarks" value="<?php echo htmlspecialchars($resident_data['remarks'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="health-list-item">
                                <div class="health-list-label-group"><i class="fas fa-user-nurse"></i> Recorded By</div>
                                <div class="health-list-input-wrapper">
                                    <input type="text" class="form-input profile-field" value="<?php echo htmlspecialchars($resident_data['record'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div id="saveBtnContainer" style="display: none; justify-content: flex-end; gap: 10px; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                    <button type="button" onclick="toggleEditMode()" style="background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <!-- Target element #saveBtnContainer > button:nth-child(2) -->
                    <button type="submit" style="background: #1a1a8c; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-save"></i> Save Health Form
                    </button>
                </div>
            </form>
        </div>

        <!-- Appointment History Sections -->
        <div class="report-table-container" id="appointmentHistorySection" style="display: none;">
            <h3 style="color: #000066; margin-top: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-medical"></i> Appointment & Medical History (Total: <?= $total_appointments ?>)
            </h3>
            <?php if (!empty($health_records)): ?>
                <table class="report-history-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Service Type</th>
                            <th>Appointment Date</th>
                            <th>Status</th>
                            <th>Notes / Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($health_records as $row): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($row['id']); ?></strong></td>
                                <td><?php echo htmlspecialchars(!empty($row['service']) ? $row['service'] : 'General Checkup'); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['appt_date']))); ?></td>
                                <td>
                                    <span class="badge-status badge-default">
                                        <?php echo htmlspecialchars(!empty($row['status']) ? ucfirst($row['status']) : 'Pending'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['notes'] ?? $row['remarks'] ?? 'None'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; color: #888; padding: 40px 0;">
                    <p style="margin: 0; color: #64748b; font-weight: 500;">No health or medical appointment history found.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        const globalOverlay = document.getElementById('globalOverlay');

        function toggleSection(sectionId, cardId, iconId) {
            const targetSection = document.getElementById(sectionId);
            const targetCard = document.getElementById(cardId);
            const targetIcon = document.getElementById(iconId);

            const sections = ['appointmentHistorySection', 'upcomingHistorySection', 'completedHistorySection'];
            const cards = ['totalApptCard', 'upcomingApptCard', 'completedApptCard'];
            const icons = ['toggleIconIndicator1', 'toggleIconIndicator2', 'toggleIconIndicator3'];

            let isCurrentlyOpen = (targetSection.style.display === 'block');

            sections.forEach((sec, idx) => {
                let el = document.getElementById(sec);
                if(el) el.style.display = 'none';
                let c = document.getElementById(cards[idx]);
                if(c) c.classList.remove('active-card');
                let i = document.getElementById(icons[idx]);
                if(i) i.className = 'fas fa-chevron-down';
            });

            if (!isCurrentlyOpen) {
                targetSection.style.display = 'block';
                targetCard.classList.add('active-card');
                targetIcon.className = 'fas fa-chevron-up';
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function toggleEditMode() {
            const inputs = document.querySelectorAll('.profile-input');
            const saveBtnContainer = document.getElementById('saveBtnContainer');
            const editToggleText = document.getElementById('editToggleText');
            const editIcon = document.getElementById('editIcon');
            const editToggleBtn = document.getElementById('editToggleBtn');

            let isEditing = saveBtnContainer.style.display === 'flex';

            if (isEditing) {
                inputs.forEach(input => input.disabled = true);
                saveBtnContainer.style.display = 'none';
                editToggleText.innerText = 'Edit Profile';
                editIcon.className = 'fas fa-edit';
                editToggleBtn.style.background = '#e2e8f0';
                editToggleBtn.style.color = '#334155';
            } else {
                inputs.forEach(input => input.disabled = false);
                saveBtnContainer.style.display = 'flex';
                editToggleText.innerText = 'Cancel Editing';
                editIcon.className = 'fas fa-times';
                editToggleBtn.style.background = '#fee2e2';
                editToggleBtn.style.color = '#991b1b';
            }
        }
    </script>
</body>
</html>