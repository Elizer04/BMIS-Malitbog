<?php
include 'db_connect.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? '';

    $table = '';
    if ($type === 'residency') {
        $table = 'cert_of_residency';
    } elseif ($type === 'indigency') {
        $table = 'cert_of_indigency';
    } elseif ($type === 'clearance') {
        $table = 'barangay_clearance';
    } elseif ($type === 'business_cert') {
        $table = 'business_certification'; 
    } elseif ($type === 'non_tenancy') {
        $table = 'certification_of_non_tenancy'; 
    } elseif ($type === 'same_person') {
        $table = 'one_same_person'; 
    } elseif ($type === 'income') {
        $table = 'livelihood_income'; 
    } elseif ($type === 'property_agricultural') {
        $table = 'property_agricultural_ownership'; 
    } elseif ($type === 'widening_road') {
        $table = 'road_widening_boundaries'; 
    } elseif ($type === 'family_history') {
        $table = 'family_residency_history'; 
    } elseif ($type === 'good_moral') {
        $table = 'certificate_of_good_moral'; 
    } elseif ($type === 'unemployment') {
        $table = 'certificate_of_unemployment'; 
    } elseif ($type === 'business_closure') {
        $table = 'certificate_of_business_closure'; 
    } elseif ($type === 'others') {
        $table = 'other_certifications'; 
    }

    if (empty($table) || empty($id)) {
        http_response_code(400);
        echo "Invalid parameters.";
        exit;
    }

    if ($action === 'approve') {
        $resident_email  = $_POST['resident_email'] ?? '';
        $or_number       = $_POST['or_number'] ?? '';
        $amount_paid     = $_POST['amount_paid'] ?? 0;
        $claim_message   = $_POST['claim_message'] ?? '';
        
        $stmt = $conn->prepare("UPDATE $table SET status = 'Approved', email = ?, or_number = ?, amount_paid = ?, claim_message = ? WHERE resident_id = ?");
        
        if ($stmt) {
            $stmt->bind_param("ssdsi", $resident_email, $or_number, $amount_paid, $claim_message, $id);
            
            if ($stmt->execute()) {
                if (!empty($resident_email)) {
                    $to = $resident_email;
                    $subject = "Barangay Malitbog - Official Certification Approved & Claim Notice";
                    $message = $claim_message;

                    $headers = "From: elizerhilliado@gmail.com\r\n" .
                               "Reply-To: elizerhilliado@gmail.com\r\n" .
                               "X-Mailer: PHP/" . phpversion();

                    @mail($to, $subject, $message, $headers);
                }

                http_response_code(200);
                echo "Success";
            } else {
                http_response_code(500);
                echo "Database update failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo "Statement preparation failed: " . $conn->error;
        }
    } 
    elseif ($action === 'reject') {
        $resident_email = $_POST['resident_email'] ?? '';
        $reject_message = $_POST['reject_message'] ?? '';
        
        $stmt = $conn->prepare("UPDATE $table SET status = 'Rejected', email = ?, claim_message = ? WHERE resident_id = ?");
        
        if ($stmt) {
            $stmt->bind_param("ssi", $resident_email, $reject_message, $id);
            
            if ($stmt->execute()) {
                if (!empty($resident_email)) {
                    $to = $resident_email;
                    $subject = "Barangay Malitbog - Certification Request Status: Notice of Rejection";
                    $message = $reject_message;

                    $headers = "From: elizerhilliado@gmail.com\r\n" .
                               "Reply-To: elizerhilliado@gmail.com\r\n" .
                               "X-Mailer: PHP/" . phpversion();

                    @mail($to, $subject, $message, $headers);
                }

                http_response_code(200);
                echo "Success";
            } else {
                http_response_code(500);
                echo "Database update failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo "Statement preparation failed: " . $conn->error;
        }
    }
} 
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? '';
    $type = $_GET['type'] ?? '';

    $table = '';
    if ($type === 'residency') {
        $table = 'cert_of_residency';
    } elseif ($type === 'indigency') {
        $table = 'cert_of_indigency';
    } elseif ($type === 'clearance') {
        $table = 'barangay_clearance';
    } elseif ($type === 'business_cert') {
        $table = 'business_certification'; 
    } elseif ($type === 'non_tenancy') {
        $table = 'certification_of_non_tenancy'; 
    } elseif ($type === 'same_person') {
        $table = 'one_same_person'; 
    } elseif ($type === 'income') {
        $table = 'livelihood_income'; 
    } elseif ($type === 'property_agricultural') {
        $table = 'property_agricultural_ownership'; 
    } elseif ($type === 'widening_road') {
        $table = 'road_widening_boundaries'; 
    } elseif ($type === 'family_history') {
        $table = 'family_residency_history'; 
    } elseif ($type === 'good_moral') {
        $table = 'certificate_of_good_moral'; 
    } elseif ($type === 'unemployment') {
        $table = 'certificate_of_unemployment'; 
    } elseif ($type === 'business_closure') {
        $table = 'certificate_of_business_closure'; 
    } elseif ($type === 'others') {
        $table = 'other_certifications'; 
    }

    if (!empty($table) && !empty($id)) {
        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM $table WHERE resident_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'archive') {
            $stmt = $conn->prepare("UPDATE $table SET status = 'Archived' WHERE resident_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: admincertification_overview.php");
    exit();
}
?>