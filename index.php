<?php
// Include essential files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gastos Guard - Student Expense Tracker to help manage your finances effectively">
    <meta name="keywords" content="expense tracker, student finances, budget management, financial planning">
    <title>Gastos Guard - Student Expense Tracker</title>
    
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
    
    <!-- Landing Page CSS -->
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
    <!-- Header Section -->
    <header class="site-header">
        <div class="container">
            <div class="logo-container">
                <a href="index.php" class="logo">
                    <img src="assets/images/logo.png" alt="Gastos Guard">
                    <span>Gastos Guard</span>
                </a>
            </div>
            
            <nav class="main-nav">
    <ul>
        <li><a href="#" class="active">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#features">Features</a></li>
        <li><a href="#cta">Sign-Up/Login</a></li>
    </ul>
</nav>
            
            <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="slideshow-background">
            <div class="slide active" style="background-image: url('assets/images/slides/saving-money-1.jpg');"></div>
            <div class="slide" style="background-image: url('assets/images/slides/student-budget-2.jpg');"></div>
            <div class="slide" style="background-image: url('assets/images/slides/financial-planning-3.jpg');"></div>
            <div class="slide" style="background-image: url('assets/images/slides/student-success-4.jpg');"></div>
            <div class="slide" style="background-image: url('assets/images/slides/future-planning-5.jpg');"></div>
        </div>
        <div class="container">
            <div class="hero-content">
                <h1>Manage Your Student Finances</h1>
                <p>Track expenses, set budgets, and achieve your financial goals with the ultimate student expense management tool.</p>
                <a href="#cta" class="btn btn-primary get-started-btn">Get Started <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About Gastos Guard</h2>
                    <p>Gastos Guard is a comprehensive expense tracking application designed specifically for students to manage their finances effectively. Our intuitive platform helps you understand your spending habits, set realistic budgets, and achieve your financial goals.</p>
                    <p>Whether you're saving for a semester abroad, managing your student loans, or just trying to make it to the end of the month, Gastos Guard is your financial companion throughout your academic journey.</p>
                </div>
                <div class="about-image">
                    <div class="logo-circle">
                        <img src="assets/images/logo.png" alt="Gastos Guard Logo" class="logo-large">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
    <div class="features-bg slideshow-background">
        <div class="slide active" style="background-image: url('assets/images/slides/saving-money-1.jpg');"></div>
        <div class="slide" style="background-image: url('assets/images/slides/student-budget-2.jpg');"></div>
        <div class="slide" style="background-image: url('assets/images/slides/financial-planning-3.jpg');"></div>
        <div class="slide" style="background-image: url('assets/images/slides/student-success-4.jpg');"></div>
        <div class="slide" style="background-image: url('assets/images/slides/future-planning-5.jpg');"></div>
    </div>
        <div class="container">
            <h2 class="section-title">Features</h2>
            <div class="features-grid">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h3>Smart Dashboard</h3>
                    <p>Comprehensive overview with expense summaries, interactive charts, and budget indicators. Monitor your daily spending and category breakdowns at a glance.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <h3>Expense Management</h3>
                    <p>Easily track expenses with detailed categorization, flexible sorting, and filtering options. Update or remove entries with just a few clicks.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-piggy-bank"></i>
                    </div>
                    <h3>Budget Management</h3>
                    <p>Set and track category-specific budgets with visual progress indicators and smart alerts when approaching limits.</p>
                </div>
                
                <!-- Feature 4 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <h3>Daily Spending Calculator</h3>
                    <p>Advanced algorithms calculate your average daily spending patterns by category and time period, helping you make informed financial decisions.</p>
                </div>
                
                <!-- Feature 5 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3>Budget Analysis</h3>
                    <p>Compare actual spending with budgets, analyze trends, and get insights for better financial planning. Automatic monthly budget resets included.</p>
                </div>
                
                <!-- Feature 6 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <h3>Detailed Reports</h3>
                    <p>Generate comprehensive reports with category breakdowns, spending trends, and monthly comparisons through interactive charts and graphs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section id="cta" class="cta-section">
        <div class="container">
            <div class="cta-content">
                <div class="cta-text">
                    <h2>Take Control of Your Finances Today!</h2>
                    <p>Sign up for Gastos Guard and easily track expenses, set budgets, and achieve your financial goals.</p>
                
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Track all your expenses in one place</li>
                        <li><i class="fas fa-check"></i> Set and maintain budgets easily</li>
                        <li><i class="fas fa-check"></i> Visualize your spending with detailed analytics</li>
                        <li><i class="fas fa-check"></i> Receive smart alerts and notifications</li>
                        <li><i class="fas fa-check"></i> Secure and private financial management</li>
                    </ul>
                </div>
                
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary">Sign Up Now <i class="fas fa-arrow-right"></i></a>
                    <a href="login.php" class="btn btn-secondary">Login to Your Account</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> All rights reserved. Gastos Guard</p>
        </div>
    </footer>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            
            // Close mobile menu if open
            if (mainNav.classList.contains('active')) {
                mobileMenuToggle.classList.remove('active');
                mainNav.classList.remove('active');
            }
            
            // For home link (#), scroll to top
            if (targetId === '#') {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                
                // Explicitly set active class for home link on click
                navLinks.forEach(link => link.classList.remove('active'));
                this.classList.add('active');
                return;
            }
            
            // For other links
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                // Scroll to target
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
                
                // Set active class when clicking other navigation items
                navLinks.forEach(link => link.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Hero section slideshow
    const heroSlides = document.querySelectorAll('.slideshow-background .slide');
    let heroCurrentSlide = 0;
    
    function nextHeroSlide() {
        heroSlides[heroCurrentSlide].classList.remove('active');
        heroCurrentSlide = (heroCurrentSlide + 1) % heroSlides.length;
        heroSlides[heroCurrentSlide].classList.add('active');
    }
    
    setInterval(nextHeroSlide, 3000);
    
    // Features section slideshow
    const featureSlides = document.querySelectorAll('.features-bg .slide');
    let featureCurrentSlide = 0;
    
    function nextFeatureSlide() {
        featureSlides[featureCurrentSlide].classList.remove('active');
        featureCurrentSlide = (featureCurrentSlide + 1) % featureSlides.length;
        featureSlides[featureCurrentSlide].classList.add('active');
    }
    
    if (featureSlides.length > 0) {
        featureSlides.forEach((slide, index) => {
            if (index === 0) {
                slide.classList.add('active');
            } else {
                slide.classList.remove('active');
            }
        });
        
        setTimeout(() => {
            setInterval(nextFeatureSlide, 3500);
        }, 500);
    }

    // Get all sections and navigation links
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('.main-nav ul li a');
    const homeLink = document.querySelector('.main-nav ul li a[href="#"]');
    
    // Ensure home link is active by default on page load
    if (homeLink) {
        navLinks.forEach(link => link.classList.remove('active'));
        homeLink.classList.add('active');
    }

    // Scroll-based active navigation
    function updateActiveNav() {
        // Get the current scroll position
        const scrollPosition = window.scrollY;
        
        // Set initial current section to home
        let currentSection = 'home';
        
        // Determine which section we're in based on scroll position
        // First check if we're below the hero section (which is the home area)
        const heroSection = document.querySelector('.hero-section');
        const heroSectionBottom = heroSection ? heroSection.offsetTop + heroSection.offsetHeight - 100 : 700;
        
        if (scrollPosition >= heroSectionBottom) {
            // We're below the hero section, so let's check other sections
            currentSection = ''; // Reset current section
            
            // Check each section after the hero
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100; // Adjust for header
                const sectionBottom = sectionTop + section.offsetHeight;
                
                // If we're within this section's boundaries
                if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                    currentSection = section.getAttribute('id');
                }
            });
            
            // Special case for bottom of page (CTA section)
            if (scrollPosition + window.innerHeight >= document.documentElement.scrollHeight - 50) {
                currentSection = 'cta';
            }
        }
        
        // Update active class on navigation links
        navLinks.forEach(link => {
            link.classList.remove('active');
            
            // Handle home link
            if (link.getAttribute('href') === '#' && currentSection === 'home') {
                link.classList.add('active');
            }
            // Handle other section links
            else if (link.getAttribute('href') !== '#') {
                const href = link.getAttribute('href').substring(1);
                if (href === currentSection) {
                    link.classList.add('active');
                }
            }
        });
    }
    
    // Update on scroll
    window.addEventListener('scroll', updateActiveNav);
    
    // Initial call to set correct state
    updateActiveNav();
});
</script>
</body>
</html>