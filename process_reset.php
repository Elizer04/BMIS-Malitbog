<?php
// process_reset.php

// Include the centralized database connection file
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Please provide a valid email address.";
        exit();
    }

    // 2. Check if the email exists in the residents table[cite: 3]
    $stmt = $conn->prepare("SELECT resident_id, full_name FROM residents WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "not found";
        $stmt->close();
        $conn->close();
        exit();
    }

    $row = $result->fetch_assoc();
    $resident_name = $row['full_name'];
    $stmt->close();

    // 3. Generate a secure unique token[cite: 3]
    $token = bin2hex(random_bytes(32));

    // 4. Store or update the token in the password_resets table using MySQL's native NOW() clock[cite: 3]
    // Delete any existing tokens for this email first
    $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $del_stmt->bind_param("s", $email);
    $del_stmt->execute();
    $del_stmt->close();

    // Using DATE_ADD(NOW(), INTERVAL 1 HOUR) prevents PHP vs MySQL timezone mismatch issues[cite: 3]
    $ins_stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    $ins_stmt->bind_param("ss", $email, $token);
    
    if (!$ins_stmt->execute()) {
        echo "Error generating reset session. Please try again later.";
        $ins_stmt->close();
        $conn->close();
        exit();
    }
    $ins_stmt->close();

    // 5. Build and send the reset email (Using SITEURL constant if preferred, or fallback to dynamic host)
    $reset_link = rtrim(SITEURL, '/') . "/reset_password.php?token=" . $token;

    $subject = "Password Reset Request - Barangay Malitbog";
    $message = "Hello " . $resident_name . ",\n\n"
             . "We received a request to reset your password for your Barangay Malitbog Resident Portal account.\n\n"
             . "Click the link below to reset your password:\n"
             . $reset_link . "\n\n"
             . "This link will expire in 1 hour. If you did not request this, please ignore this email.\n\n"
             . "Sincerely,\n"
             . "Barangay Malitbog Administration";

    $headers = "From: elizerhilliado@gmail.com\r\n" .
               "Reply-To: elizerhilliado@gmail.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    $mail_sent = mail($email, $subject, $message, $headers);

    if ($mail_sent) {
        echo "success";
    } else {
        echo "Failed to send reset email. Please check your server mail configuration.";
    }

    $conn->close();
} else {
    header("Location: forgot_password.php");
    exit();
}
?>