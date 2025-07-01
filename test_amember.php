<?php
require_once __DIR__ . '/config.php';

// echo "API Key: ". AMEMBER_API_KEY. "\n";
// die;
$ch = curl_init(AMEMBER_API_URL . '/check-access/by-login?_key=' . AMEMBER_API_KEY . '&login=test');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Status: $http_code\n";
echo "Response: $response\n";