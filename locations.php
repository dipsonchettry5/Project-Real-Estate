<?php
require "config.php";

$term = $_GET['term'] ?? '';

$stmt = $pdo->prepare(
    "SELECT DISTINCT location
     FROM properties
     WHERE location LIKE ?
     LIMIT 10"
);

$stmt->execute(["%$term%"]);

foreach ($stmt as $row) {
    echo "<div class='autocomplete-item'>" .
         htmlspecialchars($row['location']) .
         "</div>";
}
