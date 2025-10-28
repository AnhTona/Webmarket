<?php
declare(strict_types=1);

/**
 * BaseController.php
 * Base controller with common methods
 */

require_once __DIR__ . '/../../model/database.php';

abstract class BaseController
{
    /**
     * Check if user is authenticated
     */
    protected static function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header("Location: /Webmarket/admin/html/login.php");
            exit();
        }

        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            header("Location: /Webmarket/admin/html/login.php?timeout=1");
            exit();
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Check if request is AJAX
     */
    protected static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get database connection
     */
    protected static function db(): mysqli
    {
        $db = Database::getInstance();
        return $db->getConnection();
    }

    /**
     * Execute query (INSERT, UPDATE, DELETE)
     */
    protected static function query(string $sql, array $params = []): bool
    {
        $conn = self::db();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException("Query preparation failed: " . $conn->error);
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Execute transaction
     */
    protected static function transaction(callable $callback): void
    {
        $conn = self::db();
        $conn->begin_transaction();

        try {
            $callback();
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * Sanitize input string
     */
    protected static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Send JSON success response and exit
     */
    protected static function success(string $message, array $data = []): never
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Send JSON error response and exit
     */
    protected static function error(string $message, int $code = 400): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }

    /**
     * Fetch a single row from database
     */
    protected static function fetchRow(string $sql, array $params = []): ?array
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        if (!$conn) {
            return null;
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Fetch a single value from database
     */
    protected static function fetchOne(string $sql, array $params = []): mixed
    {
        $row = self::fetchRow($sql, $params);
        return $row ? reset($row) : null;
    }

    /**
     * Fetch all rows from database
     */
    protected static function fetchAll(string $sql, array $params = []): array
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        if (!$conn) {
            return [];
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows ?: [];
    }

    /**
     * Calculate percentage change
     */
    protected static function percentChange(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100.0 : 0.0;
        }
        return (($new - $old) / $old) * 100;
    }

    /**
     * Format percentage with sign
     */
    protected static function formatPercent(float $percent): string
    {
        $sign = $percent >= 0 ? '+' : '';
        return $sign . number_format($percent, 1) . '%';
    }

    /**
     * Log activity
     */
    protected static function log(string $action, array $data = []): void
    {
        error_log(sprintf(
            "[%s] %s: %s",
            date('Y-m-d H:i:s'),
            $action,
            json_encode($data)
        ));
    }
}