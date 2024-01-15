<?php
// Header, um CORS-Probleme zu vermeiden (nicht für den Produktionsbetrieb empfohlen)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

include 'lib/config_auth.php';


// Überprüfen, ob die Anfrage eine POST-Anfrage ist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Auslesen der Base64-kodierten Datei aus der POST-Anfrage
    $base64File = file_get_contents('php://input');
    
    // Dekodieren des Base64-Strings
    $decodedFile = base64_decode($base64File);
    
    // Temporäre Datei mit Timestamp erstellen
    $timestamp = time();
    $tempFileName = "temp/file_" . $timestamp;
    file_put_contents($tempFileName, $decodedFile);

    // ZIP-Datei erstellen und verschlüsseln
    $zipFileName = "temp/zip_" . $timestamp;
    $zip = new ZipArchive();
    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($tempFileName, 'file');
        $zip->setPassword("RpF.4c^H$6_ko*3%zo$6");
        $zip->close();
    } else {
        echo json_encode(['error' => 'Could not create ZIP file']);
        exit();
    }

    // ZIP-Datei als Base64 kodieren
    $base64Zip = base64_encode(file_get_contents($zipFileName));
    
    // Temporäre Dateien löschen
    unlink($tempFileName);
    unlink($zipFileName);

    // Base64-ZIP-Datei zurückgeben
    echo json_encode(['zip_file' => $base64Zip]);
} else {
    // Fehlermeldung, wenn die Methode nicht POST ist
    echo json_encode(['error' => 'Invalid request method']);
}
?>
