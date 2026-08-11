<?php
session_start();
require "config.php";

$location = $_GET['location'] ?? '';
$type     = $_GET['type']     ?? '';
$min      = $_GET['min_price'] ?? '';
$max      = $_GET['max_price'] ?? '';

$sql    = "SELECT * FROM properties WHERE 1";
$params = [];

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

    if (isset($_SESSION["user"])) {
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

