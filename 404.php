<?php
// 404.php - Page Not Found
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            text-align: center;
            color: white;
            padding: 40px;
        }
        .error-container .error-code {
            font-size: 8rem;
            font-weight: 800;
            text-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .error-container .error-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.8;
        }
        .error-container h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .error-container p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 500px;
            margin: 0 auto 30px;
        }
        .error-container .btn {
            padding: 12px 40px;
            border-radius: 50px;
            font-weight: 600;
            background: white;
            color: #667eea;
            transition: all 0.3s ease;
        }
        .error-container .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            color: #667eea;
        }
        .error-container .links {
            margin-top: 30px;
        }
        .error-container .links a {
            color: rgba(255,255,255,0.7);
            margin: 0 15px;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .error-container .links a:hover {
            color: white;
        }
        @media (max-width: 576px) {
            .error-container .error-code {
                font-size: 5rem;
            }
            .error-container h2 {
                font-size: 1.8rem;
            }
            .error-container p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code">404</div>
        <h2>Page Not Found</h2>
        <p>Oops! The page you are looking for doesn't exist or has been moved.</p>
        
        <a href="index.php" class="btn">
            <i class="fas fa-home"></i> Go to Homepage
        </a>
        
        <div class="links">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>