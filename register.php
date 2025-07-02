<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
// Redirect logged-in users away from registration page
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables
$errors = [];
$username = $email = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $terms = isset($_POST['terms']);

    // Validate inputs
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors['username'] = 'Username must be 3-20 characters (letters, numbers, underscores)';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Passwords do not match';
    }

    if (!$terms) {
        $errors['terms'] = 'You must accept the terms and conditions';
    }

    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors['general'] = 'Username or email already exists';
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate verification token
            $verification_token = bin2hex(random_bytes(32));
            $verification_expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert into local database
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, verification_token, verification_expires, created_at) 
                                  VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $password_hash, $verification_token, $verification_expires]);
            $user_id = $pdo->lastInsertId();
            
            // Integrate with A-Member Pro
            if (defined('AMEMBER_API_URL') && defined('AMEMBER_API_KEY')) {
                $amember_data = [
                    'login' => $username,
                    'pass' => $password,
                    'email' => $email,
                    'name' => $username,
                    'user_ip' => $_SERVER['REMOTE_ADDR']
                ];
                
                $ch = curl_init(AMEMBER_API_URL . '/users');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => array_merge($amember_data, ['_key' => AMEMBER_API_KEY]),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
                ]);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code !== 200) {
                    throw new Exception("A-Member integration failed: HTTP $http_code");
                }
                
                // Update local record with aMember ID
                $response_data = json_decode($response, true);
                if (isset($response_data['user_id'])) {
                    $stmt = $pdo->prepare("UPDATE users SET amember_id = ? WHERE id = ?");
                    $stmt->execute([$response_data['user_id'], $user_id]);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Send verification email
            $verification_link = "https://" . MAIN_DOMAIN . "/verify.php?token=" . urlencode($verification_token);
                // $verification_link = "betfastwallet.com/verify.php?token=" . urlencode($verification_token);
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'asadullah03189051077@gmail.com';
                    $mail->Password = 'ztbyxmsjxzadisjt';
                    // $mail->Encryption = 'tls';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                
                    $mail->setFrom('no-reply@' . MAIN_DOMAIN, 'Your Site');
                    $mail->addAddress($email, $username);
                    $mail->Subject = 'Verify Your Account';
                    $mail->Body = "Hello $username,\n\nPlease click this link: $verification_link";
                
                    $mail->send();
                } catch (Exception $e) {
                    throw new Exception("Mailer Error: " . $mail->ErrorInfo);
                }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SEO Tools GroupBuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .registration-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .terms-text {
            font-size: 0.9rem;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <h2 class="text-center mb-4">Create Your Account</h2>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="registrationForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                           id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['username']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                           id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                           id="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                    <small class="form-text text-muted">Minimum 8 characters</small>
                </div>
                
                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                           id="password_confirm" name="password_confirm" required>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input <?= isset($errors['terms']) ? 'is-invalid' : '' ?>" 
                           id="terms" name="terms" required>
                    <label class="form-check-label terms-text" for="terms">
                        I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and 
                        <a href="privacy.php" target="_blank">Privacy Policy</a>
                    </label>
                    <?php if (isset($errors['terms'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['terms']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Register</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Log in here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            let valid = true;
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                alert('Passwords do not match');
                valid = false;
            }
            
            if (!document.getElementById('terms').checked) {
                alert('You must accept the terms and conditions');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const strengthIndicator = document.getElementById('password-strength');
            if (!strengthIndicator) return;
            
            const strength = calculatePasswordStrength(this.value);
            strengthIndicator.textContent = 'Strength: ' + strength.label;
            strengthIndicator.style.color = strength.color;
        });
        
        function calculatePasswordStrength(password) {
            let strength = 0;
            
            // Length contributes up to 50% of the score
            strength += Math.min(5, password.length / 2);
            
            // Contains both lower and upper case
            if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 2;
            
            // Contains numbers
            if (password.match(/[0-9]/)) strength += 2;
            
            // Contains special characters
            if (password.match(/[^a-zA-Z0-9]/)) strength += 2;
            
            if (strength < 4) return { label: 'Weak', color: '#dc3545' };
            if (strength < 7) return { label: 'Medium', color: '#fd7e14' };
            return { label: 'Strong', color: '#28a745' };
        }
    </script>
</body>
</html>