<?php
include 'lib/config_auth.php';

// Setze den HTTP-Response-Code auf 200 OK
http_response_code(200);

// Aktuelles Datum und Uhrzeit
$currentTime = date('Y-m-d H:i:s');

// PHP-Version
$phpVersion = phpversion();

// Überprüfen, ob das Unterverzeichnis "temp" existiert
$tempDirExists = is_dir('temp');

// Überprüfen, ob die Verzeichnisse "contact", "misc", "mtasts" im übergeordneten Verzeichnis existieren
$parentDir = dirname(__DIR__); // Übergeordnetes Verzeichnis des aktuellen Skripts
$contactDirExists = is_dir($parentDir . '/contact');
$miscDirExists = is_dir($parentDir . '/misc');
$mtastsDirExists = is_dir($parentDir . '/mtasts');

// Antwort als JSON
$response = [
    'status' => 'OK',
    'timestamp' => $currentTime,
    'php_version' => $phpVersion,
    'temp_dir_exists' => $tempDirExists,
    'parent_contact_dir_exists' => $contactDirExists,
    'parent_misc_dir_exists' => $miscDirExists,
    'parent_mtasts_dir_exists' => $mtastsDirExists
];

// JSON zurückgeben
header('Content-Type: application/json');
echo json_encode($response);
?>
