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

    <title>Manage Orders - Alpine Green</title>

    <!-- Add Font Awesome for icons -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/manage_order.css">

    <style>

        /* Sidebar styling from file 1 */

    

    </style>

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

                <th>Status</th>

                <th>Type</th>

                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

            <?php

            if ($result->num_rows > 0) {

                while($row = $result->fetch_assoc()) {

                    echo "<tr>";

                    echo "<td>" . $row["order_id"] . "</td>";

                    echo "<td>" . $row["full_name"] . "</td>";

                    echo "<td>$" . number_format($row["total_cost"], 2) . "</td>";

                    echo "<td>" . $row["order_created"] . "</td>";

                    echo "<td>" . $row["order_status"] . "</td>";

                    echo "<td>" . $row["order_type"] . "</td>";

                    echo "<td>

                            <a href='view_order.php?id=" . $row["order_id"] . "'><button type='button' class='action-btn'>View</button></a>

                            <a href='update_order.php?id=" . $row["order_id"] . "'><button type='button' class='action-btn'>Update</button></a>

                          </td>";

                    echo "</tr>";

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