<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Selector - Barangay Malitbog</title>
    <!-- Font Awesome Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <!-- Main Global Stylesheet from index.php -->
    <!-- <link rel="stylesheet" href="css/style.css"> -->
    
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
            font-family: 'Inter', sans-serif; /* Applied Inter font from index.php */
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f0f4ff 0%, #e6eeff 50%, #d9e4ff 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* === Topbar Styling from index.php === */
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

        /* === Navigation & Logo Section from index.php === */
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
            max-width: 500px;
            width: 100%;
        }

        .portal-card-box {
            background: var(--white);
            border-radius: 28px;
            box-shadow: 0 20px 45px rgba(26, 26, 140, 0.12);
            overflow: hidden;
            width: 100%;
            border: 1px solid rgba(26, 26, 140, 0.12);
            padding: 3rem 2.5rem;
            box-sizing: border-box;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .portal-logo {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-blue);
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f0f4ff;
            color: var(--primary-blue);
            font-size: 35px;
        }

        .login-header h2 {
            font-size: 1.3rem;
            margin: 0;
            letter-spacing: 1px;
            color: var(--primary-blue);
            font-weight: 800;
            margin-bottom: 4px;
        }

        .login-header p {
            font-size: 0.8rem;
            color: var(--teal-green);
            margin-top: 0;
            margin-bottom: 0;
            font-weight: 600;
        }

        /* Centered Button Group Styles */
        .button-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .btn-login {
            width: 100%;
            max-width: 320px;
            background: var(--primary-blue);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-decoration: none;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .btn-login:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(26, 26, 140, 0.25);
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

        @media(max-width: 768px) {
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

    <!-- Topbar Section from index.php -->
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

    <!-- Navigation with Logo Section from index.php -->
    <nav class="main-nav">
        <div class="nav-left">
            <a href="index.php" class="btn-home" aria-label="Back to Home" title="Back to Home">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="logo-section" data-aos="fade-down" data-aos-duration="800">
                <img src="image/barangaylogo3.jpg" alt="Barangay Logo">
                <div class="logo-text">
                    <h2>BARANGAY MALITBOG</h2>
                    <small>MUNICIPALITY OF BONGABONG</small>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper with Unified Container Card -->
    <div class="main-content">
        <div class="portal-wrapper">
            <div class="portal-card-box" data-aos="fade-up" data-aos-duration="800">
                <div class="login-header">
                    <img src="image/barangaylogo3.jpg" alt="Barangay Logo" class="portal-logo">
                    <h2>BARANGAY PORTAL</h2>
                    <p>Please select your role to proceed</p>
                </div>

                <div class="button-group">
                    <a href="resident_portal.php" class="btn-login">
                        <i class="fa-solid fa-user" style="margin-right: 10px;"></i> Resident Login
                    </a>
                    <a href="official_portal.php" class="btn-login" style="background-color: #157347;">
                        <i class="fa-solid fa-user-shield" style="margin-right: 10px;"></i> Barangay Official
                    </a>
                    <a href="admin_login.php" class="btn-login" style="background-color: #a72837;">
                        <i class="fa-solid fa-lock" style="margin-right: 10px;"></i> Admin
                    </a>
                </div>
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
        AOS.init({
            once: true,
            offset: 50
        });
    </script>
</body>
</html>