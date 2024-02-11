<?php
include 'lib/config_auth.php';

function convertCurrencyToNumber($input) {
    $cleanedInput = str_replace(['$', '€', '£', '¥'], '', $input);
    $cleanedInput = str_replace([','], ['.'], $cleanedInput);
    $number = floatval($cleanedInput);

    if (!is_numeric($number)) {
        throw new Exception("Ungültige Währungseingabe: $input");
    }

    return $number;
}

function transformValue($value) {
    if (empty($value)) {
        return null;
    }

    if (strtolower($value) === 'ja') {
        return true;
    }

    if (strtolower($value) === 'nein') {
        return false;
    }

    if (preg_match("/^(\d{2})\.(\d{2})\.(\d{4})(?: (\d{2}):(\d{2})(?::(\d{2}))?)?$/", $value, $matches)) {
        if (isset($matches[4])) {
            // Konvertiert das Datum und die Uhrzeit in das ISO 8601 Format
            return date("c", mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[1], $matches[3]));
        } else {
            // Konvertiert das Datum in das ISO 8601 Format ohne Uhrzeit
            return date("Y-m-d", mktime(0, 0, 0, $matches[2], $matches[1], $matches[3])) . "T00:00:00+00:00";
        }
    }
    //if (preg_match("/^(\d{2})\.(\d{2})\.(\d{4})(?: (\d{2}):(\d{2}):(\d{2}))?$/", $value, $matches)) {
    //    if (isset($matches[4])) {
    //        return date("Y-m-d\TH:i:s.0000000", mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[1], $matches[3]));
    //    } else {
    //        return date("Y-m-d\TH:i:s.0000000", mktime(0, 0, 0, $matches[2], $matches[1], $matches[3]));
    //    }
    //}

    if (strpos($value, '%') !== false) {
        $value = str_replace('%', '', $value);
        $value = floatval($value) / 100;
        return $value;
    }

    if (is_numeric(str_replace(['$', '€', '£', '¥', ',', '%'], ['', '', '', '', '', ''], $value))) {
        try {
            return convertCurrencyToNumber($value);
        } catch (Exception $e) {
            throw new Exception("Fehler bei der Währungsumwandlung: " . $e->getMessage());
        }
    }

    return $value;
}

function convertToType($value, $type) {
    // Prüfung auf `null` statt `empty()`, um zu verhindern, dass '0' als leer betrachtet wird.
    if ($value === null) {
        return null;
    }

    try {
        switch ($type) {
            case 'Boolean':
                $valueLower = strtolower($value);
                if ($valueLower === 'ja' || $valueLower === 'true') {
                    return true;
                } elseif ($valueLower === 'nein' || $valueLower === 'false') {
                    return false;
                } else {
                    throw new Exception("Ungültiger boolescher Wert: $value");
                }
            case 'Date':
            case 'DateTime':
            case 'Time':
                return transformValue($value);
            case 'Int':
                return intval($value);
            case 'Float':
                return floatval($value);
            default:
                return (string)$value;
        }
    } catch (Exception $e) {
        throw new Exception("Fehler bei der Typkonvertierung: " . $e->getMessage());
    }
}

function transformJson($data, $mapping) {
    try {
        foreach ($data as $index => $item) {
            foreach ($item as $key => $value) {
                if (isset($mapping[$key])) {
                    $data[$index][$key] = convertToType($value, $mapping[$key]);
                } else {
                    $data[$index][$key] = empty($value) ? null : $value;
                }
            }
        }
        return $data;
    } catch (Exception $e) {
        throw new Exception("Fehler bei der JSON-Transformation: " . $e->getMessage());
    }
}

$inputJson = file_get_contents('php://input');
$utf8EncodedJson = mb_convert_encoding($inputJson, 'UTF-8', mb_detect_encoding($inputJson, 'UTF-8, ISO-8859-1', true));
$json_data = json_decode($utf8EncodedJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON input', 'json_error' => json_last_error_msg()]);
    exit();
}

try {
    $mapping = $json_data['Mapping'] ?? [];
    $data = $json_data['data'] ?? [];
    $transformed_data = transformJson($data, $mapping);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($transformed_data);
} catch (Exception $e) {
    echo json_encode(['error' => 'Processing error', 'message' => $e->getMessage()]);
    exit();
}

?>
