<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
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
    "INSERT INTO properties (user_id, status, title, location, price, type, image, bedrooms, bathrooms, land_area, built_area, amenities, description)
     VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
            $stmt->execute([
                $_SESSION["user_id"],
                $_POST["title"],
                $_POST["location"],
                $_POST["price"],
                $_POST["type"],
                $filename,
                $_POST["bedrooms"] ?: null,
                $_POST["bathrooms"] ?: null,
                $_POST["land_area"] ?: null,
                $_POST["built_area"] ?: null,
                $_POST["amenities"] ?: null,
                $_POST["description"] ?: null
            ]);

            header("Location: index.php?submitted=1");
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
        <!-- Basic Information -->
        <fieldset>
            <legend>Basic Information</legend>
            <input name="title" placeholder="Property Title" required> <br>
            <input name="location" placeholder="Location" required> <br>
            <input type="number" name="price" placeholder="Price (Rs)" required> <br>
            <select name="type" required>
                <option value="">Select Property Type</option>
                <option>Apartment</option>
                <option>House</option>
                <option>Villa</option>
                <option>Land</option>
                <option>Commercial</option>
            </select> <br>
            <input type="file" name="image" accept="image/*" required>
        </fieldset>

        <!-- Land & Property Details -->
        <fieldset>
            <legend>Land & Property Details</legend>
            <div class="form-row">
                <input type="number" name="bedrooms" placeholder="Bedrooms" min="0" step="1">
                <input type="number" name="bathrooms" placeholder="Bathrooms" min="0" step="0.5">
            </div>
            <div class="form-row">
                <input type="number" name="land_area" placeholder="Land Area (sq ft)" min="0" step="0.01">
                <input type="number" name="built_area" placeholder="Built-up Area (sq ft)" min="0" step="0.01">
            </div>
            <textarea name="amenities" placeholder="Amenities (e.g., Pool, Parking, Garden, Gym)" rows="2"></textarea>
            <textarea name="description" placeholder="Property Description" rows="4"></textarea>
        </fieldset>

        <div class="form-actions">
            <a href="index.php" class="secondary-btn">Cancel</a>
            <button class="primary-btn">Save</button>
        </div>
    </form>
</div>
</main>
</body>
</html>