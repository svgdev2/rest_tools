<?php
include 'lib/config_auth.php';

require 'vendor/autoload.php';  // Composer Autoloader

function validateInput($input) {
    if (!ctype_digit($input)) {
        throw new Exception("Der Eingabestring muss nur Ziffern enthalten.");
    }
    
    $length = strlen($input);
    
    if ($length !== 7 && $length !== 12) {
        throw new Exception("Der Eingabestring muss entweder 7 oder 12 Ziffern lang sein.");
    }

    return $length;
}

function calculateCheckDigit($number) {
    $length = strlen($number);
    $sum = 0;

    if ($length == 7) {
        // EAN-8
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$i];
            if ($i % 2 == 0) {
                $sum += $digit * 3;
            } else {
                $sum += $digit;
            }
        }
    } elseif ($length == 12) {
        // EAN-13
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$i];
            if ($i % 2 == 0) {
                $sum += $digit;
            } else {
                $sum += $digit * 3;
            }
        }
    }

    $mod = $sum % 10;
    return $mod == 0 ? 0 : 10 - $mod;
}

function generateEAN($input) {
    try {
        $length = validateInput($input);
        $checkDigit = calculateCheckDigit($input);
        $ean = $input . $checkDigit;

        if ($length == 7) {
            return $ean;
        } else {
            return $ean;
        }
    } catch (Exception $e) {
        return "Fehler: " . $e->getMessage();
    }
}

// Eingabecode aus HTTP-GET-Parameter
if (isset($_GET['code'])) {
    $input = $_GET['code'];
    $result = generateEAN($input);
    echo $result;
} else {
    echo "Fehler: Der Parameter 'code' fehlt.";
}
?>