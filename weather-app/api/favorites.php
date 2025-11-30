<?php
// Simple API wrapper for storage/favorites.json
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$path = __DIR__ . '/../storage/favorites.json';
if (!file_exists($path)) {
    http_response_code(404);
    echo json_encode(['error' => 'favorites not found']);
    exit;
}

$json = file_get_contents($path);
if ($json === false) {
    http_response_code(500);
    echo json_encode(['error' => 'could not read favorites']);
    exit;
}

$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'invalid json in storage', 'details' => json_last_error_msg()]);
    exit;
}

// Return JSON-safe output
echo json_encode($data);
