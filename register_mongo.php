<?php
include 'db_mongo.php'; // MongoDB connection

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if user with the same username or email exists
    $existingUser = $db->users->findOne([
        '$or' => [
            ['username' => $username],
            ['email' => $email]
        ]
    ]);

    if ($existingUser) {
        $error = "Username or email already exists.";
    } else {
        $insertResult = $db->users->insertOne([
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash,
            'is_admin' => false // Default to non-admin
        ]);

        if ($insertResult->getInsertedCount() === 1) {
            header("Location: login_mongo.php");
            exit();
        } else {
            $error = "Registration failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Register</h2>
<form method="POST" action="">
    Username: <input name="username" required><br>
    Email: <input type="email" name="email" required><br>
    Password: <input type="password" name="password" required><br>
    <button type="submit">Register</button>
</form>
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>
