<?php
// Replace with your actual MySQL credentials
$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get counts for dashboard summary
$total_products_query = "SELECT COUNT(*) as count FROM products";
$total_orders_query = "SELECT COUNT(*) as count FROM orders";
$total_customers_query = "SELECT COUNT(*) as count FROM users WHERE user_role = 'customer'";
$low_stock_query = "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 5";
$total_suppliers_query = "SELECT COUNT(*) as count FROM suppliers";

$products_result = $conn->query($total_products_query);
$orders_result = $conn->query($total_orders_query);
$customers_result = $conn->query($total_customers_query);
$low_stock_result = $conn->query($low_stock_query);
$suppliers_result = $conn->query($total_suppliers_query);

$total_products = $products_result->fetch_assoc()['count'];
$total_orders = $orders_result->fetch_assoc()['count'];
$total_customers = $customers_result->fetch_assoc()['count'];
$low_stock = $low_stock_result->fetch_assoc()['count'];
$total_suppliers = $suppliers_result->fetch_assoc()['count'];

// Handle report view switching
$current_report = isset($_GET['report']) ? $_GET['report'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alpine Green Plant Nursery</title>
    <!-- Add Font Awesome for PDF icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add jsPDF libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="css/reports.css">
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
        .container {
            margin-left: 250px;
            padding: 20px;
        }

        header {
            margin-left: 250px;
            padding: 15px;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <i class="fas fa-leaf"></i>
                <span>Alpine Green Plant Nursery</span>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link active">
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
                <a href="manage_supplier.php" class="nav-link">
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
    <header>
        <h1>Alpine Green Plant Nursery Reports</h1>
    </header>
    <div class="container">
        <div class="dashboard-summary">
            <div class="summary-card">
                <h3>Total Products</h3>
                <div class="number"><?php echo $total_products; ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo $total_orders; ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Customers</h3>
                <div class="number"><?php echo $total_customers; ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Suppliers</h3>
                <div class="number"><?php echo $total_suppliers; ?></div>
            </div>
        </div>

        <div class="report-buttons">
            <button class="report-button <?php if ($current_report == 'sales') echo 'active'; ?>" onclick="window.location.href='?report=sales'">Sales Report</button>
            <button class="report-button <?php if ($current_report == 'customer') echo 'active'; ?>" onclick="window.location.href='?report=customer'">Customer History</button>
            <button class="report-button <?php if ($current_report == 'supplier') echo 'active'; ?>" onclick="window.location.href='?report=supplier'">Supplier Report</button>
        </div>

        <div class="report-container">
            <?php if ($current_report == 'sales'): ?>
                <!-- Sales Report -->
                <div class="report-header">
                    <h2>Sales Report</h2>
                    <button class="export-pdf-btn" id="exportPdf">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>

                <?php
                // Get date parameters (or set defaults)
                $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
                $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

                // Query for sales report
                $sql = "SELECT o.order_id, o.order_created, o.total_cost, o.order_type, 
                            u.full_name as customer_name, 
                            COUNT(oi.order_item_id) as number_of_items,
                            o.order_status,
                            p.payment_status
                    FROM orders o
                    LEFT JOIN users u ON o.user_ref = u.user_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_ref
                    LEFT JOIN payment p ON o.order_id = p.order_ref
                    WHERE o.order_created BETWEEN '$start_date' AND '$end_date 23:59:59'
                    GROUP BY o.order_id
                    ORDER BY o.order_created DESC";

                $result = $conn->query($sql);
                ?>

                <div class="filter-form">
                    <form method="post" action="?report=sales">
                        <label>Start Date: <input type="date" name="start_date" value="<?php echo $start_date; ?>"></label>
                        <label>End Date: <input type="date" name="end_date" value="<?php echo $end_date; ?>"></label>
                        <button type="submit">Filter</button>
                    </form>
                </div>

                <h3>Sales from <?php echo $start_date; ?> to <?php echo $end_date; ?></h3>

                <table id="salesTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_sales = 0;
                        $buy_count = 0;
                        $rent_count = 0;

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['order_id'] . "</td>";
                                echo "<td>" . $row['order_created'] . "</td>";
                                echo "<td>" . $row['customer_name'] . "</td>";
                                echo "<td>" . $row['order_type'] . "</td>";
                                echo "<td>" . $row['number_of_items'] . "</td>";
                                echo "<td>₹" . number_format($row['total_cost'], 2) . "</td>";
                                echo "<td>" . $row['payment_status'] . "</td>";
                                echo "</tr>";

                                if ($row['order_status'] == 'completed') {
                                    $total_sales += $row['total_cost'];
                                    if ($row['order_type'] == 'buy') $buy_count++;
                                    if ($row['order_type'] == 'rent') $rent_count++;
                                }
                            }
                        } else {
                            echo "<tr><td colspan='8'>No orders found in this date range</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px;" id="salesSummary">
                    <h3>Summary</h3>
                    <p>Total Sales: ₹<?php echo number_format($total_sales, 2); ?></p>
                    <p>Purchase Orders: <?php echo $buy_count; ?></p>
                    <p>Rental Orders: <?php echo $rent_count; ?></p>
                </div>

            <?php elseif ($current_report == 'customer'): ?>
                <!-- Customer Purchase History Report -->
                <div class="report-header">
                    <h2>Customer Purchase History</h2>
                    <button class="export-pdf-btn" id="exportPdf">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>

                <?php
                // Get date parameters (or set defaults)
                $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
                $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

                // Date filter form
                ?>
                <div class="filter-form">
                    <form method="post" action="?report=customer">
                        <label>Start Date: <input type="date" name="start_date" value="<?php echo $start_date; ?>"></label>
                        <label>End Date: <input type="date" name="end_date" value="<?php echo $end_date; ?>"></label>
                        <button type="submit">Filter</button>
                    </form>
                </div>

                <h3>Customer Purchases from <?php echo $start_date; ?> to <?php echo $end_date; ?></h3>

                <?php
                // Query for customer purchases in the date range
                $sql = "SELECT o.order_id, o.order_created, o.total_cost, o.order_type, 
                            u.full_name as customer_name, u.user_id,
                            u.email_address, u.phone_number,
                            COUNT(oi.order_item_id) as number_of_items,
                            o.order_status,
                            p.payment_status, p.payment_method
                    FROM orders o
                    LEFT JOIN users u ON o.user_ref = u.user_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_ref
                    LEFT JOIN payment p ON o.order_id = p.order_ref
                    WHERE o.order_created BETWEEN '$start_date' AND '$end_date 23:59:59'
                    AND u.user_role = 'customer'
                    GROUP BY o.order_id
                    ORDER BY u.full_name, o.order_created DESC";

                $result = $conn->query($sql);
                ?>

                <table id="customerTable">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment Method</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_sales = 0;
                        $total_customers = 0;
                        $unique_customers = [];
                        $total_orders = 0;

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['customer_name'] . "</td>";
                                echo "<td>" . $row['email_address'] . "</td>";
                                echo "<td>" . $row['order_id'] . "</td>";
                                echo "<td>" . $row['order_created'] . "</td>";
                                echo "<td>" . $row['order_type'] . "</td>";
                                echo "<td>" . $row['number_of_items'] . "</td>";
                                echo "<td>₹" . number_format($row['total_cost'], 2) . "</td>";
                                echo "<td>" . $row['payment_method'] . "</td>";
                                echo "<td>" . $row['payment_status'] . "</td>";
                                echo "</tr>";

                                if ($row['order_status'] == 'completed') {
                                    $total_sales += $row['total_cost'];
                                    $total_orders++;
                                    
                                    if (!in_array($row['user_id'], $unique_customers)) {
                                        $unique_customers[] = $row['user_id'];
                                        $total_customers++;
                                    }
                                }
                            }
                        } else {
                            echo "<tr><td colspan='10'>No customer purchases found in this date range</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px;" id="customerSummary">
                    <h3>Summary</h3>
                    <p>Total Customers: <?php echo $total_customers; ?></p>
                    <p>Total Orders: <?php echo $total_orders; ?></p>
                    <p>Total Sales: ₹<?php echo number_format($total_sales, 2); ?></p>
                    <p>Average Order Value: $<?php echo $total_orders > 0 ? number_format($total_sales / $total_orders, 2) : '0.00'; ?></p>
                </div>

            <?php elseif ($current_report == 'supplier'): ?>
                <!-- Supplier Report -->
                <div class="report-header">
                    <h2>Supplier Report</h2>
                    <button class="export-pdf-btn" id="exportPdf">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>

                <?php
                // Get date parameters (or set defaults)
                $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
                $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

                // Filter by supplier name if provided
                $supplier_filter = isset($_POST['supplier_name']) ? $_POST['supplier_name'] : '';
                $filter_condition = "";
                
                if (!empty($supplier_filter)) {
                    $filter_condition = " AND company_name LIKE '%" . $conn->real_escape_string($supplier_filter) . "%'";
                }

                // Date filter form
                ?>
                <div class="filter-form">
                    <form method="post" action="?report=supplier">
                        <label>Start Date: <input type="date" name="start_date" value="<?php echo $start_date; ?>"></label>
                        <label>End Date: <input type="date" name="end_date" value="<?php echo $end_date; ?>"></label>
                        <label>Supplier Name: <input type="text" name="supplier_name" value="<?php echo $supplier_filter; ?>" placeholder="Filter by company name"></label>
                        <button type="submit">Filter</button>
                    </form>
                </div>

                <h3>Supplier Report from <?php echo $start_date; ?> to <?php echo $end_date; ?></h3>

                <?php
                // Query for suppliers in the date range
                $sql = "SELECT 
                            supplier_id,
                            company_name,
                            contact,
                            address,
                            product,
                            quantity,
                            amount,
                            created_on
                        FROM suppliers
                        WHERE created_on BETWEEN '$start_date' AND '$end_date 23:59:59'
                        $filter_condition
                        ORDER BY created_on DESC";

                $result = $conn->query($sql);
                ?>

                <table id="supplierTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Contact</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Date </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_suppliers = 0;
                        $total_products = 0;
                        $total_cost = 0;
                        $unique_companies = [];

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['supplier_id'] . "</td>";
                                echo "<td>" . $row['company_name'] . "</td>";
                                echo "<td>" . $row['contact'] . "</td>";
                                echo "<td>" . $row['product'] . "</td>";
                                echo "<td>" . $row['quantity'] . "</td>";
                                echo "<td>₹" . number_format($row['amount'], 2) . "</td>";
                                echo "<td>" . $row['created_on'] . "</td>";
                                echo "</tr>";

                                $total_cost += $row['amount'];
                                $total_products += $row['quantity'];
                                
                                if (!in_array($row['company_name'], $unique_companies)) {
                                    $unique_companies[] = $row['company_name'];
                                    $total_suppliers++;
                                }
                            }
                        } else {
                            echo "<tr><td colspan='7'>No suppliers found in this date range</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px;" id="supplierSummary">
                    <h3>Summary</h3>
                    <p>Total Unique Suppliers: <?php echo $total_suppliers; ?></p>
                    <p>Total Products Supplied: <?php echo $total_products; ?></p>
                    <p>Total Cost: ₹<?php echo number_format($total_cost, 2); ?></p>
                    <p>Average Cost per Product: ₹<?php echo $total_products > 0 ? number_format($total_cost / $total_products, 2) : '0.00'; ?></p>
                </div>

            <?php else: ?>
                <!-- Dashboard Home -->
                <h2>Welcome to Alpine Green Plant Nursery Reports</h2>
                <p>Please select a report from the buttons above to view detailed information.</p>
                <p>Here's a quick overview of what each report provides:</p>

                <ul>
                    <li><strong>Sales Report:</strong> View all sales data filtered by date range, including order details and payment information.</li>
                    <li><strong>Customer History:</strong> View detailed purchase histories for all customers, including all their orders and items purchased, filtered by date range.</li>
                    <li><strong>Supplier Report:</strong> View all supplier information filtered by date range, including products supplied and costs.</li>
                </ul>

                <p>The dashboard summary above provides a quick snapshot of your store's current status.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- PDF Export functionality -->
    <script>
        // Replace the existing PDF export script with this improved version
        document.addEventListener('DOMContentLoaded', function() {
            const exportBtn = document.getElementById('exportPdf');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    // Verify that jsPDF is loaded
                    if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
                        alert("PDF library not loaded correctly. Please check your internet connection and try again.");
                        console.error("jsPDF library not loaded properly");
                        return;
                    }

                    try {
                        // Get current report
                        const currentReport = '<?php echo $current_report; ?>';

                        // Initialize jsPDF
                        const { jsPDF } = window.jspdf;
                        const doc = new jsPDF();

                        // Set title based on report type
                        let title = 'Alpine Green Plant Nursery Report';
                        let tableId = '';
                        let summaryId = '';

                        switch (currentReport) {
                            case 'sales':
                                title = 'Alpine Green Plant Nursery - Sales Report';
                                tableId = 'salesTable';
                                summaryId = 'salesSummary';
                                break;
                            case 'customer':
                                title = 'Alpine Green Plant Nursery - Customer Purchase History';
                                tableId = 'customerTable';
                                summaryId = 'customerSummary';
                                break;
                            case 'supplier':
                                title = 'Alpine Green Plant Nursery - Supplier Report';
                                tableId = 'supplierTable';
                                summaryId = 'supplierSummary';
                                break;
                        }

                        // Add title
                        doc.setFontSize(18);
                        doc.text(title, 14, 20);

                        // Add current date
                        doc.setFontSize(12);
                        doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 30);

                        // Set starting Y position
                        let currentY = 40;

                        // Add date range for both reports
                        if (currentReport === 'sales' || currentReport === 'customer' || currentReport === 'supplier') {
                            const startDate = document.querySelector('input[name="start_date"]').value;
                            const endDate = document.querySelector('input[name="end_date"]').value;
                            doc.text('Date Range: ' + startDate + ' to ' + endDate, 14, currentY);
                            currentY += 10;
                        }

                        // If table exists, add it to PDF
                        const table = document.getElementById(tableId);
                        if (table) {
                            // Make sure autoTable plugin is available
                            if (typeof doc.autoTable === 'undefined') {
                                alert("AutoTable plugin not loaded correctly. Please check your internet connection and try again.");
                                console.error("autoTable plugin not loaded properly");
                                return;
                            }

                            doc.autoTable({
                                html: '#' + tableId,
                                startY: currentY,
                                theme: 'grid',
                                headStyles: {
                                    fillColor: [74, 144, 226],
                                    textColor: [255, 255, 255]
                                },
                                styles: {
                                    overflow: 'linebreak',
                                    cellpadding: 3
                                }
                            });
                        }

                        // Add summary information
                        const summary = document.getElementById(summaryId);
                        if (summary) {
                            let summaryY = doc.lastAutoTable ? doc.lastAutoTable.finalY + 10 : currentY;

                            doc.setFontSize(14);
                            doc.text('Summary', 14, summaryY);
                            summaryY += 10;

                            // Get all paragraphs from summary
                            const paragraphs = summary.querySelectorAll('p');
                            doc.setFontSize(12);

                            paragraphs.forEach(p => {
                                doc.text(p.textContent, 14, summaryY);
                                summaryY += 7;
                            });
                        }

                        // Save the PDF
                        doc.save(title + '.pdf');

                    } catch (error) {
                        console.error("Error generating PDF:", error);
                        alert("Error generating PDF. Please check console for details.");
                    }
                });
            }
        });
    </script>
</body>

</html>