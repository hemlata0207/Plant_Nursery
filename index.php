<?php
// Start session for cart functionality
session_start();

// Initialize the cart array in the session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to get cart count
function getCartItemCount() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
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

// Function to get all products
function getAllProducts($conn) {
    $sql = "SELECT * FROM products ORDER BY product_id DESC";
    $result = $conn->query($sql);
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get a single product by ID
function getProductById($conn, $id) {
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

// Function to get products by category
function getProductsByCategory($conn, $category) {
    $sql = "SELECT * FROM products WHERE category = ? ORDER BY product_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get products by price range
function getProductsByPriceRange($conn, $min_price, $max_price) {
    $sql = "SELECT * FROM products WHERE product_price BETWEEN ? AND ? ORDER BY product_price ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dd", $min_price, $max_price);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get products by stock status
function getProductsByStockStatus($conn, $status) {
    if ($status === 'in_stock') {
        $sql = "SELECT * FROM products WHERE stock_quantity > 0 ORDER BY product_id DESC";
    } else if ($status === 'out_of_stock') {
        $sql = "SELECT * FROM products WHERE stock_quantity = 0 ORDER BY product_id DESC";
    } else if ($status === 'low_stock') {
        $sql = "SELECT * FROM products WHERE stock_quantity > 0 AND stock_quantity <= 5 ORDER BY product_id DESC";
    } else {
        return getAllProducts($conn);
    }
    
    $result = $conn->query($sql);
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get all product categories
function getAllCategories($conn) {
    $sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    }
    
    return $categories;
}

// Function to get minimum and maximum product prices
function getPriceRange($conn) {
    $sql = "SELECT MIN(product_price) as min_price, MAX(product_price) as max_price FROM products";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return ['min_price' => 0, 'max_price' => 100];
}

// Function to search for products
function searchProducts($conn, $searchTerm) {
    $searchTerm = "%" . $searchTerm . "%";
    $sql = "SELECT * FROM products WHERE 
            product_name LIKE ? OR 
            product_description LIKE ? OR 
            category LIKE ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get latest products
function getLatestProducts($conn, $limit = 4) {
    $sql = "SELECT * FROM products ORDER BY product_id DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get featured products
function getFeaturedProducts($conn, $limit = 4) {
    $sql = "SELECT * FROM products WHERE stock_quantity > 0 ORDER BY product_price DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Enhanced Add to cart functionality
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Get product details
    $added_product = getProductById($conn, $product_id);
    
    // Check if product exists and is in stock
    if ($added_product && $added_product['stock_quantity'] > 0) {
        // Check if product is already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            // Make sure we don't exceed available stock
            $new_quantity = min(
                $_SESSION['cart'][$product_id]['quantity'] + $quantity,
                $added_product['stock_quantity']
            );
            $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
        } else {
            // Add new product to cart
            $_SESSION['cart'][$product_id] = [
                'id' => $product_id,
                'name' => $added_product['product_name'],
                'price' => $added_product['product_price'],
                'image' => $added_product['product_image'],
                'quantity' => min($quantity, $added_product['stock_quantity'])
            ];
        }
        
        $success_message = "Added " . $added_product['product_name'] . " to your cart!";
    } else {
        $error_message = "Sorry, this product is no longer available.";
    }
}

// Get price range for filter
$priceRange = getPriceRange($conn);
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : $priceRange['min_price'];
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : $priceRange['max_price'];

// Get products for display
$products = [];

// Check if there's a search request
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $products = searchProducts($conn, $_GET['search']);
} 
// Check if there's a category filter
else if (isset($_GET['category']) && !empty($_GET['category'])) {
    $products = getProductsByCategory($conn, $_GET['category']);
} 
// Check if there's a price range filter
else if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
    $products = getProductsByPriceRange($conn, $min_price, $max_price);
}
// Check if there's a stock status filter
else if (isset($_GET['stock_status']) && !empty($_GET['stock_status'])) {
    $products = getProductsByStockStatus($conn, $_GET['stock_status']);
}
// Default: get all products
else {
    $products = getAllProducts($conn);
}

// Get all categories for the filter dropdown
$categories = getAllCategories($conn);

// Get a specific product if view details mode is active
$viewProduct = null;
if (isset($_GET['view']) && !empty($_GET['view'])) {
    $viewProduct = getProductById($conn, $_GET['view']);
}

// Get latest products and featured products for homepage sections
$latestProducts = getLatestProducts($conn);
$featuredProducts = getFeaturedProducts($conn);

// Add custom CSS for product boxes
echo '<style>
.product-box {
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
</style>';

require("pages/header.php");
?>

<div id="main">
    <div class="banner">
        <div class="bg"></div>
        <div class="bg-info">
            <h5>Hot Sale 50% Discount</h5>
            <h1>
                Bring your next <br />
                plants home
            </h1>
            <p>
                find your dream plant for your home <br />
                decoration with us , and we will make it happen
            </p>
            <a href="product.php"><button class="shop-btn">Shop Now</button></a>
        </div>
    </div>

    <div class="service">
        <h1>Our Services</h1>
        <div class="service-one">
            <div class="s-box">
                <div class="s-img">
                    <img src="assets/images/hand.svg" alt="" />
                </div>
                <h4>pick your plant</h4>
                <p>Bringing nature home, one plant at a time.</p>
            </div>
            <div class="s-box">
                <div class="s-img">
                    <img src="assets/images/pot.svg" alt="" />
                </div>
                <h4>Choose A Pot Color</h4>
                <p>Dress Your Plants in Style, To Find Their Perfect Home.</p>
            </div>
            <div class="s-box">
                <div class="s-img">
                    <img src="assets/images/hand2.svg" alt="" />
                </div>
                <h4>Have It Shipped</h4>
                <p>Green Joy Delivered to Your Door</p>
            </div>
            <div class="s-box">
                <div class="s-img">
                    <img src="assets/images/plant_water.svg" alt="" />
                </div>
                <h4>Watch It Grow</h4>
                <p>Your Plant's Journey Begins With Us</p>
            </div>
        </div>
        <div class="service-two">
            <div class="s-two-box">
                <img src="assets/images/service-bg1.jpg" alt="" />
                <div class="s-box-text">
                    <h5>Farm Snake Plant</h5>
                    <p>
                        Greenery Nursery <br />
                        Snake Plant
                    </p>
                    <a href="product.php"><button class="shop-btn">Shop Now</button></a>
                </div>
            </div>

            <div class="s-two-box">
                <img src="assets/images/service-bg2.jpg" alt="" />
                <div class="s-box-text">
                <h5>Up To 25% Discount</h5>
                    <p>
                        Buy Zamioculcas <br>
                        Zamiifolia
                    </p>
                    <a href="product.php"><button class="shop-btn">Shop Now</button></a>
                </div>
            </div>
        </div>
    </div>

    <div class="product-section">
        <h5>New Products</h5>
        <h1>Latest Products</h1>
        <div class="product-grid">
            <?php if (count($latestProducts) > 0): ?>
                <?php foreach ($latestProducts as $product): ?>
                    <div class="product-box" onclick="window.location='product.php?view=<?php echo $product['product_id']; ?>'">
                        <div class="product-img">
                            <?php if ($product['product_image']): ?>
                                <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                    alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>
                        <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                        <p>₹<?php echo number_format($product['product_price'], 2); ?></p>
                        
                        <?php if (isset($product['category']) && $product['category']): ?>
                            <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-products">
                    No products available at this time.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="banner2">
        <div class="b-one">
          <img src="assets/images/banner-one.jpg" alt="">
        </div>
        <div class="b-two">
          <div class="b-head">
            <h5>About Plants</h5>
            <h1>We Offer Landscape and <br>
            Tree Plantation </h1>
            <p>Alpine Green Plant Nursery provides healthy, locally-grown plants and expert advice from certified <br> horticulturists to transform any space into a thriving garden.</p>
          </div>
          <div class="b-bottom" >
          <div class="s-box" style="text-align: start; ">
                <div class="s-img" style="height: 6.6vw; width: 6.6vw;">
                    <img src="assets/images/watering.svg" alt="" />
                </div>
                <h4>Plant Watering</h4>
                <p style="padding-left:2vw;   color: #666666;"> Every drop brings life to your green companions.</p>
            </div>
            <div class="s-box" >
                <div class="s-img" style="height: 6.6vw; width: 6.6vw;">
                    <img src="assets/images/potted_plant.svg" alt="" />
                </div>
                <h4>Potted Plant</h4>
                <p style="padding-left:3.5vw;    color: #666666;"> Potted plants bring nature's elegance to any space.</p>
            </div>
            <div class="s-box">
                <div class="s-img" style="height: 6.6vw; width: 6.6vw;">
                    <img src="assets/images/plant_ecology.svg" alt="" />
                </div>
                <h4>Plant Ecology</h4>
                <p style="padding-left:4.6vw;   color: #666666;">Diverse plants thrive together, creating balanced ecosystems.</p>
            </div>
          </div>
        </div>    
    </div>
    
    <div class="banner3">
        <div class="banner3-info">
            <h5>Hot Sale 30% Discount</h5>
            <h1>Potted Plant With <br>
            Pot Grey 6cm</h1>
            <p>A beautifully designed potted plant in a sleek grey pot, ideal for bringing a refreshing touch of greenery <br> to any home, office, or outdoor setting while complementing various decor styles</p>
            <a href="product.php"><button class="shop-btn">Shop Now</button></a>
        </div>
    </div>


    <div class="product-section">
        <h5>Premium Collection</h5>
        <h1>Featured Products</h1>
        <div class="product-grid">
            <?php if (count($featuredProducts) > 0): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-box" onclick="window.location='product.php?view=<?php echo $product['product_id']; ?>'">
                        <div class="product-img">
                            <?php if ($product['product_image']): ?>
                                <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                    alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>
                        <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                        <p>₹<?php echo number_format($product['product_price'], 2); ?></p>
                        
                        <?php if (isset($product['category']) && $product['category']): ?>
                            <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-products">
                    No featured products available at this time.
                </div>
            <?php endif; ?>
        </div>
       
    </div>

    <div class="service" >
        <div class="service-two">
            <div class="s-two-box">
                <img src="assets/images/service-bg3.jpg" alt="" />
                <div class="s-box-text">
                <h5 >Flat 20% Discount</h5>
                    <p>
                    The Elliot Modular <br>
                    Planters
                    </p>
                    <a href="product.php"><button class="shop-btn">Shop Now</button></a>
                </div>
            </div>

            <div class="s-two-box">
                <img src="assets/images/service-bg4.jpg" alt="" />
                <div class="s-box-text">
                    <h5 >Were Spring Plant</h5>
                    <p>
                    Cloud Farm Peace <br>
                    Lily Plant
                    </p>
                    <a href="product.php"><button class="shop-btn">Shop Now</button></a>
                </div>
            </div>
        </div>
    </div>

    <div class="product-section">
    <h5>Special Offers</h5>
    <h1>Best Selling Plants</h1>
    <div class="product-grid">
        <?php
        function getBestSellingProducts($conn, $limit = 4) {
            $sql = "SELECT * FROM products WHERE stock_quantity > 5 ORDER BY stock_quantity DESC LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $products = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
            
            return $products;
        }
        
        
        $bestSellingProducts = getBestSellingProducts($conn);
        
        if (count($bestSellingProducts) > 0):
            foreach ($bestSellingProducts as $product):
        ?>
            <div class="product-box" onclick="window.location='product.php?view=<?php echo $product['product_id']; ?>'">
                <div class="product-img">
                    <?php if ($product['product_image']): ?>
                        <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                            alt="<?php echo htmlspecialchars($product['product_name']); ?>" />
                    <?php else: ?>
                        <div class="no-image">No Image</div>
                    <?php endif; ?>
                </div>
                <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                <p>₹<?php echo number_format($product['product_price'], 2); ?></p>
                
                <?php if (isset($product['category']) && $product['category']): ?>
                    <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                <?php endif; ?>
                
                
            </div>
        <?php 
            endforeach;
        else:
        ?>
            <div class="no-products">
                No best selling products available at this time.
            </div>
        <?php endif; ?>
    </div>
    </div>

    <div class="banner4">
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <!-- Slide 1 -->
                <div class="swiper-slide">
                    <div class="slide-content">
                        <div class="top-section">
                           <img src="assets/images/apple.svg" alt="">
                        </div>
                        <div class="middle-section">
                            <p>Shopping at Alpine Green Plant Nursery was a fantastic experience! The website was easy to navigate, with detailed descriptions and care instructions that helped me choose the perfect plants. My order arrived on time, well-packaged, and in excellent condition. Highly recommend!</p>
                        </div>
                        <div class="bottom-section">
                            <img src="assets/images/p2.png" alt="Profile Image">
                        </div>
                        <p>Kuleshwar Kevat</p>
                    </div>
                </div>
                
                <!-- Slide 2 -->
                <div class="swiper-slide">
                    <div class="slide-content">
                        <div class="top-section">
                        <img src="assets/images/apple.svg" alt="">
                        </div>
                        <div class="middle-section">
                            <p>I had a wonderful experience purchasing from Alpine Green Plant Nursery. Their website is user-friendly, and I loved how informative the plant descriptions were. My plants arrived fresh, healthy, and packaged with great care. Will definitely order again!</p>
                        </div>
                        <div class="bottom-section">
                            <img src="assets/images/p1.png" alt="Profile Image">
                        </div>
                        <p>Indrani Pali</p>
                    </div>
                </div>
                
                <!-- Slide 3 -->
                <div class="swiper-slide">
                    <div class="slide-content">
                        <div class="top-section">
                        <img src="assets/images/apple.svg" alt="">                       </div>
                        <div class="middle-section">
                            <p>Alpine Green Plant Nursery offers a great selection of plants, and their website makes shopping a breeze. I appreciated the detailed care instructions, which made it easy to pick the right plants for my home. The delivery was smooth, and my plants arrived in perfect shape!</p>
                        </div>
                        <div class="bottom-section">
                            <img src="assets/images/p3.png" alt="Profile Image">
                        </div>
                        <p>Monu Kevat</p>
                    </div>
                </div>
                
                <!-- Slide 4 -->
                <div class="swiper-slide">
                    <div class="slide-content">
                        <div class="top-section">
                        <img src="assets/images/apple.svg" alt="">
                        </div>
                        <div class="middle-section">
                            <p>The entire process of ordering from Alpine Green Plant Nursery was smooth and enjoyable. Their website is well-designed, making it easy to browse and shop. My plants arrived in excellent condition, carefully packaged, and just as described. I couldn't be happier!</p>
                        </div>
                        <div class="bottom-section">
                            <img src="assets/images/p4.png" alt="Profile Image">
                        </div>
                        <p>Yash Kumar</p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation buttons -->
            <div class="swiper-button-next">&#10095;</div>
            <div class="swiper-button-prev">&#10094;</div>
            
            <!-- Pagination -->
            <div class="swiper-pagination">
                <div class="swiper-pagination-bullet active"></div>
                <div class="swiper-pagination-bullet"></div>
                <div class="swiper-pagination-bullet"></div>
                <div class="swiper-pagination-bullet"></div>
            </div>
        </div>
    </div>

   <script src="js/script.js"></script>
</div>

<?php
require("pages/footer.php");

$conn->close();
?>