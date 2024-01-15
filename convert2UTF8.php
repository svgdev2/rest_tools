<?php
include 'lib/config_auth.php';

// Überprüfen, ob die Anfrage eine POST-Anfrage ist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Auslesen des Textes aus der POST-Anfrage
    $text = file_get_contents('php://input');
    
    // Überprüfen des Encodings
    $currentEncoding = mb_detect_encoding($text, 'UTF-8, ISO-8859-1', true);
    
    // Wenn das Encoding nicht UTF-8 ist, konvertieren wir es
    if ($currentEncoding !== 'UTF-8') {
        $text = mb_convert_encoding($text, 'UTF-8', $currentEncoding);
    }
    
    // Zurückgeben des Textes (nun sicherlich in UTF-8)
    echo json_encode(['text' => $text, 'original_encoding' => $currentEncoding]);
} else {
    // Fehlermeldung, wenn die Methode nicht POST ist
    echo json_encode(['error' => 'Invalid request method']);
}
?>
