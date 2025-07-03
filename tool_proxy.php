<?php
// CONFIG
$target = "https://stealthwriter.ai";

// 🔥 Replace with your real session cookies below:
$premium_cookies = "intercom-device-id-esc25l7u=b1fd11c8-23d1-4940-9319-bdfa353a8ece; intercom-id-esc25l7u=95c92ac4-df49-4efb-ab51-0773e49fd439; intercom-session-esc25l7u=; sb-vqdtifewupwhdypyimkf-auth-token.0=base64-eyJhY2Nlc3NfdG9rZW4iOiJleUpoYkdjaU9pSklVekkxTmlJc0ltdHBaQ0k2SWtGaE5sUk5WSEp4YzI5emJqRmtUR29pTENKMGVYQWlPaUpLVjFRaWZRLmV5SnBjM01pT2lKb2RIUndjem92TDNaeFpIUnBabVYzZFhCM2FHUjVjSGxwYld0bUxuTjFjR0ZpWVhObExtTnZMMkYxZEdndmRqRWlMQ0p6ZFdJaU9pSTNZMlV3WlRaa01DMHhOV1kzTFRRMFpUSXRZakJsTlMweFlUYzFNRGM1TW1FM01qY2lMQ0poZFdRaU9pSmhkWFJvWlc1MGFXTmhkR1ZrSWl3aVpYaHdJam94TnpVeE5EY3dPVEEzTENKcFlYUWlPakUzTlRFME5qY3pORFVzSW1WdFlXbHNJam9pWVhOaFpIVnNiR0ZvTURNeE9Ea3dOVEV3TnpkQVoyMWhhV3d1WTI5dElpd2ljR2h2Ym1VaU9pSWlMQ0poY0hCZmJXVjBZV1JoZEdFaU9uc2ljSEp2ZG1sa1pYSWlPaUpuYjI5bmJHVWlMQ0p3Y205MmFXUmxjbk1pT2xzaVoyOXZaMnhsSWwxOUxDSjFjMlZ5WDIxbGRHRmtZWFJoSWpwN0ltRjJZWFJoY2w5MWNtd2lPaUpvZEhSd2N6b3ZMMnhvTXk1bmIyOW5iR1YxYzJWeVkyOXVkR1Z1ZEM1amIyMHZZUzlCUTJjNGIyTk1SbTlGTjBwNVVsSmtOMmsyVEU1M2R6bDJjVTFsVGtGM1VUZ3dRbUkyYXpkS1NrUlJjbEpGY1ZSVFFpMWtOVGxhZFQxek9UWXRZeUlzSW1WdFlXbHNJam9pWVhOaFpIVnNiR0ZvTURNeE9Ea3dOVEV3TnpkQVoyMWhhV3d1WTI5dElpd2laVzFoYVd4ZmRtVnlhV1pwWldRaU9uUnlkV1VzSW1aMWJHeGZibUZ0WlNJNklsTWc1TG1JSUVRZ1V5RGt1WWdnUkNJc0ltbHpjeUk2SW1oMGRIQnpPaTh2WVdOamIzVnVkSE11WjI5dloyeGxMbU52YlNJc0ltNWhiV1VpT2lKVElPUzVpQ0JFSUZNZzVMbUlJRVFpTENKd2FHOXVaVjkyWlhKcFptbGxaQ0k2Wm1Gc2MyVXNJbkJwWTNSMWNtVWlPaUpvZEhSd2N6b3ZMMnhvTXk1bmIyOW5iR1YxYzJWeVkyOXVkR1Z1ZEM1amIyMHZZUzlCUTJjNGIyTk1SbTlGTjBwNVVsSmtOMmsyVEU1M2R6bDJjVTFsVGtGM1VUZ3dRbUkyYXpkS1NrUlJjbEpGY1ZSVFFpMWtOVGxhZFQxek9UWXRZeUlzSW5CeWIzWnBaR1Z5WDJsa0lqb2lNVEU0TVRJMU9ETTBNalF5TlRFMU1UYzROVEl4SWl3aWMzVmlJam9pTVRFNE1USTFPRE0wTWpReU5URTFNVGM0TlRJeEluMHNJbkp2YkdVaU9pSmhkWFJvWlc1MGFXTmhkR1ZrSWl3aVlXRnNJam9pWVdGc01TSXNJbUZ0Y2lJNlczc2liV1YwYUc5a0lqb2liMkYxZEdnaUxDSjBhVzFsYzNSaGJYQWlPakUzTlRFME5qY3pORFY5WFN3aWMyVnpjMmx2Ymw5cFpDSTZJakEzTWpVMFpqZzVMVFk0WlRFdE5HWm1PUzA1TXpGbExXUmlOR0V4TUdNME16ZzJOQ0lzSW1selgyRnViMjU1Ylc5MWN5STZabUZzYzJWOS5pZWhrTk9KaHM2ZjlDSWU4ZFlUNFhJLXZxa1ZQS2VhRk9aYk5LR3FrNGg0IiwidG9rZW5fdHlwZSI6ImJlYXJlciIsImV4cGlyZXNfaW4iOjM1NjIsImV4cGlyZXNfYXQiOjE3NTE0NzA5MDcsInJlZnJlc2hfdG9rZW4iOiJFVFFlTnF2cjRac0hRNmlSR3Rka09RIiwidXNlciI6eyJpZCI6IjdjZTBlNmQwLTE1ZjctNDRlMi1iMGU1LTFhNzUwNzkyYTcyNyIsImF1ZCI6ImF1dGhlbnRpY2F0ZWQiLCJyb2xlIjoiYXV0aGVudGljYXRlZCIsImVtYWlsIjoiYXNhZHVsbGFoMDMxODkwNTEwNzdAZ21haWwuY29tIiwiZW1haWxfdmVyaWZpZWQiOnRydWV9fQ==; sb-vqdtifewupwhdypyimkf-auth-token.1=iIxMTgxMjU4MzQyNDI1MTUxNzg1MjEiLCJ1c2VyX2lkIjoiN2NlMGU2ZDAtMTVmNy00NGUyLWIwZTUtMWE3NTA3OTJhNzI3IiwiaWRlbnRpdHlfZGF0YSI6eyJhdmF0YXJfdXJsIjoiaHR0cHM6Ly9saDMuZ29vZ2xldXNlcmNvbnRlbnQuY29tL2EvQUNnOG9jTEZvRTdKeVJSZDdpNkxOd3c5dnFNZU5Bd1E4MEJiNms3SkpEUXJSRXFUU0ItZDU5WnU9czk2LWMiLCJlbWFpbCI6ImFzYWR1bGxhaDAzMTg5MDUxMDc3QGdtYWlsLmNvbSIsImVtYWlsX3ZlcmlmaWVkIjp0cnVlLCJmdWxsX25hbWUiOiJTIOS5iCBEIFMg5LmIIEQiLCJpc3MiOiJodHRwczovL2FjY291bnRzLmdvb2dsZS5jb20iLCJuYW1lIjoiUyDkuYggRCBTIOS5iCBEIiwicGhvbmVfdmVyaWZpZWQiOmZhbHNlLCJwaWN0dXJlIjoiaHR0cHM6Ly9saDMuZ29vZ2xldXNlcmNvbnRlbnQuY29tL2EvQUNnOG9jTEZvRTdKeVJSZDdpNkxOd3c5dnFNZU5Bd1E4MEJiNms3SkpEUXJSRXFUU0ItZDU5WnU9czk2LWMiLCJwcm92aWRlcl9pZCI6IjExODEyNTgzNDI0MjUxNTE3ODUyMSIsInN1YiI6IjExODEyNTgzNDI0MjUxNTE3ODUyMSJ9fQ==;";

// REQUEST
$request_uri = $_SERVER['REQUEST_URI'];
$url = $target . $request_uri;

// INIT CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
}

// HEADERS
$headers = [];
foreach (getallheaders() as $name => $value) {
    if (strtolower($name) !== 'host') $headers[] = "$name: $value";
}
$headers[] = "Cookie: $premium_cookies";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// RETURN RESPONSE
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
if ($response === false) {
    header("HTTP/1.1 502 Bad Gateway");
    exit("Proxy error: " . curl_error($ch));
}
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

// STRIP SECURITY HEADERS
$header = preg_replace('/^X-Frame-Options:.*$/mi', '', $header);
$header = preg_replace('/^Content-Security-Policy:.*$/mi', '', $header);

// SEND HEADERS
$lines = explode("\r\n", $header);
foreach ($lines as $line) {
    if (trim($line) !== "" && stripos($line, 'Transfer-Encoding') === false) {
        header($line, false);
    }
}

// INJECT WATERMARK + REWRITE URLS
if (strpos($header, "Content-Type: text/html") !== false) {
    $body = inject_watermark_and_rewrite($body, $target);
}
echo $body;

// FUNCTIONS
function inject_watermark_and_rewrite($html, $target) {
    $proxy_base = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    $html = str_ireplace('</body>', '<div style="position:fixed;bottom:10px;right:10px;opacity:0.3;pointer-events:none;z-index:9999;">User: YourBrand</div></body>', $html);
    $html = preg_replace('/(href|src)=([\'"])\//i', '$1=$2' . $proxy_base . '/', $html);
    $parsed = parse_url($target);
    $target_host = preg_quote($parsed['host'], '/');
    $html = preg_replace("/(href|src)=([\'\"])(https?:)?\/\/{$target_host}([^\'\"]*)/i", '$1=$2' . $proxy_base . '$4', $html);
    return $html;
}
?>
