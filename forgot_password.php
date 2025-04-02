<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect
if(isset($_SESSION["user_id"])){
    header("location: user_dashboard.php");
    exit;
}
 
// Include config file
require_once "config/database.php";

// Establish database connection
$conn = new mysqli("localhost", "root", "", "plant_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
// Define variables and initialize with empty values
$email = $new_password = $confirm_password = "";
$email_err = $new_password_err = $confirm_password_err = $reset_err = "";
$email_verified = false;
$reset_success = "";

// Processing email verification when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify_email"])){
    
    // Check if email is empty
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email address.";
    } else{
        $email = trim($_POST["email"]);
        // Basic email validation
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Validate email existence
    if(empty($email_err)){
        // Check if email exists in the users table
        $sql = "SELECT user_id, full_name, email_address FROM users WHERE email_address = ?";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Store result
                $stmt->store_result();
                
                // Check if email exists
                if($stmt->num_rows == 1){                    
                    // Bind result variables
                    $stmt->bind_result($id, $full_name, $email_address);
                    
                    if($stmt->fetch()){
                        // Store user_id in a session variable
                        $_SESSION["reset_user_id"] = $id;
                        $email_verified = true;
                    }
                } else{
                    // Email doesn't exist, but don't reveal this information for security reasons
                    $email_err = "No account found with that email address.";
                }
            } else{
                $reset_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
}

// Processing password reset when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_password"])){
    
    // Validate new password
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Please enter the new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6){
        $new_password_err = "Password must have at least 6 characters.";
    } else{
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm the password.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check if user_id exists in session
    if(!isset($_SESSION["reset_user_id"])){
        $reset_err = "Invalid password reset request.";
    }
    
    // Check input errors before updating the database
    if(empty($new_password_err) && empty($confirm_password_err) && empty($reset_err)){
        // Prepare an update statement
        $sql = "UPDATE users SET user_password = ? WHERE user_id = ?";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("si", $param_password, $param_id);
            
            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $_SESSION["reset_user_id"];
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Password updated successfully
                $reset_success = "Your password has been reset successfully. You can now <a href='sign-in.php'>login</a> with your new password.";
                
                // Remove the reset_user_id from session
                unset($_SESSION["reset_user_id"]);
                
                // Clear the form fields
                $email = $new_password = $confirm_password = "";
                $email_verified = false;
            } else{
                $reset_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
}

// Close connection
$conn->close();
?>

<?php include 'pages/header.php'; ?>

<body>
    <div class="sign-box">
        <div class="login-container">
            <div class="form-title">
                <h1>Forgot Password</h1>
                <p><?php echo !$email_verified ? "Enter your email address to reset your password" : "Enter your new password"; ?></p>
            </div>

            <?php 
            if(!empty($reset_err)){
                echo '<p class="error">' . $reset_err . '</p>';
            }
            if(!empty($reset_success)){
                echo '<p class="success">' . $reset_success . '</p>';
            } else {
                if(!$email_verified) {
                    // Display email verification form
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                            <?php if(!empty($email_err)) echo '<p class="error">' . $email_err . '</p>'; ?>
                        </div>
                        <div class="sign-in-btn">
                            <button type="submit" name="verify_email">Continue</button>
                        </div>
                    </form>
                    <div class="form-last-redirect">
                        <p>Remember your password? <a href="sign-in.php">Sign In</a></p>
                    </div>
                    <?php
                } else {
                    // Display password reset form
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" value="<?php echo $new_password; ?>" required>
                            <span id="toggleNewIcon" class="toggle-icon" onclick="toggleNewPasswordVisibility()">👁</span>
                            <?php if(!empty($new_password_err)) echo '<p class="error">' . $new_password_err . '</p>'; ?>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" value="<?php echo $confirm_password; ?>" required>
                            <span id="toggleConfirmIcon" class="toggle-icon" onclick="toggleConfirmPasswordVisibility()">👁</span>
                            <?php if(!empty($confirm_password_err)) echo '<p class="error">' . $confirm_password_err . '</p>'; ?>
                        </div>
                        <div class="sign-in-btn">
                            <button type="submit" name="reset_password">Reset Password</button>
                        </div>
                    </form>
                    <div class="form-last-redirect">
                        <p><a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">Back to email verification</a></p>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>

    <script>
        function toggleNewPasswordVisibility() {
            const passwordInput = document.getElementById('new_password');
            const toggleIcon = document.getElementById('toggleNewIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '👁'; // Eye
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '🙈'; // Hidden Eye
            }
        }

        function toggleConfirmPasswordVisibility() {
            const passwordInput = document.getElementById('confirm_password');
            const toggleIcon = document.getElementById('toggleConfirmIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '👁'; // Eye
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '🙈'; // Hidden Eye
            }
        }
    </script>

<?php include 'pages/footer.php'; ?>