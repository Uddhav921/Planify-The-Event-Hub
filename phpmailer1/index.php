<?php
session_start();

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

// Login functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    $table_name = $user_type == 'admin' ? 'admins' : ($user_type == 'hotel' ? 'hotels' : 'users3');
    
    $sql = "SELECT * FROM $table_name WHERE username = $1";
    $result = pg_query_params($conn, $sql, array($username));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_id'] = $row['id'];
            
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
    $user_type = $_POST['user_type'];

    $table_name = $user_type == 'admin' ? 'admins' : ($user_type == 'hotel' ? 'hotels' : 'users3');
    
    $sql = "SELECT * FROM $table_name WHERE email = $1";
    $result = pg_query_params($conn, $sql, array($email));

    if ($result && pg_num_rows($result) > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $update_sql = "UPDATE $table_name SET reset_token = $1, reset_token_expiry = $2 WHERE email = $3";
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
                $reset_link = "http://localhost/Planify-The%20Event%20Hub/phpmailer1/reset_password.php?token=" . $token . "&user_type=" . $user_type;
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
        input[type="text"], input[type="email"], input[type="password"], select { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
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
                    <select name="user_type" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="hotel">Hotel</option>
                    </select>
                    <button type="submit" name="login" class="auth-btn">Login</button>
                </form>
            </div>

            <!-- Forgot Password Form -->
            <div class="form-container">
                <h2>Forgot Password</h2>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <select name="user_type" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="hotel">Hotel</option>
                    </select>
                    <button type="submit" name="forgot_password" class="auth-btn">Send Reset Link</button>
                </form>
            </div>

            <!-- Registration Link -->
            <div class="form-container">
                <h2>Don't have an account?</h2>
                <p><a href="register.php">Register New Account</a></p>
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