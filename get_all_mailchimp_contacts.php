<?php
include 'lib/config_auth.php';

header('Content-Type: application/json');

function get_total_contacts($api_key, $dc, $list_id, $status = 'all') {
    $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$list_id}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode('user:' . $api_key)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $body = json_decode($response, true);

        if ($status == 'all') {
            // Summiere alle Statuswerte, falls 'all' ausgewählt ist
            return $body['stats']['member_count'] + 
                   $body['stats']['unsubscribe_count'] +
                   $body['stats']['cleaned_count'] + 
                   $body['stats']['pending_count'];
        } else {
            // Gebe nur die Anzahl der 'subscribed' Kontakte zurück, falls ein anderer Status ausgewählt ist
            return $body['stats']['member_count'];
        }
    } else {
        return null;
    }
}


function get_contacts($api_key, $dc, $list_id, $offset, $count, $status = 'subscribed') {
    $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$list_id}/members?offset={$offset}&count={$count}";

    if ($status != 'all') {
        $url .= "&status={$status}";
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode('user:' . $api_key)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        return json_decode($response, true)['members'];
    } else {
        return null;
    }
}

function retrieve_all_contacts($api_key, $dc, $list_id, $onlyEmails = false, $status = 'subscribed') {
    $total_contacts = get_total_contacts($api_key, $dc, $list_id, $status);
    if ($total_contacts === null) {
        return null;
    }

    $all_contacts = [];
    $limit = 1000; // Maximal 1000 Kontakte pro Anfrage

    for ($offset = 0; $offset < $total_contacts; $offset += $limit) {
        $contacts = get_contacts($api_key, $dc, $list_id, $offset, $limit, $status);
        if ($contacts === null) {
            return null;
        }

        if ($onlyEmails) {
            foreach ($contacts as $contact) {
                $all_contacts[] = $contact['email_address'];
            }
        } else {
            $all_contacts = array_merge($all_contacts, $contacts);
        }
    }

    return $all_contacts;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['api_key']) && isset($_GET['dc']) && isset($_GET['list_id'])) {
        $api_key = $_GET['api_key'];
        $dc = $_GET['dc'];
        $list_id = $_GET['list_id'];
        $onlyEmails = isset($_GET['only_emails']) && $_GET['only_emails'] == 'true';
        $status = isset($_GET['status']) ? $_GET['status'] : 'subscribed';

        $contacts = retrieve_all_contacts($api_key, $dc, $list_id, $onlyEmails, $status);
        if ($contacts !== null) {
            echo json_encode(['success' => true, 'data' => $contacts]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Fehler beim Abrufen der Daten.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'API-Key, Datacenter und ListenID sind erforderlich.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Nur GET-Anfragen sind erlaubt.']);
}
