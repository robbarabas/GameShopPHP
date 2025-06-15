<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if user exists
    $check = $conn->prepare("SELECT user_id FROM Users WHERE username=? OR email=?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Username or email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed.";
        }
    }
}
?>

<!DOCTYPE html>

<html>
<head><title>Register</title>
<link rel="stylesheet" href="style.css"></head>
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
