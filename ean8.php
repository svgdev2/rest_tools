<?php
include 'lib/config_auth.php';

require 'vendor/autoload.php';  // Composer Autoloader

$barcode_input = $_GET['barcode'];

if (!preg_match('/^\d{7}$/', $barcode_input)) {
    http_response_code(400); 
    echo "Forbidden";
    exit;
}

$generator = new Picqer\Barcode\BarcodeGeneratorPNG();
$barcode = $generator->getBarcode($barcode_input, $generator::TYPE_EAN_8);

header('Content-Type: image/png');
echo $barcode;
?>
