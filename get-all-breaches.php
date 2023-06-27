<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://haveibeenpwned.com/api/v3/breaches");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$headers = [
	'hibp-api-key: 3a3ee5eec00e4dcb8e76962004246d2d',
	'User-Agent: FMK test app'
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$server_output = curl_exec($ch);

$breaches = json_decode($server_output);

curl_close($ch);

file_put_contents('data/breaches.json', json_encode($breaches, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));

echo 'Got ' . count($breaches) . ' breaches'. PHP_EOL;