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
$debug_output = '';  // Initialize debug output variable

// Debug: Current server time
$debug_output .= "Debug: Current server time: " . date('Y-m-d H:i:s') . "<br>";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Debug: Print the token
    $debug_output .= "Debug: Token received: " . htmlspecialchars($token) . "<br>";
    
    // Verify the token
    $sql = "SELECT * FROM users3 WHERE reset_token = $1 AND reset_token_expiry > '2000-01-01'";
    $result = pg_query_params($conn, $sql, array($token));

    // Debug: Print the SQL query and result
    $debug_output .= "Debug: SQL Query: " . $sql . "<br>";
    $debug_output .= "Debug: Token used in query: " . $token . "<br>";
    $debug_output .= "Debug: Query result: " . ($result ? "Success" : "Failure") . "<br>";
    $debug_output .= "Debug: Number of rows: " . pg_num_rows($result) . "<br>";

    if ($result && pg_num_rows($result) > 0) {
        // Token is valid, show the reset password form
        $show_form = true;
    } else {
        $error_message = "Invalid or expired token. Please request a new password reset.";
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update the SQL query to set password_changed_at to the current timestamp
        $sql = "UPDATE users3 SET password = $1, reset_token = NULL, reset_token_expiry = NULL, password_changed_at = NOW() WHERE reset_token = $2 RETURNING *";
        $result = pg_query_params($conn, $sql, array($hashed_password, $token));

        if ($result && pg_num_rows($result) > 0) {
            $success_message = "Your password has been successfully reset. You can now log in with your new password.";
            $show_form = false;
            
            // Debug: Print the updated row
            $updated_row = pg_fetch_assoc($result);
            $debug_output .= "Debug: Password changed at: " . $updated_row['password_changed_at'] . "<br>";
        } else {
            $error_message = "Failed to reset password. Please try again.";
            $show_form = true;
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