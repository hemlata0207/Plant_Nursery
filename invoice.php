<?php
// Start session for cart functionality
session_start();

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header("Location: sign-in.php?redirect=invoice.php");
    exit();
}

// Database configuration (consider moving to a separate config file)
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "plant_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$error = '';
$invoice_data = null;
$order_items = [];
$order_details = null;
$user_details = null;
$invoice_details = null;

// Function to generate unique invoice number
function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . sprintf('%04d', rand(0, 9999));
}

// Fetch order details
if ($order_id > 0) {
    // Start a transaction for data consistency
    $conn->begin_transaction();

    try {
        // Fetch order details
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_ref = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $order_result = $stmt->get_result();
        
        if ($order_result->num_rows > 0) {
            $order_details = $order_result->fetch_assoc();
            
            // Fetch order items
            $stmt = $conn->prepare("SELECT oi.*, p.product_name, p.product_description, p.product_image 
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_ref = p.product_id 
                                    WHERE oi.order_ref = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $items_result = $stmt->get_result();
            while ($item = $items_result->fetch_assoc()) {
                $order_items[] = $item;
            }
            
            // Fetch user details
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_result = $stmt->get_result();
            $user_details = $user_result->fetch_assoc();
            
            // Check if invoice already exists, if not create one
            $stmt = $conn->prepare("SELECT * FROM invoice WHERE order_refer = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $invoice_result = $stmt->get_result();
            
            if ($invoice_result->num_rows == 0) {
                // Calculate invoice details
                $subtotal = $order_details['total_cost'] / 1.05;
                $tax_amount = $order_details['total_cost'] * 0.05;
                
                // Generate unique invoice number
                $invoice_number = generateInvoiceNumber();
                
                // Insert invoice
                $stmt = $conn->prepare("INSERT INTO invoice (
                    order_refer, 
                    user_refer, 
                    invoice_number, 
                    subtotal, 
                    tax_amount, 
                    total_amount, 
                    shipping_cost, 
                    payment_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $shipping_cost = 0.00; // Currently free shipping
                $payment_status = 'Paid';
                
                $stmt->bind_param(
                    "iisdddss", 
                    $order_id, 
                    $user_id, 
                    $invoice_number, 
                    $subtotal, 
                    $tax_amount, 
                    $order_details['total_cost'], 
                    $shipping_cost,
                    $payment_status
                );
                $stmt->execute();
                
                // Fetch the newly created invoice
                $stmt = $conn->prepare("SELECT * FROM invoice WHERE order_refer = ?");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $invoice_result = $stmt->get_result();
            }
            
            // Fetch invoice details
            $invoice_details = $invoice_result->fetch_assoc();
            
            // Commit transaction
            $conn->commit();
        } else {
            $error = "Order not found or does not belong to you.";
        }
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $conn->rollback();
        $error = "Database error: " . $e->getMessage();
    }
} else {
    $error = "Invalid order ID.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Plant Shop</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        @media print {
            .invoice-actions {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .invoice-logo {
            font-size: 24px;
            font-weight: bold;
        }
        .invoice-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .invoice-details-group {
            width: 45%;
        }
        .details-row {
            margin-bottom: 10px;
        }
        .details-label {
            font-weight: bold;
        }
        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .invoice-items th, .invoice-items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .invoice-summary {
            text-align: right;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 1.2em;
        }
        .invoice-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .invoice-actions {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php require("pages/header.php"); ?>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($order_details && $invoice_details): ?>
            <div class="invoice-container" id="invoice-content">
                <div class="invoice-header">
                    <div class="invoice-logo">
                        <i class="fas fa-leaf"></i> Plant Shop
                    </div>
                    <div class="invoice-info">
                        <div>Invoice #: <?php echo htmlspecialchars($invoice_details['invoice_number'] ?? ''); ?></div>
                        <div>Order #: <?php echo htmlspecialchars($order_details['order_id'] ?? ''); ?></div>
                        <div>Date: <?php echo date('d M, Y', strtotime($invoice_details['invoice_date'] ?? 'now')); ?></div>
                    </div>
                </div>
                
                <div class="invoice-title">
                    <h1>ORDER INVOICE</h1>
                </div>
                
                <div class="invoice-details">
                    <div class="invoice-details-group">
                        <h3>Bill To:</h3>
                        <div class="details-row">
                            <span class="details-label">Name:</span> 
                            <?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Email:</span> 
                            <?php echo htmlspecialchars($user_details['email_address'] ?? ''); ?>
                        </div>
                        <div class="details-row">
                            <span class="details-label">Phone:</span> 
                            <?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="invoice-details-group">
                        <h3>Shipping Address:</h3>
                        <div class="details-row">
                            <?php 
                            $address_parts = explode(',', $user_details['user_address'] ?? '');
                            echo htmlspecialchars(trim($address_parts[0] ?? '')); ?>,
                            <?php echo htmlspecialchars(trim($address_parts[1] ?? '')); ?>,
                            <?php echo htmlspecialchars(trim($address_parts[2] ?? '')); ?>
                        </div>
                    </div>
                </div>
                
                <table class="invoice-items">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name'] ?? ''); ?></td>
                            <td class="item-description">
                                <?php 
                                $desc = $item['product_description'] ?? '';
                                echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 97) . '...' : $desc); 
                                ?>
                            </td>
                            <td>₹<?php echo number_format(($item['item_price'] ?? 0), 2); ?></td>
                            <td><?php echo ($item['item_quantity'] ?? 0); ?></td>
                            <td>₹<?php echo number_format(($item['item_price'] ?? 0) * ($item['item_quantity'] ?? 0), 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="invoice-summary">
                    <div class="summary-row">
                        <div>Subtotal:</div>
                        <div>₹<?php echo number_format(($invoice_details['subtotal'] ?? 0), 2); ?></div>
                    </div>
                    
                    <div class="summary-row">
                        <div>Tax (5%):</div>
                        <div>₹<?php echo number_format(($invoice_details['tax_amount'] ?? 0), 2); ?></div>
                    </div>
                    
                    <div class="summary-row">
                        <div>Shipping:</div>
                        <div>Free</div>
                    </div>
                    
                    <div class="summary-row total">
                        <div>Total:</div>
                        <div>₹<?php echo number_format(($invoice_details['total_amount'] ?? 0), 2); ?></div>
                    </div>
                    
                    <div class="summary-row">
                        <div>Payment Status:</div>
                        <div><?php echo htmlspecialchars($invoice_details['payment_status'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="invoice-footer">
                    <p>Thank you for your purchase!</p>
                    <p>For any inquiries, please contact us at support@plantshop.com</p>
                </div>
            </div>
            
            <div class="invoice-actions">
                <button id="print-invoice" class="btn btn-outline-secondary">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
                <button id="download-invoice" class="btn btn-outline-primary">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php require("pages/footer.php"); ?>

    <!-- Required JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- PDF Generation Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Print Invoice
            const printButton = document.getElementById('print-invoice');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    window.print();
                });
            }
            
            // Download Invoice as PDF
            const downloadButton = document.getElementById('download-invoice');
            if (downloadButton) {
                downloadButton.addEventListener('click', function() {
                    const { jsPDF } = window.jspdf;
                    
                    // Create new PDF document
                    const doc = new jsPDF('p', 'pt', 'a4');
                    
                    // Get the invoice content
                    const invoiceContent = document.getElementById('invoice-content');
                    
                    // Use html2canvas to convert the HTML to a canvas
                    html2canvas(invoiceContent, {
                        scale: 2,
                        useCORS: true,
                        logging: false
                    }).then(canvas => {
                        // Get the image data from the canvas
                        const imgData = canvas.toDataURL('image/png');
                        
                        // Page dimensions
                        const pageWidth = doc.internal.pageSize.getWidth();
                        const pageHeight = doc.internal.pageSize.getHeight();
                        
                        // Calculate the image width and height to fit the page
                        const imgWidth = pageWidth - 40;
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;
                        
                        // Add the image to the PDF
                        doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
                        
                        // Save the PDF
                        doc.save('invoice-<?php echo htmlspecialchars($invoice_details['invoice_number'] ?? 'download'); ?>.pdf');
                    });
                });
            }
        });
    </script>
</body>
</html>