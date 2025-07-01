<?php
require_once 'config.php';

class WritingToolManager extends ToolManager {
    private $contentModifiers = [];
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        
        // Initialize content modifiers for different tools
        $this->contentModifiers = [
            'Stealth Writer' => [
                'input_selector' => '#content-input',
                'output_selector' => '#humanized-content',
                'button_selector' => '#humanize-button'
            ],
            'Write Human' => [
                'input_selector' => '#text-input',
                'output_selector' => '#output-text',
                'button_selector' => '#process-btn'
            ]
        ];
    }
    
    public function proxyRequest($toolData, $requestUrl) {
        // Get the parent response first
        $response = parent::proxyRequest($toolData, $requestUrl);
        
        // If this is an HTML response, modify it for the specific tool
        if (strpos($response, '<html') !== false) {
            $toolName = $toolData['name'];
            
            if (array_key_exists($toolName, $this->contentModifiers)) {
                $modifiers = $this->contentModifiers[$toolName];
                $response = $this->modifyToolInterface($response, $modifiers);
            }
        }
        
        return $response;
    }
    
    private function modifyToolInterface($html, $modifiers) {
        // Inject our JavaScript to monitor usage
        $js = "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Track character count
            const inputEl = document.querySelector('{$modifiers['input_selector']}');
            const outputEl = document.querySelector('{$modifiers['output_selector']}');
            const buttonEl = document.querySelector('{$modifiers['button_selector']}');
            
            if (inputEl && buttonEl) {
                let lastProcessTime = 0;
                const minInterval = " . (MIN_QUERY_INTERVAL * 1000) . ";
                
                buttonEl.addEventListener('click', function() {
                    const now = Date.now();
                    if (now - lastProcessTime < minInterval) {
                        alert('Please wait ' + (minInterval/1000) + ' seconds between requests');
                        return false;
                    }
                    lastProcessTime = now;
                    
                    // Track this usage
                    fetch('/api/track_usage', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '".$_SESSION['csrf_token']."'
                        },
                        body: JSON.stringify({
                            tool_id: ".$toolData['id'].",
                            input_length: inputEl.value.length,
                            output_length: outputEl ? outputEl.value.length : 0
                        })
                    });
                });
            }
            
            // Prevent right-click and copy
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            document.addEventListener('copy', function(e) {
                e.preventDefault();
                alert('Please use the download button to save your content');
                return false;
            });
        });
        </script>
        ";
        
        // Add our JS just before the closing body tag
        $html = str_replace('</body>', $js . '</body>', $html);
        
        return $html;
    }
    
    public function trackUsage($userId, $toolId, $inputLength, $outputLength) {
        $stmt = $this->pdo->prepare("INSERT INTO tool_usage 
                                    (user_id, tool_id, input_length, output_length, usage_time) 
                                    VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $toolId, $inputLength, $outputLength]);
        
        // Update user's last activity time
        $this->updateLastRequestTime($userId);
    }
}
?>