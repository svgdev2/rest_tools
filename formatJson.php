<?php

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

function convertCurrencyToNumber($input) {
    // Ersetze gängige Währungszeichen durch nichts
    $cleanedInput = str_replace(['$', '€', '£', '¥'], '', $input);

    // Ersetze regionale Tausendertrennzeichen und konvertiere Komma in Punkt für Dezimalzahlen
    $cleanedInput = str_replace([','], ['.'], $cleanedInput);

    // Konvertiere den bereinigten String in eine Gleitkommazahl
    $number = floatval($cleanedInput);

    if (!is_numeric($number)) {
        throw new Exception("Ungültige Eingabe: $input");
    }

    return $number;
}

function transformValue($value) {
    // Datumsumwandlung
    if (preg_match("/^(\d{2})\.(\d{2})\.(\d{4})(?: (\d{2}):(\d{2}):(\d{2}))?$/", $value, $matches)) {
        if (isset($matches[4])) {  // Zeit ist vorhanden
            return date("Y-m-d\TH:i:s.0000000", mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[1], $matches[3]));
        } else {  // Nur Datum
            return date("Y-m-d\TH:i:s.0000000", mktime(0, 0, 0, $matches[2], $matches[1], $matches[3]));
        }
    }

    // Behandlung von Prozentwerten
    if (strpos($value, '%') !== false) {
        $value = str_replace('%', '', $value);
        $value = floatval($value) / 100;
        return $value;
    }

    // Versuche, den Wert als Währung oder Zahl zu interpretieren
    if (is_numeric(str_replace(['$', '€', '£', '¥', ',', '%'], ['', '', '', '', '', ''], $value))) {
        try {
            return convertCurrencyToNumber($value);
        } catch (Exception $e) {
            // Falls ein Fehler auftritt, wird der Originalwert zurückgegeben
            return $value;
        }
    }

    // Für alle anderen Werte, gib den Originalwert zurück
    return $value;
}



function transformJson($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = transformJson($value);
            } else if (is_string($value)) {
                $data[$key] = transformValue($value);
            }
        }
    }
    return $data;
}

if (!isApiKeyValid($apiKey)) {
    echo json_encode(['error' => 'Invalid API key', 'received_key' => getReceivedApiKey()]);
    exit();
}

$inputJson = file_get_contents('php://input');
$utf8EncodedJson = mb_convert_encoding($inputJson, 'UTF-8', mb_detect_encoding($inputJson, 'UTF-8, ISO-8859-1', true));
$json_data = json_decode($utf8EncodedJson, true);
//$json_data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON input', 'json_error' => json_last_error_msg()]);
    exit();
}
// Transformiert das JSON
$transformed_data = transformJson($json_data);

// Gibt das transformierte JSON aus
header('Content-Type: application/json; charset=utf-8');

echo json_encode($transformed_data);

?>
