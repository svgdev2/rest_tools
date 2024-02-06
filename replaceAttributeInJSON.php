<?php
include 'lib/config_auth.php';

// Stellen Sie sicher, dass nur POST-Requests akzeptiert werden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Annahme, dass der Request Content-Type: application/json ist
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Überprüfen, ob die benötigten Daten vorhanden sind
    if (isset($data['jsonArray'], $data['targetAttribute'], $data['replacements'], $data['searchAttribute'], $data['replaceAttribute']) &&
        is_array($data['jsonArray']) && is_array($data['replacements'])) {
        
        $jsonArray = $data['jsonArray'];
        $targetAttribute = $data['targetAttribute'];
        $searchAttribute = $data['searchAttribute'];
        $replaceAttribute = $data['replaceAttribute'];
        $replacements = $data['replacements'];
        // Überprüfen, ob 'default' gesetzt ist, nicht null ist und eine Länge größer als 0 hat
        $default = (isset($data['default']) && $data['default'] !== null && strlen($data['default']) > 0) ? $data['default'] : null;

        // Iteriere über jedes Element im Ziel-JSON-Array
        foreach ($jsonArray as &$element) {
            $replacementFound = false; // Flag um zu prüfen, ob eine Ersetzung stattgefunden hat
            if (isset($element[$targetAttribute]) && is_string($element[$targetAttribute])) {
                $originalValue = $element[$targetAttribute]; // Bewahre den ursprünglichen Wert
                // Für jedes Ersetzungselement
                foreach ($replacements as $replacement) {
                    if (isset($replacement[$searchAttribute], $replacement[$replaceAttribute]) &&
                        strpos($element[$targetAttribute], $replacement[$searchAttribute]) !== false) {
                        // Ersetze den Text im Zielattribut durch die angegebenen Kombinationen
                        $element[$targetAttribute] = str_replace($replacement[$searchAttribute], $replacement[$replaceAttribute], $element[$targetAttribute]);
                        $replacementFound = true;
                        break; // Beende die Schleife, wenn eine Ersetzung stattgefunden hat
                    }
                }
                // Wende den Standardwert an, wenn keine Übereinstimmung gefunden und ein Standardwert angegeben wurde
                if (!$replacementFound && $default !== null) {
                    $element[$targetAttribute] = $default;
                }
            }
        }
        unset($element); // Referenz auf das letzte Element aufheben

        // Setze den Content-Type auf application/json und gib das manipulierte Array zurück
        header('Content-Type: application/json');
        echo json_encode($jsonArray);
    } else {
        // Sendet eine Fehlermeldung, wenn die erforderlichen Daten fehlen
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing required data']);
    }
} else {
    // Sendet eine Fehlermeldung, wenn die Request-Methode nicht POST ist
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method']);
}
?>
