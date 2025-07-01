<?php
require_once 'config.php';
require_once 'writing_tools.php';
require_once 'security.php';

// Get tool ID from subdomain
$hostParts = explode('.', $_SERVER['HTTP_HOST']);
$toolSubdomain = $hostParts[0];
$toolId = str_replace('tool-', '', $toolSubdomain);

if (!is_numeric($toolId)) {
    http_response_code(400);
    die("Invalid tool access");
}

$writingToolManager = new WritingToolManager($pdo);
$securityManager = new SecurityManager($pdo);
$toolData = $writingToolManager->getToolData($toolId);

if (!$toolData) {
    http_response_code(404);
    die("Tool not found");
}

// Verify access token with enhanced security
$accessToken = $_GET['access_token'] ?? '';
$access = $securityManager->validateSession($toolId, $accessToken);

if (!$access) {
    http_response_code(403);
    die("Access denied or session expired");
}

// Set cookie for this session (HttpOnly, Secure)
setcookie('tool_session', $accessToken, [
    'expires' => time() + COOKIE_EXPIRY,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Generate CSRF token for this session if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proxy the request
$requestUrl = $_SERVER['REQUEST_URI'];
echo $writingToolManager->proxyRequest($toolData, $requestUrl);
?>