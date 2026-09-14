<?php
/**
 * New procurement request.
 * Role: employee (own department), procurement_manager / admin (any department).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('employee', 'procurement_manager', 'admin');
$page_title = 'New Procurement Request';
$base = BASE_URL;
$role = $user['role'];

$isManager = ($role === 'procurement_manager' || $role === 'admin');

$errors = [];
$old = $_POST;

if (is_post()) {
    $dept_id = (int)($old['department_id'] ?? 0);
    $item    = trim($old['item_name'] ?? '');
    $cat_id  = (int)($old['category_id'] ?? 0);
    $qty     = (float)($old['quantity'] ?? 0);
    $unit    = trim($old['unit'] ?? 'pcs');
    $urgency = ($old['urgency'] ?? 'normal') === 'urgent' ? 'urgent' : 'normal';
    $direct  = isset($old['direct_delivery']) ? 1 : 0;
    $reason  = trim($old['reason'] ?? '');
    $rdate   = trim($old['request_date'] ?? today());

    if (!$isManager) { $dept_id = (int)$user['department_id']; }
    if (!$dept_id)       { $errors[] = 'Please choose a department.'; }
    if ($item === '')    { $errors[] = 'Item name is required.'; }
    if ($qty <= 0)       { $errors[] = 'Quantity must be greater than zero.'; }
    if ($rdate === '')   { $rdate = today(); }

    if (count($errors) === 0) {
        $prefix = 'REQ-' . date('Y') . '-';
        $last = fetch_one("SELECT request_no FROM procurement_requests
                           WHERE request_no LIKE '$prefix%' ORDER BY request_no DESC LIMIT 1");
        $n = $last ? (int)substr($last['request_no'], strlen($prefix)) + 1 : 1;
        $request_no = $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO procurement_requests
                (request_no, department_id, category_id, item_name, quantity, unit,
                 urgency, direct_delivery, reason, status, requested_by, request_date)
                VALUES ('" . esc($request_no) . "', $dept_id, " . ($cat_id ? $cat_id : 'NULL') . ",
                        '" . esc($item) . "', $qty, '" . esc($unit) . "', '$urgency', $direct,
                        '" . esc($reason) . "', 'pending', " . (int)$user['id'] . ", '$rdate')";
        if (exec_sql($sql)) {
            flash_set('success', 'Request ' . $request_no . ' submitted successfully.');
            redirect_to($base . '/requests/view.php?id=' . inserted_id());
        }
        $errors[] = 'Could not save: ' . last_error();
    }
}

$user = current_user();
$depts = fetch_all('SELECT id, name FROM departments ORDER BY name');
$cats  = fetch_all('SELECT id, name FROM categories ORDER BY name');
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-plus-square me-2"></i>Request Details</div>
      <div class="card-body">
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-danger py-2">
            <ul class="mb-1"><?php foreach ($errors as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label required">Department</label>
              <select name="department_id" class="form-select" <?php echo $isManager ? '' : 'disabled'; ?>>
                <?php foreach ($depts as $d): ?>
                <option value="<?php echo $d['id']; ?>" <?php echo (int)($old['department_id'] ?? ($user['department_id'] ?? 0)) === (int)$d['id'] ? 'selected' : ''; ?>><?php echo h($d['name']); ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!$isManager): ?>
                <input type="hidden" name="department_id" value="<?php echo (int)$user['department_id']; ?>">
                <div class="form-text text-muted small">Your department is fixed. Contact procurement if you need to change it.</div>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select">
                <option value="0">-- select category --</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo (int)($old['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label required">Item Name</label>
              <input type="text" name="item_name" class="form-control" required
                     value="<?php echo h($old['item_name'] ?? ''); ?>"
                     placeholder="e.g. Cement bags, Office chair, A4 paper...">
            </div>
            <div class="col-4">
              <label class="form-label required">Quantity</label>
              <input type="number" name="quantity" min="0.01" step="any" class="form-control" required
                     value="<?php echo h($old['quantity'] ?? ''); ?>">
            </div>
            <div class="col-4">
              <label class="form-label required">Unit</label>
              <select name="unit" class="form-select">
                <?php foreach (['pcs','bag','ton','kg','ream','roll','liter','set','pair','box','coil','meter'] as $u): ?>
                <option value="<?php echo $u; ?>" <?php echo ($old['unit'] ?? 'pcs') === $u ? 'selected' : ''; ?>><?php echo $u; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label required">Request Date</label>
              <input type="date" name="request_date" class="form-control" value="<?php echo h($old['request_date'] ?? today()); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Urgency</label>
              <select name="urgency" class="form-select">
                <option value="normal" <?php echo ($old['urgency'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal (quotation + committee)</option>
                <option value="urgent" <?php echo ($old['urgency'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent (immediate approval)</option>
              </select>
              <div class="form-text text-muted small">Urgent requests skip quotation and committee steps.</div>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch mt-4">
                <input class="form-check-input" type="checkbox" id="direct" name="direct_delivery" <?php echo isset($old['direct_delivery']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="direct">Direct delivery (do not store in warehouse)</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Reason / Description</label>
              <textarea name="reason" rows="3" class="form-control" placeholder="Why is this needed?"><?php echo h($old['reason'] ?? ''); ?></textarea>
            </div>
          </div>
          <hr>
          <button class="btn btn-primary px-4" type="submit"><i class="bi bi-send-check me-2"></i>Submit Request</button>
          <a class="btn btn-outline-secondary ms-2" href="<?php echo $base; ?>/index.php">Cancel</a>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">What happens next?</div>
      <div class="card-body text-muted small">
        <ol class="mb-0">
          <li>Procurement reviews your request.</li>
          <li>If necessary, the warehouse checks availability.</li>
          <li>Available &rarr; item is delivered; otherwise a purchase starts.</li>
          <li>Normal requests need <strong>3 supplier quotations</strong> and committee approval; urgent ones skip straight to buying.</li>
          <li>Incoming goods are checked at the <strong>gate</strong>, then stored or delivered directly.</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>