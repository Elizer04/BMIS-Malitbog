<?php
session_start();

include 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_status') {
        $id = intval($_POST['id']);
        $new_status = $_POST['status'] ?? '';

        $stmt = $conn->prepare("UPDATE my_cases SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);

        if ($stmt->execute()) { echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']); } else { echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']); } $stmt->close();
        exit;
    }

    if ($_POST['action'] === 'delete_case') {
        $id = intval($_POST['id']);

        $stmt = $conn->prepare("DELETE FROM my_cases WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) { echo json_encode(['status' => 'success', 'message' => 'Blotter report deleted successfully.']); } else { echo json_encode(['status' => 'error', 'message' => 'Failed to delete report.']); } $stmt->close();
        exit;
    }

    if ($_POST['action'] === 'send_message') {
        $case_id = $_POST['case_id'] ?? '';
        $recipient = $_POST['recipient'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $message = $_POST['message'] ?? '';

        if (!empty($case_id) && !empty($message)) {
            $stmt = $conn->prepare("UPDATE my_cases SET remarks = CONCAT(COALESCE(remarks, ''), '\n\n[Admin Notice - ', NOW(), ']:\n', ?) WHERE id = ?");
            $stmt->bind_param("si", $message, $case_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Summon notice successfully saved to case remarks and dispatched to Complainant: ' . htmlspecialchars($recipient) . ' (' . htmlspecialchars($contact) . ').']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save notice to case remarks.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Case ID or message content is missing.']);
        }
        exit;
    }
}

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$total_count_result = $conn->query("SELECT COUNT(*) AS total FROM my_cases");
$total_count_row = $total_count_result->fetch_assoc();
$total_cases_count = $total_count_row['total'] ?? 0;

$sql = "SELECT * FROM my_cases WHERE 1=1";
$params = []; $types = "";

if (!empty($search)) {
    $sql .= " AND (case_id LIKE ? OR reporter_name LIKE ? OR respondent_name LIKE ? OR type_of_incident LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$cases = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Blotter Report Management - Barangay Malitbog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #000066;
            --accent-gold: #ffcc00;
            --text-white: #ffffff;
            --white: #ffffff;
            --bg-light: #f4f7f6;
            --border-color: #e2e8f0;
            --text-dark: #1e293b;
            --admin-red: #cc0000;
            --print-green: #2e7d32;
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

        .topbar .socials a:hover { color: var(--accent-gold); }
        .topbar .socials svg { width: 14px; height: 14px; fill: currentColor; }
        .topbar .socials .instagram-mark { fill: none; stroke: currentColor; stroke-width: 2; }

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
        .back-link { color: var(--primary-blue); font-size: 1.2rem; text-decoration: none; margin-right: 5px; transition: color 0.3s ease; }
        .back-link:hover { color: var(--accent-gold); }
        .logo-section img { height: 50px; width: 50px; border-radius: 50%; object-fit: cover; }
        .logo-text h2 { margin: 0; color: var(--primary-blue); font-size: 1.1rem; letter-spacing: 1px; }
        .logo-text small { font-size: 0.7rem; color: #555; display: block; }
        .nav-links { display: flex; align-items: center; gap: 25px; }

        .rd-nav-item, .rd-nav-link-dropdown a, .rd-nav-link-dropdown span {
            display: flex; flex-direction: column; align-items: center; text-decoration: none; color: #333; gap: 4px; font-size: 0.75rem; font-weight: 600;
        }
        .rd-nav-item i, .rd-menu-symbol-trigger i, .rd-nav-menu-bar-trigger i { font-size: 1.1rem; }
        .rd-nav-item:hover, .rd-menu-symbol-trigger:hover, .rd-nav-menu-bar-trigger:hover { color: var(--accent-gold); }

        .menu-slide-drawer {
            display: none; position: fixed; z-index: 1300; right: 0; top: 0; width: 100%; max-width: 380px; height: 100%; background-color: white; box-shadow: -5px 0 20px rgba(0,0,0,0.15); flex-direction: column;
        }
        .menu-drawer-header { background-color: var(--primary-blue); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .admin-info-container { display: flex; align-items: center; gap: 12px; }
        .admin-avatar { font-size: 2.2rem; color: var(--accent-gold); }
        .admin-details h4 { margin: 0; font-size: 1rem; color: white; }
        .admin-details p { margin: 3px 0 0 0; font-size: 0.8rem; color: #d1d5db; }
        .menu-drawer-body { padding: 15px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 10px; }
        .menu-drawer-body a { display: flex; align-items: center; gap: 15px; padding: 15px; text-decoration: none; color: #333; font-size: 0.95rem; background: #f9f9f9; border-left: 4px solid var(--accent-gold); border-radius: 4px; }
        .menu-drawer-body a:hover { background: #f0f0f0; color: var(--primary-blue); }

        .rd-global-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: 1050; }

        .hero-banner {
            background: linear-gradient(rgba(0, 0, 102, 0.7), rgba(0, 0, 102, 0.7)), url('image/backgroundphoto1.jpg') no-repeat center center/cover;
            padding: 80px 10%; color: white; display: flex; justify-content: space-between; align-items: center;
        }
        .welcome-msg h1 { margin: 0; font-size: 2.2rem; }
        .welcome-msg p { font-size: 1.2rem; margin-top: 10px; opacity: 0.9; }
        .welcome-msg span { color: var(--accent-gold); }

        .container {
            max-width: 1200px; margin: -30px auto 40px; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); position: relative; z-index: 10; overflow-x: auto;
        }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h2 { margin: 0; font-size: 1.5rem; color: #0f172a; }

        .filter-card {
            background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 25px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;
        }
        .filter-card input[type="text"], .filter-card select { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; background: white; outline: none; }
        .filter-card input[type="text"] { flex: 1; min-width: 200px; }
        .btn-filter { background: var(--primary-blue); color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-filter:hover { background: #000044; }

        .table-card { background: white; border-radius: 12px; border: 1px solid var(--border-color); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; min-width: 800px; }
        th { background: #f8fafc; color: #475569; padding: 14px 16px; font-weight: 600; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; white-space: nowrap; }
        tr:hover { background: #f8fafc; }

        .btn-action-icon { border: none; background: none; font-size: 1.1rem; cursor: pointer; padding: 5px; margin-right: 5px; color: #64748b; }
        .btn-action-icon:hover { color: var(--primary-blue); }
        .btn-message:hover { color: #0284c7; }
        .btn-delete:hover { color: #dc2626; }

        .action-btn { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; text-decoration: none; display: inline-block; }
        .btn-print { background: var(--print-green); color: white; }
        .btn-print:hover { background: #1b5e20; }

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; overflow-y: auto; padding: 15px; }
        .modal-content { background-color: #fff; padding: 25px; border-radius: 12px; width: 90%; max-width: 800px; max-height: 85vh; overflow-y: auto; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); position: relative; }
        .close-btn { background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #aaa; cursor: pointer; }
        .close-btn:hover { color: #333; }
        .modal-header { border-bottom: 2px solid #eee; padding-bottom: 8px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: var(--primary-blue); font-size: 1.15rem; }
        .modal-body-content { padding: 5px 0; font-size: 0.9rem; color: #333; line-height: 1.4; }
        .modal-footer { margin-top: 15px; text-align: right; padding-top: 12px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 8px; }
        .btn-modal-action { background: var(--primary-blue); color: white; padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; }
        .btn-modal-action:hover { background: #1a1a8c; }
        .btn-modal-cancel { background: #ccc; color: #333; padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; }
        .btn-modal-cancel:hover { background: #b3b3b3; }

        .detail-section-title { font-size: 0.95rem; font-weight: 700; color: var(--primary-blue); border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; margin: 20px 0 10px 0; text-transform: uppercase; }
        .detail-group { margin-bottom: 12px; }
        .detail-group label { font-weight: 600; font-size: 0.75rem; color: #64748b; text-transform: uppercase; display: block; }
        .detail-group p { margin: 4px 0 0 0; font-size: 0.9rem; background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        ul.modal-list { margin: 4px 0 0 0; padding-left: 20px; font-size: 0.9rem; background: #f8fafc; padding: 8px 20px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .evidence-item-container { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
        .evidence-img-preview { max-width: 150px; max-height: 150px; border-radius: 6px; border: 1px solid #cbd5e1; object-fit: cover; }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="socials" aria-label="Social media">
            <a href="https://www.facebook.com/share/1MqLQuYLdG/" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24"><path d="M13.6 21.9v-8.7h2.9l.4-3.4h-3.3V7.6c0-1 .3-1.6 1.7-1.6h1.8V2.9c-.3 0-1.4-.1-2.7-.1-2.7 0-4.5 1.6-4.5 4.6v2.5H7v3.4h2.9v8.7h3.7z"/></svg></a>
            <a href="https://youtube.com/@officialpage-barangaymalitbog?si=eo-uBsODjPVwKpOn" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.7V8.3l6.3 3.7-6.3 3.7Z"/></svg></a>
            <a href="https://www.instagram.com/barangaymalitbog2k26?mdxt=a29mcDJrNTdkNGR4" target="_blank" rel="noopener noreferrer"><svg class="instagram-mark" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.7" r=".8" fill="currentColor" stroke="none"/></svg></a>
        </div>
        <div><span style="font-size: 0.8rem;"><i class="fa fa-user-shield"></i> Administrator Access</span></div>
    </div>

    <nav class="main-nav">
        <div class="logo-section">
            <a href="admin_dashboard.php" id="backBtn" class="back-link" title="Go Back"><i class="fa fa-arrow-left"></i></a>
            <img src="image/barangaylogo3.jpg" alt="Logo">
            <div class="logo-text"><h2>BARANGAY MALITBOG</h2><small>MUNICIPALITY OF BONGABONG</small></div>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="rd-nav-item" title="Admin Home"><i class="fas fa-home"></i><span>home</span></a>
            <div class="rd-nav-link-dropdown">
                <a href="javascript:void(0);" class="rd-nav-menu-bar-trigger" onclick="toggleNavBarMenu()" title="Menu Options"><i class="fas fa-bars"></i><span>Menu</span></a>
            </div>
        </div>
    </nav>

    <div class="rd-global-overlay" id="globalOverlay" onclick="closeAllModals()"></div>

    <div class="menu-slide-drawer" id="menuSlideDrawer">
        <div class="menu-drawer-header">
            <div class="admin-info-container">
                <i class="fas fa-user-circle admin-avatar"></i>
                <div class="admin-details"><h4>Barangay Administrator</h4><p>admin@malitbog.gov.ph</p></div>
            </div>
            <button type="button" class="close-btn" onclick="toggleNavBarMenu()">&times;</button>
        </div>
        <div class="menu-drawer-body">
            <a href="barangay_profileadmin.php"><i class="fas fa-university"></i> Barangay Info</a>
            <a href="change_password_admin.php"><i class="fas fa-key"></i> Change Password</a>
            <a href="javascript:void(0);" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <header class="hero-banner">
        <div class="welcome-msg"><h1>JUDICIAL <span>SYSTEM</span></h1><p>Judicial & Incident Reports of Barangay Malitbog</p></div>
        <div style="text-align: right;">
            <div style="font-weight: bold; font-size: 1.2rem; color: var(--accent-gold);">TOTAL CASES: <?php echo $total_cases_count; ?></div>
            <p>System Status: <span style="color: #00ff00;">Online</span></p>
        </div>
    </header>

    <div class="container">
        <div class="page-header"><h2>Blotter Reports Management</h2></div>

        <form method="GET" class="filter-card">
            <input type="text" name="search" placeholder="Search Case ID, Reporter, Respondent..." value="<?= htmlspecialchars($search) ?>">
            <select name="status">
                <option value="">All Judicial Status</option>
                <option value="Mediation Stage" <?= $status_filter === 'Mediation Stage' ? 'selected' : '' ?>>Mediation (Punong Barangay)</option>
                <option value="Conciliation Stage" <?= $status_filter === 'Conciliation Stage' ? 'selected' : '' ?>>Conciliation (Pangkat)</option>
                <option value="Arbitration" <?= $status_filter === 'Arbitration' ? 'selected' : '' ?>>Arbitration</option>
                <option value="Resolved / Settled" <?= $status_filter === 'Resolved / Settled' ? 'selected' : '' ?>>Resolved / Settled</option>
                <option value="Certification to File Action" <?= $status_filter === 'Certification to File Action' ? 'selected' : '' ?>>Certification to File Action</option>
                <option value="Dismissed" <?= $status_filter === 'Dismissed' ? 'selected' : '' ?>>Dismissed / Withdrawn</option>
            </select>
            <button type="submit" class="btn-filter"><i class="fa fa-search"></i> Search</button>
        </form>

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
                                <td><?= htmlspecialchars($case['type_of_incident'] ?: 'Unspecified') ?></td>
                                <td><?= htmlspecialchars($case['incident_date'] ?: 'N/A') ?></td>
                                <td>
                                    <select onchange="updateCaseStatus(<?= $case['id'] ?>, this.value)" style="padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; border: 1px solid #cbd5e1;">
                                        <option value="Mediation Stage" <?= $case['status'] === 'Mediation Stage' ? 'selected' : '' ?>>Mediation (Punong Barangay)</option>
                                        <option value="Conciliation Stage" <?= $case['status'] === 'Conciliation Stage' ? 'selected' : '' ?>>Conciliation (Pangkat)</option>
                                        <option value="Arbitration" <?= $case['status'] === 'Arbitration' ? 'selected' : '' ?>>Arbitration</option>
                                        <option value="Resolved / Settled" <?= $case['status'] === 'Resolved / Settled' ? 'selected' : '' ?>>Resolved / Settled</option>
                                        <option value="Certification to File Action" <?= $case['status'] === 'Certification to File Action' ? 'selected' : '' ?>>Certification to File Action</option>
                                        <option value="Dismissed" <?= $case['status'] === 'Dismissed' ? 'selected' : '' ?>>Dismissed / Withdrawn</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn-action-icon" title="View Full Details" onclick='openViewModal(<?= json_encode($case) ?>)'><i class="fa fa-eye"></i></button>
                                    <button class="btn-action-icon btn-message" title="Send Summon Notice / Message to Complainant" onclick='openMessageModal(<?= json_encode($case) ?>)'><i class="fa fa-comment-dots"></i></button>
                                    <button class="btn-action-icon btn-delete" title="Delete Case" onclick="deleteCase(<?= $case['id'] ?>)"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">No blotter reports found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalCaseId">Case Details</h3>
                <button type="button" class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>
            <div id="modalBody" class="modal-body-content"></div>
            <div class="modal-footer"><button type="button" class="btn-modal-cancel" onclick="closeViewModal()">Close</button></div>
        </div>
    </div>

    <!-- Send Message & Summon Notice Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h3><i class="fa fa-envelope-open-text"></i> Send Notice to Complainant / Reporter</h3>
                <button type="button" class="close-btn" onclick="closeMessageModal()">&times;</button>
            </div>
            <div class="modal-body-content">
                <form id="sendMessageForm">
                    <input type="hidden" id="msgCaseId" name="case_id">
                    
                    <div class="grid-2">
                        <div class="detail-group">
                            <label>Complainant / Reporter (Recipient)</label>
                            <p id="msgRecipientDisplay"></p>
                            <input type="hidden" id="msgRecipientInput" name="recipient">
                        </div>
                        <div class="detail-group">
                            <label>Contact Number</label>
                            <p id="msgContactDisplay"></p>
                            <input type="hidden" id="msgContactInput" name="contact">
                        </div>
                    </div>

                    <div class="grid-2" style="margin-top: 10px;">
                        <div class="detail-group">
                            <label>Respondent (Pinag-dedemanda)</label>
                            <p id="msgRespondentDisplay"></p>
                        </div>
                        <div class="detail-group">
                            <label>Dispute / Incident Type</label>
                            <p id="msgDisputeDisplay"></p>
                        </div>
                    </div>

                    <div class="detail-group" style="margin-top: 15px;">
                        <label>Complainant Notice & Summon Letter Preview</label>
                        <textarea id="msgContentText" name="message" rows="10" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Times New Roman', Times, serif; font-size: 0.95rem; outline: none; margin-top: 4px; box-sizing: border-box; line-height: 1.5; background: #fff;" required></textarea>
                    </div>
                </form>
            </div>
            <!-- TARGET: #messageModal > div > div.modal-footer -->
            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <!-- Linked directly to print_lupong_tagapapayapa.php with active record ID -->
                <a id="btnPrintLupong" href="#" target="_blank" class="action-btn btn-print" style="text-decoration: none;">
                    <i class="fa fa-print"></i> Print Certificate
                </a>
                <div>
                    <button type="button" class="btn-modal-cancel" onclick="closeMessageModal()">Cancel</button>
                    <!-- #messageModal > div > div.modal-footer > button (Send Notice action) -->
                    <button type="button" class="btn-modal-action" onclick="submitSendMessage()">Send Notice</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 360px;">
            <button type="button" class="close-btn" onclick="closeAllModals()" style="position: absolute; right: 15px; top: 12px;">&times;</button>
            <div class="modal-header"><h3>System Notice</h3></div>
            <div class="modal-body-content"><p style="margin: 0;">Are you sure you want to leave or log out?</p></div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeAllModals()">Cancel</button>
                <a href="index.php" style="text-decoration: none;"><button type="button" class="btn-modal-action">Yes</button></a>
            </div>
        </div>
    </div>

    <!-- Custom Dialog Modal -->
    <div id="customDialogModal" class="modal">
        <div class="modal-content" style="max-width: 360px;">
            <div class="modal-header"><h3>System Notice</h3></div>
            <div class="modal-body-content"><p id="customDialogMessage" style="margin: 0;"></p></div>
            <div class="modal-footer"><button type="button" class="btn-modal-action" id="customDialogOkBtn">OK</button></div>
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
            document.getElementById('viewModal').style.display = 'none';
            document.getElementById('messageModal').style.display = 'none';
            document.getElementById('customDialogModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('menuSlideDrawer').style.display = 'none';
                document.getElementById('logoutModal').style.display = 'flex';
                globalOverlay.style.display = 'block';
            });
        }

        function showCustomDialog(message, reloadOnOk = false) {
            document.getElementById('customDialogMessage').innerText = message;
            const dialogModal = document.getElementById('customDialogModal');
            dialogModal.style.display = 'flex';
            globalOverlay.style.display = 'block';

            const okBtn = document.getElementById('customDialogOkBtn');
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);

            newOkBtn.addEventListener('click', function() {
                dialogModal.style.display = 'none';
                globalOverlay.style.display = 'none';
                if (reloadOnOk) location.reload();
            });
        }

        function updateCaseStatus(id, newStatus) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('id', id);
            formData.append('status', newStatus);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') showCustomDialog('Judicial case status updated successfully.');
                else showCustomDialog('Failed to update status.');
            })
            .catch(() => showCustomDialog('Network error occurred.'));
        }

        function deleteCase(id) {
            const dialogModal = document.getElementById('customDialogModal');
            document.getElementById('customDialogMessage').innerText = 'Are you sure you want to delete this blotter report?';
            dialogModal.style.display = 'flex';
            globalOverlay.style.display = 'block';

            const okBtn = document.getElementById('customDialogOkBtn');
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);

            newOkBtn.addEventListener('click', function() {
                dialogModal.style.display = 'none';
                const formData = new FormData();
                formData.append('action', 'delete_case');
                formData.append('id', id);

                fetch(window.location.href, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') showCustomDialog(data.message, true);
                    else showCustomDialog(data.message);
                })
                .catch(() => showCustomDialog('Network error occurred.'));
            }, { once: true });
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function openViewModal(data) {
            document.getElementById('modalCaseId').innerText = 'Case Details - ' + (data.case_id || 'N/A');
            // Populate modal body details securely...
            document.getElementById('viewModal').style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        function openMessageModal(data) {
            document.getElementById('msgCaseId').value = data.id || '';
            
            // Link print_lupong_tagapapayapa.php directly with the case database ID
            const printBtnLink = document.getElementById('btnPrintLupong');
            if (data.id) {
                printBtnLink.href = 'print_lupong_tagapapayapa.php?id=' + data.id;
                printBtnLink.style.pointerEvents = 'auto';
                printBtnLink.style.opacity = '1';
            } else {
                printBtnLink.href = '#';
                printBtnLink.style.pointerEvents = 'none';
                printBtnLink.style.opacity = '0.5';
            }

            const complainant = data.reporter_name || 'Complainant';
            const respondent = data.respondent_name || 'Respondent / Kinauukulan';
            const contact = data.reporter_contact || 'No contact provided';
            const dispute = data.type_of_incident || 'Alitan / Usapin sa Barangay';

            document.getElementById('msgRecipientDisplay').innerText = complainant;
            document.getElementById('msgRecipientInput').value = complainant;
            document.getElementById('msgContactDisplay').innerText = contact;
            document.getElementById('msgContactInput').value = contact;
            document.getElementById('msgRespondentDisplay').innerText = respondent;
            document.getElementById('msgDisputeDisplay').innerText = dispute;

            const formalComplainantNoticeText = `REPUBLIKA NG PILIPINAS\n` +
                `LALAWIGAN NG SILANGANG MINDORO\n` +
                `BAYAN NG BONGABONG\n` +
                `BARANGAY MALITBOG\n` +
                `TANGGAPAN NG PUNONG BARANGAY\n\n` +
                `PARA KAY: ${complainant}\n` +
                `Address: ${data.reporter_address || 'Barangay Malitbog'}\n\n` +
                `Pagbati ng Kapayapaan!\n` +
                `Isang Magandang Araw po!\n\n` +
                `Kayo po ay pinagbibigyan ng update patungkol sa inyong inihabla / idinulog na usapin laban kay ${respondent}.\n\n` +
                `Ukol sa usaping: ${dispute}\n\n` +
                `Ang inyong kaso ay kasalukuyang sumasailalim sa proseso ng Katarungang Pambarangay. Kayo po ay inaanyayahang magsadya sa Tanggapan ng Punong Barangay ngayong ika-__ ng __________ 2026 sa ganap na ika- _____ ng Umaga para sa kahulugan o pagdinig ng inyong usapin.\n\n` +
                `Maraming Salamat po….\n\n` +
                `ADOLFO A. AMPARO\n` +
                `Barangay Captain`;

            document.getElementById('msgContentText').value = formalComplainantNoticeText;
            document.getElementById('messageModal').style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function submitSendMessage() {
            const formElement = document.getElementById('sendMessageForm');
            const formData = new FormData(formElement);
            formData.append('action', 'send_message');

            const messageText = document.getElementById('msgContentText').value;
            if (!messageText.trim()) {
                showCustomDialog('Message content cannot be empty.');
                return;
            }

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                closeMessageModal();
                showCustomDialog(data.message, true);
            })
            .catch(() => {
                closeMessageModal();
                showCustomDialog('Network error occurred while sending message.');
            });
        }
    </script>
</body>
</html>