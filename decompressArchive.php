<?php
include 'lib/config_auth.php';

// Überprüfen, ob es sich um einen POST-Request handelt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Den eingehenden JSON-Body auslesen
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

    if ($data && isset($data['file'], $data['format'])) {
        // Base64-String und Format extrahieren
        $base64String = $data['file'];
        $format = $data['format']; // 'zip', 'tar.gz', etc.

        // Base64-String in eine temporäre Datei dekodieren und speichern
        $decodedData = base64_decode($base64String);
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'upload_') . '.' . $format;
        file_put_contents($tmpFilePath, $decodedData);

        // Dateien entpacken
        $extractedFiles = extractFiles($tmpFilePath, $format);

        // Temporäre Datei löschen
        unlink($tmpFilePath);

        // Antwort vorbereiten
        $response = [
            'files' => []
        ];

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
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

function extractFiles($filePath, $format) {
    $extractedFiles = [];
    $extractToPath = sys_get_temp_dir() . '/' . uniqid('extract_', true);

    if ($format == 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === TRUE) {
            $zip->extractTo($extractToPath);
            $zip->close();
            $extractedFiles = scandir($extractToPath);
        }
    } elseif ($format == 'tar.gz') {
        // Tar.gz-Archive extrahieren
        $phar = new PharData($filePath);
        $phar->decompress(); // Erst .gz dekomprimieren

        $tarPath = str_replace('.gz', '', $filePath); // Pfad zur .tar-Datei
        $phar = new PharData($tarPath);
        $phar->extractTo($extractToPath);
        unlink($tarPath); // Temporäre .tar-Datei löschen
        $extractedFiles = scandir($extractToPath);
    }

    // Filtern, um nur Dateipfade zurückzugeben
    $extractedFiles = array_filter($extractedFiles, function ($file) use ($extractToPath) {
        return !in_array($file, ['.', '..']);
    });

    $extractedFiles = array_map(function ($file) use ($extractToPath) {
        return $extractToPath . '/' . $file;
    }, $extractedFiles);

    return $extractedFiles;
}

?>
