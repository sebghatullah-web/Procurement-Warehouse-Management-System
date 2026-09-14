<?php
/**
 * Request detail page - full information + role-aware actions.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'Request Detail';
$base = BASE_URL;
$role = $user['role'];

$id  = (int)($_GET['id'] ?? 0);
$r = fetch_one("SELECT r.*, d.name dept, c.name cat, u.name requester, rv.name reviewer
                FROM procurement_requests r
                JOIN departments d ON d.id = r.department_id
                LEFT JOIN categories c ON c.id = r.category_id
                LEFT JOIN users u ON u.id = r.requested_by
                LEFT JOIN users rv ON rv.id = r.reviewed_by
                WHERE r.id = $id");
if (!$r) {
    flash_set('danger', 'Request not found.');
    redirect_to($base . '/requests/list.php');
}
/* employees may only open their own requests */
if ($role === 'employee' && (int)$r['requested_by'] !== (int)$user['id']) {
    flash_set('danger', 'You can only view your own requests.');
    redirect_to($base . '/requests/list.php?mine=1');
}
$page_title = 'Request ' . $r['request_no'];

$purchases   = fetch_all("SELECT p.*, s.name supplier FROM purchases p
                          LEFT JOIN suppliers s ON s.id=p.supplier_id
                          WHERE p.request_id = $id ORDER BY p.id DESC");
$quotations  = fetch_all("SELECT q.*, s.name supplier FROM quotations q
                          JOIN suppliers s ON s.id=q.supplier_id
                          WHERE q.request_id = $id ORDER BY q.price ASC");
$consumptions = fetch_all("SELECT c.*, d.name dept FROM consumptions c
                           JOIN departments d ON d.id=c.department_id
                           WHERE c.request_id = $id ORDER BY c.id DESC");
$checklists  = fetch_all("SELECT g.*, p.purchase_no FROM gate_checklists g
                          JOIN purchases p ON p.id=g.purchase_id
                          WHERE p.request_id = $id ORDER BY g.check_date DESC");
$committee = fetch_all("SELECT ca.*, p.purchase_no FROM committee_approvals ca
                        JOIN purchases p ON p.id=ca.purchase_id
                        WHERE p.request_id = $id ORDER BY ca.approved_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clipboard-minus me-2"></i>Request <?php echo h($r['request_no']); ?></span>
        <?php echo badge($r['status']); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4"><div class="text-muted small">Department</div><div class="fw-semibold"><?php echo h($r['dept']); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Category</div><div class="fw-semibold"><?php echo h($r['cat'] ?? '—'); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Request Date</div><div class="fw-semibold"><?php echo h($r['request_date']); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Item</div><div class="fw-semibold"><?php echo h($r['item_name']); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Quantity</div><div class="fw-semibold"><?php echo xnum($r['quantity']); ?> <?php echo h($r['unit']); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Urgency</div><?php echo badge($r['urgency']); ?></div>
          <div class="col-md-4"><div class="text-muted small">Requested by</div><div class="fw-semibold"><?php echo h($r['requester']); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Direct delivery</div><div class="fw-semibold"><?php echo $r['direct_delivery'] ? 'Yes' : 'No'; ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Reviewed by</div><div class="fw-semibold"><?php echo h($r['reviewer'] ?? '—'); ?></div></div>
        </div>
        <div class="mt-3">
          <div class="text-muted small">Reason</div>
          <div><?php echo h($r['reason'] ?? '—'); ?></div>
        </div>
        <?php if ($r['review_note']): ?>
        <div class="mt-3">
          <div class="text-muted small">Review note</div>
          <div class="alert alert-warning py-1 px-2 mb-1 small"><?php echo h($r['review_note']); ?>
            <span class="text-muted">(<?php echo h($r['reviewed_at'] ?? ''); ?>)</span></div>
        </div>
        <?php endif; ?>
        <?php if ($r['warehouse_note']): ?>
        <div class="mt-3">
          <div class="text-muted small">Warehouse note</div>
          <div class="alert alert-info py-1 px-2 mb-1 small"><?php echo h($r['warehouse_note']); ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">Current Stage</div>
      <div class="card-body">
        <div class="fw-semibold mb-2"><?php echo badge($r['status']); ?></div>
        <ul class="small text-muted-2 mb-2 ps-3">
          <li>Submitted by <?php echo h($r['requester']); ?></li>
          <?php if ($r['status'] !== 'pending' && $r['status'] !== 'closed'): ?>
          <li>Reviewed &amp; found necessary by procurement</li>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['purchase_required','quotation_pending','committee_pending','approved','purchased','received','completed'], true)): ?>
          <li>Not available in warehouse - purchase flow started</li>
          <?php endif; ?>
          <?php if ($r['status'] === 'closed'): ?><li class="text-danger">Closed as unnecessary</li><?php endif; ?>
          <?php if (in_array($r['status'], ['quotation_pending','committee_pending','approved','purchased','received','completed'], true)): ?>
          <li>Quotations collected; committee approval required</li>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['purchased','received','completed'], true)): ?>
          <li>Goods ordered from final supplier</li>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['received','completed'], true)): ?>
          <li>Received &amp; verified at gate</li>
          <?php endif; ?>
          <?php if ($r['status'] === 'completed'): ?><li class="text-success"><strong>&#10003; Fulfilled</strong></li><?php endif; ?>
        </ul>
<div class="border-top pt-2">
          <div class="text-muted small mb-1">Actions</div>
          <?php $p0 = count($purchases) > 0 ? $purchases[0] : null; ?>
          <?php if ($r['status'] === 'pending' && ($role === 'procurement_manager' || $role === 'admin')): ?>
            <a class="btn btn-primary w-100" href="<?php echo $base; ?>/requests/review.php?id=<?php echo $r['id']; ?>"><i class="bi bi-check2-circle me-1"></i>Review &amp; Decide</a>
          <?php elseif ($r['status'] === 'warehouse_check' && ($role === 'warehouse_manager' || $role === 'admin')): ?>
            <a class="btn btn-primary w-100" href="<?php echo $base; ?>/warehouse/check.php?request_id=<?php echo $r['id']; ?>"><i class="bi bi-upc-scan me-1"></i>Check Warehouse</a>
          <?php elseif ($r['status'] === 'purchase_required' && ($role === 'procurement_manager' || $role === 'admin')): ?>
            <a class="btn btn-primary w-100" href="<?php echo $base; ?>/purchases/create.php?request_id=<?php echo $r['id']; ?>"><i class="bi bi-cart-plus me-1"></i>Start Purchase Process</a>
          <?php elseif ($p0 && in_array($r['status'], ['quotation_pending','committee_pending','approved','purchased','received','completed'], true)): ?>
            <a class="btn btn-outline-primary w-100" href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p0['id']; ?>"><i class="bi bi-cart-check me-1"></i>Open Purchase #<?php echo h($p0['purchase_no']); ?></a>
            <?php if ($r['status'] === 'purchased' && ($role === 'gate_security' || $role === 'admin')): ?>
              <a class="btn btn-outline-warning w-100 mt-2" href="<?php echo $base; ?>/gate/receipts.php?purchase_id=<?php echo $p0['id']; ?>"><i class="bi bi-shield-check me-1"></i>Complete Gate Checklist</a>
            <?php endif; ?>
            <?php if ($r['status'] === 'received' && ($role === 'warehouse_manager' || $role === 'admin')): ?>
              <a class="btn btn-success w-100 mt-2" href="<?php echo $base; ?>/warehouse/disposition.php?purchase_id=<?php echo $p0['id']; ?>"><i class="bi bi-box-seam me-1"></i>Decide: Store or Deliver Directly</a>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-muted small">No further action needed from you.</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-cart-check me-2"></i>Purchase Record(s)</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>PO No</th><th>Supplier</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($purchases as $p): ?>
        <tr><td><a href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p['id']; ?>"><?php echo h($p['purchase_no']); ?></a></td>
            <td><?php echo h($p['supplier'] ?? '—'); ?></td>
            <td><?php echo xnum($p['quantity']); ?></td>
            <td class="text-nowrap"><?php echo money0($p['unit_price']); ?></td>
            <td class="text-nowrap fw-semibold"><?php echo money0($p['total_cost']); ?></td>
            <td><?php echo badge($p['status']); ?></td>
            <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      <?php if (count($purchases) === 0): ?><tr><td colspan="7" class="text-center text-muted py-3">No purchase yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (count($quotations) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-tags me-2"></i>Supplier Quotations</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Supplier</th><th>Unit Price</th><th>Delivery (days)</th><th>Notes</th></tr></thead>
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

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-box-arrow-up me-2"></i>Deliveries / Usage</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th>Department</th><th>Item</th><th>Qty</th><th>Source</th></tr></thead>
      <tbody>
      <?php foreach ($consumptions as $c): ?>
        <tr><td class="text-nowrap"><?php echo h($c['delivery_date']); ?></td>
            <td><?php echo h($c['dept']); ?></td>
            <td><?php echo h($c['item_name']); ?></td>
            <td><?php echo xnum($c['quantity']); ?> <?php echo h($c['unit']); ?></td>
            <td><?php echo badge($c['source']); ?></td></tr>
      <?php endforeach; ?>
      <?php if (count($consumptions) === 0): ?><tr><td colspan="5" class="text-center text-muted py-3">Not delivered yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (count($checklists) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-shield-check me-2"></i>Gate Receipt Checks</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th>PO</th><th>Qty Received</th><th>Condition</th><th>Remarks</th></tr></thead>
      <tbody>
      <?php foreach ($checklists as $g): ?>
        <tr><td class="text-nowrap small text-muted"><?php echo h($g['check_date']); ?></td>
            <td><?php echo h($g['purchase_no']); ?></td>
            <td><?php echo xnum($g['quantity_received']); ?></td>
            <td><?php echo $g['condition_ok'] ? '<span class="badge text-bg-success">OK</span>' : '<span class="badge text-bg-danger">Damaged / Short</span>'; ?></td>
            <td class="text-muted small"><?php echo h($g['remarks'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<a class="btn btn-outline-secondary" href="javascript:history.back()"><i class="bi bi-arrow-left me-1"></i>Back</a>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>