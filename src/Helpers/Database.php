<?php
/**
 * Database helper for SQLite
 */

class Database
{
    private static ?PDO $pdo = null;

    /**
     * Initialize the database connection and run migrations
     */
    public static function init(): void
    {
        $dbPath = config('DB_PATH');
        $dbDir = dirname($dbPath);

        // Create data directory if it doesn't exist
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $isNewDatabase = !file_exists($dbPath);

        // Connect to database
        self::$pdo = new PDO('sqlite:' . $dbPath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Enable foreign keys
        self::$pdo->exec('PRAGMA foreign_keys = ON');

        // Run migrations
        self::runMigrations();
    }

    /**
     * Get the PDO instance
     */
    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::init();
        }
        return self::$pdo;
    }

    /**
     * Execute a query
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Get the last insert ID
     */
    public static function lastInsertId(): string
    {
        return self::connection()->lastInsertId();
    }

    /**
     * Begin a transaction
     */
    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    /**
     * Rollback a transaction
     */
    public static function rollback(): bool
    {
        return self::connection()->rollBack();
    }

    /**
     * Close the database connection
     */
    public static function close(): void
    {
        self::$pdo = null;
    }

    /**
     * Run database migrations
     */
    private static function runMigrations(): void
    {
        // Create migrations table if it doesn't exist
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Get list of executed migrations
        $executed = self::fetchAll("SELECT migration FROM migrations");
        $executedMigrations = array_column($executed, 'migration');

        // Get migration files
        $migrationDir = BASE_PATH . '/migrations';
        if (!is_dir($migrationDir)) {
            return;
        }

        $files = glob($migrationDir . '/*.php');
        sort($files);

        foreach ($files as $file) {
            $migration = basename($file, '.php');

            if (in_array($migration, $executedMigrations)) {
                continue;
            }

            // Run the migration
            $sql = require $file;
            if (is_string($sql)) {
                self::$pdo->exec($sql);
            } elseif (is_array($sql)) {
                foreach ($sql as $query) {
                    self::$pdo->exec($query);
                }
            }

            // Record the migration
            self::query(
                "INSERT INTO migrations (migration) VALUES (?)",
                [$migration]
            );
        }
    }
}
