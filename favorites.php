<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];

// Fetch favorited properties for this user
$sql = "SELECT p.* FROM properties p 
        JOIN favorites f ON p.id = f.property_id 
        WHERE f.user_id = ? AND (p.status = 'approved' OR p.user_id = ?)
        ORDER BY f.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $userId]);
$favoritedProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Sapanko Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .favorites-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .favorites-header h2 {
            font-size: 24px;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empty-favorites {
            text-align: center;
            padding: 60px 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            margin: 40px 0;
        }

        .empty-favorites-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }

        .empty-favorites h3 {
            font-size: 20px;
            color: #334155;
            margin-bottom: 10px;
        }

        .empty-favorites p {
            color: #64748b;
            margin-bottom: 20px;
        }

        .card-header-wrapper {
            position: relative;
        }

        .favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.2s ease;
            z-index: 5;
        }

        .favorite-btn:hover {
            transform: scale(1.15);
            background: #ffffff;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container header-content">
        <div class="header-left">
            <a href="index.php" class="secondary-btn">← All Properties</a>
        </div>

        <h1 class="header-title">My Favorites</h1>

        <div class="header-right">
            <a href="inquiries.php" class="secondary-btn" style="margin-right: 8px;">Inquiries</a>
            <a href="add.php" class="primary-btn">Add Property</a>
            <a href="logout.php" class="secondary-btn" style="margin-left: 8px;">Logout</a>
        </div>
    </div>
</header>

<main class="container">
    <div class="favorites-header">
        <h2>Favorited Properties (<?= count($favoritedProperties) ?>)</h2>
    </div>

    <?php if (empty($favoritedProperties)): ?>
        <div class="empty-favorites">
            <div class="empty-favorites-icon">&#9825;</div>
            <h3>No favorites saved yet</h3>
            <p>Click the heart icon on any property card to save it to your favorites list.</p>
            <a href="index.php" class="primary-btn">Browse Properties</a>
        </div>
    <?php else: ?>
        <section class="property-grid">
            <?php foreach ($favoritedProperties as $row): 
                $title    = htmlspecialchars($row['title']);
                $location = htmlspecialchars($row['location']);
                $type     = htmlspecialchars($row['type']);
                $price    = number_format($row['price']);
                $image    = htmlspecialchars($row['image']);
                $id       = intval($row['id']);
                $landArea = !empty($row['land_area']) ? htmlspecialchars($row['land_area']) : '';
                $canManage = isset($_SESSION["user_id"]) && ((int)$row["user_id"] === (int)$_SESSION["user_id"] || ($_SESSION["role"] ?? "") === "admin");
            ?>
                <div class='card' onclick="viewProperty(<?= $id ?>)" style='cursor: pointer;'>
                    <div class="card-header-wrapper">
                        <img src='uploads/<?= $image ?>' alt='<?= $title ?>'>
                        <button class="favorite-btn favorited" onclick="event.stopPropagation(); toggleFavorite(<?= $id ?>, this);" title="Remove from Favorites">
                            &hearts;
                        </button>
                    </div>
                    <div class='card-content'>
                        <h3><?= $title ?></h3>
                        <p><strong>Location:</strong> <?= $location ?></p>
                        <p><strong>Type:</strong> <?= $type ?></p>
                        <?php if ($landArea): ?>
                            <p><strong>Land:</strong> <?= $landArea ?></p>
                        <?php endif; ?>
                        <div class='price'>Rs <?= $price ?></div>
                        
                        <?php if ($canManage): ?>
                            <div class='actions'>
                                <a class='edit' href='edit.php?id=<?= $id ?>' onclick='event.stopPropagation();'>Edit</a>
                                <a class='delete' href='delete.php?id=<?= $id ?>' onclick='event.stopPropagation();'>Delete</a>
                            </div>
                        <?php else: ?>
                            <div style='text-align: center; margin-top: 10px;'>
                                <a class='view-details-btn' href='details.php?id=<?= $id ?>' onclick='event.stopPropagation();' style='display: inline-block; background: #0b192c; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: bold;'>View Details</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<script src="js/script.js"></script>
</body>
</html>
