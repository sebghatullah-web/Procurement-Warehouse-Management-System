<?php
/**
 * Purchase detail + workflow actions.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'Purchase Detail';
$base = BASE_URL;
$role = $user['role'];
$isPro = in_array($role, ['procurement_manager','admin'], true);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_set('danger', 'Invalid purchase.'); redirect_to($base . '/purchases/list.php'); }

$p = fetch_one("SELECT p.*, r.request_no, r.item_name AS req_item, r.department_id,
                       r.quantity AS req_qty, r.unit AS req_unit, r.urgency, r.direct_delivery,
                       d.name AS dept, s.name AS supplier_name
                FROM purchases p
                JOIN procurement_requests r ON r.id=p.request_id
                JOIN departments d ON d.id=r.department_id
                LEFT JOIN suppliers s ON s.id=p.supplier_id
                WHERE p.id=$id");

if (!$p) { flash_set('danger', 'Purchase not found.'); redirect_to($base . '/purchases/list.php'); }

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_quote' && $isPro) {
        $sid   = (int)($_POST['supplier_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $days  = (int)($_POST['delivery_days'] ?? 0);
        $note  = trim($_POST['quote_note'] ?? '');
        if ($sid <= 0 || $price <= 0) { $errors[] = 'Supplier and valid price are required.'; }
        else {
            $ok = exec_sql("INSERT INTO quotations (request_id, supplier_id, price, delivery_days, notes)
                            VALUES (" . (int)$p['request_id'] . ", $sid, $price, " . ($days ?: 'NULL') . ", '" . esc($note) . "')");
            if ($ok) { flash_set('success', 'Quotation added.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
            $errors[] = last_error();
        }
    } elseif ($action === 'submit_committee' && $isPro) {
        $ok = exec_sql("UPDATE purchases SET status='committee_pending', committee_note='" . esc(trim($_POST['committee_note'] ?? '')) . "' WHERE id=$id");
        if ($ok) { flash_set('success', 'Submitted to committee.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
        $errors[] = last_error();
    } elseif ($action === 'committee_decision') {
        $member = trim($_POST['member_name'] ?? '');
        $mrole  = trim($_POST['member_role'] ?? 'finance');
        $dec    = trim($_POST['decision'] ?? 'approved');
        $comment= trim($_POST['comment'] ?? '');
        if ($member === '') { $errors[] = 'Member name required.'; }
        else {
            $ok = exec_sql("INSERT INTO committee_approvals (purchase_id, member_name, member_role, decision, comment)
                            VALUES ($id, '" . esc($member) . "', '" . esc($mrole) . "', '" . esc($dec) . "', '" . esc($comment) . "')");
            if ($ok) {
                $newStatus = ($dec === 'approved') ? 'approved' : 'rejected';
                exec_sql("UPDATE purchases SET status='" . esc($newStatus) . "' WHERE id=$id");
                flash_set('success', 'Decision recorded: ' . ucfirst($dec) . '.');
                redirect_to($base . '/purchases/view.php?id=' . $id);
            }
            $errors[] = last_error();
        }
    } elseif ($action === 'resubmit' && $isPro) {
        $ok = exec_sql("UPDATE purchases SET status='quotation', committee_note=NULL WHERE id=$id");
        if ($ok) { flash_set('success', 'Re-opened for revision.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
        $errors[] = last_error();
    } elseif ($action === 'finalize' && $isPro) {
        $quotePath = trim($_POST['quote_path'] ?? '');
        $supId     = (int)($_POST['supplier_id'] ?? 0);
        $unitPrice = (float)($_POST['unit_price'] ?? 0);
        $pDate     = trim($_POST['purchase_date'] ?? today());
        if ($unitPrice <= 0) { $errors[] = 'Unit price must be greater than zero.'; }
        else {
            $supSql = ($supId > 0) ? ", supplier_id=$supId" : '';
            $total  = $unitPrice * (float)$p['quantity'];
            $ok = exec_sql("UPDATE purchases SET unit_price=$unitPrice, total_cost=$total,
                            purchase_date='" . esc($pDate) . "', status='ordered'$supSql WHERE id=$id");
            if ($ok) { flash_set('success', 'Order finalized @ ' . money0($unitPrice) . ' PKR/unit.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
            $errors[] = last_error();
        }
    }
}

$quotations = fetch_all("SELECT q.*, s.name AS supplier
                        FROM quotations q
                        JOIN suppliers s ON s.id=q.supplier_id
                        WHERE q.request_id=" . (int)$p['request_id']);

$committee = fetch_all("SELECT * FROM committee_approvals WHERE purchase_id=$id ORDER BY approved_at DESC");
$gate      = fetch_all("SELECT * FROM gate_checklists WHERE purchase_id=$id ORDER BY check_date DESC");
$consumptions = fetch_all("SELECT c.*, d.name AS dept FROM consumptions c
                           JOIN departments d ON d.id=c.department_id
                           WHERE c.request_id=" . (int)$p['request_id'] . " OR c.item_name LIKE '%" . esc($p['req_item']) . "%'
                           ORDER BY c.delivery_date DESC");
$suppliers = fetch_all("SELECT id, name FROM suppliers ORDER BY name");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-cart-check me-2"></i><?php echo h($p['purchase_no']); ?> - <?php echo h($p['req_item']); ?></span>
    <?php echo badge($p['status']); ?>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3"><strong>Request</strong><br><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $p['request_id']; ?>"><?php echo h($p['request_no']); ?></a></div>
      <div class="col-md-3"><strong>Department</strong><br><?php echo h($p['dept']); ?></div>
      <div class="col-md-2"><strong>Qty</strong><br><?php echo xnum($p['quantity']); ?> <?php echo h($p['req_unit']); ?></div>
      <div class="col-md-2"><strong>Urgency</strong><br><?php echo badge($p['urgency']); ?></div>
      <div class="col-md-2"><strong>Direct delivery</strong><br><?php echo $p['direct_delivery'] ? 'Yes' : 'No'; ?></div>
      <?php if ($p['supplier_name']): ?>
      <div class="col-md-3"><strong>Supplier</strong><br><?php echo h($p['supplier_name']); ?></div>
      <?php endif; ?>
      <?php if ($p['total_cost']): ?>
      <div class="col-md-3"><strong>Total Cost</strong><br class="text-nowrap"><?php echo money0($p['total_cost']); ?> PKR</div>
      <?php endif; ?>
      <?php if ($p['purchase_date']): ?>
      <div class="col-md-3"><strong>Purchase Date</strong><br class="text-nowrap"><?php echo h($p['purchase_date']); ?></div>
      <?php endif; ?>
      <?php if ($p['payment_status']): ?>
      <div class="col-md-3"><strong>Payment</strong><br><?php echo badge($p['payment_status']); ?></div>
      <?php endif; ?>
    </div>
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger mt-2 py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>

<?php if ($isPro && in_array($p['status'], ['draft','quotation'], true)): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-tags me-2"></i>Add Quotation (need at least 3 for normal purchases)</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="add_quote">
      <div class="col-md-4">
        <select name="supplier_id" class="form-select" required>
          <option value="">-- supplier --</option>
          <?php foreach ($suppliers as $s): ?>
          <option value="<?php echo $s['id']; ?>"><?php echo h($s['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><input type="number" name="price" class="form-control" placeholder="Unit price (PKR)" required min="0.01" step="any"></div>
      <div class="col-md-2"><input type="number" name="delivery_days" class="form-control" placeholder="Days" min="0"></div>
      <div class="col-md-3"><input type="text" name="quote_note" class="form-control" placeholder="Note (optional)"></div>
      <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">Add</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (count($quotations) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-tags me-2"></i>Quotations (<?php echo count($quotations); ?>)</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>Supplier</th><th>Unit Price (PKR)</th><th>Delivery (days)</th><th>Notes</th></tr></thead>
      <tbody>
      <?php foreach ($quotations as $qt): ?>
        <tr><td><?php echo h($qt['supplier']); ?></td>
            <td class="text-nowrap"><?php echo money0($qt['price']); ?></td>
            <td><?php echo h($qt['delivery_days'] ?? '—'); ?></td>
            <td class="text-muted small"><?php echo h($qt['notes'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($isPro && in_array($p['status'], ['draft','quotation'], true) && count($quotations) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-people me-2"></i>Submit To Committee</div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="action" value="submit_committee">
      <div class="row g-2">
        <div class="col-md-8"><input type="text" name="committee_note" class="form-control" placeholder="Note for committee (optional)"></div>
        <div class="col-md-4"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-send-check me-1"></i>Submit For Approval</button></div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($p['status'] === 'committee_pending' && in_array($role, ['committee','procurement_manager','admin'], true)): ?>
<div class="card mb-3 border-primary">
  <div class="card-header bg-primary-subtle text-primary-emphasis"><i class="bi bi-people me-2"></i>Committee Decision</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="committee_decision">
      <div class="col-md-3"><label class="form-label required">Member Name</label>
        <input type="text" name="member_name" class="form-control" required placeholder="e.g. Faisal - Finance"></div>
      <div class="col-md-3"><label class="form-label">Role</label>
        <select name="member_role" class="form-select">
          <option value="finance">Finance</option><option value="management">Management</option>
          <option value="procurement">Procurement</option>
        </select></div>
      <div class="col-md-2"><label class="form-label required">Decision</label>
        <select name="decision" class="form-select">
          <option value="approved">Approve</option><option value="rejected">Reject</option>
        </select></div>
      <div class="col-md-4"><label class="form-label">Comment</label>
        <input type="text" name="comment" class="form-control" placeholder="Optional"></div>
      <div class="col-12"><button class="btn btn-success" type="submit"><i class="bi bi-check2 me-1"></i>Submit Decision</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($p['status'] === 'rejected' && $isPro): ?>
<div class="card mb-3">
  <div class="card-body">
    <form method="post"><input type="hidden" name="action" value="resubmit">
      <button class="btn btn-outline-primary" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Re-open &amp; Revise</button></form>
  </div>
</div>
<?php endif; ?>

<?php if ($p['status'] === 'approved' && $isPro): ?>
<div class="card mb-3 border-success">
  <div class="card-header bg-success-subtle text-success-emphasis"><i class="bi bi-check2-circle me-2"></i>Finalize Order</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="finalize">
      <div class="col-md-4">
        <label class="form-label">From Quotation</label>
        <select name="quote_path" class="form-select" id="quoteSelect">
          <option value="">-- select a quotation --</option>
          <?php foreach ($quotations as $qt): ?>
          <option value="<?php echo $qt['supplier_id']; ?>|<?php echo money0($qt['price']); ?>"><?php echo h($qt['supplier']); ?> @ <?php echo money0($qt['price']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">Or Supplier</label>
        <select name="supplier_id" class="form-select" id="supplierSelect">
          <option value="0">-- supplier --</option>
          <?php foreach ($suppliers as $s): ?>
          <option value="<?php echo $s['id']; ?>"><?php echo h($s['name']); ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-2"><label class="form-label">Unit Price (PKR)</label>
        <input type="number" name="unit_price" class="form-control" min="0.01" step="any" required></div>
      <div class="col-md-2"><label class="form-label">Date</label>
        <input type="date" name="purchase_date" class="form-control" required value="<?php echo today(); ?>"></div>
      <div class="col-md-1 pt-4"><button class="btn btn-success w-100" type="submit">Order</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (count($committee) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-people me-2"></i>Committee Approvals</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th>Member</th><th>Role</th><th>Decision</th><th>Comment</th></tr></thead>
      <tbody>
      <?php foreach ($committee as $a): ?>
        <tr><td class="text-nowrap small text-muted"><?php echo h($a['approved_at']); ?></td>
            <td><?php echo h($a['member_name']); ?></td>
            <td class="text-muted small"><?php echo h($a['member_role'] ?? '—'); ?></td>
            <td><?php echo $a['decision'] === 'approved' ? '<span class="badge text-bg-success">Approved</span>' : '<span class="badge text-bg-danger">Rejected</span>'; ?></td>
            <td class="text-muted small"><?php echo h($a['comment'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (count($gate) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-shield-check me-2"></i>Gate Receipt Checklist</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th>Received By</th><th>Qty Received</th><th>Condition</th><th>Remarks</th></tr></thead>
      <tbody>
      <?php foreach ($gate as $g): ?>
        <tr><td class="text-nowrap small text-muted"><?php echo h($g['check_date']); ?></td>
            <td>#<?php echo (int)$g['received_by']; ?></td>
            <td><?php echo xnum($g['quantity_received']); ?></td>
            <td><?php echo $g['condition_ok'] ? '<span class="badge text-bg-success">OK</span>' : '<span class="badge text-bg-danger">Damaged / Short</span>'; ?></td>
            <td class="text-muted small"><?php echo h($g['remarks'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (count($consumptions) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-box-arrow-up me-2"></i>Deliveries / Usage</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th>Department</th><th>Item</th><th>Qty</th><th>Source</th></tr></thead>
      <tbody>
      <?php foreach ($consumptions as $c): ?>
        <tr><td class="text-nowrap"><?php echo h($c['delivery_date']); ?></td>
            <td><?php echo h($c['dept']); ?></td><td><?php echo h($c['item_name']); ?></td>
            <td><?php echo xnum($c['quantity']); ?> <?php echo h($c['unit']); ?></td>
            <td><?php echo badge($c['source']); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<a class="btn btn-outline-secondary" href="javascript:history.back()"><i class="bi bi-arrow-left me-1"></i>Back</a>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>