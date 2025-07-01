<?php
require_once 'config.php';
require_once 'writing_tools.php';

header('Content-Type: application/json');

$writingToolManager = new WritingToolManager($pdo);

// Verify CSRF token
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['tool_id'])) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $writingToolManager->trackUsage(
        $_SESSION['user_id'],
        $input['tool_id'],
        $input['input_length'] ?? 0,
        $input['output_length'] ?? 0
    );
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>