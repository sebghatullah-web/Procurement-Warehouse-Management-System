<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('general_manager', 'procurement_manager', 'warehouse_manager', 'admin');
$page_title = 'Consumption Reports';
$base = BASE_URL;

$frm = trim($_GET['from_date'] ?? '');
$to  = trim($_GET['to_date'] ?? '');
$dp  = (int)($_GET['department_id'] ?? 0);
$src = trim($_GET['source'] ?? '');

$where = [];
if ($frm !== '') { $where[] = "c.delivery_date >= '" . esc($frm) . "'"; }
if ($to  !== '') { $where[] = "c.delivery_date <= '" . esc($to) . "'"; }
if ($dp > 0)     { $where[] = 'c.department_id = ' . $dp; }
if ($src !== '') { $where[] = "c.source = '" . esc($src) . "'"; }

$sql = "SELECT c.*, d.name dept FROM consumptions c
        JOIN departments d ON d.id=c.department_id";
if (count($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY c.delivery_date DESC';
$rows = fetch_all($sql);
$depts = fetch_all('SELECT id, name FROM departments ORDER BY name');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="filter-box rounded-2 p-3 mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label small text-muted">From</label>
      <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo h($frm); ?>"></div>
    <div class="col-auto"><label class="form-label small text-muted">To</label>
      <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo h($to); ?>"></div>
    <div class="col-md-3"><label class="form-label small text-muted">Department</label>
      <select name="department_id" class="form-select form-select-sm" data-autosubmit>
        <option value="0">All</option>
        <?php foreach ($depts as $d): ?>
        <option value="<?php echo $d['id']; ?>" <?php echo $dp===(int)$d['id']?'selected':''; ?>><?php echo h($d['name']); ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><label class="form-label small text-muted">Source</label>
      <select name="source" class="form-select form-select-sm" data-autosubmit>
        <option value="">All</option>
        <option value="warehouse" <?php echo $src==='warehouse'?'selected':''; ?>>Warehouse</option>
        <option value="direct" <?php echo $src==='direct'?'selected':''; ?>>Direct</option>
      </select></div>
    <div class="col-auto pt-3"><button class="btn btn-primary btn-sm" type="submit">Filter</button></div>
    <div class="col-auto pt-3"><a class="btn btn-outline-secondary btn-sm" href="<?php echo $base; ?>/reports/consumption.php">Reset</a></div>
  </form>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-box-arrow-up me-2"></i>Consumption Log</span>
    <button class="btn btn-sm btn-outline-secondary" data-print><i class="bi bi-printer me-1"></i>Print</button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Date</th><th>Department</th><th>Item</th><th>Category</th><th>Qty</th><th>Unit</th><th>Source</th><th>Notes</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $c): ?>
        <tr>
          <td class="text-nowrap"><?php echo h($c['delivery_date']); ?></td>
          <td><?php echo h($c['dept']); ?></td>
          <td><?php echo h($c['item_name']); ?></td>
          <td class="text-muted small"><?php echo h($c['category_id'] ?? '—'); ?></td>
          <td class="text-nowrap"><?php echo xnum($c['quantity']); ?></td>
          <td><?php echo h($c['unit']); ?></td>
          <td><?php echo badge($c['source']); ?></td>
          <td class="text-muted small"><?php echo h($c['notes'] ?? '—'); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No consumption records found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>