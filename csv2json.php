<?php
include 'lib/config_auth.php';

function sanitizeString($string) {
    return str_replace(["\r\n", "\n", "\r"], ' ', $string);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'text/csv') {
        http_response_code(415);
        echo "Ungültiger Content-Type. Erwartet 'text/csv'.";
        exit;
    }

    $csvStream = fopen('php://input', 'r');
    $headers = fgetcsv($csvStream, 0, ';');

    // Sanitize headers
    $headers = array_map('sanitizeString', $headers);

    $data = [];
    while ($row = fgetcsv($csvStream, 0, ';')) {
        // Sanitize each row
        $row = array_map('sanitizeString', $row);

        // Überprüfen, ob die Anzahl der Spalten in der Zeile mit den Kopfzeilen übereinstimmt
        if (count($headers) !== count($row)) {
            // Fehlerbehandlung oder Überspringen der Zeile
            continue; // Überspringt diese Zeile
        }

        $data[] = array_combine($headers, $row);
    }
    fclose($csvStream);

    header('Content-Type: application/json');
    echo json_encode($data);

} else {
    http_response_code(405);
    echo "Methode nicht erlaubt. Bitte HTTP POST verwenden.";
}

?>
