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

// Fetch user details from the database including email and contact info
$stmt = $conn->prepare("SELECT full_name, contact_no, email FROM residents WHERE resident_id = ?");
$stmt->bind_param("s", $resident_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $full_name = $row['full_name'];
    $contact_no = $row['contact_no'];
    $email = $row['email'];
} else {
    $full_name = "";
    $contact_no = "";
    $email = "";
}
$stmt->close();

if (empty($resident_email) && !empty($email)) {
    $resident_email = $email;
}

// Fetch all messages linked strictly to this resident's ID or email across all tables (all 14 certs + residents, appointments, cases)
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
        SELECT 'good_moral' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM certificate_of_good_moral WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'unemployment' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM certificate_of_unemployment WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'business_closure' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM certificate_of_business_closure WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'others' COLLATE utf8mb4_unicode_ci AS src, claim_message COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM other_certifications WHERE (resident_id = ? OR email = ?) AND claim_message IS NOT NULL AND claim_message != ''
        UNION ALL
        SELECT 'resident' COLLATE utf8mb4_unicode_ci AS src, remarks COLLATE utf8mb4_unicode_ci AS remarks, 'General' COLLATE utf8mb4_unicode_ci AS status, created_at FROM residents WHERE (resident_id = ? OR email = ?) AND remarks IS NOT NULL AND remarks != ''
        UNION ALL
        SELECT 'health_remarks' COLLATE utf8mb4_unicode_ci AS src, health_remarks COLLATE utf8mb4_unicode_ci AS remarks, 'Health Note' COLLATE utf8mb4_unicode_ci AS status, created_at FROM residents WHERE (resident_id = ? OR email = ?) AND health_remarks IS NOT NULL AND health_remarks != ''
        UNION ALL
        SELECT 'appointment' COLLATE utf8mb4_unicode_ci AS src, remarks COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM appointments WHERE resident_id = ? AND remarks IS NOT NULL AND remarks != ''
        UNION ALL
        SELECT 'judicial' COLLATE utf8mb4_unicode_ci AS src, remarks COLLATE utf8mb4_unicode_ci AS remarks, status COLLATE utf8mb4_unicode_ci AS status, created_at FROM my_cases WHERE resident_id = ? AND remarks IS NOT NULL AND remarks != ''
        ORDER BY created_at DESC
    ";
    
    if ($stmt_msg = $conn->prepare($msg_query)) {
        $stmt_msg->bind_param("ssssssssssssssssssssssssssssssssss", 
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
            $resident_id, $resident_email, 
            $resident_id, 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Certification - Barangay Malitbog[cite: 1]</title>
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

    /* --- Applied Message Drawer Body Styles from Resident Dashboard --- */
    #messageDrawer > div.message-body {
        max-height: calc(85vh - 70px);
        overflow-y: auto;
        padding: 15px;
        box-sizing: border-box;
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

    <!-- Navigation Bar -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="certification_overview.php" class="rd-back-link" title="Back to Dashboard">
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

    <!-- Message Drawer Modal[cite: 1] -->
    <div class="message-modal" id="messageDrawer">
        <div class="message-header">
            <h3><i class="fas fa-comments"></i>  Messages</h3>
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

    <div id="resultModal" class="modal">
        <div class="modal-content">
            <span class="modal-close-btn" onclick="closeModal('resultModal')">&times;</span>
            <div class="modal-header-custom">
                <h3 id="modalTitle">Application Notice</h3>
            </div>
            
            <div class="modal-body-content">
                <p id="modalMessageBody" style="margin: 0;">Processing your request...</p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modal-action" id="modalActionBtn" onclick="closeModal('resultModal')">OK</button>
            </div>
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

    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>DOCUMENT <span>REQUEST</span></h1>
            <p>BARANGAY MALITBOG OFFICIAL SERVICES</p>
        </div>
    </header>

    <div class="container">
        <div class="form-header">
            <h2>Certification Application</h2>
        </div>

        <form id="certificationForm" action="process_request.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="resident_id" value="<?php echo htmlspecialchars($resident_id); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

            <div class="form-section-title"><i class="fa fa-id-card"></i> Personal Info</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" readonly required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_no" value="<?php echo htmlspecialchars($contact_no); ?>" readonly required>
                </div>
            </div>

            <div class="form-section-title"><i class="fa fa-file-alt"></i> Request Details</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Document Type</label>
                    <select name="cert_type" id="cert_type" onchange="updateFee()" required>
                        <option value="">Select Document Type</option>
                        <option value="indigency">Barangay Indigency</option>
                        <option value="clearance">Barangay Clearance</option>
                        <option value="residency">Certificate of Residency</option>
                        <option value="business_cert">Business Certification</option>
                        <option value="non_tenancy">Certification of Non-Tenancy</option>
                        <option value="unemployment">Certificate of Unemployment</option>
                        <option value="business_closure">Certificate of Business Closure</option>
                        <option value="good_moral">Certificate of Good Moral Character</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Processing Fee</label>
                    <input type="text" name="fee" id="fee_display" class="fee-highlight" value="₱0.00" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Purpose Category</label>
                    <select name="purpose_cat" required>
                        <option value="Employment">Employment</option>
                        <option value="Scholarship">Scholarship</option>
                        <option value="ID">Government ID</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Detailed Purpose</label>
                <textarea name="purpose_detail" rows="2" placeholder="Reason for request..." required></textarea>
            </div>

            <div class="form-section-title"><i class="fa fa-upload"></i> Supporting Document</div>
            <div class="form-group">
                <div class="file-upload-box">
                    <input type="file" name="requirement_file" accept="image/*,.pdf">
                    <p style="font-size: 0.75rem; color: #777; margin-top: 5px;">Upload Valid ID (JPG, PNG, PDF)</p>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">Submit Application</button>
        </form>
    </div>

    <script>
        function updateFee() {
            const certType = document.getElementById('cert_type').value;
            const feeInput = document.getElementById('fee_display');
            const feeMap = {
                'indigency': '₱0.00 (Free)',
                'clearance': '₱50.00',
                'residency': '₱50.00',
                'business_cert': '₱100.00',
                'non_tenancy': '₱50.00',
                'unemployment': '₱50.00',
                'business_closure': '₱50.00',
                'good_moral': '₱50.00'
            };
            feeInput.value = feeMap[certType] || '₱0.00';

            const targetLabel = document.querySelector('#certificationForm > div:nth-child(8) > label');
            if (targetLabel) {
                if (certType === 'others') {
                    targetLabel.textContent = 'Please specify';
                } else {
                    targetLabel.textContent = 'Detailed Purpose';
                }
            }
        }

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
            const resultModal = document.getElementById('resultModal');
            if (e.target === resultModal) {
                resultModal.style.display = 'none';
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
            document.getElementById('resultModal').style.display = 'none';
            document.getElementById('logoutModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        // Handle Certification Form Submission via AJAX & Result Modal Display[cite: 1]
        const certificationForm = document.getElementById('certificationForm');
        const submitBtn = document.getElementById('submitBtn');
        const resultModal = document.getElementById('resultModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessageBody = document.getElementById('modalMessageBody');
        const modalActionBtn = document.getElementById('modalActionBtn');

        certificationForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(certificationForm);
            submitBtn.disabled = true;
            submitBtn.textContent = "Submitting Application...";

            const startTime = Date.now();

            fetch('process_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                const elapsedTime = Date.now() - startTime;
                const minDisplayTime = 500; 
                const delay = elapsedTime < minDisplayTime ? minDisplayTime - elapsedTime : 0;

                setTimeout(() => {
                    let serverMsg = data.trim();

                    if (serverMsg.toLowerCase().includes('success') || serverMsg === '1') {
                        modalTitle.textContent = "Success: Application Submitted";
                        modalMessageBody.textContent = "Your certification application has been successfully submitted! Redirecting to certification overview...";
                        certificationForm.reset(); 
                        document.getElementById('fee_display').value = "₱0.00";

                        modalActionBtn.onclick = function() {
                            window.location.href = 'certification_overview.php';
                        };

                        setTimeout(() => {
                            window.location.href = 'certification_overview.php';
                        }, 1500);

                    } else {
                        modalTitle.textContent = "Notice: Submission Issue";
                        
                        modalActionBtn.onclick = function() {
                            closeModal('resultModal');
                        };
                        
                        if (serverMsg.toLowerCase().includes('duplicate') || serverMsg.toLowerCase().includes('already') || serverMsg.toLowerCase().includes('exists')) {
                            modalMessageBody.textContent = "You already have a pending or existing request for this document type. Please check your dashboard for updates.";
                        } else {
                            modalMessageBody.textContent = serverMsg || 'Application submission failed. Please check your inputs and try again.';
                        }
                    }

                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Application';
                    resultModal.style.display = 'flex';
                    globalOverlay.style.display = 'block';
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

                    modalActionBtn.onclick = function() {
                        closeModal('resultModal');
                    };

                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Application';
                    resultModal.style.display = 'flex';
                    globalOverlay.style.display = 'block';
                }, delay);
            });
        });
    </script>
</body>
</html>
<?php 
if (isset($conn)) { $conn->close(); }
?>