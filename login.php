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
        $_SESSION["username"] = $u["username"];
        $_SESSION["role"] = $u["role"];
        $_SESSION["user_id"] = $u["id"];
        
        if ($u["role"] === "admin") {
            header("Location: admin.php");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0b192c 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(11, 25, 44, 0.4);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-card h2 {
            text-align: center;
            color: #0b192c;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 700;
        }

        .role-selection {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .role-selection label {
            display: inline-block;
            margin-right: 20px;
            cursor: pointer;
            font-size: 14px;
            color: #334155;
            user-select: none;
        }

        .role-selection label:hover {
            color: #1e3a8a;
        }

        .role-selection input[type="radio"] {
            margin-right: 8px;
            cursor: pointer;
            accent-color: #1e3a8a;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        form input[type="text"],
        form input[type="password"] {
            padding: 12px 15px;
            border: 2px solid #cbd5e1;
            border-radius: 8px; 
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        form input[type="text"]:focus,
        form input[type="password"]:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.15);
        }

        form input::placeholder {
            color: #94a3b8;
        }

        .primary-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #0b192c 0%, #1e3a8a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .primary-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.4);
        }

        .primary-btn:active {
            transform: translateY(0);
        }

        .error-message {
            padding: 12px 15px;
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 10px;
            animation: shake 0.3s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-card p {
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            font-size: 14px;
        }

        .form-card a {
            color: #1e3a8a;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-card a:hover {
            color: #0b192c;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .form-card {
                padding: 30px 20px;
            }

            .form-card h2 {
                font-size: 24px;
                margin-bottom: 25px;
            }

            .role-selection label {
                display: block;
                margin-bottom: 12px;
                margin-right: 0;
            }
        }
    </style>
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
        <input type="text" name="username" placeholder="Username / Gmail" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="primary-btn">Login</button>
        <?php if ($error): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </form>
    <p>Don't have an account? <a href="register.php">Register</a></p>
</div>

</body>
</html>