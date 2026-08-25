<?php
/**
 * HoneyGuard — Configuration
 * Update these values with your InfinityFree MySQL credentials.
 */

// ── Database Configuration ───────────────────────────────────
define('DB_HOST', 'sql305.infinityfree.com');
define('DB_NAME', 'if0_42739341_soc');
define('DB_USER', 'if0_42739341');
define('DB_PASS', 'Sudeep2003');
define('DB_CHARSET', 'utf8mb4');

// ── Application Settings ─────────────────────────────────────
define('APP_NAME', 'HoneyGuard');
define('APP_VERSION', '2.0.0');
define('APP_TAGLINE', 'Deception-Based Security Intelligence');
define('APP_URL', 'https://soctestone.free.je');

// ── Security ─────────────────────────────────────────────────
define('SESSION_LIFETIME', 86400);    // 24 hours in seconds
define('SESSION_NAME', 'HG_SESSION');
define('CSRF_TOKEN_NAME', 'hg_csrf');
define('BCRYPT_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900);      // 15 minutes

// ── API ──────────────────────────────────────────────────────
define('API_RATE_LIMIT', 60);         // requests per minute
define('ALERTS_PER_PAGE', 25);
define('MAX_ALERT_AGE_DAYS', 90);     // auto-cleanup after 90 days

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ── Session Setup ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    session_name(SESSION_NAME);
    session_start();
}

// ── Error Handling (disable in production) ───────────────────
// Set to 0 for production
ini_set('display_errors', 0);
error_reporting(E_ALL);
