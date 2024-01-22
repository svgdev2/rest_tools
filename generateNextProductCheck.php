<?php
// Fehlerberichterstattung aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'lib/config_auth.php';

// Header für JSON-Response setzen
header('Content-Type: application/json');

// Daten aus Request-Body lesen
$requestData = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON-Fehler: ' . json_last_error_msg());
    echo 'JSON-Fehler: ' . json_last_error_msg();
    exit;
}

// Daten extrahieren
$mapping = $requestData['mapping'];
$productDirectory = $requestData['productDirectory'];
$turnoverRates = $requestData['turnoverRates'];

// Standard-Umschlagshäufigkeit festlegen
$defaultTurnoverRate = 1;

// Integration der Umschlagshäufigkeiten in das Produktverzeichnis
foreach ($productDirectory as &$product) {
    $product['Umschlagshäufigkeit'] = $defaultTurnoverRate;

    foreach ($turnoverRates as $rate) {
        if (isset($product[$mapping['ean']]) && $product[$mapping['ean']] === $rate['EAN']) {
            $product['Umschlagshäufigkeit'] = $rate['Umschlagshäufigkeit'];
            break;
        }
    }

    if ($product['Umschlagshäufigkeit'] == $defaultTurnoverRate) {
        foreach ($turnoverRates as $rate) {
            if (isset($product[$mapping['hersteller']]) && isset($product[$mapping['artikelnummer']]) &&
                $product[$mapping['hersteller']] === $rate['Hersteller'] &&
                $product[$mapping['artikelnummer']] === $rate['Artikelnummer']) {
                $product['Umschlagshäufigkeit'] = $rate['Umschlagshäufigkeit'];
                break;
            }
        }
    }

    if ($product['Umschlagshäufigkeit'] == $defaultTurnoverRate) {
        foreach ($turnoverRates as $rate) {
            if (isset($product[$mapping['hersteller']]) && isset($product[$mapping['produktname']]) &&
                $product[$mapping['hersteller']] === $rate['Hersteller'] &&
                $product[$mapping['produktname']] === $rate['Produktname']) {
                $product['Umschlagshäufigkeit'] = $rate['Umschlagshäufigkeit'];
                break;
            }
        }
    }
}

function selectProductsForCounting($products, $currentWeek) {
    $selectedProducts = [];
    foreach ($products as $product) {
        $umschlagshaeufigkeit = max(0.02, $product['Umschlagshäufigkeit']);
        $zaehlfrequenz = min(52, max(1, (int)(52 / $umschlagshaeufigkeit)));

        // Konkateniere alle Attribute des Produkts für den Hashwert
        $hashInput = implode('_', array_values($product));

        $hashValue = crc32($hashInput);
        $countWeek = ($hashValue % $zaehlfrequenz) + 1;

        if ($currentWeek == $countWeek) {
            $selectedProducts[] = $product;
        }
    }
    return $selectedProducts;
}

// Aktuelle Woche festlegen (z.B. 20)
$currentWeek = $requestData['currentWeek'];

// Auswahl der Produkte für die aktuelle Woche
$selectedProductsForThisWeek = selectProductsForCounting($productDirectory, $currentWeek);

// Ausgabe der ausgewählten Produkte
echo json_encode($selectedProductsForThisWeek);
?>
