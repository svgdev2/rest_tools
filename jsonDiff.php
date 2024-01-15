<?php
include 'lib/config_auth.php';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['array1'], $input['array2'], $input['attributes'])) {
    http_response_code(400);
    die('Array1, Array2 und attributes sind erforderlich');
}

$array1 = $input['array1'];
$array2 = $input['array2'];
$attributes = $input['attributes'];
$operation = isset($input['operation']) ? $input['operation'] : 'union';
$priority = isset($input['priority']) ? $input['priority'] : 'array1';

function findElementInArray($element, $array, $attributes) {
    foreach ($array as $item) {
        $match = true;
        foreach ($attributes as $attribute) {
            if (!isset($element[$attribute], $item[$attribute]) || $element[$attribute] !== $item[$attribute]) {
                $match = false;
                break;
            }
        }
        if ($match) {
            return $item;
        }
    }
    return null;
}

function arrayOperation($array1, $array2, $attributes, $operation, $priority) {
    $result = [];

    switch ($operation) {
        case 'union':
            $union = array_merge($array1, $array2);
            $result = array_unique($union, SORT_REGULAR);
            break;

        case 'intersection':
            foreach ($array1 as $item1) {
                $matchedItem = findElementInArray($item1, $array2, $attributes);
                if ($matchedItem !== null) {
                    $mergedItem = ($priority === 'array1') ? array_merge($matchedItem, $item1) : array_merge($item1, $matchedItem);
                    $result[] = $mergedItem;
                }
            }
            break;

        case 'difference':
            foreach ($array1 as $item1) {
                if (findElementInArray($item1, $array2, $attributes) === null) {
                    $result[] = $item1;
                }
            }
            break;

        case 'symmetric_difference':
            foreach (array_merge($array1, $array2) as $item) {
                if (findElementInArray($item, $array1, $attributes) === null || findElementInArray($item, $array2, $attributes) === null) {
                    $result[] = $item;
                }
            }
            $result = array_unique($result, SORT_REGULAR);
            break;
    }

    return $result;
}

$result = arrayOperation($array1, $array2, $attributes, $operation, $priority);

header('Content-Type: application/json');
echo json_encode($result);
?>
