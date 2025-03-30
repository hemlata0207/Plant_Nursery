<?php
// Start session for cart functionality
session_start();

// Initialize the cart array in the session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to get cart count
function getCartItemCount() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}

// Database connection parameters
$servername = "127.0.0.1";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password (empty)
$dbname = "plant_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get all products
function getAllProducts($conn) {
    $sql = "SELECT * FROM products ORDER BY product_id DESC";
    $result = $conn->query($sql);
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get a single product by ID
function getProductById($conn, $id) {
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to get products by category
function getProductsByCategory($conn, $category) {
    $sql = "SELECT * FROM products WHERE category = ? ORDER BY product_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get products by price range
function getProductsByPriceRange($conn, $min_price, $max_price) {
    $sql = "SELECT * FROM products WHERE product_price BETWEEN ? AND ? ORDER BY product_price ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dd", $min_price, $max_price);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get products by stock status
function getProductsByStockStatus($conn, $status) {
    if ($status === 'in_stock') {
        $sql = "SELECT * FROM products WHERE stock_quantity > 0 ORDER BY product_id DESC";
    } else if ($status === 'out_of_stock') {
        $sql = "SELECT * FROM products WHERE stock_quantity = 0 ORDER BY product_id DESC";
    } else if ($status === 'low_stock') {
        $sql = "SELECT * FROM products WHERE stock_quantity > 0 AND stock_quantity <= 5 ORDER BY product_id DESC";
    } else {
        return getAllProducts($conn);
    }
    
    $result = $conn->query($sql);
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get all product categories
function getAllCategories($conn) {
    $sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    }
    
    return $categories;
}

// Function to get minimum and maximum product prices
function getPriceRange($conn) {
    $sql = "SELECT MIN(product_price) as min_price, MAX(product_price) as max_price FROM products";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return ['min_price' => 0, 'max_price' => 100];
}

// Function to search for products
function searchProducts($conn, $searchTerm) {
    $searchTerm = "%" . $searchTerm . "%";
    $sql = "SELECT * FROM products WHERE 
            product_name LIKE ? OR 
            product_description LIKE ? OR 
            category LIKE ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Enhanced Add to cart functionality
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Get product details
    $added_product = getProductById($conn, $product_id);
    
    // Check if product exists and is in stock
    if ($added_product && $added_product['stock_quantity'] > 0) {
        // Check if product is already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            // Make sure we don't exceed available stock
            $new_quantity = min(
                $_SESSION['cart'][$product_id]['quantity'] + $quantity,
                $added_product['stock_quantity']
            );
            $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
        } else {
            // Add new product to cart
            $_SESSION['cart'][$product_id] = [
                'id' => $product_id,
                'name' => $added_product['product_name'],
                'price' => $added_product['product_price'],
                'image' => $added_product['product_image'],
                'quantity' => min($quantity, $added_product['stock_quantity'])
            ];
        }
        
        $success_message = "Added " . $added_product['product_name'] . " to your cart!";
    } else {
        $error_message = "Sorry, this product is no longer available.";
    }
}

// Get price range for filter
$priceRange = getPriceRange($conn);
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : $priceRange['min_price'];
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : $priceRange['max_price'];

// Get products for display
$products = [];

// Check if there's a search request
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $products = searchProducts($conn, $_GET['search']);
} 
// Check if there's a category filter
else if (isset($_GET['category']) && !empty($_GET['category'])) {
    $products = getProductsByCategory($conn, $_GET['category']);
} 
// Check if there's a price range filter
else if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
    $products = getProductsByPriceRange($conn, $min_price, $max_price);
}
// Check if there's a stock status filter
else if (isset($_GET['stock_status']) && !empty($_GET['stock_status'])) {
    $products = getProductsByStockStatus($conn, $_GET['stock_status']);
}
// Default: get all products
else {
    $products = getAllProducts($conn);
}

// Get all categories for the filter dropdown
$categories = getAllCategories($conn);

// Get a specific product if view details mode is active
$viewProduct = null;
if (isset($_GET['view']) && !empty($_GET['view'])) {
    $viewProduct = getProductById($conn, $_GET['view']);
}
require("pages/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Shop</title>
    <link rel="stylesheet" href="css/product.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

</head>
<body>
    <div class="container-fluid mt-3">
        
        <div class="row">
            <!-- Left Sidebar - Filters -->
            <div class="col-md-3">
                <div class="filter-section">
                    <h3 class="filter-title">Search Products</h3>
                    <form method="GET" action="">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Search products..." name="search" 
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <h3 class="filter-title">Filter Options</h3>
                    
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <h5>By Category</h5>
                        <form method="GET" action="">
                            <select name="category" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" 
                                        <?php echo (isset($_GET['category']) && $_GET['category'] == $category) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    
                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <h5>By Price</h5>
                        <form method="GET" action="">
                            <div class="form-row">
                                <div class="col">
                                    <input type="number" step="0.01" min="<?php echo $priceRange['min_price']; ?>" 
                                        class="form-control form-control-sm" placeholder="Min" name="min_price"
                                        value="<?php echo $min_price; ?>">
                                </div>
                                <div class="col">
                                    <input type="number" step="0.01" max="<?php echo $priceRange['max_price']; ?>" 
                                        class="form-control form-control-sm" placeholder="Max" name="max_price"
                                        value="<?php echo $max_price; ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-secondary mt-2 w-100">Apply</button>
                        </form>
                    </div>
                    
                    <!-- Stock Status Filter -->
                    <div class="filter-group">
                        <h5>By Stock Status</h5>
                        <form method="GET" action="">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="stock_status" id="inStock" 
                                    value="in_stock" <?php echo (isset($_GET['stock_status']) && $_GET['stock_status'] == 'in_stock') ? 'checked' : ''; ?> 
                                    onchange="this.form.submit()">
                                <label class="form-check-label" for="inStock">
                                    In Stock
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="stock_status" id="lowStock" 
                                    value="low_stock" <?php echo (isset($_GET['stock_status']) && $_GET['stock_status'] == 'low_stock') ? 'checked' : ''; ?> 
                                    onchange="this.form.submit()">
                                <label class="form-check-label" for="lowStock">
                                    Low Stock (≤ 5)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="stock_status" id="outOfStock" 
                                    value="out_of_stock" <?php echo (isset($_GET['stock_status']) && $_GET['stock_status'] == 'out_of_stock') ? 'checked' : ''; ?> 
                                    onchange="this.form.submit()">
                                <label class="form-check-label" for="outOfStock">
                                    Out of Stock
                                </label>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (isset($_GET['search']) || isset($_GET['category']) || 
                             isset($_GET['min_price']) || isset($_GET['stock_status'])): ?>
                        <a href="product.php" class="btn btn-danger btn-sm w-100">
                            <i class="fas fa-times"></i> Clear All Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Products -->
            <div class="col-md-9">
                <?php if ($viewProduct): ?>
                    <!-- Product Detail View -->
                    <div class="card mb-3">
                        <div class="row no-gutters">
                            <div class="col-md-4">
                                <?php if ($viewProduct['product_image']): ?>
                                    <img src="<?php echo htmlspecialchars($viewProduct['product_image']); ?>" 
                                        class="card-img" alt="<?php echo htmlspecialchars($viewProduct['product_name']); ?>">
                                <?php else: ?>
                                    <div class="card-img bg-light d-flex align-items-center justify-content-center" style="height: 300px;">
                                        <span class="text-muted">No Image Available</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo htmlspecialchars($viewProduct['product_name']); ?></h3>
                                    
                                    <?php if ($viewProduct['category']): ?>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($viewProduct['category']); ?></span>
                                    <?php endif; ?>
                                    
                                    <p class="card-text mt-3"><?php echo nl2br(htmlspecialchars($viewProduct['product_description'])); ?></p>
                                    
                                    <h4 class="product-price mt-3">₹<?php echo number_format($viewProduct['product_price'], 2); ?></h4>
                                    
                                    <p class="card-text mt-3 <?php echo $viewProduct['stock_quantity'] == 0 ? 'text-danger' : ($viewProduct['stock_quantity'] < 5 ? 'text-warning' : 'text-success'); ?>">
                                        <?php if ($viewProduct['stock_quantity'] == 0): ?>
                                            <i class="fas fa-times-circle"></i> Out of Stock
                                        <?php elseif ($viewProduct['stock_quantity'] < 5): ?>
                                            <i class="fas fa-exclamation-circle"></i> Low Stock: Only <?php echo $viewProduct['stock_quantity']; ?> remaining
                                        <?php else: ?>
                                            <i class="fas fa-check-circle"></i> In Stock: <?php echo $viewProduct['stock_quantity']; ?> available
                                        <?php endif; ?>
                                    </p>
                                    
                                    <div class="mt-4">
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?php echo $viewProduct['product_id']; ?>">
                                            <div class="form-row align-items-center mb-3">
                                                <div class="col-auto">
                                                    <label for="quantity" class="sr-only">Quantity</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Qty</span>
                                                        </div>
                                                        <input type="number" class="form-control" id="quantity" name="quantity" 
                                                            value="1" min="1" max="<?php echo $viewProduct['stock_quantity']; ?>" 
                                                            style="width: 70px;" <?php echo $viewProduct['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <button type="submit" name="add_to_cart" class="btn btn-success <?php echo $viewProduct['stock_quantity'] == 0 ? 'disabled' : ''; ?>">
                                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        <a href="Product.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Products
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Products Grid View -->
                    <div class="row">
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <div class="col-md-4 col-sm-6 mb-4">
                                    <div class="card product-card h-100">
                                        <?php if ($product['product_image']): ?>
                                            <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                                class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                        <?php else: ?>
                                            <div class="card-img-top product-image bg-light d-flex align-items-center justify-content-center">
                                                <span class="text-muted small">No Image</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                            <p class="card-text text-truncate"><?php echo htmlspecialchars($product['product_description']); ?></p>
                                            <p class="product-price mb-1">₹<?php echo number_format($product['product_price'], 2); ?></p>
                                            <p class="stock-info mb-2 <?php echo $product['stock_quantity'] == 0 ? 'text-danger' : ($product['stock_quantity'] < 5 ? 'text-warning' : ''); ?>">
                                                <?php if ($product['stock_quantity'] == 0): ?>
                                                    <i class="fas fa-times-circle"></i> Out of Stock
                                                <?php elseif ($product['stock_quantity'] < 5): ?>
                                                    <i class="fas fa-exclamation-circle"></i> Only <?php echo $product['stock_quantity']; ?> left
                                                <?php else: ?>
                                                    <i class="fas fa-check-circle"></i> In Stock
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($product['category']): ?>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($product['category']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-white border-top-0">
                                            <div class="btn-group w-100">
                                                <form method="POST" action="" class="w-50">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" name="add_to_cart" class="btn btn-success btn-sm w-100 <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>">
                                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                                    </button>
                                                </form>
                                                <a href="?view=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary btn-sm w-50">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    No products found. 
                                    <?php if (isset($_GET['search']) || isset($_GET['category']) || 
                                            isset($_GET['min_price']) || isset($_GET['stock_status'])): ?>
                                        Please try different filter options.
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>