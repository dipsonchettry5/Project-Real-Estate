git a<?php
session_start();
require "config.php";

// Get property ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch property details
$sql = "SELECT * FROM properties WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

// If property not found, redirect
if (!$property) {
    header("Location: index.php");
    exit;
}

// Prepare data
$title = htmlspecialchars($property['title']);
$location = htmlspecialchars($property['location']);
$type = htmlspecialchars($property['type']);
$price = number_format($property['price']);
$image = htmlspecialchars($property['image']);
$createdAt = date("F j, Y", strtotime($property['created_at']));
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?> - Real Estate</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .details-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .details-header {
            position: relative;
        }

        .details-header img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            display: block;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
            z-index: 10;
        }

        .back-button:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .details-content {
            padding: 40px;
        }

        .details-content h1 {
            font-size: 36px;
            color: #333;
            margin: 0 0 20px 0;
        }

        .property-meta {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .meta-value {
            font-size: 18px;
            color: #333;
            font-weight: 600;
            margin-top: 5px;
        }

        .price-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 8px;
            margin: 30px 0;
            font-size: 28px;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-contact {
            flex: 1;
            min-width: 200px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-favorite {
            flex: 1;
            min-width: 200px;
            padding: 15px 30px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-favorite:hover {
            background: #f8f9ff;
            transform: translateY(-2px);
        }

        .edit-delete-section {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }

        .btn-edit, .btn-delete {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-edit {
            background: #4CAF50;
            color: white;
        }

        .btn-edit:hover {
            background: #45a049;
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #da190b;
        }

        .property-description {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }

        .property-description h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        .description-text {
            font-size: 16px;
            line-height: 1.8;
            color: #666;
        }

        .posted-info {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 768px) {
            .details-content {
                padding: 20px;
            }

            .details-content h1 {
                font-size: 24px;
            }

            .property-meta {
                gap: 15px;
            }

            .details-header img {
                height: 300px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-contact, .btn-favorite {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container header-content">
        <div class="header-left">
            <?php if (isset($_SESSION["user"])): ?>
                <a href="logout.php" class="secondary-btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="secondary-btn">Login</a>
            <?php endif; ?>
        </div>

        <h1 class="header-title">Real Estate Listings</h1>

        <div class="header-right">
            <?php if (isset($_SESSION["user"])): ?>
                <a href="add.php" class="primary-btn">Add Property</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container">
    <div class="details-container">
        <div class="details-header">
            <img src="uploads/<?php echo $image; ?>" alt="<?php echo $title; ?>">
            <a href="index.php" class="back-button">← Back to Listings</a>
        </div>

        <div class="details-content">
            <h1><?php echo $title; ?></h1>

            <div class="property-meta">
                <div class="meta-item">
                    <span class="meta-label">Location</span>
                    <span class="meta-value"><?php echo $location; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Property Type</span>
                    <span class="meta-value"><?php echo $type; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Posted On</span>
                    <span class="meta-value"><?php echo $createdAt; ?></span>
                </div>
            </div>

            <div class="price-display">
                Rs <?php echo $price; ?>
            </div>

            <div class="action-buttons">
               
                <button class="btn-favorite" onclick="toggleFavorite()">♥ Add to Favorites</button>
            </div>

            <?php if (isset($_SESSION["user"])): ?>
            <div class="edit-delete-section">
                <a href="edit.php?id=<?php echo $id; ?>" class="btn-edit">Edit Property</a>
                <a href="delete.php?id=<?php echo $id; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this property?');">Delete Property</a>
            </div>
            <?php endif; ?>

            <div class="property-description">
                <h2>About This Property</h2>
                <p class="description-text">
                    This is a beautiful <?php echo strtolower($type); ?> located in <?php echo $location; ?>. 
                    The property is listed at Rs <?php echo $price; ?> and offers excellent value for money. 
                    Contact the agent for more information and to schedule a viewing.
                </p>
            </div>

            <div class="posted-info">
                <strong>Property ID:</strong> #<?php echo str_pad($id, 5, '0', STR_PAD_LEFT); ?><br>
                <strong>Posted on:</strong> <?php echo $createdAt; ?><br>
                <strong>Status:</strong> <span style="color: #4CAF50; font-weight: bold;">Available</span>
            </div>
        </div>
    </div>
</main>

<script>
   

    function toggleFavorite() {
        const btn = event.target;
        if (btn.style.color === 'rgb(244, 67, 54)') {
            btn.style.color = '#667eea';
            btn.textContent = '♥ Add to Favorites';
        } else {
            btn.style.color = '#f44336';
            btn.textContent = '♥ Added to Favorites';
        }
    }
</script>

</body>
</html>
