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
$debug_output = '';

// Debug: Current server time
$current_time = date('Y-m-d H:i:s');
$debug_output .= "Debug: Current server time: " . $current_time . "<br>";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token']) && isset($_GET['user_type'])) {
    $token = $_GET['token'];
    $user_type = $_GET['user_type'];
    
    $debug_output .= "Debug: Token received: " . htmlspecialchars($token) . "<br>";
    $debug_output .= "Debug: User type: " . htmlspecialchars($user_type) . "<br>";
    
    $table_name = $user_type == 'admin' ? 'admins' : ($user_type == 'hotel' ? 'hotels' : 'users3');
    
    // Verify the token
    $sql = "SELECT * FROM $table_name WHERE reset_token = $1 AND reset_token_expiry > $2";
    $result = pg_query_params($conn, $sql, array($token, $current_time));

    $debug_output .= "Debug: SQL Query: " . $sql . "<br>";
    $debug_output .= "Debug: Query result: " . ($result ? "Success" : "Failure") . "<br>";
    $debug_output .= "Debug: Number of rows: " . pg_num_rows($result) . "<br>";

    if ($result && pg_num_rows($result) > 0) {
        $show_form = true;
        $row = pg_fetch_assoc($result);
        $debug_output .= "Debug: Token expiry: " . $row['reset_token_expiry'] . "<br>";
    } else {
        $error_message = "Invalid or expired token. Please request a new password reset.";
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
            
            $updated_row = pg_fetch_assoc($result);
            $debug_output .= "Debug: Password changed at: " . $updated_row['password_changed_at'] . "<br>";
        } else {
            $error_message = "Failed to reset password. The token may have expired. Please request a new password reset.";
            $show_form = false;
            $debug_output .= "Debug: SQL Error: " . pg_last_error($conn) . "<br>";
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
    <title>Reset Password</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input[type="password"] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .reset-btn { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .error-message { color: red; margin-bottom: 10px; }
        .success-message { color: green; margin-bottom: 10px; }
        .debug-output { background-color: #f0f0f0; padding: 10px; margin-top: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
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
                <button type="submit" name="reset_password" class="reset-btn">Reset Password</button>
            </form>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p><a href="index.php">Return to Login</a></p>
        <?php endif; ?>

        <?php if (!empty($debug_output)): ?>
            <div class="debug-output">
                <?php echo $debug_output; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>