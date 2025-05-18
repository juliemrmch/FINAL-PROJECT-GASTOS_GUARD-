<?php
// Include configuration and dependencies
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input data
    if (empty($username) || empty($password)) {
        $error = "All fields are required";
    } else {
        // Authenticate user
        if (authenticateUser($username, $password)) {
            // Redirect based on user type
            if ($_SESSION['user_type'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gastos Guard - Student Expense Tracker to help manage your finances effectively">
    <meta name="keywords" content="expense tracker, student finances, budget management, financial planning">
    <title>Login | Gastos Guard</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Normalize CSS -->
    <link rel="stylesheet" href="assets/css/normalize.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Auth CSS -->
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <main id="main-content">
        <div class="auth-container">
            <!-- Slideshow Background -->
            <div class="slideshow-background">
                <div class="slide active" style="background-image: url('assets/images/slides/saving-money-1.jpg');"></div>
                <div class="slide" style="background-image: url('assets/images/slides/student-budget-2.jpg');"></div>
                <div class="slide" style="background-image: url('assets/images/slides/financial-planning-3.jpg');"></div>
                <div class="slide" style="background-image: url('assets/images/slides/student-success-4.jpg');"></div>
                <div class="slide" style="background-image: url('assets/images/slides/future-planning-5.jpg');"></div>
            </div>

            <div class="auth-wrapper">
                <div class="auth-card">
                    <div class="auth-header">
                        <img src="assets/images/logo.png" alt="Gastos Guard" class="logo">
                        <h1>Welcome Back</h1>
                        <p>Track your expenses with ease</p>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="login-form" class="auth-form">
                        <div class="form-group">
                            <label for="username">Username or Email</label>
                            <div class="input-icon-outer">
                                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                                <input type="text" id="username" name="username" placeholder="Enter your username or email" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-icon-outer">
                                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                                <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <?php if (!empty($error)): ?>
                                <div class="invalid-feedback" style="display:block;"><?php echo $error; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Login</button>
                        
                        <div class="auth-footer">
                            <p>Don't have an account? <a href="register.php">Sign up</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/validation.js"></script>
    <script src="assets/js/auth.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Slideshow functionality
        const slides = document.querySelectorAll('.slideshow-background .slide');
        let currentSlide = 0;
        
        function nextSlide() {
            // Hide current slide
            slides[currentSlide].classList.remove('active');
            
            // Move to next slide
            currentSlide = (currentSlide + 1) % slides.length;
            
            // Show new slide
            slides[currentSlide].classList.add('active');
        }
        
        // Change slide every 3 seconds
        setInterval(nextSlide, 3000);
    });
    </script>
</body>
</html>