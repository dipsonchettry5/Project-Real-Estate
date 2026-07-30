<?php
session_start();
require "config.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate inputs
    if (strlen($_POST["username"]) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($_POST["password"]) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $stmt->execute([$_POST["username"]]);
        
        if ($stmt->fetch()) {
            $error = "Username already exists.";
        } else {
            // Determine role based on registration type
            $role = $_POST["register_type"] ?? "user";
            
            // Register new user
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, role) VALUES (?, ?, ?)"
            );
            $stmt->execute([
                $_POST["username"],
                password_hash($_POST["password"], PASSWORD_BCRYPT),
                $role
            ]);
            
            $success = "Registration successful! <a href='login.php'>Login here</a>";
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
    <h2>Create Account</h2>
    
    <!-- Registration Type -->
    <div class="role-selection">
        <label>
            <input type="radio" name="register_type" value="user" checked onchange="document.getElementById('register_type').value='user'"> 
            Register as User
        </label>
        <label>
            <input type="radio" name="register_type" value="admin" onchange="document.getElementById('register_type').value='admin'"> 
            Register as Admin
        </label>
    </div>
    
    <form method="post">
        <input type="hidden" name="register_type" id="register_type" value="user">
        <input name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="primary-btn">Register</button>
        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:green;"><?= $success ?></p>
        <?php endif; ?>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
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