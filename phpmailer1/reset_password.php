<?php
session_start();

// Establish PostgreSQL connection
$conn = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password='uddhav taur7777'");
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$error_message = '';
$success_message = '';
$show_form = false;

// Current server time
$current_time = date('Y-m-d H:i:s');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token']) && isset($_GET['user_type'])) {
    $token = $_GET['token'];
    $user_type = $_GET['user_type'];
    
    $table_name = $user_type == 'admin' ? 'admins' : ($user_type == 'hotel' ? 'hotels' : 'users3');
    
    // Verify the token
    $sql = "SELECT * FROM $table_name WHERE reset_token = $1";
    $result = pg_query_params($conn, $sql, array($token));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        if (strtotime($row['reset_token_expiry']) > time()) {
            $show_form = true;
        } else {
            $error_message = "Token has expired. Please request a new password reset.";
        }
    } else {
        $error_message = "Invalid token. Please request a new password reset.";
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $user_type = $_POST['user_type'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $table_name = $user_type == 'admin' ? 'admins' : ($user_type == 'hotel' ? 'hotels' : 'users3');

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE $table_name SET password = $1, reset_token = NULL, reset_token_expiry = NULL, password_changed_at = NOW() WHERE reset_token = $2 AND reset_token_expiry > $3 RETURNING *";
        $result = pg_query_params($conn, $sql, array($hashed_password, $token, $current_time));

        if ($result && pg_num_rows($result) > 0) {
            $success_message = "Your password has been successfully reset. You can now log in with your new password.";
            $show_form = false;
        } else {
            $error_message = "Failed to reset password. The token may have expired. Please request a new password reset.";
            $show_form = false;
        }
    } else {
        $error_message = "Passwords do not match. Please try again.";
        $show_form = true;
    }
}

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planify - Reset Password</title>
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

        .form-section input {
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

        .form-section input::placeholder {
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
            <h2>Reset Password</h2>
            <?php if ($error_message): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo $success_message; ?></p>
            <?php endif; ?>
            <?php if ($show_form): ?>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user_type); ?>">
                    <input type="password" name="new_password" placeholder="New Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                    <button type="submit" name="reset_password">Reset Password</button>
                </form>
            <?php endif; ?>
            <?php if ($success_message || $error_message): ?>
                <div class="links">
                    <a href="index.php">Return to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>