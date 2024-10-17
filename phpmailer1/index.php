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
    <title>Planify - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #6e3c97, #d18cff);
        }

        .container {
            display: flex;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 1000px;
            width: 95%;
        }

        .logo-section, .form-section {
            padding: 50px;
        }

        .logo-section {
            background: linear-gradient(135deg, #612d91, #a64dff);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 40%;
        }

        .logo-section img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        .logo-section h1 {
            color: #fff;
            margin: 20px 0 10px;
            font-size: 3em;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .logo-section p {
            color: #fff;
            font-size: 1.3em;
            font-weight: 300;
            text-align: center;
        }

        .form-section {
            background: linear-gradient(135deg, #4c2a7b, #9d6ab3);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 60%;
        }

        .form-section h2 {
            color: #fff;
            margin-bottom: 30px;
            font-size: 2.5em;
            font-weight: 600;
        }

        .form-section form {
            width: 100%;
            max-width: 400px;
        }

        .form-section input, .form-section select {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            border: none;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 1.1em;
            font-family: 'Poppins', Arial, sans-serif;
        }

        .form-section input::placeholder, .form-section select {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-section button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background-color: #b36ed3;
            color: #fff;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-section button:hover {
            background-color: #9c58b2;
        }

        .links {
            margin-top: 20px;
            text-align: center;
        }

        .links a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
            font-size: 1em;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #b36ed3;
        }

        .error-message, .success-message {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            font-size: 1em;
        }

        .error-message {
            border: 1px solid #ff6b6b;
        }

        .success-message {
            border: 1px solid #51cf66;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .logo-section, .form-section {
                width: 100%;
                padding: 30px;
            }

            .logo-section h1 {
                font-size: 2.5em;
            }

            .logo-section p {
                font-size: 1.1em;
            }

            .form-section h2 {
                font-size: 2em;
            }
        }
        .form-section select {
    width: 100%;
    padding: 15px;
    margin-bottom: 20px;
    border: none;
    border-radius: 8px;
    background-color: rgba(255, 255, 255, 0.2);
    color: #fff;
    font-size: 1.1em;
    font-family: 'Poppins', Arial, sans-serif;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg fill='white' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
    background-repeat: no-repeat;
    background-position: right 15px top 50%;
    cursor: pointer;
}

.form-section select::-ms-expand {
    display: none;
}

.form-section select option {
    background-color: #4c2a7b;
    color: #fff;
}

.form-section select:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
}
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <img src="planify.webp" alt="Event Hub Logo">
            <h1>PLANIFY</h1>
            <p>The Event Hub</p>
        </div>
        <div class="form-section">
            <h2>Login</h2>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="user_type" required>
                    <option value="" disabled selected>Select user type</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="hotel">Hotel</option>
                </select>
                <button type="submit" name="login">Login</button>
            </form>
            <div class="links">
                <a href="register.php">Create an account</a>
                <a href="#" onclick="showForgotPassword()">Forgot password?</a>
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

    <script>
        function showForgotPassword() {
            document.querySelector('.form-section').innerHTML = `
                <h2>Forgot Password</h2>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <select name="user_type" required>
                        <option value="" disabled selected>Select user type</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="hotel">Hotel</option>
                    </select>
                    <button type="submit" name="forgot_password">Send Reset Link</button>
                </form>
                <div class="links">
                    <a href="index.php">Back to Login</a>
                </div>
            `;
        }
    </script>
</body>
</html>