<?php
// Start session for order data
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$host = 'localhost';
$dbname = 'plant_db';
$username = 'root';
$password = '';

// Establish database connection using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Convert PDO connection to mysqli for compatibility with existing code
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

class OrderConfirmation {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Validate and process order
    public function processOrder($orderData) {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Validate input data
            $this->validateOrderData($orderData);

            // Create order
            $orderId = $this->createOrder($orderData);

            // Process order items
            $this->processOrderItems($orderId, $orderData['items']);

            // Process payment
            $paymentId = $this->processPayment($orderId, $orderData['payment']);

            // Update product stock
            $this->updateProductStock($orderData['items']);

            // Commit transaction
            $this->pdo->commit();

            // Store order success in session
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $orderId;
            $_SESSION['payment_success'] = $orderData['payment']['status'] == 'completed';

            return [
                'success' => true,
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'message' => 'Order processed successfully'
            ];

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Validate order data
    private function validateOrderData($orderData) {
        // Check required fields
        $requiredFields = ['user_id', 'total_cost', 'order_type', 'items', 'payment'];
        foreach ($requiredFields as $field) {
            if (!isset($orderData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Validate total cost
        if ($orderData['total_cost'] <= 0) {
            throw new Exception("Invalid total cost");
        }

        // Validate order type
        if (!in_array($orderData['order_type'], ['buy', 'rent'])) {
            throw new Exception("Invalid order type");
        }

        // Validate items
        if (empty($orderData['items'])) {
            throw new Exception("No items in the order");
        }
    }

    // Create order record
    private function createOrder($orderData) {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders 
            (user_ref, total_cost, order_type, discount_amount, invoice_number, order_status) 
            VALUES 
            (:user_id, :total_cost, :order_type, :discount_amount, :invoice_number, :order_status)
        ");

        $invoiceNumber = 'INV-' . uniqid();
        $orderStatus = $orderData['payment']['method'] == 'cod' ? 'processing' : 'confirmed';

        $stmt->execute([
            ':user_id' => $orderData['user_id'],
            ':total_cost' => $orderData['total_cost'],
            ':order_type' => $orderData['order_type'],
            ':discount_amount' => $orderData['discount_amount'] ?? 0,
            ':invoice_number' => $invoiceNumber,
            ':order_status' => $orderStatus
        ]);

        return $this->pdo->lastInsertId();
    }

    // Process order items
    private function processOrderItems($orderId, $items) {
        $stmt = $this->pdo->prepare("
            INSERT INTO order_items 
            (order_ref, product_ref, item_quantity, item_price) 
            VALUES 
            (:order_id, :product_id, :quantity, :price)
        ");

        foreach ($items as $item) {
            $stmt->execute([
                ':order_id' => $orderId,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price']
            ]);
        }
    }

    // Process payment
    private function processPayment($orderId, $paymentData) {
        $stmt = $this->pdo->prepare("
            INSERT INTO payment 
            (order_ref, transaction_amount, payment_method, payment_status, transaction_id, payment_details) 
            VALUES 
            (:order_id, :amount, :method, :status, :transaction_id, :details)
        ");

        $stmt->execute([
            ':order_id' => $orderId,
            ':amount' => $paymentData['amount'],
            ':method' => $paymentData['method'],
            ':status' => $paymentData['status'] ?? 'pending',
            ':transaction_id' => $paymentData['transaction_id'] ?? null,
            ':details' => json_encode($paymentData['details'] ?? [])
        ]);

        return $this->pdo->lastInsertId();
    }

    // Update product stock
    private function updateProductStock($items) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - :quantity 
            WHERE product_id = :product_id
        ");

        foreach ($items as $item) {
            $stmt->execute([
                ':quantity' => $item['quantity'],
                ':product_id' => $item['product_id']
            ]);
        }
    }

    // Additional function to fetch order details
    public function getOrderDetails($order_id, $user_id) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, p.payment_method, p.payment_status, p.transaction_id 
            FROM orders o
            LEFT JOIN payment p ON o.order_id = p.order_ref
            WHERE o.order_id = ? AND o.user_ref = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Additional function to get order items
    public function getOrderItems($order_id) {
        $stmt = $this->pdo->prepare("
            SELECT oi.*, p.product_name, p.product_image 
            FROM order_items oi 
            JOIN products p ON oi.product_ref = p.product_id 
            WHERE oi.order_ref = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: sign-in.php?redirect=order_confirmation.php");
    exit();
}

// Process order via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse incoming JSON data
    $rawInput = file_get_contents('php://input');
    $orderData = json_decode($rawInput, true);

    // Add user_id from session
    $orderData['user_id'] = $_SESSION['user_id'];

    // Initialize order confirmation
    $orderConfirmation = new OrderConfirmation($pdo);
    $result = $orderConfirmation->processOrder($orderData);

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

// Check if order_id is provided in URL
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    // If no order_id is provided but success session exists, try to get the most recent order
    if (isset($_SESSION['order_success']) && $_SESSION['order_success']) {
        $order_id = $_SESSION['order_id'];
    } else {
        // No order success and no order_id
        header("Location: index.php");
        exit();
    }
} else {
    $order_id = intval($_GET['order_id']);
}

// Check if payment was successful
$payment_successful = isset($_SESSION['payment_success']) && $_SESSION['payment_success'];

// Initialize order confirmation for retrieving details
$orderConfirmation = new OrderConfirmation($pdo);

// Get order details
$order = $orderConfirmation->getOrderDetails($order_id, $_SESSION['user_id']);

// Check if order exists and belongs to the current user
if (!$order) {
    // Order not found or doesn't belong to user
    header("Location: index.php");
    exit();
}

// If payment was successful, update payment and order status
if ($payment_successful) {
    // Update payment status to completed
    $sqlUpdatePayment = "UPDATE payment SET payment_status = 'completed' 
                         WHERE order_ref = ? AND payment_method != 'cod'";
    $stmtUpdatePayment = $conn->prepare($sqlUpdatePayment);
    
    if ($stmtUpdatePayment) {
        $stmtUpdatePayment->bind_param("i", $order_id);
        $stmtUpdatePayment->execute();
    }
    
    // Update order status to confirmed
    $sqlUpdateOrder = "UPDATE orders SET order_status = 'confirmed' WHERE order_id = ?";
    $stmtUpdateOrder = $conn->prepare($sqlUpdateOrder);
    
    if ($stmtUpdateOrder) {
        $stmtUpdateOrder->bind_param("i", $order_id);
        $stmtUpdateOrder->execute();
    }
}

// Get order items
$orderItems = $orderConfirmation->getOrderItems($order_id);

// Get user details
function getUserDetails($conn, $user_id) {
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
$user = getUserDetails($conn, $_SESSION['user_id']);

// Clear order success and payment success session if exists
if (isset($_SESSION['order_success'])) {
    unset($_SESSION['order_success']);
}
if (isset($_SESSION['payment_success'])) {
    unset($_SESSION['payment_success']);
}

// Include header
require("pages/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Plant Shop</title>
    <link rel="stylesheet" href="css/Product.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        /* CSS styles remain the same as in the previous implementation */
        .order-success-icon {
            color: #28a745;
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .order-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .order-items .item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .order-items .item:last-child {
            border-bottom: none;
        }
        
        .order-items .item-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .custom-status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .custom-status-badge i {
            margin-right: 5px;
        }
        
        .status-processing {
            background-color: #007bff;
            color: white;
        }
        
        .status-shipped {
            background-color: #17a2b8;
            color: white;
        }
        
        .status-delivered {
            background-color: #28a745;
            color: white;
        }
        
        .status-preparing {
            background-color: #ffc107;
            color: #212529;
        }
        
        .payment-successful {
            background-color: #28a745;
            color: white;
        }
        
        .payment-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .payment-failed {
            background-color: #dc3545;
            color: white;
        }
        
        .print-section {
            text-align: right;
            margin-bottom: 20px;
        }
        
        /* Styling for print */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
            }
            
            body {
                font-size: 12pt;
            }
            
            .card {
                border: none !important;
            }
        }
    </style>
</head>

<body>
    <?php if (isset($_GET['debug']) && $_GET['debug'] == 1): ?>
    <div class="container mt-4 p-3 bg-light">
        <h4>Debug Information</h4>
        <pre><?php print_r($order); ?></pre>
    </div>
    <?php endif; ?>

    <div class="container mt-4 mb-5">
        <!-- Print button -->
        <div class="print-section no-print">
            <<a href="invoice.php?order_id=<?php echo $order_id; ?>" class="btn btn-outline-secondary">View Invoice</a>
        </div>
        
        <!-- Order Confirmation Header -->
        <div class="text-center mb-4">
            <i class="fas fa-check-circle order-success-icon"></i>
            <h1 class="display-4">Order Confirmed!</h1>
            <p class="lead">Thank you for your purchase. Your order has been received and is being processed.</p>
        </div>
        
        <!-- Order Summary Card -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-shopping-bag"></i> Order Summary</h5>
                    <span class="badge badge-pill badge-info">Order #<?php echo $order_id; ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Order Info Column -->
                    <div class="col-md-6">
                        <div class="order-details">
                            <h5 class="mb-3">Order Information</h5>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Order Number:</div>
                                <div class="col-7">#<?php echo $order_id; ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Date Placed:</div>
                                <div class="col-7"><?php echo date('F j, Y, g:i a', strtotime($order['order_created'])); ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Order Status:</div>
                                <div class="col-7">
                                    <?php 
                                    // Determine order status
                                    $method = $order['payment_method'] ?? ($order['order_type'] == 'cod' ? 'cod' : '');
                                    
                                    // Define custom order statuses
                                    $status = $order['order_status'] ?? 'processing';
                                    $statusIcon = 'cog fa-spin';
                                    $statusClass = 'status-processing';
                                    
                                    if ($method == 'cod') {
                                        $status = 'preparing';
                                        $statusIcon = 'box';
                                        $statusClass = 'status-preparing';
                                    } elseif ($order['payment_status'] == 'completed') {
                                        $status = 'shipped';
                                        $statusIcon = 'shipping-fast';
                                        $statusClass = 'status-shipped';
                                    }
                                    ?>
                                    <span class="custom-status-badge <?php echo $statusClass; ?>">
                                        <i class="fas fa-<?php echo $statusIcon; ?>"></i> <?php echo ucfirst($status); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Invoice Number:</div>
                                <div class="col-7"><?php echo $order['invoice_number']; ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Order Type:</div>
                                <div class="col-7"><?php echo ucfirst($order['order_type'] ?? 'Standard'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Info Column -->
                    <div class="col-md-6">
                        <div class="order-details">
                            <h5 class="mb-3">Payment Information</h5>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Payment Method:</div>
                                <div class="col-7">
                                    <?php 
                                    $method = $order['payment_method'] ?? ($order['order_type'] == 'cod' ? 'cod' : '');
                                    $methodName = "";
                                    $methodIcon = "money-check-alt";
                                    
                                    switch($method) {
                                        case 'cod':
                                            $methodName = "Cash on Delivery";
                                            $methodIcon = "money-bill-wave";
                                            break;
                                        case 'card':
                                            $methodName = "Credit/Debit Card";
                                            $methodIcon = "credit-card";
                                            break;
                                        case 'upi':
                                            $methodName = "UPI";
                                            $methodIcon = "mobile-alt";
                                            break;
                                        case 'qr':
                                            $methodName = "QR Code Payment";
                                            $methodIcon = "qrcode";
                                            break;
                                        case 'netbanking':
                                            $methodName = "Net Banking";
                                            $methodIcon = "university";
                                            break;
                                        default:
                                            $methodName = !empty($method) ? ucfirst($method) : "Not specified";
                                    }
                                    ?>
                                    <i class="fas fa-<?php echo $methodIcon; ?>"></i> <?php echo $methodName; ?>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Payment Status:</div>
                                <div class="col-7">
                                    <?php 
                                    // Simplified payment status logic
                                    $paymentIcon = '';
                                    $paymentClass = '';
                                    $paymentStatusText = '';
                                    
                                    if ($method == 'cod') {
                                        $paymentIcon = 'clock';
                                        $paymentClass = 'payment-pending';
                                        $paymentStatusText = 'Pending';
                                    } else {
                                        $paymentIcon = $order['payment_status'] == 'completed' ? 'check-circle' : 'clock';
                                        $paymentClass = $order['payment_status'] == 'completed' ? 'payment-successful' : 'payment-pending';
                                        $paymentStatusText = $order['payment_status'] == 'completed' ? 'Successful' : 'Pending';
                                    }
                                    ?>
                                    <span class="custom-status-badge <?php echo $paymentClass; ?>">
                                        <i class="fas fa-<?php echo $paymentIcon; ?>"></i> <?php echo $paymentStatusText; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (!empty($order['transaction_id'])): ?>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Transaction ID:</div>
                                <div class="col-7"><?php echo $order['transaction_id']; ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Amount:</div>
                                <div class="col-7">₹<?php echo number_format($order['total_cost'], 2); ?></div>
                            </div>
                            <?php if ($method == 'cod'): ?>
                            <div class="mt-3 alert alert-info">
                                <i class="fas fa-info-circle"></i> You will pay ₹<?php echo number_format($order['total_cost'], 2); ?> upon delivery.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="order-details">
                            <h5 class="mb-3">Customer Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-5 info-label">Name:</div>
                                        <div class="col-7"><?php echo htmlspecialchars($user['full_name'] ?? 'Not available'); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5 info-label">Email:</div>
                                        <div class="col-7"><?php echo htmlspecialchars($user['email_address'] ?? 'Not available'); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5 info-label">Phone:</div>
                                        <div class="col-7"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not available'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-5 info-label">Shipping Address:</div>
                                        <div class="col-7">
                                            <?php 
                                                if (isset($user['user_address']) && !empty($user['user_address'])) {
                                                    echo nl2br(htmlspecialchars($user['user_address']));
                                                } else {
                                                    echo '<span class="text-muted">No address provided</span>';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Order Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="order-items">
                    <?php if (empty($orderItems)): ?>
                        <div class="p-4 text-center">
                            <p class="text-muted">No items found for this order.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orderItems as $item): ?>
                            <div class="item d-flex align-items-center">
                                <?php if (isset($item['product_image']) && !empty($item['product_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['product_image']); ?>" class="item-image mr-3" alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?>">
                                <?php else: ?>
                                    <div class="item-image mr-3 bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-seedling text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></h6>
                                    <p class="mb-0 text-muted">Quantity: <?php echo $item['item_quantity']; ?></p>
                                </div>
                                
                                <div class="text-right ml-3">
                                    <h6 class="mb-0">₹<?php echo number_format($item['item_price'], 2); ?></h6>
                                    <p class="mb-0 text-muted">
                                    ₹<?php echo number_format($item['item_price'] * $item['item_quantity'], 2); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Order Totals -->
                        <div class="p-4 bg-light">
                            <div class="row">
                                <div class="col-md-6 offset-md-6">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span>₹<?php 
                                            $subtotal = 0;
                                            foreach ($orderItems as $item) {
                                                $subtotal += $item['item_price'] * $item['item_quantity'];
                                            }
                                            echo number_format($subtotal, 2);
                                        ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Shipping:</span>
                                        <span>₹0.00</span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tax (5%):</span>
                                        <span>₹<?php echo number_format($subtotal * 0.05, 2); ?></span>
                                    </div>
                                    
                                    <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span>Discount:</span>
                                        <span>-₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                        <strong>Total:</strong>
                                        <strong>₹<?php echo number_format($order['total_cost'], 2); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row mt-4 no-print">
            <div class="col-md-12 d-flex justify-content-between">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                
                <div>
                    <a href="my_orders.php" class="btn btn-outline-secondary mr-2">
                        <i class="fas fa-list-alt"></i> View All Orders
                    </a>
                    <a href="contact.php" class="btn btn-outline-info">
                        <i class="fas fa-question-circle"></i> Need Help?
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <?php require("pages/footer.php"); ?>
</body>

</html>