<?php
include 'lib/config_auth.php';

// Temporäres Verzeichnis relativ zum Skriptverzeichnis erstellen
$tempDir = __DIR__ . '/temp';

// Überprüfen, ob es sich um einen POST-Request handelt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Den eingehenden JSON-Body auslesen
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad Request: JSON data could not be decoded']);
        exit;
    }

    if (!isset($data['file'], $data['format'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: file or format']);
        exit;
    }

    // Base64-String und Format extrahieren
    $base64String = $data['file'];
    $format = $data['format']; // 'zip', 'tar.gz', 'gz', etc.

    // Base64-String in eine temporäre Datei dekodieren und speichern
    $decodedData = base64_decode($base64String);
    if ($decodedData === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Base64 decoding failed']);
        exit;
    }
    $tmpFilePath = tempnam($tempDir, 'upload_') . '.' . $format;
    if (file_put_contents($tmpFilePath, $decodedData) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to write to temporary file']);
        exit;
    }

    // Dateien entpacken
    try {
        $extractedFiles = extractFiles($tmpFilePath, $format, $tempDir);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to extract files: ' . $e->getMessage()]);
        exit;
    }

    // Temporäre Datei löschen
    unlink($tmpFilePath);

    // Antwort vorbereiten
    $response = ['files' => []];

    foreach ($extractedFiles as $file) {
        $encodedContent = base64_encode(file_get_contents($file));
        $response['files'][] = [
            'path' => $file,
            'content' => $encodedContent
        ];
        // Entpackte Datei löschen
        unlink($file);
    }

    // Antwort-JSON ausgeben
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

function addFilesRecursively($directory, &$extractedFiles) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        if ($fileinfo->isFile()) {
            $extractedFiles[] = $fileinfo->getRealPath();
        }
    }
}


function extractFiles($filePath, $format, $tempDir) {
    $extractedFiles = [];
    $extractToPath = $tempDir . '/' . uniqid('extract_', true);

    // Verzeichnis erstellen, falls es nicht existiert
    if (!file_exists($extractToPath)) {
        if (!mkdir($extractToPath, 0777, true)) {
            throw new Exception("Failed to create extraction directory");
        }
    }

    switch ($format) {
        case 'zip':
			$zip = new ZipArchive();
			if ($zip->open($filePath) !== TRUE) {
				throw new Exception("Failed to open ZIP file");
			}
			$zip->extractTo($extractToPath);
			$zip->close();

			// Sammle die Pfade der extrahierten Dateien
			addFilesRecursively($extractToPath, $extractedFiles);
			break;
		case 'tar.gz':
            $phar = new PharData($filePath);
            $phar->decompress(); // Erst .gz dekomprimieren

            $tarPath = str_replace('.gz', '', $filePath); // Pfad zur .tar-Datei ändern
            $phar = new PharData($tarPath);
            $phar->extractTo($extractToPath);
            unlink($tarPath); // Temporäre .tar-Datei löschen
            break;
        case 'gz':
            $bufferSize = 4096; // Größe des Lesebuffers
            $decompressedFile = $extractToPath . '/' . uniqid() . '.decompressed'; // Zielname der dekomprimierten Datei
            $filegz = gzopen($filePath, 'rb'); // Öffnet die .gz-Datei zum Lesen
            $fileOut = fopen($decompressedFile, 'wb'); // Öffnet die Zieldatei zum Schreiben

            if (!$filegz || !$fileOut) {
                throw new Exception("Failed to open files for gzip decompression");
            }

            while (!gzeof($filegz)) {
                fwrite($fileOut, gzread($filegz, $bufferSize)); // Liest und schreibt in 4096-Byte-Blöcken
            }

            gzclose($filegz);
            fclose($fileOut);

            $extractedFiles = [$decompressedFile]; // Setzt die dekomprimierte Datei als extrahiert
            break;
        default:
            throw new Exception("Unsupported format: $format");
    }

    // Filtern, um nur Dateipfade zurückzugeben
    $extractedFiles = array_filter($extractedFiles, function ($file) use ($extractToPath) {
        return !in_array($file, ['.', '..']);
    });

    return $extractedFiles;
}
?>
