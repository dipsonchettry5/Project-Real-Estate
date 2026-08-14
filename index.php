<?php
session_start();
require "config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Real Estate Listings</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <div class="container header-content">

        <div class="header-left">
            <?php if (isset($_SESSION["user_id"])): ?>
                <a href="logout.php" class="secondary-btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="secondary-btn">Login</a>
            <?php endif; ?>
        </div>

        <h1 class="header-title">Real Estate Listings</h1>

        <div class="header-right">
            <?php if (isset($_SESSION["user_id"])): ?>
                <a href="add.php" class="primary-btn">Add Property</a>
            <?php endif; ?>
        </div>

    </div>
</header>

<main class="container">
    <?php if (isset($_GET["submitted"])): ?>
    <div style="background:#e7f7ea;border:1px solid #4CAF50;color:#2e7d32;padding:14px 18px;border-radius:6px;margin:20px 0;">
        Your listing was submitted and is awaiting admin approval. It will appear here once approved.
    </div>
<?php endif; ?>

    <section class="filter-panel">

        <div class="autocomplete">
            <input type="text" id="location" placeholder="Location" autocomplete="off">
            <div id="location-list" class="autocomplete-list"></div>
        </div>

        <select id="type">
            <option value="">Property Type</option>
            <option>Apartment</option>
            <option>House</option>
            <option>Villa</option>
            
        </select>

        <input type="number" id="min_price" placeholder="Minimum Price (Rs)">
        <input type="number" id="max_price" placeholder="Maximum Price (Rs)">
        
    </section>

    <section id="results" class="property-grid"></section>

</main>

<script src="js/script.js"></script>
</body>
</html>
