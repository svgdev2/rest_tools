<?php
include 'lib/config_auth.php';

// Verarbeite den eingehenden JSON-Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isApiKeyValid($apiKey)) {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    $newAttributes = $input['new_attribute'] ?? [];
    $data = $input['data'] ?? [];

    $result = [];
    foreach ($data as $item) {
        // Füge die neuen Attribute zu jedem Objekt im Data-Array hinzu
        foreach ($newAttributes as $key => $value) {
            $item[$key] = $value;
        }
        $result[] = $item;
    }

    // Gib das modifizierte Array zurück
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    // Unauthorized Access
    header('HTTP/1.1 401 Unauthorized');
    echo 'Ungültiger API-Schlüssel';
}
?>
