<?php
// Start session
session_start();

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header("Location: login.php?redirect=checkout.php");
    exit();
}

// Check if this is a valid payment request
if (!isset($_SESSION['payment_processing']) || !isset($_POST['payment_method'])) {
    header("Location: checkout.php");
    exit();
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

// Get order information from session
$order_id = $_SESSION['pending_order_id'] ?? 0;
$transaction_amount = $_SESSION['transaction_amount'] ?? 0;
$payment_method = $_POST['payment_method'];

// Additional payment details based on the method
$payment_details = '';
$transaction_id = 'TXN' . time() . rand(1000, 9999);
$payment_status = 'pending';

switch ($payment_method) {
    case 'card':
        // Validate card details
        $card_number = isset($_POST['card_number']) ? preg_replace('/\s+/', '', $_POST['card_number']) : '';
        $card_exp = isset($_POST['card_exp']) ? $_POST['card_exp'] : '';
        $card_cvv = isset($_POST['card_cvv']) ? $_POST['card_cvv'] : '';
        $card_name = isset($_POST['card_name']) ? $_POST['card_name'] : '';
        
        if (empty($card_number) || empty($card_exp) || empty($card_cvv) || empty($card_name)) {
            $_SESSION['payment_error'] = "Please fill in all card details";
            header("Location: checkout.php");
            exit();
        }
        
        // In a real scenario, you would use a payment gateway API here
        // This is a simplified example
        $payment_status = 'completed'; // Assume payment successful for demo
        
        $payment_details = json_encode([
            'card_last_four' => substr($card_number, -4),
            'card_type' => getCardType($card_number),
            'card_holder' => $card_name,
            'expiry' => $card_exp
        ]);
        break;
        
    case 'upi':
        // Validate UPI details
        $upi_id = isset($_POST['upi_id']) ? $_POST['upi_id'] : '';
        $upi_provider = isset($_POST['upi_provider']) ? $_POST['upi_provider'] : '';
        
        if (empty($upi_id) || empty($upi_provider) || strpos($upi_id, '@') === false) {
            $_SESSION['payment_error'] = "Please enter a valid UPI ID";
            header("Location: checkout.php");
            exit();
        }
        
        // In a real scenario, you would use a UPI payment gateway API here
        // For demonstration, simulate a successful payment
        $payment_status = 'completed';
        
        $payment_details = json_encode([
            'upi_id' => $upi_id,
            'upi_provider' => $upi_provider,
            'payment_reference' => 'UPI' . rand(100000, 999999)
        ]);
        break;
        
    case 'netbanking':
        // Validate bank selection
        $bank_name = isset($_POST['bank_name']) ? $_POST['bank_name'] : '';
        
        if (empty($bank_name)) {
            $_SESSION['payment_error'] = "Please select your bank";
            header("Location: checkout.php");
            exit();
        }
        
        
        // payment completion message
        $payment_status = 'completed';
        
        $payment_details = json_encode([
            'bank_name' => $bank_name,
            'reference_number' => 'NB' . rand(100000, 999999)
        ]);
        break;
        
    case 'cod':
    default:
        // Cash on Delivery
        $payment_status = 'pending'; // Payment will be collected on delivery
        
        $payment_details = json_encode([
            'cod_reference' => 'COD' . rand(100000, 999999)
        ]);
        break;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Update payment transaction
    $payment_sql = "UPDATE payment 
                   SET payment_method = ?, 
                       payment_status = ?, 
                       transaction_id = ?, 
                       payment_details = ? 
                   WHERE order_ref = ?";
                   
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("ssssi", 
                              $payment_method, 
                              $payment_status, 
                              $transaction_id, 
                              $payment_details, 
                              $order_id);
    $payment_stmt->execute();
    
    // If payment is completed, update order status
    if ($payment_status == 'completed') {
        $update_order_sql = "UPDATE orders SET order_status = 'paid' WHERE order_id = ?";
        $update_order_stmt = $conn->prepare($update_order_sql);
        $update_order_stmt->bind_param("i", $order_id);
        $update_order_stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Store transaction info in session for confirmation page
    $_SESSION['order_success'] = true;
    $_SESSION['transaction_id'] = $transaction_id;
    $_SESSION['payment_method'] = $payment_method;
    $_SESSION['payment_status'] = $payment_status;
    
    // Clear processing flags
    unset($_SESSION['payment_processing']);
    unset($_SESSION['pending_order_id']);
    unset($_SESSION['transaction_amount']);
    
    // Redirect to order confirmation page
    header("Location: order_confirmation.php?order_id=" . $order_id);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $_SESSION['payment_error'] = "Payment processing error: " . $e->getMessage();
    header("Location: checkout.php");
    exit();
}

// Function to determine card type based on first few digits
function getCardType($cardNumber) {
    $cardNumber = preg_replace('/\s+/', '', $cardNumber);
    
    if (preg_match('/^4/', $cardNumber)) {
        return 'Visa';
    } else if (preg_match('/^5[1-5]/', $cardNumber)) {
        return 'MasterCard';
    } else if (preg_match('/^3[47]/', $cardNumber)) {
        return 'American Express';
    } else if (preg_match('/^6(?:011|5)/', $cardNumber)) {
        return 'Discover';
    } else if (preg_match('/^2[2-7]/', $cardNumber)) {
        return 'MasterCard';
    } else if (preg_match('/^62/', $cardNumber)) {
        return 'UnionPay';
    } else if (preg_match('/^35(?:2[89]|[3-8])/', $cardNumber)) {
        return 'JCB';
    } else if (preg_match('/^3(?:0[0-5]|[68])/', $cardNumber)) {
        return 'Diners Club';
    } else {
        return 'Unknown';
    }
}
?>