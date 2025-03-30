<?php 
session_start(); 
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$is_logged_in = isset($_SESSION['user_id']);
$full_name = $is_logged_in ? htmlspecialchars($_SESSION['full_name']) : '';

$conn = new mysqli("localhost", "root", "", "plant_db", 3306); 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); 
}
require("pages/header.php")
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Alpine Green Plant Nursery</title>
    <link rel="stylesheet" href="css/about.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Main styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header styling */
        header {
            background-color: #2c3e50;
            color: #fff;
            padding: 15px 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
        }

        .brand i {
            margin-right: 10px;
            color: #4CAF50;
        }

        .nav-menu {
            display: flex;
            list-style: none;
        }

        .nav-item {
            margin: 0 15px;
        }

        .nav-link {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #4CAF50;
        }

        .user-actions {
            display: flex;
            align-items: center;
        }

        .user-actions a {
            margin-left: 15px;
            color: #ecf0f1;
            text-decoration: none;
        }

        /* About Us specific styles */
        .about-hero {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/nursery-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .about-hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .about-hero p {
            font-size: 18px;
            max-width: 800px;
            margin: 0 auto;
        }

        .about-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 40px;
        }

        .about-section h2 {
            color: #2c3e50;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .about-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s;
            border-top: 4px solid #4CAF50;
        }

        .about-card:hover {
            transform: translateY(-5px);
        }

        .about-card i {
            font-size: 40px;
            color: #4CAF50;
            margin-bottom: 15px;
        }

        .team-member {
            text-align: center;
            margin-bottom: 30px;
        }

        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 4px solid #4CAF50;
        }

        .cta-section {
            background-color: #2c3e50;
            color: white;
            padding: 50px 0;
            text-align: center;
            margin: 40px 0;
            border-radius: 8px;
        }

        .cta-btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .cta-btn:hover {
            background-color: #45a049;
        }

        /* Footer styling */
        footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 40px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 20px;
        }

        .footer-column h3 {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
        }

        .footer-column ul li {
            margin-bottom: 8px;
        }

        .footer-column ul li a {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-column ul li a:hover {
            color: #4CAF50;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .social-links a {
            color: #ecf0f1;
            font-size: 20px;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: #4CAF50;
        }

        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #34495e;
            margin-top: 20px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-menu {
                margin: 15px 0;
                justify-content: center;
            }
            
            .user-actions {
                margin-top: 15px;
                justify-content: center;
            }
            
            .about-hero h1 {
                font-size: 36px;
            }
            
            .about-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>



<div class="about-hero">
    <div class="container">
        <h1>About Alpine Green Plant Nursery</h1>
        <p>Growing with Nature, Nurturing with Care</p>
    </div>
</div>

<div class="container">
    <div class="about-section">
        <h2>Our Story</h2>
        <p>Founded in 2010, Alpine Green Plant Nursery started as a small family business with a passion for plants and a mission to bring the beauty of nature to every home and garden. What began as a modest operation with just a few varieties of plants has now grown into a comprehensive nursery offering hundreds of plant species, gardening supplies, and expert advice.</p>
        
        <p>Through the years, we've maintained our commitment to quality, sustainability, and customer satisfaction. Our team of horticulturists and plant enthusiasts work tirelessly to ensure that every plant that leaves our nursery is healthy, vibrant, and ready to thrive in its new home.</p>
        
        <p>At Alpine Green, we believe that plants have the power to transform spaces and enhance well-being. We're not just selling plants; we're sharing a lifestyle that embraces the natural world and its countless benefits.</p>
    </div>
    
    <div class="about-section">
        <h2>What Sets Us Apart</h2>
        <div class="about-grid">
            <div class="about-card">
                <i class="fas fa-seedling"></i>
                <h3>Quality Assurance</h3>
                <p>Every plant is carefully nurtured and inspected before it reaches your hands. We guarantee the health and quality of all our products.</p>
            </div>
            
            <div class="about-card">
                <i class="fas fa-leaf"></i>
                <h3>Plant Variety</h3>
                <p>With over 500 species of indoor and outdoor plants, we offer one of the most diverse collections in the region.</p>
            </div>
            
            <div class="about-card">
                <i class="fas fa-users"></i>
                <h3>Expert Guidance</h3>
                <p>Our team of certified horticulturists provides personalized advice to help you select and care for your plants.</p>
            </div>
            
            <div class="about-card">
                <i class="fas fa-recycle"></i>
                <h3>Sustainability</h3>
                <p>We're committed to eco-friendly practices, from biodegradable pots to organic fertilizers and responsible water usage.</p>
            </div>
            
            <div class="about-card">
                <i class="fas fa-heart"></i>
                <h3>Community Focus</h3>
                <p>We regularly host workshops, donate to local schools, and participate in community greening initiatives.</p>
            </div>
            
            <div class="about-card">
                <i class="fas fa-truck"></i>
                <h3>Reliable Delivery</h3>
                <p>Our specialized plant delivery service ensures your new green friends arrive safely and in perfect condition.</p>
            </div>
        </div>
    </div>
    
    <div class="about-section">
        <h2>Our Team</h2>
        <p>Behind every thriving plant at Alpine Green is a team of dedicated professionals who share a passion for horticulture and customer service.</p>
        
        <div class="about-grid">
            <div class="team-member">
                <img src="/api/placeholder/150/150" alt="John Smith">
                <h3>John Smith</h3>
                <p>Founder & Head Horticulturist</p>
            </div>
            
            <div class="team-member">
                <img src="/api/placeholder/150/150" alt="Emily Johnson">
                <h3>Emily Johnson</h3>
                <p>Plant Care Specialist</p>
            </div>
            
            <div class="team-member">
                <img src="/api/placeholder/150/150" alt="Michael Torres">
                <h3>Michael Torres</h3>
                <p>Landscape Designer</p>
            </div>
        </div>
    </div>
    
    <div class="cta-section">
        <h2>Ready to Start Your Green Journey?</h2>
        <p>Visit our nursery today or browse our online collection to find the perfect plants for your space.</p>
        <a href="Product.php" class="cta-btn">Shop Now</a>
    </div>
</div>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="Product.php">Shop</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
    
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="shipping.php">Shipping Policy</a></li>
                    <li><a href="returns.php">Returns & Refunds</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contact Us</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Green Street, Plant City</li>
                    <li><i class="fas fa-phone"></i> (555) 123-4567</li>
                    <li><i class="fas fa-envelope"></i> info@alpinegreen.com</li>
                </ul>
                
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Newsletter</h3>
                <p>Subscribe for plant care tips, special offers, and more!</p>
                <form action="subscribe.php" method="post">
                    <input type="email" name="email" placeholder="Your email address" required style="padding: 10px; width: 100%; margin-bottom: 10px;">
                    <button type="submit" style="background-color: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Subscribe</button>
                </form>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Alpine Green Plant Nursery. All rights reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>