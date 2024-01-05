<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

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

function mapAttributesToColumns($object, $mapping) {
    $mappedObject = new stdClass();
    foreach ($object as $attribute => $value) {
        if (isset($mapping[$attribute])) {
            $mappedAttribute = $mapping[$attribute];
            $mappedObject->$mappedAttribute = $value;
        } else {
            $mappedObject->$attribute = $value;
        }
    }
    return $mappedObject;
}

function createDataverseRequest($method, $object, $tableName, $recordId, $changesetBoundary, $dataverseUrl, $mapping, $contentId) {
    if ($mapping !== null && ($method === 'POST' || $method === 'PATCH')) {
        $object = mapAttributesToColumns($object, $mapping);
    }
    $request = "";
    $request .= "--$changesetBoundary\r\n";
    $request .= "Content-Type: application/http\n";
    $request .= "Content-Transfer-Encoding: binary\n\n";

    $url = "$dataverseUrl/api/data/v9.2/$tableName";
    if ($recordId !== null && ($method === 'PATCH' || $method === 'DELETE')) {
        $url .= "($recordId)";
    }

    $request .= "$method $url HTTP/1.1\n";
    $request .= "Content-Type: application/json;type=entry\n";
    $request .= "Content-ID: $contentId\n"; // Content-ID Header hinzufügen
    $request .= "OData-MaxVersion: 4.0\n";
    $request .= "OData-Version: 4.0\n\n";

    if ($method !== 'DELETE') {
        $request .= json_encode($object) . "\n\n";
    } else {
        $request .= "\r\n";
    }

    return $request;
}

function processDataverseOperations($createArray, $updateArray, $deleteArray, $tableName, $dataverseUrl, $idAttribute, $mapping = null) {
    $batchBoundary = 'batch_' . bin2hex(random_bytes(16));
    $changesetBoundary = 'changeset_' . bin2hex(random_bytes(16));
	
    $counter = 0;
    $contentId = 1; // Initialisierung des Content-ID Zählers
    $batchRequests = [];

    $batchRequest = "--$batchBoundary\r\n";
    $batchRequest .= "Content-Type: multipart/mixed; boundary=$changesetBoundary\r\n\r\n";

    //Neu:Fügen Sie alle Operationen zum gleichen Changeset hinzu
    foreach ($createArray as $object) {
        unset($object[$idAttribute]);
        $batchRequest .= createDataverseRequest('POST', $object, $tableName, null, $changesetBoundary, $dataverseUrl, $mapping, $contentId);

        $counter++;
		$contentId++;

        if ($counter >= 1000) {
            // Schließe den aktuellen Batch und starte einen neuen
            $batchRequest .= "--$changesetBoundary--\r\n";
            $batchRequest .= "--$batchBoundary--\r\n";
            $batchRequests[] = array("key" => $batchBoundary, "value" => $batchRequest);

            $batchBoundary = 'batch_' . bin2hex(random_bytes(16));
            $changesetBoundary = 'changeset_' . bin2hex(random_bytes(16));
            $batchRequest = "--$batchBoundary\r\n";
            $batchRequest .= "Content-Type: multipart/mixed; boundary=$changesetBoundary\r\n\r\n";
            $counter = 0;
        }
    }

    foreach ($updateArray as $object) {
        $recordId = $object[$idAttribute];
        unset($object[$idAttribute]);
        $batchRequest .= createDataverseRequest('PATCH', $object, $tableName, $recordId, $changesetBoundary, $dataverseUrl, $mapping, $contentId);

        $counter++;
		$contentId++;

        if ($counter >= 1000) {
            // Schließe den aktuellen Batch und starte einen neuen
            $batchRequest .= "--$changesetBoundary--\r\n";
            $batchRequest .= "--$batchBoundary--\r\n";
            $batchRequests[] = array("key" => $batchBoundary, "value" => $batchRequest);

            $batchBoundary = 'batch_' . bin2hex(random_bytes(16));
            $changesetBoundary = 'changeset_' . bin2hex(random_bytes(16));
            $batchRequest = "--$batchBoundary\r\n";
            $batchRequest .= "Content-Type: multipart/mixed; boundary=$changesetBoundary\r\n\r\n";
            $counter = 0;
        }
    }

    foreach ($deleteArray as $object) {
        $recordId = $object[$idAttribute];
        $batchRequest .= createDataverseRequest('DELETE', new stdClass(), $tableName, $recordId, $changesetBoundary, $dataverseUrl, $mapping, $contentId);

        $counter++;
		$contentId++;

        if ($counter >= 1000) {
            // Schließe den aktuellen Batch und starte einen neuen
            $batchRequest .= "--$changesetBoundary--\r\n";
            $batchRequest .= "--$batchBoundary--\r\n";
            $batchRequests[] = array("key" => $batchBoundary, "value" => $batchRequest);

            $batchBoundary = 'batch_' . bin2hex(random_bytes(16));
            $changesetBoundary = 'changeset_' . bin2hex(random_bytes(16));
            $batchRequest = "--$batchBoundary\r\n";
            $batchRequest .= "Content-Type: multipart/mixed; boundary=$changesetBoundary\r\n\r\n";
            $counter = 0;
        }
    }

    if ($counter > 0) {
        // Schließe den letzten Batch ab
        $batchRequest .= "--$changesetBoundary--\r\n";
        $batchRequest .= "--$batchBoundary--\r\n";
        $batchRequests[] = array("key" => $batchBoundary, "value" => $batchRequest);
    }

    return $batchRequests;
}


$jsonInput = file_get_contents('php://input');
$utf8EncodedInput = mb_convert_encoding($jsonInput, 'UTF-8', 'UTF-8');

$data = json_decode($utf8EncodedInput, true);
if ($data === null) {
    echo "Fehler beim Dekodieren des JSON-Strings: " . json_last_error_msg();
    exit;
}

$dataverseUrl = $data['dataverseUrl'];
$tableName = $data['tableName'];
$createArray = $data['createArray'] ?? [];
$updateArray = $data['updateArray'] ?? [];
$deleteArray = $data['deleteArray'] ?? [];
$idAttribute = $data['idAttribute'] ?? 'id'; // Standardmäßig 'id', falls nicht im JSON angegeben
$mapping = $data['mapping'] ?? []; // Mapping hinzufügen, Standard ist ein leeres Array

$httpMethod = $_SERVER['REQUEST_METHOD'];

if (!isApiKeyValid($apiKey)) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$batchRequests = processDataverseOperations($createArray, $updateArray, $deleteArray, $tableName, $dataverseUrl, $idAttribute, $mapping);
echo json_encode($batchRequests);

?>
