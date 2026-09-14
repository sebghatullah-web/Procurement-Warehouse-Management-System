<?php
/**
 * Purchase list with filters.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'committee', 'admin');
$page_title = 'Purchases';
$base = BASE_URL;
$role = $user['role'];

$where = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') { $where[] = "(p.purchase_no LIKE '%" . esc($q) . "%' OR r.item_name LIKE '%" . esc($q) . "%' OR s.name LIKE '%" . esc($q) . "%')"; }
$st = trim($_GET['status'] ?? '');
if ($st !== '') { $where[] = "p.status = '" . esc($st) . "'"; }
$frm = trim($_GET['from_date'] ?? '');
$to  = trim($_GET['to_date'] ?? '');
if ($frm !== '') { $where[] = "p.purchase_date >= '" . esc($frm) . "'"; }
if ($to  !== '') { $where[] = "p.purchase_date <= '" . esc($to) . "'"; }

$sql = "SELECT p.*, r.request_no, r.item_name, s.name supplier, d.name dept
        FROM purchases p
        JOIN procurement_requests r ON r.id=p.request_id
        LEFT JOIN suppliers s ON s.id=p.supplier_id
        LEFT JOIN departments d ON d.id=r.department_id";
if (count($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.id DESC';
$rows = fetch_all($sql);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="filter-box rounded-2 p-3 mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small text-muted">Search</label>
      <input type="text" name="q" class="form-control form-control-sm" value="<?php echo h($q); ?>" placeholder="PO no / item / supplier"></div>
    <div class="col-md-3"><label class="form-label small text-muted">Status</label>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All</option>
        <?php foreach (_pur_statuses() as $key => $label): ?>
        <option value="<?php echo $key; ?>" <?php echo $st === $key ? 'selected' : ''; ?>><?php echo h($label); ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-auto"><label class="form-label small text-muted">From</label>
      <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo h($frm); ?>"></div>
    <div class="col-auto"><label class="form-label small text-muted">To</label>
      <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo h($to); ?>"></div>
    <div class="col-auto pt-3"><button class="btn btn-primary btn-sm" type="submit">Filter</button></div>
    <div class="col-auto pt-3"><a class="btn btn-outline-secondary btn-sm" href="<?php echo $base; ?>/purchases/list.php">Reset</a></div>
  </form>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-cart-check me-2"></i>Purchases <span class="text-muted small">(<?php echo count($rows); ?> records)</span></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><th>PO No</th><th>Request</th><th>Item</th><th>Department</th><th>Supplier</th><th>Qty</th><th>Total (PKR)</th><th>Status</th><th>Date</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $p): ?>
        <tr>
          <td><a href="view.php?id=<?php echo $p['id']; ?>"><?php echo h($p['purchase_no']); ?></a></td>
          <td><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $p['request_id']; ?>"><?php echo h($p['request_no']); ?></a></td>
          <td><?php echo h($p['item_name']); ?></td>
          <td class="text-muted small"><?php echo h($p['dept']); ?></td>
          <td><?php echo h($p['supplier'] ?? '—'); ?></td>
          <td><?php echo xnum($p['quantity']); ?></td>
          <td class="text-nowrap"><?php echo money0($p['total_cost']); ?></td>
          <td><?php echo badge($p['status']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? '—'); ?></td>
          <td><a class="btn btn-sm btn-outline-primary" href="view.php?id=<?php echo $p['id']; ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">No purchases found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>