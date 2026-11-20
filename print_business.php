<?php
session_start();
include 'db_connect.php';

// Ensure ID is provided
if (!isset($_GET['id'])) {
    die("Business Record ID not specified.");
}

$resident_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch business details from the database
$query = "SELECT * FROM business_certification WHERE resident_id = '$resident_id' LIMIT 1";
$result = mysqli_query($conn, $query);
$doc = mysqli_fetch_assoc($result);

if (!$doc) {
    die("Record not found.");
}

$owner_name = strtoupper(htmlspecialchars($doc['full_name'] ?? $doc['owner_name'] ?? ''));
$business_name = strtoupper(htmlspecialchars($doc['business_name'] ?? ''));
$permit_no = htmlspecialchars($doc['permit_no'] ?? '056-2026');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Clearance - Barangay Malitbog</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 10px 30px;
            color: #000;
            background: #fff;
            line-height: 1.4;
        }
        .cert-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            padding: 10px 20px;
            background: #fff;
        }
        /* Watermark Logo Background */
        .cert-container::before {
            content: "";
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 480px;
            height: 480px;
            background: url('image/barangaylogo3.jpg') no-repeat center center;
            background-size: contain;
            opacity: 0.12;
            z-index: 0;
            pointer-events: none;
        }
        .cert-content-wrapper {
            position: relative;
            z-index: 1;
        }
        .cert-header {
            text-align: center;
            margin-bottom: 2px;
        }
        .header-top-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 35px;
            margin-bottom: 5px;
        }
        .header-text p {
            margin: 1px 0;
            font-size: 0.85rem;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .office-heading {
            text-align: center;
            font-weight: bold;
            font-size: 1.05rem;
            margin: 5px 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 1.4rem;
            color: #000;
            margin: 10px 0 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .to-whom {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 1rem;
        }
        .cert-body {
            text-align: justify;
            text-indent: 40px;
            font-size: 1rem;
            margin-bottom: 12px;
            line-height: 1.6;
        }
        .main-signature-column {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-end;
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .captain-sig {
            text-align: center;
            width: 280px;
            margin-bottom: 12px;
        }
        .sig-line {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            display: inline-block;
            min-width: 250px;
            letter-spacing: 0.5px;
        }
        .sig-label {
            font-size: 0.9rem;
            margin-top: 2px;
        }
        .clearance-validity {
            width: 100%;
            font-size: 0.9rem;
            margin-bottom: 15px;
            text-align: left;
        }
        .contact-info-footer {
            margin-top: 15px;
            font-size: 0.75rem;
            line-height: 1.3;
            border-top: 1px solid #000;
            padding-top: 5px;
            width: 100%;
            text-align: left;
        }
        .contact-info-footer p {
            margin: 1px 0;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-btn {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 8px 20px;
            font-size: 0.95rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .cert-container { border: none; padding: 5px; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Print Business Clearance</button>
        <button class="print-btn" style="background:#555; margin-left:10px;" onclick="window.close()">Close Window</button>
    </div>

    <div class="cert-container">
        <div class="cert-content-wrapper">
            <!-- Header Logos matching the standard template -->
            <div class="cert-header">
                <div class="header-top-row">
                    <img src="image/bagong_pilipinas.jpg" alt="Bagong Pilipinas" style="height:80px; object-fit:contain;">
                    <img src="image/barangaylogo3.jpg" alt="Barangay Logo" style="height:80px; width:80px; border-radius:50%; object-fit:cover;">
                    <img src="image/municipal_seal.jpg" alt="Municipal Seal" style="height:80px; width:80px; border-radius:50%; object-fit:cover;">
                </div>
                <div class="header-text">
                    <p>REPUBLIC OF THE PHILIPPINES</p>
                    <p>PROVINCE OF ORIENTAL MINDORO</p>
                    <p>MUNICIPALITY OF BONGABONG</p>
                    <p style="font-weight: bold; font-size: 1rem; margin-top: 2px;">BARANGAY MALITBOG</p>
                </div>
            </div>

            <div class="office-heading">OFFICE OF THE PUNONG BARANGAY</div>

            <div class="doc-title">BUSINESS CLEARANCE</div>

            <div style="font-size: 0.95rem; font-weight: bold; margin-bottom: 10px;">
                Brgy. Buss. Permit No. <?php echo $permit_no; ?> &nbsp;&nbsp;|&nbsp;&nbsp; Note: Valid Until December 31, 2026
            </div>

            <div class="to-whom">TO WHOM IT MAY CONCERN:</div>

            <div class="cert-body">
                This is to certify that the undersigned hereby approved the herein application <strong><?php echo $owner_name; ?></strong> for a Barangay Clearance/Permit to operate the business of <strong><?php echo $business_name; ?></strong> located at BRGY. MALITBOG, BONGABONG, OR. MINDORO.
            </div>

            <div class="cert-body" style="text-indent: 40px;">
                This certifies that the applicant pledges to abide with laws, rules and regulations regarding the said activity and the same is not prejudicial to public order and safety. This is being issued to the applicant for presentation to Business Permits and Licensing Office, this municipality and all offices and agencies concerned prior to the issuance of any permit regarding the said activity pursuant to Republic Act 7160 otherwise known as the Local Government Code of 1991.
            </div>

            <div class="cert-body" style="text-indent: 0; margin-top: 12px;">
                Issued this <?php echo date('j', strtotime($doc['created_at'] ?? 'now')); ?> day of <?php echo date('F Y', strtotime($doc['created_at'] ?? 'now')); ?>, at Barangay Hall of Barangay Malitbog, Bongabong, Oriental Mindoro.
            </div>

            <div style="font-size: 0.85rem; margin-top: 10px; font-style: italic;">
                - Ang Business Certification na ito ay mawawalang bisa kung sakaling ang May-ari nito ay lumabag sa Batas Ordinansa ng Barangay na ipinatutupad.
            </div>

            <div class="main-signature-column">
                <div class="captain-sig">
                    <div class="sig-line">ADOLFO A. AMPARO</div>
                    <div class="sig-label">Barangay Captain</div>
                </div>

                <div class="contact-info-footer">
                    <p><strong>E-mail :</strong> barangaymalitbog1947@gmail.com</p>
                    <p><strong>Facebook Page:</strong> Brgy. Malitbog Official</p>
                    <p><strong>Contact Number:</strong> 09971582535</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
if (isset($conn)) {
    $conn->close(); 
}
?>