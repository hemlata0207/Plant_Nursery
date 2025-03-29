<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

if (!isset($_POST['selected_suppliers']) || empty($_POST['selected_suppliers'])) {
    echo "<script>alert('No suppliers selected!'); window.location.href='manage_supplier.php';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$selected_suppliers = $_POST['selected_suppliers'];
$count = 0;

foreach ($selected_suppliers as $supplier_id) {
    $sql = "DELETE FROM suppliers WHERE supplier_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $supplier_id);
    
    if ($stmt->execute()) {
        $count++;
    }
    
    $stmt->close();
}

echo "<script>alert('" . $count . " supplier(s) deleted successfully!'); window.location.href='manage_supplier.php';</script>";

$conn->close();
?>