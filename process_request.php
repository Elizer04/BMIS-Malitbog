<?php
include 'db_connect.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $resident_id    = $_POST['resident_id'] ?? '';
    $full_name      = $_POST['full_name'] ?? '';
    $contact_no     = $_POST['contact_no'] ?? '';
    $email          = $_POST['email'] ?? ''; 
    $cert_type      = $_POST['cert_type'] ?? '';
    $purpose_cat    = $_POST['purpose_cat'] ?? '';
    $purpose_detail = $_POST['purpose_detail'] ?? '';

    // Clean fee input (removes ₱, free text, spaces)
    $raw_fee = $_POST['fee'] ?? '0.00';
    $fee     = (float) preg_replace('/[^0-9.]/', '', $raw_fee);

    // Map the form's cert_type value to your exact database table names
    $target_table = "";
    switch ($cert_type) {
        case 'indigency':
            $target_table = 'cert_of_indigency';
            break;
        case 'clearance':
            $target_table = 'barangay_clearance';
            break;
        case 'residency':
            $target_table = 'cert_of_residency'; 
            break;
        case 'business_cert':
            $target_table = 'business_certification'; 
            break;
        case 'non_tenancy':
            $target_table = 'certification_of_non_tenancy'; 
            break;
        case 'unemployment':
            $target_table = 'certificate_of_unemployment'; 
            break;
        case 'business_closure':
            $target_table = 'certificate_of_business_closure'; 
            break;
        case 'good_moral':
            $target_table = 'certificate_of_good_moral'; 
            break;
        case 'others':
            $target_table = 'other_certifications'; 
            break;
        case 'same_person':
            $target_table = 'one_same_person'; 
            break;
        case 'income':
            $target_table = 'livelihood_income'; 
            break;
        case 'property_agricultural':
            $target_table = 'property_agricultural_ownership'; 
            break;
        case 'widening_road':
            $target_table = 'road_widening_boundaries'; 
            break; 
        case 'family_history':
            $target_table = 'family_residency_history'; 
            break;   
        default:
            echo "Error: Invalid document type selected.";
            exit();
    }

    // --- DUPLICATE/ACTIVE REQUEST PREVENTION CHECK ---
    // Checks if the resident already has a pending or active request for this document type
    $check_dup_sql = "SELECT COUNT(*) as cnt FROM `$target_table` WHERE resident_id = ? AND status IN ('Pending', 'Processing', 'Ready')";
    if ($stmt_dup = $conn->prepare($check_dup_sql)) {
        $stmt_dup->bind_param("s", $resident_id);
        $stmt_dup->execute();
        $res_dup = $stmt_dup->get_result()->fetch_assoc();
        $stmt_dup->close();

        if ($res_dup['cnt'] > 0) {
            echo "duplicate_request"; // Triggers the duplicate modal warning on the frontend
            exit();
        }
    }
    // ------------------------------------------------

    // Handle File Upload safely
    $file_path = null;
    if (isset($_FILES['requirement_file']) && $_FILES['requirement_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['requirement_file']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['requirement_file']['name']);
        $upload_dir = 'uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($file_tmp, $target_path)) {
            $file_path = $target_path;
        }
    }

    // Dynamically prepare the SQL statement including 'email'
    $sql = "INSERT INTO `$target_table` (resident_id, full_name, contact_no, email, cert_type, fee, purpose_cat, purpose_detail, requirement_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sssssdsss", $resident_id, $full_name, $contact_no, $email, $cert_type, $fee, $purpose_cat, $purpose_detail, $file_path);

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Error executing query: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
}
?>