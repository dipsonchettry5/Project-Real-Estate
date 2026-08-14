<?php
session_start();
require "config.php";

$location = $_GET['location'] ?? '';
$type     = $_GET['type']     ?? '';
$min      = $_GET['min_price'] ?? '';
$max      = $_GET['max_price'] ?? '';

$isAdmin = ($_SESSION["role"] ?? "") === "admin";
$userId  = $_SESSION["user_id"] ?? null;

if ($isAdmin) {
    $sql    = "SELECT * FROM properties WHERE status = 'approved'";
    $params = [];
} elseif ($userId) {
    $sql    = "SELECT * FROM properties WHERE (status = 'approved' OR user_id = ?)";
    $params = [$userId];
} else {
    $sql    = "SELECT * FROM properties WHERE status = 'approved'";
    $params = [];
}

if ($location !== '') {
    $sql     .= " AND location LIKE ?";
    $params[] = "%$location%";
}
if ($type !== '') {
    $sql     .= " AND type = ?";
    $params[] = $type;
}
if ($min !== '') {
    $sql     .= " AND price >= ?";
    $params[] = intval($min);
}
if ($max !== '') {
    $sql     .= " AND price <= ?";
    $params[] = intval($max);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch()) {
    $title    = htmlspecialchars($row['title']);
    $location = htmlspecialchars($row['location']);
    $type     = htmlspecialchars($row['type']);
    $price    = number_format($row['price']);
    $image    = htmlspecialchars($row['image']);
    $id       = intval($row['id']);

    echo "
    <div class='card' onclick=\"viewProperty({$id})\" style='cursor: pointer;'>
        <img src='uploads/{$image}' alt='{$title}'>
        <div class='card-content'>
            <h3>{$title}</h3>
            <p>{$location}</p>
            <p>{$type}</p>
            <div class='price'>Rs {$price}</div>";

    $canManage = isset($_SESSION["user_id"]) &&
        ((int)$row["user_id"] === (int)$_SESSION["user_id"] || ($_SESSION["role"] ?? "") === "admin");

    if ($canManage) {
        echo "
            <div class='actions'>
                <a class='edit' href='edit.php?id={$id}' onclick='event.stopPropagation();'>Edit</a>
                <a class='delete' href='delete.php?id={$id}' onclick='event.stopPropagation();'>Delete</a>
            </div>";
    } else {
        echo "
            <div style='text-align: center; margin-top: 10px;'>
                <a class='view-details-btn' href='details.php?id={$id}' onclick='event.stopPropagation();' style='display: inline-block; background: #667eea; color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: bold;'>View Details</a>
            </div>";
    }

    echo "
        </div>
    </div>";
}
?>

