<?php
/**
 * Activity Logger
 * Logs user and system activities for admin visibility
 */

class ActivityLogger
{
    /**
     * Log an activity
     *
     * @param string $action The action type (login, password_change, etc.)
     * @param string|null $targetType The type of target (user, razor, subscription, etc.)
     * @param int|null $targetId The ID of the target
     * @param array|null $details Additional details (will be JSON encoded)
     * @param int|null $userId The user who performed the action (null for system)
     */
    public static function log(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $details = null,
        ?int $userId = null
    ): void {
        $db = Database::getInstance();

        // Use session user if not specified
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }

        $db->query(
            "INSERT INTO activity_log (user_id, action, target_type, target_id, details, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $action,
                $targetType,
                $targetId,
                $details ? json_encode($details) : null,
                self::getClientIp(),
                self::getUserAgent(),
            ]
        );
    }

    /**
     * Log a successful login
     */
    public static function logLogin(int $userId, string $email): void
    {
        self::log('login', 'user', $userId, ['email' => $email], $userId);
    }

    /**
     * Log a failed login attempt
     */
    public static function logLoginFailed(string $email): void
    {
        self::log('login_failed', null, null, ['email' => $email], null);
    }

    /**
     * Log a password change
     */
    public static function logPasswordChange(int $userId): void
    {
        self::log('password_change', 'user', $userId, null, $userId);
    }

    /**
     * Log profile update
     */
    public static function logProfileUpdate(int $userId, array $changedFields): void
    {
        self::log('profile_update', 'user', $userId, ['changed_fields' => $changedFields], $userId);
    }

    /**
     * Log user creation by admin
     */
    public static function logUserCreated(int $targetUserId, string $email, int $adminId): void
    {
        self::log('user_created', 'user', $targetUserId, ['email' => $email], $adminId);
    }

    /**
     * Log user deletion by admin
     */
    public static function logUserDeleted(int $targetUserId, string $email, int $adminId): void
    {
        self::log('user_deleted', 'user', $targetUserId, ['email' => $email], $adminId);
    }

    /**
     * Log admin action
     */
    public static function logAdminAction(string $action, ?string $targetType = null, ?int $targetId = null, ?array $details = null): void
    {
        self::log('admin_' . $action, $targetType, $targetId, $details);
    }

    /**
     * Get activities with pagination and filtering
     */
    public static function getActivities(
        int $page = 1,
        int $perPage = 50,
        ?string $action = null,
        ?int $userId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $search = null
    ): array {
        $db = Database::getInstance();
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if ($action) {
            $where[] = "a.action = ?";
            $params[] = $action;
        }

        if ($userId) {
            $where[] = "a.user_id = ?";
            $params[] = $userId;
        }

        if ($dateFrom) {
            $where[] = "DATE(a.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(a.created_at) <= ?";
            $params[] = $dateTo;
        }

        if ($search) {
            $where[] = "(a.details LIKE ? OR a.ip_address LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count
        $countSql = "SELECT COUNT(*) as count FROM activity_log a {$whereClause}";
        $total = $db->query($countSql, $params)[0]['count'];

        // Get activities
        $sql = "SELECT a.*, u.username, u.email as user_email
                FROM activity_log a
                LEFT JOIN users u ON a.user_id = u.id
                {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $activities = $db->query($sql, $params);

        // Decode JSON details
        foreach ($activities as &$activity) {
            if ($activity['details']) {
                $activity['details'] = json_decode($activity['details'], true);
            }
        }

        return [
            'data' => $activities,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
        ];
    }

    /**
     * Get unique action types for filtering
     */
    public static function getActionTypes(): array
    {
        $db = Database::getInstance();
        $result = $db->query("SELECT DISTINCT action FROM activity_log ORDER BY action");
        return array_column($result, 'action');
    }

    /**
     * Get client IP address
     */
    private static function getClientIp(): string
    {
        // Check for forwarded IP (behind proxy/load balancer)
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent string
     */
    private static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
}
