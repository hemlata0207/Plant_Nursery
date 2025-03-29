<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

// Replace with your actual MySQL credentials
$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users
$sql = "SELECT * FROM users ORDER BY created_on DESC";
$result = $conn->query($sql);

// Handle user type filtering if set
$user_type = isset($_GET['user_type']) ? $_GET['user_type'] : '';
$filter_condition = "";

if (!empty($user_type)) {
    $filter_condition = " WHERE user_role = '" . $conn->real_escape_string($user_type) . "'";
    $sql = "SELECT * FROM users" . $filter_condition . " ORDER BY created_on DESC";
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alpine Green Plant Nursery - Manage Users</title>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/reports.css">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <i class="fas fa-leaf"></i>
                <span>Alpine Green</span>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_users.php" class="nav-link active">
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
    <header>
        <h1>Alpine Green Plant Nursery User Management</h1>
    </header>
    <div class="container">
        <div class="report-container">
            <div class="report-header">
                <h2>User Management</h2>
            </div>

            <?php
            $filter_text = !empty($user_type) ? ucfirst($user_type) . " Users" : "All Users";
            ?>
            <h3><?php echo $filter_text; ?></h3>

            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Image</th>
                        <th>Created On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['user_id'] . "</td>";
                            echo "<td>" . $row['full_name'] . "</td>";
                            echo "<td>" . $row['email_address'] . "</td>";
                            echo "<td>" . ($row['phone_number'] ?? 'N/A') . "</td>";
                            echo "<td>" . $row['user_role'] . "</td>";
                            echo "<td>";
                            if (!empty($row['user_image'])) {
                                echo "<img src='uploads/" . $row['user_image'] . "' class='user-img' alt='User Image'>";
                            } else {
                                echo "No image";
                            }
                            echo "</td>";
                            echo "<td>" . $row['created_on'] . "</td>";
                            echo "<td class='action-buttons'>";
                            echo "<a href='edit_user.php?id=" . $row['user_id'] . "' class='edit-btn'><i class='fas fa-edit'></i></a>";
                            echo "<a href='delete_user.php?id=" . $row['user_id'] . "' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this user?\")'><i class='fas fa-trash'></i></a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>No users found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <div style="margin-top: 20px;" id="usersSummary">
                <h3>Summary</h3>
                <?php
                // Get user role counts
                $admin_count_query = "SELECT COUNT(*) as count FROM users WHERE user_role = 'admin'";
                $customer_count_query = "SELECT COUNT(*) as count FROM users WHERE user_role = 'customer'";
                $employee_count_query = "SELECT COUNT(*) as count FROM users WHERE user_role = 'employee'";
                
                $admin_count_result = $conn->query($admin_count_query);
                $customer_count_result = $conn->query($customer_count_query);
                $employee_count_result = $conn->query($employee_count_query);
                
                $admin_count = $admin_count_result->fetch_assoc()['count'];
                $customer_count = $customer_count_result->fetch_assoc()['count'];
                $employee_count = $employee_count_result->fetch_assoc()['count'];
                $total_users = $admin_count + $customer_count + $employee_count;
                ?>
                <p>Total Users: <?php echo $total_users; ?></p>
                <p>Admin Users: <?php echo $admin_count; ?></p>
                <p>Customer Users: <?php echo $customer_count; ?></p>
                <p>Employee Users: <?php echo $employee_count; ?></p>
            </div>
        </div>
    </div>

    <style>
        /* Additional styles for the user management page */
        .user-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .edit-btn, .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 4px;
        }
        
        .edit-btn {
            background-color: #4a90e2;
            color: white;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</body>

</html>

<?php $conn->close(); ?>