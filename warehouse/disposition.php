<?php
/**
 * Warehouse disposition after gate receipt:
 *   A) Store in warehouse   B) Deliver directly to requesting department.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('warehouse_manager', 'admin');
$page_title = 'Store or Deliver Directly';
$base = BASE_URL;

$pid = (int)($_GET['purchase_id'] ?? 0);
$p = fetch_one("SELECT p.*, s.name supplier, r.request_no, r.item_name, r.department_id, r.unit, r.category_id,
                       r.quantity AS req_qty, d.name dept, r.direct_delivery
                FROM purchases p
                LEFT JOIN suppliers s ON s.id=p.supplier_id
                JOIN procurement_requests r ON r.id=p.request_id
                JOIN departments d ON d.id=r.department_id
                WHERE p.id=$pid");
if (!$p) {
    flash_set('danger', 'Purchase not found.');
    redirect_to($base . '/purchases/list.php');
}
if ($p['status'] !== 'received') {
    if ($p['status'] === 'completed') {
        flash_set('info', 'This purchase has already been completed (stored or delivered).');
    } else {
        flash_set('warning', 'Items must be received at the gate before disposition (status: ' . pur_status_label($p['status']) . ').');
    }
    redirect_to($base . '/purchases/view.php?id=' . $pid);
}

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $name   = trim($_POST['name'] ?? '');
        $cat_id = (int)($_POST['category_id'] ?? 0);
        $qty    = (float)($_POST['quantity'] ?? 0);
        $unit   = trim($_POST['unit'] ?? 'pcs');
        $loc    = trim($_POST['location'] ?? '');
        $min    = (float)($_POST['min_stock'] ?? 0);
        $note   = trim($_POST['notes'] ?? '');
        if ($name === '') { $errors[] = 'Item name is required.'; }
        if ($qty <= 0)    { $errors[] = 'Quantity must be positive.'; }
        if (count($errors) === 0) {
            $catSql  = $cat_id ? (string)$cat_id : 'NULL';
            $locSql  = $loc  === '' ? 'NULL' : "'" . esc($loc) . "'";
            $noteSql = $note === '' ? 'NULL' : "'" . esc($note) . "'";
            $existing = fetch_one("SELECT id FROM warehouse_items
                                   WHERE LOWER(name) = '" . esc(strtolower($name)) . "'"
                                   . ($cat_id ? " AND category_id = $cat_id" : ''));
            $ok = $existing
                ? exec_sql("UPDATE warehouse_items SET quantity = quantity + $qty, unit='" . esc($unit) . "', location=$locSql, updated_at=NOW() WHERE id=" . (int)$existing['id'])
                : exec_sql("INSERT INTO warehouse_items (name, category_id, quantity, unit, location, min_stock, notes)
                            VALUES ('" . esc($name) . "', $catSql, $qty, '" . esc($unit) . "', $locSql, $min, $noteSql)");
            if ($ok) {
                exec_sql("UPDATE purchases SET status='completed' WHERE id=$pid");
                exec_sql("UPDATE procurement_requests SET status='completed',
                          warehouse_note='Stored in warehouse (" . esc($name) . ", " . xnum($qty) . " " . esc($unit) . ")' WHERE id=" . (int)$p['request_id']);
                flash_set('success', 'Item stored in warehouse. Purchase completed.');
                redirect_to($base . '/warehouse/inventory.php');
            }
            $errors[] = 'Save failed: ' . last_error();
        }
    } elseif ($action === 'direct') {
        $date = trim($_POST['delivery_date'] ?? today());
        $note = trim($_POST['direct_note'] ?? 'Direct delivery on purchase ' . $p['purchase_no']);
        $qty  = (float)$p['quantity'];
        $ok = exec_sql("INSERT INTO consumptions
                (item_id, request_id, department_id, item_name, category_id, quantity, unit, source, delivery_date, delivered_by, notes)
                VALUES (NULL, " . (int)$p['request_id'] . ", " . (int)$p['department_id'] . ", '" . esc($p['item_name']) . "',
                        " . ($p['category_id'] ? (int)$p['category_id'] : 'NULL') . ", $qty, '" . esc($p['unit']) . "',
                        'direct', '$date', " . (int)$user['id'] . ", '" . esc($note) . "')");
        if ($ok) {
            exec_sql("UPDATE purchases SET status='completed' WHERE id=$pid");
            exec_sql("UPDATE procurement_requests SET status='completed',
                      warehouse_note='Delivered directly to " . esc($p['dept']) . " (" . xnum($qty) . " " . esc($p['unit']) . ")' WHERE id=" . (int)$p['request_id']);
            flash_set('success', 'Delivered directly to ' . $p['dept'] . '. Purchase completed.');
            redirect_to($base . '/requests/view.php?id=' . (int)$p['request_id']);
        }
        $errors[] = 'Delivery failed: ' . last_error();
    }
}

$cats = fetch_all('SELECT id, name FROM categories ORDER BY name');
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-box-seam me-2"></i>Disposition - Purchase <?php echo h($p['purchase_no']); ?></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3"><strong>Request</strong><br><?php echo h($p['request_no']); ?></div>
      <div class="col-md-3"><strong>Item</strong><br><?php echo h($p['item_name']); ?></div>
      <div class="col-md-2"><strong>Quantity</strong><br><?php echo xnum($p['quantity']); ?> <?php echo h($p['unit']); ?></div>
      <div class="col-md-2"><strong>For</strong><br><?php echo h($p['dept']); ?></div>
      <div class="col-md-2"><strong>Supplier</strong><br><?php echo h($p['supplier'] ?? '—'); ?></div>
    </div>
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger mt-2 py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header text-success"><i class="bi bi-boxes me-1"></i>Store In Warehouse</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="store">
          <div class="row g-2">
            <div class="col-12"><label class="form-label required">Item Name</label>
              <input type="text" name="name" class="form-control" required value="<?php echo h($p['item_name']); ?>"></div>
            <div class="col-md-6"><label class="form-label">Category</label>
              <select name="category_id" class="form-select">
                <option value="0">-- none --</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo (int)$p['category_id'] === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['name']); ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-md-6"><label class="form-label required">Quantity</label>
              <input type="number" name="quantity" min="0.01" step="any" class="form-control" required value="<?php echo xnum($p['quantity']); ?>"></div>
            <div class="col-md-4"><label class="form-label">Unit</label>
              <select name="unit" class="form-select">
                <?php foreach (['pcs','bag','ton','kg','ream','roll','liter','set','pair','box','coil','meter'] as $u): ?>
                <option value="<?php echo $u; ?>" <?php echo $p['unit'] === $u ? 'selected' : ''; ?>><?php echo $u; ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-md-4"><label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" placeholder="Shed / Rack"></div>
            <div class="col-md-4"><label class="form-label">Min Stock</label>
              <input type="number" name="min_stock" min="0" step="any" class="form-control" value="0"></div>
            <div class="col-12"><label class="form-label">Notes</label>
              <input type="text" name="notes" class="form-control" value="Purchased <?php echo h($p['purchase_no']); ?>"></div>
          </div>
          <hr>
          <button class="btn btn-success w-100" type="submit"><i class="bi bi-boxes me-1"></i>Store &amp; Complete</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header text-primary"><i class="bi bi-box-arrow-up me-1"></i>Deliver Directly To Department</div>
      <div class="card-body">
        <p class="small text-muted">Recorded as a <strong>direct delivery</strong> to
          <strong><?php echo h($p['dept']); ?></strong>. Used for items that are not stored
          (e.g. site-only materials, urgent repairs) - as chosen at request time
          <?php echo $p['direct_delivery'] ? '<span class="badge text-bg-info">requested direct</span>' : ''; ?>.</p>
        <form method="post">
          <input type="hidden" name="action" value="direct">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label required">Delivery Date</label>
              <input type="date" name="delivery_date" class="form-control" required value="<?php echo today(); ?>"></div>
            <div class="col-md-6"><label class="form-label">Receiver note</label>
              <input type="text" name="direct_note" class="form-control" value="Direct delivery on <?php echo h($p['purchase_no']); ?> signed by <?php echo h($user['name']); ?>"></div>
          </div>
          <hr>
          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-send-check me-1"></i>Confirm Direct Delivery</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>