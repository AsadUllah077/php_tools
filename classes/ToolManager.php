<?php
class ToolManager {
    private $pdo;
    private $currentUser;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->currentUser = $this->getCurrentUser();
    }
    
    public function getCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
    
    public function validateAccess($toolId) {
        // Check if user is logged in
        if (!$this->currentUser) {
            return ['success' => false, 'error' => 'Not logged in'];
        }
        
        // Check if user has credits
        if ($this->currentUser['credits'] <= 0) {
            return ['success' => false, 'error' => 'No credits remaining'];
        }
        
        // Check query interval
        $lastRequest = new DateTime($this->currentUser['last_request_time']);
        $now = new DateTime();
        $interval = $now->getTimestamp() - $lastRequest->getTimestamp();
        
        $minInterval = MIN_QUERY_INTERVAL;
        $maxInterval = MAX_QUERY_INTERVAL;
        
        if ($interval < $minInterval) {
            $waitTime = $minInterval - $interval;
            return ['success' => false, 'error' => 'Please wait before making another request', 'wait_time' => $waitTime];
        }
        
        // Check if user already has active access to this tool
        $stmt = $this->pdo->prepare("SELECT * FROM user_tool_access WHERE user_id = ? AND tool_id = ? AND expires_at > NOW()");
        $stmt->execute([$this->currentUser['id'], $toolId]);
        
        if ($stmt->rowCount() > 0) {
            $access = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['success' => true, 'access_token' => $access['access_token']];
        }
        
        // Create new access token
        $accessToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + COOKIE_EXPIRY);
        
        $stmt = $this->pdo->prepare("INSERT INTO user_tool_access (user_id, tool_id, access_token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->currentUser['id'], $toolId, $accessToken, $expiresAt]);
        
        // Deduct credit
        $this->deductCredit();
        
        // Update last request time
        $this->updateLastRequestTime();
        
        return ['success' => true, 'access_token' => $accessToken];
    }
    
    private function deductCredit() {
        $stmt = $this->pdo->prepare("UPDATE users SET credits = credits - 1 WHERE id = ?");
        $stmt->execute([$this->currentUser['id']]);
    }
    
    private function updateLastRequestTime() {
        $stmt = $this->pdo->prepare("UPDATE users SET last_request_time = NOW() WHERE id = ?");
        $stmt->execute([$this->currentUser['id']]);
    }
    
    public function verifyAccessToken($toolId, $accessToken) {
        // Verify token exists and is not expired
        $stmt = $this->pdo->prepare("SELECT u.*, uta.* FROM user_tool_access uta 
                                    JOIN users u ON uta.user_id = u.id 
                                    WHERE uta.tool_id = ? AND uta.access_token = ? AND uta.expires_at > NOW()");
        $stmt->execute([$toolId, $accessToken]);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }
        
        $access = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log this activity
        $this->logActivity($access['user_id'], $toolId);
        
        return $access;
    }
    
    private function logActivity($userId, $toolId) {
        $screenRes = $_POST['screen_res'] ?? 'Unknown';
        $timezone = $_POST['timezone'] ?? 'Unknown';
        
        $stmt = $this->pdo->prepare("INSERT INTO user_activity 
                                    (user_id, tool_id, ip_address, user_agent, screen_resolution, timezone, activity_time) 
                                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $toolId,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'],
            $screenRes,
            $timezone
        ]);
    }
    
    public function getToolData($toolId) {
        $stmt = $this->pdo->prepare("SELECT * FROM tools WHERE id = ?");
        $stmt->execute([$toolId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function generateWatermark($username) {
        $watermark = str_replace('%username%', $username, WATERMARK_TEXT);
        return '<div style="'.WATERMARK_CSS.'">'.$watermark.'</div>';
    }
    
    public function proxyRequest($toolData, $requestUrl) {
        // Initialize cURL
        $ch = curl_init();
        
        // Set target URL
        $targetUrl = $toolData['base_url'] . parse_url($requestUrl, PHP_URL_PATH);
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        
        // Set cookies from JSON data
        $cookies = json_decode($toolData['cookie_data'], true);
        $cookieString = '';
        foreach ($cookies as $name => $value) {
            $cookieString .= "$name=$value; ";
        }
        
        // Get request headers in a cross-platform way
        $headers = $this->getRequestHeaders();
        
        // Remove problematic headers
        unset(
            $headers['Host'],
            $headers['Content-Length'],
            $headers['Accept-Encoding'],
            $headers['Pragma'],
            $headers['Connection'],
            $headers['Cookie']
        );
        
        // Set default headers
        $headers['User-Agent'] = $toolData['user_agent'];
        $headers['Origin'] = $toolData['base_url'];
        $headers['Referer'] = $toolData['base_url'];
        $headers['Sec-Fetch-Site'] = 'same-origin';
        $headers['Cookie'] = $cookieString;
        
        // Convert headers to cURL format
        $curlHeaders = [];
        foreach ($headers as $name => $value) {
            $curlHeaders[] = "$name: $value";
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $targetUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $curlHeaders
        ]);
        
        // Handle different request methods
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_INFILE, fopen('php://input', 'r'));
                break;
            case 'OPTIONS':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                break;
        }
        
        // Execute request
        $response = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL
        curl_close($ch);
        
        // Handle errors
        if ($response === false) {
            error_log("cURL error: " . curl_error($ch));
            http_response_code(500);
            die("Proxy request failed");
        }
        
        // Modify response if HTML
        if (strpos($contentType, 'text/html') !== false) {
            $response = $this->processHtmlResponse($response, $toolData);
        }
        
        // Set appropriate content type
        header('Content-Type: ' . $contentType);
        http_response_code($httpCode);
        
        return $response;
    }
    
    /**
     * Get all HTTP request headers in a cross-platform way
     */
    private function getRequestHeaders() {
        $headers = [];
        
        // If getallheaders() is available (Apache)
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        // Fallback for other servers
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$header] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }
    
    private function processHtmlResponse($html, $toolData) {
        // Replace all occurrences of the original domain with our subdomain
        $originalDomain = parse_url($toolData['base_url'], PHP_URL_HOST);
        $ourDomain = sprintf(TOOLS_SUBDOMAIN_PATTERN, $toolData['id']);
        
        $html = str_replace($originalDomain, $ourDomain, $html);
        $html = str_replace('https://' . $originalDomain, 'https://' . $ourDomain, $html);
        $html = str_replace('http://' . $originalDomain, 'http://' . $ourDomain, $html);
        
        // Add watermark
        if ($this->currentUser) {
            $watermark = $this->generateWatermark($this->currentUser['username']);
            $html = str_replace('</body>', $watermark . '</body>', $html);
        }
        
        return $html;
    }
}