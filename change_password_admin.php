<?php
// 1. Start session
session_start();

// Check if admin is logged in via email
if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connect.php'; 

$admin_email = $_SESSION['email'];
$full_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Barangay Malitbog</title>
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

        .back-link:hover {
            color: var(--accent-gold);
        }

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

        .rd-nav-item.active {
            color: var(--accent-gold);
        }

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

        /* Hero Banner */
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

        /* Form Container */
        .container {
            max-width: 600px;
            margin: -30px auto 40px;
            padding: 0 20px;
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
            margin-top: 0;
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

        /* Hide browser's default native password reveal icon & auto-fill buttons */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
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

        .password-wrapper {
            position: relative;
        }

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
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-track:hover { background: #000044; transform: translateY(-2px); }
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
            max-width: 380px;
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

    <!-- Top bar aligned edge-to-edge with main-nav -->
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

    <nav class="main-nav">
        <div class="logo-section">
            <!-- Back button keeps its original direct link to admin_dashboard.php -->
            <a href="admin_dashboard.php" class="back-link" title="Back to Dashboard">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Logo">
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
                    <p><?php echo htmlspecialchars($admin_email); ?></p>
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

    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>SECURITY <span>SETTINGS</span></h1>
            <p>Update Your Administrator Password - Barangay Malitbog</p>
        </div>
        <div style="text-align: right;">
            <div id="current-date" style="font-weight: bold; font-size: 1.2rem; color: var(--accent-gold);"></div>
            <p>System Status: <span style="color: #00ff00;">Online</span></p>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h3 class="section-title">Change Password</h3>
            
            <form id="updatePasswordForm" action="update_password_admin.php" method="post">
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
                    <label class="info-label" for="confirm_password">Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-input" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required style="padding-right: 40px;">
                        <i class="fa-solid fa-eye-slash toggle-password" data-target="confirm_password"></i>
                    </div>
                </div>

                <button type="submit" name="update" class="btn-track" id="submitBtn">
                    <i class="fas fa-lock-open"></i> Update Password
                </button>
            </form>
            
            <div class="rd-back-dashboard">
                <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Password Update Result Modal -->
    <div id="resultModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('resultModal')" style="position: absolute; right: 15px; top: 12px;">&times;</span>
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

    <!-- System Notice Modal for Logout -->
    <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 360px;">
            <span class="close-btn" onclick="closeModal('logoutModal')" style="position: absolute; right: 15px; top: 12px;">&times;</span>
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content">
                <p style="margin: 0;">Are you sure you want to leave or log out?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('logoutModal')">Cancel</button>
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
            document.getElementById('resultModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
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

        // Password Visibility Toggle Logic for multiple fields
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

        const updatePasswordForm = document.getElementById('updatePasswordForm');
        const submitBtn = document.getElementById('submitBtn');
        const resultModal = document.getElementById('resultModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessageBody = document.getElementById('modalMessageBody');

        updatePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                modalTitle.textContent = "Validation Error";
                modalMessageBody.textContent = "New passwords do not match. Please check and try again.";
                resultModal.style.display = 'flex';
                globalOverlay.style.display = 'block';
                return;
            }

            const formData = new FormData(updatePasswordForm);
            formData.append('update', 'true');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            const startTime = Date.now();

            fetch('update_password_admin.php', {
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
                        modalMessageBody.textContent = "Your password has been successfully updated across all admin profiles.";
                        updatePasswordForm.reset(); 
                    } else {
                        modalTitle.textContent = "Notice: Update Issue";
                        modalMessageBody.textContent = (cleanResponse && cleanResponse.length < 120 && !cleanResponse.startsWith('<')) 
                            ? cleanResponse 
                            : "Unable to update password. Please verify that your current password is correct and try again.";
                    }

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-lock-open"></i> Update Password';
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

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-lock-open"></i> Update Password';
                    resultModal.style.display = 'flex';
                    globalOverlay.style.display = 'block';
                }, delay);
            });
        });

        window.addEventListener('click', function(event) {
            if (event.target === resultModal) {
                closeModal('resultModal');
            }
            if (event.target === logoutModal) {
                closeModal('logoutModal');
            }
        });
    </script>
</body>
</html>
<?php 
if (isset($conn)) {
    $conn->close(); 
}
?>