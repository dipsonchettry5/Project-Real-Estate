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
    "INSERT INTO properties (user_id, status, title, location, price, type, image, bedrooms, bathrooms, land_area, built_area, furnished_status, road_width, amenities, description)
     VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
                $_POST["furnished_status"] ?: null,
                $_POST["road_width"] ?: null,
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

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; font-weight: 600; color: #0b192c; margin-bottom: 6px; display: block;">
                    Land Area (Nepali & Standard Units)
                </label>
                <input type="text" id="land_area" name="land_area" placeholder="e.g. 4 Aana, 1 Ropani 2 Aana, 10 Katha, 1200 Sq Ft" style="width: 100%; margin-bottom: 8px;">

                <!-- Ropani - Aana Builder -->
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <div style="font-size: 13px; font-weight: 600; color: #1e3a8a; margin-bottom: 8px;">
                        Ropani / Aana / Paisa / Daam Builder:
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <input type="number" id="ropani_val" placeholder="Ropani" min="0" style="flex: 1; min-width: 70px;" oninput="updateNepaliLandText()">
                        <input type="number" id="aana_val" placeholder="Aana" min="0" max="16" step="0.25" style="flex: 1; min-width: 70px;" oninput="updateNepaliLandText()">
                        <input type="number" id="paisa_val" placeholder="Paisa" min="0" max="4" step="0.25" style="flex: 1; min-width: 70px;" oninput="updateNepaliLandText()">
                        <input type="number" id="daam_val" placeholder="Daam" min="0" max="4" step="0.25" style="flex: 1; min-width: 70px;" oninput="updateNepaliLandText()">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <input type="number" name="built_area" placeholder="Built-up Area (sq ft)" min="0" step="0.01">
                <input type="text" name="road_width" placeholder="Road Access / Width (e.g. 13 ft, 20 ft pitched)">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; font-weight: 600; color: #0b192c; margin-bottom: 6px; display: block;">Furnished Status</label>
                <select name="furnished_status" style="width: 100%;">
                    <option value="">Select Furnished Status</option>
                    <option value="Full-Furnished">Full-Furnished</option>
                    <option value="Semi-Furnished">Semi-Furnished</option>
                    <option value="Unfurnished">Unfurnished</option>
                </select>
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

<script>
    function updateNepaliLandText() {
        const r = parseFloat(document.getElementById('ropani_val').value) || 0;
        const a = parseFloat(document.getElementById('aana_val').value) || 0;
        const p = parseFloat(document.getElementById('paisa_val').value) || 0;
        const d = parseFloat(document.getElementById('daam_val').value) || 0;

        let parts = [];
        if (r > 0) parts.push(r + ' Ropani');
        if (a > 0) parts.push(a + ' Aana');
        if (p > 0) parts.push(p + ' Paisa');
        if (d > 0) parts.push(d + ' Daam');

        if (parts.length > 0) {
            document.getElementById('land_area').value = parts.join(' ');
        }
    }
</script>
</body>
</html>