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
    <style>
        /* Sidebar styling from file 1 */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #fff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid #3c546c;
        }

        .brand {
            display: flex;
            align-items: center;
            font-size: 20px;
            font-weight: bold;
        }

        .brand i {
            margin-right: 10px;
            color: #4CAF50;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin: 5px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: #34495e;
            border-left: 4px solid #4CAF50;
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .logout-btn {
            padding: 15px;
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 1px solid #3c546c;
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            color: #ecf0f1;
            text-decoration: none;
        }

        .logout-btn a i {
            margin-right: 10px;
        }

        /* Adjust main content to accommodate sidebar */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            padding: 15px;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <i class="fas fa-leaf"></i>
                <span>Alpine Green Plant Nursery</span>
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
                    <i class="fas fa-seedling"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_supplier.php" class="nav-link">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
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
              
                <div class="card-footer">
                <i class="fa-brands fa-envira"></i>&nbsp; Available products
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