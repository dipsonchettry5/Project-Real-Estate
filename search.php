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
    <div class='card'>
        <img src='uploads/{$image}' alt='{$title}'>
        <div class='card-content'>
            <h3>{$title}</h3>
            <p>{$location}</p>
            <p>{$type}</p>
            <div class='price'>Rs {$price}</div>";

    if (isset($_SESSION["user"])) {
        echo "
            <div class='actions'>
                <a class='edit' href='edit.php?id={$id}'>Edit</a>
                <a class='delete' href='delete.php?id={$id}'>Delete</a>
            </div>";
    }

    echo "
        </div>
    </div>";
}
