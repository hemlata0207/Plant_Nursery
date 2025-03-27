<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email_address, created_on FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$full_name = $user['full_name'];
$email = $user['email_address'];
$join_date = $user['created_on'];

// Get order count
$stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_ref = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order_data = $result->fetch_assoc();
$order_count = $order_data['order_count'];

$conn->close();

// Utility functions
function formatDate($date) {
    return date("M d, Y", strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Alpine Green Plant Nursery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/user_dashboard.css">
</head>
<body>
    <!-- Sidebar Toggle for Mobile -->
    <button class="sidebar-toggle d-md-none">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-content">
            <div class="sidebar-logo">
                <h3 class="text-center">Alpine Green Plant Nursery</h3>
            </div>
            <nav class="sidebar-menu">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="user_dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Product.php">
                            <i class="bi bi-grid-3x3-gap"></i> Browse Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart4"></i> My Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="order_Tracking.php">
                            <i class="bi bi-clipboard-check"></i> Order Tracking
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person-circle"></i> My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="support.php">
                            <i class="bi bi-life-preserver"></i> Help & Support
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer p-3 text-center border-top">
                <a href="logout.php" class="btn btn-outline-danger w-100">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="dashboard-card text-center">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($full_name); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($email); ?></p>
                        <p>Joined: <?php echo formatDate($join_date); ?></p>
                    </div>

                    <div class="dashboard-card">
                        <h5 class="mb-3">Quick Stats</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total Orders</span>
                            <strong class="text-primary"><?php echo $order_count; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');

            sidebarToggle.addEventListener('click', function() {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' 
                    ? 'translateX(-100%)' 
                    : 'translateX(0px)';
            });
        });
    </script>
</body>
</html>