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