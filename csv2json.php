<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

const EXPECTED_API_KEY = 'kbqq6nibSGWmhpsrqnHH';

$config = parse_ini_file('config/config.ini');
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


function sanitizeString($string) {
    return str_replace(["\r\n", "\n", "\r"], ' ', $string);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isApiKeyValid($apiKey)) {
        http_response_code(401);
        echo "Unauthorized";
        exit;
    }

    if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'text/csv') {
        http_response_code(415);
        echo "Ungültiger Content-Type. Erwartet 'text/csv'.";
        exit;
    }

    $csvStream = fopen('php://input', 'r');
    $headers = fgetcsv($csvStream, 0, ';');

    // Sanitize headers
    $headers = array_map('sanitizeString', $headers);

    $data = [];
    while ($row = fgetcsv($csvStream, 0, ';')) {
        // Sanitize each row
        $row = array_map('sanitizeString', $row);
        $data[] = array_combine($headers, $row);
    }
    fclose($csvStream);

    header('Content-Type: application/json');
    echo json_encode($data);

} else {
    http_response_code(405);
    echo "Methode nicht erlaubt. Bitte HTTP POST verwenden.";
}

?>
