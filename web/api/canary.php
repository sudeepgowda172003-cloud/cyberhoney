<?php
/**
 * HoneyGuard — Canary Token API
 * GET /api/canary.php?id=<token_id>
 * 
 * Logs an attacker's IP and User-Agent when a Canary Token file is opened.
 * Returns a 1x1 transparent GIF (tracking pixel).
 */

require_once __DIR__ . '/../config.php';

// Disable error reporting to prevent information leakage
error_reporting(0);

$tokenId = $_GET['id'] ?? 'unknown_token';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Log the CRITICAL alert directly to the database
Database::execute(
    'INSERT INTO alerts (level, action, file_name, ip_address, message, extra_data)
     VALUES (?, ?, ?, ?, ?, ?)',
    [
        'CRITICAL',
        'CANARY_TRIGGERED',
        'Canary Token: ' . $tokenId,
        $ipAddress,
        "🚨 STOLEN FILE OPENED! A canary token was triggered by a remote attacker.",
        json_encode(['user_agent' => $userAgent])
    ]
);

// Serve a 1x1 transparent GIF tracking pixel so the attacker sees nothing
header('Content-Type: image/gif');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Hex representation of a 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
exit;
