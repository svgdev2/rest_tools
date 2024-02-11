<?php
include 'lib/config_auth.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // Empfängt das JSON im Body des Requests
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true); // true, um es als Array zu decodieren

    // Überprüft, ob die notwendigen Daten vorhanden sind
    if (isset($data['attribute'], $data['targetText'], $data['replacementText'], $data['json'])) {
        $attribute = $data['attribute'];
        $targetText = $data['targetText'];
        $replacementText = $data['replacementText'];
        $jsonData = $data['json'];

        // Überprüft, ob $jsonData ein Array oder ein Objekt ist
        if (is_array($jsonData)) {
            // Ist ein Array
            array_walk_recursive($jsonData, function (&$item, $key) use ($attribute, $targetText, $replacementText) {
                if ($key === $attribute && is_string($item)) {
                    $item = str_replace($targetText, $replacementText, $item);
                }
            });
        } elseif (is_array($jsonData) && isset($jsonData[$attribute]) && is_string($jsonData[$attribute])) {
            // Ist ein einzelnes Objekt
            $jsonData[$attribute] = str_replace($targetText, $replacementText, $jsonData[$attribute]);
        }

        echo json_encode($jsonData);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing required fields']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}
?>
