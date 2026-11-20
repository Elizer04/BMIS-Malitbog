<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Malitbog - Contact Us</title>
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

        /* === CONTACT SECTION STYLING === */
        .contact-section {
            padding: 80px 10%;
            background-color: #f8f9fc;
            box-sizing: border-box;
            width: 100%;
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
            align-items: stretch;
        }

        .contact-info-side, .contact-form-side {
            background: #ffffff;
            padding: 35px 30px;
            border-radius: 16px;
            border: 1px solid rgba(26, 26, 140, 0.08);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-sizing: border-box;
        }

        .contact-info-side:hover, .contact-form-side:hover {
            box-shadow: 0 16px 35px rgba(26, 26, 140, 0.08);
            border-color: rgba(26, 26, 140, 0.2);
        }

        .contact-info-side h3, .contact-form-side h3 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1a1a8c;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        .contact-info-side > p {
            font-size: 13px;
            color: #555555;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .info-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .info-item i {
            width: 40px;
            height: 40px;
            background: rgba(26, 26, 140, 0.08);
            color: #1a1a8c;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
            transition: all 0.35s ease;
        }

        .info-item:hover i {
            background: linear-gradient(135deg, #1a1a8c 0%, #2e2ebd 100%);
            color: #ffffff;
            box-shadow: 0 6px 15px rgba(26, 26, 140, 0.25);
            transform: scale(1.05);
        }

        .info-item h4 {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a8c;
            margin-bottom: 4px;
        }

        .info-item p {
            font-size: 12.5px;
            color: #555555;
            line-height: 1.45;
            margin: 0;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #1a1a8c;
            margin-bottom: 6px;
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(26, 26, 140, 0.15);
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #333333;
            background: #fafbfd;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .form-group input:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #1a1a8c;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(26, 26, 140, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #1a1a8c 0%, #2e2ebd 100%);
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 4px 12px rgba(26, 26, 140, 0.2);
            width: 100%;
            justify-content: center;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 26, 140, 0.35);
        }

        .btn-signin, .back-to-top {
            transition: all 0.3s ease;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 26, 140, 0.3);
        }

        @media (max-width: 992px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .contact-section, .hero-banner {
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
        <a href="faq.php" class="nav-item">FAQ</a>
        <a href="contact.php" class="nav-item active">Contact</a>
        <a href="loginform.php" class="btn-signin"><i class="fa-solid fa-user"></i> Sign In</a>
    </div>
</nav>

<!-- Hero Section Banner -->
<header class="hero-banner">
    <div data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
        <h1>GET IN TOUCH</h1>
        <p class="hero-subtitle">Have questions, concerns, or need assistance? Reach out to the Barangay Malitbog team.</p>
    </div>
</header>

<!-- Contact Content Section -->
<section class="contact-section">
    <div class="contact-container">
        
        <!-- Left Side: Contact Information -->
        <div class="contact-info-side" data-aos="fade-right" data-aos-duration="1000">
            <h3>Contact Information</h3>
            <p>We are here to assist residents with inquiries regarding digital certificates, resident registry, clearance requests, and general barangay services.</p>
            
            <div class="info-item">
                <i class="fa-solid fa-location-dot"></i>
                <div>
                    <h4>Office Address</h4>
                    <p>Barangay Hall, Barangay Malitbog<br>Municipality of Bongabong, Oriental Mindoro 5211</p>
                </div>
            </div>

            <div class="info-item">
                <i class="fa-solid fa-envelope"></i>
                <div>
                    <h4>Email Support</h4>
                    <p>support@barangaymalitbog.ph<br>info@barangaymalitbog.ph</p>
                </div>
            </div>

            <div class="info-item">
                <i class="fa-solid fa-phone"></i>
                <div>
                    <h4>Contact Numbers</h4>
                    <p>Hotline: +63 (900) 000-0000<br>Landline: (043) 000-0000</p>
                </div>
            </div>

            <div class="info-item">
                <i class="fa-solid fa-clock"></i>
                <div>
                    <h4>Office Hours</h4>
                    <p>Monday to Friday: 8:00 AM – 5:00 PM<br>Closed during weekends and holidays.</p>
                </div>
            </div>
        </div>

        <!-- Right Side: Contact Form -->
        <div class="contact-form-side" data-aos="fade-left" data-aos-duration="1000">
            <h3>Send Us a Message</h3>
            <form action="#" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your contact number">
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="How can we help you?" required></textarea>
                </div>

                <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
            </form>
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

    // Close mobile menu automatically when a link is clicked
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            navLinksContainer.classList.remove('active');
        });
    });
</script>
</body>
</html>