<?php
$host = "localhost";
$db   = "realestate";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Ensure favorites table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        property_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_property_unique (user_id, property_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure inquiries table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        property_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add user_id column to inquiries if table already existed without it
    try {
        $pdo->exec("ALTER TABLE inquiries ADD COLUMN user_id INT DEFAULT NULL AFTER id");
    } catch (Exception $ex) {}

    // Ensure properties.land_area is VARCHAR to support Nepali units (Aana, Ropani, Bigha, etc.)
    try {
        $pdo->exec("ALTER TABLE properties MODIFY land_area VARCHAR(100) DEFAULT NULL");
    } catch (Exception $ex) {}

} catch (PDOException $e) {
    die("Database connection failed");
}
