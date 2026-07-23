<?php
session_start();
require "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        $message = "Username already exists!";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (username, password) VALUES (?, ?)"
        );

        if ($stmt->execute([$username, $hashedPassword])) {
            $message = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $message = "Registration failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="form-card">
    <h2>Register</h2>

    <form method="post">
        <input name="username" placeholder="Username" required>

        <input type="password"
               name="password"
               placeholder="Password"
               required>

        <button class="primary-btn">Register</button>
    </form>

    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>

    <p>Already have an account?
        <a href="login.php">Login</a>
    </p>
</div>
</body>
</html>