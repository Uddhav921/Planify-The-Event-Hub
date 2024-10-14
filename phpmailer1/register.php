<?php
session_start();

// Establish PostgreSQL connection
$conn = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password='uddhav taur7777'");
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];

    $table_name = $user_type == 'admin' ? 'admins' : ($user_type == 'hotel' ? 'hotels' : 'users3');

    // Check if email already exists
    $check_email_sql = "SELECT * FROM $table_name WHERE email = $1";
    $check_email_result = pg_query_params($conn, $check_email_sql, array($email));

    if (pg_num_rows($check_email_result) > 0) {
        $error_message = "Email already registered. Please use a different email or try logging in.";
    } else {
        if ($user_type == 'hotel') {
            $hotel_name = $_POST['hotel_name'];
            $location = $_POST['location'];
            $sql = "INSERT INTO hotels (username, email, password, hotel_name, location, password_changed_at) VALUES ($1, $2, $3, $4, $5, NOW())";
            $result = pg_query_params($conn, $sql, array($username, $email, $password, $hotel_name, $location));
        } else {
            $sql = "INSERT INTO $table_name (username, email, password, password_changed_at) VALUES ($1, $2, $3, NOW())";
            $result = pg_query_params($conn, $sql, array($username, $email, $password));
        }

        if ($result) {
            $success_message = "Registration successful. Please log in.";
        } else {
            $error_message = "Registration failed: " . pg_last_error($conn);
        }
    }
}

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input[type="text"], input[type="email"], input[type="password"], select { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .auth-btn { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .error-message { color: red; margin-bottom: 10px; }
        .success-message { color: green; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Registration</h2>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="user_type" id="user_type" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="hotel">Hotel</option>
            </select>
            <div id="hotel_fields" style="display: none;">
                <input type="text" name="hotel_name" placeholder="Hotel Name">
                <input type="text" name="location" placeholder="Location">
            </div>
            <button type="submit" name="register" class="auth-btn">Register</button>
        </form>
        <?php
        if ($error_message) {
            echo "<p class='error-message'>$error_message</p>";
        }
        if ($success_message) {
            echo "<p class='success-message'>$success_message</p>";
        }
        ?>
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>
    <script>
        document.getElementById('user_type').addEventListener('change', function() {
            var hotelFields = document.getElementById('hotel_fields');
            if (this.value === 'hotel') {
                hotelFields.style.display = 'block';
            } else {
                hotelFields.style.display = 'none';
            }
        });
    </script>
</body>
</html>