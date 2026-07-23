<?php
require "config.php";

$term = $_GET['term'] ?? '';

$stmt = $pdo->prepare(
    "SELECT DISTINCT location FROM properties WHERE location LIKE ? LIMIT 10"
);
$stmt->execute(["%$term%"]);

while ($row = $stmt->fetch()) {
    echo "<div class='autocomplete-item'>".
         htmlspecialchars($row['location']).
         "</div>";
}
