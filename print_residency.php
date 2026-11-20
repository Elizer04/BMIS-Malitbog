<?php
session_start();
include 'db_connect.php';

// Ensure ID is provided
if (!isset($_GET['id'])) {
    die("Resident ID not specified.");
}

$resident_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch resident details from the database
$query = "SELECT * FROM cert_of_residency WHERE resident_id = '$resident_id' LIMIT 1";
$result = mysqli_query($conn, $query);
$doc = mysqli_fetch_assoc($result);

if (!$doc) {
    die("Record not found.");
}

$full_name = strtoupper(htmlspecialchars($doc['full_name']));
$date_issued = date('jS \d\a\y \o\f F, Y', strtotime($doc['created_at']));
$purpose_val = htmlspecialchars(($doc['purpose_cat'] ?? '') . ' - ' . ($doc['purpose_detail'] ?? 'Any Legal Purposes'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Residency - Barangay Malitbog</title>
    <style>
        @page { size: portrait; margin: 10mm; }
        body { font-family: 'Times New Roman', Times, serif; margin: 0; padding: 30px; color: #000; background: #fff; line-height: 1.6; }
        .cert-container { max-width: 800px; margin: 0 auto; padding: 20px; background: #fff; position: relative; }
        .cert-header { display: flex; align-items: center; justify-content: space-between; text-align: center; line-height: 1.3; margin-bottom: 25px; border-bottom: 3px double #000; padding-bottom: 15px; }
        .cert-header img.logo-img { width: 85px; height: 85px; object-fit: contain; }
        .cert-header-text h5 { margin: 2px 0; font-size: 0.85rem; font-weight: normal; }
        .cert-header-text h4 { margin: 2px 0; font-size: 0.95rem; font-weight: bold; }
        .cert-header-text h3 { margin: 4px 0; font-size: 1.1rem; font-weight: bold; color: #000066; }
        .cert-title { text-align: center; text-transform: uppercase; font-size: 1.4rem; font-weight: bold; margin: 25px 0; letter-spacing: 1px; }
        .cert-body { font-size: 1.1rem; text-align: justify; margin-bottom: 40px; }
        .cert-body p { text-indent: 40px; margin-bottom: 18px; }
        .cert-footer { margin-top: 50px; display: flex; justify-content: flex-end; }
        .signatory-box { text-align: center; width: 280px; }
        .signatory-box .name { font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 2px; display: block; font-size: 1.05rem; }
        .signatory-box .title { font-size: 0.95rem; margin-top: 4px; }
        .no-print { text-align: center; margin-bottom: 30px; }
        .print-btn { background: #2e7d32; color: white; border: none; padding: 10px 25px; font-size: 1rem; font-weight: bold; border-radius: 4px; cursor: pointer; }
        @media print { .no-print { display: none; } body { padding: 0; margin: 0; } .cert-container { width: 100%; max-width: none; border: none; padding: 10px; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Print Certificate of Residency</button>
        <button class="print-btn" style="background:#555; margin-left:10px;" onclick="window.close()">Close Window</button>
    </div>

    <div class="cert-container">
        <div class="cert-header">
            <img src="image/barangaylogo3.jpg" alt="Barangay Logo" class="logo-img">
            <div class="cert-header-text">
                <h5>REPUBLIKA NG PILIPINAS</h5>
                <h5>LALAWIGAN NG SILANGANG MINDORO</h5>
                <h4>BAYAN NG BONGABONG</h4>
                <h3>BARANGAY MALITBOG</h3>
                <strong>OFFICE OF THE PUNONG BARANGAY</strong>
            </div>
            <img src="image/barangaylogo3.jpg" alt="Municipal Logo" class="logo-img">
        </div>

        <div class="cert-title">CERTIFICATE OF RESIDENCY</div>
        <div class="cert-body">
            <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
            <p>
                This is to certify that according to my records, <strong>[FULL NAME]</strong>, [AGE] years old, born on [BIRTHDATE] at [MUNICIPALITY, PROVINCE], is a permanent resident of Barangay Malitbog, Bongabong, Oriental Mindoro since [YEAR] up to the present.
            </p>
            <p>
                This certification is issued upon the request of the above-named person for [PURPOSE] and for whatever legal intent and purpose it may serve.
            </p>
            <p>
                Issued this [DAY] day of [MONTH], 2026 at Barangay Malitbog, Bongabong, Oriental Mindoro.
            </p>
        </div>

        <div class="cert-footer">
            <div class="signatory-box">
                <span class="name">ADOLFO A. AMPARO</span>
                <span class="title">Punong Barangay</span>
            </div>
        </div>
    </div>
</body>
</html>