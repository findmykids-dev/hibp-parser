<?php
include 'functions.php';

$country_code = 'JP';

$collected_breaches = [];

$emails_list= explode("\n", file_get_contents("data/accounts-to-check-$country_code.csv"));

$emails = array_unique(array_values($emails_list), SORT_STRING);

sort($emails);

$emails_count = count($emails);

foreach ($emails as $i => $email) {

	if (file_exists("data/checked-accounts/$country_code/$email.json")) {
		$breaches = json_decode(file_get_contents("data/checked-accounts/$country_code/$email.json"), true);
	} else {
		// API limit - 1 request per 1500 milliseconds
		usleep(1000 * 1000);
		$breaches = checkAccount($email);
	}

	$collected_breaches[$email] = $breaches;

	file_put_contents("data/checked-accounts/$country_code/$email.json", json_encode($breaches, JSON_PRETTY_PRINT));

	echo ($i + 1). "/$emails_count Checked $email - " . (is_array($breaches) ? count($breaches) : '0')  . " breaches" . PHP_EOL;

	collectAndSaveStats($collected_breaches, $country_code);
}

file_put_contents("data/checked-accounts-$country_code.json", json_encode($collected_breaches, JSON_PRETTY_PRINT));

collectAndSaveStats($collected_breaches, $country_code);