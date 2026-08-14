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

$userFavorites = [];
if ($userId) {
    $favStmt = $pdo->prepare("SELECT property_id FROM favorites WHERE user_id = ?");
    $favStmt->execute([$userId]);
    $userFavorites = $favStmt->fetchAll(PDO::FETCH_COLUMN);
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
    $landArea  = !empty($row['land_area']) ? htmlspecialchars($row['land_area']) : '';
    $furnished = !empty($row['furnished_status']) ? htmlspecialchars($row['furnished_status']) : '';
    $road      = !empty($row['road_width']) ? htmlspecialchars($row['road_width']) : '';

    $isFav      = in_array($id, $userFavorites);
    $heartIcon  = $isFav ? '&hearts;' : '&#9825;';
    $heartClass = $isFav ? 'favorited' : '';
    $favTitle   = $isFav ? 'Remove from Favorites' : 'Save to Favorites';

    $landAreaHtml  = $landArea !== '' ? "<p><strong>Land:</strong> {$landArea}</p>" : "";
    $roadHtml      = $road !== '' ? "<p><strong>Road Access:</strong> {$road}</p>" : "";
    $furnishedHtml = $furnished !== '' ? "<p><strong>Furnished:</strong> {$furnished}</p>" : "";

    echo "
    <div class='card' onclick=\"viewProperty({$id})\" style='cursor: pointer;'>
        <div class='card-header-wrapper'>
            <img src='uploads/{$image}' alt='{$title}'>
            <button class='favorite-btn {$heartClass}' onclick='event.stopPropagation(); toggleFavorite({$id}, this);' title='{$favTitle}'>
                {$heartIcon}
            </button>
        </div>
        <div class='card-content'>
            <h3>{$title}</h3>
            <p><strong>Location:</strong> {$location}</p>
            <p><strong>Type:</strong> {$type}</p>
            {$landAreaHtml}
            {$roadHtml}
            {$furnishedHtml}
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
                <a class='view-details-btn' href='details.php?id={$id}' onclick='event.stopPropagation();' style='display: inline-block; background: #0b192c; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: bold;'>View Details</a>
            </div>";
    }

    echo "
        </div>
    </div>";
}
?>

