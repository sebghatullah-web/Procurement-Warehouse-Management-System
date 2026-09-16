<?php
/** Employee dashboard panel - My recent requests. */
$mine = fetch_all("SELECT r.id, r.request_no, d.name dept, r.item_name, r.quantity, r.unit, r.status, r.request_date
  FROM procurement_requests r JOIN departments d ON d.id = r.department_id
  WHERE r.requested_by = " . (int)$user['id'] . " ORDER BY r.id DESC LIMIT 8");
?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-clipboard-plus me-2"></i>درخواست‌های اخیر من</span>
    <a class="small" href="<?php echo $base; ?>/requests/list.php?mine=1">مشاهده همه &larr;</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>شماره درخواست</th><th>اداره</th><th>کالا</th><th>تعداد</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
      <tbody>
      <?php foreach ($mine as $r): ?>
        <tr>
          <td><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $r['id']; ?>"><?php echo h($r['request_no']); ?></a></td>
          <td><?php echo h($r['dept']); ?></td>
          <td><?php echo h($r['item_name']); ?></td>
          <td><?php echo h($r['quantity']); ?> <?php echo h(unit_label($r['unit'])); ?></td>
          <td><?php echo badge($r['status']); ?></td>
          <td class="text-nowrap text-muted small"><?php echo h($r['request_date']); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($mine) === 0): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">هنوز هیچ درخواستی ثبت نکرده‌اید.
          <a href="<?php echo $base; ?>/requests/create.php">اولین درخواست خود را ثبت کنید</a>.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>