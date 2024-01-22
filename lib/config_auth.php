<?php
// config_auth.php
$config = parse_ini_file('config/config.ini', true);
$apiKey = $config['api_key'];
$temp_dir = $config['temp_path'];

function getReceivedApiKey() {
    $headers = apache_request_headers();
    $apiKeyHeader = 'X-Api-Key';
    if (!isset($headers[$apiKeyHeader])) {
        $apiKeyHeader = 'X-API-Key'; // Alternative Schreibweise
    }

    return isset($headers[$apiKeyHeader]) ? $headers[$apiKeyHeader] : 'Nicht vorhanden';
}

function isApiKeyValid($apiKey) {
    $receivedApiKey = getReceivedApiKey();
    return $receivedApiKey === $apiKey;
}


if (!isApiKeyValid($apiKey)) {
    // Unauthorized Access
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Invalid API key']);
    exit();
}


