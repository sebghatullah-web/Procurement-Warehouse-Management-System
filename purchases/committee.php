<?php
/**
 * Committee approvals list.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'committee', 'admin');
$page_title = 'Committee Approvals';
$base = BASE_URL;

$rows = fetch_all("SELECT ca.*, p.purchase_no, p.request_id, r.item_name, r.department_id, d.name dept
                   FROM committee_approvals ca
                   JOIN purchases p ON p.id=ca.purchase_id
                   JOIN procurement_requests r ON r.id=p.request_id
                   JOIN departments d ON d.id=r.department_id
                   ORDER BY ca.approved_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header"><i class="bi bi-people me-2"></i>Committee Decisions <span class="text-muted small">(<?php echo count($rows); ?>)</span></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Date</th><th>PO No</th><th>Request</th><th>Department</th><th>Item</th><th>Member</th><th>Role</th><th>Decision</th><th>Comment</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $a): ?>
        <tr>
          <td class="text-nowrap small text-muted"><?php echo h($a['approved_at']); ?></td>
          <td><a href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $a['purchase_id']; ?>"><?php echo h($a['purchase_no']); ?></a></td>
          <td><?php echo h($a['request_id']); ?></td>
          <td class="text-muted small"><?php echo h($a['dept']); ?></td>
          <td><?php echo h($a['item_name']); ?></td>
          <td><?php echo h($a['member_name']); ?></td>
          <td class="text-muted small"><?php echo h($a['member_role'] ?? '—'); ?></td>
          <td><?php echo $a['decision'] === 'approved' ? '<span class="badge text-bg-success">Approved</span>' : '<span class="badge text-bg-danger">Rejected</span>'; ?></td>
          <td class="text-muted small"><?php echo h($a['comment'] ?? '—'); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No committee decisions recorded.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>