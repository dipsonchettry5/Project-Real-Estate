<?php
session_start();
require "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate image type
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!isset($_FILES["image"]) || !in_array($_FILES["image"]["type"], $allowed)) {
        $error = "Only JPG, PNG, GIF, or WEBP image files are allowed.";
    } else {
        $filename = time() . "_" . basename($_FILES["image"]["name"]);
        $target = __DIR__ . "/uploads/" . $filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
            $stmt = $pdo->prepare(
                "INSERT INTO properties (title, location, price, type, image)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $_POST["title"],
                $_POST["location"],
                $_POST["price"],
                $_POST["type"],
                $filename
            ]);

            header("Location: index.php");
            exit;
        } else {
            $error = "Failed to upload image. Make sure the uploads/ folder exists and is writable.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Property</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <div class="container header-content">
        <h1>Add Property</h1>
    </div>
</header>

<main class="container">
<div class="form-card">
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="property-form">
        <input name="title" placeholder="Title" required>
        <input name="location" placeholder="Location" required>
        <input type="number" name="price" placeholder="Price (Rs)" required>
        <select name="type">
            <option>Apartment</option>
            <option>House</option>
            <option>Villa</option>
        </select>
        <input type="file" name="image" accept="image/*" required>
        <div class="form-actions">
            <a href="index.php" class="secondary-btn">Cancel</a>
            <button class="primary-btn">Save</button>
        </div>
    </form>
</div>
</main>
</body>
</html>
