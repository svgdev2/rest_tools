<?php
// Laden der Autoload-Datei für den Composer, falls erforderlich
// require 'vendor/autoload.php';

// Header, um CORS-Probleme zu vermeiden (nicht für den Produktionsbetrieb empfohlen)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

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

if (!isApiKeyValid($apiKey)) {
    echo json_encode(['error' => 'Invalid API key']);
    exit();
}

// Überprüfen, ob die Anfrage eine POST-Anfrage ist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Auslesen des Textes aus der POST-Anfrage
    $text = file_get_contents('php://input');
    
    // Überprüfen des Encodings
    $currentEncoding = mb_detect_encoding($text, 'UTF-8, ISO-8859-1', true);
    
    // Wenn das Encoding nicht UTF-8 ist, konvertieren wir es
    if ($currentEncoding !== 'UTF-8') {
        $text = mb_convert_encoding($text, 'UTF-8', $currentEncoding);
    }
    
    // Zurückgeben des Textes (nun sicherlich in UTF-8)
    echo json_encode(['text' => $text, 'original_encoding' => $currentEncoding]);
} else {
    // Fehlermeldung, wenn die Methode nicht POST ist
    echo json_encode(['error' => 'Invalid request method']);
}
?>
