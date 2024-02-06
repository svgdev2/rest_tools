<?php
include 'lib/config_auth.php';
// Sicherstellen, dass dieser Header gesetzt ist, um Probleme mit der Textausgabe zu vermeiden
header('Content-Type: text/plain; charset=UTF-8');

// Überprüfen, ob die Anfrage eine POST-Anfrage ist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lesen des CSV-Inhalts aus der POST-Anfrage
    $csvContent = file_get_contents('php://input');

    // Konvertieren des Encodings von Windows-1252 zu UTF-8
    $csvContentUtf8 = mb_convert_encoding($csvContent, 'UTF-8', 'Windows-1252');
    
    // Zurückgeben des konvertierten CSV-Inhalts
    echo $csvContentUtf8;
} else {
    // Fehlermeldung, wenn die Methode nicht POST ist
    echo 'Invalid request method. Please use POST.';
}
?>
