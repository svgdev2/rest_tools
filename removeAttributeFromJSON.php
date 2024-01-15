<?php
include 'lib/config_auth.php';

// Verarbeite den eingehenden JSON-Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isApiKeyValid($apiKey)) {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    $attributesToRemove = $input['remove_attribute'] ?? [];
    $data = $input['data'] ?? [];

    $result = [];
    foreach ($data as $item) {
        // Entferne die angegebenen Attribute aus jedem Objekt im Data-Array
        foreach ($attributesToRemove as $attr) {
            unset($item[$attr]);
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
