<?php
session_start();

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Update these paths to match your PHPMailer installation
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Establish PostgreSQL connection
$conn = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password='uddhav taur7777'");
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$error_message = '';
$success_message = '';

// Registration functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $check_email_sql = "SELECT * FROM users3 WHERE email = $1";
    $check_email_result = pg_query_params($conn, $check_email_sql, array($email));

    if (pg_num_rows($check_email_result) > 0) {
        $error_message = "Email already registered. Please use a different email or try logging in.";
    } else {
        $sql = "INSERT INTO users3 (username, email, password, password_changed_at) VALUES ($1, $2, $3, NOW())";
        $result = pg_query_params($conn, $sql, array($username, $email, $password));

        if ($result) {
            $success_message = "Registration successful. Please log in.";
        } else {
            $error_message = "Registration failed: " . pg_last_error($conn);
        }
    }
}

// Login functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    $sql = "SELECT * FROM users3 WHERE username = $1";
    $result = pg_query_params($conn, $sql, array($username));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            
            if (empty($row['email']) && !empty($email)) {
                $update_sql = "UPDATE users3 SET email = $1 WHERE username = $2";
                $update_result = pg_query_params($conn, $update_sql, array($email, $username));
                
                if (!$update_result) {
                    $error_message = "Failed to update email: " . pg_last_error($conn);
                }
            }
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }
}

// Forgot password functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    $email = $_POST['email'];
    
    $sql = "SELECT * FROM users3 WHERE email = $1";
    $result = pg_query_params($conn, $sql, array($email));

    if ($result && pg_num_rows($result) > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $update_sql = "UPDATE users3 SET reset_token = $1, reset_token_expiry = $2 WHERE email = $3";
        $update_result = pg_query_params($conn, $update_sql, array($token, $expiry, $email));

        if ($update_result) {
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'uddhavtour29@gmail.com';
                $mail->Password   = 'caig eang hmah vacx';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                //Recipients
                $mail->setFrom('uddhavtour29@gmail.com', 'Uddhav tour');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $reset_link = "http://localhost/Planify-The%20Event%20Hub/phpmailer1/reset_password.php?token=" . $token;
                $mail->Body    = "Click the link to reset your password: <a href='$reset_link'>Reset Password</a>";

                $mail->send();
                $success_message = "A password reset link has been sent to your email address.";
            } catch (Exception $e) {
                $error_message = "Failed to send password reset email. Please try again. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error_message = "Error updating reset token: " . pg_last_error($conn);
        }
    } else {
        $error_message = "Email address not found.";
    }
}

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Authentication System</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-container { margin-bottom: 20px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .auth-btn { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .error-message { color: red; margin-bottom: 10px; }
        .success-message { color: green; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-forms">
            <!-- Login Form -->
            <div class="form-container">
                <h2>Login</h2>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="email" name="email" placeholder="Email (optional)">
                    <button type="submit" name="login" class="auth-btn">Login</button>
                </form>
            </div>

            <!-- Registration Form -->
            <div class="form-container">
                <h2>Register</h2>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="register" class="auth-btn">Register</button>
                </form>
            </div>

            <!-- Forgot Password Form -->
            <div class="form-container">
                <h2>Forgot Password</h2>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit" name="forgot_password" class="auth-btn">Send Reset Link</button>
                </form>
            </div>

            <?php
            if ($error_message) {
                echo "<p class='error-message'>$error_message</p>";
            }
            if ($success_message) {
                echo "<p class='success-message'>$success_message</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>