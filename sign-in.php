<?php
session_start();
$conn = new mysqli("localhost", "root", "", "plant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please enter both email and password.";
        header("Location: sign-in.php");
        exit();
    }

    $query = "SELECT user_id, full_name, user_password, user_role FROM users WHERE email_address = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $full_name, $hashed_password, $user_role);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['user_role'] = $user_role;

            if ($user_role === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password!";
        }
    } else {
        $_SESSION['error'] = "Invalid email or password!";
    }

    $stmt->close();
    header("Location: sign-in.php");
    exit();
}
$conn->close();
?>

<?php include 'pages/header.php'; ?>

<body>
    <div class="sign-box">
        <div class="login-container">
            <div class="form-title">
                <h1>Sign In</h1>
                <p>Enter your credentials to access your account</p>
            </div>
            <?php
            if (isset($_SESSION['error'])) {
                echo "<p class='error'>" . $_SESSION['error'] . "</p>";
                unset($_SESSION['error']);
            }
            ?>
            <form action="sign-in.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span id="toggleIcon" class="toggle-icon" onclick="togglePasswordVisibility()">👁</span>
                </div>

                <div class="sign-in-btn">
                    <button type="submit">Sign In </button>
                </div>
            </form>
            <div class="form-last-redirect">
                <p>Don't have an account? <a href="sign-up.php">Register</a></p>
                <div class="forgot-pass">
                    <a href="forget_password.php">Forgot Password ?</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '👁'; // Slash Eye
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '🙈'; // Unslash Eye
            }
        }
    </script>
    <?php include 'pages/footer.php'; ?>