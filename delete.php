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

$ownerId = isset($p["user_id"]) && $p["user_id"] !== null ? (int)$p["user_id"] : 0;
$currentUserId = (int)$_SESSION["user_id"];
$isAdmin = ($_SESSION["role"] ?? "") === "admin";

$isOwner = ($ownerId > 0 && $ownerId === $currentUserId);

if (!$isOwner && !$isAdmin) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pdo->prepare("DELETE FROM properties WHERE id=?")->execute([$id]);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Property</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="form-card">
    <p>Are you sure you want to delete this property?</p>
    <form method="post">
        <div class="form-actions">
            <button class="primary-btn">Yes, Delete</button>
            <a href="index.php" class="secondary-btn">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
