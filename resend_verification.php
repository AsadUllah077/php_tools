<?php
require_once __DIR__ . '/config.php';

$email = $_GET['email'] ?? '';

if (empty($email)) {
    header('Location: register.php');
    exit;
}

try {
    // Check if user exists and is not verified
    $stmt = $pdo->prepare("SELECT id, username, verification_token FROM users WHERE email = ? AND is_verified = 0");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = 'No pending verification found for this email';
        header('Location: login.php');
        exit;
    }
    
    $user = $stmt->fetch();
    
    // Resend verification email
    $verification_link = "https://" . MAIN_DOMAIN . "/verify.php?token=" . urlencode($user['verification_token']);
    
    $to = $email;
    $subject = "Verify Your Account";
    $message = "Hello {$user['username']},\n\n";
    $message .= "Please click the following link to verify your account:\n";
    $message .= $verification_link . "\n\n";
    $message .= "This link will expire in 1 hour.\n\n";
    $message .= "If you didn't request this, please ignore this email.\n";
    
    $headers = "From: no-reply@" . MAIN_DOMAIN . "\r\n";
    $headers .= "Reply-To: support@" . MAIN_DOMAIN . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    if (mail($to, $subject, $message, $headers)) {
        $_SESSION['success'] = 'Verification email resent successfully!';
    } else {
        $_SESSION['error'] = 'Failed to resend verification email';
    }
    
    header('Location: login.php');
    exit;
    
} catch (PDOException $e) {
    error_log("Resend verification error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to resend verification email';
    header('Location: login.php');
    exit;
}