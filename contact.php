<?php 
session_start(); 
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$is_logged_in = isset($_SESSION['user_id']);
$full_name = $is_logged_in ? htmlspecialchars($_SESSION['full_name']) : '';

$conn = new mysqli("localhost", "root", "", "plant_db", 3306); 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); 
}

// Initialize variables
$message_status = '';
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => ''
];

// Process contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    // Get form data and sanitize
    $form_data = [
        'name' => htmlspecialchars(trim($_POST['name'])),
        'email' => htmlspecialchars(trim($_POST['email'])),
        'phone' => htmlspecialchars(trim($_POST['phone'] ?? '')),
        'subject' => htmlspecialchars(trim($_POST['subject'])),
        'message' => htmlspecialchars(trim($_POST['message']))
    ];
    
    // Validate the data
    $errors = [];
    
    if (empty($form_data['name'])) {
        $errors[] = "Name is required";
    }
    
    if (empty($form_data['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($form_data['subject'])) {
        $errors[] = "Subject is required";
    }
    
    if (empty($form_data['message'])) {
        $errors[] = "Message is required";
    }
    
    // If no errors, proceed with saving to database
    if (empty($errors)) {
        // Create the contacts table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS contact_inquiries (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('new', 'read', 'responded') DEFAULT 'new'
        )";
        
        if ($conn->query($sql) === FALSE) {
            $message_status = '<div class="alert alert-danger">Error creating table: ' . $conn->error . '</div>';
        } else {
            // Prepare and execute the statement
            $stmt = $conn->prepare("INSERT INTO contact_inquiries (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $form_data['name'], $form_data['email'], $form_data['phone'], $form_data['subject'], $form_data['message']);
            
            if ($stmt->execute()) {
                $message_status = '<div class="alert alert-success">Thank you for your message! We\'ll get back to you shortly.</div>';
                // Clear form data after successful submission
                $form_data = [
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'subject' => '',
                    'message' => ''
                ];
            } else {
                $message_status = '<div class="alert alert-danger">Sorry, there was an error sending your message. Please try again later.</div>';
            }
            
            $stmt->close();
        }
    } else {
        // Display errors
        $message_status = '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) {
            $message_status .= '<li>' . $error . '</li>';
        }
        $message_status .= '</ul></div>';
    }
}

require("pages/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Alpine Green Plant Nursery</title>
    <link rel="stylesheet" href="css/contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* General styles */
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Hero section */
        .contact-hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/contact-bg.jpg');
            background-size: cover;
            background-position: center;
            color: #fff;
            text-align: center;
            padding: 100px 20px;
            position: relative;
        }
        
        .contact-hero h1 {
            font-size: 42px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .contact-hero p {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        /* Contact cards section */
        .contact-cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            margin: -60px 0 40px;
            position: relative;
            z-index: 10;
        }
        
        .contact-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            padding: 25px;
            text-align: center;
            flex: 1;
            min-width: 250px;
            max-width: 300px;
            transition: transform 0.3s ease;
        }
        
        .contact-card:hover {
            transform: translateY(-10px);
        }
        
        .contact-card i {
            font-size: 36px;
            color: #4CAF50;
            margin-bottom: 15px;
            display: inline-block;
            width: 70px;
            height: 70px;
            line-height: 70px;
            border-radius: 50%;
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .contact-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .contact-card p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .contact-card a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .contact-card a:hover {
            color: #45a049;
        }
        
        /* Contact form section */
        .contact-section {
            display: flex;
            flex-wrap: wrap;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
            margin-bottom: 60px;
            overflow: hidden;
        }
        
        .contact-info {
            flex: 1;
            background-color: #4CAF50;
            color: #fff;
            padding: 40px;
            min-width: 300px;
        }
        
        .contact-info h2 {
            font-size: 28px;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .contact-info h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #fff;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .info-item i {
            font-size: 20px;
            margin-right: 15px;
            margin-top: 3px;
        }
        
        .info-details h4 {
            font-size: 18px;
            margin: 0 0 5px;
        }
        
        .info-details p {
            margin: 0;
            font-size: 15px;
            opacity: 0.9;
        }
        
        .social-links {
            margin-top: 30px;
        }
        
        .social-links h4 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
        }
        
        .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            color: #fff;
            text-align: center;
            line-height: 40px;
            transition: all 0.3s ease;
        }
        
        .social-icons a:hover {
            background-color: #fff;
            color: #4CAF50;
            transform: translateY(-3px);
        }
        
        .contact-form {
            flex: 2;
            padding: 40px;
            min-width: 300px;
        }
        
        .contact-form h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .contact-form h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #4CAF50;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #4CAF50;
            outline: none;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }
        
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 14px 30px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 600;
        }
        
        .submit-btn:hover {
            background-color: #45a049;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Map section */
        .map-section {
            margin-bottom: 60px;
        }
        
        .map-section h2 {
            text-align: center;
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .map-section h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: #4CAF50;
        }
        
        .map-container {
            height: 450px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
        }
        
        /* FAQ Section */
        .faq-section {
            margin-bottom: 60px;
        }
        
        .faq-section h2 {
            text-align: center;
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .faq-section h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: #4CAF50;
        }
        
        .accordion {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .accordion-item {
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .accordion-header {
            background-color: #fff;
            padding: 18px 20px;
            cursor: pointer;
            position: relative;
            font-weight: 600;
            color: #2c3e50;
            transition: all 0.3s ease;
        }
        
        .accordion-header:hover {
            background-color: #f9f9f9;
        }
        
        .accordion-header:after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #4CAF50;
            transition: transform 0.3s ease;
        }
        
        .accordion-header.active:after {
            transform: translateY(-50%) rotate(180deg);
        }
        
        .accordion-body {
            background-color: #f9f9f9;
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        
        .accordion-body.active {
            max-height: 500px;
            padding: 20px;
        }
        
        /* Newsletter Section */
        .newsletter-section {
            background-color: #f1f8e9;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 60px;
        }
        
        .newsletter-section h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .newsletter-section p {
            max-width: 600px;
            margin: 0 auto 25px;
            color: #666;
        }
        
        .newsletter-form {
            max-width: 500px;
            margin: 0 auto;
            display: flex;
        }
        
        .newsletter-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 15px;
        }
        
        .newsletter-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 0 25px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 600;
        }
        
        .newsletter-btn:hover {
            background-color: #45a049;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .contact-hero h1 {
                font-size: 32px;
            }
            
            .contact-hero p {
                font-size: 16px;
            }
            
            .contact-info, .contact-form {
                padding: 30px;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-input {
                border-radius: 4px;
                margin-bottom: 10px;
            }
            
            .newsletter-btn {
                border-radius: 4px;
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <h1>Get In Touch</h1>
        <p>Have questions about plants or our services? We're here to help! Reach out to us through any of the methods below or visit us in person.</p>
    </div>
</section>

<!-- Contact Cards -->
<div class="container">
    <div class="contact-cards">
        <div class="contact-card">
            <i class="fas fa-map-marker-alt"></i>
            <h3>Visit Us</h3>
            <p>123 Green Street, Plant City<br>Open 7 days a week</p>
            <a href="#map-section">View on Map</a>
        </div>
        
        <div class="contact-card">
            <i class="fas fa-phone-alt"></i>
            <h3>Call Us</h3>
            <p>(555) 123-4567<br>Mon-Fri: 9am-6pm</p>
            <a href="tel:5551234567">Make a Call</a>
        </div>
        
        <div class="contact-card">
            <i class="fas fa-envelope"></i>
            <h3>Email Us</h3>
            <p>info@alpinegreen.com<br>We respond within 24 hours</p>
            <a href="mailto:info@alpinegreen.com">Send an Email</a>
        </div>
    </div>
    
    <!-- Display status message if exists -->
    <?php echo $message_status; ?>
    
    <!-- Contact Form Section -->
    <section class="contact-section">
        <div class="contact-info">
            <h2>Contact Information</h2>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div class="info-details">
                    <h4>Our Location</h4>
                    <p>123 Green Street, Plant City</p>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div class="info-details">
                    <h4>Opening Hours</h4>
                    <p>Monday-Friday: 9am-6pm</p>
                    <p>Saturday: 8am-5pm</p>
                    <p>Sunday: 10am-4pm</p>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-phone-alt"></i>
                <div class="info-details">
                    <h4>Phone</h4>
                    <p>(555) 123-4567</p>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div class="info-details">
                    <h4>Email</h4>
                    <p>info@alpinegreen.com</p>
                </div>
            </div>
            
            <div class="social-links">
                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                </div>
            </div>
        </div>
        
        <div class="contact-form">
            <h2>Send Us a Message</h2>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo $form_data['name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Your Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo $form_data['email']; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $form_data['phone']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" value="<?php echo $form_data['subject']; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" class="form-control" rows="6" required><?php echo $form_data['message']; ?></textarea>
                </div>
                
                <button type="submit" name="submit_contact" class="submit-btn">Send Message</button>
            </form>
        </div>
    </section>
    
    <!-- Map Section -->
    <section id="map-section" class="map-section">
        <h2>Find Us On The Map</h2>
        <div class="map-container">
            <!-- Replace with actual Google Maps embed -->
            <img src="/api/placeholder/1200/450" alt="Our Location Map" style="width:100%; height:100%; object-fit:cover;">
        </div>
    </section>
    
    <!-- FAQ Section -->
    <section class="faq-section">
        <h2>Frequently Asked Questions</h2>
        <div class="accordion">
            <div class="accordion-item">
                <div class="accordion-header">Do you offer plant delivery services?</div>
                <div class="accordion-body">
                    <p>Yes, we offer delivery services within a 30-mile radius of our nursery. For deliveries within 10 miles, there's a flat fee of $15. For distances between 10-30 miles, the fee is $25. For larger plants or bulk orders, we have specialized transportation to ensure they arrive safely. Please note that additional fees may apply for extra large plants or orders requiring special handling.</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <div class="accordion-header">What's your plant guarantee policy?</div>
                <div class="accordion-body">
                    <p>We stand behind the quality of our plants. All our plants come with a 14-day guarantee against defects or unhealthy conditions not caused by improper care. If your plant shows signs of distress within this period, please bring it back along with your receipt for a replacement or store credit. This guarantee does not cover damage due to improper care, pests introduced after purchase, or extreme weather conditions.</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <div class="accordion-header">Do you offer landscaping or gardening services?</div>
                <div class="accordion-body">
                    <p>Yes, our professional landscaping team offers design consultations, installation, and maintenance services. We can help with everything from small garden redesigns to complete yard transformations. Our certified horticulturists can create custom landscape plans that match your style, budget, and the specific conditions of your property. Please contact us to schedule a consultation or to request a quote for your project.</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <div class="accordion-header">I'm new to plants. Can you help me choose the right ones?</div>
                <div class="accordion-body">
                    <p>Absolutely! We love helping new plant enthusiasts find the perfect plants for their spaces and skill levels. Our knowledgeable staff can recommend plants based on your home's lighting conditions, your schedule, and your experience level. We also offer regular workshops for beginners and provide care guides with every purchase. Feel free to visit us in-store for personalized recommendations or contact us with specific questions.</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <div class="accordion-header">Do you offer workshops or classes?</div>
                <div class="accordion-body">
                    <p>Yes, we host regular workshops on various topics including basic plant care, propagation techniques, container gardening, terrarium making, and seasonal planting. Our classes are suitable for all skill levels from beginners to advanced gardeners. Workshop schedules are posted on our website and social media pages. You can also sign up for our newsletter to receive updates about upcoming events. Pre-registration is required for most workshops as spaces are limited.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <h2>Subscribe to Our Newsletter</h2>
            <p>Stay updated with our latest plant arrivals, care tips, and exclusive offers. Join our green community today!</p>
            <form class="newsletter-form" action="subscribe.php" method="POST">
                <input type="email" name="subscribe_email" class="newsletter-input" placeholder="Your email address" required>
                <button type="submit" class="newsletter-btn">Subscribe</button>
            </form>
        </div>
    </section>
</div>

<!-- Footer included from the common file -->
<?php require("pages/footer.php"); ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // FAQ Accordion
        const accordionHeaders = document.querySelectorAll('.accordion-header');
        
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                // Toggle active class on the clicked header
                this.classList.toggle('active');
                
                // Toggle the accordion body
                const accordionBody = this.nextElementSibling;
                accordionBody.classList.toggle('active');
                
                // Close other open accordions (optional)
                accordionHeaders.forEach(otherHeader => {
                    if (otherHeader !== this) {
                        otherHeader.classList.remove('active');
                        otherHeader.nextElementSibling.classList.remove('active');
                    }
                });
            });
        });
        
        // Form validation
        const contactForm = document.querySelector('.contact-form form');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                let valid = true;
                
                // Basic validation
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const subject = document.getElementById('subject').value.trim();
                const message = document.getElementById('message').value.trim();
                
                if (!name || !email || !subject || !message) {
                    valid = false;
                }
                
                // Email validation
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill out all required fields correctly.');
                }
            });
        }
    });
</script>

</body>
</html>