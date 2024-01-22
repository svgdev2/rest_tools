<?php
include 'lib/config_auth.php';

header('Content-Type: application/json');

// Diese Funktion entfernt optional Leerzeichen und setzt den Text in Kleinbuchstaben um
function prepareString($str, $ignoreCaseAndSpaces) {
    if ($ignoreCaseAndSpaces) {
        return strtolower(str_replace(' ', '', $str));
    }
    return $str;
}

// Diese Funktion berechnet die Distanz zwischen zwei Strings
function calculateWordProximity($str1, $str2, $ignoreCaseAndSpaces) {
    return levenshtein(prepareString($str1, $ignoreCaseAndSpaces), prepareString($str2, $ignoreCaseAndSpaces));
}

// Überprüfung, ob ein Wortpaar in der Ignorierliste enthalten ist
function isPairIgnored($pair, $ignoreList) {
    foreach ($ignoreList as $ignoredPair) {
        if (($pair[0] === $ignoredPair[0] && $pair[1] === $ignoredPair[1]) ||
            ($pair[0] === $ignoredPair[1] && $pair[1] === $ignoredPair[0])) {
            return true;
        }
    }
    return false;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "Ungültiges JSON-Format."]);
    return;
}

$wordList = $input['jsonList'] ?? [];
$text = $input['text'] ?? '';
$threshold = $input['threshold'] ?? 0;
$ignoreCaseAndSpaces = $input['ignoreCaseAndSpaces'] ?? false;
$ignoreList = $input['ignoreList'] ?? []; // Die zu ignorierenden Wortpaare (optional)
$uniqueList = $input['uniqueList'] ?? true; // Neuer Parameter für eindeutige Liste

$matchingWords = [];

// Überprüfung der Wortnähe für jedes Wort in der Liste
foreach ($wordList as $word) {
    $proximity = calculateWordProximity($word, $text, $ignoreCaseAndSpaces);
    if ($proximity <= $threshold && 
        (empty($ignoreList) || !isPairIgnored([$word, $text], $ignoreList))) {
        $matchingWords[] = $word;
    }
}

if ($uniqueList) {
    $matchingWords = array_values(array_unique($matchingWords));
}

echo json_encode(["matchingWords" => $matchingWords]);
?>
