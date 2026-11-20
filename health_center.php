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

// Fetch Health Schedule Updates from Database safely including schedule_date[cite: 1]
$center_schedules = [];
$sched_query = "SELECT day_of_week, schedule_date, service_title, description FROM health_schedule_updates ORDER BY schedule_date DESC, FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') LIMIT 5";
if ($result_sched = $conn->query($sched_query)) {
    while ($row_sched = $result_sched->fetch_assoc()) {
        $center_schedules[] = $row_sched;
    }
}

// Handle Appointment Form Submission via AJAX/POST (Allows multiple appointments per resident)[cite: 1]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    $form_resident_id = trim($_POST['resident_id']);
    $service = trim($_POST['service']);
    $appt_day = trim($_POST['appt_day']); 
    $notes = trim($_POST['notes']); 
    
    $blood_type = trim($_POST['blood_type'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $medical_conditions = trim($_POST['medical_conditions'] ?? '');

    if (!empty($form_resident_id) && !empty($service) && !empty($appt_day)) {
        $current_year = date('Y');
        $prefix = "APPT" . $current_year . "-";
        
        $conn->begin_transaction();
        try {
            $count_query = "SELECT COUNT(*) as total FROM appointments WHERE YEAR(created_at) = ?";
            $stmt_count = $conn->prepare($count_query);
            $stmt_count->bind_param("s", $current_year);
            $stmt_count->execute();
            $count_result = $stmt_count->get_result()->fetch_assoc();
            $next_seq = intval($count_result['total']) + 1;
            $stmt_count->close();

            $appointment_code = $prefix . str_pad($next_seq, 4, '0', STR_PAD_LEFT);

            $comprehensive_notes = "Preferred Day: " . $appt_day . " | Notes: " . $notes . " | Blood Type: " . $blood_type . " | Allergies: " . $allergies . " | Conditions: " . $medical_conditions;

            $stmt_appt = $conn->prepare("INSERT INTO appointments (appointment_code, resident_id, service, appt_date, notes, status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
            if ($stmt_appt) {
                $stmt_appt->bind_param("sisss", $appointment_code, $form_resident_id, $service, $appt_day, $comprehensive_notes);
                if ($stmt_appt->execute()) {
                    $conn->commit();
                    $stmt_appt->close();
                    
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        echo json_encode(['status' => 'success', 'appt_id' => $appointment_code]);
                        exit();
                    } else {
                        header("Location: " . $_SERVER['PHP_SELF'] . "?status=success&appt=" . $appointment_code);
                        exit();
                    }
                } else {
                    throw new Exception('Failed to save appointment into database.');
                }
            } else {
                throw new Exception('Failed to prepare statement.');
            }
        } catch (Exception $e) {
            $conn->rollback();
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit();
            }
        }
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
            exit();
        }
    }
}

// Fetch all messages linked to this resident[cite: 1]
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
    <title>Health Center - Barangay Malitbog[cite: 1]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style1.css">
   
    <style>
        .rd-dashboard-page .rd-hero-banner {
            scroll-margin-top: 80px;
            background: linear-gradient(rgba(26, 26, 140, 0.85), rgba(26, 26, 140, 0.85)), 
                        url('image/backgroundphoto1.jpg') no-repeat center center/cover;
            padding: 50px 10%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
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
        .logo-text h2 { margin: 0; color: #000066; font-size: 1.1rem; letter-spacing: 1px; }
        .logo-text small { font-size: 0.7rem; color: #555; display: block; }

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

        .health-grid {
            display: flex;
            flex-direction: column;
            gap: 30px;
            margin-top: 30px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #000066;
            font-size: 0.85rem;
        }

        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #eaeaea;
            border-radius: 6px;
            font-size: 0.9rem;
            box-sizing: border-box;
        }

        .form-group input[readonly] {
            background-color: #f8f9fa;
        }

        .schedule-item {
            padding: 12px 0;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .schedule-item:last-child {
            border-bottom: none;
        }

        .schedule-date-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            background-color: #d4edda;
            color: #155724;
        }

        .schedule-notice {
            font-size: 0.82rem;
            color: #666;
        }

        .health-modal {
            display: none;
            position: fixed;
            z-index: 1200;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .health-modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            box-sizing: border-box;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .rd-btn-exit {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            transition: background-color 0.2s ease;
        }
        .rd-btn-exit:hover {
            background-color: #5a6268;
        }

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

    <div class="rd-global-overlay" id="globalOverlay" onclick="closeAllModals()"></div>

    <div class="menu-slide-drawer" id="menuSlideDrawer">
        <div class="menu-drawer-header">
            <h3><i class="fas fa-bars"></i> Menu Options</h3>
            <button type="button" class="close-btn" onclick="toggleNavBarMenu()">&times;</button>
        </div>
        <div class="menu-drawer-body">
            <a href="certification.php"><i class="fas fa-file-contract"></i> Apply Certification</a>
            <a href="resident_dashboard.php"><i class="fas fa-home"></i> Dashboard Home</a>
            <a href="my_profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 5px 0;">
            <a href="javascript:void(0);" id="logoutBtnDrawer"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

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

    <header class="rd-hero-banner">
        <div class="rd-welcome-msg">
            <h1>HEALTH <span>CENTER</span></h1>
            <p>Book health appointments and view medical service schedules.</p>
        </div>
    </header>

    <div class="rd-container">
        <div class="health-grid">
            <div class="card" style="text-align: center; padding: 40px;">
                <h2 class="rd-section-title" style="margin-bottom: 15px;">Need Medical Assistance?</h2>
                <p style="color: #666; margin-bottom: 25px;">Schedule a visit with our health professionals by filling out your health information and booking request.</p>
                <button type="button" onclick="openHealthApptModal()" class="rd-btn-track rd-btn-blue" style="padding: 14px 30px; font-weight: bold; cursor: pointer; font-size: 1rem;">
                    <i class="fas fa-calendar-plus"></i> REQUEST AN APPOINTMENT
                </button>
            </div>

            <div class="sidebar">
                <h2 class="rd-section-title">Center Schedule</h2>
                <div class="card">
                    <?php if (!empty($center_schedules)): ?>
                        <?php foreach ($center_schedules as $sched): ?>
                            <div class="schedule-item">
                                <div class="schedule-date-header">
                                    <span><strong><i class='fas fa-calendar-day'></i> <?php echo htmlspecialchars($sched['day_of_week']); ?> (<?php echo htmlspecialchars($sched['schedule_date']); ?>)</strong></span>
                                    <span class="status-badge"><?php echo htmlspecialchars($sched['service_title']); ?></span>
                                </div>
                                <?php if (!empty($sched['description'])): ?>
                                    <span class="schedule-notice"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($sched['description']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: #888; padding: 10px 0;">
                            <p style="margin: 0; font-size: 0.9rem;">No upcoming schedule updates.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Information & Appointment Request Modal Form -->
    <div id="healthApptModal" class="health-modal">
        <div class="health-modal-content">
            <span class="close-btn" onclick="closeHealthApptModal()" style="position: absolute; right: 20px; top: 15px; font-size: 1.5rem; cursor: pointer;">&times;</span>
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #000066;"><i class="fas fa-file-medical"></i> Health Info & Appointment Request Form</h3>
            
            <!-- Generated Appointment ID display container -->
            <div id="generatedApptCodeContainer" style="display: none; background: #e8f4fd; border: 1px solid #b8daff; color: #004085; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem;">
                <strong>Generated Appointment ID:</strong> <span id="displayApptCode" style="color: #000066; font-weight: bold;"></span>
            </div>

            <form id="appointmentForm" method="POST" action="">
                <fieldset style="border: 1px solid #eaeaea; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                    <legend style="font-weight: 600; color: #000066; font-size: 0.9rem; padding: 0 5px;">Appointment Details</legend>
                    <div class="form-group">
                        <label>Resident ID</label>
                        <input type="text" name="resident_id" value="<?php echo htmlspecialchars($resident_id); ?>" readonly required>
                    </div>
                    <div class="form-group">
                        <label>Nature of Visit / Service</label>
                        <select name="service" required>
                            <option value="">Select Service...</option>
                            <option value="General Check-up">General Check-up</option>
                            <option value="Vaccination">Vaccination</option>
                            <option value="Prenatal Care">Prenatal Care</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Preferred Day (Monday to Friday)</label>
                        <select name="appt_day" required>
                            <option value="">Select Available Day...</option>
                            <?php 
                            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            $scheduled_map = [];
                            foreach ($center_schedules as $s) {
                                $scheduled_map[strtolower(trim($s['day_of_week']))] = $s['service_title'] . ' (' . $s['schedule_date'] . ')';
                            }
                            
                            foreach ($weekdays as $day): 
                                $day_lower = strtolower($day);
                                $tag = isset($scheduled_map[$day_lower]) ? " - " . $scheduled_map[$day_lower] : "";
                            ?>
                                <option value="<?php echo $day; ?>"><?php echo $day . $tag; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Additional Notes / Specific Concerns</label>
                        <textarea name="notes" rows="3" placeholder="Enter any extra details or instructions here..."></textarea>
                    </div>
                </fieldset>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeHealthApptModal()" class="rd-btn-track rd-btn-exit" style="flex: 1; padding: 12px; font-weight: bold; cursor: pointer;">EXIT</button>
                    <button type="submit" name="submit_appointment" id="submitBtn" class="rd-btn-track rd-btn-blue" style="flex: 2; padding: 12px; font-weight: bold; cursor: pointer;">SUBMIT APPOINTMENT REQUEST</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Result Modal -->
    <div id="resultModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('resultModal')" style="position: absolute; right: 15px; top: 12px;">&times;</span>
            <div class="modal-header"><h3 id="modalTitle">Appointment Notice</h3></div>
            <div class="modal-body-content"><p id="modalMessageBody" style="margin: 0;">Processing...</p></div>
            <div class="modal-footer"><button type="button" class="btn-modal-action" onclick="closeModal('resultModal')">OK</button></div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeModalBtn" onclick="document.getElementById('logoutModal').style.display='none'" style="position: absolute; right: 15px; top: 12px;">&times;</span>
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content">
                <p style="margin: 0;">Are you sure you want to log out?</p>
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
        const globalOverlay = document.getElementById('globalOverlay');

        function openHealthApptModal() {
            document.getElementById('healthApptModal').style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        function closeHealthApptModal() {
            document.getElementById('healthApptModal').style.display = 'none';
            globalOverlay.style.display = 'none';
            document.getElementById('appointmentForm').reset();
            document.getElementById('generatedApptCodeContainer').style.display = 'none';
        }

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
                const badgeMobile = document.getElementById('msgBadgeMobile');
                
                [badgeTop, badgeMobile].forEach(badge => {
                    if (badge) {
                        let currentCount = parseInt(badge.innerText, 10);
                        if (currentCount > 1) {
                            badge.innerText = currentCount - 1;
                        } else {
                            badge.remove();
                            const inboxTrigger = document.querySelector('.rd-message-symbol-trigger');
                            if (inboxTrigger) {
                                inboxTrigger.classList.remove('has-unread-pulse');
                            }
                        }
                    }
                });

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

        window.addEventListener('click', function() {
            document.querySelectorAll('.msg-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
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

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (modalId === 'resultModal') location.reload();
        }

        function closeAllModals() {
            document.getElementById('menuSlideDrawer').style.display = 'none';
            document.getElementById('messageDrawer').style.display = 'none';
            document.getElementById('readMessageModal').style.display = 'none';
            document.getElementById('healthApptModal').style.display = 'none';
            document.getElementById('appointmentForm').reset();
            document.getElementById('generatedApptCodeContainer').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        const appointmentForm = document.getElementById('appointmentForm');
        const submitBtn = document.getElementById('submitBtn');
        const resultModal = document.getElementById('resultModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessageBody = document.getElementById('modalMessageBody');

        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(appointmentForm);
            formData.append('submit_appointment', '1');

            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                closeHealthApptModal();
                if (data.status === 'success') {
                    modalTitle.textContent = "Success: Appointment Requested";
                    modalMessageBody.textContent = "Your health appointment has been successfully requested for your chosen weekday (Reference ID: #" + data.appt_id + ").";
                    
                    // Populate and show the generated code container if needed
                    document.getElementById('displayApptCode').textContent = "#" + data.appt_id;
                    document.getElementById('generatedApptCodeContainer').style.display = 'block';

                    appointmentForm.reset();
                } else {
                    modalTitle.textContent = "Notice: Submission Issue";
                    modalMessageBody.textContent = data.message || 'Appointment request failed.';
                }
                submitBtn.disabled = false;
                submitBtn.textContent = 'SUBMIT APPOINTMENT REQUEST';
                resultModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            })
            .catch(error => {
                closeHealthApptModal();
                modalTitle.textContent = "System Error";
                modalMessageBody.textContent = 'A network error occurred.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'SUBMIT APPOINTMENT REQUEST';
                resultModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            });
        });
    </script>
</body>
</html>
<?php if (isset($conn)) { $conn->close(); } ?>