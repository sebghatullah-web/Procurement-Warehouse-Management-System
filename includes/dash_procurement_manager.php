<?php
/** Procurement manager dashboard panel. */
$pending = fetch_all("SELECT r.id, r.request_no, d.name dept, r.item_name, r.quantity, r.unit FROM procurement_requests r
  JOIN departments d ON d.id=r.department_id WHERE r.status='pending' ORDER BY r.id DESC LIMIT 6");
$buying  = fetch_all("SELECT r.id, r.request_no, d.name dept, r.item_name, r.status FROM procurement_requests r
  JOIN departments d ON d.id=r.department_id
  WHERE r.status IN ('purchase_required','quotation_pending') ORDER BY r.id DESC LIMIT 6");
$recentPur = fetch_all("SELECT p.id, p.purchase_no, p.request_id, s.name supplier, p.total_cost, p.status, p.purchase_date
  FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id ORDER BY p.id DESC LIMIT 6");
?>
<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">درخواست‌های در انتظار بررسی شما</div>
      <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>شماره</th><th>اداره</th><th>کالا</th><th>تعداد</th></tr></thead>
        <tbody>
        <?php foreach ($pending as $r): ?>
          <tr><td><a href="<?php echo $base; ?>/requests/review.php?id=<?php echo $r['id']; ?>"><?php echo h($r['request_no']); ?></a></td>
              <td><?php echo h($r['dept']); ?></td><td><?php echo h($r['item_name']); ?></td>
              <td><?php echo h($r['quantity']); ?> <?php echo h(unit_label($r['unit'])); ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($pending) === 0): ?><tr><td colspan="4" class="text-center text-muted py-3">صف بررسی خالی است &#127881;</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">درخواست‌ها در فرآیند خرید</div>
      <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>شماره</th><th>اداره</th><th>کالا</th><th>وضعیت</th></tr></thead>
        <tbody>
        <?php foreach ($buying as $r): ?>
          <tr><td><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $r['id']; ?>"><?php echo h($r['request_no']); ?></a></td>
              <td><?php echo h($r['dept']); ?></td><td><?php echo h($r['item_name']); ?></td>
              <td><?php echo badge($r['status']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($buying) === 0): ?><tr><td colspan="4" class="text-center text-muted py-3">خرید فعالی وجود ندارد.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-cart-check me-2"></i>خریدهای اخیر</span>
    <a class="small" href="<?php echo $base; ?>/purchases/list.php">همه خریدها &larr;</a>
  </div>
  <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>شماره سفارش</th><th>درخواست</th><th>تأمین‌کننده</th><th>مجموع (PKR)</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
    <tbody>
    <?php foreach ($recentPur as $p): ?>
      <tr><td><a href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p['id']; ?>"><?php echo h($p['purchase_no']); ?></a></td>
          <td><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $p['request_id']; ?>">#<?php echo $p['request_id']; ?></a></td>
          <td><?php echo h($p['supplier'] ?? '-'); ?></td>
          <td class="text-nowrap"><?php echo money0($p['total_cost']); ?></td>
          <td><?php echo badge($p['status']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? ''); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>