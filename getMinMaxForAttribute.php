<?php
include 'lib/config_auth.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // Empfängt das JSON im Body des Requests
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

    if (isset($data['targetAttribut']) && isset($data['json'])) {
        $targetAttribut = $data['targetAttribut'];
        $jsonData = $data['json'];

        $values = array_map(function ($item) use ($targetAttribut) {
            return $item[$targetAttribut] ?? null;
        }, $jsonData);

        // Filtert ungültige Werte aus
        $values = array_filter($values, function ($value) {
            return !is_null($value);
        });

        if (count($values) > 0) {
            // Versucht, den Datentyp zu erkennen und entsprechend zu sortieren
            usort($values, function ($a, $b) {
                if (strtotime($a) && strtotime($b)) {
                    // Beide Werte sind gültige Daten
                    return strtotime($a) - strtotime($b);
                } elseif (is_numeric($a) && is_numeric($b)) {
                    // Beide Werte sind Zahlen
                    return $a - $b;
                } else {
                    // Standardmäßiger Textvergleich
                    return strcmp($a, $b);
                }
            });

            $min = $values[0];
            $max = $values[count($values) - 1];

            echo json_encode(['min' => $min, 'max' => $max]);
        } else {
            echo json_encode(['error' => 'No valid values found']);
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing required fields']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}

?>
