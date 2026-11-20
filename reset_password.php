<?php
// reset_password.php

// Include the centralized database connection file
require_once 'db_connect.php';

$error_message = "";
$success_message = "";
$valid_token = false;

$token = isset($_GET['token']) ? trim($_GET['token']) : (isset($_POST['token']) ? trim($_POST['token']) : '');

if (!empty($token)) {
    // Check if token exists and is not expired based on MySQL's NOW()
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $valid_token = true;
    } else {
        $error_message = "This password reset link is invalid or has expired.";
    }
    $stmt->close();
} else {
    $error_message = "No reset token provided.";
}

// Handle New Password Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        $raw_password = $new_password;

        // Update resident password with plain raw text
        $update_stmt = $conn->prepare("UPDATE residents SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $raw_password, $email);
        
        if ($update_stmt->execute()) {
            // Delete token after successful use
            $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del_stmt->bind_param("s", $email);
            $del_stmt->execute();
            $del_stmt->close();

            $success_message = "Your password has been successfully updated! You can now login.";
            $valid_token = false; 
        } else {
            $error_message = "Error updating password. Please try again.";
        }
        $update_stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password - Barangay Malitbog</title>
    <!-- Font Awesome Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #000066;
            --secondary-blue: #1a1a8c;
            --accent-blue: #294a71;
            --text-gray: #555;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: #f0f4ff; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .card { 
            background: white; 
            padding: 30px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
            box-sizing: border-box; 
        }
        h2 { 
            color: var(--primary-blue); 
            margin-top: 0; 
            font-size: 1.2rem; 
            text-align: center; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            display: block; 
            font-size: 0.85rem; 
            font-weight: 600; 
            margin-bottom: 5px; 
            color: var(--accent-blue); 
        }
        input { 
            width: 100%; 
            padding: 10px 14px; 
            border: 1px solid #d1d5db; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 0.9rem;
            background-color: #f9fbfd;
        }

        /* Hide browser's default native password reveal icon & auto-fill buttons */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-contacts-auto-fill-button {
            visibility: hidden;
            display: none !important;
            pointer-events: none;
            height: 0;
            width: 0;
            margin: 0;
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        button { 
            width: 100%; 
            background: var(--primary-blue); 
            color: white; 
            padding: 11px; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            transition: background 0.3s ease;
        }
        button:hover { 
            background: var(--secondary-blue); 
        }
        .alert-error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 10px; 
            border-radius: 6px; 
            font-size: 0.85rem; 
            margin-bottom: 15px; 
            text-align: center; 
        }
        .alert-success { 
            background: #d4edda; 
            color: #155724; 
            padding: 10px; 
            border-radius: 6px; 
            font-size: 0.85rem; 
            margin-bottom: 15px; 
            text-align: center; 
        }
        .login-link { 
            text-align: center; 
            margin-top: 15px; 
            font-size: 0.85rem; 
        }
        .login-link a { 
            color: var(--secondary-blue); 
            text-decoration: none; 
            font-weight: bold; 
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Set New Password</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <div class="login-link"><a href="forgot_password.php">Request a new reset link</a></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <div class="login-link"><a href="resident_portal.php">Click here to Login</a></div>
        <?php elseif ($valid_token): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" required placeholder="At least 6 characters" style="padding-right: 40px;">
                        <i class="fa-solid fa-eye-slash toggle-password" data-target="new_password"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password" style="padding-right: 40px;">
                        <i class="fa-solid fa-eye-slash toggle-password" data-target="confirm_password"></i>
                    </div>
                </div>

                <button type="submit">Update Password</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Password Visibility Toggle Script -->
    <script>
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                
                if (passwordInput) {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                }
            });
        });
    </script>
</body>
</html>