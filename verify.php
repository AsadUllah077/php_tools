<?php
require_once __DIR__ . '/config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['error'] = 'Invalid verification link';
    header('Location: login.php');
    exit;
}

try {
    // Check if token exists and is not expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND verification_expires > NOW()");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = 'Invalid or expired verification link';
        header('Location: login.php');
        exit;
    }
    
    $user = $stmt->fetch();
    
    // Mark user as verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    $_SESSION['success'] = 'Your account has been verified successfully! You can now log in.';
    header('Location: login.php');
    exit;
    
} catch (PDOException $e) {
    error_log("Verification error: " . $e->getMessage());
    $_SESSION['error'] = 'Verification failed. Please try again.';
    header('Location: login.php');
    exit;
}