<?php
header('Content-Type: application/json; charset=utf-8');

// Configure your OpenWeatherMap API key here, or set environment variable OPENWEATHER_API_KEY
$API_KEY = getenv('OPENWEATHER_API_KEY') ?: '9cc1dc69cc3741b0e72584db83c9924c';

// Helper to send JSON error
function json_error($message, $code = 400) {
	http_response_code($code);
	echo json_encode(['error' => $message]);
	exit;
}

if (empty($_GET['city'])) {
	json_error('City parameter is required', 400);
}

$cityRaw = $_GET['city'];
$city = trim($cityRaw);

// Units default to metric
$units = isset($_GET['units']) && in_array($_GET['units'], ['metric', 'imperial']) ? $_GET['units'] : 'metric';

// Build URLs
$cityEnc = urlencode($city);
$base_current = "https://api.openweathermap.org/data/2.5/weather?q={$cityEnc}&units={$units}&appid={$API_KEY}";
$base_forecast = "https://api.openweathermap.org/data/2.5/forecast?q={$cityEnc}&units={$units}&appid={$API_KEY}";

// Simple cURL function
function curl_get($url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$response = curl_exec($ch);
	$err = curl_error($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return [$response, $httpcode, $err];
}

// Fetch current weather
list($resp_cur, $code_cur, $err_cur) = curl_get($base_current);
if ($err_cur) json_error('Failed to fetch current weather: '.$err_cur, 500);

$data_cur = json_decode($resp_cur, true);
if (!$data_cur || isset($data_cur['cod']) && $data_cur['cod'] == '404') {
	json_error('City not found', 404);
}

// Fetch forecast
list($resp_f, $code_f, $err_f) = curl_get($base_forecast);
if ($err_f) json_error('Failed to fetch forecast: '.$err_f, 500);
$data_f = json_decode($resp_f, true);
if (!$data_f || isset($data_f['cod']) && (int)$data_f['cod'] !== 200) {
	// forecast may return 404 for bad city as well
	json_error('Forecast data unavailable', 500);
}

// Build current structure
$current = [
	'city' => $data_cur['name'] ?? $city,
	'country' => $data_cur['sys']['country'] ?? '',
	'timestamp' => ($data_cur['dt'] ?? time()),
	'temp' => isset($data_cur['main']['temp']) ? (float)$data_cur['main']['temp'] : null,
	'feels_like' => isset($data_cur['main']['feels_like']) ? (float)$data_cur['main']['feels_like'] : null,
	'humidity' => isset($data_cur['main']['humidity']) ? (int)$data_cur['main']['humidity'] : null,
	'pressure' => isset($data_cur['main']['pressure']) ? (int)$data_cur['main']['pressure'] : null,
	'wind_speed' => isset($data_cur['wind']['speed']) ? (float)$data_cur['wind']['speed'] : null,
	'weather' => isset($data_cur['weather'][0]) ? $data_cur['weather'][0] : null
];

// Aggregate forecast by day (min/max temps)
$forecastData = [];
if (isset($data_f['list']) && is_array($data_f['list'])) {
	foreach ($data_f['list'] as $item) {
		$dt = $item['dt'];
		$day = gmdate('Y-m-d', $dt + ($data_cur['timezone'] ?? 0));
		if (!isset($forecastData[$day])) {
			$forecastData[$day] = [
				'date' => $day,
				'min' => $item['main']['temp_min'],
				'max' => $item['main']['temp_max'],
				'icon' => $item['weather'][0]['icon'],
				'description' => $item['weather'][0]['description'],
				'items' => [$item]
			];
		} else {
			$forecastData[$day]['min'] = min($forecastData[$day]['min'], $item['main']['temp_min']);
			$forecastData[$day]['max'] = max($forecastData[$day]['max'], $item['main']['temp_max']);
			$forecastData[$day]['items'][] = $item;
		}
	}
	// Convert associative to numeric, drop today's partial if necessary
	$forecastList = array_values($forecastData);
	// We want the next 5 distinct days (including today)
	$finalForecast = array_slice($forecastList, 0, 5);
} else {
	$finalForecast = [];
}

// Return structured JSON
echo json_encode([
	'current' => $current,
	'forecast' => $finalForecast,
	'units' => $units
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
