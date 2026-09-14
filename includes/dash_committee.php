<?php
/** Committee member dashboard panel. */
$pending = fetch_all("SELECT p.id, p.purchase_no, p.total_cost, r.request_no, r.item_name, d.name dept,
        (SELECT COUNT(*) FROM quotations q WHERE q.request_id=r.id) quotes
  FROM purchases p JOIN procurement_requests r ON r.id=p.request_id
  JOIN departments d ON d.id=r.department_id
  WHERE p.status='committee_pending' ORDER BY p.id ASC");
$done = fetch_all("SELECT ca.*, p.purchase_no FROM committee_approvals ca
  JOIN purchases p ON p.id=ca.purchase_id ORDER BY ca.approved_at DESC LIMIT 6");
?>
<div class="row g-3 mb-3">
  <div class="col-lg-12">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-people me-2"></i>Purchases Awaiting Committee Decision</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>PO No</th><th>Request</th><th>Department</th><th>Item</th><th>Quotes</th><th>Total (PKR)</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($pending as $p): ?>
            <tr>
              <td><?php echo h($p['purchase_no']); ?></td>
              <td><?php echo h($p['request_no']); ?></td>
              <td><?php echo h($p['dept']); ?></td>
              <td><?php echo h($p['item_name']); ?></td>
              <td><?php echo $p['quotes']; ?></td>
              <td class="text-nowrap"><?php echo money0($p['total_cost']); ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p['id']; ?>">Review &amp; Decide</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($pending) === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Nothing awaiting committee approval right now.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header">Recent Committee Decisions</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>Date</th><th>PO No</th><th>Member</th><th>Role</th><th>Decision</th><th>Comment</th></tr></thead>
      <tbody>
      <?php foreach ($done as $a): ?>
        <tr>
          <td class="text-nowrap"><?php echo h($a['approved_at']); ?></td>
          <td><?php echo h($a['purchase_no']); ?></td>
          <td><?php echo h($a['member_name']); ?></td>
          <td class="text-muted small"><?php echo h($a['member_role'] ?? '-'); ?></td>
          <td><?php echo $a['decision'] === 'approved' ? '<span class="badge text-bg-success">Approved</span>' : '<span class="badge text-bg-danger">Rejected</span>'; ?></td>
          <td class="text-muted small"><?php echo h($a['comment'] ?? '-'); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>