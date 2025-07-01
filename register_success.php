<?php
require_once __DIR__ . '/config.php';

// Check if registration was successful
if (!isset($_SESSION['registration_success'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['registered_email'];
unset($_SESSION['registration_success']);
unset($_SESSION['registered_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - SEO Tools GroupBuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 72px;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h2 class="mb-3">Registration Successful!</h2>
            <p class="mb-4">We've sent a verification email to <strong><?= htmlspecialchars($email) ?></strong>.</p>
            <p>Please check your inbox and click the verification link to activate your account.</p>
            <div class="alert alert-info mt-4">
                <p class="mb-0">Didn't receive the email? Check your spam folder or 
                <a href="resend_verification.php?email=<?= urlencode($email) ?>">click here to resend</a>.</p>
            </div>
            <a href="login.php" class="btn btn-primary mt-3">Go to Login Page</a>
        </div>
    </div>
</body>
</html>