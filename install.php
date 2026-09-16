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

echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>نصب‌کننده</title>'
   . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">'
   . '<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">'
   . '<style>body{font-family:"Vazirmatn","Segoe UI",Tahoma,Arial,sans-serif;}</style>'
   . '</head><body class="bg-light"><div class="container" style="max-width:720px">
   <div class="card shadow-sm mt-5"><div class="card-body">
   <h3 class="mb-1"><i class="bi bi-tools"></i> نصب‌کننده PWMS</h3>
   <p class="text-muted small">پایگاه داده <code>' . h($dbName) . '</code> را از
   <code>database/procurement_warehouse.sql</code> می‌سازد/تازه می‌کند.</p>';

if ($error) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
} elseif ($report) {
    echo '<div class="alert alert-success"><h5>نصب با موفقیت انجام شد</h5><ul>'
       . implode('</li><li>', $report) . '</li></ul></div>';
    echo '<a class="btn btn-primary" href="login.php">رفتن به صفحه ورود &larr;</a> ';
    echo '<span class="text-muted small ms-2">برای محیط تولید، install.php را حذف کنید.</span>';
} else {
    echo '<div class="alert alert-warning">این کار اگر پایگاه داده <code>'
       . h($dbName) . '</code> از قبل وجود داشته باشد، همه جدول‌های آن را <strong>حذف و دوباره می‌سازد</strong>. فقط در محیط توسعه/محلی اجرا کنید.</div>';
    echo '<form method="post"><button class="btn btn-danger btn-lg" type="submit">'
       . '<i class="bi bi-cone-striped me-1"></i> نصب پایگاه داده از حالا</button></form>';
}
echo '</div></div></div></body></html>';