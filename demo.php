<?php

// Start session for cart functionality
session_start();

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header("Location: sign-in.php?redirect=checkout.php");
    exit();
}

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

// Function to get user details
function getUserDetails($conn, $user_id)
{
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

// Function to get saved shipping addresses
function getSavedAddresses($conn, $user_id)
{
    $sql = "SELECT * FROM order_shipping WHERE user_ref = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }

    return $addresses;
}

// Function to calculate cart total
function calculateCartTotal()
{
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}

// Function to get a single product by ID
function getProductById($conn, $id)
{
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

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Get user details
$user = getUserDetails($conn, $_SESSION['user_id']);
$saved_addresses = getSavedAddresses($conn, $_SESSION['user_id']);
$cart_total = calculateCartTotal();
$total_with_tax = $cart_total * 1.05; // Adding 5% tax

// Initialize error and success messages
$error_message = "";
$success_message = "";

// Process order submission
if (isset($_POST['place_order'])) {
    // Validate shipping information
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postal_code = trim($_POST['postal_code']);
    $phone = trim($_POST['phone']);
    $payment_method = trim($_POST['payment_method']);

    // Check if save address is requested
    $save_address = isset($_POST['save_address']) ? 1 : 0;

    // Simple validation
    if (empty($full_name) || empty($email) || empty($address) || empty($city) || empty($state) || empty($postal_code) || empty($phone)) {
        $error_message = "Please fill in all required fields";
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Generate invoice number (Simple format: Current date + random number)
            $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

            // Create order record
            $order_sql = "INSERT INTO orders (user_ref, total_cost, order_status, order_type, invoice_number) 
                          VALUES (?, ?, 'pending', 'buy', ?)";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("ids", $_SESSION['user_id'], $total_with_tax, $invoice_number);
            $order_stmt->execute();

            // Get the new order ID
            $order_id = $conn->insert_id;

            // Insert shipping information
            $shipping_sql = "INSERT INTO order_shipping (order_ref, user_ref, shipping_address, shipping_city, shipping_state, shipping_pincode) 
                             VALUES (?, ?, ?, ?, ?, ?)";
            $shipping_stmt = $conn->prepare($shipping_sql);
            $shipping_stmt->bind_param("iissss", $order_id, $_SESSION['user_id'], $address, $city, $state, $postal_code);
            $shipping_stmt->execute();

            // Save address to user's addresses if requested
            if ($save_address) {
                // Check if this exact address already exists
                $check_address_sql = "SELECT COUNT(*) as count FROM order_shipping 
                                      WHERE user_ref = ? AND shipping_address = ? 
                                      AND shipping_city = ? AND shipping_state = ? 
                                      AND shipping_pincode = ?";
                $check_address_stmt = $conn->prepare($check_address_sql);
                $check_address_stmt->bind_param("issss", $_SESSION['user_id'], $address, $city, $state, $postal_code);
                $check_address_stmt->execute();
                $check_result = $check_address_stmt->get_result()->fetch_assoc();

                // If address doesn't exist, insert it
                if ($check_result['count'] == 0) {
                    $save_new_address_sql = "INSERT INTO order_shipping 
                                             (user_ref, shipping_address, shipping_city, shipping_state, shipping_pincode, is_saved_address) 
                                             VALUES (?, ?, ?, ?, ?, 1)";
                    $save_new_address_stmt = $conn->prepare($save_new_address_sql);
                    $save_new_address_stmt->bind_param("issss", $_SESSION['user_id'], $address, $city, $state, $postal_code);
                    $save_new_address_stmt->execute();
                }
            }

            // Rest of the order processing remains the same as in the previous code...
            // (Insert order items, update stock, process payment, etc.)
            // ... [Previous order processing code remains unchanged]

            // Commit transaction
            $conn->commit();

            // Clear cart after successful order
            $_SESSION['cart'] = [];
            $_SESSION['order_success'] = true;
            $_SESSION['invoice_number'] = $invoice_number;
            $_SESSION['transaction_id'] = $transaction_id;

            // Redirect to order confirmation page
            header("Location: order_confirmation.php?order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "An error occurred: " . $e->getMessage();
        }
    }
}

// Include header
require("pages/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Plant Shop</title>
    <link rel="stylesheet" href="css/Product.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        /* Previous styles remain the same */
        
        /* New styles for saved addresses */
        .saved-address-option {
            border: 1px solid #e0e0e0;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .saved-address-option.active {
            border-color: #007bff;
            background-color: #f0f8ff;
        }
        .saved-address-option input[type="radio"] {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <h1 class="mb-4"><i class="fas fa-credit-card"></i> Checkout</h1>

        <!-- Error Message -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Checkout Form -->
        <form method="POST" action="">
            <div class="row">
                <!-- Customer Information & Shipping -->
                <div class="col-lg-8">
                    <!-- Saved Addresses Section (New) -->
                    <?php if (!empty($saved_addresses)): ?>
                    <div class="checkout-section card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Saved Addresses</h5>
                        </div>
                        <div class="card-body">
                            <div class="saved-addresses">
                                <?php foreach ($saved_addresses as $index => $saved_address): ?>
                                    <div class="saved-address-option">
                                        <input type="radio" 
                                               name="saved_address_select" 
                                               id="saved_address_<?php echo $index; ?>" 
                                               data-address="<?php echo htmlspecialchars($saved_address['shipping_address']); ?>"
                                               data-city="<?php echo htmlspecialchars($saved_address['shipping_city']); ?>"
                                               data-state="<?php echo htmlspecialchars($saved_address['shipping_state']); ?>"
                                               data-pincode="<?php echo htmlspecialchars($saved_address['shipping_pincode']); ?>">
                                        <label for="saved_address_<?php echo $index; ?>">
                                            <?php echo htmlspecialchars($saved_address['shipping_address'] . ", " . 
                                                                        $saved_address['shipping_city'] . ", " . 
                                                                        $saved_address['shipping_state'] . " - " . 
                                                                        $saved_address['shipping_pincode']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="saved-address-option">
                                    <input type="radio" name="saved_address_select" id="new_address" checked>
                                    <label for="new_address">Enter a New Address</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Shipping Information -->
                    <div class="checkout-section card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-shipping-fast"></i> Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name" class="required">Full Name</label>
                                        <input type="text" id="full_name" name="full_name" class="form-control"
                                            value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="required">Email</label>
                                        <input type="email" id="email" name="email" class="form-control"
                                            value="<?php echo htmlspecialchars($user['email_address'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="address" class="required">Street Address</label>
                                <input type="text" id="address" name="address" class="form-control" required>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="city" class="required">City</label>
                                        <input type="text" id="city" name="city" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="state" class="required">State</label>
                                        <input type="text" id="state" name="state" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="postal_code" class="required">Postal Code</label>
                                        <input type="text" id="postal_code" name="postal_code" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="required">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                    value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="save_address" name="save_address">
                                <label class="form-check-label" for="save_address">Save this address for future orders</label>
                            </div>
                        </div>
                    </div>

                    <!-- Rest of the code remains the same as in the previous version -->
                    <!-- (Payment Method section, Order Summary section) -->
                    <!-- ... -->
                </div>
            </div>
        </form>
    </div>

    <!-- JavaScript dependencies and scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            // Saved Address Selection
            $('.saved-address-option input[type="radio"]').change(function() {
                $('.saved-address-option').removeClass('active');
                $(this).closest('.saved-address-option').addClass('active');

                if ($(this).attr('id') !== 'new_address') {
                    // Populate address fields with saved address
                    $('#address').val($(this).data('address'));
                    $('#city').val($(this).data('city'));
                    $('#state').val($(this).data('state'));
                    $('#postal_code').val($(this).data('pincode'));
                } else {
                    // Clear address fields for new address
                    $('#address, #city, #state, #postal_code').val('');
                }
            });

            // Rest of the previous JavaScript validation and form handling code remains the same
            // ... (include all the previous JavaScript code for payment methods, validation, etc.)
        });
    </script>

    <?php require("pages/footer.php"); ?>
</body>
</html>