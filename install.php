<?php
/**
 * One-click installer - creates the database and imports
 * database/procurement_warehouse.sql  (local development only).
 *
 *   http://localhost/ProcurementWarehouseMS/install.php
 */
date_default_timezone_set('Asia/Karachi');

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'procurement_warehouse_ms';
$sqlPath = __DIR__ . '/database/procurement_warehouse.sql';

function h($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES); }

$report = null;
$error  = null;

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!file_exists($sqlPath)) {
        $error = 'SQL file not found: database/procurement_warehouse.sql';
    } else {
        $my = new mysqli($dbHost, $dbUser, $dbPass);
        if ($my->connect_error) {
            $error = 'Cannot reach MySQL: ' . $my->connect_error
                   . '<br>Start MySQL in the XAMPP Control Panel first.';
        } else {
            $my->set_charset('utf8mb4');
            $sql = file_get_contents($sqlPath);
            $ok  = $my->multi_query($sql);
            if (!$ok) {
                $error = 'SQL error: ' . h($my->error);
            } else {
                // drain all statements
                do {
                    if ($res = $my->store_result()) { $res->free(); }
                } while ($my->next_result());
                if ($my->errno) {
                    $error = 'SQL error (step ' . ($my->errno) . '): ' . h($my->error);
                } else {
                    $report = [];
                    $report[] = 'Database <strong>' . $dbName . '</strong> created / refreshed.';
                    if ($res2 = $my->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '" . $dbName . "' ORDER BY table_name")) {
                        while ($row = $res2->fetch_row()) { $report[] = '&middot; ' . h($row[0]); }
                    }
                    $report[] = 'Default users seeded (admin/admin123 etc.) - see login page.';
                    $my->close();
                }
            }
        }
    }
}

echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Installer</title>'
   . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">'
   . '</head><body class="bg-light"><div class="container" style="max-width:720px">
   <div class="card shadow-sm mt-5"><div class="card-body">
   <h3 class="mb-1"><i class="bi bi-tools"></i> PWMS Installer</h3>
   <p class="text-muted small">Creates/refreshes the <code>' . h($dbName) . '</code> database from
   <code>database/procurement_warehouse.sql</code>.</p>';

if ($error) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
} elseif ($report) {
    echo '<div class="alert alert-success"><h5>Installation successful</h5><ul>'
       . implode('</li><li>', $report) . '</li></ul></div>';
    echo '<a class="btn btn-primary" href="login.php">Go to sign-in page &rarr;</a> ';
    echo '<span class="text-muted small ms-2">Delete install.php for production.</span>';
} else {
    echo '<div class="alert alert-warning">This will <strong>drop and recreate</strong> every table in <code>'
       . h($dbName) . '</code> if it already exists. Run only on a local/development machine.</div>';
    echo '<form method="post"><button class="btn btn-danger btn-lg" type="submit">'
       . '<i class="bi bi-cone-striped me-1"></i> Install database now</button></form>';
}
echo '</div></div></div></body></html>';