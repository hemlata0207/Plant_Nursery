<?php
session_start();
$conn = new mysqli("localhost", "root", "", "plant_db", 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $user_role = $_POST['user_role'] ?? 'customer';
    
    // Address Fields
    $flat_house_building = trim($_POST['flat_house_building'] ?? '');
    $area_street_village = trim($_POST['area_street_village'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $town_city_state_country = trim($_POST['town_city_state_country'] ?? '');

    // Admin-Specific Fields
    $admin_code = trim($_POST['admin_code'] ?? '');

    // Validation
    if (empty($name)) $errors['name'] = "Enter your name";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Enter a valid email address";
    if (empty($password) || strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors['confirm_password'] = "Passwords do not match";
    if (empty($phone_number) || !preg_match("/^[0-9]{10}$/", $phone_number)) $errors['phone_number'] = "Enter a valid 10-digit phone number";

    if ($user_role == "customer") {
        if (empty($flat_house_building) || empty($area_street_village) || empty($town_city_state_country)) {
            $errors['address'] = "Complete address required";
        }
        $full_address = "$flat_house_building, $area_street_village, $landmark, $town_city_state_country";
    } else {
        if (empty($admin_code)) {
            $errors['admin_code'] = "Admin code is required";
        }
        $full_address = "Admin User";
    }

    // Proceed if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (full_name, email_address, user_password, user_role, user_address, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $hashed_password, $user_role, $full_address, $phone_number);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! Please sign in.";
            $stmt->close();
            $conn->close();
            header("Location: sign-in.php");
            exit();
        } else {
            $errors['database'] = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<?php include 'pages/header.php'; ?>
<body class="sign-up-body">
    <div class="sign-up-container">
      
        
        <?php if (!empty($errors)): ?>
            <div class="sign-up-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <p class="sign-up-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <form method="POST" class="sign-up-form">
            <h1>Create account</h1>
            <div class="form-columns">
                <!-- Left Column - Personal Information -->
                <div class="form-column left-column">
                    <h3 class="sign-up-section-title">Personal Information</h3>
                    <input type="text" name="name" class="sign-up-input" placeholder="Full Name" required>
                    <input type="email" name="email" class="sign-up-input" placeholder="Email" required>
                    <input type="tel" name="phone_number" class="sign-up-input" placeholder="Phone Number" required>
                    <input type="password" name="password" class="sign-up-input" placeholder="Password" required>
                    <input type="password" name="confirm_password" class="sign-up-input" placeholder="Confirm Password" required>
                    
                    <select name="user_role" id="user_role" class="sign-up-select" required onchange="toggleFields()">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                    
                    <!-- Admin Fields (Visible only for Admins) -->
                    <div id="admin_fields" class="sign-up-admin-fields" style="display: none;">
                        <h3 class="sign-up-section-title">Admin Verification</h3>
                        <input type="text" name="admin_code" class="sign-up-input" placeholder="Enter Admin Code">
                    </div>
                </div>
                
                <!-- Right Column - Address Information -->
                <div class="form-column right-column" id="address_fields">
                    <h3 class="sign-up-section-title">Address</h3>
                    <input type="text" name="flat_house_building" class="sign-up-input" placeholder="Flat, House no., Building, Apartment">
                    <input type="text" name="area_street_village" class="sign-up-input" placeholder="Area, Street, Sector, Village">
                    <input type="text" name="landmark" class="sign-up-input" placeholder="Landmark (Optional)">
                    <input type="text" name="town_city_state_country" class="sign-up-input" placeholder="Town/City, State, Country">
                </div>
            </div>
            
            <!-- Centered Button -->
            <div class="button-container">
                <button type="submit" class="sign-up-button">Create Account</button>
            </div>
            <div class="form-last-redirect sign-up-redirect"> 
                <p>If Already has an account? <a href="sign-in.php">Log-In</a></p>
            </div>
        </form>
        
    </div>
    <?php include 'pages/footer.php'; ?>
    
   
    
    <script>
        function toggleFields() {
            var userRole = document.getElementById("user_role").value;
            var addressSection = document.getElementById("address_fields");
            var adminFields = document.getElementById("admin_fields");

            if (userRole === "admin") {
                addressSection.style.display = "none";
                adminFields.style.display = "block";
            } else {
                addressSection.style.display = "block";
                adminFields.style.display = "none";
            }
        }
    </script>
</body>
</html>