<?php
// index.php - Landing Page with Animations
session_start();
require_once 'includes/config.php';

// Get some statistics for the landing page
$total_employees = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
$total_trainings = $conn->query("SELECT COUNT(*) as count FROM training_programs")->fetch_assoc()['count'] ?? 0;
$total_certifications = $conn->query("SELECT COUNT(*) as count FROM certifications")->fetch_assoc()['count'] ?? 0;
$total_enrollments = $conn->query("SELECT COUNT(*) as count FROM employee_trainings")->fetch_assoc()['count'] ?? 0;

// Get training types for display
$training_types = $conn->query("SELECT DISTINCT type FROM training_programs LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Staff Training &amp; Development</title>
    <meta name="description" content="THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE Staff Training & Development Tracking System. Manage employee training, certifications, and development plans efficiently.">
    <meta name="keywords" content="staff training, employee development, polytechnic ibadan, skill development, training management">
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #48bb78;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --info: #36b9cc;
            --dark: #1a1a2e;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-dark: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
            color: #2d3748;
        }
        
        /* ===== ANIMATIONS ===== */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(2deg); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.3); }
            50% { box-shadow: 0 0 40px rgba(102, 126, 234, 0.6); }
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        /* ===== NAVIGATION ===== */
        .navbar {
            padding: 1rem 0;
            transition: all 0.4s ease;
            background: transparent;
        }
        
        .navbar.scrolled {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        .navbar-brand i {
            font-size: 2rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-link {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8) !important;
            margin: 0 8px;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--gradient);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 60%;
        }
        
        .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .btn-nav-login {
            background: var(--gradient);
            color: white !important;
            padding: 8px 25px !important;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-nav-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
            color: white !important;
        }
        
        /* ===== HERO SECTION ===== */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: var(--gradient-dark);
            position: relative;
            overflow: hidden;
            padding: 120px 0 80px;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.15) 0%, transparent 70%);
            animation: float 15s infinite ease-in-out;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(118, 75, 162, 0.15) 0%, transparent 70%);
            animation: float-slow 20s infinite ease-in-out;
        }
        
        .hero-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .hero-particles .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(102, 126, 234, 0.5);
            border-radius: 50%;
            animation: float 8s infinite ease-in-out;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-badge {
            display: inline-block;
            background: rgba(102, 126, 234, 0.15);
            color: #a3b1ff;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 25px;
            border: 1px solid rgba(102, 126, 234, 0.3);
            animation: slideInLeft 1s ease forwards;
        }
        
        .hero-badge i {
            margin-right: 8px;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 1.5rem;
            color: white;
            animation: slideInLeft 1s ease 0.2s forwards;
            opacity: 0;
        }
        
        .hero h1 .highlight {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }
        
        .hero p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2.5rem;
            max-width: 550px;
            animation: slideInLeft 1s ease 0.4s forwards;
            opacity: 0;
        }
        
        .hero-buttons {
            animation: slideInLeft 1s ease 0.6s forwards;
            opacity: 0;
        }
        
        .btn-hero {
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-hero-primary {
            background: var(--gradient);
            color: white;
            border: none;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-hero-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
            color: white;
        }
        
        .btn-hero-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-hero-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            color: white;
            transform: translateY(-3px);
        }
        
        .hero-image-wrapper {
            position: relative;
            z-index: 2;
            animation: slideInRight 1s ease 0.4s forwards;
            opacity: 0;
        }
        
        .hero-image-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            animation: float 6s infinite ease-in-out;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        }
        
        .hero-image-card .card-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 20px;
        }
        
        .hero-image-card h4 {
            color: white;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .hero-image-card p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin: 0;
        }
        
        .floating-stat {
            position: absolute;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: float 5s infinite ease-in-out;
            z-index: 3;
        }
        
        .floating-stat:nth-child(2) {
            top: 5%;
            right: -10%;
            animation-delay: 1s;
        }
        
        .floating-stat:nth-child(3) {
            bottom: 10%;
            left: -15%;
            animation-delay: 2s;
        }
        
        .floating-stat .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        
        .floating-stat .stat-icon.blue {
            background: rgba(102, 126, 234, 0.15);
            color: #667eea;
        }
        
        .floating-stat .stat-icon.green {
            background: rgba(72, 187, 120, 0.15);
            color: #48bb78;
        }
        
        .floating-stat .stat-info h6 {
            font-weight: 800;
            font-size: 1.3rem;
            margin: 0;
            color: #2d3748;
        }
        
        .floating-stat .stat-info small {
            color: #6c757d;
            font-size: 0.75rem;
        }
        
        /* ===== STATS SECTION ===== */
        .stats-section {
            padding: 80px 0;
            background: white;
            position: relative;
        }
        
        .stat-card {
            text-align: center;
            padding: 40px 25px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card .stat-icon-wrapper {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 20px;
            transition: all 0.4s ease;
        }
        
        .stat-card:hover .stat-icon-wrapper {
            transform: scale(1.1) rotate(5deg);
        }
        
        .stat-card .stat-icon-wrapper.purple {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .stat-card .stat-icon-wrapper.green {
            background: rgba(72, 187, 120, 0.1);
            color: #48bb78;
        }
        
        .stat-card .stat-icon-wrapper.orange {
            background: rgba(246, 194, 62, 0.1);
            color: #f6c23e;
        }
        
        .stat-card .stat-icon-wrapper.blue {
            background: rgba(54, 185, 204, 0.1);
            color: #36b9cc;
        }
        
        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        /* ===== FEATURES SECTION ===== */
        .features-section {
            padding: 100px 0;
            background: #f8f9fc;
        }
        
        .section-badge {
            display: inline-block;
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
            padding: 6px 18px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 900;
            color: #1a1a2e;
            margin-bottom: 15px;
        }
        
        .section-title p {
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
            font-size: 1.1rem;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 0;
            background: var(--gradient);
            transition: height 0.4s ease;
            z-index: 0;
        }
        
        .feature-card:hover::after {
            height: 5px;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(102, 126, 234, 0.15);
        }
        
        .feature-card .icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            background: var(--gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            transition: all 0.4s ease;
            position: relative;
            z-index: 1;
        }
        
        .feature-card:hover .icon-wrapper {
            transform: scale(1.1) rotate(-5deg);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }
        
        .feature-card h5 {
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .feature-card p {
            color: #6c757d;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
            margin: 0;
        }
        
        /* ===== HOW IT WORKS ===== */
        .how-it-works {
            padding: 100px 0;
            background: white;
        }
        
        .step-card {
            text-align: center;
            padding: 30px;
            position: relative;
            transition: all 0.4s ease;
        }
        
        .step-card:hover {
            transform: translateY(-5px);
        }
        
        .step-number {
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 900;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transition: all 0.4s ease;
            position: relative;
            z-index: 2;
        }
        
        .step-card:hover .step-number {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }
        
        .step-card h5 {
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        
        .step-card p {
            color: #6c757d;
            font-size: 0.95rem;
            margin: 0;
        }
        
        .step-connector {
            position: absolute;
            top: 40px;
            right: -30%;
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), transparent);
            opacity: 0.3;
        }
        
        /* ===== TRAINING TYPES ===== */
        .training-types {
            padding: 100px 0;
            background: #f8f9fc;
        }
        
        .type-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }
        
        .type-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }
        
        .type-card .type-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
            transition: all 0.4s ease;
        }
        
        .type-card:hover .type-icon {
            transform: scale(1.1) rotate(10deg);
        }
        
        .type-card h6 {
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .type-card p {
            color: #6c757d;
            font-size: 0.85rem;
            margin: 0;
        }
        
        /* ===== CTA SECTION ===== */
        .cta-section {
            padding: 100px 0;
            background: var(--gradient-dark);
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
            animation: float 15s infinite ease-in-out;
        }
        
        .cta-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(118, 75, 162, 0.2) 0%, transparent 70%);
            animation: float-slow 20s infinite ease-in-out;
        }
        
        .cta-content {
            position: relative;
            z-index: 2;
        }
        
        .cta-section h2 {
            font-size: 2.8rem;
            font-weight: 900;
            color: white;
            margin-bottom: 20px;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 35px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn-cta {
            padding: 16px 45px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            background: white;
            color: var(--primary);
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .btn-cta:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            color: var(--primary);
        }
        
        /* ===== FOOTER ===== */
        .footer {
            background: #0f0f1a;
            color: white;
            padding: 80px 0 30px;
            position: relative;
        }
        
        .footer h5 {
            font-weight: 800;
            margin-bottom: 25px;
            color: white;
            font-size: 1.1rem;
        }
        
        .footer h5 i {
            color: var(--primary);
            margin-right: 8px;
        }
        
        .footer p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.95rem;
            line-height: 1.8;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer-links a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .footer-links a i {
            font-size: 0.7rem;
            color: var(--primary);
        }
        
        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        .social-links a {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .social-links a:hover {
            background: var(--gradient);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .footer-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 50px;
            padding-top: 30px;
        }
        
        .footer-bottom {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.9rem;
        }
        
        /* ===== SCROLL TO TOP ===== */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 55px;
            height: 55px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.3rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-image-wrapper {
                margin-top: 50px;
            }
            
            .floating-stat {
                display: none;
            }
            
            .step-connector {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .hero {
                padding: 100px 0 60px;
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
            
            .cta-section h2 {
                font-size: 2rem;
            }
            
            .stat-card .stat-number {
                font-size: 2rem;
            }
            
            .btn-hero {
                padding: 12px 30px;
                font-size: 0.95rem;
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .hero h1 {
                font-size: 1.7rem;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .navbar-brand i {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 25px 20px;
            }
            
            .feature-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<!-- ===== NAVIGATION ===== -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-graduation-cap"></i> 
            <span>PolyIbadan SDC</span>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto align-items-center">
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
                    <a class="nav-link" href="#training-types">Training Types</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="btn btn-nav-login" href="<?php echo ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager') ? 'admin/dashboard.php' : 'employee/dashboard.php'; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-nav-login" href="login.php">
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
    <div class="hero-particles" id="particles"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <div class="hero-badge">
                    <i class="fas fa-university"></i> THE POLYTECHNIC, IBADAN
                </div>
                <h1>
                    Skill Development <br>
                    <span class="highlight">Training &amp; Tracking</span>
                </h1>
                <p>
                    Streamline staff training, track certifications, and manage development plans
                    at THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE.
                </p>
                <div class="hero-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager') ? 'admin/dashboard.php' : 'employee/dashboard.php'; ?>" 
                           class="btn btn-hero btn-hero-primary">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-hero btn-hero-primary">
                            <i class="fas fa-sign-in-alt"></i> Get Started
                        </a>
                        <a href="#features" class="btn btn-hero btn-hero-outline">
                            <i class="fas fa-play"></i> Learn More
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 hero-image-wrapper">
                <div class="hero-image-card">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Track Your Progress</h4>
                    <p>Monitor training completion, certifications, and skill development in real-time with our comprehensive tracking system.</p>
                    
                    <div class="row mt-4">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 style="color: #667eea; font-weight: 800;"><?php echo number_format($total_trainings); ?>+</h3>
                                <small style="color: rgba(255,255,255,0.5);">Trainings</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 style="color: #48bb78; font-weight: 800;"><?php echo number_format($total_certifications); ?>+</h3>
                                <small style="color: rgba(255,255,255,0.5);">Certificates</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="floating-stat">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h6><?php echo number_format($total_employees); ?>+</h6>
                        <small>Employees</small>
                    </div>
                </div>
                
                <div class="floating-stat">
                    <div class="stat-icon green">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-info">
                        <h6><?php echo number_format($total_enrollments); ?>+</h6>
                        <small>Enrollments</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== STATS SECTION ===== -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="0">
                <div class="stat-card">
                    <div class="stat-icon-wrapper purple">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number" data-count="<?php echo $total_employees; ?>">0</div>
                    <div class="stat-label">Active Employees</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="stat-icon-wrapper green">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-number" data-count="<?php echo $total_trainings; ?>">0</div>
                    <div class="stat-label">Training Programs</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="stat-icon-wrapper orange">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="stat-number" data-count="<?php echo $total_certifications; ?>">0</div>
                    <div class="stat-label">Certifications</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="stat-icon-wrapper blue">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-number" data-count="<?php echo $total_enrollments; ?>">0</div>
                    <div class="stat-label">Total Enrollments</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FEATURES SECTION ===== -->
<section class="features-section" id="features">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <div class="section-badge">Features</div>
            <h2>Why Choose Our System?</h2>
            <p>Comprehensive features designed to streamline your staff training and development process.</p>
        </div>
        <div class="row">
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="0">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h5>Employee Management</h5>
                    <p>Complete employee profiles with training history, certifications, and development tracking.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5>Training Scheduling</h5>
                    <p>Easy training program creation, scheduling, and management with calendar integration.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Progress Tracking</h5>
                    <p>Real-time progress monitoring with detailed analytics and performance reports.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h5>Certification Management</h5>
                    <p>Track and manage employee certifications with expiry alerts and renewal reminders.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h5>Development Plans</h5>
                    <p>Create personalized development plans with goals, tasks, and progress tracking.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h5>Payment Integration</h5>
                    <p>Secure payment processing for paid training programs via Paystack.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="how-it-works" id="how-it-works">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <div class="section-badge">Process</div>
            <h2>How It Works</h2>
            <p>Simple steps to get started with our training management system.</p>
        </div>
        <div class="row">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5>Create Account</h5>
                    <p>Sign up as an employee or get registered by admin to access the system.</p>
                    <div class="step-connector d-none d-md-block"></div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5>Browse Trainings</h5>
                    <p>Explore available training programs and apply for those that match your career goals.</p>
                    <div class="step-connector d-none d-md-block"></div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5>Track &amp; Achieve</h5>
                    <p>Complete trainings, earn certifications, and track your professional development.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== TRAINING TYPES ===== -->
<section class="training-types" id="training-types">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <div class="section-badge">Categories</div>
            <h2>Training Types</h2>
            <p>We offer a wide range of training programs to meet your professional development needs.</p>
        </div>
        <div class="row">
            <div class="col-6 col-md-4 col-lg mb-4" data-aos="fade-up" data-aos-delay="0">
                <div class="type-card">
                    <div class="type-icon" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">
                        <i class="fas fa-code"></i>
                    </div>
                    <h6>Technical</h6>
                    <p>Programming, IT, engineering skills</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="type-card">
                    <div class="type-icon" style="background: rgba(72, 187, 120, 0.1); color: #48bb78;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h6>Soft Skills</h6>
                    <p>Communication, teamwork, leadership</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="type-card">
                    <div class="type-icon" style="background: rgba(54, 185, 204, 0.1); color: #36b9cc;">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h6>Management</h6>
                    <p>Project management, strategy</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="type-card">
                    <div class="type-icon" style="background: rgba(246, 194, 62, 0.1); color: #f6c23e;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h6>Compliance</h6>
                    <p>Regulatory, safety, ethics</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg mb-4" data-aos="fade-up" data-aos-delay="400">
                <div class="type-card">
                    <div class="type-icon" style="background: rgba(118, 75, 162, 0.1); color: #764ba2;">
                        <i class="fas fa-book"></i>
                    </div>
                    <h6>Other</h6>
                    <p>Specialized training programs</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section class="cta-section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8 cta-content" data-aos="zoom-in">
                <h2>Ready to Transform Your Training?</h2>
                <p>
                    Join THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE's training management system 
                    and take your professional development to the next level.
                </p>
                <div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager') ? 'admin/dashboard.php' : 'employee/dashboard.php'; ?>" 
                           class="btn btn-cta">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-cta">
                            <i class="fas fa-sign-in-alt"></i> Get Started Now
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
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="0">
                <h5>
                    <i class="fas fa-graduation-cap"></i> 
                    PolyIbadan SDC
                </h5>
                <p>
                    THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE<br>
                    Staff Training &amp; Development Tracking System
                </p>
                <div class="social-links">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="#features"><i class="fas fa-chevron-right"></i> Features</a></li>
                    <li><a href="#how-it-works"><i class="fas fa-chevron-right"></i> How It Works</a></li>
                    <li><a href="#training-types"><i class="fas fa-chevron-right"></i> Training Types</a></li>
                </ul>
            </div>
            <div class="col-md-3 col-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                <h5>Training</h5>
                <ul class="footer-links">
                    <li><a href="login.php"><i class="fas fa-chevron-right"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-chevron-right"></i> Register</a></li>
                    <li><a href="forgot-password.php"><i class="fas fa-chevron-right"></i> Forgot Password</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="300">
                <h5>Contact</h5>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> The Polytechnic, Ibadan, Oyo State</a></li>
                    <li><a href="#"><i class="fas fa-envelope"></i> sdc@polyibadan.edu.ng</a></li>
                    <li><a href="#"><i class="fas fa-phone"></i> +234 (0) 800 000 0000</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-divider">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="footer-bottom">
                        &copy; <?php echo date('Y'); ?> THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE. All rights reserved.
                    </div>
                </div>
                <div class="col-md-6 text-md-right">
                    <div class="footer-bottom">
                        Staff Training &amp; Development Tracking System v1.0
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- ===== SCROLL TO TOP ===== -->
<button class="scroll-top" id="scrollTop">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- ===== SCRIPTS ===== -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<script>
$(document).ready(function() {
    'use strict';
    
    // ===== INITIALIZE AOS =====
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });
    
    // ===== NAVBAR SCROLL EFFECT =====
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('#mainNav').addClass('scrolled');
            $('#scrollTop').addClass('visible');
        } else {
            $('#mainNav').removeClass('scrolled');
            $('#scrollTop').removeClass('visible');
        }
    });
    
    // ===== SMOOTH SCROLL =====
    $('a[href*="#"]').on('click', function(e) {
        if (this.hash !== '') {
            e.preventDefault();
            const hash = this.hash;
            if ($(hash).length) {
                $('html, body').animate({
                    scrollTop: $(hash).offset().top - 70
                }, 800, 'swing');
            }
        }
    });
    
    // ===== SCROLL TO TOP =====
    $('#scrollTop').click(function() {
        $('html, body').animate({ scrollTop: 0 }, 500);
    });
    
    // ===== ACTIVE NAV LINK =====
    $(window).scroll(function() {
        const scrollPos = $(window).scrollTop() + 100;
        
        $('section').each(function() {
            const top = $(this).offset().top;
            const bottom = top + $(this).outerHeight();
            
            if (scrollPos >= top && scrollPos < bottom) {
                const id = $(this).attr('id');
                if (id) {
                    $('.nav-link').removeClass('active');
                    $('.nav-link[href="#' + id + '"]').addClass('active');
                }
            }
        });
    });
    
    // ===== COUNTER ANIMATION =====
    function animateCounters() {
        $('.stat-number').each(function() {
            const $this = $(this);
            const target = parseInt($this.data('count')) || 0;
            const duration = 2000;
            const stepTime = 20;
            const steps = duration / stepTime;
            const increment = target / steps;
            let current = 0;
            
            const counter = setInterval(function() {
                current += increment;
                if (current >= target) {
                    $this.text(target);
                    clearInterval(counter);
                } else {
                    $this.text(Math.floor(current));
                }
            }, stepTime);
        });
    }
    
    // ===== TRIGGER COUNTERS WHEN VISIBLE =====
    let counterTriggered = false;
    
    function checkCounterTrigger() {
        const statsSection = $('.stats-section');
        if (statsSection.length) {
            const offset = statsSection.offset().top;
            const windowHeight = $(window).height();
            const scrollPos = $(window).scrollTop();
            
            if (!counterTriggered && scrollPos + windowHeight > offset + 100) {
                counterTriggered = true;
                animateCounters();
            }
        }
    }
    
    $(window).scroll(checkCounterTrigger);
    $(document).ready(checkCounterTrigger);
    
    // ===== CREATE PARTICLES =====
    function createParticles() {
        const particlesContainer = document.getElementById('particles');
        if (!particlesContainer) return;
        
        const particleCount = 30;
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 8 + 's';
            particle.style.animationDuration = (5 + Math.random() * 5) + 's';
            particle.style.opacity = 0.2 + Math.random() * 0.5;
            particle.style.width = (2 + Math.random() * 4) + 'px';
            particle.style.height = particle.style.width;
            particlesContainer.appendChild(particle);
        }
    }
    
    createParticles();
    
    // ===== MOBILE MENU =====
    $('.navbar-toggler').click(function() {
        $(this).toggleClass('active');
    });
    
    // ===== TYPING EFFECT =====
    function typeWriter(element, text, speed, callback) {
        let i = 0;
        element.textContent = '';
        
        function type() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            } else if (callback) {
                callback();
            }
        }
        
        type();
    }
    
    // ===== PARALLAX EFFECT =====
    $(window).scroll(function() {
        const scrolled = $(this).scrollTop();
        $('.hero::before').css('transform', 'translateY(' + (scrolled * 0.3) + 'px)');
    });
    
    console.log('Landing page loaded successfully!');
});
</script>

<!-- ===== AOS INITIALIZATION ===== -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof AOS !== 'undefined') {
            AOS.init();
        }
    });
</script>

</body>
</html>