<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Malitbog - Home</title>
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

        /* === CENTERED BADGE & HEADER === */
        .system-nav > div:first-child {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .core-features-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0 auto 15px auto;
            animation: softPulse 3s infinite ease-in-out;
        }

        /* === UNIFORM GRID & CONSISTENT SCALING === */
        .system-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
            align-items: stretch;
            width: 100%;
        }

        /* === SYSTEM CARDS & FEATURE GRID DIVS (Enhanced Polish) === */
        .system-card {
            background: #ffffff;
            padding: 20px 18px;
            border-radius: 16px;
            border: 1px solid rgba(26, 26, 140, 0.08);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            will-change: transform;
            position: relative;
            min-height: 185px;
            box-sizing: border-box;
        }

        .system-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 35px rgba(26, 26, 140, 0.1);
            border-color: rgba(26, 26, 140, 0.2);
        }

        .system-card:hover .card-icon-box {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, #1a1a8c 0%, #2e2ebd 100%);
            color: #ffffff;
            box-shadow: 0 6px 15px rgba(26, 26, 140, 0.25);
        }

        .card-icon-box {
            transition: all 0.35s ease;
            width: 40px;
            height: 40px;
            background: rgba(26, 26, 140, 0.08);
            color: #1a1a8c;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            margin-bottom: 14px;
        }

        .system-card h4 {
            font-size: 14.5px;
            font-weight: 700;
            color: #1a1a8c;
            margin-bottom: 8px;
            letter-spacing: -0.2px;
        }

        .system-card p {
            font-size: 12px;
            color: #555555;
            line-height: 1.45;
            margin: 0;
        }

        .card-number {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 11.5px;
            font-weight: 700;
            color: rgba(26, 26, 140, 0.2);
            letter-spacing: 0.5px;
        }

        @keyframes cardFadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-grid-fade {
            animation: cardFadeInUp 0.5s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

        @keyframes softPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        .btn-signin, .back-to-top {
            transition: all 0.3s ease;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 26, 140, 0.3);
        }

        /* === Slick-Style Custom Carousel Arrows === */
        .carousel-container {
            position: relative;
        }

        .carousel-arrow {
            position: absolute;
            top: 45%;
            transform: translateY(-50%);
            background: #ffffff;
            color: #1a1a8c;
            border: 2px solid rgba(26, 26, 140, 0.15);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(26, 26, 140, 0.15);
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .carousel-arrow.prev {
            left: -25px;
        }

        .carousel-arrow.next {
            right: -25px;
        }

        .carousel-arrow:hover {
            background: #1a1a8c;
            color: #ffffff;
            border-color: #1a1a8c;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px rgba(26, 26, 140, 0.3);
        }

        @media (max-width: 768px) {
            .carousel-arrow.prev {
                left: 5px;
            }
            .carousel-arrow.next {
                right: 5px;
            }
        }

        /* === Numbered Pagination Dots Styling === */
        .dots {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
        }

        .dots button {
            background: #f0f2f5;
            border: 2px solid rgba(26, 26, 140, 0.15);
            color: #1a1a8c;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .dots button:hover {
            border-color: #1a1a8c;
            background: rgba(26, 26, 140, 0.1);
            transform: translateY(-2px);
        }

        .dots button.active {
            background: #1a1a8c;
            color: #ffffff;
            border-color: #1a1a8c;
            width: 48px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(26, 26, 140, 0.3);
        }

        /* === ABOUT PLATFORM LAYOUT (Consistent 4-column span scaling) === */
        .about-platform-wrapper {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
            align-items: stretch;
            width: 100%;
            grid-column: 1 / -1;
        }

        /* Main Brand Card spans 2 columns - matched with consistent grid card dimensions */
        .about-brand-card {
            grid-column: span 2;
            background: linear-gradient(135deg, #1a1a8c 0%, #2e2ebd 100%);
            color: #ffffff;
            padding: 24px 20px;
            border-radius: 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(26, 26, 140, 0.18);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            min-height: 185px;
            box-sizing: border-box;
        }

        .about-brand-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 35px rgba(26, 26, 140, 0.28);
        }

        .about-brand-card::after {
            content: '';
            position: absolute;
            bottom: -40px;
            right: -40px;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .about-main-logo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            margin-bottom: 10px;
        }

        .about-brand-card h3 {
            font-size: 15.5px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        .about-brand-card p {
            font-size: 11.5px;
            opacity: 0.9;
            line-height: 1.4;
            margin: 0;
            color: #ffffff;
        }

        /* Individual about feature boxes match system-card scaling 1-to-1 */
        .about-feature-box {
            background: #ffffff;
            padding: 20px 18px;
            border-radius: 16px;
            border: 1px solid rgba(26, 26, 140, 0.08);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            min-height: 185px;
            box-sizing: border-box;
        }

        .about-feature-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 35px rgba(26, 26, 140, 0.12);
            border-color: rgba(26, 26, 140, 0.2);
        }

        .about-feature-box:hover .about-feature-icon {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, #1a1a8c 0%, #2e2ebd 100%);
            color: #ffffff;
            box-shadow: 0 6px 15px rgba(26, 26, 140, 0.25);
        }

        .about-feature-icon {
            transition: all 0.35s ease;
            width: 40px;
            height: 40px;
            background: rgba(26, 26, 140, 0.08);
            color: #1a1a8c;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            margin-bottom: 14px;
        }

        .about-feature-box h4 {
            font-size: 14.5px;
            font-weight: 700;
            color: #1a1a8c;
            margin-bottom: 8px;
            letter-spacing: -0.2px;
        }

        .about-feature-box p {
            font-size: 12px;
            color: #555555;
            line-height: 1.45;
            margin: 0;
        }

        @media (max-width: 992px) {
            .about-platform-wrapper, .system-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .about-brand-card {
                grid-column: span 2;
            }
        }

        @media (max-width: 576px) {
            .about-platform-wrapper, .system-grid {
                grid-template-columns: 1fr;
            }
            .about-brand-card {
                grid-column: span 1;
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
<div>

</div>
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
        <a href="#home" class="nav-item active">Home</a>
        <a href="#governance-section" class="nav-item" data-slide="0">Barangay Services</a>
        <a href="#governance-section" class="nav-item" data-slide="1">How It Works</a>
        <a href="#governance-section" class="nav-item" data-slide="2">About</a>
        <a href="loginform.php" class="btn-signin"><i class="fa-solid fa-user"></i> Sign In</a>
    </div>
</nav>

<!-- Hero Section Banner -->
<header id="home" class="hero-banner">
    <div class="hero-image-container" data-aos="fade-right" data-aos-duration="1000" data-aos-delay="200">
        <img src="image/backgroundphoto1.jpg" alt="Barangay Building">
    </div>
    <div class="hero-text-side" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="300">
        <h1>WELCOME TO</h1>
        <h2>E-BARANGAY</h2>
        <p class="hero-subtitle">A WEB-BASED MANAGEMENT INFORMATION SYSTEM FOR BARANGAY MALITBOG</p>

        <div class="seals-row">
            <img src="image/ebarangay.jpg" class="seal" alt="Barangay Seal 2">
        </div>
    </div>
</header>

<!-- E-Governance Systems Carousel Section -->
<section id="governance-section" class="system-nav" style="scroll-margin-top: 100px;">
    <div data-aos="fade-up" data-aos-duration="800">
        <div class="core-features-badge" id="slideBadge">
            <i class="fa-solid fa-wand-magic-sparkles"></i> CORE FEATURES
        </div>
        <div id="slideTitleContainer">
            <h2 class="services-main-title" id="slideTitle">One system for everyday <span>barangay services</span></h2>
        </div>
        <p class="services-description" id="slideSubtitle">
            Built to make community records easier to manage, public services faster to deliver, and information more accessible to authorized users.
        </p>
    </div>

    <div class="carousel-container" data-aos="fade-up" data-aos-duration="1000">
        <!-- React-Slick Style Custom Arrow Prev -->
        <button class="carousel-arrow prev" id="prevSlide" aria-label="Previous slide">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        
        <!-- Dynamic Grid Container -->
        <div class="system-grid" id="featureGrid">
            <!-- Cards/Containers will be dynamically injected here -->
        </div>

        <!-- React-Slick Style Custom Arrow Next -->
        <button class="carousel-arrow next" id="nextSlide" aria-label="Next slide">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        
        <!-- Numbered Pagination Dots -->
        <div class="dots" id="slideDots" aria-label="Choose feature slide"></div>
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
                <li><a href="#governance-section" onclick="renderCarouselSlide(0)">Features</a></li>
                <li><a href="admin_login.php">Admin Portal</a></li>
                <li><a href="resident_portal.php">Resident Services</a></li>
                <li><a href="#governance-section" onclick="renderCarouselSlide(1)">How It Works</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>INFORMATION</h4><br>
            <ul>
                <li><a href="#governance-section" onclick="renderCarouselSlide(2)">About E-Barangay</a></li>
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

    // Handle navigation link clicks for smooth scrolling and dynamic carousel slide switching
    const navItems = document.querySelectorAll('.nav-links .nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            navLinksContainer.classList.remove('active');

            if (item.hasAttribute('data-slide')) {
                const slideIndex = parseInt(item.getAttribute('data-slide'), 10);
                renderCarouselSlide(slideIndex);
            }
        });
    });

    // Scroll Spy synchronized with current carousel slide
    window.addEventListener('scroll', () => {
        const scrollPos = window.pageYOffset;
        const govSection = document.getElementById('governance-section');

        if (govSection && scrollPos >= (govSection.offsetTop - 200)) {
            const activeNav = document.querySelector(`.nav-links a[data-slide="${currentSlide}"]`);
            if (activeNav) {
                navItems.forEach(i => i.classList.remove('active'));
                activeNav.classList.add('active');
            }
        } else {
            const homeNav = document.querySelector('.nav-links a[href="#home"]');
            if (homeNav) {
                navItems.forEach(i => i.classList.remove('active'));
                homeNav.classList.add('active');
            }
        }
    });

    const featureSlides = [
        {
            badge: '<i class="fa-solid fa-wand-magic-sparkles"></i> CORE FEATURES',
            title: 'One system for everyday <span>barangay services</span>',
            subtitle: 'Built to make community records easier to manage, public services faster to deliver, and information more accessible to authorized users.',
            cards: [
                ['fa-address-card', 'Resident Registry', 'Maintain organized resident profiles, demographic details, beneficiary status, and household records.', '01'],
                ['fa-file-invoice', 'Digital Certificates', 'Create, review, approve, preview, print, and release barangay documents using reusable templates.', '02'],
                ['fa-gavel', 'Judicial System', 'Manage community dispute resolutions, blotter records, and amicable settlement hearings efficiently.', '03'],
                ['fa-square-plus', 'Health Logbook', 'Track medical consultations, vaccination logs, and health center patient records securely.', '04']
            ]
        },
        {
            badge: '<i class="fa-solid fa-diagram-project"></i> HOW ebarangay WORKS',
            title: 'A clear path from registration<br>to <span>digital service</span>',
            subtitle: 'The platform combines account verification, barangay assignment, protected access, and service processing in one coordinated workflow.',
            cards: [
                ['fa-user-plus', 'Register', 'A resident creates an account using their community and contact information.', '01'],
                ['fa-circle-check', 'Verify', 'The assigned barangay reviews and approves the resident account.', '02'],
                ['fa-grip', 'Access Services', 'Approved users access announcements, applications, messages, and profile tools.', '03'],
                ['fa-circle-check', 'Receive Updates', 'Residents receive status updates while administrators manage requests and records.', '04']
            ]
        },
        {
            badge: '<i class="fa-solid fa-circle-info"></i> ABOUT THE PLATFORM',
            title: 'ABOUT <span>E-BARANGAY</span>',
            subtitle: 'Technology that supports responsive governance: Designed to improve how barangays organize information, provide resident services, protect data separation, and communicate with communities.',
            type: 'about-slide',
            cards: [
                ['fa-users', 'Community-centered', 'Features are organized around actual barangay and resident service needs.'],
                ['fa-shield-halved', 'Secure by Role', 'Access is limited according to resident, barangay admin, or super admin permissions.'],
                ['fa-network-wired', 'Barangay Isolated', 'Resident information belongs only to the correct assigned barangay.'],
                ['fa-rocket', 'Built to Expand', 'The platform supports additional local services as barangay needs evolve.']
            ]
        }
    ];

    let currentSlide = 0;
    const gridContainer = document.getElementById('featureGrid');
    const slideBadge = document.getElementById('slideBadge');
    const slideTitle = document.getElementById('slideTitle');
    const slideSubtitle = document.getElementById('slideSubtitle');
    const dotsContainer = document.getElementById('slideDots');

    function renderCarouselSlide(index) {
        currentSlide = (index + featureSlides.length) % featureSlides.length;
        const slide = featureSlides[currentSlide];

        slideBadge.innerHTML = slide.badge;
        slideTitle.innerHTML = slide.title;
        slideSubtitle.innerHTML = slide.subtitle;

        gridContainer.classList.remove('animate-grid-fade');
        void gridContainer.offsetWidth;
        gridContainer.classList.add('animate-grid-fade');

        if (slide.type === 'about-slide') {
            gridContainer.innerHTML = `
                <div class="about-platform-wrapper">
                    <div class="about-brand-card">
                        <img src="image/ebarangay.jpg" alt="E-Barangay Logo" class="about-main-logo">
                        <h3>E-BARANGAY</h3>
                        <p>Empowering local governance and community transactions through secure, reliable digital solutions.</p>
                    </div>
                    ${slide.cards.map(([icon, title, desc]) => `
                        <div class="about-feature-box">
                            <div class="about-feature-icon">
                                <i class="fa-solid ${icon}"></i>
                            </div>
                            <h4>${title}</h4>
                            <p>${desc}</p>
                        </div>
                    `).join('')}
                </div>
            `;
        } else {
            gridContainer.innerHTML = slide.cards.map(([icon, title, desc, num]) => `
                <div class="system-card">
                    <div class="card-icon-box">
                        <i class="fa-solid ${icon}"></i>
                    </div>
                    <h4>${title}</h4>
                    <p>${desc}</p>
                    <div class="card-number">${num}</div>
                </div>
            `).join('');
        }

        dotsContainer.innerHTML = featureSlides.map((_, i) => `
            <button class="${i === currentSlide ? 'active' : ''}" aria-label="Go to slide ${i + 1}">${i + 1}</button>
        `).join('');

        [...dotsContainer.children].forEach((dot, i) => {
            dot.onclick = () => renderCarouselSlide(i);
        });

        if (window.pageYOffset >= document.getElementById('governance-section').offsetTop - 200) {
            navItems.forEach(item => {
                if (item.hasAttribute('data-slide')) {
                    item.classList.remove('active');
                    if (parseInt(item.getAttribute('data-slide'), 10) === currentSlide) {
                        item.classList.add('active');
                    }
                }
            });
        }
    }

    document.getElementById('prevSlide').onclick = () => renderCarouselSlide(currentSlide - 1);
    document.getElementById('nextSlide').onclick = () => renderCarouselSlide(currentSlide + 1);

    renderCarouselSlide(0);
</script>
</body>
</html>