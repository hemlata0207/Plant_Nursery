<?php
session_start();

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "plant_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if product IDs are selected
if (isset($_POST['selected_products']) && is_array($_POST['selected_products'])) {
    $product_ids = implode(',', array_map('intval', $_POST['selected_products'])); // Securely format IDs

    // Delete products from database
    $sql = "DELETE FROM products WHERE product_id IN ($product_ids)";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Selected products deleted successfully!'); window.location.href='manage_products.php';</script>";
    } else {
        echo "<script>alert('Error deleting products: " . $conn->error . "'); window.location.href='manage_products.php';</script>";
    }
} else {
    echo "<script>alert('No products selected for deletion!'); window.location.href='manage_products.php';</script>";
}

$conn->close();
?>