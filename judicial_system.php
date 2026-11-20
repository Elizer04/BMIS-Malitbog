<?php
include 'db_connect.php'; 
session_start();

$resident_id = isset($_SESSION['resident_id']) ? $_SESSION['resident_id'] : 1;
$user_full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Resident';

if (!isset($_SESSION['deleted_messages'])) { $_SESSION['deleted_messages'] = []; }
if (!isset($_SESSION['read_messages'])) { $_SESSION['read_messages'] = []; }

// Fetch exact database columns from residents table based on schema[cite: 2]
$resident_data = [];
if ($resident_id) {
    $stmt_res = $conn->prepare("SELECT last_name, first_name, middle_name, sex, occupation, religion, citizenship, voter_status, spouse_name, date_of_birth, civil_status, address, email, contact_no FROM residents WHERE resident_id = ? LIMIT 1");
    if ($stmt_res) {
        $stmt_res->bind_param("s", $resident_id);
        $stmt_res->execute();
        $result_res = $stmt_res->get_result();
        if ($row_res = $result_res->fetch_assoc()) {
            $resident_data = $row_res;
        }
        $stmt_res->close();
    }
}

// Construct full name or individual parts matching the database columns[cite: 2]
$first_name  = $resident_data['first_name'] ?? '';
$middle_name = $resident_data['middle_name'] ?? '';
$last_name   = $resident_data['last_name'] ?? '';
$full_name_db = trim("$first_name $middle_name $last_name");
if (empty($full_name_db)) {
    $full_name_db = $user_full_name;
}

$rep_address = $resident_data['address'] ?? '';
$rep_gender  = $resident_data['sex'] ?? '';
$rep_contact = $resident_data['contact_no'] ?? '';
$rep_email   = $resident_data['email'] ?? '';
$rep_dob     = $resident_data['date_of_birth'] ?? '';
$rep_status  = $resident_data['civil_status'] ?? '';

// Calculate age dynamically from date of birth if available
$rep_age = '';
if (!empty($rep_dob)) {
    $dob_date = new DateTime($rep_dob);
    $today = new DateTime('today');
    $rep_age = $dob_date->diff($today)->y;
}

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

// Fetch admin messages linked strictly to this resident
$admin_messages = []; 
$unread_count = 0;

if ($resident_id) {
    $msg_query = "
        SELECT 'residency' AS src, claim_message AS remarks, status, created_at FROM cert_of_residency WHERE resident_id = ? AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'indigency' AS src, claim_message AS remarks, status, created_at FROM cert_of_indigency WHERE resident_id = ? AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'clearance' AS src, claim_message AS remarks, status, created_at FROM barangay_clearance WHERE resident_id = ? AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'resident' AS src, remarks, 'General' AS status, created_at FROM residents WHERE resident_id = ? AND remarks IS NOT NULL AND remarks != ''
        UNION ALL
        SELECT 'appointment' AS src, remarks, status, created_at FROM appointments WHERE resident_id = ? AND remarks IS NOT NULL AND remarks != ''
        ORDER BY created_at DESC
    ";
    
    if ($stmt_msg = $conn->prepare($msg_query)) {
        $stmt_msg->bind_param("sssss", $resident_id, $resident_id, $resident_id, $resident_id, $resident_id);
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

// Handle AJAX Request from Blotter Form Submission / Draft Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    
    header('Content-Type: application/json');

    $form_action = $_POST['form_action'] ?? 'Submit Report';

    if ($form_action === 'Save as Draft') {
        echo json_encode([
            'status'  => 'draft_success',
            'message' => 'Draft saved successfully! You can view and resume it from your Dashboard Overview.'
        ]);
        exit;
    }

    $case_id = 'CASE-' . date('Ymd') . '-' . rand(1000, 9999);

    $reporter_name    = $_POST['reporter_name'] ?? '';
    $reporter_address = $_POST['reporter_address'] ?? '';
    $reporter_gender  = $_POST['reporter_gender'] ?? '';
    $reporter_contact = $_POST['reporter_contact'] ?? '';
    $reporter_email   = $_POST['reporter_email'] ?? '';
    $reporter_dob     = !empty($_POST['reporter_dob']) ? $_POST['reporter_dob'] : null;
    $reporter_age     = !empty($_POST['reporter_age']) ? (int)$_POST['reporter_age'] : null;

    $witnesses = isset($_POST['witnesses']) ? json_encode(array_values($_POST['witnesses'])) : json_encode([]);
    $suspects  = isset($_POST['suspects']) ? json_encode(array_values($_POST['suspects'])) : json_encode([]);

    $first_suspect = $_POST['suspects'][0]['name'] ?? '';
    $respondent_name = !empty($first_suspect) ? $first_suspect : 'Unknown';

    $incident_date     = !empty($_POST['incident_date']) ? $_POST['incident_date'] : null;
    $incident_time     = !empty($_POST['incident_time']) ? $_POST['incident_time'] : null;
    $incident_location = $_POST['incident_location'] ?? '';
    $type_of_incident  = $_POST['type_of_incident'] ?? '';
    $nature_of_dispute = $type_of_incident; 
    $narrative         = $_POST['narrative'] ?? ''; 

    $status = 'Mediation Stage';

    $evidence_files = [];
    $upload_dir = 'uploads/evidence/';

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['evidence_files']['name'][0])) {
        foreach ($_FILES['evidence_files']['tmp_name'] as $key => $tmp_name) {
            $file_name = time() . '_' . basename($_FILES['evidence_files']['name'][$key]);
            $file_size = $_FILES['evidence_files']['size'][$key];
            $target_path = $upload_dir . $file_name;

            if ($file_size <= 1048576) {
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $evidence_files[] = $target_path;
                }
            }
        }
    }
    $evidence = json_encode($evidence_files);

    $stmt = $conn->prepare("INSERT INTO my_cases (
        case_id, resident_id, respondent_name, nature_of_dispute, details, status, 
        reporter_name, reporter_address, reporter_gender, reporter_contact, reporter_email, 
        reporter_dob, reporter_age, witnesses, suspects, incident_date, incident_time, 
        incident_location, type_of_incident, evidence
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "sssssssssssssissssss",
        $case_id, $resident_id,
        $respondent_name, $nature_of_dispute,
        $narrative, $status,
        $reporter_name, $reporter_address,
        $reporter_gender, $reporter_contact,
        $reporter_email, $reporter_dob,
        $reporter_age, $witnesses,
        $suspects, $incident_date,
        $incident_time, $incident_location,
        $type_of_incident, $evidence
    );

    if ($stmt->execute()) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Blotter report submitted successfully! Case ID: ' . $case_id
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to save report: ' . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judicial System - Barangay Malitbog[cite: 1]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style1.css">
    <style>
    .hero-banner {
        background: linear-gradient(rgba(0, 0, 102, 0.7), rgba(0, 0, 102, 0.7)), 
                    url('image/backgroundphoto1.jpg') no-repeat center center/cover;
        padding: 40px 10% 60px;
        color: white;
        text-align: left;
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

    .blotter-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }

    .blotter-box h4 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 1rem;
        color: #000066;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 8px;
        font-family: 'Poppins', sans-serif;
    }

    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .form-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; }

    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: #475569; }
    
    input[type="text"], input[type="email"], input[type="date"], input[type="time"], input[type="number"], select, textarea { 
        width: 100%; 
        padding: 10px 12px; 
        border: 1px solid #cbd5e1; 
        border-radius: 8px; 
        box-sizing: border-box;
        font-size: 0.9rem;
        background-color: #f8fafc;
    }

    .party-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 10px;
        position: relative;
    }

    .btn-dynamic {
        border: none;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 5px;
        margin-right: 5px;
    }
    .btn-add { background: #17a2b8; color: white; }
    .btn-remove { background: #f8f9fa; border: 1px solid #ccc; color: #333; }
    
    .action-buttons-group {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 25px;
    }

    .btn-action {
        padding: 12px 24px;
        border-radius: 25px;
        border: none;
        font-weight: bold;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-draft { background: #17a2b8; color: white; }
    .btn-clear { background: #f8f9fa; border: 1px solid #ccc; color: #333; }
    .btn-submit-report { background: #28a745; color: white; }

    @media (max-width: 768px) {
        .main-nav { padding: 8px 12px; }
        .logo-section img { height: 40px; width: 40px; }
        .logo-text h2 { font-size: 0.95rem; }
        .logo-text small { font-size: 0.6rem; }
        .nav-links { gap: 15px; }
        .rd-nav-item span, .rd-menu-symbol-trigger span, .rd-nav-menu-bar-trigger span, .rd-message-symbol-trigger span { display: none; }
        .form-grid-2, .form-grid-3, .form-grid-4 { grid-template-columns: 1fr; }
        .action-buttons-group { flex-direction: column; }
        .btn-action { width: 100%; }
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

    <!-- Navigation Bar -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="dashboard_overview.php" class="rd-back-link" title="Back to Dashboard Overview">
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

    <!-- Menu Slide Drawer -->
    <div class="menu-slide-drawer" id="menuSlideDrawer">
        <div class="menu-drawer-header">
            <h3><i class="fas fa-bars"></i> Menu Options</h3>
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

    <!-- Message Drawer Modal -->
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

    <!-- Custom Dialog Modal -->
    <div id="customDialogModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content">
                <p id="customDialogMessage" style="margin: 0;"></p>
            </div>
            <div class="modal-footer" style="text-align: right;">
                <button type="button" class="btn-modal-action" id="customDialogOkBtn">OK</button>
            </div>
        </div>
    </div>

    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>JUDICIAL <span>SYSTEM</span></h1>
            <p>BARANGAY BLOTTER & CASE REPORTING</p>
        </div>
    </header>

    <div class="container">
        <div class="form-header">
            <h2>Create New Blotter Report</h2>
        </div>

        <form id="blotterForm" enctype="multipart/form-data">
            
            <!-- 1. Reporting Party Details (Auto-generated from residents table) -->
            <div class="blotter-box">
                <h4>1. Reporting Party Details <small style="font-weight: normal; font-size: 0.75rem; color: #17a2b8; float: right;"></small></h4>
                <input type="hidden" name="resident_id" value="<?php echo htmlspecialchars($resident_id); ?>">
                <div class="form-grid-4">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="reporter_name" value="<?php echo htmlspecialchars($full_name_db); ?>" readonly style="background-color: #f1f5f9; cursor: not-allowed;" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="reporter_address" value="<?php echo htmlspecialchars($rep_address); ?>" readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="reporter_gender" style="background-color: #f1f5f9; pointer-events: none;" tabindex="-1">
                            <option value="">Select gender</option>
                            <option value="Male" <?php echo ($rep_gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($rep_gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($rep_gender === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="reporter_contact" value="<?php echo htmlspecialchars($rep_contact); ?>" readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                    </div>
                </div>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Email (optional)</label>
                        <input type="email" name="reporter_email" value="<?php echo htmlspecialchars($rep_email); ?>" readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="reporter_dob" value="<?php echo htmlspecialchars($rep_dob); ?>" readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="reporter_age" value="<?php echo htmlspecialchars($rep_age); ?>" readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                    </div>
                </div>
            </div>

            <!-- 2. Involved Parties -->
            <div class="blotter-box">
                <h4>2. Involved Parties</h4>
                <div class="form-grid-2">
                    
                    <!-- Witnesses Section -->
                    <div>
                        <label style="font-weight: bold; font-size: 0.95rem;">Witnesses</label>
                        <div id="witnessContainer">
                            <div class="party-item">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="witnesses[0][name]">
                                </div>
                                <div class="form-group">
                                    <label>Description / Details (optional)</label>
                                    <textarea name="witnesses[0][details]" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-dynamic btn-remove" onclick="removeWitness()">Remove Witness</button>
                        <button type="button" class="btn-dynamic btn-add" onclick="addWitness()">Add Witness</button>
                    </div>

                    <!-- Suspect(s) / Accused Section -->
                    <div>
                        <label style="font-weight: bold; font-size: 0.95rem;">Suspect(s) / Accused</label>
                        <div id="suspectContainer">
                            <div class="party-item">
                                <div class="form-grid-3">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="suspects[0][name]">
                                    </div>
                                    <div class="form-group">
                                        <label>Address</label>
                                        <input type="text" name="suspects[0][address]">
                                    </div>
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select name="suspects[0][gender]">
                                            <option value="">Select gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group" style="width: 33%;">
                                    <label>Age</label>
                                    <input type="number" name="suspects[0][age]">
                                </div>
                                <div class="form-group">
                                    <label>Description / Details (optional)</label>
                                    <textarea name="suspects[0][details]" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-dynamic btn-remove" onclick="removeSuspect()">Remove Suspect</button>
                        <button type="button" class="btn-dynamic btn-add" onclick="addSuspect()">Add Suspect</button>
                    </div>

                </div>
            </div>

            <!-- 3. Incident Details -->
            <div class="blotter-box">
                <h4>3. Incident Details</h4>
                <div class="form-grid-4">
                    <div class="form-group">
                        <label>Date of Incident</label>
                        <input type="date" name="incident_date" required>
                    </div>
                    <div class="form-group">
                        <label>Time of Incident</label>
                        <input type="time" name="incident_time">
                    </div>
                    <div class="form-group">
                        <label>Place / Location of Incident</label>
                        <input type="text" name="incident_location">
                    </div>
                    <div class="form-group">
                        <label>Type of Incident</label>
                        <select name="type_of_incident">
                            <option value="">Select type</option>
                            <option value="Property Dispute">Property Dispute</option>
                            <option value="Civil Dispute">Civil Dispute</option>
                            <option value="Public Disturbance">Public Disturbance</option>
                            <option value="Physical Injury">Physical Injury</option>
                            <option value="Theft">Theft</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 4. Nature of Incident / Narrative -->
            <div class="blotter-box">
                <h4>4. Nature of Incident / Narrative</h4>
                <div class="form-group">
                    <label>Detailed statement of facts</label>
                    <textarea name="narrative" rows="4" placeholder="Enter detailed narrative..." required></textarea>
                </div>
            </div>

            <!-- 5. Evidence & Attachment (optional) -->
            <div class="blotter-box">
                <h4>5. Evidence & Attachment (optional)</h4>
                <div class="form-group">
                    <label>Upload documents or photos</label>
                    <input type="file" name="evidence_files[]" multiple accept="image/*,.pdf,.doc,.docx">
                    <small style="color: #666; display: block; margin-top: 5px;">Each attachment must be 1 MB or smaller.</small>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons-group">
                <button type="button" id="saveDraftBtn" class="btn-action btn-draft">Save as Draft</button>
                <button type="button" id="clearFormBtn" class="btn-action btn-clear">Clear Form</button>
                <button type="submit" name="form_action" value="Submit Report" class="btn-action btn-submit-report">Submit Report</button>
            </div>

        </form>
    </div>

    <script>
        const globalOverlay = document.getElementById('globalOverlay');
        let witnessCount = 1;
        let suspectCount = 1;

        function addWitness(nameVal = '', detailsVal = '') {
            const container = document.getElementById('witnessContainer');
            const div = document.createElement('div');
            div.className = 'party-item';
            div.innerHTML = `
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="witnesses[${witnessCount}][name]" value="${nameVal}">
                </div>
                <div class="form-group">
                    <label>Description / Details (optional)</label>
                    <textarea name="witnesses[${witnessCount}][details]" rows="2">${detailsVal}</textarea>
                </div>
            `;
            container.appendChild(div);
            witnessCount++;
        }

        function removeWitness() {
            const container = document.getElementById('witnessContainer');
            if (container.children.length > 1) {
                container.removeChild(container.lastElementChild);
                witnessCount--;
            }
        }

        function addSuspect(nameVal = '', addressVal = '', genderVal = '', ageVal = '', detailsVal = '') {
            const container = document.getElementById('suspectContainer');
            const div = document.createElement('div');
            div.className = 'party-item';
            div.innerHTML = `
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="suspects[${suspectCount}][name]" value="${nameVal}">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="suspects[${suspectCount}][address]" value="${addressVal}">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="suspects[${suspectCount}][gender]">
                            <option value="">Select gender</option>
                            <option value="Male" ${genderVal === 'Male' ? 'selected' : ''}>Male</option>
                            <option value="Female" ${genderVal === 'Female' ? 'selected' : ''}>Female</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="width: 33%;">
                    <label>Age</label>
                    <input type="number" name="suspects[${suspectCount}][age]" value="${ageVal}">
                </div>
                <div class="form-group">
                    <label>Description / Details (optional)</label>
                    <textarea name="suspects[${suspectCount}][details]" rows="2">${detailsVal}</textarea>
                </div>
            `;
            container.appendChild(div);
            suspectCount++;
        }

        function removeSuspect() {
            const container = document.getElementById('suspectContainer');
            if (container.children.length > 1) {
                container.removeChild(container.lastElementChild);
                suspectCount--;
            }
        }

        // Updated Clear Form Button using custom dialog modal instead of browser confirm()
        document.getElementById('clearFormBtn').addEventListener('click', function() {
            showConfirmDialog('Are you sure you want to clear dynamic fields and incident details?', function(confirmed) {
                if (confirmed) {
                    document.querySelectorAll('#blotterForm input:not([readonly]), #blotterForm textarea:not([readonly]), #blotterForm select:not([readonly])').forEach(el => el.value = '');
                    localStorage.removeItem('blotter_draft');
                }
            });
        });

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

        window.onclick = function(event) {
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
                globalOverlay.style.display = 'none';
            }
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

        window.addEventListener('click', function(e) {
            document.querySelectorAll('.msg-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            const customModal = document.getElementById('customDialogModal');
            if (e.target === customModal) {
                customModal.style.display = 'none';
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
            document.getElementById('customDialogModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        // 2-Button Confirmation Dialog Box Function
        function showConfirmDialog(message, onConfirm) {
            document.getElementById('customDialogMessage').innerText = message;
            const dialogModal = document.getElementById('customDialogModal');
            dialogModal.style.display = 'flex';
            globalOverlay.style.display = 'block';

            const footer = dialogModal.querySelector('.modal-footer');
            footer.innerHTML = `
                <button type="button" class="btn-modal-cancel" id="customDialogCancelBtn">Cancel</button>
                <button type="button" class="btn-modal-action" id="customDialogOkBtn">OK</button>
            `;

            document.getElementById('customDialogOkBtn').onclick = function() {
                dialogModal.style.display = 'none';
                globalOverlay.style.display = 'none';
                onConfirm(true);
            };

            document.getElementById('customDialogCancelBtn').onclick = function() {
                dialogModal.style.display = 'none';
                globalOverlay.style.display = 'none';
                onConfirm(false);
            };
        }

        // Single-Button Notice Dialog Box Function
        function showCustomDialog(message, redirectUrl = null) {
            document.getElementById('customDialogMessage').innerText = message;
            const dialogModal = document.getElementById('customDialogModal');
            dialogModal.style.display = 'flex';
            globalOverlay.style.display = 'block';

            const footer = dialogModal.querySelector('.modal-footer');
            footer.innerHTML = `
                <button type="button" class="btn-modal-action" id="customDialogOkBtn">OK</button>
            `;

            document.getElementById('customDialogOkBtn').onclick = function() {
                dialogModal.style.display = 'none';
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                } else {
                    globalOverlay.style.display = 'none';
                }
            };
        }

        // Check and prompt to restore saved draft on page load
        window.addEventListener('DOMContentLoaded', () => {
            const savedDraft = localStorage.getItem('blotter_draft');
            if (savedDraft) {
                showConfirmDialog("A saved draft was found in your browser. Would you like to restore it?", function(confirmed) {
                    if (confirmed) {
                        const formData = JSON.parse(savedDraft);
                        for (const key in formData) {
                            const element = document.querySelector(`[name="${key}"]`);
                            if (element && !element.hasAttribute('readonly')) {
                                element.value = formData[key];
                            }
                        }

                        if (formData.witnesses && Array.isArray(formData.witnesses)) {
                            formData.witnesses.forEach((w, index) => {
                                if (index === 0) {
                                    const nameEl = document.querySelector('[name="witnesses[0][name]"]');
                                    const detailsEl = document.querySelector('[name="witnesses[0][details]"]');
                                    if (nameEl) nameEl.value = w.name || '';
                                    if (detailsEl) detailsEl.value = w.details || '';
                                } else {
                                    addWitness(w.name || '', w.details || '');
                                }
                            });
                        }

                        if (formData.suspects && Array.isArray(formData.suspects)) {
                            formData.suspects.forEach((s, index) => {
                                if (index === 0) {
                                    const nameEl = document.querySelector('[name="suspects[0][name]"]');
                                    const addrEl = document.querySelector('[name="suspects[0][address]"]');
                                    const genEl = document.querySelector('[name="suspects[0][gender]"]');
                                    const ageEl = document.querySelector('[name="suspects[0][age]"]');
                                    const detEl = document.querySelector('[name="suspects[0][details]"]');
                                    
                                    if (nameEl) nameEl.value = s.name || '';
                                    if (addrEl) addrEl.value = s.address || '';
                                    if (genEl) genEl.value = s.gender || '';
                                    if (ageEl) ageEl.value = s.age || '';
                                    if (detEl) detEl.value = s.details || '';
                                } else {
                                    addSuspect(s.name || '', s.address || '', s.gender || '', s.age || '', s.details || '');
                                }
                            });
                        }
                    }
                });
            }
        });

        // Save Draft Button Click Handler
        document.getElementById('saveDraftBtn').addEventListener('click', function() {
            showConfirmDialog("Are you sure you want to save this report as a draft?", function(confirmed) {
                if (confirmed) {
                    const formElement = document.getElementById('blotterForm');
                    const formDataObj = {};
                    const data = new FormData(formElement);
                    
                    for (let [key, value] of data.entries()) {
                        if (!key.startsWith('witnesses') && !key.startsWith('suspects') && !key.startsWith('evidence_files')) {
                            formDataObj[key] = value;
                        }
                    }

                    formDataObj.witnesses = [];
                    document.querySelectorAll('#witnessContainer .party-item').forEach((item, idx) => {
                        formDataObj.witnesses.push({
                            name: item.querySelector(`[name="witnesses[${idx}][name]"]`)?.value || '',
                            details: item.querySelector(`[name="witnesses[${idx}][details]"]`)?.value || ''
                        });
                    });

                    formDataObj.suspects = [];
                    document.querySelectorAll('#suspectContainer .party-item').forEach((item, idx) => {
                        formDataObj.suspects.push({
                            name: item.querySelector(`[name="suspects[${idx}][name]"]`)?.value || '',
                            address: item.querySelector(`[name="suspects[${idx}][address]"]`)?.value || '',
                            gender: item.querySelector(`[name="suspects[${idx}][gender]"]`)?.value || '',
                            age: item.querySelector(`[name="suspects[${idx}][age]"]`)?.value || '',
                            details: item.querySelector(`[name="suspects[${idx}][details]"]`)?.value || ''
                        });
                    });

                    formDataObj.saved_at = new Date().toLocaleString();
                    localStorage.setItem('blotter_draft', JSON.stringify(formDataObj));
                    
                    showCustomDialog('Draft saved successfully! Would you like to go to your Dashboard Overview?', 'dashboard_overview.php');
                }
            });
        });

        // Submit Report Form Handler
        document.getElementById('blotterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitter = e.submitter ? e.submitter.value : 'Submit Report';
            const formData = new FormData(this);
            formData.append('submit_complaint', '1');
            formData.append('form_action', submitter);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    localStorage.removeItem('blotter_draft'); 
                    showCustomDialog(data.message, 'dashboard_overview.php');
                } else {
                    showCustomDialog(data.message || 'Error processing request.');
                }
            })
            .catch(err => {
                console.error(err);
                showCustomDialog('A network error occurred.');
            });
        });
    </script>
</body>
</html>
<?php 
if (isset($conn)) { $conn->close(); }
?>