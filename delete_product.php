<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

// Check if the product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Product ID is missing!'); window.location.href='manage_products.php';</script>";
    exit();
}

$product_id = $_GET['id'];

$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete the product
$sql = "DELETE FROM products WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    echo "<script>alert('Product deleted successfully!'); window.location.href='manage_products.php';</script>";
} else {
    echo "<script>alert('Error deleting product: " . $stmt->error . "'); window.location.href='manage_products.php';</script>";
}

$stmt->close();
$conn->close();
?>