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

$supplier_id = $_GET['id'];
$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$full_name = htmlspecialchars($_SESSION['full_name']); 

// Process form submission for update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = $_POST['company_name'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $product = $_POST['product'];
    $amount = $_POST['amount'];
    
    // Basic validation
    if (empty($company_name) || empty($contact) || empty($address) || empty($product) || empty($amount)) {
        $message = "All fields are required!";
    } else {
        // Update supplier in database
        $sql = "UPDATE suppliers 
                SET company_name = ?, contact = ?, address = ?, product = ?, amount = ? 
                WHERE supplier_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdi", $company_name, $contact, $address, $product, $amount, $supplier_id);
        
        if ($stmt->execute()) {
            $message = "Supplier updated successfully!";
            // Redirect after 2 seconds
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'manage_supplier.php';
                    }, 2000);
                  </script>";
        } else {
            $message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Fetch the supplier data
$sql = "SELECT * FROM suppliers WHERE supplier_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Supplier not found!'); window.location.href='manage_supplier.php';</script>";
    exit();
}

$supplier = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/manage_product.css">
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: #4CAF50;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }

        /* Form styling */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .submit-btn:hover {
            background-color: #45a049;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #f8d7da;
            color: #721c24;
            text-align: center;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
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
    
    <div class="main-content">
        <div class="header">
            <div class="menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <h1 class="page-title">Edit Supplier</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo substr($full_name, 0, 1); ?>
                </div>
                <div class="user-name"><?php echo $full_name; ?></div>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo (strpos($message, "successfully") !== false) ? "success" : ""; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $supplier_id); ?>" method="POST">
                <div class="form-group">
                    <label for="company_name">Company Name:</label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($supplier['company_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="contact">Contact:</label>
                    <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($supplier['contact']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" required><?php echo htmlspecialchars($supplier['address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="product">Product:</label>
                    <input type="text" id="product" name="product" value="<?php echo htmlspecialchars($supplier['product']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount ($):</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" value="<?php echo htmlspecialchars($supplier['amount']); ?>" required>
                </div>
                
                <button type="submit" class="submit-btn">Update Supplier</button>
            </form>
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
<?php $conn->close(); ?>