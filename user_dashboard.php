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

// Get recent orders
$stmt = $conn->prepare("SELECT 
    order_id, 
    order_created, 
    total_cost, 
    order_status 
    FROM orders 
    WHERE user_ref = ? 
    ORDER BY order_created DESC 
    LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result();

// Get recommended products
$stmt = $conn->prepare("SELECT 
    product_id, 
    product_name, 
    product_price, 
    product_image 
    FROM products 
    ORDER BY RAND() 
    LIMIT 4");
$stmt->execute();
$recommended_products = $stmt->get_result();

$conn->close();

// Utility functions
function formatDate($date) {
    return date("M d, Y", strtotime($date));
}

function getStatusBadge($status) {
    $badges = [
        'completed' => 'badge-success',
        'processing' => 'badge-warning',
        'shipped' => 'badge-info',
        'cancelled' => 'badge-danger'
    ];
    return $badges[strtolower($status)] ?? 'badge-secondary';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard |Alpine Green Plant Nursery</title>
    <link rel="stylesheet" href="css/user_dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
</head>
<body>
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
                <div class="col-md-4">
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

                <div class="col-md-8">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Recent Orders</h4>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>

                        <?php if ($recent_orders->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo formatDate($order['order_created']); ?></td>
                                                <td>₹<?php echo number_format($order['total_cost'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadge($order['order_status']); ?>">
                                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No orders yet</p>
                        <?php endif; ?>
                    </div>

                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Recommended Products</h4>
                            <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>

                        <div class="row g-3">
                            <?php while ($product = $recommended_products->fetch_assoc()): ?>
                                <div class="col-6 col-md-3 text-center">
                                    <div class="bg-light p-3 rounded">
                                        <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                             class="img-fluid rounded mb-2" 
                                             style="max-height: 150px; object-fit: cover;">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                        <p class="text-muted">₹<?php echo number_format($product['product_price'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Optional: Add mobile sidebar toggle functionality
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.createElement('button');
            sidebarToggle.innerHTML = '<i class="bi bi-list"></i>';
            sidebarToggle.classList.add('btn', 'btn-outline-primary', 'position-fixed', 'top-0', 'start-0', 'm-3', 'd-md-none');
            sidebarToggle.style.zIndex = '1050';
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' 
                    ? 'translateX(-100%)' 
                    : 'translateX(0px)';
            });

            document.body.prepend(sidebarToggle);
        });
    </script>
</body>
</html>