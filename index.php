<?php
// index.php - Landing Page
session_start();
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Staff Training & Development Tracking System</title>
    
    <!-- Meta Tags for SEO -->
    <meta name="description" content="Staff Training and Development Tracking System - Manage employee training, certifications, and development plans efficiently.">
    <meta name="keywords" content="staff training, employee development, training management, certification tracking">
    <meta name="author" content="Staff Training System">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo SITE_NAME; ?>">
    <meta property="og:description" content="Complete training and development management system for organizations.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        /* ===== Global Styles ===== */
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #48bb78;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-hover: linear-gradient(135deg, #5a67d8 0%, #6b3f9e 100%);
            --light-bg: #f7f9fc;
            --dark-text: #2d3748;
            --gray-text: #718096;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            overflow-x: hidden;
            background: #ffffff;
        }
        
        /* ===== Preloader ===== */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ffffff;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.8s ease;
        }
        
        #preloader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .loader {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ===== Navigation ===== */
        .navbar {
            padding: 1rem 0;
            transition: all 0.3s ease;
            background: transparent;
        }
        
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
            backdrop-filter: blur(10px);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary) !important;
        }
        
        .navbar-brand i {
            margin-right: 8px;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-link {
            font-weight: 600;
            color: var(--dark-text) !important;
            margin: 0 10px;
            transition: color 0.3s ease;
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gradient);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }
        
        .nav-link:hover {
            color: var(--primary) !important;
        }
        
        .btn-gradient {
            background: var(--gradient);
            color: white !important;
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-gradient:hover {
            background: var(--gradient-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: white !important;
        }
        
        .btn-outline-gradient {
            background: transparent;
            color: var(--primary) !important;
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 600;
            border: 2px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .btn-outline-gradient:hover {
            background: var(--gradient);
            color: white !important;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        /* ===== Hero Section ===== */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
            overflow: hidden;
            padding: 100px 0 80px;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.1);
            animation: float 20s infinite ease-in-out;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(118, 75, 162, 0.08);
            animation: float 15s infinite ease-in-out reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.1); }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        
        .hero h1 .highlight {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 1.2rem;
            color: var(--gray-text);
            margin-bottom: 2rem;
            max-width: 500px;
        }
        
        .hero-badge {
            display: inline-block;
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .hero-badge i {
            margin-right: 8px;
        }
        
        .hero-image {
            position: relative;
            z-index: 1;
            animation: float 6s infinite ease-in-out;
        }
        
        .hero-image img {
            max-width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .floating-card {
            position: absolute;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: float 4s infinite ease-in-out;
        }
        
        .floating-card:nth-child(1) {
            top: 10%;
            right: -10%;
            animation-delay: 0s;
        }
        
        .floating-card:nth-child(2) {
            bottom: 20%;
            left: -5%;
            animation-delay: 1s;
        }
        
        .floating-card i {
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        /* ===== Stats Section ===== */
        .stats-section {
            padding: 80px 0;
            background: var(--light-bg);
        }
        
        .stat-card {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .number {
            font-size: 3rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card .label {
            color: var(--gray-text);
            font-weight: 600;
            margin-top: 5px;
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        /* ===== Features Section ===== */
        .features-section {
            padding: 100px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .section-title p {
            color: var(--gray-text);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }
        
        .feature-card .icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .icon-wrapper {
            transform: scale(1.1) rotate(5deg);
        }
        
        .feature-card h5 {
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: var(--gray-text);
            font-size: 0.95rem;
        }
        
        /* ===== How It Works ===== */
        .how-it-works {
            padding: 100px 0;
            background: var(--light-bg);
        }
        
        .step-card {
            text-align: center;
            padding: 30px;
            position: relative;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .step-card h5 {
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .step-card p {
            color: var(--gray-text);
            font-size: 0.95rem;
        }
        
        .step-connector {
            position: absolute;
            top: 45px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: var(--primary);
            opacity: 0.3;
        }
        
        /* ===== Testimonials ===== */
        .testimonials {
            padding: 100px 0;
        }
        
        .testimonial-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin: 15px;
        }
        
        .testimonial-card .stars {
            color: #f6c23e;
            margin-bottom: 15px;
        }
        
        .testimonial-card .quote {
            font-size: 1rem;
            color: var(--dark-text);
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .testimonial-card .author {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .testimonial-card .author img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .testimonial-card .author .name {
            font-weight: 700;
            margin: 0;
        }
        
        .testimonial-card .author .role {
            color: var(--gray-text);
            font-size: 0.85rem;
            margin: 0;
        }
        
        /* ===== CTA Section ===== */
        .cta-section {
            padding: 100px 0;
            background: var(--gradient);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite ease-in-out;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .btn-cta {
            background: white;
            color: var(--primary) !important;
            padding: 15px 50px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            display: inline-block;
        }
        
        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            color: var(--primary) !important;
        }
        
        .btn-cta-outline {
            background: transparent;
            color: white !important;
            padding: 15px 50px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: 2px solid white;
            display: inline-block;
            margin-left: 15px;
        }
        
        .btn-cta-outline:hover {
            background: white;
            color: var(--primary) !important;
            transform: translateY(-3px);
        }
        
        /* ===== Footer ===== */
        .footer {
            background: #1a1a2e;
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer h5 {
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .footer .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .footer .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }
        
        /* ===== Responsive ===== */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-image {
                margin-top: 50px;
            }
            
            .floating-card {
                display: none;
            }
            
            .step-connector {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .hero {
                padding: 80px 0 60px;
                min-height: auto;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .stat-card .number {
                font-size: 2rem;
            }
            
            .cta-section h2 {
                font-size: 2rem;
            }
            
            .btn-cta, .btn-cta-outline {
                padding: 12px 30px;
                font-size: 1rem;
                display: block;
                margin: 10px auto;
            }
            
            .btn-cta-outline {
                margin-left: 0;
            }
        }
        
        @media (max-width: 576px) {
            .hero h1 {
                font-size: 1.8rem;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .feature-card {
                padding: 25px 20px;
            }
        }
        
        /* ===== Scroll to Top ===== */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }
        
        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>

<!-- ===== PRELOADER ===== -->
<div id="preloader">
    <div class="loader"></div>
</div>

<!-- ===== SCROLL TO TOP ===== -->
<button class="scroll-top" id="scrollTop">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- ===== NAVIGATION ===== -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-graduation-cap"></i> StaffTraining
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#how-it-works">How It Works</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#testimonials">Testimonials</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-gradient" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-gradient" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== HERO SECTION ===== -->
<section class="hero" id="home">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <div class="hero-badge animate__animated animate__fadeInUp">
                    <i class="fas fa-rocket"></i> Next-Gen Training Management
                </div>
                <h1 class="animate__animated animate__fadeInUp animate__delay-1s">
                    Transform Your <br>
                    <span class="highlight">Staff Training</span> &amp; <br>
                    Development
                </h1>
                <p class="animate__animated animate__fadeInUp animate__delay-2s">
                    Streamline employee training, track certifications, and manage development plans 
                    all in one powerful platform.
                </p>
                <div class="animate__animated animate__fadeInUp animate__delay-3s">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-gradient btn-lg mr-3">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-gradient btn-lg mr-3">
                            <i class="fas fa-sign-in-alt"></i> Get Started
                        </a>
                        <a href="#features" class="btn btn-outline-gradient btn-lg">
                            <i class="fas fa-play"></i> Learn More
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 hero-image">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/img/bootstrap-stack.png" 
                     alt="Training System" 
                     class="img-fluid animate__animated animate__fadeInRight animate__delay-1s"
                     style="max-width: 80%; display: block; margin: 0 auto;">
                
                <!-- Floating Cards -->
                <div class="floating-card animate__animated animate__fadeInUp animate__delay-2s">
                    <i class="fas fa-users"></i>
                    <div>
                        <small>Active Employees</small>
                        <h6 class="mb-0">1,284</h6>
                    </div>
                </div>
                <div class="floating-card animate__animated animate__fadeInUp animate__delay-3s">
                    <i class="fas fa-certificate"></i>
                    <div>
                        <small>Certifications</small>
                        <h6 class="mb-0">342</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== STATS SECTION ===== -->
<section class="stats-section" id="stats">
    <div class="container">
        <div class="row">
            <div class="col-6 col-lg-3 mb-4">
                <div class="stat-card animate__animated animate__fadeInUp">
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="number" data-count="5000">0</div>
                    <div class="label">Employees Trained</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-4">
                <div class="stat-card animate__animated animate__fadeInUp animate__delay-1s">
                    <div class="icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="number" data-count="250">0</div>
                    <div class="label">Training Programs</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-4">
                <div class="stat-card animate__animated animate__fadeInUp animate__delay-2s">
                    <div class="icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="number" data-count="1200">0</div>
                    <div class="label">Certifications Issued</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-4">
                <div class="stat-card animate__animated animate__fadeInUp animate__delay-3s">
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="number" data-count="98">0</div>
                    <div class="label">Success Rate %</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FEATURES SECTION ===== -->
<section class="features-section" id="features">
    <div class="container">
        <div class="section-title">
            <h2 class="animate__animated animate__fadeInUp">Why Choose Us?</h2>
            <p class="animate__animated animate__fadeInUp animate__delay-1s">
                Comprehensive features designed to streamline your staff training and development process.
            </p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="feature-card animate__animated animate__fadeInUp">
                    <div class="icon-wrapper">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h5>Employee Management</h5>
                    <p>Complete employee profiles with training history, certifications, and development tracking.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card animate__animated animate__fadeInUp animate__delay-1s">
                    <div class="icon-wrapper">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5>Training Scheduling</h5>
                    <p>Easy training program creation, scheduling, and management with calendar integration.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card animate__animated animate__fadeInUp animate__delay-2s">
                    <div class="icon-wrapper">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Progress Tracking</h5>
                    <p>Real-time progress monitoring with detailed analytics and performance reports.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card animate__animated animate__fadeInUp animate__delay-3s">
                    <div class="icon-wrapper">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h5>Certification Management</h5>
                    <p>Track and manage employee certifications with expiry alerts and renewal reminders.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card animate__animated animate__fadeInUp animate__delay-4s">
                    <div class="icon-wrapper">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h5>Development Plans</h5>
                    <p>Create personalized development plans with goals, tasks, and progress tracking.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card animate__animated animate__fadeInUp animate__delay-5s">
                    <div class="icon-wrapper">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h5>Reports &amp; Analytics</h5>
                    <p>Generate comprehensive reports and insights for informed decision making.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="how-it-works" id="how-it-works">
    <div class="container">
        <div class="section-title">
            <h2 class="animate__animated animate__fadeInUp">How It Works</h2>
            <p class="animate__animated animate__fadeInUp animate__delay-1s">
                Simple steps to get started with our training management system.
            </p>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="step-card animate__animated animate__fadeInUp">
                    <div class="step-number">1</div>
                    <h5>Create Account</h5>
                    <p>Sign up as an administrator or employee to access the system.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card animate__animated animate__fadeInUp animate__delay-1s">
                    <div class="step-number">2</div>
                    <h5>Add Training Programs</h5>
                    <p>Create and schedule training programs for your staff members.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card animate__animated animate__fadeInUp animate__delay-2s">
                    <div class="step-number">3</div>
                    <h5>Track &amp; Manage</h5>
                    <p>Monitor progress, issue certifications, and generate reports.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section class="testimonials" id="testimonials">
    <div class="container">
        <div class="section-title">
            <h2 class="animate__animated animate__fadeInUp">What Our Users Say</h2>
            <p class="animate__animated animate__fadeInUp animate__delay-1s">
                Real feedback from organizations using our training management system.
            </p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="testimonial-card animate__animated animate__fadeInUp">
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="quote">
                        "This system has transformed how we manage staff training. The tracking and reporting features are exceptional."
                    </p>
                    <div class="author">
                        <div>
                            <p class="name">Sarah Johnson</p>
                            <p class="role">HR Director, TechCorp</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="testimonial-card animate__animated animate__fadeInUp animate__delay-1s">
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="quote">
                        "The certification tracking feature has been a game-changer for our compliance requirements."
                    </p>
                    <div class="author">
                        <div>
                            <p class="name">Michael Chen</p>
                            <p class="role">Training Manager, GlobalSoft</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="testimonial-card animate__animated animate__fadeInUp animate__delay-2s">
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="quote">
                        "Excellent platform for tracking employee development. The reporting tools provide great insights."
                    </p>
                    <div class="author">
                        <div>
                            <p class="name">Emily Rodriguez</p>
                            <p class="role">Learning &amp; Development Lead</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section class="cta-section" id="cta">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h2 class="animate__animated animate__fadeInUp">
                    Ready to Transform Your Training?
                </h2>
                <p class="animate__animated animate__fadeInUp animate__delay-1s">
                    Join thousands of organizations that trust our system for their staff training and development needs.
                </p>
                <div class="animate__animated animate__fadeInUp animate__delay-2s">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn-cta">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn-cta">
                            <i class="fas fa-sign-in-alt"></i> Get Started Now
                        </a>
                        <a href="#features" class="btn-cta-outline">
                            <i class="fas fa-info-circle"></i> Learn More
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5>
                    <i class="fas fa-graduation-cap" style="color: var(--primary);"></i> 
                    StaffTraining
                </h5>
                <p style="color: rgba(255,255,255,0.7);">
                    Comprehensive staff training and development tracking system designed to help organizations 
                    manage employee learning and growth effectively.
                </p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5>Features</h5>
                <ul class="list-unstyled">
                    <li><a href="#">Employee Management</a></li>
                    <li><a href="#">Training Programs</a></li>
                    <li><a href="#">Certifications</a></li>
                    <li><a href="#">Development Plans</a></li>
                    <li><a href="#">Reports</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5>Contact</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-envelope"></i> support@stafftraining.com</li>
                    <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Training Ave, Suite 100</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- ===== SCRIPTS ===== -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // ===== Preloader =====
    $(window).on('load', function() {
        setTimeout(function() {
            $('#preloader').addClass('hidden');
        }, 500);
    });

    // ===== Navbar Scroll Effect =====
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('#mainNav').addClass('scrolled');
        } else {
            $('#mainNav').removeClass('scrolled');
        }
        
        // Scroll to top button
        if ($(this).scrollTop() > 300) {
            $('#scrollTop').addClass('visible');
        } else {
            $('#scrollTop').removeClass('visible');
        }
    });

    // ===== Scroll to Top =====
    $('#scrollTop').click(function() {
        $('html, body').animate({ scrollTop: 0 }, 500);
    });

    // ===== Smooth Scroll =====
    $('a[href*="#"]').on('click', function(e) {
        if (this.hash !== '') {
            e.preventDefault();
            const hash = this.hash;
            $('html, body').animate({
                scrollTop: $(hash).offset().top - 70
            }, 800);
        }
    });

    // ===== Counter Animation =====
    function animateCounters() {
        $('.stat-card .number').each(function() {
            const target = parseInt($(this).data('count'));
            const duration = 2000;
            const stepTime = 20;
            const steps = duration / stepTime;
            const increment = target / steps;
            let current = 0;
            
            const counter = setInterval(function() {
                current += increment;
                if (current >= target) {
                    $(this).text(target + (target > 1000 ? '+' : ''));
                    clearInterval(counter);
                } else {
                    $(this).text(Math.floor(current));
                }
            }.bind(this), stepTime);
        });
    }

    // ===== Trigger counters when visible =====
    let counterTriggered = false;
    
    $(window).scroll(function() {
        if (!counterTriggered) {
            const statsSection = $('.stats-section');
            const offset = statsSection.offset().top;
            const windowHeight = $(window).height();
            const scrollPos = $(window).scrollTop();
            
            if (scrollPos + windowHeight > offset + 100) {
                counterTriggered = true;
                animateCounters();
            }
        }
    });

    // ===== Mobile Menu Toggle =====
    $('.navbar-toggler').click(function() {
        $('.navbar-collapse').toggleClass('show');
    });

    // ===== Active Nav Link =====
    $(window).scroll(function() {
        const scrollPos = $(window).scrollTop() + 100;
        
        $('section').each(function() {
            const top = $(this).offset().top;
            const bottom = top + $(this).outerHeight();
            
            if (scrollPos >= top && scrollPos < bottom) {
                const id = $(this).attr('id');
                $('.nav-link').removeClass('active');
                $('.nav-link[href="#' + id + '"]').addClass('active');
            }
        });
    });

    // ===== Trigger initial load =====
    $(document).ready(function() {
        // Check if stats section is visible on load
        const statsSection = $('.stats-section');
        const offset = statsSection.offset().top;
        const windowHeight = $(window).height();
        const scrollPos = $(window).scrollTop();
        
        if (scrollPos + windowHeight > offset + 100) {
            counterTriggered = true;
            animateCounters();
        }
    });
</script>

</body>
</html>