<?php 
session_start(); 
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$is_logged_in = isset($_SESSION['user_id']);
$full_name = $is_logged_in ? htmlspecialchars($_SESSION['full_name']) : '';

$conn = new mysqli("localhost", "root", "", "plant_db", 3306); 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); 
}
$pageTitle = "About Us | Alpine Green Plant Nursery";
include('pages/header.php');
?>
<link rel="stylesheet" href="css/about.css">
<main class="about-us-container">
  <section class="hero-section">
    <h1>About Alpine Green Plant Nursery</h1>
    <p class="tagline">Growing with Nature, Nurturing with Care</p>
  </section>

  <section class="our-story">
    <div class="about-container">
      <h2>Our Story</h2>
      <div class="story-content">
        <div class="story-image">
          <img src="assets/images/logo.png.png" alt="Alpine Green Plant Nursery Logo" />
        </div>
        <div class="story-text">
          <p><b>Alpine Green</b> Plant Nursery was founded in 2010 as a small family business with a passion for plants and a mission to bring the beauty of nature to every home and garden.</p>
          <p>What began as a modest operation with just a few varieties of plants has now grown into a comprehensive nursery offering hundreds of plant species, gardening supplies, and expert advice. Through the years, we've maintained our commitment to quality, sustainability, and customer satisfaction. Our team of horticulturists and plant enthusiasts work tirelessly to ensure that every plant that leaves our nursery is healthy, vibrant, and ready to thrive in its new home for <?php echo date('Y') - 2010; ?> years and counting.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="our-values">
        <h2>What Sets Us Apart</h2>
        <div class="values-grid">
            <div class="value-card ">
                <div class="value-card-front">
                    <i class="fa fa-seedling"></i>
                    <h3>Quality Assurance</h3>
                    <p>Every plant is carefully nurtured and inspected before it reaches your hands. We guarantee the health and quality of all our products.</p>
                </div>
                <div class="value-card-back">
                    <p>Our quality assurance process includes regular health checks, optimal growing conditions, and a final inspection before any plant leaves our nursery. We're so confident in our plants that we offer a 30-day health guarantee.</p>
                </div>
            </div>

            <div class="value-card">
                <div class="value-card-front">
                    <i class="fa fa-leaf"></i>
                    <h3>Plant Variety</h3>
                    <p>With over 500 species of indoor and outdoor plants, we offer one of the most diverse collections in the region.</p>
                </div>
                <div class="value-card-back">
                    <p>Our collection includes rare specimens, native species, seasonal favorites, and everything in between. We constantly update our inventory to bring you unique and trending plants from around the world.</p>
                </div>
            </div>

            <div class="value-card">
                <div class="value-card-front">
                    <i class="fa fa-users"></i>
                    <h3>Expert Guidance</h3>
                    <p>Our team of certified horticulturists provides personalized advice to help you select and care for your plants.</p>
                </div>
                <div class="value-card-back">
                    <p>Beyond in-store consultations, we offer workshops, detailed care guides, and follow-up support. Our experts are passionate about sharing their knowledge and ensuring your green friends thrive.</p>
                </div>
            </div>

            <div class="value-card">
                <div class="value-card-front">
                    <i class="fa fa-recycle"></i>
                    <h3>Sustainability</h3>
                    <p>We're committed to eco-friendly practices, from biodegradable pots to organic fertilizers and responsible water usage.</p>
                </div>
                <div class="value-card-back">
                    <p>Sustainability drives every decision we make. We've implemented rainwater collection systems, use solar power where possible, and prioritize local sourcing to reduce our carbon footprint.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="our-team">
    <h2>Meet Our Team</h2>
    <div class="team-members">
        <div class="team-member">
            <div class="team-member-front">
                <img src="assets/images/p2.png" alt="John Smith" />
                <h3>John Smith</h3>
                <p class="position">Founder & Head Horticulturist</p>
            </div>
            <div class="team-member-back">
                <h3>John Smith</h3>
                <p class="bio">With over 20 years of experience in horticulture, John founded Alpine Green with a vision to create a sustainable plant nursery focused on quality and education.</p>
                <div class="contact">
                    <p><b>Email:</b> john@alpinegreen.com</p>
                    <p><b>Specialties:</b> Rare Plants & Cultivation</p>
                </div>
            </div>
        </div>

        <div class="team-member">
            <div class="team-member-front">
                <img src="assets/images/p1.png" alt="Emily Johnson" />
                <h3>Emily Johnson</h3>
                <p class="position">Plant Care Specialist</p>
            </div>
            <div class="team-member-back">
                <h3>Emily Johnson</h3>
                <p class="bio">Emily brings her botanical science background to help customers find the perfect plants for their specific environments and provides expert care advice.</p>
                <div class="contact">
                    <p><b>Email:</b> emily@alpinegreen.com</p>
                    <p><b>Specialties:</b> Indoor Plants & Terrariums</p>
                </div>
            </div>
        </div>

        <div class="team-member">
            <div class="team-member-front">
                <img src="assets/images/p3.png" alt="Michael Torres" />
                <h3>Michael Torres</h3>
                <p class="position">Landscape Designer</p>
            </div>
            <div class="team-member-back">
                <h3>Michael Torres</h3>
                <p class="bio">Michael creates beautiful, sustainable garden designs that harmonize with nature. His designs focus on native plants and water conservation.</p>
                <div class="contact">
                    <p><b>Email:</b> michael@alpinegreen.com</p>
                    <p><b>Specialties:</b> Landscape Design & Native Plants</p>
                </div>
            </div>
        </div>

        <div class="team-member">
            <div class="team-member-front">
                <img src="assets/images/p4.png" alt="Sarah Chen" />
                <h3>Sarah Chen</h3>
                <p class="position">Workshop Coordinator</p>
            </div>
            <div class="team-member-back">
                <h3>Sarah Chen</h3>
                <p class="bio">Sarah organizes our educational workshops and community events, sharing her passion for connecting people with nature through hands-on learning experiences.</p>
                <div class="contact">
                    <p><b>Email:</b> sarah@alpinegreen.com</p>
                    <p><b>Specialties:</b> Education & Community Outreach</p>
                </div>
            </div>
        </div>
    </div>
</section>

  <section class="store-info">
    <h2>Visit Our Nursery</h2>
    <div class="store-details">
      <div class="map">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3782.0030997589285!2d73.7507627745495!3d18.578303871515914!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bc2ba33f8d3d9e7%3A0xa5f95e0d5a1e2d0b!2sSus%2C%20Pune%2C%20Maharashtra%20411211%2C%20India!5e0!3m2!1sen!2sin!4v1709801000000!5m2!1sen!2sin" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
      <div class="contact-info">
        <p><strong>Address:</strong> 123 Green Valley Road, Pune, Maharashtra 411211</p>
        <p><strong>Phone:</strong> (+91) 987-654-3210</p>
        <p><strong>Hours:</strong></p> 
        <ul>
          <li><b>Monday - Friday</b>: 8am - 6pm</li>
          <li><b>Saturday</b>: 8am - 5pm</li>
          <li><b>Sunday</b>: 10am - 4pm</li>
        </ul>
        <a href="contact.php" class="btn">Contact Us</a>
      </div>
    </div>
  </section>

  <section class="testimonials">
        <h2>What Our Customers Say</h2>
        <div class="testimonial-slider">
            <div class="testimonial">
                <blockquote>"Alpine Green Nursery has transformed my home with their amazing plant selection. The staff is knowledgeable and always helpful with care tips."</blockquote>
                <p class="author">— Priya M., Plant Enthusiast</p>
            </div>

            <div class="testimonial">
                <blockquote>"The landscape design service by Michael was exceptional. He understood exactly what we wanted and created a sustainable garden that's beautiful year-round."</blockquote>
                <p class="author">— Raj S., Homeowner</p>
            </div>

            <div class="testimonial">
                <blockquote>"I've attended several workshops at Alpine Green and always leave with new knowledge and plants. Their passion for education sets them apart from other nurseries."</blockquote>
                <p class="author">— Anisha P., Workshop Participant</p>
            </div>
        </div>
    </section>
    
</main>
<?php include('pages/footer.php'); ?>
