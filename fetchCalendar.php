<?php
// Erlaube CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

// Pfad zum Cache-Verzeichnis definieren
$cacheDir = __DIR__ . '/temp';

// Verschlüsselungskonfiguration
$encryptionMethod = "AES-256-CBC"; // Verschlüsselungsmethode
$secretKey = hash('sha256', "dvf.-32.,3290d3", true); // Schlüssel für die Verschlüsselung
$ivLength = openssl_cipher_iv_length($encryptionMethod); // IV Länge ermitteln

function getConfigValue($section, $key) {
    $configArray = parse_ini_file(__DIR__ . '/config/config.ini', true);
    if (isset($configArray[$section][$key])) {
        return $configArray[$section][$key];
    } else {
        return null; // oder einen Standardwert zurückgeben
    }
}

$baseUrl = getConfigValue('ical', 'base_url');
$suffix = getConfigValue('ical', 'suffix');

// Funktion zum Bereinigen alter Cache-Dateien
function cleanupOldCacheFiles($cacheDir) {
    $files = glob($cacheDir . '/cache_*'); // Alle Cache-Dateien im Verzeichnis
    $now = time();

    foreach ($files as $file) {
        if (is_file($file) && $now - filemtime($file) > 600) { // Cache-Dateien löschen, die älter als 10 Minuten sind
            unlink($file);
        }
    }
}

// Funktion zum Verschlüsseln der Daten
function encryptData($data, $secretKey, $iv) {
    global $encryptionMethod;
    return openssl_encrypt($data, $encryptionMethod, $secretKey, 0, $iv) . "::" . base64_encode($iv);
}

// Funktion zum Entschlüsseln der Daten
function decryptData($data, $secretKey) {
    global $encryptionMethod;
    list($encryptedData, $iv) = explode("::", $data, 2);
    return openssl_decrypt($encryptedData, $encryptionMethod, $secretKey, 0, base64_decode($iv));
}

// Angepasste Funktion zum Abrufen von iCal-Daten unter Berücksichtigung des Caches
function fetchICalDataWithCache($url, $cacheDir, $secretKey) {
    global $ivLength;
    $iv = openssl_random_pseudo_bytes($ivLength); // IV generieren

    cleanupOldCacheFiles($cacheDir); // Alte Cache-Dateien vor jedem Abruf bereinigen

    $cacheFile = $cacheDir . '/cache_' . md5($url) . '.txt'; // Cache-Datei im /temp Verzeichnis

    if (file_exists($cacheFile) && (filemtime($cacheFile) + 660) > time()) {
        $encryptedData = file_get_contents($cacheFile);
        return decryptData($encryptedData, $secretKey);
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $encryptedData = encryptData($data, $secretKey, $iv);
        file_put_contents($cacheFile, $encryptedData);
        return $data;
    }
}

function convertICalToJson($icalData) {
    $events = []; // Initialisieren eines Arrays für Kalendereinträge
    $icalLines = explode("\n", str_replace("\r", "", $icalData)); // Bereinige und teile die Daten in Zeilen

    $currentEvent = [];
    foreach ($icalLines as $line) {
        if (strpos($line, 'BEGIN:VEVENT') !== false) {
            // Beginn eines neuen Events, initialisiere das Array neu
            $currentEvent = [];
        } elseif (strpos($line, 'END:VEVENT') !== false) {
            // Ende eines Events, füge es zum Array hinzu
            $events[] = $currentEvent;
            $currentEvent = [];
        } else {
            // Verarbeite Event-Details
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2); // Teile die Zeile in Schlüssel und Wert
                switch ($key) {
                    case 'DTSTART':
                    case 'DTEND':
                    case 'DTSTAMP':
                    case 'UID':
                    case 'SEQUENCE':
                    case 'SUMMARY':
                        // Bereinige und decodiere die Werte
                        $currentEvent[strtolower($key)] = str_replace('\\,', ',', str_replace('\\;', ';', $value));
                        break;
                }
            }
        }
    }

    return $events;
}

// Hauptlogik für die Verarbeitung der Anfrage
if (isset($_GET['resource'])) {
    $encodedUrlPart = $_GET['resource'];
    $decodedUrlPart = base64_decode($encodedUrlPart);

    // Zusammensetzen der vollständigen URL aus den Teilen
    $fullUrl = $baseUrl . $decodedUrlPart . $suffix;

    // Überprüfung, ob die zusammengesetzte URL gültig ist
    if (filter_var($fullUrl, FILTER_VALIDATE_URL) === false) {
        echo 'Invalid parameter provided.';
        exit;
    }

    // Verwende $fullUrl in der Funktion fetchICalDataWithCache
    $icalData = fetchICalDataWithCache($fullUrl, $cacheDir, $secretKey);
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'ical';

    if ($format == 'json') {
        header('Content-Type: application/json');
        $jsonEvents = convertICalToJson($icalData);
        echo json_encode(['events' => $jsonEvents]);
    } else {
		header('Content-Type: text/calendar');
       // Gibt die iCal-Daten direkt zurück
        echo $icalData;
    }
} else {
    echo 'Required parameter is missing.';
}
?>
