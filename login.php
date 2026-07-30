<?php 
session_start();
require "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = $_POST["login_type"] ?? "user";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND role=?");
    $stmt->execute([$_POST["username"], $role]);
    $u = $stmt->fetch();

    if ($u && password_verify($_POST["password"], $u["password"])) {
        $_SESSION["user"] = $u["username"];
        $_SESSION["role"] = $u["role"];
        
        if ($u["role"] === "admin") {
            header("Location: dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    }
    $error = "Invalid credentials for selected role.";
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
    
    <!-- Role Selection -->
    <div class="role-selection">
        <label>
            <input type="radio" name="login_type" value="user" checked onchange="document.getElementById('login_type').value='user'"> 
            User Login
        </label>
        <label>
            <input type="radio" name="login_type" value="admin" onchange="document.getElementById('login_type').value='admin'"> 
            Admin Login
        </label>
    </div>

    <form method="post">
        <input type="hidden" name="login_type" id="login_type" value="user">
        <input name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="primary-btn">Login</button>
        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </form>
    <p>Don't have an account? <a href="register.php">Register</a></p>
</div>

<style>
.role-selection {
    margin-bottom: 20px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
}

.role-selection label {
    display: inline-block;
    margin-right: 20px;
    cursor: pointer;
}

.role-selection input[type="radio"] {
    margin-right: 8px;
    cursor: pointer;
}
</style>
</body>
</html>