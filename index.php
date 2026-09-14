<?php
/**
 * Dashboard - summary cards and quick action lists (role aware).
 */
require_once __DIR__ . '/includes/auth.php';
$user = require_login();
$page_title = 'Dashboard';
$base = BASE_URL;
$role = $user['role'];

$fm  = date('Y-m-01');          // first day of current month
$yr  = date('Y-01-01');         // first day of current year
$cards = [];

if ($role === 'employee') {
    $s = fetch_one("SELECT COUNT(*) total,
         SUM(status='pending') pending,
         SUM(status IN ('warehouse_check','purchase_required','quotation_pending','committee_pending','approved','purchased','received')) progress,
         SUM(status='completed') done,
         SUM(status='closed') closed
         FROM procurement_requests WHERE requested_by = " . (int)$user['id']);
    $cards = [
        ['My Requests',        (string)$s['total'],    'bi-inboxes',        'bg-primary'],
        ['In Progress',        (string)$s['progress'], 'bi-arrow-repeat',   'bg-warning text-dark'],
        ['Completed',          (string)$s['done'],     'bi-check2-circle',  'bg-success'],
        ['Closed as Unnecessary', (string)$s['closed'], 'bi-x-circle',      'bg-secondary'],
    ];
} elseif ($role === 'procurement_manager') {
    $s = fetch_one("SELECT
         (SELECT COUNT(*) FROM procurement_requests WHERE status='pending') AS review_pending,
         (SELECT COUNT(*) FROM procurement_requests WHERE status IN ('purchase_required','quotation_pending','approved')) AS buying,
         (SELECT COUNT(*) FROM purchases WHERE status='committee_pending') AS cm_pending,
         (SELECT COALESCE(SUM(total_cost),0) FROM purchases WHERE status IN ('completed','received','ordered') AND purchase_date >= '$fm') AS spend_m");
    $cards = [
        ['Requests Awaiting Review', (string)$s['review_pending'], 'bi-check2-circle', 'bg-primary'],
        ['Purchase Flow Active',   (string)$s['buying'],           'bi-cart-check',    'bg-warning text-dark'],
        ['Awaiting Committee',     (string)$s['cm_pending'],       'bi-people',        'bg-danger'],
        ['Spent This Month (PKR)', money0($s['spend_m']),          'bi-currency-dollar','bg-success'],
    ];
} elseif ($role === 'warehouse_manager') {
    $s = fetch_one("SELECT
         (SELECT COUNT(*) FROM warehouse_items WHERE quantity <= min_stock) AS low_stock,
         (SELECT COUNT(*) FROM procurement_requests WHERE status='warehouse_check') AS to_check,
         (SELECT COUNT(*) FROM warehouse_items) AS items,
         (SELECT COALESCE(SUM(quantity),0) FROM consumptions WHERE delivery_date >= '$fm') AS issued_m");
    $cards = [
        ['Low Stock Alerts',      (string)$s['low_stock'],   'bi-exclamation-triangle', 'bg-danger'],
        ['Requests to Check',     (string)$s['to_check'],    'bi-upc-scan',            'bg-primary'],
        ['Inventory Items',       (string)$s['items'],       'bi-boxes',               'bg-info text-dark'],
        ['Issued This Month',     xnum($s['issued_m']),      'bi-box-arrow-up',        'bg-success'],
    ];
} elseif ($role === 'gate_security') {
    $s = fetch_one("SELECT
         (SELECT COUNT(*) FROM purchases WHERE status='ordered') AS to_receive,
         (SELECT COUNT(*) FROM gate_checklists WHERE check_date >= '$fm') AS received_m");
    $cards = [
        ['Purchases to Receive', (string)$s['to_receive'], 'bi-truck',   'bg-warning text-dark'],
        ['Checklists This Month',(string)$s['received_m'],  'bi-shield-check', 'bg-success'],
    ];
} elseif ($role === 'committee') {
    $s = fetch_one("SELECT
         (SELECT COUNT(*) FROM purchases WHERE status='committee_pending') AS pending_cm,
         (SELECT COUNT(*) FROM purchases WHERE status='approved') AS approved_cm,
         (SELECT COALESCE(SUM(total_cost),0) FROM purchases WHERE status IN ('committee_pending','approved','ordered')) AS pipeline");
    $cards = [
        ['Awaiting My Approval', (string)$s['pending_cm'], 'bi-people',  'bg-warning text-dark'],
        ['Approved Purchases',   (string)$s['approved_cm'], 'bi-check2-circle', 'bg-success'],
        ['In Pipeline (PKR)',    money0($s['pipeline']),   'bi-graph-up','bg-info text-dark'],
    ];
} else { /* general_manager + admin */
    $s = fetch_one("SELECT
         (SELECT COUNT(*) FROM procurement_requests) AS total_req,
         (SELECT COUNT(*) FROM purchases WHERE purchase_date >= '$yr') AS year_pur,
         (SELECT COALESCE(SUM(total_cost),0) FROM purchases WHERE purchase_date >= '$yr') AS year_spend,
         (SELECT COUNT(*) FROM warehouse_items) AS items,
         (SELECT COUNT(*) FROM warehouse_items WHERE quantity <= min_stock) AS low_stock,
         (SELECT COUNT(*) FROM consumptions) AS total_cons");
    $cards = [
        ['Total Requests',       (string)$s['total_req'], 'bi-inboxes',   'bg-primary'],
        ['Purchases This Year',  (string)$s['year_pur'],  'bi-cart-check','bg-info text-dark'],
        ['Spend This Year (PKR)', money0($s['year_spend']), 'bi-currency-dollar','bg-success'],
        ['Warehouse Items',      (string)$s['items'],     'bi-boxes',     'bg-warning text-dark'],
    ];
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-title-row mb-3">
  <div>
    <h4 class="mb-0">Assalam-o-Alaikum, <?php echo h($user['name']); ?> &#128075;</h4>
    <div class="text-muted small"><?php echo h(role_label($role)); ?> &middot; <?php echo h($user['department_name'] ?? 'General'); ?></div>
  </div>
  <?php if ($role === 'employee' || $role === 'procurement_manager' || $role === 'admin'): ?>
  <a class="btn btn-primary" href="<?php echo $base; ?>/requests/create.php"><i class="bi bi-plus-square me-1"></i>New Request</a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($cards as $c): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon <?php echo h($c[3]); ?>"><i class="bi <?php echo h($c[2]); ?>"></i></div>
        <div>
          <div class="fs-4 fw-bold lh-1"><?php echo $c[1]; ?></div>
          <div class="text-muted small"><?php echo h($c[0]); ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php
$dashFrag = [
    'employee' => 'employee', 'procurement_manager' => 'procurement_manager',
    'warehouse_manager' => 'warehouse_manager', 'gate_security' => 'gate_security',
    'committee' => 'committee', 'general_manager' => 'general_manager',
    'admin' => 'admin',
];
require __DIR__ . '/includes/dash_' . ($dashFrag[$role] ?? 'general_manager') . '.php';
?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>