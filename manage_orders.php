<?php

session_start(); // Added session start



// Check if the user is logged in and is an admin (Added admin check)

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {

    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";

    exit();

}



// Database connection

$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {

    die("Connection failed: " . $conn->connect_error);

}

// Fetch all orders with user details

$sql = "SELECT orders.order_id, orders.total_cost, orders.order_created, orders.order_status, orders.order_type, users.full_name 

        FROM orders 

        LEFT JOIN users ON orders.user_ref = users.user_id 

        ORDER BY orders.order_created DESC";

$result = $conn->query($sql);


$full_name = $_SESSION['full_name']; // Added session variable

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - Alpine Green Plant Nursery</title>

    <!-- Add Font Awesome for icons -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/manage_order.css">

    <style>

        .sidebar {

            width: 250px;

            background-color: #2C3E50;

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

        

        .nav-link:hover, .nav-link.active {

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

        

        header {

            margin-left: 250px;

            padding: 15px;

            background-color: #f5f5f5;

            border-bottom: 1px solid #ddd;

        }

    </style>

</head>

<body>



<div class="sidebar">

    <div class="sidebar-header">

        <div class="brand">

            <i class="fas fa-leaf"></i>

            <span>Alpine Green Plant Nursery</span>

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

            <a href="manage_orders.php" class="nav-link active">

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

    <h1>Manage Orders - Alpine Green Plant Nursery</h1>

</header>



<div class="main-content">

    <table>

        <thead>

            <tr>

                <th>Order ID</th>

                <th>User</th>

                <th>Total Cost</th>

                <th>Order Created</th>
                <th>Type</th>

            </tr>

        </thead>

        <tbody>

            <?php

            if ($result->num_rows > 0) {

                while($row = $result->fetch_assoc()) {

                    echo "<tr>";

                    echo "<td>" . $row["order_id"] . "</td>";

                    echo "<td>" . $row["full_name"] . "</td>";

                    echo "<td>₹" . number_format($row["total_cost"], 2) . "</td>";

                    echo "<td>" . $row["order_created"] . "</td>";

                    echo "<td>" . $row["order_type"] . "</td>";

                     "</tr>";

                }

            } else {

                echo "<tr><td colspan='7' style='text-align:center'>No orders found</td></tr>";

            }

            ?>

        </tbody>

    </table>

</div>



</body>

</html>

<?php $conn->close(); ?>