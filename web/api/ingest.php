<?php
/**
 * HoneyGuard — Alert Ingestion API
 * POST /api/ingest.php
 * 
 * Receives alerts from the Python monitoring agent.
 * Requires API key via X-API-Key header.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../auth.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Validate API key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing API key. Set X-API-Key header.']);
    exit;
}

$keyData = Auth::validateApiKey($apiKey);
if (!$keyData) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or revoked API key.']);
    exit;
}

// Parse request body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body.']);
    exit;
}

// Support both single alert and batch
$alerts = isset($input['alerts']) ? $input['alerts'] : [$input];
$inserted = 0;

foreach ($alerts as $alert) {
    $level = strtoupper($alert['level'] ?? 'INFO');
    if (!in_array($level, ['INFO', 'WARNING', 'ALERT', 'CRITICAL'])) {
        $level = 'INFO';
    }

    $fileName = $alert['file_name'] ?? $alert['file'] ?? null;
    $filePath = $alert['file_path'] ?? $alert['path'] ?? null;

    // Extract file name from path if not provided
    if (!$fileName && $filePath) {
        $fileName = basename($filePath);
    }

    Database::execute(
        'INSERT INTO alerts (level, action, file_path, file_name, ip_address, hostname, username, pid, process_name, message, extra_data)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $level,
            $alert['action'] ?? null,
            $filePath,
            $fileName,
            $alert['ip'] ?? $alert['ip_address'] ?? null,
            $alert['hostname'] ?? null,
            $alert['user'] ?? $alert['username'] ?? null,
            $alert['pid'] ?? null,
            $alert['process'] ?? $alert['process_name'] ?? null,
            $alert['message'] ?? null,
            isset($alert['extra']) ? json_encode($alert['extra']) : null,
        ]
    );
    $inserted++;

    // Update honeyfile access count if file_name exists
    if ($fileName) {
        Database::execute(
            'UPDATE honeyfiles SET access_count = access_count + 1, last_accessed = NOW() WHERE file_name = ? AND is_active = 1',
            [$fileName]
        );
    }
}

http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => "$inserted alert(s) ingested.",
    'count' => $inserted
]);
