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
    <link rel="stylesheet" href="css/manage_product.css">
    <style>
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
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_products.php">Manage Plants</a></li>
            <li><a href="manage_supplier.php">Manage Suppliers</a></li>
            <li><a href="manage_orders.php">View Orders</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <h1>Edit Supplier</h1>
        
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

</body>
</html>
<?php $conn->close(); ?>