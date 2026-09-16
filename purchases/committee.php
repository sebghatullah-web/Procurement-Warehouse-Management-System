<?php
/**
 * Committee approvals list.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'committee', 'admin');
$page_title = 'تصویب‌های کمیته';
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
  <div class="card-header"><i class="bi bi-people me-2"></i>تصمیم‌های کمیته <span class="text-muted small">(<?php echo count($rows); ?>)</span></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>شماره سفارش</th><th>درخواست</th><th>اداره</th><th>کالا</th><th>عضو</th><th>نقش</th><th>تصمیم</th><th>نظر</th></tr></thead>
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
          <td><?php echo $a['decision'] === 'approved' ? '<span class="badge text-bg-success">تصویب شد</span>' : '<span class="badge text-bg-danger">رد شد</span>'; ?></td>
          <td class="text-muted small"><?php echo h($a['comment'] ?? '—'); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">هیچ تصمیم کمیته‌ای ثبت نشده است.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>