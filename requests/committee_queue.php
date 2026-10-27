<?php
/**
 * Request committee queue, filtered to each reviewer's pending assignment.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$role = $user['role'];
$page_title = 'صف تأیید درخواست‌ها';
$base = BASE_URL;

$conditions = [];
if ($role === 'committee') {
    $conditions[] = "(r.status='pending' OR
                      (r.status='committee_review' AND a.committee_key='department'
                       AND a.assigned_user_id=" . (int)$user['id'] . " AND a.decision='pending'))";
} elseif ($role === 'admin') {
    $conditions[] = "r.status IN ('pending','committee_review')";
} else {
    $keyByRole = [
        'procurement_manager' => 'procurement',
        'contracting_manager' => 'contracting',
        'finance' => 'finance',
    ];
    if (isset($keyByRole[$role])) {
        $key = $keyByRole[$role];
        $conditions[] = "r.status='committee_review'";
        $conditions[] = "a.decision='pending'";
        $conditions[] = "(a.committee_key='$key' OR
                          (a.committee_key='department' AND a.assigned_user_id=" . (int)$user['id'] . '))';
    } else {
        $conditions[] = "r.status='committee_review'";
        $conditions[] = "a.committee_key='department'";
        $conditions[] = "a.assigned_user_id=" . (int)$user['id'];
        $conditions[] = "a.decision='pending'";
    }
}

$rows = fetch_all("SELECT DISTINCT r.id, r.request_no, r.item_name, r.request_type, r.status, r.created_at,
                          d.name dept,
                          (SELECT COUNT(*) FROM request_committee_approvals ca
                           WHERE ca.request_id=r.id) approval_count,
                          (SELECT COUNT(*) FROM request_committee_approvals ca
                           WHERE ca.request_id=r.id AND ca.decision='approved') approved_count
                   FROM procurement_requests r
                   JOIN departments d ON d.id=r.department_id
                   LEFT JOIN request_committee_approvals a ON a.request_id=r.id
                   WHERE " . implode(' AND ', $conditions) . "
                   ORDER BY r.id ASC");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header"><i class="bi bi-people me-2"></i>صف درخواست‌های کمیته <span class="text-muted small">(<?php echo count($rows); ?>)</span></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>شماره</th><th>اداره</th><th>نوع</th><th>کالا / وسیله</th><th>وضعیت</th><th>تأییدها</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?php echo h($row['request_no']); ?></td>
          <td><?php echo h($row['dept']); ?></td>
          <td><?php echo ($row['request_type'] ?? 'purchase') === 'rental' ? 'کرایه' : 'خرید'; ?></td>
          <td><?php echo h($row['item_name']); ?></td>
          <td><?php echo badge($row['status']); ?></td>
          <td><?php echo (int)$row['approved_count']; ?> / <?php echo (int)$row['approval_count']; ?></td>
          <td class="text-nowrap">
            <?php if ($row['status'] === 'pending' && can_manage_request_committee($user)): ?>
            <a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/requests/review.php?id=<?php echo (int)$row['id']; ?>">تعیین بخش و نماینده</a>
            <?php else: ?>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/requests/view.php?id=<?php echo (int)$row['id']; ?>">باز کردن</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">درخواستی در صف شما نیست.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
