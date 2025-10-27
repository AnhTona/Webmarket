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