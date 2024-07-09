<?php
include 'lib/config_auth.php';

// Stelle sicher, dass der Content-Type auf application/json gesetzt ist
header('Content-Type: application/json');

// Empfange den rohen POST-Body als XML-String
$xmlString = file_get_contents('php://input');

// Überprüfe, ob Daten empfangen wurden
if (!empty($xmlString)) {
    // Versuche, den XML-String in ein SimpleXMLElement zu laden
    try {
        $xml = new SimpleXMLElement($xmlString);
        // Konvertiere das SimpleXMLElement in ein Array
        $array = json_decode(json_encode((array)$xml), true);
        // Entferne leere Nodes, die als Arrays konvertiert wurden
        $array = array_filter($array);
        // Sende das Array als JSON zurück
        echo json_encode(['status' => 'success', 'data' => $array]);
    } catch (Exception $e) {
        // Im Fehlerfall sende eine Fehlermeldung
        echo json_encode(['status' => 'error', 'message' => 'Invalid XML format']);
    }
} else {
    // Wenn keine Daten empfangen wurden, sende eine Fehlermeldung
    echo json_encode(['status' => 'error', 'message' => 'No XML data received']);
}
?>
