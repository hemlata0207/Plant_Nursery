<?php 
session_start(); 

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='sign-in.php';</script>";
    exit();
}

$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all suppliers
$sql = "SELECT * FROM suppliers ORDER BY created_on DESC";
$result = $conn->query($sql);

$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers - Alpine Green</title>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/manage_product.css">
    <style>
        .bulk-actions {
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        
        .bulk-delete-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .bulk-delete-btn:hover {
            background-color: #ff3333;
        }
        
        .select-all-container {
            margin-right: 15px;
        }
        
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
            <a href="manage_supplier.php" class="nav-link active">
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
    <h1>Manage Suppliers - Alpine Green Plant Nursery</h1>
</header>
    
<div class="main-content">
    <a href="admin_add_supplier.php"><button class="add-btn">Add Supplier</button></a>
    
    <form action="bulk_delete_suppliers.php" method="POST" id="suppliersForm">
        <div class="bulk-actions">
            <div class="select-all-container">
                <input type="checkbox" id="select-all">
                <label for="select-all">Select All</label>
            </div>
            <button type="submit" class="bulk-delete-btn" onclick="return confirmBulkDelete()">Delete Selected</button>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Select</th>
                    <th>ID</th>
                    <th>Company Name</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Created On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><input type='checkbox' name='selected_suppliers[]' value='" . $row["supplier_id"] . "' class='supplier-checkbox'></td>";
                        echo "<td>" . $row["supplier_id"] . "</td>";
                        echo "<td>" . $row["company_name"] . "</td>";
                        echo "<td>" . $row["contact"] . "</td>";
                        
                        // Truncate address if it's too long
                        $address = $row["address"];
                        if (strlen($address) > 50) {
                            $address = substr($address, 0, 50) . "...";
                        }
                        echo "<td>" . $address . "</td>";
                        
                        echo "<td>" . $row["product"] . "</td>";
                        echo "<td>$" . number_format($row["amount"], 2) . "</td>";
                        echo "<td>" . $row["created_on"] . "</td>";
                        echo "<td>
                                <a href='edit_supplier.php?id=" . $row["supplier_id"] . "'><button type='button' class='action-btn'>Edit</button></a>
                                <a href='delete_supplier.php?id=" . $row["supplier_id"] . "' onclick='return confirm(\"Are you sure you want to delete this supplier?\")'><button type='button' class='action-btn delete-btn'>Delete</button></a>
                            </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' style='text-align:center'>No suppliers found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </form>
</div>

<script>
    // Script for select all functionality
    document.getElementById('select-all').addEventListener('change', function() {
        var checkboxes = document.getElementsByClassName('supplier-checkbox');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    });
    
    // Confirm bulk delete
    function confirmBulkDelete() {
        var checkboxes = document.getElementsByClassName('supplier-checkbox');
        var selected = 0;
        
        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                selected++;
            }
        }
        
        if (selected === 0) {
            alert('No suppliers selected for deletion!');
            return false;
        }
        
        return confirm('Are you sure you want to delete ' + selected + ' selected supplier(s)?');
    }
</script>

</body>
</html>
<?php $conn->close(); ?>