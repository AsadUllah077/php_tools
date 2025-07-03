<?php
/**
 * SEO Tools GroupBuy Configuration File
 * Enhanced with security measures and error handling
 */

// === Environment Configuration ===
define('ENVIRONMENT', 'development'); // 'production' or 'development'

// === Database Configuration ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'seo_tools_groupbuy');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// === Security Configuration ===
define('SECRET_KEY', bin2hex(random_bytes(32))); // Dynamically generated for each installation
define('COOKIE_EXPIRY', 7200); // 2 hours in seconds
define('MIN_QUERY_INTERVAL', 3); // Minimum seconds between queries

define('MAX_QUERY_INTERVAL', 5); // Maximum seconds between queries
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// === Session Configuration ===
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable when using HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', COOKIE_EXPIRY);

// === A-Member Pro Integration ===
define('AMEMBER_API_KEY', 'isYjkHXFzvEpmnVmcJAv');
define('AMEMBER_API_URL', 'https://app.toolsworlds.com/api');
define('AMEMBER_WEBHOOK_SECRET', bin2hex(random_bytes(16)));

// === Domain Configuration ===
define('MAIN_DOMAIN', 'toolsworlds.com'); // Remove scheme; keep domain only
define('TOOLS_SUBDOMAIN_PATTERN', 'tool0%d.' . MAIN_DOMAIN);
define('ALLOWED_DOMAINS', serialize([
    'https://' . MAIN_DOMAIN,
    'https://www.' . MAIN_DOMAIN,
    'https://api.' . MAIN_DOMAIN,
]));


// === Watermark Configuration ===
define('WATERMARK_TEXT', 'Shared Account - %username%');
define('WATERMARK_CSS', '
    position: fixed;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    font-size: 14px;
    z-index: 9999;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
');

// === Error Handling ===
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error_' . date('Y-m-d') . '.log');

// === Timezone ===
date_default_timezone_set('UTC');

// === Autoloader ===
spl_autoload_register(function ($className) {
    $file = __DIR__ . '/classes/' . str_replace('\\', '/', $className) . '.php';
    
    if (file_exists($file)) {
        require $file;
    } else {
        error_log("Autoloader: Class $className not found in $file");
        if (ENVIRONMENT === 'development') {
            die("Class $className not found. Check autoload paths.");
        }
    }
});

// === Database Connection ===
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    die("System maintenance in progress. Please try again later.");
}

// === Security Headers ===
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Feature-Policy: geolocation 'none'; microphone 'none'; camera 'none'");

// === CSRF Protection ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === Initialize Custom Error Handler ===
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR             => 'Error',
        E_WARNING           => 'Warning',
        E_PARSE             => 'Parse Error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core Error',
        E_CORE_WARNING      => 'Core Warning',
        E_COMPILE_ERROR     => 'Compile Error',
        E_COMPILE_WARNING   => 'Compile Warning',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED        => 'Deprecated',
        E_USER_DEPRECATED   => 'User Deprecated'
    ];
    
    $errtype = $errorTypes[$errno] ?? 'Unknown Error';
    $message = "$errtype: $errstr in $errfile on line $errline";
    
    error_log($message);
    
    if (ENVIRONMENT === 'development') {
        echo "<div style='background:#f8d7da;padding:15px;border:1px solid #f5c6cb;margin:10px;'>";
        echo "<strong>$errtype:</strong> $errstr<br>";
        echo "<small>File: $errfile (Line: $errline)</small>";
        echo "</div>";
    }
    
    return true;
});

// === Shutdown Function for Fatal Errors ===
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}");
        if (ENVIRONMENT === 'production') {
            http_response_code(500);
            include __DIR__ . '/views/errors/500.php';
        }
    }
});

// === Helper Functions ===
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validate_domain($domain) {
    return in_array($domain, unserialize(ALLOWED_DOMAINS));
}
session_start();
?>