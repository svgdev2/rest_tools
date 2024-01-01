<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$config = parse_ini_file('config/config.ini');
$apiKey = $config['api_key'];
$temp_dir = $config['temp_path'];

function getReceivedApiKey() {
    $headers = apache_request_headers();
    $apiKeyHeader = 'X-Api-Key';
    if (!isset($headers[$apiKeyHeader])) {
        $apiKeyHeader = 'X-API-Key'; // Alternative Schreibweise
    }

    return isset($headers[$apiKeyHeader]) ? $headers[$apiKeyHeader] : 'Nicht vorhanden';
}

function isApiKeyValid($apiKey) {
    $receivedApiKey = getReceivedApiKey();
    return $receivedApiKey === $apiKey;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isApiKeyValid($apiKey)) {
        http_response_code(401);
		echo "Unauthorized";
        exit;
    }

    if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
        http_response_code(415);
        echo "Ungültiger Content-Type. Erwartet 'application/json'.";
        exit;
    }

    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    $maxFileSize = 5 * 1024 * 1024;
    if ($contentLength > $maxFileSize) {
        http_response_code(413);
        echo "Die Dateigröße überschreitet das Limit.";
        exit;
    }

    $jsonContent = file_get_contents('php://input');
    $data = json_decode($jsonContent, true);

    if (isset($data['content'])) {
        $base64Content = $data['content'];
        $excelContent = base64_decode($base64Content);

        $timestamp = time();
        $filePath = "{$temp_dir}/temp_excel_{$timestamp}.xlsx";
        file_put_contents($filePath, $excelContent);

        try {
            $reader = new Xlsx();
            $spreadsheet = $reader->load($filePath);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            http_response_code(415);
            echo "Ungültiges Dateiformat.";
            unlink($filePath);
            exit;
        }

        chmod($filePath, 0644);

        $headers = [];
        $firstRow = true;
        $columnsToRemove = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    // Remove line breaks from all cells
                    $cellValue = $cell->getValue();
					$cellValue = $cell->getValue();
					// Konvertiere den Zellenwert in UTF-8
					$cellValueUtf8 = mb_convert_encoding($cellValue, 'UTF-8', mb_detect_encoding($cellValue, 'UTF-8, Windows-1252, ISO-8859-1', true));
                    $cellValueUtf8 = str_replace(["\r\n", "\r", "\n"], ' ', $cellValueUtf8); 
                    $cell->setValue($cellValueUtf8);
                }
                
                if ($firstRow) {
                    foreach ($row->getCellIterator() as $cell) {
                        $header = $cell->getValue();
                        if (empty($header)) {
                            // Mark the column for removal by storing its alphabetic representation (e.g., A, B, C, ...)
                            $columnsToRemove[] = $cell->getColumn();
                        } else {
                            $headers[] = $header;
                        }
                    }

                    // Reverse the columns array to ensure we're removing columns from right to left.
                    $columnsToRemove = array_reverse($columnsToRemove);

                    // Remove the columns without headers
                    foreach ($columnsToRemove as $columnToRemove) {
                        $sheet->removeColumn($columnToRemove);
                    }

                    $firstRow = false;
                    continue;
                }
            }
        }
		
		// Setze den Content-Type-Header auf CSV mit UTF-8 Kodierung
		header('Content-Type: text/csv; charset=utf-8');

        $csvWriter = IOFactory::createWriter($spreadsheet, 'Csv');
        $csvWriter->setDelimiter(';'); // Set the delimiter to semicolon
        $csvWriter->setEnclosure('"'); // Enclose values with double quotes
        $csvStream = fopen('php://output', 'w');
        $csvWriter->save($csvStream);
        fclose($csvStream);
        
        unlink($filePath); // Ensure the temporary Excel file is always deleted

    } else {
        http_response_code(400);
        echo "Bad Request. 'content' Attribut fehlt im JSON.";
    }

} else {
    http_response_code(405);
    echo "Methode nicht erlaubt. Bitte HTTP POST verwenden.";
}

?>
