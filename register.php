<?php
session_start();
require "config.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Validate inputs
    if (strlen($username) < 3) {
        $error = "Username/Email must be at least 3 characters.";
    } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $username)) {
        $error = "Registration requires a valid Gmail address (e.g. user@gmail.com).";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email/username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = "An account with this Gmail address already exists.";
        } else {
            // Determine role: always "user" for public registration
            $role = "user";
            
            // Register new user
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, role) VALUES (?, ?, ?)"
            );
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_BCRYPT),
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="password"] {
            padding: 12px 15px;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        form input[type="text"]:focus,
        form input[type="email"]:focus,
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

        .success-message {
            padding: 12px 15px;
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 10px;
            animation: slideDown 0.3s ease-out;
        }

        .success-message a {
            color: #166534;
            font-weight: 600;
            text-decoration: none;
        }

        .success-message a:hover {
            text-decoration: underline;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
    <h2>Create Account</h2>
    
    <form method="post">
        <input type="email" name="username" placeholder="Gmail Address (e.g. user@gmail.com)" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="primary-btn">Register</button>
        <?php if ($error): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success-message"><?= $success ?></p>
        <?php endif; ?>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

</body>
</html>