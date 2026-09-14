<?php
/**
 * Procurement & Warehouse Management System - Khawar Construction Co.
 * Global configuration + database connection.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Karachi');

/* ---- MySQL / MariaDB (XAMPP defaults: root / empty password) ---- */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'procurement_warehouse_ms');

define('APP_NAME', 'Procurement & Warehouse Management System');
define('APP_SHORT', 'PWMS');

/**
 * Auto-detect the web base path of this application so it can live in
 * ANY folder under htdocs (e.g. '/ProcurementWarehouseMS').
 */
function _pwms_base_url()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
    $doc    = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
    $base   = dirname($script);
    $doc    = rtrim($doc, '/');
    while ($base !== '' && $base !== '/' && $doc !== '') {
        if (file_exists($doc . '/' . trim($base, '/') . '/includes/config.php')) {
            break;
        }
        $parent = dirname($base);
        if ($parent === $base) { break; }
        $base = $parent;
    }
    return ($base === '' || $base === '/') ? '' : $base;
}
define('BASE_URL', _pwms_base_url());

/* ---- Connection ---- */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('[PWMS] DB connection failed: ' . $conn->connect_error);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database error</title>'
       . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">'
       . '</head><body class="bg-light"><div class="container mt-5"><div class="alert alert-danger shadow-sm">'
       . '<h4><i class="bi bi-cone-striped"></i> Cannot connect to MySQL</h4>'
       . '<p>' . htmlspecialchars($conn->connect_error) . '</p>'
       . '<p>Make sure <strong>MySQL</strong> is running in the XAMPP Control Panel and the database '
       . '<code>' . DB_NAME . '</code> has been created. Run the '
       . '<a href="' . BASE_URL . '/install.php" class="alert-link">installer</a> or import '
       . '<code>database/procurement_warehouse.sql</code>.</p>'
       . '</div></div></body></html>';
    exit(1);
}
$conn->set_charset('utf8mb4');