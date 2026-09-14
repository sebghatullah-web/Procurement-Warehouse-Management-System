<?php
/**
 * Warehouse availability check for an approved request.
 *  - Find a matching stock item and deliver (deduct stock + record consumption).
 *  - Or mark "not available" so the purchase flow starts.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('warehouse_manager', 'admin');
$page_title = 'Warehouse Check';
$base = BASE_URL;

$id = (int)($_GET['request_id'] ?? 0);
$r = fetch_one("SELECT r.*, d.name dept, c.name cat FROM procurement_requests r
                JOIN departments d ON d.id=r.department_id
                LEFT JOIN categories c ON c.id=r.category_id WHERE r.id=$id");
if (!$r) {
    flash_set('danger', 'Request not found.');
    redirect_to($base . '/requests/list.php');
}
if ($r['status'] !== 'warehouse_check') {
    flash_set('warning', 'This request is not in the warehouse-check stage (now: ' . req_status_label($r['status']) . ').');
    redirect_to($base . '/requests/view.php?id=' . $id);
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT w.*, c.name cat FROM warehouse_items w
        LEFT JOIN categories c ON c.id=w.category_id";
if ($q !== '') { $sql .= " WHERE w.name LIKE '%" . esc($q) . "%' OR c.name LIKE '%" . esc($q) . "%'"; }
$sql .= ' ORDER BY w.name';
$items = fetch_all($sql);

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'issue') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $note    = trim($_POST['note'] ?? '');
        $item = fetch_one("SELECT * FROM warehouse_items WHERE id=$item_id");
        $need = (float)$r['quantity'];
        if (!$item)                                      { $errors[] = 'Select a warehouse item.'; }
        elseif ((float)$item['quantity'] < $need)        { $errors[] = 'Insufficient stock (' . xnum($item['quantity']) . ' ' . h($item['unit']) . ' left, ' . xnum($need) . ' needed).'; }
        if (count($errors) === 0) {
            $ok1 = exec_sql("UPDATE warehouse_items SET quantity = quantity - $need, updated_at=NOW() WHERE id=$item_id AND quantity >= $need");
            if ($ok1 && affected_rows() === 1) {
                $ok2 = exec_sql("INSERT INTO consumptions
                        (item_id, request_id, department_id, item_name, category_id, quantity, unit, source, delivery_date, delivered_by, notes)
                        VALUES ($item_id, $id, " . (int)$r['department_id'] . ", '" . esc($item['name']) . "', " . ($item['category_id'] ? (int)$item['category_id'] : 'NULL') . ",
                                $need, '" . esc($item['unit']) . "', 'warehouse', '" . today() . "', " . (int)$user['id'] . ", '" . esc($note) . "')");
                $ok3 = exec_sql("UPDATE procurement_requests SET status='completed', warehouse_note='" . esc($note === '' ? 'Issued from warehouse' : $note) . "' WHERE id=$id");
                if ($ok2 && $ok3) {
                    flash_set('success', 'Item issued - request fulfilled.');
                    redirect_to($base . '/requests/view.php?id=' . $id);
                }
                exec_sql("UPDATE warehouse_items SET quantity = quantity + $need WHERE id=$item_id"); /* rollback */
                $errors[] = 'Could not record the issue: ' . last_error();
            } else {
                $errors[] = 'Stock changed meanwhile - please refresh and retry.';
            }
        }
    } elseif ($action === 'unavailable') {
        $note = trim($_POST['note'] ?? 'No matching stock.');
        if (exec_sql("UPDATE procurement_requests SET status='purchase_required', warehouse_note='" . esc($note) . "' WHERE id=$id")) {
            flash_set('warning', 'Marked as not available - moved to purchase flow.');
            redirect_to($base . '/requests/view.php?id=' . $id);
        }
        $errors[] = 'Update failed: ' . last_error();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-upc-scan me-2"></i>Check Availability For <?php echo h($r['request_no']); ?></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3"><strong>Department</strong><br><?php echo h($r['dept']); ?></div>
      <div class="col-md-4"><strong>Item</strong><br><?php echo h($r['item_name']); ?></div>
      <div class="col-md-2"><strong>Needed</strong><br><?php echo xnum($r['quantity']); ?> <?php echo h($r['unit']); ?></div>
      <div class="col-md-3"><strong>Urgency</strong><br><?php echo badge($r['urgency']); ?></div>
    </div>
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger mt-2 py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-boxes me-2"></i>Warehouse Stock</span>
    <form method="get" class="d-flex gap-2 mb-0">
      <input type="hidden" name="request_id" value="<?php echo $id; ?>">
      <input type="text" name="q" class="form-control form-control-sm" value="<?php echo h($q); ?>" placeholder="Search stock...">
      <button class="btn btn-sm btn-outline-primary" type="submit">Search</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>Item</th><th>Category</th><th>Stock</th><th>Unit</th><th>Location</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $w): ?>
        <?php $enough = (float)$w['quantity'] >= (float)$r['quantity']; ?>
        <tr class="<?php echo $enough ? '' : 'table-warning'; ?>">
          <td><?php echo h($w['name']); ?></td>
          <td class="text-muted small"><?php echo h($w['cat'] ?? '—'); ?></td>
          <td class="<?php echo $enough ? 'text-success fw-semibold' : 'text-danger fw-semibold'; ?>"><?php echo xnum($w['quantity']); ?></td>
          <td><?php echo h($w['unit']); ?></td>
          <td class="text-muted small"><?php echo h($w['location'] ?? '—'); ?></td>
          <td>
            <?php if ($enough): ?>
              <button class="btn btn-sm btn-success" type="button" data-bs-toggle="modal" data-bs-target="#issueModal"
                      data-item="<?php echo $w['id']; ?>" data-name="<?php echo h($w['name']); ?>">Issue to Department</button>
            <?php else: ?>
              <span class="badge text-bg-warning">Insufficient</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($items) === 0): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">No stock found. <a href="inventory.php">Manage inventory</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card border-danger-subtle">
  <div class="card-header text-danger"><i class="bi bi-x-circle me-1"></i>Item NOT Available in Warehouse?</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="unavailable">
      <div class="col-md-8"><input type="text" name="note" class="form-control" placeholder="e.g. Not stocked - notify procurement"></div>
      <div class="col-md-4"><button class="btn btn-outline-danger w-100" type="submit">Send to Purchase Flow</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="issueModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="issue">
        <input type="hidden" name="item_id" id="issueItemId" value="0">
        <div class="modal-header"><h5 class="modal-title">Issue Item To Department</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p>Issuing <strong id="issueItemName">—</strong> (<?php echo xnum($r['quantity']); ?> <?php echo h($r['unit']); ?>) to <strong><?php echo h($r['dept']); ?></strong>.</p>
          <div class="mb-2"><label class="form-label">Note (optional)</label>
            <input type="text" name="note" class="form-control" value="Issued for <?php echo h($r['item_name']); ?>"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-success" type="submit"><i class="bi bi-check2 me-1"></i>Confirm Issue</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('click', function (e) {
  var t = e.target.closest('[data-bs-target="#issueModal"]');
  if (t) {
    document.getElementById('issueItemId').value = t.getAttribute('data-item');
    document.getElementById('issueItemName').textContent = t.getAttribute('data-name');
  }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>