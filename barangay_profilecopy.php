<?php
include 'db_connect.php';

session_start();

// Fetch current profile data matching table schema (supporting user_id or id check as needed)
$stmt = $pdo->query("SELECT * FROM barangay_profile LIMIT 1");
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    $profile = [
        'barangay_name' => 'MALITBOG',
        'municipality' => 'BONGABONG',
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
    <title>Barangay Profile - Barangay <?php echo htmlspecialchars($profile['barangay_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #000066;
            --secondary-blue: #1a1a8c;
            --accent-gold: #ffcc00;
            --text-white: #ffffff;
            --white: #ffffff;
            --bg-light: #f4f7f6;
            --pill-light-blue: #2c72a8;
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
            color: #333;
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        .topbar {
            background: var(--secondary-blue);
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
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--bg-light);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .back-link:hover { 
            background: var(--secondary-blue);
            color: white; 
        }

        .logo-section img {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-blue);
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

        .hero-banner {
            background: linear-gradient(rgba(0, 0, 102, 0.7), rgba(0, 0, 102, 0.7)), 
                        url('image/backgroundphoto1.jpg') no-repeat center center/cover;
            padding: 60px 10%;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-msg h1 { margin: 0; font-size: 2.2rem; }
        .welcome-msg h1 span { color: var(--accent-gold); }
        .welcome-msg p { font-size: 1.1rem; margin-top: 10px; opacity: 0.9; }

        .container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 30px auto;
            align-items: start;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
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

        /* Professional Footer Styles */
        .site-footer {
            background-color: #120b13; 
            color: #d1d5db;
            padding: 60px 40px 30px 40px;
            margin: 80px -15px -15px -15px;
            font-size: 0.85rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1.5fr;
            gap: 40px;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-col h3, .footer-col h4 {
            color: white;
            font-weight: 700;
            margin-top: 0;
        }

        .footer-col h3 {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .footer-col h3 img {
            height: 30px;
            width: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .footer-col h4 {
            font-size: 0.85rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
            color: #9ca3af;
        }

        .footer-col p {
            color: #9ca3af;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.8rem;
        }

        .footer-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul li a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.85rem;
        }

        .footer-col ul li a:hover {
            color: white;
        }

        .footer-social-icons {
            display: flex;
            gap: 15px;
        }

        .footer-social-icons a {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .footer-social-icons a:hover {
            background: var(--secondary-blue);
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 30px auto 0 auto;
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

        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--secondary-blue);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 999;
        }

        @media(max-width: 900px) { 
            .container { grid-template-columns: 1fr; } 
            .topbar { display: none; }
            .main-nav { padding: 8px 12px; }
            .nav-links { gap: 15px; }
            .footer-container { grid-template-columns: 1fr 1fr; }
            .svg-connector { display: none; }
            .hero-banner { padding: 40px 5%; flex-direction: column; text-align: center; gap: 20px; }
        }
    </style>
</head>
<body>

    <!-- Top bar -->
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
                <span style="font-size: 0.8rem;"><i class="fa fa-user"></i> Resident Portal View</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="main-nav">
        <div class="logo-section">
            <a href="official_dashboard.php" class="back-link" title="Back to Portal">
                <i class="fa fa-arrow-left"></i>
            </a>
            <img src="image/barangaylogo3.jpg" alt="Barangay Logo">
            <div class="logo-text">
                <h2>BARANGAY <?php echo htmlspecialchars($profile['barangay_name']); ?></h2>
                <small>MUNICIPALITY OF <?php echo htmlspecialchars($profile['municipality']); ?></small>
            </div>
        </div>
        <div class="nav-links">
            <a href="official_dashboard.php" class="rd-nav-item custom-home-style" title="Resident Home">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
        </div>
    </nav>

    <!-- Hero Banner Profile -->
    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>BARANGAY <span>PROFILE</span></h1>
            <p>Welcome to Barangay <?php echo htmlspecialchars($profile['barangay_name']); ?> Official Information & Officials Directory</p>
        </div>
        <div style="text-align: right;">
            <div id="current-date" style="font-weight: bold; font-size: 1.1rem; color: var(--accent-gold);"></div>
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
            
            <!-- Profile & Background Card -->
            <div class="card">
                <h3 class="section-title">
                    <span>Barangay Profile & Background</span>
                </h3>

                <div class="vision-mission-grid">
                    <div>
                        <h4 class="vm-heading">OVERVIEW</h4>
                        <p class="vm-text"><?php echo nl2br(htmlspecialchars($profile['overview_text'])); ?></p>
                    </div>
                    <div style="margin-top: 20px;">
                        <h4 class="vm-heading">LOCAL ECONOMY & GEOGRAPHY</h4>
                        <p class="vm-text"><?php echo nl2br(htmlspecialchars($profile['economy_geography_text'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Barangay Officials Organizational Chart Card -->
            <div class="card">
                <h3 class="section-title">
                    <span>Barangay Officials</span>
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
                                if (!container || !svg) return;

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

            </div>
        </div>
    </div>

    <!-- Professional Footer Section -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-col">
                <h3><img src="image/bmis.jpg" alt="BMIS Logo"> BMIS</h3>
                <p>BARANGAY MANAGEMENT & INFORMATION SYSTEM</p>
                <p>A secure digital platform supporting resident records, barangay services, communication, and local governance.</p>
                <div class="footer-social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-facebook-messenger"></i></a>
                    <a href="#"><i class="fa-solid fa-globe"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>PLATFORM</h4>
                <ul>
                    <li><a href="admin_login.php">Admin Portal</a></li>
                    <li><a href="resident_portal.php">Resident Services</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>INFORMATION</h4>
                <ul>
                    <li><a href="barangay_profile.php">About BMIS</a></li>
                    <li><a href="#">Support</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>SERVICE NOTICE</h4>
                <p>Access is limited to verified users and authorized barangay personnel.</p>
            </div>
        </div>
        <div class="footer-bottom">
            <div>&copy; 2026 BMIS. All rights reserved.</div>
            <div class="footer-bottom-links">
                <a href="#">Data Privacy</a>
                <a href="#">Terms of Use</a>
            </div>
        </div>
    </footer>

    <a href="#" class="back-to-top">
        <i class="fa-solid fa-arrow-up"></i>
    </a>

    <script>
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateElement = document.getElementById('current-date');
        if(dateElement) {
            dateElement.innerHTML = new Date().toLocaleDateString(undefined, options);
        }
    </script>
</body>
</html>