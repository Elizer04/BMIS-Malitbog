<?php
include 'db_connect.php';

session_start();

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Ensure upload directory exists
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 1. Handle Form Submission for Profile Update (Standard POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $stmt = $pdo->prepare("UPDATE barangay_profile SET 
            barangay_name = ?, 
            municipality = ?,
            established_year = ?, 
          
            total_population = ?, 
            land_area = ?, 
            overview_text = ?, 
            economy_geography_text = ? 
            WHERE user_id = 1");
            
        $stmt->execute([
            $_POST['barangay_name'],
            $_POST['municipality'],
            $_POST['established_year'],
      
            $_POST['total_population'],
            $_POST['land_area'],
            $_POST['overview_text'],
            $_POST['economy_geography_text']
        ]);
        
        $success_msg = "Barangay profile successfully updated!";
    } catch (\PDOException $e) {
        $error_msg = "Error updating profile: " . $e->getMessage();
    }
}

// 2. Handle AJAX Form Submissions for Officials (Update, Add, or Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        if ($action === 'delete_official') {
            $official_id = $_POST['official_id'] ?? 0;
            
            $imgStmt = $pdo->prepare("SELECT photo FROM barangay_officials WHERE id = ?");
            $imgStmt->execute([$official_id]);
            $officialData = $imgStmt->fetch(PDO::FETCH_ASSOC);
            if ($officialData && !empty($officialData['photo']) && file_exists($officialData['photo'])) {
                unlink($officialData['photo']);
            }

            $delStmt = $pdo->prepare("DELETE FROM barangay_officials WHERE id = ?");
            $delStmt->execute([$official_id]);
            echo json_encode(['success' => true, 'message' => 'Official successfully deleted from the database!']);
            exit;
        } 
        elseif ($action === 'update_officials') {
            if (isset($_POST['official_id']) && is_array($_POST['official_id'])) {
                for ($i = 0; $i < count($_POST['official_id']); $i++) {
                    $id = $_POST['official_id'][$i];
                    $name = $_POST['official_name'][$i];
                    $position = $_POST['official_position'][$i];

                    $photo_sql = "";
                    $params = [$name, $position];

                    if (isset($_FILES['official_photo']['name'][$i]) && $_FILES['official_photo']['error'][$i] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['official_photo']['tmp_name'][$i];
                        $fileName = $_FILES['official_photo']['name'][$i];
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = 'official_' . $id . '_' . time() . '.' . $fileExtension;
                            $dest_path = $upload_dir . $newFileName;

                            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                                $oldImgStmt = $pdo->prepare("SELECT photo FROM barangay_officials WHERE id = ?");
                                $oldImgStmt->execute([$id]);
                                $oldData = $oldImgStmt->fetch(PDO::FETCH_ASSOC);
                                if ($oldData && !empty($oldData['photo']) && file_exists($oldData['photo'])) {
                                    unlink($oldData['photo']);
                                }

                                $photo_sql = ", photo = ?";
                                $params[] = $dest_path;
                            }
                        }
                    }

                    $params[] = $id;
                    $stmt = $pdo->prepare("UPDATE barangay_officials SET name = ?, position = ?" . $photo_sql . " WHERE id = ?");
                    $stmt->execute($params);
                }
            }

            if (!empty($_POST['new_official_name']) && !empty($_POST['new_official_position'])) {
                $new_name = $_POST['new_official_name'];
                $new_position = $_POST['new_official_position'];
                $new_photo_path = null;

                if (isset($_FILES['new_official_photo']) && $_FILES['new_official_photo']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['new_official_photo']['tmp_name'];
                    $fileName = $_FILES['new_official_photo']['name'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (in_array($fileExtension, $allowedExtensions)) {
                        $newFileName = 'official_new_' . time() . '.' . $fileExtension;
                        $dest_path = $upload_dir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            $new_photo_path = $dest_path;
                        }
                    }
                }

                $insertStmt = $pdo->prepare("INSERT INTO barangay_officials (name, position, photo) VALUES (?, ?, ?)");
                $insertStmt->execute([$new_name, $new_position, $new_photo_path]);
            }

            echo json_encode(['success' => true, 'message' => 'Barangay officials successfully updated!']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Fetch current profile data using user_id instead of id
$stmt = $pdo->query("SELECT * FROM barangay_profile WHERE user_id = 1");
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallback array if table is completely empty so it doesn't throw warnings
if (!$profile) {
    $profile = [
        'barangay_name' => 'Enter Name',
        'municipality' => 'Enter Municipality',
        'established_year' => '',
       
        'total_population' => '',
        'land_area' => '',
        'overview_text' => '',
        'economy_geography_text' => ''
    ];
}

// Fetch current barangay officials list
$officialsStmt = $pdo->query("SELECT * FROM barangay_officials ORDER BY id ASC");
$officials = $officialsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Profile - Barangay <?php echo htmlspecialchars($profile['barangay_name']); ?>[cite: 1]</title>
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
            --teal-green: #157347;   
            --deep-red: #dc3545;
            --pill-light-blue: #2c72a8;
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

        .welcome-msg h1 { margin: 0; font-size: 2.2rem; }
        .welcome-msg p { font-size: 1.2rem; margin-top: 10px; opacity: 0.9; }
        .welcome-msg span { color: var(--accent-gold); }

        .container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: -30px auto 40px auto;
            align-items: start;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.03);
            margin-bottom: 25px;
        }

        .sidebar-info { text-align: center; }
        .sidebar-info img {
            width: 110px; height: 110px; border-radius: 50%; object-fit: cover;
            border: 3px solid var(--primary-blue); margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 102, 0.15);
        }

        .sidebar-title { font-size: 1.4rem; color: #1a1a1a; margin: 0 0 5px 0; font-weight: 800; }
        .sidebar-subtitle { color: #555; font-size: 0.85rem; margin: 0 0 25px 0; }

        .info-group {
            text-align: left; background: var(--bg-light); padding: 12px 16px;
            border-radius: 12px; margin-bottom: 12px; border: 1px solid rgba(0,0,0,0.03);
        }
        .info-group label {
            display: block; font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 1px; color: var(--primary-blue); font-weight: 700; margin-bottom: 3px;
        }
        .info-group p { margin: 0; font-size: 0.95rem; font-weight: 600; color: #222; }

        .section-title {
            font-size: 1.3rem; color: var(--primary-blue); margin: 0 0 20px 0;
            font-weight: 700; border-bottom: 2px solid var(--bg-light); padding-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center;
        }

        .vm-heading { font-size: 0.95rem; color: var(--primary-blue); margin: 0 0 8px 0; letter-spacing: 1px; font-weight: 700; }
        .vm-text { font-size: 0.9rem; color: #555; line-height: 1.6; margin: 0; }

        .org-chart-container {
            width: 100%;
            padding: 30px 10px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #ffffff;
            overflow-x: auto;
            position: relative; 
        }

        .org-level {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 30px;
            width: 100%;
            margin-bottom: 45px;
            position: relative;
        }

        .org-node {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            width: 190px;
            text-align: center;
            z-index: 2;
        }

        .info-pill {
            width: 100%;
            background: var(--primary-blue);
            border-radius: 14px;
            padding: 14px 10px 10px 10px;
            box-sizing: border-box;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .info-pill .official-name {
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 3px;
            line-height: 1.2;
        }

        .info-pill .official-title {
            background: var(--pill-light-blue);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 4px;
            width: 90%;
            box-sizing: border-box;
            text-transform: capitalize;
        }

        .svg-connector {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }

        .svg-connector line {
            stroke: var(--primary-blue);
            stroke-width: 2;
        }

        .admin-edit-btn {
            background: var(--primary-blue); color: white; border: none;
            padding: 6px 14px; border-radius: 6px; font-size: 0.8rem;
            cursor: pointer; font-weight: 600; transition: background 0.3s;
        }
        .admin-edit-btn:hover { background: #1a1a8c; }

        .edit-form-container {
            display: none; background: #fafafa; padding: 20px;
            border-radius: 12px; border: 1px dashed var(--primary-blue); margin-top: 15px;
        }

        .form-control-group { margin-bottom: 15px; }
        .form-control-group label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--primary-blue); margin-bottom: 5px; }
        .form-control-group input, .form-control-group textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box;
            font-family: inherit;
        }
        .form-control-group textarea { resize: vertical; min-height: 100px; }

        .btn-save {
            background: var(--teal-green); color: white; border: none; padding: 10px 20px;
            border-radius: 8px; font-weight: 700; cursor: pointer; transition: opacity 0.3s;
        }
        .btn-save:hover { opacity: 0.9; }

        .btn-delete {
            background: var(--deep-red); color: white; border: none; padding: 8px 12px;
            border-radius: 6px; font-size: 0.8rem; cursor: pointer; text-decoration: none;
        }
        .btn-delete:hover { opacity: 0.85; }

        .alert-success {
            background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px;
            margin-bottom: 20px; border: 1px solid #c3e6cb; font-size: 0.9rem;
        }
        .alert-danger {
            background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px;
            margin-bottom: 20px; border: 1px solid #f5c6cb; font-size: 0.9rem;
        }

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
        .modal-content-custom {
            background-color: #fff; padding: 20px; border-radius: 12px;
            width: 100%; max-width: 420px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); position: relative; box-sizing: border-box;
        }
        .modal-close-btn {
            position: absolute; right: 18px; top: 15px; font-size: 1.4rem; font-weight: bold; color: #aaa; cursor: pointer; background: none; border: none;
        }
        .modal-close-btn:hover { color: #000; }
        .modal-header-custom { border-bottom: 2px solid #eee; padding-bottom: 8px; margin-bottom: 15px; padding-right: 30px; }
        .modal-header-custom h3 { margin: 0; color: var(--primary-blue); font-size: 1.1rem; }
        .modal-body-content { padding: 5px 0; font-size: 0.9rem; color: #333; line-height: 1.5; word-wrap: break-word; }
        .btn-modal-action { background: var(--primary-blue); color: white; padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; transition: 0.3s; }
        .btn-modal-action:hover { background: #1a1a8c; }
        .btn-modal-cancel { background: #6c757d; color: white; padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; transition: 0.3s; }
        .btn-modal-cancel:hover { background: #5a6268; }
        .btn-modal-danger { background: var(--deep-red); color: white; padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; transition: 0.3s; }
        .btn-modal-danger:hover { opacity: 0.85; }
        .modal-footer { margin-top: 20px; text-align: right; padding-top: 15px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }

        @media(max-width: 900px) { 
            .container { grid-template-columns: 1fr; } 
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
            .svg-connector { display: none; }
        }
    </style>
</head>
<body>

    <!-- Top bar aligned edge-to-edge with the main nav's 5% edge alignment -->
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
                <span style="font-size: 0.8rem;"><i class="fa fa-user-shield"></i> Administrator Access</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="main-nav">
        <div class="logo-section">
            <!-- Back button retains direct link behavior back to admin_dashboard.php -->
            <a href="admin_dashboard.php" class="back-link" title="Back to Dashboard">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Barangay Logo">
            <div class="logo-text">
                <h2>BARANGAY MALITBOG</h2>
                <small>MUNICIPALITY OF BONGABONG</small>
            </div>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="rd-nav-item custom-home-style" title="Admin Home">
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
                    <h4>Barangay Administrator</h4>
                    <p>admin@malitbog.gov.ph</p>
                </div>
            </div>
            <button type="button" class="close-btn" onclick="toggleNavBarMenu()">&times;</button>
        </div>
        <div class="menu-drawer-body">
            <a href="barangay_profileadmin.php"><i class="fas fa-university"></i> Barangay Info</a>
            <a href="change_password_admin.php"><i class="fas fa-key"></i> Change Password</a>
            <a href="javascript:void(0);" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content-custom" style="max-width: 360px;">
            <span class="modal-close-btn" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';">&times;</span>
            <div class="modal-header-custom">
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

    <!-- Hero Banner Profile -->
    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>BARANGAY <span>PROFILE</span></h1>
            <p>Managing Barangay Malitbog Information & Officials</p>
        </div>
        <div style="text-align: right;">
            <div id="current-date" style="font-weight: bold; font-size: 1.2rem; color: var(--accent-gold);"></div>
            <p>System Status: <span style="color: #00ff00;">Online</span></p>
        </div>
    </header>

    <!-- Content Layout Grid -->
    <div class="container">
        
        <!-- Sidebar Info Section -->
        <div class="card sidebar-info">
            <img src="image/barangaylogo3.jpg" alt="Barangay Seal">
            <h2 class="sidebar-title"><?php echo htmlspecialchars($profile['barangay_name']); ?></h2>
            <p class="sidebar-subtitle">Established <?php echo htmlspecialchars($profile['established_year']); ?></p>
            
            <div class="info-group">
                <label>Total Population</label>
                <p><?php echo htmlspecialchars($profile['total_population']); ?></p>
            </div>
            <div class="info-group">
                <label>Land Area</label>
                <p><?php echo htmlspecialchars($profile['land_area']); ?></p>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content-wrapper">
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert-success"><i class="fa fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- Profile & Background Card -->
            <div class="card">
                <h3 class="section-title">
                    <span>Barangay Profile & Background</span>
                    <button class="admin-edit-btn" onclick="toggleEditForm()">
                        <i class="fa fa-edit"></i> Edit Profile
                    </button>
                </h3>

                <div id="profile-display-view" class="vision-mission-grid">
                    <div>
                        <h4 class="vm-heading">OVERVIEW</h4>
                        <p class="vm-text"><?php echo nl2br(htmlspecialchars($profile['overview_text'])); ?></p>
                    </div>
                    <div style="margin-top: 20px;">
                        <h4 class="vm-heading">LOCAL ECONOMY & GEOGRAPHY</h4>
                        <p class="vm-text"><?php echo nl2br(htmlspecialchars($profile['economy_geography_text'])); ?></p>
                    </div>
                </div>

                <div id="profile-edit-form" class="edit-form-container">
                    <form action="" method="POST">
                        <h4 style="margin-top:0; color:var(--primary-blue);">Update Barangay Details</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-control-group">
                                <label>Barangay Name</label>
                                <input type="text" name="barangay_name" value="<?php echo htmlspecialchars($profile['barangay_name']); ?>" required>
                            </div>
                            <div class="form-control-group">
                                <label>Municipality</label>
                                <input type="text" name="municipality" value="<?php echo htmlspecialchars($profile['municipality']); ?>" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-control-group">
                                <label>Established Year</label>
                                <input type="text" name="established_year" value="<?php echo htmlspecialchars($profile['established_year']); ?>">
                            </div>
        
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-control-group">
                                <label>Total Population</label>
                                <input type="text" name="total_population" value="<?php echo htmlspecialchars($profile['total_population']); ?>" required>
                            </div>
                            <div class="form-control-group">
                                <label>Land Area</label>
                                <input type="text" name="land_area" value="<?php echo htmlspecialchars($profile['land_area']); ?>" required>
                            </div>
                        </div>

                        <div class="form-control-group">
                            <label>Overview Text</label>
                            <textarea name="overview_text" required><?php echo htmlspecialchars($profile['overview_text']); ?></textarea>
                        </div>

                        <div class="form-control-group">
                            <label>Local Economy & Geography</label>
                            <textarea name="economy_geography_text" required><?php echo htmlspecialchars($profile['economy_geography_text']); ?></textarea>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="update_profile" class="btn-save"><i class="fa fa-save"></i> Save Changes</button>
                            <button type="button" class="admin-edit-btn" style="background:#6c757d;" onclick="toggleEditForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Barangay Officials Organizational Chart Card -->
            <div class="card">
                <h3 class="section-title">
                    <span>Barangay Officials</span>
                    <button class="admin-edit-btn" onclick="toggleOfficialsForm()">
                        <i class="fa fa-edit"></i> Manage Officials
                    </button>
                </h3>
                
                <div id="officials-display-view">
                    <?php if (count($officials) > 0): ?>
                        <?php 
                        $captains = [];
                        $row2Officials = []; 
                        $row3Officials = []; 
                        $row4Officials = [];

                        foreach ($officials as $official) {
                            $posLower = strtolower($official['position']);
                            if (strpos($posLower, 'captain') !== false || strpos($posLower, 'punong barangay') !== false) {
                                $captains[] = $official;
                            } elseif (strpos($posLower, 'secretary') !== false || strpos($posLower, 'treasurer') !== false || strpos($posLower, 'sk chairman') !== false) {
                                $row2Officials[] = $official;
                            } elseif (strpos($posLower, 'kagawad') !== false && count($row3Officials) < 4) {
                                $row3Officials[] = $official;
                            } else {
                                $row4Officials[] = $official;
                            }
                        }

                        if (empty($captains) && !empty($officials)) {
                            $captains[] = array_shift($officials);
                        }
                        
                        $remainingPool = array_merge($row2Officials, $row3Officials, $row4Officials);
                        $countRem = count($remainingPool);
                        $r2Count = min(3, max(1, floor($countRem / 3)));
                        $r3Count = min(4, max(1, floor(($countRem - $r2Count) / 2)));
                        
                        $branchRow2 = array_splice($remainingPool, 0, $r2Count);
                        $branchRow3 = array_splice($remainingPool, 0, $r3Count);
                        $branchRow4 = $remainingPool; 
                        ?>

                        <div class="org-chart-container" id="orgChartContainer">
                            <svg class="svg-connector" id="svgConnector">
                                <defs>
                                    <marker id="arrowhead" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                                        <path d="M 0 0 L 10 5 L 0 10 z" fill="#000066" />
                                    </marker>
                                </defs>
                            </svg>

                            <div class="org-level" id="level1">
                                <?php foreach ($captains as $index => $captain): ?>
                                    <div class="org-node" id="captain-<?php echo $index; ?>">
                                        <div class="info-pill">
                                            <div class="official-name"><?php echo htmlspecialchars($captain['name']); ?></div>
                                            <div class="official-title"><?php echo htmlspecialchars($captain['position']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($branchRow2)): ?>
                                <div class="org-level" id="level2">
                                    <?php foreach ($branchRow2 as $index => $officer): ?>
                                        <div class="org-node level2-node" id="manager-<?php echo $index; ?>">
                                            <div class="info-pill">
                                                <div class="official-name"><?php echo htmlspecialchars($officer['name']); ?></div>
                                                <div class="official-title"><?php echo htmlspecialchars($officer['position']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($branchRow3)): ?>
                                <div class="org-level" id="level3">
                                    <?php foreach ($branchRow3 as $index => $st): ?>
                                        <div class="org-node level3-node" id="staff-<?php echo $index; ?>">
                                            <div class="info-pill">
                                                <div class="official-name"><?php echo htmlspecialchars($st['name']); ?></div>
                                                <div class="official-title"><?php echo htmlspecialchars($st['position']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($branchRow4)): ?>
                                <div class="org-level" id="level4">
                                    <?php foreach ($branchRow4 as $index => $b4): ?>
                                        <div class="org-node level4-node" id="bottom-<?php echo $index; ?>">
                                            <div class="info-pill">
                                                <div class="official-name"><?php echo htmlspecialchars($b4['name']); ?></div>
                                                <div class="official-title"><?php echo htmlspecialchars($b4['position']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <script>
                            function drawChartConnectors() {
                                const container = document.getElementById('orgChartContainer');
                                const svg = document.getElementById('svgConnector');
                                const displayView = document.getElementById('officials-display-view');
                                
                                if (!container || !svg || (displayView && displayView.style.display === 'none')) return;

                                svg.style.width = container.scrollWidth + 'px';
                                svg.style.height = container.scrollHeight + 'px';
                                svg.setAttribute('width', container.scrollWidth);
                                svg.setAttribute('height', container.scrollHeight);

                                const defs = svg.querySelector('defs');
                                svg.innerHTML = '';
                                if (defs) {
                                    svg.appendChild(defs);
                                } else {
                                    const newDefs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                                    newDefs.innerHTML = '<marker id="arrowhead" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto"><path d="M 0 0 L 10 5 L 0 10 z" fill="#000066" /></marker>';
                                    svg.appendChild(newDefs);
                                }

                                const containerRect = container.getBoundingClientRect();
                                const level1Nodes = document.querySelectorAll('#level1 .org-node');
                                const level2Nodes = document.querySelectorAll('#level2 .org-node');
                                const level3Nodes = document.querySelectorAll('#level3 .org-node');
                                const level4Nodes = document.querySelectorAll('#level4 .org-node');

                                level1Nodes.forEach(parent => {
                                    const pRect = parent.getBoundingClientRect();
                                    const startX = pRect.left - containerRect.left + container.scrollLeft + pRect.width / 2;
                                    const startY = pRect.bottom - containerRect.top + container.scrollTop;

                                    level2Nodes.forEach(child => {
                                        const cRect = child.getBoundingClientRect();
                                        const endX = cRect.left - containerRect.left + container.scrollLeft + cRect.width / 2;
                                        const endY = cRect.top - containerRect.top + container.scrollTop;

                                        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                                        line.setAttribute('x1', startX);
                                        line.setAttribute('y1', startY);
                                        line.setAttribute('x2', endX);
                                        line.setAttribute('y2', endY);
                                        line.setAttribute('marker-end', 'url(#arrowhead)');
                                        svg.appendChild(line);
                                    });
                                });

                                if (level2Nodes.length > 0 && level3Nodes.length > 0) {
                                    level2Nodes.forEach((parent, i) => {
                                        const pRect = parent.getBoundingClientRect();
                                        const startX = pRect.left - containerRect.left + container.scrollLeft + pRect.width / 2;
                                        const startY = pRect.bottom - containerRect.top + container.scrollTop;

                                        level3Nodes.forEach((child, cIndex) => {
                                            if (cIndex % level2Nodes.length === i) {
                                                const cRect = child.getBoundingClientRect();
                                                const endX = cRect.left - containerRect.left + container.scrollLeft + cRect.width / 2;
                                                const endY = cRect.top - containerRect.top + container.scrollTop;

                                                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                                                line.setAttribute('x1', startX);
                                                line.setAttribute('y1', startY);
                                                line.setAttribute('x2', endX);
                                                line.setAttribute('y2', endY);
                                                line.setAttribute('marker-end', 'url(#arrowhead)');
                                                svg.appendChild(line);
                                            }
                                        });
                                    });
                                }

                                if (level3Nodes.length > 0 && level4Nodes.length > 0) {
                                    level3Nodes.forEach((parent, i) => {
                                        const pRect = parent.getBoundingClientRect();
                                        const startX = pRect.left - containerRect.left + container.scrollLeft + pRect.width / 2;
                                        const startY = pRect.bottom - containerRect.top + container.scrollTop;

                                        level4Nodes.forEach((child, cIndex) => {
                                            if (cIndex % level3Nodes.length === i) {
                                                const cRect = child.getBoundingClientRect();
                                                const endX = cRect.left - containerRect.left + container.scrollLeft + cRect.width / 2;
                                                const endY = cRect.top - containerRect.top + container.scrollTop;

                                                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                                                line.setAttribute('x1', startX);
                                                line.setAttribute('y1', startY);
                                                line.setAttribute('x2', endX);
                                                line.setAttribute('y2', endY);
                                                line.setAttribute('marker-end', 'url(#arrowhead)');
                                                svg.appendChild(line);
                                            }
                                        });
                                    });
                                }
                            }

                            window.addEventListener('load', () => setTimeout(drawChartConnectors, 100));
                            window.addEventListener('resize', () => {
                                clearTimeout(window.resizeTimer);
                                window.resizeTimer = setTimeout(drawChartConnectors, 100);
                            });
                        </script>
                    <?php else: ?>
                        <p style="color:#555; font-style: italic;">No officials recorded yet.</p>
                    <?php endif; ?>
                </div>

                <div id="officials-edit-form" class="edit-form-container">
                    <form id="officialsEditForm" enctype="multipart/form-data" onsubmit="saveOfficialsDetails(event)">
                        <input type="hidden" name="action" value="update_officials">
                        <h4 style="margin-top:0; color:var(--primary-blue);">Edit or Update Existing Officials</h4>
                        
                        <?php foreach ($officials as $official): ?>
                            <div style="background: white; padding: 12px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #ddd;">
                                <input type="hidden" name="official_id[]" value="<?php echo $official['id']; ?>">
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: center;">
                                    <div class="form-control-group" style="margin-bottom:0;">
                                        <label>Name</label>
                                        <input type="text" name="official_name[]" value="<?php echo htmlspecialchars($official['name']); ?>" required>
                                    </div>
                                    <div class="form-control-group" style="margin-bottom:0;">
                                        <label>Position</label>
                                        <input type="text" name="official_position[]" value="<?php echo htmlspecialchars($official['position']); ?>" required>
                                    </div>
                                    <div>
                                        <button type="button" class="btn-delete" title="Delete Official" onclick="confirmDeleteOfficial(<?php echo $official['id']; ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr style="margin: 20px 0; border:0; border-top: 1px solid #ddd;">
                        
                        <h4 style="color:var(--primary-blue);">Add New Official</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                            <div class="form-control-group">
                                <label>New Official Name</label>
                                <input type="text" name="new_official_name" placeholder="e.g. Juan Dela Cruz">
                            </div>
                            <div class="form-control-group">
                                <label>New Official Position</label>
                                <input type="text" name="new_official_position" placeholder="e.g. Kagawad - Health">
                            </div>
                            <div class="form-control-group">
                                <label>New Official Photo</label>
                                <input type="file" name="new_official_photo" accept="image/*">
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <button type="submit" id="submitOfficialsBtn" class="btn-save"><i class="fa fa-save"></i> Save Officials</button>
                            <button type="button" class="admin-edit-btn" style="background:#6c757d;" onclick="toggleOfficialsForm()">Cancel</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content-custom">
            <button type="button" class="modal-close-btn" onclick="closeConfirmModal()">&times;</button>
            <div class="modal-header-custom">
                <h3>Confirm Deletion</h3>
            </div>
            <div class="modal-body-content">
                <p style="margin: 0;">Are you sure you want to permanently delete this official?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button type="button" class="btn-modal-danger" id="confirmDeleteBtn" onclick="executeDeleteOfficial()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Application Result Modal -->
    <div id="resultModal" class="modal">
        <div class="modal-content-custom">
            <button type="button" class="modal-close-btn" onclick="closeResultModal()">&times;</button>
            <div class="modal-header-custom">
                <h3 id="resultModalTitle">Application Notice</h3>
            </div>
            <div class="modal-body-content">
                <p id="resultModalMessageBody" style="margin: 0;">Processing your request...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-action" onclick="closeResultModal()">OK</button>
            </div>
        </div>
    </div>

    <!-- JavaScript & AJAX Handlers -->
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
            document.getElementById('resultModal').style.display = 'none';
            document.getElementById('confirmDeleteModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        // Logout Button Modal Logic
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

        const resultModal = document.getElementById('resultModal');
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        const resultModalTitle = document.getElementById('resultModalTitle');
        const resultModalMessageBody = document.getElementById('resultModalMessageBody');
        
        let officialIdToDelete = null;

        function toggleEditForm() {
            const displayView = document.getElementById('profile-display-view');
            const editForm = document.getElementById('profile-edit-form');
            if (editForm.style.display === 'none' || editForm.style.display === '') {
                editForm.style.display = 'block';
                displayView.style.display = 'none';
            } else {
                editForm.style.display = 'none';
                displayView.style.display = 'grid';
            }
        }

        function toggleOfficialsForm() {
            const displayView = document.getElementById('officials-display-view');
            const editForm = document.getElementById('officials-edit-form');
            if (editForm.style.display === 'none' || editForm.style.display === '') {
                editForm.style.display = 'block';
                displayView.style.display = 'none';
            } else {
                editForm.style.display = 'none';
                displayView.style.display = 'block';
                setTimeout(drawChartConnectors, 100);
            }
        }

        function closeResultModal() { 
            resultModal.style.display = "none"; 
            globalOverlay.style.display = 'none';
            location.reload();
        }

        function closeConfirmModal() {
            confirmDeleteModal.style.display = "none";
            globalOverlay.style.display = 'none';
            officialIdToDelete = null;
        }

        function confirmDeleteOfficial(officialId) {
            officialIdToDelete = officialId;
            confirmDeleteModal.style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        function executeDeleteOfficial() {
            if (!officialIdToDelete) return;

            confirmDeleteModal.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'delete_official');
            formData.append('official_id', officialIdToDelete);

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultModalTitle.textContent = "Success: Official Deleted";
                    resultModalMessageBody.textContent = data.message;
                } else {
                    resultModalTitle.textContent = "Notice: Error Occurred";
                    resultModalMessageBody.textContent = "Error: " + data.message;
                }
                resultModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                resultModalTitle.textContent = "System Error";
                resultModalMessageBody.textContent = "An unexpected error occurred.";
                resultModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            });
        }

        function saveOfficialsDetails(event) {
            event.preventDefault();
            const form = document.getElementById('officialsEditForm');
            const formData = new FormData(form);
            const submitBtn = document.getElementById('submitOfficialsBtn');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                toggleOfficialsForm();
                if (data.success) {
                    resultModalTitle.textContent = "Success: Officials Updated";
                    resultModalMessageBody.textContent = data.message;
                } else {
                    resultModalTitle.textContent = "Notice: Error Occurred";
                    resultModalMessageBody.textContent = "Error: " + data.message;
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-save"></i> Save Officials';
                resultModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            })
            .catch(error => {
                toggleOfficialsForm();
                console.error('Error:', error);
                resultModalTitle.textContent = "System Error";
                resultModalMessageBody.textContent = "An unexpected error occurred.";
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-save"></i> Save Officials';
                resultModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
            });
        }

        window.onclick = function(event) {
            if (event.target === resultModal) closeResultModal();
            if (event.target === confirmDeleteModal) closeConfirmModal();
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
                globalOverlay.style.display = 'none';
            }
        }
    </script>
</body>
</html>