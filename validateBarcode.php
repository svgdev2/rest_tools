<?php
include 'lib/config_auth.php';

header('Content-Type: application/json');

// Funktion zur Validierung der EAN-13, UPC-A und ITF-14
function validateStandardBarcode($barcode) {
    $length = strlen($barcode);
    if (!in_array($length, [12, 13, 14]) || !preg_match('/^[0-9]+$/', $barcode)) {
        return false;
    }

    $sum = 0;
    $factor = ($length % 2 == 0) ? 3 : 1; // Umkehrung für UPC-A und ITF-14
    for ($i = 0; $i < $length - 1; $i++) {
        $sum += $barcode[$i] * ($factor);
        $factor = 4 - $factor; // Wechselt zwischen 1 und 3
    }

    $check = (10 - ($sum % 10)) % 10;
    return $check == $barcode[$length - 1];
}
// Funktion zur Validierung von EAN-8
function validateEAN8($ean8) {
    if (strlen($ean8) != 8 || !preg_match('/^[0-9]{8}$/', $ean8)) {
        return false;
    }

    $sum = 0;
    for ($i = 0; $i < 7; $i++) {
        $sum += $ean8[$i] * ($i % 2 == 0 ? 3 : 1);
    }

    $check = (10 - ($sum % 10)) % 10;
    return $check == $ean8[7];
}

// Bestimmen Sie den Barcode-Typ und rufen Sie die entsprechende Validierungsfunktion auf
function validateBarcode($barcode) {
    switch (strlen($barcode)) {
        case 8:
            return validateEAN8($barcode);
        case 12:
        case 13:
        case 14:
            return validateStandardBarcode($barcode);
        default:
            return false;
    }
}

// EAN/GS1/UPC/ITF Barcode aus dem HTTP-Request erhalten
$barcode = $_GET['barcode'] ?? '';

// Validierung des Barcodes
if (validateBarcode($barcode)) {
    http_response_code(200);
    echo json_encode(["message" => "Valid Barcode"]);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Invalid Barcode"]);
}

?>
