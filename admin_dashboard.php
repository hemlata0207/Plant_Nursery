<?php 
session_start(); 
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit(); 
}

$conn = new mysqli("localhost", "root", "", "plant_db", 3306); 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); 
}

$full_name = htmlspecialchars($_SESSION['full_name']); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <i class="fas fa-music"></i>
                <span>Music Store</span>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="manage_products.php" class="nav-link">
                    <i class="fas fa-guitar"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
        
        <div class="logout-btn">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div class="menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <h1 class="page-title">Admin Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo substr($full_name, 0, 1); ?>
                </div>
                <div class="user-name"><?php echo $full_name; ?></div>
            </div>
        </div>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="card-title">Total Users</div>
                <div class="card-value">
                    <?php
                    $userCount = 0;
                    $userQuery = "SELECT COUNT(*) as count FROM users";
                    $userResult = $conn->query($userQuery);
                    if ($userResult && $userResult->num_rows > 0) {
                        $userCount = $userResult->fetch_assoc()['count'];
                    }
                    echo $userCount;
                    ?>
                </div>
                <i class="fas fa-users card-icon"></i>
                <div class="card-footer">
                    <i class="fas fa-user"></i>&nbsp; Registered users
                </div>
            </div>
            
            <div class="stat-card">
                <div class="card-title">Total Products</div>
                <div class="card-value">
                    <?php
                    $prodCount = 0;
                    $prodQuery = "SELECT COUNT(*) as count FROM products";
                    $prodResult = $conn->query($prodQuery);
                    if ($prodResult && $prodResult->num_rows > 0) {
                        $prodCount = $prodResult->fetch_assoc()['count'];
                    }
                    echo $prodCount;
                    ?>
                </div>
                <i class="fas fa-guitar card-icon"></i>
                <div class="card-footer">
                    <i class="fas fa-guitar"></i>&nbsp; Available products
                </div>
            </div>
            
            <div class="stat-card">
                <div class="card-title">Recent Orders</div>
                <div class="card-value">
                    <?php
                    $orderCount = 0;
                    
                    // Check if orders table exists
                    $tableCheckQuery = "SHOW TABLES LIKE 'orders'";
                    $tableExists = $conn->query($tableCheckQuery);
                    
                    if ($tableExists && $tableExists->num_rows > 0) {
                        // Just count all orders if we can't filter by date
                        $orderQuery = "SELECT COUNT(*) as count FROM orders";
                        $orderResult = $conn->query($orderQuery);
                        if ($orderResult && $orderResult->num_rows > 0) {
                            $orderCount = $orderResult->fetch_assoc()['count'];
                        }
                    }
                    
                    echo $orderCount;
                    ?>
                </div>
                <i class="fas fa-shopping-cart card-icon"></i>
                <div class="card-footer">
                    <i class="fas fa-clock"></i>&nbsp; Total orders
                </div>
            </div>
            
           
        </div>
        
        <div class="quick-actions">
            <h2 class="section-title">Quick Actions</h2>
            <div class="action-buttons">
                <a href="admin_add_product.php" class="action-btn">
                    <i class="fas fa-plus"></i> Add Product
                </a>
                <a href="admin_add_categories.php" class="action-btn">
                    <i class="fas fa-folder-plus"></i> Add Category
                </a>
                
            </div>
        </div>
        
        <div class="recent-activity">
            <!-- You can add recent activities or orders here -->
        </div>
    </div>
</div>

<script>
    // Toggle sidebar on mobile
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>

</body>
</html>