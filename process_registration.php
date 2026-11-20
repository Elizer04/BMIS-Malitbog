<?php
include 'db_connect.php'; 

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve and sanitize form inputs[cite: 1, 2]
    $resident_id   = trim($_POST['resident_id']);
    $last_name     = trim($_POST['last_name']);
    $first_name    = trim($_POST['first_name']);
    $middle_name   = trim($_POST['middle_name']);
    $date_of_birth = $_POST['date_of_birth'];
    $civil_status  = $_POST['civil_status'];
    $address       = trim($_POST['address']);
    $contact_no    = trim($_POST['phone_number']); 
    $email         = trim($_POST['email']);
    
    $password_val  = $_POST['password'];
    $status        = 'Pending';

    // Strict Server-Side Philippine Phone Number Validation
    $clean_contact = preg_replace('/[\s\-\(\)]/', '', $contact_no);
    if (!preg_match('/^(\+63|0)9\d{9}$/', $clean_contact)) {
        echo "Error: Please provide a valid Philippine mobile number (e.g., 09123456789 or +639123456789).";
        exit;
    }

    // Generate full name required by table schema
    $full_name = trim("$first_name $middle_name $last_name");

    // Fallback security check: If resident_id is empty, generate it server-side
    if (empty($resident_id)) {
        $currentYear = date("Y");
        $id_query = "SELECT resident_id FROM residents WHERE resident_id LIKE ? ORDER BY resident_id DESC";
        $id_stmt = $conn->prepare($id_query);
        $like_year = $currentYear . "%";
        $id_stmt->bind_param("s", $like_year);
        $id_stmt->execute();
        $result = $id_stmt->get_result();

        $max_number = 0;
        while ($row = $result->fetch_assoc()) {
            $parts = preg_split('/[- ]/', $row['resident_id']);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $num = intval($parts[1]);
                if ($num > $max_number) {
                    $max_number = $num;
                }
            }
        }
        $id_stmt->close();
        $next_number = $max_number + 1;
        $resident_id = $currentYear . "-" . str_pad($next_number, 4, "0", STR_PAD_LEFT);
    }

    // Prepare SQL Statement using MySQLi Prepared Statements
    $sql = "INSERT INTO residents (
                resident_id, 
                full_name, 
                last_name, 
                first_name, 
                middle_name, 
                date_of_birth, 
                civil_status, 
                address, 
                contact_no, 
                email, 
                password,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param(
            "ssssssssssss", 
            $resident_id, 
            $full_name, 
            $last_name, 
            $first_name, 
            $middle_name, 
            $date_of_birth, 
            $civil_status, 
            $address, 
            $clean_contact, // Save validated format
            $email, 
            $password_val, 
            $status
        );

        if ($stmt->execute()) {
            echo "success"; 
        } else {
            // Check for MySQL Duplicate Entry Error (Error Code: 1062)
            if ($stmt->errno === 1062) {
                echo "duplicate";
            } else {
                echo "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>