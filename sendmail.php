<?php
// Include PHPMailer manual class files (adjust path if your folder is named differently)
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email_sent = false;

// Ensure variables like $resident_email, $resident_name, and $formatted_message are defined before this block
if (!empty($resident_email) && filter_var($resident_email, FILTER_VALIDATE_EMAIL)) {
    $mail = new PHPMailer(true);

    try {
        // Server settings for Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'elizerhilliado@gmail.com'; // Your Gmail address
        $mail->Password   = 'lknhzdkikfjhpuob';  // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('elizerhilliado@gmail.com', 'Barangay Malitbog Administration');
        $mail->addAddress($resident_email, $resident_name);
        $mail->addReplyTo('elizerhilliado@gmail.com', 'Barangay Malitbog Administration');

        // Content
        $mail->isHTML(true); // Recommended so HTML formatting in $formatted_message renders correctly
        $mail->Subject = "Hello " . $resident_name . ", notice regarding your resident application!";
        $mail->Body    = $formatted_message;

        $mail->send();
        $email_sent = true;
    } catch (Exception $e) {
        $email_sent = false;
        // Optional debug log: error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}
?>