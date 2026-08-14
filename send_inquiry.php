<?php
session_start();
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate
$errors = [];
if ($propertyId <= 0) $errors[] = "Invalid property.";
if ($name === '') $errors[] = "Name is required.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
if ($message === '') $errors[] = "Message is required.";

if (!empty($errors)) {
    $_SESSION['inquiry_errors'] = $errors;
    $_SESSION['inquiry_old'] = [
        'name' => $name, 'email' => $email, 'phone' => $phone, 'message' => $message
    ];
    header("Location: details.php?id=" . $propertyId);
    exit;
}

// Confirm the property actually exists before inserting
$stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ?");
$stmt->execute([$propertyId]);
if (!$stmt->fetch()) {
    header("Location: index.php");
    exit;
}

// Store the inquiry
$userId = $_SESSION['user_id'] ?? null;
$stmt = $pdo->prepare(
    "INSERT INTO inquiries (user_id, property_id, name, email, phone, message) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$userId, $propertyId, $name, $email, $phone, $message]);

$_SESSION['inquiry_success'] = "Your inquiry has been sent. The agent will contact you soon.";
header("Location: details.php?id=" . $propertyId);
exit;
