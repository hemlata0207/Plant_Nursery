<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid supplier ID!'); window.location.href='manage_supplier.php';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$supplier_id = $_GET['id'];

// Delete the supplier
$sql = "DELETE FROM suppliers WHERE supplier_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $supplier_id);

if ($stmt->execute()) {
    echo "<script>alert('Supplier deleted successfully!'); window.location.href='manage_supplier.php';</script>";
} else {
    echo "<script>alert('Error deleting supplier: " . $stmt->error . "'); window.location.href='manage_supplier.php';</script>";
}

$stmt->close();
$conn->close();
?>