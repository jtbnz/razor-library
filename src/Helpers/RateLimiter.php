<?php
/**
 * Rate limiting utility
 */

class RateLimiter
{
    /**
     * Check if an action is rate limited
     *
     * @param string $identifier User identifier (email, IP, etc.)
     * @param string $action Action being limited (login, reset_password, etc.)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if the action should be blocked
     */
    public static function isLimited(
        string $identifier,
        string $action,
        int $maxAttempts,
        int $windowSeconds
    ): bool {
        // Clean up old entries
        self::cleanup($windowSeconds);

        // Get current attempts
        $record = Database::fetch(
            "SELECT * FROM rate_limits WHERE identifier = ? AND action = ?",
            [$identifier, $action]
        );

        if (!$record) {
            return false;
        }

        // Check if within window
        $firstAttempt = strtotime($record['first_attempt']);
        $now = time();

        if ($now - $firstAttempt > $windowSeconds) {
            // Window has passed, reset
            Database::query(
                "DELETE FROM rate_limits WHERE id = ?",
                [$record['id']]
            );
            return false;
        }

        return $record['attempts'] >= $maxAttempts;
    }

    /**
     * Record an attempt
     */
    public static function hit(string $identifier, string $action): void
    {
        $record = Database::fetch(
            "SELECT * FROM rate_limits WHERE identifier = ? AND action = ?",
            [$identifier, $action]
        );

        if ($record) {
            Database::query(
                "UPDATE rate_limits SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE id = ?",
                [$record['id']]
            );
        } else {
            Database::query(
                "INSERT INTO rate_limits (identifier, action) VALUES (?, ?)",
                [$identifier, $action]
            );
        }
    }

    /**
     * Clear rate limit for an identifier/action
     */
    public static function clear(string $identifier, string $action): void
    {
        Database::query(
            "DELETE FROM rate_limits WHERE identifier = ? AND action = ?",
            [$identifier, $action]
        );
    }

    /**
     * Get remaining attempts
     */
    public static function remaining(
        string $identifier,
        string $action,
        int $maxAttempts,
        int $windowSeconds
    ): int {
        $record = Database::fetch(
            "SELECT * FROM rate_limits WHERE identifier = ? AND action = ?",
            [$identifier, $action]
        );

        if (!$record) {
            return $maxAttempts;
        }

        $firstAttempt = strtotime($record['first_attempt']);
        $now = time();

        if ($now - $firstAttempt > $windowSeconds) {
            return $maxAttempts;
        }

        return max(0, $maxAttempts - $record['attempts']);
    }

    /**
     * Clean up old rate limit entries
     */
    private static function cleanup(int $windowSeconds): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        Database::query(
            "DELETE FROM rate_limits WHERE first_attempt < ?",
            [$cutoff]
        );
    }
}
