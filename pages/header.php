<?php
// Make sure session is started in header.php too
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to get cart count (if not already defined)
if (!function_exists('getCartItemCount')) {
    function getCartItemCount() {
        $count = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }
}

// Get cart count
$cartCount = getCartItemCount();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Plant Shop</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/sign-in-up.css">
    
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <style>
      .cart-icon-container {
        position: relative;
        display: inline-block;
      }
      .cart-count {
        position: absolute;
        top: -10px;
        right: -10px;
        background-color: #e74c3c;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 12px;
        font-weight: bold;
      }
    </style>
  </head>
  <body>
  <div id="nav">
        <div class="nav-logo">
          <img src="assets/images/logo.png.png" alt="" />
        </div>
        <div class="nav-items">
          <a href="index.php">Home</a>
          <a href="product.php">Product</a>
          <a href="about.php">About Us</a>
          <a href="contact.php">Contact Us</a>
          <a href="sign-in.php">Sign In</a>
        </div>
        <div class="nav-icons">
          <a href="Cart.php" class="cart-icon-container">
            <i class="ri-shopping-cart-fill"></i>
            <?php if ($cartCount > 0): ?>
              <span class="cart-count"><?php echo $cartCount; ?></span>
            <?php endif; ?>
          </a>
          <a href="user_dashboard.php"><i class="ri-user-fill"></i></a>
        </div>
      </div>