<?php
session_start();
include 'db_mongo.php'; // MongoDB connection

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Find user by username
    $user = $db->users->findOne(['username' => $username]);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (string)$user['_id']; // store as string for compatibility
        $_SESSION['user'] = $username;
        header("Location: index_mongo.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Login</h2>
<form method="POST" action="">
    Username: <input name="username" required><br>
    Password: <input type="password" name="password" required><br>
    <button type="submit">Login</button>
</form>
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<p>Don't have an account? <a href="register_mongo.php">Register here</a></p>
</body>
</html>
