<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Malitbog - Support & Help Desk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hero-banner {
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

        /* === SUPPORT PAGE SPECIFIC STYLING === */
        .support-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 10%;
            background-color: #f8f9fc;
            box-sizing: border-box;
            width: 100%;
        }

        .support-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 22px;
            margin-top: 40px;
            align-items: stretch;
        }

        .support-card {
            background: #ffffff;
            padding: 30px 24px;
            border-radius: 16px;
            border: 1px solid rgba(26, 26, 140, 0.08);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            box-sizing: border-box;
        }

        .support-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 35px rgba(26, 26, 140, 0.1);
            border-color: rgba(26, 26, 140, 0.2);
        }

        .support-card:hover .card-icon-box {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, #1a1a8c 0%, #2e2ebd 100%);
            color: #ffffff;
            box-shadow: 0 6px 15px rgba(26, 26, 140, 0.25);
        }

        .support-card .card-icon-box {
            transition: all 0.35s ease;
            width: 50px;
            height: 50px;
            background: rgba(26, 26, 140, 0.08);
            color: #1a1a8c;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin: 0 auto 18px auto;
        }

        .support-card h4 {
            font-size: 15px;
            font-weight: 700;
            color: #1a1a8c;
            margin-bottom: 10px;
            letter-spacing: -0.2px;
        }

        .support-card p {
            font-size: 12.5px;
            color: #555555;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .support-link-btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #1a1a8c 0%, #2e2ebd 100%);
            color: #ffffff;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 4px 12px rgba(26, 26, 140, 0.2);
            width: 100%;
            box-sizing: border-box;
        }

        .support-link-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 26, 140, 0.35);
        }

        .core-features-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0 auto 15px auto;
        }

        .btn-signin, .back-to-top {
            transition: all 0.3s ease;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 26, 140, 0.3);
        }

        @media (max-width: 768px) {
            .support-container {
                padding: 40px 5%;
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
    <div></div>
</div>

<nav class="main-nav">
    <div class="logo-section" data-aos="fade-down" data-aos-duration="800">
        <img src="image/barangaylogo3.jpg" alt="Barangay Logo">
        <div class="logo-text">
            <h2>BARANGAY MALITBOG</h2>
            <small>MUNICIPALITY OF BONGABONG</small>
        </div>
    </div>

    <button class="menu-toggle" id="mobile-menu-btn" aria-label="Toggle Navigation">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div class="nav-links" id="nav-links-container" data-aos="fade-down" data-aos-duration="1000">
        <a href="index.php" class="nav-item">Home</a>
        <a href="index.php#governance-section" class="nav-item">Barangay Services</a>
        <a href="faq.php" class="nav-item">FAQ</a>
        <a href="contact.php" class="nav-item">Contact</a>
        <a href="loginform.php" class="btn-signin"><i class="fa-solid fa-user"></i> Sign In</a>
    </div>
</nav>

<!-- Hero Section Banner -->
<header class="hero-banner">
    <div class="hero-image-container" data-aos="fade-right" data-aos-duration="1000" data-aos-delay="200">
        <img src="image/backgroundphoto1.jpg" alt="Barangay Support">
    </div>
    <div class="hero-text-side" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="300">
        <h1>HELP & SUPPORT</h1>
        <h2>E-BARANGAY DESK</h2>
        <p class="hero-subtitle">We are here to assist you with system navigation, account verification, and barangay services.</p>

        <div class="seals-row">
            <img src="image/ebarangay.jpg" class="seal" alt="Barangay Seal 2">
        </div>
    </div>
</header>

<!-- Support Options Section -->
<section class="support-container">
    <div style="text-align: center;" data-aos="fade-up" data-aos-duration="800">
        <div class="core-features-badge">
            <i class="fa-solid fa-life-ring"></i> ASSISTANCE CHANNELS
        </div>
        <h2 class="services-main-title">How can we help you <span>today?</span></h2>
        <p class="services-description" style="max-width: 700px; margin: 0 auto;">
            Choose from the options below to find answers, review common guidelines, or get in touch directly with the Barangay Malitbog support staff.
        </p>
    </div>

    <div class="support-grid">
        <!-- FAQ Card -->
        <div class="support-card" data-aos="fade-up" data-aos-delay="100">
            <div>
                <div class="card-icon-box">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h4>Frequently Asked Questions</h4>
                <p>Browse through answers regarding account registration, digital certificate requests, and resident verification processes.</p>
            </div>
            <a href="faq.php" class="support-link-btn">View FAQs</a>
        </div>

        <!-- Contact Helpdesk Card -->
        <div class="support-card" data-aos="fade-up" data-aos-delay="200">
            <div>
                <div class="card-icon-box">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h4>Contact Barangay Hall</h4>
                <p>Send a direct message or visit the official Barangay Malitbog hall for hands-on assistance and document inquiries.</p>
            </div>
            <a href="contact.php" class="support-link-btn">Contact Us</a>
        </div>

        <!-- Privacy Policy Card -->
        <div class="support-card" data-aos="fade-up" data-aos-delay="300">
            <div>
                <div class="card-icon-box">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h4>Privacy Policy</h4>
                <p>Review how your personal information and resident data are securely collected, handled, and protected under privacy guidelines.</p>
            </div>
            <a href="privacy.php" class="support-link-btn">Privacy Policy</a>
        </div>

        <!-- Terms of Use Card -->
        <div class="support-card" data-aos="fade-up" data-aos-delay="400">
            <div>
                <div class="card-icon-box">
                    <i class="fa-solid fa-file-contract"></i>
                </div>
                <h4>Terms of Use</h4>
                <p>Understand the platform user policies, user responsibilities, and guidelines for accessing digital barangay portal services.</p>
            </div>
            <a href="terms.php" class="support-link-btn">Terms of Use</a>
        </div>
    </div>
</section>

<!-- Professional Footer Section -->
<footer class="site-footer" data-aos="fade-up" data-aos-duration="1000">
    <div class="footer-container">
        <div class="footer-col">
            <h3><img src="image/barangaylogo3.jpg" alt="ebarangay Logo"> E-Barangay</h3>
            <p>BARANGAY MANAGEMENT INFORMATION SYSTEM</p>
            <p>A secure digital platform supporting resident records, barangay services, communication, and local governance.</p>
            <p>Bridging barangays and residents through secure technology, reliable data, and responsive public service.</p>
        </div>

        <div class="footer-col">
            <h4>PLATFORM</h4><br>
            <ul>
                <li><a href="index.php#governance-section">Features</a></li>
                <li><a href="admin_login.php">Admin Portal</a></li>
                <li><a href="resident_portal.php">Resident Services</a></li>
                <li><a href="index.php#how-it-works">How It Works</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>INFORMATION</h4><br>
            <ul>
                <li><a href="index.php#governance-section">About E-Barangay</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="resident_portal.php">Sign In</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>SERVICE NOTICE</h4><br>
            <p>Access is limited to verified users and authorized barangay personnel.</p>
            <p>Records are protected according to assigned barangay and system role.</p>
        </div>
    </div>

    <div class="footer-bottom">
        <div>&copy; 2026 e-barangay. All rights reserved.</div>
        <div class="footer-bottom-links">
            <a href="privacy.php">Data Privacy</a>
            <a href="terms.php">Terms of Use</a>
            <a href="support.php">Support</a>
        </div>
    </div>
</footer>

<!-- Floating Back to Top Anchor -->
<a href="#" class="back-to-top">
    <i class="fa-solid fa-arrow-up"></i>
</a>

<!-- AOS (Animate on Scroll) Library JS Script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
    // Initialize AOS animations
    AOS.init({
        once: true,
        offset: 100
    });

    // Mobile Menu Toggle Script
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navLinksContainer = document.getElementById('nav-links-container');

    mobileMenuBtn.addEventListener('click', () => {
        navLinksContainer.classList.toggle('active');
    });

    // Close mobile menu automatically when a link is clicked
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            navLinksContainer.classList.remove('active');
        });
    });
</script>
</body>
</html>