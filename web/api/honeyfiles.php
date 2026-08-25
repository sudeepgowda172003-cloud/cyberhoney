<?php
/**
 * HoneyGuard — Honeyfiles Registry API
 * GET    /api/honeyfiles.php        — list honeyfiles
 * POST   /api/honeyfiles.php        — register a new honeyfile
 * DELETE /api/honeyfiles.php?id=X   — deactivate a honeyfile
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../auth.php';

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $honeyfiles = Database::query(
            'SELECT * FROM honeyfiles ORDER BY is_active DESC, access_count DESC'
        );
        echo json_encode(['honeyfiles' => $honeyfiles]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['file_name']) || empty($input['file_path'])) {
            http_response_code(400);
            echo json_encode(['error' => 'file_name and file_path are required.']);
            break;
        }
        Database::execute(
            'INSERT INTO honeyfiles (file_name, file_path, file_type) VALUES (?, ?, ?)',
            [$input['file_name'], $input['file_path'], $input['file_type'] ?? null]
        );
        echo json_encode(['success' => true, 'message' => 'Honeyfile registered.', 'id' => Database::lastInsertId()]);
        break;

    case 'DELETE':
        Auth::requireRole('admin');
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid honeyfile ID required.']);
            break;
        }
        Database::execute('UPDATE honeyfiles SET is_active = 0 WHERE id = ?', [$id]);
        echo json_encode(['success' => true, 'message' => 'Honeyfile deactivated.']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
