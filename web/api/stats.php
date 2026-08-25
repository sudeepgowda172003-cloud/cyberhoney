<?php
/**
 * HoneyGuard — Dashboard Statistics API
 * GET /api/stats.php
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../auth.php';

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$type = $_GET['type'] ?? 'overview';

switch ($type) {
    case 'overview':
        getOverview();
        break;
    case 'timeline':
        getTimeline();
        break;
    case 'top_files':
        getTopFiles();
        break;
    case 'top_ips':
        getTopIps();
        break;
    case 'actions':
        getActionBreakdown();
        break;
    case 'levels':
        getLevelBreakdown();
        break;
    default:
        getOverview();
}

function getOverview(): void {
    $dateFilter = '';
    $params = [];
    if (!empty($_GET['date'])) {
        $dateFilter = 'WHERE DATE(created_at) = ?';
        $params[] = $_GET['date'];
    }

    $total = Database::queryOne("SELECT COUNT(*) as count FROM alerts $dateFilter", $params)['count'] ?? 0;
    
    $critFilter = $dateFilter ? "$dateFilter AND level = 'CRITICAL'" : "WHERE level = 'CRITICAL'";
    $critical = Database::queryOne("SELECT COUNT(*) as count FROM alerts $critFilter", $params)['count'] ?? 0;
    
    $alertFilter = $dateFilter ? "$dateFilter AND level = 'ALERT'" : "WHERE level = 'ALERT'";
    $alerts = Database::queryOne("SELECT COUNT(*) as count FROM alerts $alertFilter", $params)['count'] ?? 0;
    
    $warnFilter = $dateFilter ? "$dateFilter AND level = 'WARNING'" : "WHERE level = 'WARNING'";
    $warnings = Database::queryOne("SELECT COUNT(*) as count FROM alerts $warnFilter", $params)['count'] ?? 0;
    
    $unreadFilter = $dateFilter ? "$dateFilter AND is_read = 0" : "WHERE is_read = 0";
    $unread = Database::queryOne("SELECT COUNT(*) as count FROM alerts $unreadFilter", $params)['count'] ?? 0;
    
    $fileFilter = $dateFilter ? "$dateFilter AND file_name IS NOT NULL" : "WHERE file_name IS NOT NULL";
    $uniqueFiles = Database::queryOne("SELECT COUNT(DISTINCT file_name) as count FROM alerts $fileFilter", $params)['count'] ?? 0;
    
    $ipFilter = $dateFilter ? "$dateFilter AND ip_address IS NOT NULL" : "WHERE ip_address IS NOT NULL";
    $uniqueIps = Database::queryOne("SELECT COUNT(DISTINCT ip_address) as count FROM alerts $ipFilter", $params)['count'] ?? 0;
    
    $honeyfiles = Database::queryOne('SELECT COUNT(*) as count FROM honeyfiles WHERE is_active = 1')['count'] ?? 0;

    // "Today" alerts (or selected day alerts)
    if (!empty($_GET['date'])) {
        $today = Database::queryOne("SELECT COUNT(*) as count FROM alerts WHERE DATE(created_at) = ?", [$_GET['date']])['count'] ?? 0;
    } else {
        $today = Database::queryOne("SELECT COUNT(*) as count FROM alerts WHERE DATE(created_at) = CURDATE()")['count'] ?? 0;
    }

    // Last 24h trend
    $last24h = Database::queryOne("SELECT COUNT(*) as count FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
    $prev24h = Database::queryOne("SELECT COUNT(*) as count FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
    $trend = $prev24h > 0 ? round((($last24h - $prev24h) / $prev24h) * 100, 1) : 0;

    // Security score (inverse of threat density — higher is safer)
    $score = max(0, 100 - min(100, ($critical * 15) + ($alerts * 8) + ($warnings * 3)));

    echo json_encode([
        'total_alerts' => (int)$total,
        'critical' => (int)$critical,
        'alerts' => (int)$alerts,
        'warnings' => (int)$warnings,
        'unread' => (int)$unread,
        'unique_files' => (int)$uniqueFiles,
        'unique_ips' => (int)$uniqueIps,
        'honeyfiles_active' => (int)$honeyfiles,
        'today' => (int)$today,
        'trend_24h' => $trend,
        'security_score' => $score,
    ]);
}

function getTimeline(): void {
    $period = $_GET['period'] ?? '24h';
    $dateFilter = '';
    $params = [];

    if (!empty($_GET['date'])) {
        // If a specific date is chosen, show hourly breakdown for that day
        $rows = Database::query(
            "SELECT DATE_FORMAT(created_at, '%H:00') as label, COUNT(*) as count
             FROM alerts WHERE DATE(created_at) = ?
             GROUP BY DATE_FORMAT(created_at, '%H:00') ORDER BY label ASC",
            [$_GET['date']]
        );
    } else {
        switch ($period) {
            case '7d':
                $rows = Database::query(
                    "SELECT DATE(created_at) as label, COUNT(*) as count
                     FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     GROUP BY DATE(created_at) ORDER BY label ASC"
                );
                break;
            case '30d':
                $rows = Database::query(
                    "SELECT DATE(created_at) as label, COUNT(*) as count
                     FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY DATE(created_at) ORDER BY label ASC"
                );
                break;
            default: // 24h
                $rows = Database::query(
                    "SELECT DATE_FORMAT(created_at, '%H:00') as label, COUNT(*) as count
                     FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     GROUP BY DATE_FORMAT(created_at, '%H:00') ORDER BY label ASC"
                );
        }
    }

    echo json_encode(['timeline' => $rows]);
}

function getTopFiles(): void {
    $limit = min(10, (int)($_GET['limit'] ?? 5));
    $where = "WHERE file_name IS NOT NULL";
    $params = [];
    if (!empty($_GET['date'])) {
        $where .= " AND DATE(created_at) = ?";
        $params[] = $_GET['date'];
    }
    
    $rows = Database::query(
        "SELECT file_name as label, COUNT(*) as count FROM alerts
         $where
         GROUP BY file_name ORDER BY count DESC LIMIT $limit",
         $params
    );
    echo json_encode(['top_files' => $rows]);
}

function getTopIps(): void {
    $limit = min(10, (int)($_GET['limit'] ?? 5));
    $where = "WHERE ip_address IS NOT NULL";
    $params = [];
    if (!empty($_GET['date'])) {
        $where .= " AND DATE(created_at) = ?";
        $params[] = $_GET['date'];
    }
    
    $rows = Database::query(
        "SELECT ip_address as label, COUNT(*) as count FROM alerts
         $where
         GROUP BY ip_address ORDER BY count DESC LIMIT $limit",
         $params
    );
    echo json_encode(['top_ips' => $rows]);
}

function getActionBreakdown(): void {
    $where = "";
    $params = [];
    if (!empty($_GET['date'])) {
        $where = "WHERE DATE(created_at) = ?";
        $params[] = $_GET['date'];
    }

    $rows = Database::query(
        "SELECT COALESCE(action, 'unknown') as label, COUNT(*) as count FROM alerts
         $where
         GROUP BY action ORDER BY count DESC",
         $params
    );
    echo json_encode(['actions' => $rows]);
}

function getLevelBreakdown(): void {
    $where = "";
    $params = [];
    if (!empty($_GET['date'])) {
        $where = "WHERE DATE(created_at) = ?";
        $params[] = $_GET['date'];
    }

    $rows = Database::query(
        "SELECT level as label, COUNT(*) as count FROM alerts
         $where
         GROUP BY level ORDER BY FIELD(level, 'CRITICAL', 'ALERT', 'WARNING', 'INFO')",
         $params
    );
    echo json_encode(['levels' => $rows]);
}
