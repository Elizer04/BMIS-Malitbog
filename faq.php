<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Malitbog - Frequently Asked Questions</title>
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
            justify-content: center;
            text-align: center;
            flex-wrap: wrap;
            gap: 30px;
            width: 100%;
            box-sizing: border-box;
        }

        .hero-banner h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .hero-banner .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.5;
        }

        /* === FAQ SECTION STYLING === */
        .faq-section {
            padding: 80px 10%;
            background-color: #f8f9fc;
            box-sizing: border-box;
            width: 100%;
        }

        .faq-container {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .faq-item {
            background: #ffffff;
            padding: 24px 28px;
            border-radius: 16px;
            border: 1px solid rgba(26, 26, 140, 0.08);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            cursor: pointer;
        }

        .faq-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(26, 26, 140, 0.08);
            border-color: rgba(26, 26, 140, 0.2);
        }

        .faq-question {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .faq-question h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a8c;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.2px;
        }

        .faq-question h3 i {
            color: #1a1a8c;
            font-size: 18px;
            background: rgba(26, 26, 140, 0.08);
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .faq-item:hover .faq-question h3 i {
            background: #1a1a8c;
            color: #ffffff;
        }

        .faq-toggle-icon {
            color: #1a1a8c;
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease-in-out, padding 0.35s ease-in-out;
            padding: 0 0 0 40px;
        }

        .faq-answer p {
            font-size: 13.5px;
            color: #555555;
            line-height: 1.6;
            margin: 0;
        }

        .faq-item.active .faq-answer {
            max-height: 200px;
            padding-top: 15px;
        }

        .faq-item.active .faq-toggle-icon {
            transform: rotate(180deg);
        }

        .btn-signin, .back-to-top {
            transition: all 0.3s ease;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 26, 140, 0.3);
        }

        @media (max-width: 768px) {
            .faq-section, .hero-banner {
                padding: 40px 5%;
            }
            .hero-banner h1 {
                font-size: 2rem;
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
        <a href="faq.php" class="nav-item active">FAQ</a>
        <a href="index.php#governance-section" class="nav-item">About</a>
        <a href="loginform.php" class="btn-signin"><i class="fa-solid fa-user"></i> Sign In</a>
    </div>
</nav>

<!-- Hero Section Banner -->
<header class="hero-banner">
    <div data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
        <h1>FREQUENTLY ASKED QUESTIONS</h1>
        <p class="hero-subtitle">Everything you need to know about the E-Barangay Management Information System for Barangay Malitbog</p>
    </div>
</header>

<!-- FAQ Content Section -->
<section class="faq-section">
    <div class="faq-container">
        
        <div class="faq-item" data-aos="fade-up" data-aos-delay="100">
            <div class="faq-question">
                <h3><i class="fa-solid fa-circle-question"></i> What is the E-Barangay Management Information System (BMIS)?</h3>
                <i class="fa-solid fa-chevron-down faq-toggle-icon"></i>
            </div>
            <div class="faq-answer">
                <p>It is a secure digital platform designed for Barangay Malitbog (Municipality of Bongabong) to organize community records, deliver public services faster, and make information more accessible to authorized users.</p>
            </div>
        </div>

        <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
            <div class="faq-question">
                <h3><i class="fa-solid fa-circle-question"></i> What core features are included in the system?</h3>
                <i class="fa-solid fa-chevron-down faq-toggle-icon"></i>
            </div>
            <div class="faq-answer">
                <p>The platform features four core modules: Resident Registry (for profiles and households), Digital Certificates (for document processing and printing), Judicial System (for dispute resolutions and blotters), and Health Logbook (for medical consultations and vaccination logs).</p>
            </div>
        </div>

        <div class="faq-item" data-aos="fade-up" data-aos-delay="300">
            <div class="faq-question">
                <h3><i class="fa-solid fa-circle-question"></i> How do I register and access resident services?</h3>
                <i class="fa-solid fa-chevron-down faq-toggle-icon"></i>
            </div>
            <div class="faq-answer">
                <p>First, a resident creates an account using their community and contact information. Once the assigned barangay reviews and approves your account, you can sign in to access announcements, applications, messages, and profile tools.</p>
            </div>
        </div>

        <div class="faq-item" data-aos="fade-up" data-aos-delay="400">
            <div class="faq-question">
                <h3><i class="fa-solid fa-circle-question"></i> How is my personal data and account security protected?</h3>
                <i class="fa-solid fa-chevron-down faq-toggle-icon"></i>
            </div>
            <div class="faq-answer">
                <p>Access is strictly limited according to assigned user roles (resident, barangay admin, or super admin). Additionally, resident information belongs only to the correct assigned barangay to ensure isolation and data privacy.</p>
            </div>
        </div>

        <div class="faq-item" data-aos="fade-up" data-aos-delay="500">
            <div class="faq-question">
                <h3><i class="fa-solid fa-circle-question"></i> Who can use the Admin Portal?</h3>
                <i class="fa-solid fa-chevron-down faq-toggle-icon"></i>
            </div>
            <div class="faq-answer">
                <p>The Admin Portal is restricted to authorized barangay personnel and administrators to manage service requests, resident accounts, and local records.</p>
            </div>
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
                <li><a href="index.php#governance-section">How It Works</a></li>
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

    // Interactive FAQ Accordion Script
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const questionBox = item.querySelector('.faq-question');
        questionBox.addEventListener('click', () => {
            // Optional: Close other active items if you want single-expansion accordion behavior
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                }
            });
            item.classList.toggle('active');
        });
    });
</script>
</body>
</html>