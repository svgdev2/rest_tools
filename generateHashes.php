<?php
include 'lib/config_auth.php';

function generateHashes($dir)
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];

    foreach ($rii as $file) {
        if ($file->isDir()){ 
            continue;
        }
        // Ausschluss des temp-Unterverzeichnisses
        if (strpos($file->getPathname(), DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR) !== false) {
            continue;
        }
        // Optional: Ausschluss nur von PHP-Dateien entfernt, um alle Dateitypen einzuschließen
        $path = $file->getPathname();
        $hash = hash_file('sha256', $path);
        // Erstellung eines Objekts pro Datei
        $files[] = ['Pfad' => $path, 'Hash' => $hash];
    }

    return $files;
}

$dir = __DIR__; // Aktuelles Verzeichnis des Skripts
$hashes = generateHashes($dir);

header('Content-Type: application/json');
// Konvertierung des Arrays in eine JSON-String-Repräsentation
echo json_encode($hashes);

?>
