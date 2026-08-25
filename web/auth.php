<?php
/**
 * HoneyGuard — Authentication & Authorization
 */

require_once __DIR__ . '/db.php';

class Auth {

    /**
     * Register a new user.
     * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public static function register(string $username, string $email, string $password): array {
        $username = trim($username);
        $email = strtolower(trim($email));

        // Validate
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'message' => 'Username must be 3-50 characters.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
        }

        // Check duplicates
        $existing = Database::queryOne(
            'SELECT id FROM users WHERE email = ? OR username = ?',
            [$email, $username]
        );
        if ($existing) {
            return ['success' => false, 'message' => 'Email or username already exists.'];
        }

        // Insert
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        Database::execute(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)',
            [$username, $email, $hash, 'viewer']
        );
        $userId = Database::lastInsertId();

        self::auditLog((int)$userId, 'register', 'New user registration');

        return ['success' => true, 'message' => 'Account created successfully.', 'user_id' => (int)$userId];
    }

    /**
     * Authenticate a user and create a session.
     * @return array ['success' => bool, 'message' => string]
     */
    public static function login(string $email, string $password, bool $remember = false): array {
        $email = strtolower(trim($email));

        $user = Database::queryOne(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::auditLog(null, 'login_failed', "Failed login for: $email");
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Update last login
        Database::execute('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);

        // Create session
        $token = bin2hex(random_bytes(32));
        $expiry = $remember ? 30 * 86400 : SESSION_LIFETIME;  // 30 days or 24h
        $expiresAt = date('Y-m-d H:i:s', time() + $expiry);

        Database::execute(
            'INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)',
            [
                $user['id'],
                hash('sha256', $token),
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                $expiresAt
            ]
        );

        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['session_token'] = $token;
        $_SESSION['logged_in'] = true;

        // Set cookie if remember me
        if ($remember) {
            setcookie('hg_remember', $token, time() + (30 * 86400), '/', '', true, true);
        }

        self::auditLog($user['id'], 'login', 'Successful login');

        return ['success' => true, 'message' => 'Login successful.'];
    }

    /**
     * Check if user is authenticated.
     */
    public static function check(): bool {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }

        // Check remember me cookie
        if (isset($_COOKIE['hg_remember'])) {
            $tokenHash = hash('sha256', $_COOKIE['hg_remember']);
            $session = Database::queryOne(
                'SELECT s.*, u.username, u.email, u.role FROM user_sessions s
                 JOIN users u ON u.id = s.user_id
                 WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1',
                [$tokenHash]
            );
            if ($session) {
                $_SESSION['user_id'] = $session['user_id'];
                $_SESSION['username'] = $session['username'];
                $_SESSION['email'] = $session['email'];
                $_SESSION['role'] = $session['role'];
                $_SESSION['logged_in'] = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Get the current authenticated user data.
     */
    public static function user(): ?array {
        if (!self::check()) return null;
        return Database::queryOne('SELECT id, username, email, role, avatar_url, last_login, created_at FROM users WHERE id = ?', [$_SESSION['user_id']]);
    }

    /**
     * Require authentication — redirect to login if not authenticated.
     */
    public static function requireAuth(): void {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Require a specific role.
     */
    public static function requireRole(string $role): void {
        self::requireAuth();
        if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
    }

    /**
     * Logout the current user.
     */
    public static function logout(): void {
        if (isset($_SESSION['user_id'])) {
            self::auditLog($_SESSION['user_id'], 'logout', 'User logged out');

            // Delete session from DB
            if (isset($_SESSION['session_token'])) {
                $tokenHash = hash('sha256', $_SESSION['session_token']);
                Database::execute('DELETE FROM user_sessions WHERE session_token = ?', [$tokenHash]);
            }
        }

        // Clear session
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        // Clear remember me cookie
        if (isset($_COOKIE['hg_remember'])) {
            setcookie('hg_remember', '', time() - 42000, '/', '', true, true);
        }
    }

    /**
     * Generate a CSRF token.
     */
    public static function csrfToken(): string {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Validate CSRF token.
     */
    public static function csrfValidate(string $token): bool {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Validate API key for agent authentication.
     */
    public static function validateApiKey(string $key): ?array {
        $keyHash = hash('sha256', $key);
        $apiKey = Database::queryOne(
            'SELECT ak.*, u.username, u.role FROM api_keys ak
             JOIN users u ON u.id = ak.user_id
             WHERE ak.key_hash = ? AND ak.is_active = 1 AND u.is_active = 1',
            [$keyHash]
        );
        if ($apiKey) {
            Database::execute('UPDATE api_keys SET last_used = NOW() WHERE id = ?', [$apiKey['id']]);
            return $apiKey;
        }
        return null;
    }

    /**
     * Generate a new API key for a user.
     * @return string The raw API key (show to user once!)
     */
    public static function generateApiKey(int $userId, string $name = 'Default'): string {
        $rawKey = 'hg_' . bin2hex(random_bytes(24));
        $keyHash = hash('sha256', $rawKey);
        $prefix = substr($rawKey, 0, 8);

        Database::execute(
            'INSERT INTO api_keys (user_id, key_hash, key_prefix, name) VALUES (?, ?, ?, ?)',
            [$userId, $keyHash, $prefix, $name]
        );

        self::auditLog($userId, 'api_key_created', "API key created: $name");

        return $rawKey;
    }

    /**
     * Change user password.
     */
    public static function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        $user = Database::queryOne('SELECT password_hash FROM users WHERE id = ?', [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $userId]);
        self::auditLog($userId, 'password_changed', 'Password updated');
        return ['success' => true, 'message' => 'Password changed successfully.'];
    }

    /**
     * Write to audit log.
     */
    public static function auditLog(?int $userId, string $action, string $details = ''): void {
        Database::execute(
            'INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)',
            [$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']
        );
    }
}
