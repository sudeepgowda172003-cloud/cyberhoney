<?php
/**
 * HoneyGuard — Alerts API
 * GET  /api/alerts.php          — list alerts (paginated, filterable)
 * PUT  /api/alerts.php?id=X     — mark alert as read
 * DELETE /api/alerts.php?id=X   — delete alert (admin only)
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../auth.php';

// Require session auth for browser requests
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet(): void {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? ALERTS_PER_PAGE)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    // Filter by level
    if (!empty($_GET['level'])) {
        $where[] = 'level = ?';
        $params[] = strtoupper($_GET['level']);
    }

    // Filter by action
    if (!empty($_GET['action'])) {
        $where[] = 'action = ?';
        $params[] = $_GET['action'];
    }

    // Filter by date range
    if (!empty($_GET['from'])) {
        $where[] = 'created_at >= ?';
        $params[] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $where[] = 'created_at <= ?';
        $params[] = $_GET['to'] . ' 23:59:59';
    }

    // Filter by read status
    if (isset($_GET['is_read']) && $_GET['is_read'] !== '') {
        $where[] = 'is_read = ?';
        $params[] = (int)$_GET['is_read'];
    }

    // Search
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $where[] = '(file_name LIKE ? OR message LIKE ? OR ip_address LIKE ? OR hostname LIKE ?)';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count
    $countRow = Database::queryOne("SELECT COUNT(*) as total FROM alerts $whereClause", $params);
    $total = $countRow['total'] ?? 0;

    // Fetch alerts
    $alerts = Database::query(
        "SELECT * FROM alerts $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
        $params
    );

    echo json_encode([
        'alerts' => $alerts,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit),
        ]
    ]);
}

function handlePut(): void {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        // Bulk mark as read
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['ids']) && is_array($input['ids'])) {
            $placeholders = implode(',', array_fill(0, count($input['ids']), '?'));
            Database::execute("UPDATE alerts SET is_read = 1 WHERE id IN ($placeholders)", $input['ids']);
            echo json_encode(['success' => true, 'message' => 'Alerts marked as read.']);
        } else {
            // Mark all as read
            Database::execute('UPDATE alerts SET is_read = 1 WHERE is_read = 0');
            echo json_encode(['success' => true, 'message' => 'All alerts marked as read.']);
        }
        return;
    }

    Database::execute('UPDATE alerts SET is_read = 1 WHERE id = ?', [$id]);
    echo json_encode(['success' => true, 'message' => 'Alert marked as read.']);
}

function handleDelete(): void {
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required.']);
        return;
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        Database::execute('DELETE FROM alerts WHERE id = ?', [$id]);
        echo json_encode(['success' => true, 'message' => 'Alert deleted.']);
    } else {
        // Bulk delete old alerts
        $days = (int)($_GET['older_than'] ?? MAX_ALERT_AGE_DAYS);
        $count = Database::execute(
            'DELETE FROM alerts WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$days]
        );
        echo json_encode(['success' => true, 'message' => "$count old alerts deleted."]);
    }
}
