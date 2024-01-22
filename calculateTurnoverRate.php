<?php
include 'lib/config_auth.php';

// Header für JSON-Response setzen
header('Content-Type: application/json');

// Funktion zur Berechnung der Umschlagshäufigkeit
function calculateTurnoverRate($jsonData, $mapping) {
    $products = [];

    foreach ($jsonData as $data) {
        $stockValue = (int)$data->{$mapping->bestandnachveranderung};
        $quantity = (int)$data->{$mapping->anzahl};
        $date = $data->{$mapping->datum};
        $manufacturer = $data->{$mapping->hersteller};
        $productName = $data->{$mapping->produktname};
        $ean = $data->{$mapping->ean};
        $articleNumber = $data->{$mapping->artikelnummer};

        $key = $manufacturer . '_' . $productName . '_' . $ean . '_' . $articleNumber;
        $products[$key]['stock'][] = ['value' => $stockValue, 'quantity' => $quantity, 'date' => $date];
        if ($quantity < 0) { // Negativ für Ausgänge
            $products[$key]['sold'][] = abs($quantity);
        }
    }

    $turnoverRates = [];
    foreach ($products as $key => $product) {
        // Sortieren nach Datum
        usort($product['stock'], function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        // Anfangs- und Endbestand festlegen
        $initialStock = $product['stock'][0]['value'] < 0 ? abs($product['stock'][0]['quantity']) : $product['stock'][0]['value'];
        $finalStock = end($product['stock'])['value'] < 0 ? 0 : end($product['stock'])['value'];

        // Durchschnittlicher Lagerbestand
        $averageStock = ($initialStock + $finalStock) / 2;

        // Gesamtverkaufte Stückzahl
        $totalSold = array_sum($product['sold'] ?? []);

        // Berechnung der Umschlagshäufigkeit
        $turnoverRate = $averageStock != 0 ? $totalSold / $averageStock : 0;

        list($manufacturer, $productName, $ean, $articleNumber) = explode('_', $key);
        $turnoverRates[] = [
            'Hersteller' => $manufacturer,
            'Produktname' => $productName,
            'EAN' => $ean,
            'Artikelnummer' => $articleNumber,
            'Umschlagshäufigkeit' => $turnoverRate
        ];
    }

    return $turnoverRates;
}

// JSON-Daten aus POST-Body lesen
$requestData = json_decode(file_get_contents('php://input'));
$data = $requestData->data;
$mapping = $requestData->mapping;

// Umschlagshäufigkeit berechnen
$turnoverRates = calculateTurnoverRate($data, $mapping);

// JSON-Response zurückgeben
echo json_encode($turnoverRates);
?>
