<?php
// Include configuration and dependencies
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's the final step
    if (isset($_POST['register_submit'])) {
        // Sanitize input data
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $full_name = sanitize_input($_POST['full_name']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $current_balance = floatval($_POST['current_balance']);
        
        // Validate input data
        $validation_errors = validate_registration($username, $email, $full_name, $password, $confirm_password, $current_balance);
        
        if (!empty($validation_errors)) {
            $error = implode("<br>", $validation_errors);
        } else {
            // Register user
            $result = register_user($conn, $username, $email, $full_name, $password, $current_balance);
            
            if ($result['success']) {
                $success = $result['message'];
                // Redirect to login page after 3 seconds
                header("refresh:3;url=login.php");
            } else {
                $error = $result['message'];
            }
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
    <title>Register | Gastos Guard</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Normalize CSS -->
    <link rel="stylesheet" href="assets/css/normalize.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
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
                        <h1>Create Your Account</h1>
                        <p>Start tracking your expenses today</p>
                    </div>
                    
                    <!-- Multi-step indicators -->
                    <div class="form-steps">
                        <div class="step-indicator step-1 active"></div>
                        <div class="step-indicator step-2"></div>
                        <div class="step-indicator step-3"></div>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="register-form" class="auth-form multi-step-form">
                        <!-- Step 1: Personal Information -->
                        <div class="form-step step-1 active">
                            <h3 class="step-title">Personal Information</h3>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <div class="input-icon-outer">
                                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                                <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <div class="input-icon-outer">
                                <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                                </div>
                            </div>
                            
                            <div class="step-buttons">
                                <div></div> <!-- Empty div for alignment -->
                                <button type="button" class="btn btn-primary next-step" data-step="1">Continue</button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Account Information -->
                        <div class="form-step step-2">
                            <h3 class="step-title">Account Information</h3>
                            
                            <div class="form-group">
                                <label for="username">Username</label>
                                <div class="input-icon-outer">
                                <span class="input-icon"><i class="fa-solid fa-at"></i></span>
                                <input type="text" id="username" name="username" placeholder="Choose a username" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="current_balance">Current Balance</label>
                                <div class="input-icon-outer">
                                <span class="input-icon"><i class="fa-solid fa-coins"></i></span>
                                <input type="number" id="current_balance" name="current_balance" placeholder="Enter your current balance / budget" step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="step-buttons">
                                <button type="button" class="btn btn-outline prev-step" data-step="2">Back</button>
                                <button type="button" class="btn btn-primary next-step" data-step="2">Continue</button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Security -->
                        <div class="form-step step-3">
                            <h3 class="step-title">Security</h3>
                            
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="input-icon-outer">
                                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                 </div>
                                <div class="password-strength">
                                    <div class="strength-meter">
                                        <div class="strength-meter-fill" data-strength="0"></div>
                                    </div>
                                    <span class="strength-text">Password strength: Too weak</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <div class="input-icon-outer">
                                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                 </div>
                            </div>
                            
                            <div class="step-buttons">
                                <button type="button" class="btn btn-outline prev-step" data-step="3">Back</button>
                                <button type="submit" name="register_submit" class="btn btn-primary">Create Account</button>
                            </div>
                        </div>
                        
                        <div class="auth-footer">
                            <p>Already have an account? <a href="login.php">Sign in</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/validation.js"></script>
    <script src="assets/js/auth.js"></script>
</body>
</html>