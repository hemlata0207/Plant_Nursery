<?php session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email_address, phone_number FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$full_name = $user['full_name'];
$email = $user['email_address'];
$phone = $user['phone_number'];

// Handle ticket submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ticket'])) {
    $subject = $_POST['subject'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    
    // Prepare and execute the query
    // Create support_tickets table if not exists
    $create_table_query = "CREATE TABLE IF NOT EXISTS support_tickets (
        ticket_id INT AUTO_INCREMENT PRIMARY KEY,
        user_ref BIGINT UNSIGNED,
        ticket_subject VARCHAR(255),
        ticket_category VARCHAR(100),
        ticket_description TEXT,
        ticket_priority ENUM('Low', 'Medium', 'High'),
        ticket_status ENUM('Open', 'In Progress', 'Closed') DEFAULT 'Open',
        created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_ref) REFERENCES users(user_id)
    )";
    $conn->query($create_table_query);
    
    // Insert ticket
    $stmt = $conn->prepare("INSERT INTO support_tickets (user_ref, ticket_subject, ticket_category, ticket_description, ticket_priority) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $subject, $category, $description, $priority);
    
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Ticket submitted successfully! Our team will respond shortly.</div>";
    } else {
        $message = "<div class='error-message'>Error submitting ticket. Please try again.</div>";
    }
}

// Get user's previous tickets
$stmt = $conn->prepare("SELECT ticket_id, ticket_subject, ticket_category, ticket_priority, ticket_status, created_on FROM support_tickets WHERE user_ref = ? ORDER BY created_on DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets = $stmt->get_result();

// Create FAQs table if not exists
$create_faq_table = "CREATE TABLE IF NOT EXISTS faqs (
    faq_id INT AUTO_INCREMENT PRIMARY KEY,
    faq_question VARCHAR(255),
    faq_answer TEXT,
    created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($create_faq_table);

// Get FAQ data
$faq_query = "SELECT faq_id, faq_question, faq_answer FROM faqs ORDER BY faq_id ASC LIMIT 8";
$faqs = $conn->query($faq_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support & Help | Plant Store</title>
    <link rel="stylesheet" href="css/support.css">
    <link rel="stylesheet" href="https://unpkg.com/lucide-static@latest/font/lucide.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Plant Store</h2>
            </div>
            <nav>
                <ul class="nav-list">
                    <li><a href="dashboard.php" class="nav-link"><i class="lucide-home"></i> <span>Dashboard</span></a></li>
                    <li><a href="products.php" class="nav-link"><i class="lucide-leaf"></i> <span>Browse Plants</span></a></li>
                    <li><a href="cart.php" class="nav-link"><i class="lucide-shopping-cart"></i> <span>My Cart</span></a></li>
                    <li><a href="orders.php" class="nav-link"><i class="lucide-clipboard-list"></i> <span>My Orders</span></a></li>
                    <li><a href="profile.php" class="nav-link"><i class="lucide-user"></i> <span>My Profile</span></a></li>
                    <li><a href="support.php" class="nav-link active"><i class="lucide-help-circle"></i> <span>Help & Support</span></a></li>
                </ul>
            </nav>
            <div class="logout-section">
                <a href="logout.php" class="logout-btn"><i class="lucide-log-out"></i> <span>Logout</span></a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="welcome-banner">
                <h1>Help & Support</h1>
                <p>Have questions or need assistance? We're here to help you with any plant-related inquiries.</p>
            </div>
            
            <?php echo $message; ?>
            
            <div class="support-container">
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2 class="section-title">Submit a Support Ticket</h2>
                    </div>
                    
                    <form class="support-form" method="POST" action="">
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required placeholder="Brief description of your issue">
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="Plant Care">Plant Care</option>
                                <option value="Order Issue">Order Issue</option>
                                <option value="Payment Problem">Payment Problem</option>
                                <option value="Product Question">Product Question</option>
                                <option value="Shipping Inquiry">Shipping Inquiry</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                value="High">High</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" required placeholder="Please provide details about your plant-related issue..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_ticket" class="form-submit">Submit Ticket</button>
                    </form>
                </div>
                
                <div>
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2 class="section-title">Frequently Asked Questions</h2>
                        </div>
                        
                        <?php if ($faqs->num_rows > 0): ?>
                            <div class="faq-list">
                                <?php while ($faq = $faqs->fetch_assoc()): ?>
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <?php echo htmlspecialchars($faq['faq_question']); ?>
                                        <i class="lucide-chevron-down icon-toggle"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <?php echo htmlspecialchars($faq['faq_answer']); ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No FAQs available at the moment.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="contact-info">
                            <h3>Contact Us Directly</h3>
                            <div class="contact-method">
                                <i class="lucide-mail"></i>
                                <a href="mailto:support@plantstore.com">support@plantstore.com</a>
                            </div>
                            <div class="contact-method">
                                <i class="lucide-phone"></i>
                                <a href="tel:+18001234567">+1 (800) 123-4567</a>
                            </div>
                            <div class="contact-method">
                                <i class="lucide-clock"></i>
                                <span>Mon-Fri: 9am to 5pm EST</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Previous Tickets Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Your Recent Tickets</h2>
                </div>
                
                <?php if ($tickets->num_rows > 0): ?>
                    <div class="tickets-list">
                        <?php while ($ticket = $tickets->fetch_assoc()): ?>
                            <div class="ticket-card">
                                <div class="ticket-header">
                                    <div class="ticket-subject"><?php echo htmlspecialchars($ticket['ticket_subject']); ?></div>
                                    <div class="ticket-date"><?php echo date("M d, Y", strtotime($ticket['created_on'])); ?></div>
                                </div>
                                <div class="ticket-details">
                                    <div class="ticket-category">
                                        <i class="lucide-tag"></i> <?php echo htmlspecialchars($ticket['ticket_category']); ?>
                                    </div>
                                    <div class="ticket-priority priority-<?php echo strtolower($ticket['ticket_priority']); ?>">
                                        <i class="lucide-alert-circle"></i> <?php echo htmlspecialchars($ticket['ticket_priority']); ?> Priority
                                    </div>
                                </div>
                                <div class="ticket-footer">
                                    <span class="ticket-status status-<?php echo strtolower(str_replace(' ', '-', $ticket['ticket_status'])); ?>">
                                        <?php echo htmlspecialchars($ticket['ticket_status']); ?>
                                    </span>
                                    <a href="ticket_details.php?id=<?php echo $ticket['ticket_id']; ?>" class="view-ticket-btn">View Details</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You haven't submitted any support tickets yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // FAQ accordion functionality
        const faqQuestions = document.querySelectorAll('.faq-question');
        
        faqQuestions.forEach(question => {
            question.addEventListener('click', function() {
                this.classList.toggle('active');
            });
        });
    });
    </script>
</body>
</html>