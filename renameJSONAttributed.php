<?php
include 'lib/config_auth.php';

// Funktion zur Umbenennung der Attribute in einem Objekt
function renameAttributesInObject($object, $mapping) {
    foreach ($mapping as $oldKey => $newKey) {
        // Prüft, ob das Objekt die Eigenschaft hat, unabhängig von ihrem Wert
        if (property_exists($object, $oldKey)) {
            $object->$newKey = $object->$oldKey;
            unset($object->$oldKey);
        }
    }
    return $object;
}

// Hauptfunktion zur Verarbeitung des JSON-Inputs
function renameAttributes($json) {
    $decodedJson = json_decode($json);

    if (!$decodedJson) {
        http_response_code(400); // Bad Request
        return json_encode(["error" => "Ungültiges JSON-Format."]);
    }

    if (!isset($decodedJson->mapping) || !isset($decodedJson->data)) {
        http_response_code(400); // Bad Request
        return json_encode(["error" => "'mapping' und 'data' müssen im JSON vorhanden sein."]);
    }

    $mapping = $decodedJson->mapping;
    $data = $decodedJson->data;

    // Überprüft, ob data ein Array ist
    if (is_array($data)) {
        // Verarbeitet jedes Element des Arrays
        foreach ($data as $key => $value) {
            $data[$key] = renameAttributesInObject($value, $mapping);
        }
    } else {
        // Verarbeitet ein einzelnes Objekt
        $data = renameAttributesInObject($data, $mapping);
    }

    return json_encode($data, JSON_PRETTY_PRINT);
}

// REST-API-Teil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonInput = file_get_contents('php://input');
    $result = renameAttributes($jsonInput);

    if (json_decode($result)->error) {
        echo $result;
    } else {
        http_response_code(200); // OK
        header('Content-Type: application/json');
        echo $result;
    }
} else {
    http_response_code(405); // Method Not Allowed
    header('Content-Type: application/json');
    echo json_encode(["error" => "Nur POST-Requests sind erlaubt."]);
}
?>
