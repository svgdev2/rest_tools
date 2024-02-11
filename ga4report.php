<?php
include 'lib/config_auth.php';
require 'vendor/autoload.php';

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\ApiCore\ApiException;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $authConfig = $input['authConfig'];
        if ($authConfig === null) {
            throw new Exception('Fehler beim Dekodieren der authConfig JSON-Daten.');
        }

        $propertyId = $input['propertyId'];
        $startDate = $input['startDate'];
        $endDate = $input['endDate'];

        $authConfig['private_key'] = str_replace('\n', "\n", $authConfig['private_key']);
        $credentials = new ServiceAccountCredentials('https://www.googleapis.com/auth/analytics.readonly', $authConfig);
        $client = new BetaAnalyticsDataClient(['credentials' => $credentials]);

        function runReport($client, $propertyId, $startDate, $endDate, $metrics, $dimensions = []) {
            $metricRequests = array_map(function ($metricName) {
                return new \Google\Analytics\Data\V1beta\Metric(['name' => $metricName]);
            }, $metrics);

            $dimensionRequests = array_map(function ($dimensionName) {
                return new \Google\Analytics\Data\V1beta\Dimension(['name' => $dimensionName]);
            }, $dimensions);

            $response = $client->runReport([
                'property' => 'properties/' . $propertyId,
                'dateRanges' => [
                    new \Google\Analytics\Data\V1beta\DateRange([
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]),
                ],
                'metrics' => $metricRequests,
                'dimensions' => $dimensionRequests,
            ]);

            $results = [];
            foreach ($response->getRows() as $row) {
                $metricsResult = [];
                foreach ($row->getMetricValues() as $index => $metricValue) {
                    $metricsResult[$metrics[$index]] = $metricValue->getValue();
                }

                $dimensionsResult = [];
                foreach ($row->getDimensionValues() as $index => $dimensionValue) {
                    if (!empty($dimensions) && isset($dimensions[$index])) {
                        $dimensionsResult[$dimensions[$index]] = $dimensionValue->getValue();
                    }
                }

                $resultItem = [];
                if (!empty($dimensionsResult)) {
                    $resultItem['dimensions'] = $dimensionsResult;
                }
                $resultItem = array_merge($resultItem, $metricsResult);

                $results[] = $resultItem;
            }

            return $results;
        }
        // Gruppe A ohne 'source'
        $metricsA = ['sessions', 'conversions', 'totalUsers', 'engagedSessions', 'sessionsPerUser', 'sessionConversionRate:contact', 'sessionConversionRate:maps', 'sessionConversionRate:newsletter', 'sessionConversionRate:pinterest_pin', 'sessionConversionRate'];
        $responseAWithoutSource = runReport($client, $propertyId, $startDate, $endDate, $metricsA);
		

        // Gruppe B
        $metricsB = ['bounceRate', 'newUsers', 'averageSessionDuration', 'userConversionRate', 'userConversionRate:contact', 'userConversionRate:maps', 'userConversionRate:newsletter', 'userConversionRate:pinterest_pin'];
        $responseB = runReport($client, $propertyId, $startDate, $endDate, $metricsB);

        // Gruppe C mit 'sessionCampaignName'
        $metricsC = ['advertiserAdClicks', 'advertiserAdCostPerClick', 'advertiserAdCostPerConversion', 'advertiserAdCost', 'advertiserAdImpressions'];
        $responseC = runReport($client, $propertyId, $startDate, $endDate, $metricsC, ['sessionCampaignName']);

        // Gruppe D mit 'month'
        $metricsD = ['organicGoogleSearchClicks', 'organicGoogleSearchAveragePosition', 'organicGoogleSearchClickThroughRate', 'organicGoogleSearchImpressions'];
        $responseD = runReport($client, $propertyId, $startDate, $endDate, $metricsD, ['month']);

        // Gruppe A mit 'source'
        //$responseAWithChannel = runReport($client, $propertyId, $startDate, $endDate, $metricsA, ['defaultChannelGroup']);

        // Gruppe A mit 'quelle'
        //$responseAWithSource = runReport($client, $propertyId, $startDate, $endDate, $metricsA, ['source']);

		// Finale Ergebnisse zusammenstellen
		// Hinzufügen von "source": "all" zu "Analytics - ohne Quellen"
		//$modifiedResponseAWithoutSource = array_map(function ($item) {
		//	$item['dimensions'] = ['source' => 'all']; // Setze source auf 'all' für jeden Eintrag
		//	return $item;
		//}, $responseAWithoutSource);

		// Zusammenfassen von "Analytics - ohne Quellen" und "Analytics nach Quellen" in einem Array
		//$analyticsCombined = array_merge($modifiedResponseAWithoutSource, $responseAWithChannel, $responseAWithSource);
		// Zusammenführen von $responseAWithoutSource und $responseB zu $responseMerged
		if (!empty($responseAWithoutSource) && !empty($responseB)) {
			// Da beide Arrays genau ein Element enthalten, greifen wir direkt auf das erste Element zu
			$responseAItem = $responseAWithoutSource[0];
			$responseBItem = $responseB[0];

			// Zusammenführen der Metrik- und Dimensionswerte beider Antworten
			// Überprüfen, ob 'dimensions' vorhanden sind und diese gegebenenfalls zusammenführen
			$mergedDimensions = [];
			if (isset($responseAItem['dimensions']) && isset($responseBItem['dimensions'])) {
				$mergedDimensions = array_merge($responseAItem['dimensions'], $responseBItem['dimensions']);
			} elseif (isset($responseAItem['dimensions'])) {
				$mergedDimensions = $responseAItem['dimensions'];
			} elseif (isset($responseBItem['dimensions'])) {
				$mergedDimensions = $responseBItem['dimensions'];
			}

			// Zusammenführen der Metriken
			$mergedMetrics = array_merge($responseAItem, $responseBItem);

			// Entfernen der 'dimensions' aus den Metriken, falls vorhanden, und Hinzufügen der zusammengeführten Dimensionen
			unset($mergedMetrics['dimensions']);
			if (!empty($mergedDimensions)) {
				$mergedMetrics['dimensions'] = $mergedDimensions;
			}

			$responseMerged = [$mergedMetrics];
		} else {
			// Fallback, falls eine der Antworten leer ist
			$responseMerged = !empty($responseAWithoutSource) ? $responseAWithoutSource : $responseB;
		}

		// Aktualisieren des finalResults-Arrays, um die zusammengeführten Daten zu enthalten
		$finalResults = [
			'Google Analytics' => $responseMerged,
			'Google Ads' => $responseC,
			'Google Search Console' => $responseD,
		];

		echo json_encode(['success' => true, 'data' => $finalResults]);
    } catch (ApiException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'API-Fehler: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Allgemeiner Fehler: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
}
?>
