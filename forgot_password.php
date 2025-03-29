<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}
 
// Include config file
require_once "config/database.php";

// Establish database connection - use this approach if your config/database.php doesn't define $link
// If your config file already creates a connection variable with a different name, adjust accordingly
if(!isset($link)) {
    // Direct connection if constants aren't defined in config file
    $link = mysqli_connect("localhost", "root", "", "plant_db");
    
    // Check connection
    if(!$link){
        die("Connection failed: " . mysqli_connect_error());
    }
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
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $full_name, $email_address);
                    
                    if(mysqli_stmt_fetch($stmt)){
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
            mysqli_stmt_close($stmt);
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
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
            
            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $_SESSION["reset_user_id"];
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
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
            mysqli_stmt_close($stmt);
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 360px; padding: 20px; margin: 0 auto; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Forgot Password</h2>

        <?php 
        if(!empty($reset_err)){
            echo '<div class="alert alert-danger">' . $reset_err . '</div>';
        }
        if(!empty($reset_success)){
            echo '<div class="alert alert-success">' . $reset_success . '</div>';
        } else {
            if(!$email_verified) {
                // Display email verification form
                ?>
                <p>Please enter your email address to reset your password.</p>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" name="verify_email" class="btn btn-primary" value="Continue">
                    </div>
                    <p>Remember your password? <a href="sign-in.php">Login here</a></p>
                </form>
                <?php
            } else {
                // Display password reset form
                ?>
                <p>Please enter your new password.</p>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_password; ?>">
                        <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" name="reset_password" class="btn btn-primary" value="Reset Password">
                    </div>
                    <p><a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">Back to email verification</a></p>
                </form>
                <?php
            }
        }
        ?>
    </div>
</body>
</html>