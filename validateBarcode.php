<?php
include 'lib/config_auth.php';

header('Content-Type: application/json');

// Funktion zur Validierung der EAN-13, UPC-A und ITF-14
function validateStandardBarcode($barcode) {
    $length = strlen($barcode);
    if (!in_array($length, [12, 13, 14]) || !preg_match('/^[0-9]+$/', $barcode)) {
        return ['valid' => false, 'error' => "Ungültige Länge oder ungültige Zeichen für Standard Barcode"];
    }

    $sum = 0;
    $factor = ($length % 2 == 0) ? 3 : 1;
    for ($i = 0; $i < $length - 1; $i++) {
        $sum += $barcode[$i] * ($factor);
        $factor = 4 - $factor;
    }

    $check = (10 - ($sum % 10)) % 10;
    return $check == $barcode[$length - 1] ? ['valid' => true] : ['valid' => false, 'error' => "Ungültige Prüfziffer für Standard Barcode"];
}

// Funktion zur Validierung von EAN-8
function validateEAN8($ean8) {
    if (strlen($ean8) != 8 || !preg_match('/^[0-9]{8}$/', $ean8)) {
        return ['valid' => false, 'error' => "Ungültige Länge oder ungültige Zeichen für EAN-8"];
    }

    $sum = 0;
    for ($i = 0; $i < 7; $i++) {
        $sum += $ean8[$i] * ($i % 2 == 0 ? 3 : 1);
    }

    $check = (10 - ($sum % 10)) % 10;
    return $check == $ean8[7] ? ['valid' => true] : ['valid' => false, 'error' => "Ungültige Prüfziffer für EAN-8"];
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
            return ['valid' => false, 'error' => "Unbekannte Barcode-Länge"];
    }
}

// EAN/GS1/UPC/ITF Barcode aus dem HTTP-Request erhalten
$barcode = $_GET['barcode'] ?? '';

// Validierung des Barcodes
$validationResult = validateBarcode($barcode);
if ($validationResult['valid']) {
    echo json_encode(["error_code" => 200, "message" => "Valid Barcode"]);
} else {
    // HTTP-Statuscode bleibt 200, aber eine eigene Fehlermeldung wird zurückgegeben
    echo json_encode(["error_code" => 400, "message" => $validationResult['error']]);
}

?>
