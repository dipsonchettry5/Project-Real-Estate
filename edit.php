<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET["id"] ?? 0);
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    header("Location: index.php");
    exit;
}

$isOwner = (int)$p["user_id"] === (int)$_SESSION["user_id"];
$isAdmin = ($_SESSION["role"] ?? "") === "admin";
if (!$isOwner && !$isAdmin) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $pdo->prepare(
        "UPDATE properties SET title=?, location=?, price=?, type=? WHERE id=?"
    );
    $stmt->execute([
        $_POST["title"],
        $_POST["location"],
        $_POST["price"],
        $_POST["type"],
        $id
    ]);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Property</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <div class="container header-content">
        <h1>Edit Property</h1>
    </div>
</header>

<main class="container">
<div class="form-card">
    <form method="post" class="property-form">
        <input name="title" placeholder="Title" value="<?= htmlspecialchars($p['title']) ?>" required>
        <input name="location" placeholder="Location" value="<?= htmlspecialchars($p['location']) ?>" required>
        <input type="number" name="price" placeholder="Price (Rs)" value="<?= $p['price'] ?>" required>
        <select name="type">
            <option <?= $p['type'] === "Apartment" ? "selected" : "" ?>>Apartment</option>
            <option <?= $p['type'] === "House"     ? "selected" : "" ?>>House</option>
            <option <?= $p['type'] === "Villa"     ? "selected" : "" ?>>Villa</option>
        </select>
        <div class="form-actions">
            <a href="index.php" class="secondary-btn">Cancel</a>
            <button class="primary-btn">Update</button>
        </div>
    </form>
</div>
</main>
</body>
</html>
