<?php
/**
 * Centralized Initialization File
 * Handles session management and database connection
 * Created: 2025-10-26
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../model/database.php';
