<?php

session_start();

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
include 'db_connect.php';

// HANDLE DIRECT PRINT/DOWNLOAD REQUESTS (WORD TEMPLATE OR RECEIPT)
if (isset($_GET['action']) && ($_GET['action'] === 'print' || $_GET['action'] === 'print_receipt') && isset($_GET['id']) && isset($_GET['type'])) {
    $print_id = mysqli_real_escape_string($conn, $_GET['id']);
    $print_type = mysqli_real_escape_string($conn, $_GET['type']);
    $action_mode = $_GET['action'];
    
    // Determine table based on certificate type including general & newly added certification types
    $table_name = "barangay_clearance";
    if ($print_type === 'residency') {
        $table_name = "cert_of_residency";
    } elseif ($print_type === 'indigency') {
        $table_name = "cert_of_indigency";
    } elseif ($print_type === 'business_cert') {
        $table_name = "business_certification";
    } elseif ($print_type === 'non_tenancy') {
        $table_name = "certification_of_non_tenancy";
    } elseif ($print_type === 'same_person') {
        $table_name = "one_same_person";
    } elseif ($print_type === 'income') {
        $table_name = "livelihood_income";
    } elseif ($print_type === 'property_agricultural') {
        $table_name = "property_agricultural_ownership";
    } elseif ($print_type === 'widening_road') {
        $table_name = "road_widening_boundaries";
    } elseif ($print_type === 'family_history') {
        $table_name = "family_residency_history";
    } elseif ($print_type === 'good_moral') {
        $table_name = "certificate_of_good_moral";
    } elseif ($print_type === 'unemployment') {
        $table_name = "certificate_of_unemployment";
    } elseif ($print_type === 'business_closure') {
        $table_name = "certificate_of_business_closure";
    } elseif ($print_type === 'others') {
        $table_name = "other_certifications";
    }

    $print_query = "SELECT * FROM $table_name WHERE resident_id = '$print_id' LIMIT 1";
    $print_result = mysqli_query($conn, $print_query);
    $doc = mysqli_fetch_assoc($print_result);

    if (!$doc) {
        die("<script>alert('Document record not found.'); window.close();</script>");
    }
    
    // ==================== SEPARATE VIEW 1: DIRECT PRINT CERTIFICATE VIEW ====================
    if ($action_mode === 'print') {
        $full_name = strtoupper(htmlspecialchars($doc['full_name'] ?? ''));
        $date_day = date('j', strtotime($doc['created_at']));
        $date_month = date('F', strtotime($doc['created_at']));
        $date_year = date('Y', strtotime($doc['created_at']));
        $date_issued = date('jS \d\a\y \o\f F, Y', strtotime($doc['created_at']));
        $purpose_cat = htmlspecialchars($doc['purpose_cat'] ?? '');
        $purpose_detail = htmlspecialchars($doc['purpose_detail'] ?? '');
        $purpose_val = trim($purpose_cat . ' - ' . $purpose_detail, ' -');
        if (empty($purpose_val)) { $purpose_val = 'Any Legal Purposes'; }
        
        $doc_title = ucwords(str_replace('_', ' ', $print_type));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Certificate - <?php echo $doc_title; ?> - Barangay Malitbog</title>
    <style>
        @page {
            size: portrait;
            margin: 10mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 30px;
            color: #000;
            background: #fff;
            line-height: 1.6;
        }
        .cert-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            position: relative;
        }
        .cert-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            line-height: 1.3;
            margin-bottom: 25px;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
        }
        .cert-header img.logo-img {
            width: 85px;
            height: 85px;
            object-fit: contain;
        }
        .cert-header-text {
            flex-grow: 1;
            text-align: center;
        }
        .cert-header-text h5 {
            margin: 2px 0;
            font-size: 0.85rem;
            font-weight: normal;
        }
        .cert-header-text h4 {
            margin: 2px 0;
            font-size: 0.95rem;
            font-weight: bold;
        }
        .cert-header-text h3 {
            margin: 4px 0;
            font-size: 1.1rem;
            font-weight: bold;
            color: #000066;
        }
        .cert-title {
            text-align: center;
            text-transform: uppercase;
            font-size: 1.4rem;
            font-weight: bold;
            margin: 25px 0;
            letter-spacing: 1px;
        }
        .cert-body {
            font-size: 1.1rem;
            text-align: justify;
            margin-bottom: 40px;
        }
        .cert-body p {
            text-indent: 40px;
            margin-bottom: 18px;
        }
        .cert-footer {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }
        .signatory-box {
            text-align: center;
            width: 280px;
        }
        .signatory-box .name {
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            display: block;
            font-size: 1.05rem;
        }
        .signatory-box .title {
            font-size: 0.95rem;
            margin-top: 4px;
        }
        .no-print {
            text-align: center;
            margin-bottom: 30px;
        }
        .print-btn {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
            .cert-container { width: 100%; max-width: none; border: none; padding: 10px; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button class="print-btn" onclick="window.print()"><i class="fa fa-print"></i> Print Document</button>
        <button class="print-btn" style="background:#555; margin-left:10px;" onclick="if(window.opener){window.close();}else{window.location.href='admincertification_overview.php';}">Close Window</button>
    </div>

    <div class="cert-container">
        <!-- OFFICIAL HEADER -->
        <div class="cert-header">
            <img src="image/barangaylogo3.jpg" alt="Barangay Logo" class="logo-img">
            <div class="cert-header-text">
                <h5>REPUBLIC OF THE PHILIPPINES</h5>
                <h5>PROVINCE OF ORIENTAL MINDORO</h5>
                <h4>MUNICIPALITY OF BONGABONG</h4>
                <h3>BARANGAY MALITBOG</h3>
                <strong>OFFICE OF THE PUNONG BARANGAY</strong>
            </div>
            <img src="image/barangaylogo3.jpg" alt="Municipal Logo" class="logo-img">
        </div>

        <!-- CONDITIONAL TEMPLATE RENDERING BASED ON TYPE -->
        <?php 
        switch($print_type) {
            case 'clearance': 
        ?>
            <div class="cert-title">BARANGAY CLEARANCE</div>
            <div class="cert-body">
                <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
                <p>
                    This is to certify that <strong><?php echo $full_name; ?></strong>, of legal age and a resident of Barangay Malitbog, Bongabong, Oriental Mindoro is known to be of good moral character and law-abiding citizen in the community.
                </p>
                <p>
                    To certify further, that he/she has no derogatory and/or criminal record filed in this barangay.
                </p>
                <p>
                    This certification is issued to the above-named person, upon his/her request, for <strong><?php echo $purpose_val; ?></strong>.
                </p>
                <p>
                    Issued this <?php echo $date_day; ?> day of <?php echo $date_month; ?> <?php echo $date_year; ?> at Barangay Malitbog, Bongabong, Oriental Mindoro.
                </p>
            </div>
            <div style="margin-top: 20px; font-size: 0.9rem; font-style: italic;">
                Note: This Clearance is Valid until 90 days from date issued.
            </div>
        <?php 
                break;
            case 'indigency': 
        ?>
            <div class="cert-title">CERTIFICATE OF INDIGENCY</div>
            <div class="cert-body">
                <p><strong>Sa Kinauukulan;</strong></p>
                <p>
                    Ito po ay pagpapatunay na si <strong><?php echo $full_name; ?></strong>, may sapat na taong gulang, ay lehitimong nakatira at residente ng Barangay Malitbog. Siya ay kabilang sa mga mahihirap na pamilya sa aming Barangay. Siya ay humihingi ng tulong para sa: <strong><?php echo $purpose_val; ?></strong>.
                </p>
                <p>
                    Ibinigay ito ngayong ika- <?php echo $date_day; ?> ng <?php echo $date_month; ?> <?php echo $date_year; ?> dito sa Pamahalaang Barangay Malitbog, Bongabong, Silangang Mindoro.
                </p>
            </div>
        <?php 
                break;
            case 'business_cert': 
        ?>
            <div class="cert-title">BUSINESS CERTIFICATION</div>
            <div class="cert-body">
                <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
                <p>
                    This is to certify that the undersigned hereby approved the herein application <strong><?php echo $full_name; ?></strong> (Name of Manager/Owner) for a Barangay Clearance/Permit to operate the business located at BRGY. MALITBOG, BONGABONG, OR. MINDORO.
                </p>
                <p>
                    This certifies that the applicant pledges to abide with laws, rules and regulations regarding the said activity and the same is not prejudicial to public order and safety. This is being issued to the applicant for presentation to Business Permits and Licensing Office, this municipality and all offices and agencies concerned prior to the issuance of any permit regarding the said activity pursuant to Republic Act 7160 otherwise known as the Local Government Code of 1991.
                </p>
                <p>
                    Issued this <?php echo $date_day; ?> day of <?php echo $date_month; ?> <?php echo $date_year; ?>, at Barangay Hall of Barangay Malitbog, Bongabong, Oriental Mindoro.
                </p>
            </div>
            <div style="margin-top: 15px; font-size: 0.85rem;">
                Brgy. Buss. Permit No. 056-<?php echo $date_year; ?> (Valid Until December 31, <?php echo $date_year; ?>)<br>
                <em>- Ang Business Certification na ito ay mawawalang bisa kung sakaling ang May-ari nito ay lumabag sa Batas Ordinansa ng Barangay na ipinatutupad.</em>
            </div>
        <?php 
                break;
            case 'residency': 
        ?>
            <div class="cert-title">CERTIFICATE OF RESIDENCY</div>
            <div class="cert-body">
                <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
                <p>
                    This is to certify that according to my records, <strong><?php echo $full_name; ?></strong>, of legal age, has not been accused of any crime, violation of law, or legal orders of Barangay Ordinances. He/She is personally known to me to be a permanent resident of Barangay Malitbog, Bongabong, Oriental Mindoro up to present.
                </p>
                <p>
                    This certification is issued upon the request of the above-mentioned name for <strong><?php echo $purpose_val; ?></strong>.
                </p>
                <p>
                    Issued this <?php echo $date_day; ?> day of <?php echo $date_month; ?> <?php echo $date_year; ?> at Barangay Malitbog, Bongabong, Oriental Mindoro.
                </p>
            </div>
        <?php 
                break;
            case 'non_tenancy': 
        ?>
            <div class="cert-title">CERTIFICATION OF NON-TENANCY</div>
            <div class="cert-body">
                <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
                <p>
                    This is to Certify that a parcel of land located in Barangay Malitbog, Bongabong, Oriental Mindoro, associated with applicant <strong><?php echo $full_name; ?></strong>, is NOT TENANTED. 
                </p>
                <p>
                    Issued this <?php echo $date_day; ?> day of <?php echo $date_month; ?> <?php echo $date_year; ?> at Barangay Malitbog, Bongabong, Oriental Mindoro.   
                </p>
            </div>
        <?php 
                break;
            case 'income': 
        ?>
            <div class="cert-title">KATUNAYAN (LIVELIHOOD / INCOME)</div>
            <div class="cert-body">
                <p><strong>Sa Kinauukulan,</strong></p>
                <p>
                    Ito ay isang Pagpapatunay na si <strong><?php echo $full_name; ?></strong>, may sapat na taong gulang, kasalukuyang naninirahan sa Barangay Malitbog, Bongabong, Oriental Mindoro ay naghahanapbuhay at kumikita sa loob ng isang buwan ayon sa kanyang kahilingan para sa <strong><?php echo $purpose_val; ?></strong>.
                </p>
                <p>
                    Nilagdaan ito, ngayong ika-<?php echo $date_day; ?> ng <?php echo $date_month; ?> <?php echo $date_year; ?>, sa Pamahalaan ng Punong Barangay Malitbog, Bongabong, Silangang Mindoro.
                </p>
            </div>
        <?php 
                break;
            default: 
        ?>
            <div class="cert-title">CERTIFICATION OF <?php echo strtoupper($doc_title); ?></div>
            <div class="cert-body">
                <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
                <p>
                    This is to certify that <strong><?php echo $full_name; ?></strong>, of legal age, is a permanent resident of Barangay Malitbog, Bongabong, Oriental Mindoro, and is known to be of good moral character and a law-abiding citizen in the community.
                </p>
                <p>
                    This certification is being issued upon the request of the above-named individual for <strong><?php echo $purpose_val; ?></strong> and for whatever legal intent and purpose it may serve.
                </p>
                <p>
                    Issued this <?php echo $date_day; ?> day of <?php echo $date_month; ?> <?php echo $date_year; ?> at Barangay Malitbog, Bongabong, Oriental Mindoro.
                </p>
            </div>
        <?php 
                break;
        }
        ?>

        <!-- OFFICIAL SIGNATURE -->
        <div class="cert-footer">
            <div class="signatory-box">
                <span class="name">ADOLFO A. AMPARO</span>
                <span class="title">Punong Barangay</span>
            </div>
        </div>
    </div>
</body>
</html>
<?php
        exit;
    }
    // ==================== SEPARATE VIEW 2: PRINT OFFICIAL RECEIPT (HALF-SHEET FORMAT) ====================
    if ($action_mode === 'print_receipt') {
        $fee_amount = 50.00;
        if ($print_type === 'indigency' || $print_type === 'others') {
            $fee_amount = 0.00;
        } elseif ($print_type === 'business_cert') {
            $fee_amount = 100.00; 
        }
        $or_number = "OR-" . date('Ymd', strtotime($doc['created_at'])) . "-" . strtoupper(substr($print_type, 0, 3)) . "-" . htmlspecialchars($doc['resident_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Receipt (Half-Sheet) - #<?php echo htmlspecialchars($doc['resident_id']); ?> - Barangay Malitbog</title>
    <style>
        @page {
            size: 5.5in 8.5in; 
            margin: 10mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 10px;
            color: #000;
            background: #fff;
        }
        .receipt-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            border: 2px dashed #000;
            padding: 15px;
            background: #fff;
            box-sizing: border-box;
        }
        .receipt-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            font-size: 0.8rem;
            line-height: 1.3;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        .receipt-totals {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 8px 0;
            margin: 10px 0;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-btn {
            background: #003366;
            color: white;
            border: none;
            padding: 8px 20px;
            font-size: 0.9rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
            .receipt-container { border: 1.5px dashed #000; width: 100%; max-width: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button class="print-btn" onclick="window.print()"><i class="fa fa-receipt"></i> Print Half-Sheet Receipt</button>
        <button class="print-btn" style="background:#555; margin-left:10px;" onclick="if(window.opener){window.close();}else{window.location.href='admincertification_overview.php';}">Close Window</button>
    </div>

    <div class="receipt-container">
        <div class="receipt-header">
            REPUBLIKA NG PILIPINAS<br>
            LALAWIGAN NG SILANGANG MINDORO<br>
            BAYAN NG BONGABONG<br>
            <strong>BARANGAY MALITBOG</strong><br>
            OFFICE OF THE BARANGAY TREASURER<br>
            <strong>OFFICIAL TRANSACTION RECEIPT</strong>
        </div>

        <div class="receipt-row">
            <span><strong>OR No:</strong></span>
            <span><?php echo $or_number; ?></span>
        </div>
        <div class="receipt-row">
            <span><strong>Date:</strong></span>
            <span><?php echo date('F d, Y', strtotime($doc['created_at'])); ?></span>
        </div>
        <div class="receipt-row" style="margin-top: 8px;">
            <span><strong>Received From:</strong></span>
            <span><strong><?php echo strtoupper(htmlspecialchars($doc['full_name'])); ?></strong></span>
        </div>
        <div class="receipt-row">
            <span><strong>Payment For:</strong></span>
            <span>Barangay <?php echo ucwords(str_replace('_', ' ', $print_type)); ?> Fee</span>
        </div>

        <div class="receipt-totals">
            <div class="receipt-row">
                <span><strong>Certification Fee:</strong></span>
                <span>₱<?php echo number_format($fee_amount, 2); ?></span>
            </div>
            <div class="receipt-row" style="font-size: 0.95rem; margin-top: 6px;">
                <span><strong>TOTAL AMOUNT PAID:</strong></span>
                <span><strong>₱<?php echo number_format($fee_amount, 2); ?></strong></span>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: right; font-size: 0.8rem;">
            <br>
            <strong>CONELYN VELASQUEZ ATIENZA</strong><br>
            <em>Barangay Treasurer</em>
        </div>
    </div>
</body>
</html>
<?php
    exit;
    }
}

// Query total counts for all document types (including general & newly added ones)
$residency_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cert_of_residency");
$total_residency = mysqli_fetch_assoc($residency_count_result)['total'] ?? 0;

$indigency_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cert_of_indigency");
$total_indigency = mysqli_fetch_assoc($indigency_count_result)['total'] ?? 0;

$clearance_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM barangay_clearance");
$total_clearance = mysqli_fetch_assoc($clearance_count_result)['total'] ?? 0;

$business_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM business_certification");
$total_business = mysqli_fetch_assoc($business_count_result)['total'] ?? 0;

$non_tenancy_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certification_of_non_tenancy");
$total_non_tenancy = mysqli_fetch_assoc($non_tenancy_count_result)['total'] ?? 0;

$same_person_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM one_same_person");
$total_same_person = mysqli_fetch_assoc($same_person_count_result)['total'] ?? 0;

$income_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM livelihood_income");
$total_income = mysqli_fetch_assoc($income_count_result)['total'] ?? 0;

$property_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM property_agricultural_ownership");
$total_property = mysqli_fetch_assoc($property_count_result)['total'] ?? 0;

$widening_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM road_widening_boundaries");
$total_widening = mysqli_fetch_assoc($widening_count_result)['total'] ?? 0;

$history_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM family_residency_history");
$total_history = mysqli_fetch_assoc($history_count_result)['total'] ?? 0;

$good_moral_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certificate_of_good_moral");
$total_good_moral = mysqli_fetch_assoc($good_moral_count_result)['total'] ?? 0;

$unemployment_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certificate_of_unemployment");
$total_unemployment = mysqli_fetch_assoc($unemployment_count_result)['total'] ?? 0;

$business_closure_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certificate_of_business_closure");
$total_business_closure = mysqli_fetch_assoc($business_closure_count_result)['total'] ?? 0;

$others_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM other_certifications");
$total_others = mysqli_fetch_assoc($others_count_result)['total'] ?? 0;

$total_cert_count = $total_residency + $total_indigency + $total_clearance + $total_business + $total_non_tenancy + $total_same_person + $total_income + $total_property + $total_widening + $total_history + $total_good_moral + $total_unemployment + $total_business_closure + $total_others;

// Combine records from all tables via UNION ALL
$query = "
    SELECT resident_id, full_name, contact_no, email, 'residency' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM cert_of_residency
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'indigency' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM cert_of_indigency
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'clearance' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM barangay_clearance
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'business_cert' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM business_certification
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'non_tenancy' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM certification_of_non_tenancy
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'same_person' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM one_same_person
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'income' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM livelihood_income
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'property_agricultural' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM property_agricultural_ownership
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'widening_road' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM road_widening_boundaries
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'family_history' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM family_residency_history
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'good_moral' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM certificate_of_good_moral
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'unemployment' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM certificate_of_unemployment
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'business_closure' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM certificate_of_business_closure
    UNION ALL
    SELECT resident_id, full_name, contact_no, email, 'others' AS cert_type, purpose_cat, purpose_detail, requirement_file, status, created_at FROM other_certifications
    ORDER BY created_at DESC
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certification Requests - Barangay Malitbog</title>
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
            --admin-orange: #d97706;
            --admin-green: #28a745;
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
            max-width: 1200px;
            margin: -30px auto 40px;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .section-title {
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 25px;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Filter Cards Grid Style Integration */
        .filter-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .filter-card {
            background: #fdfdfd;
            border: 2px solid #e9ecef;
            border-radius: 14px;
            padding: 15px;
            text-decoration: none;
            color: #333;
            display: flex;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
        }

        .filter-card i {
            font-size: 1.3rem;
            color: var(--primary-blue);
            margin-bottom: 2px;
            transition: transform 0.2s ease;
        }

        .filter-card .card-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-card .card-count {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .filter-card:hover {
            border-color: var(--primary-blue);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,102,0.08);
        }

        .filter-card:hover i {
            transform: scale(1.1);
        }

        .filter-card.active {
            background: linear-gradient(135deg, var(--primary-blue), #1a1a8c);
            border-color: var(--primary-blue);
            color: white;
            box-shadow: 0 6px 15px rgba(0,0,102,0.2);
        }

        .filter-card.active i,
        .filter-card.active .card-label,
        .filter-card.active .card-count {
            color: white;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 350px;
            font-family: inherit;
        }

        .request-table {
            width: 100%;
            border-collapse: collapse;
        }

        .request-table th {
            background: #f8f9fa;
            color: var(--primary-blue);
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #eee;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .request-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            display: inline-block;
            text-transform: capitalize;
        }

        .pending { background: #fff3e0; color: #ef6c00; }
        .approved { background: #e8f5e9; color: #2e7d32; }
        .rejected { background: #ffebee; color: #c62828; }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.75rem;
            text-transform: uppercase;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 3px;
        }

        .btn-details { background: var(--primary-blue); color: white; }
        .btn-details:hover { background: #000044; }
        .btn-approve { background: var(--primary-blue); color: white; }
        .btn-deny { background: #eee; color: #333; }
        .btn-reject-modal { background: var(--admin-red); color: white; }
        .btn-print { background: var(--print-green); color: white; }
        .btn-approve:hover { background: #000044; }
        .btn-reject-modal:hover { background: #990000; }
        .btn-print:hover { background: #1b5e20; }
        .btn-file { background: #e3f2fd; color: #1565c0; margin-right: 5px; }

        .request-row { display: table-row; }

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
            max-width: 450px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); 
            position: relative; 
            box-sizing: border-box;
            max-height: 90vh;
            overflow-y: auto;
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

        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            box-sizing: border-box;
            font-family: inherit;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .letter-preview {
            background-color: #fcfcfc;
            border: 1px solid #dcdcdc;
            border-left: 4px solid var(--primary-blue);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #333;
            line-height: 1.5;
        }

        .letter-header {
            font-weight: bold;
            text-transform: uppercase;
            color: var(--primary-blue);
            margin-bottom: 4px;
        }

        .letter-sub {
            color: #666;
            font-size: 0.8rem;
            margin-bottom: 12px;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 8px;
        }

        .receipt-preview-box {
            background: #fff;
            border: 1px dashed #000066;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.85rem;
            color: #111;
        }

        .receipt-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .receipt-row-flex {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .success-message-box {
            text-align: center;
            padding: 40px 20px;
        }
        .success-message-box i {
            font-size: 3.5rem;
            color: var(--print-green);
            margin-bottom: 15px;
        }
        .success-message-box h4 {
            color: var(--primary-blue);
            margin: 0 0 10px 0;
            font-size: 1.3rem;
            text-transform: uppercase;
        }
        .success-message-box p {
            color: #666;
            font-size: 0.95rem;
            margin: 0 0 20px 0;
            line-height: 1.5;
        }

        /* Mobile Adjustments */
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

    <!-- Top bar matches the main nav's 5% edge alignment -->
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
            <!-- Back button retains direct link behavior back to admin_dashboard.php -->
            <a href="admin_dashboard.php" class="back-link" title="Go Back">
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
        <div class="modal-content">
            <span class="close-btn" id="closeModalBtn" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';" style="position: absolute; right: 15px; top: 12px;">&times;</span>
            <div class="modal-header">
                <h3>System Notice</h3>
            </div>
            <div class="modal-body-content">
                <p style="margin: 0;">Are you sure you want to leave or log out?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" id="cancelLogoutBtn" onclick="document.getElementById('logoutModal').style.display='none'; document.getElementById('globalOverlay').style.display='none';">Cancel</button>
                <a href="index.php" style="text-decoration: none;">
                    <button type="button" class="btn-modal-action">Yes</button>
                </a>
            </div>
        </div>
    </div>

    <header class="hero-banner">
        <div class="welcome-msg">
            <h1>DOCUMENT <span>CERTIFICATIONS</span></h1>
            <p>Review and process official resident certification requests</p>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: bold; font-size: 1.2rem; color: var(--accent-gold);">
                TOTAL: <?php echo $total_cert_count; ?>
            </div>
            <p>System Status: <span style="color: #00ff00;">Online</span></p>
        </div>
    </header>

    <div class="container">
        <div class="section-title">
            <span>Certification Management</span>
            <small style="font-weight: normal; font-size: 0.8rem; color: #666;">Document Filter Queue</small>
        </div>

        <div class="action-bar">
            <input type="text" id="searchInput" class="search-box" onkeyup="filterCertifications()" placeholder="Search by name or ID...">
        </div>

        <div class="filter-cards-grid">
            <div class="filter-card active" onclick="filterTab('all', this)">
                <i class="fas fa-layer-group"></i>
                <span class="card-label">All Requests</span>
                <span class="card-count"><?php echo $total_cert_count; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('residency', this)">
                <i class="fas fa-home"></i>
                <span class="card-label">Residency</span>
                <span class="card-count"><?php echo $total_residency; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('indigency', this)">
                <i class="fas fa-hands-helping"></i>
                <span class="card-label">Indigency</span>
                <span class="card-count"><?php echo $total_indigency; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('clearance', this)">
                <i class="fas fa-file-alt"></i>
                <span class="card-label">Clearance</span>
                <span class="card-count"><?php echo $total_clearance; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('business_cert', this)">
                <i class="fas fa-store"></i>
                <span class="card-label">Business Cert</span>
                <span class="card-count"><?php echo $total_business; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('non_tenancy', this)">
                <i class="fas fa-file-contract"></i>
                <span class="card-label">Non-Tenancy</span>
                <span class="card-count"><?php echo $total_non_tenancy; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('same_person', this)">
                <i class="fas fa-user-check"></i>
                <span class="card-label">Same Person</span>
                <span class="card-count"><?php echo $total_same_person; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('income', this)">
                <i class="fas fa-peso-sign"></i>
                <span class="card-label">Income / Livelihood</span>
                <span class="card-count"><?php echo $total_income; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('property_agricultural', this)">
                <i class="fas fa-tractor"></i>
                <span class="card-label">Property / Agri</span>
                <span class="card-count"><?php echo $total_property; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('widening_road', this)">
                <i class="fas fa-road"></i>
                <span class="card-label">Road Widening</span>
                <span class="card-count"><?php echo $total_widening; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('family_history', this)">
                <i class="fas fa-users"></i>
                <span class="card-label">Family History</span>
                <span class="card-count"><?php echo $total_history; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('good_moral', this)">
                <i class="fas fa-award"></i>
                <span class="card-label">Good Moral</span>
                <span class="card-count"><?php echo $total_good_moral; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('unemployment', this)">
                <i class="fas fa-user-slash"></i>
                <span class="card-label">Unemployment</span>
                <span class="card-count"><?php echo $total_unemployment; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('business_closure', this)">
                <i class="fas fa-store-slash"></i>
                <span class="card-label">Business Closure</span>
                <span class="card-count"><?php echo $total_business_closure; ?></span>
            </div>
            <div class="filter-card" onclick="filterTab('others', this)">
                <i class="fas fa-file-invoice"></i>
                <span class="card-label">Others</span>
                <span class="card-count"><?php echo $total_others; ?></span>
            </div>
        </div>

        <table class="request-table" id="certTable">
            <thead>
                <tr>
                    <th>Resident ID</th>
                    <th>Resident Name</th>
                    <th>Contact No</th>
                    <th>Document Type</th>
                    <th>Purpose</th>
                    <th>Requirement File</th>
                    <th>Status</th>
                    <th>Date Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $status = htmlspecialchars($row['status']);
                        $statusClass = strtolower($status);
                        $docCategory = strtolower($row['cert_type']); 
                        $residentEmail = htmlspecialchars($row['email'] ?? '');
                ?>
                <tr class="request-row" data-doc="<?php echo $docCategory; ?>">
                    <td>#<?php echo htmlspecialchars($row['resident_id']); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['contact_no']); ?></td>
                    <td><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($row['cert_type']))); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['purpose_cat']); ?></strong><br>
                        <small style="color: #666;"><?php echo htmlspecialchars($row['purpose_detail']); ?></small>
                    </td>
                    <td>
                        <?php if (!empty($row['requirement_file'])): ?>
                            <a href="<?php echo htmlspecialchars($row['requirement_file']); ?>" target="_blank" class="action-btn btn-file">
                                <i class="fa fa-eye"></i> View
                            </a>
                        <?php else: ?>
                            <span style="color: #999;">None</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <button type="button" class="action-btn btn-details" 
                                onclick="openDetailsModal('<?php echo $row['resident_id']; ?>', '<?php echo $docCategory; ?>', '<?php echo addslashes($row['full_name']); ?>', '<?php echo htmlspecialchars($row['contact_no']); ?>', '<?php echo $residentEmail; ?>', '<?php echo $status; ?>')">
                            <i class="fa fa-info-circle"></i> Details
                        </button>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr class="no-data-row">
                    <td colspan="9" style="text-align: center; color: #777;">No certification requests found.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <button type="button" class="close-btn" onclick="closeDetailsModal()" style="position: absolute; right: 15px; top: 12px;">&times;</button>
            <div class="modal-header">
                <h3>Request Management Details</h3>
            </div>
            
            <div style="margin-bottom: 15px; font-size: 0.9rem; color: #555;">
                <p><strong>Resident ID:</strong> <span id="detResidentId"></span></p>
                <p><strong>Name:</strong> <span id="detResidentName"></span></p>
                <p><strong>Contact No:</strong> <span id="detContactNo"></span></p>
                <p><strong>Email:</strong> <span id="detEmail"></span></p>
                <p><strong>Current Status:</strong> <span id="detStatusBadge" class="status-badge"></span></p>
            </div>

            <div style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 10px; display: block; color: #333;">Available Actions:</label>
                <div id="detActionButtonsContainer" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
            </div>

            <div class="modal-footer" style="margin-top: 25px;">
                <button type="button" class="btn-modal-cancel" onclick="closeDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content" style="max-width: 550px;">
            <button type="button" class="close-btn" onclick="closeApprovalModal()" style="position: absolute; right: 15px; top: 12px;">&times;</button>
            <div class="modal-header">
                <h3>Approve & Release Details</h3>
            </div>
            
            <div id="successMessageBox" style="display: none;" class="success-message-box">
                <i class="fas fa-paper-plane"></i>
                <h4>Message Sent & Receipt Dispatched!</h4>
                <p>The certification has been approved successfully, and the claim notice & transaction receipt have been sent to the resident's email.</p>
                <button type="button" class="btn-modal-action" onclick="closeAndReload()" style="padding: 10px 30px; font-size: 0.9rem;">OK</button>
            </div>

            <form id="approvalForm" onsubmit="handleApprovalSubmit(event)">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" id="modalResidentId">
                <input type="hidden" name="type" id="modalDocType">

                <div class="form-group">
                    <label>Resident Name</label>
                    <input type="text" id="modalResidentName" readonly style="background: #e9ecef; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label for="resident_email">Resident Email Address (For Transaction Receipt)</label>
                    <input type="email" id="resident_email" name="resident_email" placeholder="e.g., resident@example.com" required>
                </div>

                <div class="form-group">
                    <label for="or_number">Official Receipt (OR) Number </label>
                    <input type="text" id="or_number" name="or_number" readonly required style="background: #e9ecef; font-weight: bold; color: var(--primary-blue);">
                </div>

                <div class="form-group">
                    <label for="amount_paid">Amount Paid (₱) </label>
                    <input type="number" step="0.01" id="amount_paid" name="amount_paid" required style="background: #f8f9fa; font-weight: bold;" oninput="updateReceiptPreview()">
                </div>

                <div class="form-group">
                    <label for="claim_message">Message to Resident (Formal Ready-to-Claim Notice)</label>
                    <div class="letter-preview">
                        <div class="letter-header">Barangay Malitbog Administrative Office</div>
                        <div class="letter-sub">Official Certificate Release & Claim Notice</div>
                        Dear <strong id="display_modal_resident_name">[Resident Name]</strong>,<br><br>
                        <textarea id="claim_message" name="claim_message" rows="7" style="resize: vertical; border: 1px dashed #ccc; background: #fff; padding: 8px; font-family: inherit; font-size: 0.85rem;" required></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label>Detailed Official Receipt Preview</label>
                    <div class="receipt-preview-box">
                        <div class="receipt-header">
                            REPUBLIKA NG PILIPINAS<br>
                            LALAWIGAN NG SILANGANG MINDORO<br>
                            BAYAN NG BONGABONG<br>
                            BARANGAY MALITBOG<br>
                            <strong>OFFICE OF THE BARANGAY TREASURER</strong><br>
                            OFFICIAL TRANSACTION RECEIPT
                        </div>
                        <div class="receipt-row-flex">
                            <span><strong>OR No:</strong> <span id="preview_or">[OR Number]</span></span>
                            <span><strong>Date:</strong> <span id="preview_date">--</span></span>
                        </div>
                        <div style="margin-bottom: 5px;"><strong>Received From:</strong> <span id="preview_name">[Resident Name]</span></div>
                        <div style="margin-bottom: 5px;"><strong>Payment For:</strong> Barangay <span id="preview_doc_type">[Document Type] Fee</span></div>
                        <div class="receipt-row-flex" style="border-top: 1px dashed #ccc; border-bottom: 1px dashed #ccc; padding: 5px 0; margin-top: 5px;">
                            <span><strong>Total Amount Paid:</strong></span>
                            <span><strong>₱<span id="preview_amount">0.00</span></strong></span>
                        </div>
                        <div style="margin-top: 8px; text-align: right; font-size: 0.75rem;">
                            <em>CONELYN VELASQUEZ ATIENZA<br>Barangay Treasurer</em>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 10px; background: #e8f4fd; padding: 10px; border-radius: 6px;">
                    <input type="checkbox" id="send_receipt_email" name="send_receipt_email" value="1" checked style="width: auto; cursor: pointer;">
                    <label for="send_receipt_email" style="margin-bottom: 0; cursor: pointer; color: var(--primary-blue); font-weight: 600;">
                        <i class="fa fa-receipt"></i> Send Transaction Receipt details to user email
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeApprovalModal()">Cancel</button>
                    <button type="submit" id="submitApprovalBtn" class="btn-modal-action">Confirm Release</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <button type="button" class="close-btn" onclick="closeRejectModal()" style="position: absolute; right: 15px; top: 12px;">&times;</button>
            <div class="modal-header">
                <h3>Reject Request & Send Notice</h3>
            </div>
            
            <div id="rejectSuccessBox" style="display: none;" class="success-message-box">
                <i class="fas fa-paper-plane" style="color: var(--admin-red);"></i>
                <h4>Rejection Notice Sent Successfully!</h4>
                <p>The certification request has been marked as rejected, and the notice has been sent to the resident's email.</p>
                <button type="button" class="btn-modal-action" style="background: var(--admin-red);" onclick="closeRejectAndReload()" style="padding: 10px 30px; font-size: 0.9rem;">OK</button>
            </div>

            <form id="rejectForm" onsubmit="handleRejectSubmit(event)">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" id="rejectResidentId">
                <input type="hidden" name="type" id="rejectDocType">

                <div class="form-group">
                    <label>Resident Name</label>
                    <input type="text" id="rejectResidentName" readonly style="background: #e9ecef; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label for="reject_resident_email">Resident Email Address</label>
                    <input type="email" id="reject_resident_email" name="resident_email" placeholder="e.g., resident@example.com" required>
                </div>

                <div class="form-group">
                    <label for="reject_message">Message / Reason for Rejection (Formal Notice)</label>
                    <div class="letter-preview" style="border-left-color: var(--admin-red);">
                        <div class="letter-header" style="color: var(--admin-red);">Barangay Malitbog Administrative Office</div>
                        <div class="letter-sub">Official Certification Request Status: Notice of Rejection</div>
                        Dear <strong id="display_reject_resident_name">[Resident Name]</strong>,<br><br>
                        <textarea id="reject_message" name="reject_message" rows="7" style="resize: vertical; border: 1px dashed #ccc; background: #fff; padding: 8px; font-family: inherit; font-size: 0.85rem;" required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" id="submitRejectBtn" class="btn-modal-action" style="background: var(--admin-red);">Confirm Rejection & Send Notice</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <button type="button" class="close-btn" onclick="closeDeleteModal()" style="position: absolute; right: 15px; top: 12px;">&times;</button>
            <div class="modal-header">
                <h3>Delete Record Confirmation</h3>
            </div>
            <p style="color: #555; font-size: 0.95rem; margin-top: 15px;">
                Are you sure you want to permanently delete this record from the database for <strong id="deleteResidentName">[Resident Name]</strong>?
            </p>
            <form id="deleteForm" action="process_certification.php" method="GET">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteResidentId">
                <input type="hidden" name="type" id="deleteDocType">

                <div class="modal-footer" style="margin-top: 25px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-modal-action" style="background:#cc0000; color:white;">Yes, Delete</button>
                </div>
            </form>
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
            document.getElementById('detailsModal').style.display = 'none';
            document.getElementById('approvalModal').style.display = 'none';
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('deleteModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

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

        let currentActiveDocType = 'all';

        function filterTab(docType, element) {
            const cards = document.querySelectorAll('.filter-card');
            cards.forEach(card => card.classList.remove('active'));
            element.classList.add('active');
            currentActiveDocType = docType;

            filterCertifications();
        }

        function filterCertifications() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('certTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                if (tr[i].classList.contains('no-data-row')) continue;

                const docAttr = tr[i].getAttribute('data-doc');
                const tdId = tr[i].getElementsByTagName('td')[0];
                const tdName = tr[i].getElementsByTagName('td')[1];
                const tdPurpose = tr[i].getElementsByTagName('td')[4];

                let matchesTab = (currentActiveDocType === 'all' || docAttr === currentActiveDocType);

                if (matchesTab) {
                    if (tdId || tdName || tdPurpose) {
                        const idVal = tdId ? (tdId.textContent || tdId.innerText) : '';
                        const nameVal = tdName ? (tdName.textContent || tdName.innerText) : '';
                        const purposeVal = tdPurpose ? (tdPurpose.textContent || tdPurpose.innerText) : '';

                        if (
                            idVal.toUpperCase().indexOf(filter) > -1 ||
                            nameVal.toUpperCase().indexOf(filter) > -1 ||
                            purposeVal.toUpperCase().indexOf(filter) > -1
                        ) {
                            tr[i].style.display = "";
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        function openDetailsModal(residentId, docType, residentName, contactNo, residentEmail, status) {
    document.getElementById('detResidentId').innerText = '#' + residentId;
    document.getElementById('detResidentName').innerText = residentName;
    document.getElementById('detContactNo').innerText = contactNo;
    document.getElementById('detEmail').innerText = residentEmail || 'N/A';
    
    const badge = document.getElementById('detStatusBadge');
    badge.className = 'status-badge ' + status.toLowerCase();
    badge.innerText = status;

    const container = document.getElementById('detActionButtonsContainer');
    container.innerHTML = '';

    const statusLower = status.toLowerCase();
    
    if (statusLower === 'pending') {
        container.innerHTML = `
            <button type="button" class="action-btn btn-approve" onclick="closeDetailsModal(); openApprovalModal('${residentId}', '${docType}', '${residentName.replace(/'/g, "\\'")}', '${contactNo}', '${residentEmail}')">Approve</button>
            <button type="button" class="action-btn btn-deny" onclick="closeDetailsModal(); openRejectModal('${residentId}', '${docType}', '${residentName.replace(/'/g, "\\'")}', '${residentEmail}')">Reject</button>
        `;
    } else if (statusLower === 'approved') {
        let printFileName = 'print_clearance.php'; // default

        if (docType === 'indigency') printFileName = 'print_indigency.php';
        else if (docType === 'business_cert') printFileName = 'print_business.php';
        else if (docType === 'residency') printFileName = 'print_residency.php';
        else if (docType === 'non_tenancy') printFileName = 'print_non_tenancy.php';
        else if (docType === 'income') printFileName = 'print_income.php';
        else if (docType === 'same_person') printFileName = 'print_same_person.php';
        else if (docType === 'family_history') printFileName = 'print_family_history.php';
        else if (docType === 'widening_road' || docType === 'road_widening') printFileName = 'print_road_widening.php';
        else if (docType === 'good_moral') printFileName = 'print_good_moral.php';
        else if (docType === 'unemployment' || docType === 'no_business') printFileName = 'print_unemployment.php';
        else if (docType === 'property_agricultural' || docType === 'agricultural') printFileName = 'print_property_agricultural.php';
        else if (docType === 'business_closure' || docType === 'closure') printFileName = 'print_business_closure.php';

        container.innerHTML = `
            <a href="#" class="action-btn btn-print" onclick="event.preventDefault(); window.open('${printFileName}?id=${encodeURIComponent(residentId)}', '_blank');"><i class="fa fa-print"></i> Print Cert</a>
            <a href="#" class="action-btn btn-print" style="background:#003366;" onclick="event.preventDefault(); window.location.href='?action=print_receipt&id=${encodeURIComponent(residentId)}&type=${encodeURIComponent(docType)}';"><i class="fa fa-receipt"></i> Print Receipt</a>
        `;
    } else if (statusLower === 'rejected') {
        // Displays the Delete action button container when status is rejected
        container.innerHTML = `
            <button type="button" class="action-btn btn-reject-modal" onclick="closeDetailsModal(); openDeleteModal('${residentId}', '${docType}', '${residentName.replace(/'/g, "\\'")}')">
                <i class="fa fa-trash"></i> Delete Record
            </button>
        `;
    } else {
        container.innerHTML = `
            <a href="#" class="action-btn btn-print" style="background:#003366;" onclick="event.preventDefault(); window.location.href='?action=print_receipt&id=${encodeURIComponent(residentId)}&type=${encodeURIComponent(docType)}';"><i class="fa fa-receipt"></i> Print Receipt</a>
        `;
    }

    document.getElementById('detailsModal').style.display = 'flex';
    globalOverlay.style.display = 'block';
}

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function openApprovalModal(residentId, docType, residentName, contactNo, residentEmail) {
            document.getElementById('successMessageBox').style.display = 'none';
            document.getElementById('approvalForm').style.display = 'block';
            document.getElementById('approvalForm').reset();

            document.getElementById('modalResidentId').value = residentId;
            document.getElementById('modalDocType').value = docType;
            document.getElementById('modalResidentName').value = residentName;
            document.getElementById('display_modal_resident_name').innerText = residentName;

            document.getElementById('preview_name').innerText = residentName;
            document.getElementById('preview_doc_type').innerText = docType.replace('_', ' ').toUpperCase();
            document.getElementById('preview_date').innerText = new Date().toLocaleDateString();

            if (residentEmail && residentEmail.trim() !== '') {
                document.getElementById('resident_email').value = residentEmail;
            } else {
                const cleanName = residentName.toLowerCase().replace(/[^a-z0-9]/g, '');
                document.getElementById('resident_email').value = `${cleanName}${residentId}@gmail.com`;
            }

            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const randomSeq = Math.floor(1000 + Math.random() * 9000);
            const docPrefix = docType.substring(0, 3).toUpperCase();
            
            const autoOrNumber = `OR-${year}${month}${day}-${docPrefix}-${residentId}-${randomSeq}`;
            document.getElementById('or_number').value = autoOrNumber;
            document.getElementById('preview_or').innerText = autoOrNumber;

            let autoAmount = '50.00';
            if (docType === 'indigency' || docType === 'others') {
                autoAmount = '0.00';
            } else if (docType === 'business_cert') {
                autoAmount = '100.00';
            }
            document.getElementById('amount_paid').value = autoAmount;
            document.getElementById('preview_amount').innerText = parseFloat(autoAmount).toFixed(2);

            const formalNoticeText = `Warm greetings from the Office of the Punong Barangay, Barangay Malitbog, Bongabong, Oriental Mindoro!\n\n` +
                `We are pleased to formally notify you that your application for an official Barangay ${docType.replace('_', ' ').toUpperCase()} has been thoroughly reviewed, verified, and APPROVED by our administrative office.\n\n` +
                `Your requested document is now ready for physical release. You may claim your official certificate personally at the Barangay Malitbog Hall during regular operating hours (Monday to Friday, 8:00 AM to 5:00 PM).\n\n` +
                `Important Reminders & Requirements upon Claiming:\n` +
                `1. Present a valid government-issued photo ID or institutional ID for proper identity verification.\n` +
                `2. Present this official email notice/transaction copy along with your Official Receipt Reference Number (${autoOrNumber}).\n\n` +
                `Thank you for your cooperation and adherence to our local barangay protocols. Should you have any questions or require further assistance, please feel free to reach out to our administrative staff.`;

            document.getElementById('claim_message').value = formalNoticeText;
            document.getElementById('approvalModal').style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        function openRejectModal(residentId, docType, residentName, residentEmail) {
            document.getElementById('rejectSuccessBox').style.display = 'none';
            document.getElementById('rejectForm').style.display = 'block';
            document.getElementById('rejectForm').reset();

            document.getElementById('rejectResidentId').value = residentId;
            document.getElementById('rejectDocType').value = docType;
            document.getElementById('rejectResidentName').value = residentName;
            document.getElementById('display_reject_resident_name').innerText = residentName;

            if (residentEmail && residentEmail.trim() !== '') {
                document.getElementById('reject_resident_email').value = residentEmail;
            } else {
                const cleanName = residentName.toLowerCase().replace(/[^a-z0-9]/g, '');
                document.getElementById('reject_resident_email').value = `${cleanName}${residentId}@gmail.com`;
            }

            const formalRejectText = `Warm greetings from the Office of the Punong Barangay, Barangay Malitbog, Bongabong, Oriental Mindoro!\n\n` +
                `We regret to inform you that your application for an official Barangay ${docType.replace('_', ' ').toUpperCase()} could not be approved at this time due to the following reason / incomplete requirements:\n\n` +
                `[Please specify the reason or missing requirement here]\n\n` +
                `You are welcome to re-submit your request with the complete requirements or visit the Barangay Hall for further clarification during regular operating hours (Monday to Friday, 8:00 AM to 5:00 PM).\n\n` +
                `Thank you for your understanding.`;

            document.getElementById('reject_message').value = formalRejectText;
            document.getElementById('rejectModal').style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function closeRejectAndReload() {
            document.getElementById('rejectModal').style.display = 'none';
            globalOverlay.style.display = 'none';
            location.reload();
        }

        function handleRejectSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('rejectForm');
            const submitBtn = document.getElementById('submitRejectBtn');
            const formData = new FormData(form);

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

            fetch('process_certification.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                form.style.display = 'none';
                document.getElementById('rejectSuccessBox').style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Rejection & Send Notice';
            })
            .catch(error => {
                console.error('Error saving rejection details:', error);
                alert('An error occurred while processing the rejection. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Rejection & Send Notice';
            });
        }

        function openDeleteModal(residentId, docType, residentName) {
            document.getElementById('deleteResidentId').value = residentId;
            document.getElementById('deleteDocType').value = docType;
            document.getElementById('deleteResidentName').innerText = residentName;
            document.getElementById('deleteModal').style.display = 'flex';
            globalOverlay.style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function updateReceiptPreview() {
            const amountVal = document.getElementById('amount_paid').value;
            document.getElementById('preview_amount').innerText = amountVal ? parseFloat(amountVal).toFixed(2) : '0.00';
        }

        function closeApprovalModal() {
            document.getElementById('approvalModal').style.display = 'none';
            globalOverlay.style.display = 'none';
        }

        function closeAndReload() {
            document.getElementById('approvalModal').style.display = 'none';
            globalOverlay.style.display = 'none';
            location.reload();
        }

        function handleApprovalSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('approvalForm');
            const submitBtn = document.getElementById('submitApprovalBtn');
            const formData = new FormData(form);

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

            fetch('process_certification.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                form.style.display = 'none';
                document.getElementById('successMessageBox').style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Release';
            })
            .catch(error => {
                console.error('Error saving approval details:', error);
                alert('An error occurred while saving the record. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Release';
            });
        }

        window.onclick = function(event) {
            const detailsModal = document.getElementById('detailsModal');
            const approvalModal = document.getElementById('approvalModal');
            const rejectModal = document.getElementById('rejectModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === detailsModal) closeDetailsModal();
            if (event.target === approvalModal) closeApprovalModal();
            if (event.target === rejectModal) closeRejectModal();
            if (event.target === deleteModal) closeDeleteModal();
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
                globalOverlay.style.display = 'none';
            }
            if (event.target === globalOverlay) {
                closeAllModals();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const activeCard = document.querySelector('.filter-card.active');
            if (activeCard) {
                filterTab('all', activeCard);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>