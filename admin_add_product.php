<?php
session_start();
require 'config/database.php'; //  database connection file

// Make sure $conn is available from database.php
// If it's not, add this code (adjust according to your actual database configuration):
if (!isset($conn) || $conn === null) {
    // Database connection
    $servername = "localhost";
    $username = "root";  //  database username
    $password = "";      //  database password
    $dbname = "plant_db"; //  database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Enable detailed error 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add debugging to see what's being submitted
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Check if keys exist before accessing them
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;
    

    error_log("Processed name: '$name'");
    error_log("Processed category: '$category'");
    error_log("Processed price: '$price'");
    
    // Check if image exists and is uploaded properly
    $imageUploaded = isset($_FILES['image']) && 
                     isset($_FILES['image']['name']) && 
                     !empty($_FILES['image']['name']) && 
                     $_FILES['image']['error'] === UPLOAD_ERR_OK;
    
    error_log("Image uploaded check: " . ($imageUploaded ? "YES" : "NO"));
    if (isset($_FILES['image'])) {
        error_log("Image error code: " . $_FILES['image']['error']);
    }
    
    // Modified validation with better error messages
    $errors = [];
    
    // validation checks
    if (empty($name)) $errors[] = "Product name is required.";
    if ($price <= 0) $errors[] = "Valid price is required.";
    if (empty($category)) $errors[] = "Category selection is required.";
    
    // More detailed image upload validation
    if (!$imageUploaded) {
        if (!isset($_FILES['image']) || !isset($_FILES['image']['name']) || empty($_FILES['image']['name'])) {
            $errors[] = "Product image is required.";
        } else if (isset($_FILES['image']['error']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed with code: " . $_FILES['image']['error'];
            
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errors[] = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "The uploaded file was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errors[] = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errors[] = "Failed to write file to disk";
                    break;
                default:
                    $errors[] = "Unknown upload error";
            }
        }
    }
    
    if (!empty($errors)) {
        $message = "<div class='error-message'><ul>";
        foreach ($errors as $error) {
            $message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $message .= "</ul></div>";
        error_log("Validation errors: " . implode(", ", $errors));
    } else {
        error_log("Validation passed, proceeding with image processing");
        
        // check if image is uploaded or not
        $image = $_FILES['image'];
        // Image Formats
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $imageExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

        if (!in_array($imageExtension, $allowedExtensions)) {
            $message = "<p class='error-message'>Invalid image format! Only JPG, PNG, GIF, WEBP, SVG allowed.</p>";
        } elseif ($image['size'] > 500000000) { // 5MB limit
            $message = "<p class='error-message'>File is too large! Max size: 5MB.</p>";
        } else {
            error_log("Image validation passed, proceeding with file storage");
            
            // Create uploads directory if it doesn't exist
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            
            $filename = uniqid() . '_' . basename($image['name']);
            $target_file = $target_dir . $filename;
            $imageType = $image['type'];
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Move uploaded file to target directory
                if (move_uploaded_file($image['tmp_name'], $target_file)) {
                    // Insert Product into Database with file path instead of blob
                    $stmt = $conn->prepare("INSERT INTO products (product_name, product_description, category, product_price, stock_quantity, product_image, image_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssdiss", $name, $description, $category, $price, $stock_quantity, $target_file, $imageType);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        $message = "<p class='success-message'>Plant added successfully!</p>";
                        error_log("Plant added successfully");
                        
                        // Reset form after successful submission
                        $name = $description = $category = '';
                        $price = $stock_quantity = 0;
                    } else {
                        throw new Exception("Error adding plant: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Failed to move uploaded file.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $message = "<p class='error-message'>" . $e->getMessage() . "</p>";
                error_log("Exception during processing: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Plant</title>
    <link rel="stylesheet" href="css/add_product.css">
</head>
<body>
<div class="container">
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
    
    <div class="content">
        <h2>Add New Plant</h2>
        <?php echo $message; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name" class="required">Plant Name</label>
                <input type="text" id="name" name="name" placeholder="Enter plant name" required 
                       value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Plant Description</label>
                <textarea id="description" name="description" placeholder="Enter plant description"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="category" class="required">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="Ornamental Plant" <?php echo (isset($category) && $category == 'Ornamental Plant') ? 'selected' : ''; ?>>Ornamental Plant</option>
                    <option value="Fruit Plant" <?php echo (isset($category) && $category == 'Fruit Plant') ? 'selected' : ''; ?>>Fruit Plant</option>
                    <option value="Vegetable Plant" <?php echo (isset($category) && $category == 'Vegetable Plant') ? 'selected' : ''; ?>>Vegetable Plant</option>
                    <option value="Medicinal Plant" <?php echo (isset($category) && $category == 'Medicinal Plant') ? 'selected' : ''; ?>>Medicinal Plant</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="price" class="required">Price</label>
                <input type="number" id="price" step="0.01" name="price" placeholder="Enter price" required min="0.01"
                       value="<?php echo isset($price) && $price > 0 ? htmlspecialchars($price) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" placeholder="Enter stock quantity" min="0"
                       value="<?php echo isset($stock_quantity) ? htmlspecialchars($stock_quantity) : '0'; ?>">
            </div>
            
            <div class="form-group">
                <label for="image" class="required">Plant Image</label>
                <input type="file" id="image" name="image" accept="image/*" required>
                <small>Allowed formats: JPG, PNG, GIF, WEBP, SVG. Max size: 5MB</small>
            </div>
            
            <button type="submit">Add Plant</button>
        </form>
    </div>
</div>
</body>
</html>