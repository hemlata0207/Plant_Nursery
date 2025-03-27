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

// Fetch all plants
$sql = "SELECT * FROM products ORDER BY created_on DESC";
$result = $conn->query($sql);

$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Plants</title>
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
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .image-cell {
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_products.php">Manage Plants</a></li>
            <li><a href="manage_orders.php">View Orders</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <h1>Manage Plants</h1>
        <a href="admin_add_product.php"><button class="add-btn">Add Plant</button></a>
        
        <form action="bulk_delete_products.php" method="POST" id="productsForm">
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
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Created On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><input type='checkbox' name='selected_products[]' value='" . $row["product_id"] . "' class='product-checkbox'></td>";
                            echo "<td>" . $row["product_id"] . "</td>";
                            echo "<td class='image-cell'>";
                            if (!empty($row["product_image"])) {
                                echo "<img src='" . $row["product_image"] . "' alt='" . $row["product_name"] . "' class='product-image'>";
                            } else {
                                echo "<span>No image</span>";
                            }
                            echo "</td>";
                            echo "<td>" . $row["product_name"] . "</td>";
                            
                            // Truncate description if it's too long
                            $description = $row["product_description"];
                            if (strlen($description) > 50) {
                                $description = substr($description, 0, 50) . "...";
                            }
                            echo "<td>" . $description . "</td>";
                            
                            echo "<td>" . $row["category"] . "</td>";
                            echo "<td>$" . number_format($row["product_price"], 2) . "</td>";
                            echo "<td>" . $row["stock_quantity"] . "</td>";
                            echo "<td>" . $row["created_on"] . "</td>";
                            echo "<td>
                                    <a href='edit_product.php?id=" . $row["product_id"] . "'><button type='button' class='action-btn'>Edit</button></a>
                                    <a href='delete_product.php?id=" . $row["product_id"] . "' onclick='return confirm(\"Are you sure you want to delete this plant?\")'><button type='button' class='action-btn delete-btn'>Delete</button></a>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10' style='text-align:center'>No plants found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<script>
    // Script for select all functionality
    document.getElementById('select-all').addEventListener('change', function() {
        var checkboxes = document.getElementsByClassName('product-checkbox');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    });
    
    // Confirm bulk delete
    function confirmBulkDelete() {
        var checkboxes = document.getElementsByClassName('product-checkbox');
        var selected = 0;
        
        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                selected++;
            }
        }
        
        if (selected === 0) {
            alert('No plants selected for deletion!');
            return false;
        }
        
        return confirm('Are you sure you want to delete ' + selected + ' selected plant(s)?');
    }
</script>

</body>
</html>
<?php $conn->close(); ?>