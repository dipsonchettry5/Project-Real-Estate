<?php 
session_start();
require "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$_POST["username"]]);
    $u = $stmt->fetch();

    if ($u && password_verify($_POST["password"], $u["password"])) {
        $_SESSION["user"] = $u["username"];
        header("Location: index.php");
        exit;
    }
    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="form-card">
    <h2>Login</h2>
    <form method="post">
        <input name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="primary-btn">Login</button>
        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </form>
    <p>Don't have an account?
    <a href="register.php">Register</a>
</p>
</div>
</body>
</html>
