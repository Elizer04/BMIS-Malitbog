<?php
include 'db_connect.php'; 

$count_result = $conn->query("SELECT COUNT(*) AS total FROM residents");
$count_row = $count_result->fetch_assoc();
$next_num = $count_row['total'] + 1;
$current_year = date('Y');
$auto_resident_id = $current_year . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Registration - Barangay Malitbog</title>
    <!-- Font Awesome Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Custom CSS for Two-Column Grid Form Layout -->
    <style>
        :root {
            --primary-blue: #000066;
            --secondary-blue: #1a1a8c;
            --accent-gold: #ffcc00;
            --text-white: #ffffff;
            --white: #ffffff;
            --bg-light: #f4f7f6;
            --primary-dark: #2c3e50;
            --accent-blue: #294a71;
            --text-gray: #555;
            --teal-green: #157347;   
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f0f4ff 0%, #e6eeff 50%, #d9e4ff 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* === Topbar Styling matching index.php === */
        .topbar {
            background: var(--secondary-blue);
            color: white;
            padding: 8px 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            box-sizing: border-box;
            z-index: 1000;
        }

        .socials {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .socials a {
            color: white;
            display: inline-flex;
            width: 16px;
            height: 16px;
            transition: color 0.3s ease;
        }

        .socials a svg {
            fill: currentColor;
            width: 100%;
            height: 100%;
        }

        .socials a:hover {
            color: var(--accent-gold);
        }

        .socials .instagram-mark {
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
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
            z-index: 1000;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .logo-section img { 
            height: 55px; 
            width: 55px; 
            border-radius: 50%; 
            object-fit: cover;
            border: 2px solid var(--primary-blue);
        }

        .logo-text h2 {
            margin: 0;
            color: var(--primary-blue);
            font-size: 1.2rem;
            letter-spacing: 1px;
            font-weight: 800;
        }

        .logo-text small {
            color: var(--text-gray);
            font-size: 0.75rem;
            font-weight: 600;
        }

        a.btn-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-blue);
            background: transparent;
            border: none;
            text-decoration: none;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        a.btn-home:hover {
            color: var(--accent-gold);
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
            background: linear-gradient(135deg, #f0f4ff 0%, #e6eeff 50%, #d9e4ff 100%);
        }

        .portal-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .portal-card-box {
            background: var(--white);
            border-radius: 28px;
            box-shadow: 0 20px 45px rgba(26, 26, 140, 0.12);
            overflow: hidden;
            max-width: 800px !important;
            width: 100%;
            border: 1px solid rgba(26, 26, 140, 0.12);
            padding: 30px;
            box-sizing: border-box;
            margin: 20px auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px 20px;
            width: 100%;
        }

        .form-group.full-width, 
        .btn-register, 
        .footer-links {
            grid-column: span 2;
        }

        .form-group {
            text-align: left;
            margin-bottom: 0.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--accent-blue);
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.85rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
            background-color: #f9fbfd;
        }

        /* Hide browser's default native password reveal icon */
        #password::-ms-reveal,
        #password::-ms-clear {
            display: none;
        }
        #password::-webkit-credentials-auto-fill-button,
        #password::-webkit-contacts-auto-fill-button {
            visibility: hidden;
            display: none !important;
            pointer-events: none;
            height: 0;
            width: 0;
            margin: 0;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--secondary-blue);
            background-color: #ffffff;
            box-sizing: border-box;
            box-shadow: 0 0 0 4px rgba(26, 26, 140, 0.1);
        }

        .btn-register {
            width: 100%;
            background: var(--primary-blue);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.8rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-register:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(26, 26, 140, 0.25);
        }

        .footer-links a {
            color: var(--secondary-blue);
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--accent-gold);
            text-decoration: underline;
        }

        /* === Enhanced Dialog Box (Modal) Styling === */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
            animation: backdropFadeIn 0.25s ease-out;
        }

        .modal-content {
            background-color: #ffffff;
            padding: 32px 28px 28px;
            border-radius: 22px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 102, 0.25);
            border: 1px solid rgba(26, 26, 140, 0.08);
            position: relative;
            text-align: center;
            box-sizing: border-box;
            animation: modalPopIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .close-btn {
            position: absolute;
            top: 16px;
            right: 18px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #64748b;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .close-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .modal-icon-badge {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 18px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }

        .modal-icon-badge.success {
            background-color: #dcfce7;
            color: #173ab8;
            box-shadow: 0 0 0 8px rgba(220, 252, 231, 0.45);
        }

        .modal-icon-badge.warning {
            background-color: #fef3c7;
            color: #d97706;
            box-shadow: 0 0 0 8px rgba(254, 243, 199, 0.45);
        }

        .modal-icon-badge.error {
            background-color: #fee2e2;
            color: #dc2626;
            box-shadow: 0 0 0 8px rgba(254, 226, 226, 0.45);
        }

        .modal-header h3 {
            margin: 0 0 10px 0;
            color: var(--primary-blue);
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .modal-body-content {
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .resident-id-box {
            background: #f0f4ff;
            border: 1.5px dashed #93c5fd;
            border-radius: 12px;
            padding: 12px 16px;
            margin: 14px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .resident-id-box span {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--accent-blue);
            font-weight: 700;
        }

        .resident-id-box strong {
            font-size: 1.35rem;
            color: var(--primary-blue);
            font-weight: 800;
            letter-spacing: 1px;
        }

        .btn-modal-action {
            width: 100%;
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 0.92rem;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 102, 0.2);
        }

        .btn-modal-action:hover {
            background: var(--secondary-blue);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 102, 0.28);
        }

        .btn-modal-action.success-btn {
            background: var(--teal-green);
            box-shadow: 0 4px 12px rgba(21, 115, 71, 0.25);
        }

        .btn-modal-action.success-btn:hover {
            background: #160ca4;
            box-shadow: 0 6px 16px rgba(21, 115, 71, 0.35);
        }

        @keyframes backdropFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalPopIn {
            from {
                opacity: 0;
                transform: scale(0.92) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .site-footer {
            background-color: var(--primary-blue);
            color: #d1d5db;
            padding: 30px 40px;
            font-size: 0.85rem;
            width: 100%;
            box-sizing: border-box;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #9ca3af;
            font-size: 0.8rem;
            flex-wrap: wrap;
            gap: 15px;
        }

        .footer-bottom-links {
            display: flex;
            gap: 25px;
        }

        .footer-bottom-links a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a:hover {
            color: white;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width, 
            .btn-register, 
            .footer-links {
                grid-column: span 1;
            }
            .portal-card-box {
                padding: 2rem 1.5rem;
            }
            .footer-bottom {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <!-- Topbar Section matching index.php -->
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
        <div></div>
    </div>

    <!-- Navigation Bar -->
    <nav class="main-nav">
        <div class="nav-left">
            <a href="resident_portal.php" class="btn-home" aria-label="Back to Portal" title="Back to Portal">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="logo-section">
                <img src="image/barangaylogo3.jpg" alt="Barangay Logo">
                <div class="logo-text">
                    <h2>BARANGAY MALITBOG</h2>
                    <small>RESIDENT PORTAL</small>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper with Unified Container Card -->
    <div class="main-content">
        <div class="portal-wrapper">
            <div class="portal-card-box" data-aos="fade-up" data-aos-duration="800">
                <div class="registration-header" style="text-align: center; margin-bottom: 25px;">
                    <img src="image/accregister.jpg" alt="Barangay Logo" class="portal-logo" style="width: 75px; height: 75px; object-fit: cover; border-radius: 50%; border: 3px solid var(--primary-blue); margin-bottom: 0.5rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <h2 style="font-size: 1.3rem; margin: 0; letter-spacing: 1px; color: var(--primary-blue); font-weight: 800; margin-bottom: 4px;">ACCOUNT REGISTRATION</h2>
                    <p style="font-size: 0.8rem; color: var(--teal-green); margin-top: 0; margin-bottom: 0; font-weight: 600;">Join your barangay digital portal</p>
                </div>

                <form id="registrationForm" action="process_registration.php" method="POST" autocomplete="off" style="width: 100%;">
                    <input type="hidden" id="resident_id" name="resident_id" value="<?php echo htmlspecialchars($auto_resident_id); ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Dela Cruz" maxlength="50" autocomplete="off" required>
                        </div>

                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" placeholder="Juan" maxlength="50" autocomplete="off" required>
                        </div>

                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" placeholder="Santos" maxlength="50" autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" required>
                        </div>

                        <div class="form-group">
                            <label for="civil_status">Civil Status</label>
                            <select id="civil_status" name="civil_status" required>
                                <option value="" disabled selected>Select Civil Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="address">Address / Purok</label>
                            <select id="address" name="address" required>
                                <option value="" disabled selected>Select Address / Purok</option>
                                <option value="Centro">Centro</option>
                                <option value="KM4">KM4</option>
                                <option value="KM5">KM5</option>
                                <option value="Miaje">Miaje</option>
                                <option value="Mayangis">Mayangis</option>
                                <option value="Sampalocan">Sampalocan</option>
                                <option value="Sto. Niño">Sto. Niño</option>
                                <option value="Alacan">Alacan</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" placeholder="09123456789 or +639123456789" autocomplete="off" required>
                            <!-- Inline Phone Error Message Container -->
                            <small id="phoneError" style="color: #dc3545; display: none; margin-top: 5px; font-size: 0.85rem; font-weight: 500;"></small>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="juan.dc@example.com" autocomplete="off" required>
                            <!-- Inline Email Error Message Container -->
                            <small id="emailError" style="color: #dc3545; display: none; margin-top: 5px; font-size: 0.85rem; font-weight: 500;"></small>
                        </div>

                        <div class="form-group full-width">
                            <label for="password">Password (6 to 20 characters)</label>
                            <div class="password-wrapper" style="position: relative;">
                                <input type="password" id="password" name="password" placeholder="Enter 6-20 characters" minlength="6" maxlength="20" autocomplete="new-password" required style="width: 100%; padding-right: 40px;">
                                <i class="fa-solid fa-eye-slash toggle-password" id="togglePassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-gray);"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-register" id="submitBtn">Register Now</button>

                        <div class="footer-links" style="text-align: center; margin-top: 10px; font-size: 0.8rem; color: var(--text-gray); border-top: 1px solid rgba(0,0,0,0.06); padding-top: 1rem;">
                            <span>Already have an account? <a href="resident_portal.php">Login here</a></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Application Result Dialog Box -->
    <div id="resultModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('resultModal')" aria-label="Close">&times;</span>
            <div id="modalIconBadge" class="modal-icon-badge success">
                <i id="modalIcon" class="fa-solid fa-check"></i>
            </div>
            <div class="modal-header">
                <h3 id="modalTitle">Registration Notice</h3>
            </div>
            <div class="modal-body-content" id="modalMessageBody">
                Processing your status...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-action" id="modalActionBtn" onclick="closeModal('resultModal')">OK</button>
            </div>
        </div>
    </div>

    <!-- Professional Footer Section -->
    <footer class="site-footer" data-aos="fade" data-aos-duration="1000">
        <div class="footer-bottom">
            <div>&copy; 2026 BMIS. All rights reserved.</div>
            <div class="footer-bottom-links">
                <a href="privacy.php">Data Privacy</a>
                <a href="terms.php">Terms of Use</a>
                <a href="support.php">Support</a>
            </div>
        </div>
    </footer>

    <!-- AOS Script Integration -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 50 });

        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        let shouldRedirect = false;

        function showDialog({ type = 'success', title, bodyHTML, buttonText = 'OK', redirect = false }) {
            const badge = document.getElementById('modalIconBadge');
            const icon = document.getElementById('modalIcon');
            const titleEl = document.getElementById('modalTitle');
            const bodyEl = document.getElementById('modalMessageBody');
            const btnEl = document.getElementById('modalActionBtn');

            badge.className = 'modal-icon-badge ' + type;
            btnEl.className = 'btn-modal-action' + (type === 'success' ? ' success-btn' : '');

            if (type === 'success') {
                icon.className = 'fa-solid fa-check';
                titleEl.style.color = '#157347';
            } else if (type === 'warning') {
                icon.className = 'fa-solid fa-exclamation';
                titleEl.style.color = '#b45309';
            } else {
                icon.className = 'fa-solid fa-xmark';
                titleEl.style.color = '#dc2626';
            }

            titleEl.textContent = title;
            bodyEl.innerHTML = bodyHTML;
            btnEl.textContent = buttonText;
            shouldRedirect = redirect;

            document.getElementById('resultModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (shouldRedirect) {
                window.location.href = 'sign_in.php';
            }
        }

        const registrationForm = document.getElementById('registrationForm');
        const submitBtn = document.getElementById('submitBtn');
        const resultModal = document.getElementById('resultModal');
        
        const phoneInputEl = document.getElementById('phone_number');
        const phoneErrorEl = document.getElementById('phoneError');
        const emailInputEl = document.getElementById('email');
        const emailErrorEl = document.getElementById('emailError');

        phoneInputEl.addEventListener('input', function() {
            phoneErrorEl.style.display = 'none';
            phoneErrorEl.textContent = '';
            phoneInputEl.style.borderColor = '';
        });

        emailInputEl.addEventListener('input', function() {
            emailErrorEl.style.display = 'none';
            emailErrorEl.textContent = '';
            emailInputEl.style.borderColor = '';
        });

        registrationForm.addEventListener('submit', function(e) {
            e.preventDefault(); 

            phoneErrorEl.style.display = 'none';
            phoneErrorEl.textContent = '';
            phoneInputEl.style.borderColor = '';

            emailErrorEl.style.display = 'none';
            emailErrorEl.textContent = '';
            emailInputEl.style.borderColor = '';

            const passwordVal = password.value;
            if (passwordVal.length < 6 || passwordVal.length > 20) {
                showDialog({
                    type: 'warning',
                    title: 'Validation Error',
                    bodyHTML: 'Password must be strictly between <strong>6 and 20 characters</strong> long.',
                    buttonText: 'Got It',
                    redirect: false
                });
                return;
            }

            const phoneInput = phoneInputEl.value.trim();
            const cleanPhone = phoneInput.replace(/[\s\-\(\)]/g, ''); 
            const phPhoneRegex = /^(\+63|0)9\d{9}$/;

            if (!phPhoneRegex.test(cleanPhone)) {
                phoneErrorEl.textContent = "Please enter a valid Philippine mobile number (e.g., 09123456789 or +639123456789).";
                phoneErrorEl.style.display = 'block';
                phoneInputEl.style.borderColor = '#dc3545';
                phoneInputEl.focus();
                return; 
            }

            const emailInput = emailInputEl.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(emailInput)) {
                emailErrorEl.textContent = "Please enter a real and properly formatted email address (e.g., name@example.com).";
                emailErrorEl.style.display = 'block';
                emailInputEl.style.borderColor = '#dc3545';
                emailInputEl.focus();
                return;
            }

            const formData = new FormData(registrationForm);
            formData.set('phone_number', cleanPhone);

            submitBtn.disabled = true;
            submitBtn.textContent = "Processing Registration...";

            const startTime = Date.now();

            fetch('process_registration.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                const elapsedTime = Date.now() - startTime;
                const delay = elapsedTime < 500 ? 500 - elapsedTime : 0;

                setTimeout(() => {
                    let serverMsg = data.trim();

                    if (serverMsg.toLowerCase().includes('success') || serverMsg === '1') {
                        const generatedResidentId = document.getElementById('resident_id').value;
                        registrationForm.reset();

                        showDialog({
                            type: 'success',
                            title: 'Registration Complete!',
                            bodyHTML: `
                                Your resident account has been successfully created. Please save your Resident ID below:
                                <div class="resident-id-box">
                                    <span>Your Resident ID</span>
                                    <strong>${generatedResidentId}</strong>
                                </div>
                                You can now log in to access the Barangay Malitbog Resident Portal.
                            `,
                            buttonText: 'Proceed to Login',
                            redirect: true
                        });
                    } else if (serverMsg === 'duplicate' || serverMsg.toLowerCase().includes('duplicate') || serverMsg.toLowerCase().includes('already')) {
                        showDialog({
                            type: 'warning',
                            title: 'Existing Record Detected',
                            bodyHTML: 'The resident details, email address, or phone number provided are already registered in the system.',
                            buttonText: 'Review Details',
                            redirect: false
                        });
                    } else {
                        showDialog({
                            type: 'error',
                            title: 'Registration Failed',
                            bodyHTML: serverMsg,
                            buttonText: 'Try Again',
                            redirect: false
                        });
                    }

                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Register Now';
                }, delay);
            })
            .catch(error => {
                setTimeout(() => {
                    showDialog({
                        type: 'error',
                        title: 'System Error',
                        bodyHTML: 'A network error occurred. Please check your internet connection and try again.',
                        buttonText: 'Close',
                        redirect: false
                    });

                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Register Now';
                }, 500);
            });
        });

        window.onclick = function(event) {
            if (event.target === resultModal) {
                closeModal('resultModal');
            }
        }
    </script>
</body>
</html>