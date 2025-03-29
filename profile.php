<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "plant_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch current user details
$user_id = $_SESSION['user_id'];
$user_details = [];
$error_message = "";
$success_message = "";

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_details = $result->fetch_assoc();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $full_name = trim($_POST['full_name'] ?? '');
    $email_address = trim($_POST['email_address'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $user_address = trim($_POST['user_address'] ?? '');

    // Validate inputs
    $errors = [];
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    if (empty($email_address) || !filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required.";
    }

    // Handle profile image upload
    $profile_image = $user_details['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $upload_dir = 'uploads/profile_images/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = $user_id . '_profile_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            // Remove old profile image if it's not the default
            if ($profile_image != 'default-avatar.png' && file_exists($upload_dir . $profile_image)) {
                unlink($upload_dir . $profile_image);
            }

            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = $new_filename;
            } else {
                $errors[] = "Failed to upload profile image.";
            }
        } else {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }

    // If no errors, update profile
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET 
            full_name = ?, 
            email_address = ?, 
            phone_number = ?, 
            user_address = ?, 
            profile_image = ?
            WHERE user_id = ?");
        $stmt->bind_param("sssssi", 
            $full_name, 
            $email_address, 
            $phone_number, 
            $user_address, 
            $profile_image, 
            $user_id
        );

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            // Refresh user details
            $user_details = array_merge($user_details, [
                'full_name' => $full_name,
                'email_address' => $email_address,
                'phone_number' => $phone_number,
                'user_address' => $user_address,
                'profile_image' => $profile_image
            ]);
        } else {
            $error_message = "Failed to update profile. " . $stmt->error;
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Close connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Plant Store</title>
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .profile-image-container {
            position: relative;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
        }
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.5);
            overflow: hidden;
            width: 100%;
            height: 0;
            transition: .5s ease;
        }
        .profile-image-container:hover .profile-image-overlay {
            height: 50px;
        }
        .profile-image-overlay input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="bi bi-person-circle me-2"></i>User Profile
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="profile-image-container">
                                <img src="uploads/profile_images/<?php echo htmlspecialchars($user_details['profile_image'] ?? 'default-avatar.png'); ?>" 
                                     alt="Profile Image" class="profile-image">
                                <div class="profile-image-overlay text-center text-white">
                                    <span class="align-middle">Change Photo</span>
                                    <input type="file" name="profile_image" accept="image/*">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email_address" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email_address" name="email_address" 
                                           value="<?php echo htmlspecialchars($user_details['email_address'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone_number" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                           value="<?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="user_role" class="form-label">User Role</label>
                                    <input type="text" class="form-control" id="user_role" 
                                           value="<?php echo htmlspecialchars(ucfirst($user_details['user_role'] ?? 'Customer')); ?>" 
                                           readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="user_address" class="form-label">Address</label>
                                <textarea class="form-control" id="user_address" name="user_address" rows="3"><?php 
                                    echo htmlspecialchars($user_details['user_address'] ?? ''); 
                                ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="created_on" class="form-label">Member Since</label>
                                <input type="text" class="form-control" id="created_on" 
                                       value="<?php echo date('F j, Y', strtotime($user_details['created_on'] ?? 'now')); ?>" 
                                       readonly>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Update Profile
                                </button>
                                <a href="user_dashboard.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview profile image on selection
        document.querySelector('input[name="profile_image"]').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>