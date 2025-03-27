<?php
// Start session for cart functionality
session_start();

// Initialize the cart array in the session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
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

// Function to calculate cart total
function calculateCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
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

// Handle cart actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : 0;
    
    // Update quantity
    if ($action === 'update' && isset($_POST['quantity'])) {
        $quantity = intval($_POST['quantity']);
        
        // Validate quantity and update cart
        if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
            $product = getProductById($conn, $product_id);
            
            if ($product) {
                // Ensure quantity doesn't exceed available stock
                $quantity = min($quantity, $product['stock_quantity']);
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                $success_message = "Cart updated successfully!";
            }
        } elseif ($quantity <= 0) {
            // Remove item if quantity is zero or negative
            unset($_SESSION['cart'][$product_id]);
            $success_message = "Item removed from cart!";
        }
    }
    
    // Remove item
    elseif ($action === 'remove') {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $success_message = "Item removed from cart!";
        }
    }
    
    // Clear entire cart
    elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        $success_message = "Your cart has been cleared!";
    }
}

// Calculate cart total
$cart_total = calculateCartTotal();

// Include header
require("pages/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - Plant Shop</title>
    <link rel="stylesheet" href="css/Product.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
        .cart-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .quantity-input {
            width: 70px;
        }
        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }
        .empty-cart i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <h1 class="mb-4"><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <!-- Empty Cart Display -->
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted">Looks like you haven't added any plants to your cart yet.</p>
                <a href="product.php" class="btn btn-primary mt-3">
                    <i class="fas fa-leaf"></i> Browse Plants
                </a>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Cart Items (<?php echo getCartItemCount(); ?>)</h5>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to empty your cart?');">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Empty Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="border-top-0">Product</th>
                                        <th class="border-top-0 text-center">Price</th>
                                        <th class="border-top-0 text-center">Quantity</th>
                                        <th class="border-top-0 text-center">Subtotal</th>
                                        <th class="border-top-0 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" class="cart-item-image mr-3" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <?php else: ?>
                                                        <div class="cart-item-image mr-3 bg-light d-flex align-items-center justify-content-center">
                                                            <span class="text-muted small">No Image</span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <a href="product.php?view=<?php echo $product_id; ?>" class="text-muted small">View product</a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">₹<?php echo number_format($item['price'], 2); ?></td>
                                            <td class="text-center">
                                                <form method="POST" action="" class="d-flex justify-content-center">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" 
                                                        class="form-control form-control-sm quantity-input" onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="text-center">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            <td class="text-center">
                                                <form method="POST" action="" onsubmit="return confirm('Remove this item from your cart?');">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="cart-summary">
                        <h5 class="mb-4">Order Summary</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items (<?php echo getCartItemCount(); ?>):</span>
                            <span>₹<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span id="shipping">Calculated at checkout</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Estimated Total:</strong>
                            <strong>₹<?php echo number_format($cart_total, 2); ?></strong>
                        </div>
                        
                        <button type="button" class="btn btn-success btn-block" onclick="location.href='Checkout.php';">
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </button>
                        
                        <button type="button" class="btn btn-outline-secondary btn-block mt-2" onclick="location.href='product.php';">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </button>
                    </div>
                    
                    <!-- Promo Code Section -->
                    <div class="cart-summary mt-3">
                        <h5 class="mb-3">Promo Code</h5>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Enter code">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- JavaScript dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>