<?php
class SecurityManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function validateSession($toolId, $accessToken) {
        // First verify the basic access token
        $stmt = $this->pdo->prepare("SELECT u.*, uta.* FROM user_tool_access uta 
                                    JOIN users u ON uta.user_id = u.id 
                                    WHERE uta.tool_id = ? AND uta.access_token = ? AND uta.expires_at > NOW()");
        $stmt->execute([$toolId, $accessToken]);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }
        
        $access = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Now perform advanced checks
        if (!$this->checkFingerprint($access['user_id'])) {
            return false;
        }
        
        return $access;
    }
    
    private function checkFingerprint($userId) {
        // Get expected fingerprint from last successful login
        $stmt = $this->pdo->prepare("SELECT * FROM user_fingerprints WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $knownFingerprint = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$knownFingerprint) {
            // No fingerprint on record, allow first access
            $this->saveFingerprint($userId);
            return true;
        }
        
        // Check current fingerprint against known one
        $currentFingerprint = $this->generateFingerprint();
        
        // Compare critical components
        $criticalComponents = ['ip_address', 'user_agent', 'timezone'];
        foreach ($criticalComponents as $component) {
            if ($currentFingerprint[$component] !== $knownFingerprint[$component]) {
                // Log this suspicious activity
                $this->logSuspiciousActivity($userId, "Fingerprint mismatch on $component");
                return false;
            }
        }
        
        // Compare non-critical components with some tolerance
        if (abs($currentFingerprint['screen_width'] - $knownFingerprint['screen_width']) > 100 ||
            abs($currentFingerprint['screen_height'] - $knownFingerprint['screen_height']) > 100) {
            $this->logSuspiciousActivity($userId, "Screen resolution change detected");
            // We'll allow this but log it
        }
        
        return true;
    }
    
    private function generateFingerprint() {
        return [
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'screen_width' => $_POST['screen_width'] ?? 0,
            'screen_height' => $_POST['screen_height'] ?? 0,
            'timezone' => $_POST['timezone'] ?? 'Unknown',
            'plugins' => $_POST['plugins'] ?? 'Unknown',
            'fonts' => $_POST['fonts'] ?? 'Unknown',
            'hardware_concurrency' => $_POST['hardware_concurrency'] ?? 0
        ];
    }
    
    private function saveFingerprint($userId) {
        $fingerprint = $this->generateFingerprint();
        
        $stmt = $this->pdo->prepare("INSERT INTO user_fingerprints 
                                    (user_id, ip_address, user_agent, screen_width, screen_height, 
                                     timezone, plugins, fonts, hardware_concurrency, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $fingerprint['ip_address'],
            $fingerprint['user_agent'],
            $fingerprint['screen_width'],
            $fingerprint['screen_height'],
            $fingerprint['timezone'],
            $fingerprint['plugins'],
            $fingerprint['fonts'],
            $fingerprint['hardware_concurrency']
        ]);
    }
    
    private function logSuspiciousActivity($userId, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO security_logs 
                                    (user_id, description, ip_address, user_agent, log_time) 
                                    VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    }
}
?>