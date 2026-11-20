<?php
// print_lupong_tagapapayapa.php
session_start();
include 'db_connect.php';

if (!isset($_GET['id'])) {
    die("Record ID not specified.");
}
$record_id = mysqli_real_escape_string($conn, $_GET['id']);
$query = "SELECT * FROM lupong_tagapapayapa WHERE id = '$record_id' LIMIT 1";
$result = mysqli_query($conn, $query);
$doc = mysqli_fetch_assoc($result);

if (!$doc) {
    $respondent_name = "[NAME OF RESPONDENT]";
    $respondent_address = "Barangay Malitbog, Bongabong, Oriental Mindoro";
    $complainant_name = "[NAME OF COMPLAINANT]";
    $dispute = "usapin tungkol sa hindi pagkakaintindihan";
    $hearing_date = "20th day of November, 2026";
    $hearing_time = "9:00 ng Umaga";
    $notice_date = date('F j, Y');
} else {
    $respondent_name = strtoupper(htmlspecialchars($doc['respondent_name'] ?? '[NAME OF RESPONDENT]'));
    $respondent_address = htmlspecialchars($doc['respondent_address'] ?? 'Barangay Malitbog, Bongabong, Oriental Mindoro');
    $complainant_name = strtoupper(htmlspecialchars($doc['complainant_name'] ?? '[NAME OF COMPLAINANT]'));
    $dispute = htmlspecialchars($doc['dispute'] ?? 'usapin tungkol sa lupa / hindi pagkakaintindihan');
    $hearing_date = !empty($doc['hearing_date']) ? date('jS \d\a\y \o\f F, Y', strtotime($doc['hearing_date'])) : '20th day of November, 2026';
    $hearing_time = htmlspecialchars($doc['hearing_time'] ?? '9:00 ng Umaga');
    $notice_date = date('F j, Y', strtotime($doc['created_at'] ?? 'now'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lupong Tagapapayapa Summon - Barangay Malitbog</title>
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
        .date-section {
            text-align: right;
            font-weight: bold;
            margin-bottom: 12px;
            font-size: 1rem;
        }
        .recipient-section {
            margin-bottom: 15px;
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
        .received-section {
            margin-top: 20px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 0.9rem;
            page-break-inside: avoid;
        }
        .received-section table {
            width: 100%;
            margin-top: 5px;
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
        <button class="print-btn" onclick="window.print()">Print Lupong Tagapapayapa Summon</button>
        <button class="print-btn" style="background:#555; margin-left:10px;" onclick="window.close()">Close Window</button>
    </div>

    <div class="cert-container">
        <div class="cert-content-wrapper">
            <!-- Header Logos matching standard system template -->
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

            <div class="office-heading">TANGGAPAN NG PUNONG BARANGAY</div>

            <div class="doc-title">PANAWAGAN (SUMMON)</div>

            <div class="date-section">Date: <?php echo $notice_date; ?></div>

            <div class="recipient-section">
                <strong>TO: <?php echo $respondent_name; ?></strong><br>
                <span><?php echo $respondent_address; ?></span>
            </div>

            <div class="cert-body">
                Pagbati ng Kapayapaan!<br>
                Isang Magandang Araw po!
            </div>

            <div class="cert-body" style="text-indent: 40px;">
                Kayo po ay malugod naming inaanyayahang dumulog sa Tanggapan ng Punong Barangay sa Barangay Malitbog, para sa pagpupulong na hinihiling ni <strong><?php echo $complainant_name; ?></strong> na taga Barangay Malitbog, Bongabong, Silangang Mindoro.
            </div>

            <div class="cert-body" style="text-indent: 40px;">
                Upang pag-usapan ninyo ang patungkol sa: <strong><?php echo $dispute; ?></strong>.
            </div>

            <div class="cert-body" style="text-indent: 40px;">
                Itinakda ang inyong pagpupulong ngayong ika-<strong><?php echo $hearing_date; ?></strong>, ganap na ika-<strong><?php echo $hearing_time; ?></strong> ng Umaga.
            </div>

            <div class="cert-body" style="text-indent: 40px;">
                Inaasahan po namin ang inyong malugod na pagdalo at ang hindi pagsipot at pagtalima sa usaping ito ay inyo ring pagbibigay daan upang ang usaping ito ay aming idulog sa mas mataas na sangay ng Pamahalaan.
            </div>

            <div class="cert-body" style="text-indent: 0;">
                Maraming Salamat po....
            </div>

            <div class="main-signature-column">
                <div class="captain-sig">
                    <div class="sig-line">ADOLFO A. AMPARO</div>
                    <div class="sig-label">Punong Barangay</div>
                </div>

                <div class="received-section">
                    <table>
                        <tr>
                            <td><strong>Notice Received by:</strong> ______________________</td>
                            <td><strong>Date Received:</strong> __________________</td>
                        </tr>
                    </table>
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