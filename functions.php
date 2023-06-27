<?php

function getBreachByName ($name, $breaches = null) {
	if (!$breaches) {
		$breaches = json_decode(file_get_contents('data/breaches.json'));
	}

	foreach ($breaches as $breach) {
		if ($breach['Name'] == $name) {
			return $breach;
		}
	}

	return null;
}

function checkAccount($email) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://haveibeenpwned.com/api/v3/breachedaccount/$email");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);

	$headers = [
		'hibp-api-key: 23b9a241ff8e4f9e9b17795a105c26d4',
		'User-Agent: FMK test app'
	];

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$response = curl_exec($ch);

	curl_close ($ch);

	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$response_headers = get_headers_from_curl_response($response);
	$server_output = json_decode(substr($response, $header_size), 1);;

	if (isset($server_output['statusCode'])) {
		if ($server_output['statusCode'] == 429) {
			echo 'Too many requests, sleeping sec: ' . $response_headers['retry-after'] . PHP_EOL;
			sleep($response_headers['retry-after']);

			return checkAccount($email);
		}
	}

	return $server_output;
}


function get_headers_from_curl_response($response) {
	$headers = array();

	$header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

	foreach (explode("\r\n", $header_text) as $i => $line)
		if ($i === 0)
			$headers['http_code'] = $line;
		else
		{
			list ($key, $value) = explode(': ', $line);

			$headers[$key] = $value;
		}

	return $headers;
}


function collectAndSaveStats($collected_breaches, $country_code) {

	$stats = [];

	$stats['emails_total'] = count(array_keys($collected_breaches));
	$stats['emails_breached'] = 0;
	$stats['breaches_total'] = 0;

	$stats['breaches_per_user'] = [
		'0' => 0
	];

	foreach ($collected_breaches as $breaches) {
		if (is_array($breaches)) {
			$stats['breaches_total'] += count($breaches);

			$stats['emails_breached'] += 1;

			!isset($stats['breaches_per_user'][strval(count($breaches))]) && ($stats['breaches_per_user'][strval(count($breaches))] = 0);

			$stats['breaches_per_user'][strval(count($breaches))] += 1;
		}
		else {
			$stats['breaches_per_user']['0'] += 1;
		}
	}

	$stats['breaches_avg'] = $stats['breaches_total'] / $stats['emails_total'];

	ksort($stats['breaches_per_user']);

	file_put_contents("data/stats-$country_code.json", json_encode($stats, JSON_PRETTY_PRINT));
}