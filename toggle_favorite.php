<?php
session_start();
require "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in', 'message' => 'Please log in to save favorites.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : (isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0);

if ($propertyId <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_id', 'message' => 'Invalid property ID.']);
    exit;
}

// Check if already favorited
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
$stmt->execute([$userId, $propertyId]);
$fav = $stmt->fetch();

if ($fav) {
    // Remove from favorites
    $deleteStmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND property_id = ?");
    $deleteStmt->execute([$userId, $propertyId]);
    $isFavorite = false;
} else {
    // Add to favorites
    $insertStmt = $pdo->prepare("INSERT INTO favorites (user_id, property_id) VALUES (?, ?)");
    $insertStmt->execute([$userId, $propertyId]);
    $isFavorite = true;
}

// Return JSON response for AJAX or redirect for fallback
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode(['success' => true, 'is_favorite' => $isFavorite]);
    exit;
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: " . $redirect);
exit;
