<?php
class AMemberIntegration {
    private $apiKey;
    private $apiUrl;
    
    public function __construct($apiKey, $apiUrl) {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }
    
    public function checkSubscription($userId) {
        $url = $this->apiUrl . '/check-access/by-user-id?' . http_build_query([
            '_key' => $this->apiKey,
            'user_id' => $userId,
            'product_ids' => implode(',', [/* your product IDs here */])
        ]);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        return !empty($data) && $data[0]['has_access'];
    }
    
    public function syncUser($userId) {
        // Get user data from aMember
        $url = $this->apiUrl . '/users?' . http_build_query([
            '_key' => $this->apiKey,
            'user_id' => $userId
        ]);
        
        $response = file_get_contents($url);
        $userData = json_decode($response, true);
        
        if (empty($userData)) {
            return false;
        }
        
        // Update local database
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET 
                              username = ?, email = ?, amember_id = ?
                              WHERE id = ?");
        $stmt->execute([
            $userData[0]['login'],
            $userData[0]['email'],
            $userData[0]['user_id'],
            $userId
        ]);
        
        return true;
    }
    
    public function webhookHandler() {
        // Handle aMember webhooks for real-time updates
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['event'])) {
            http_response_code(400);
            die('Invalid webhook data');
        }
        
        global $pdo;
        
        switch ($data['event']) {
            case 'subscription_started':
            case 'subscription_renewed':
                // Grant access or update credits
                $userId = $this->getLocalUserId($data['user_id']);
                if ($userId) {
                    $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
                    $stmt->execute([$data['product_id'] === 'premium' ? 100 : 50, $userId]);
                }
                break;
                
            case 'subscription_expired':
                // Revoke access or set credits to 0
                $userId = $this->getLocalUserId($data['user_id']);
                if ($userId) {
                    $stmt = $pdo->prepare("UPDATE users SET credits = 0 WHERE id = ?");
                    $stmt->execute([$userId]);
                }
                break;
        }
        
        http_response_code(200);
        echo 'OK';
    }
    
    private function getLocalUserId($amemberId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE amember_id = ?");
        $stmt->execute([$amemberId]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }
}
?>