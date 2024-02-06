<?php

require 'vendor/autoload.php';
include 'lib/config_auth.php'; // Stelle sicher, dass die Konfiguration korrekt geladen wird.

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;



// Annahme der JSON-Daten per HTTP POST
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if ($data) {
    $jsonData = $data['json'];
    $jsonSchema = $data['schema'];

    $validator = new Validator();
	
    $validator->validate($jsonData, $jsonSchema, Constraint::CHECK_MODE_TYPE_CAST);

    if ($validator->isValid()) {
        http_response_code(200);
        echo 'Die JSON-Daten sind gültig.';
    } else {
        http_response_code(422);
        echo "JSON-Validierung fehlgeschlagen:\n";
        foreach ($validator->getErrors() as $error) {
            echo sprintf("[%s] %s\n", $error['property'], $error['message']);
        }
    }
} else {
    http_response_code(400);
    echo 'Ungültige Eingabe';
}

?>
