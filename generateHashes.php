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
        //if ($file->getExtension() === 'php') {
            $path = $file->getPathname();
            $hash = hash_file('sha256', $path);
            // Hier wird nun ein Objekt pro Datei erstellt
            $files[] = ['Pfad' => $path, 'Hash' => $hash];
        //}
    }

    return $files;
}

$dir = __DIR__; // Aktuelles Verzeichnis des Skripts
$hashes = generateHashes($dir);

header('Content-Type: application/json');
// Konvertierung des Arrays von Objekten in eine JSON-String-Repräsentation
echo json_encode($hashes);

?>

