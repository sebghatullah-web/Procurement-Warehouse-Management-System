<?php
/** Admin dashboard panel - system overview. */
$recentReq = fetch_all("SELECT r.id, r.request_no, d.name dept, r.item_name, r.status, r.request_date
  FROM procurement_requests r JOIN departments d ON d.id=r.department_id ORDER BY r.id DESC LIMIT 8");
$recentPur = fetch_all("SELECT p.id, p.purchase_no, s.name supplier, p.total_cost, p.purchase_date, p.status
  FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id ORDER BY p.id DESC LIMIT 8");
$low = fetch_all("SELECT w.name, w.quantity, w.unit, w.min_stock FROM warehouse_items w
  WHERE w.quantity <= w.min_stock ORDER BY (w.quantity - w.min_stock) ASC LIMIT 6");
?>
<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-inboxes me-2"></i>آخرین درخواست‌های تدارکاتی</div>
      <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>شماره درخواست</th><th>اداره</th><th>کالا</th><th>وضعیت</th></tr></thead>
        <tbody>
        <?php foreach ($recentReq as $r): ?>
          <tr><td><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $r['id']; ?>"><?php echo h($r['request_no']); ?></a></td>
              <td><?php echo h($r['dept']); ?></td><td><?php echo h($r['item_name']); ?></td>
              <td><?php echo badge($r['status']); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header text-danger"><i class="bi bi-exclamation-triangle me-2"></i>اقلام کم‌موجودی</div>
      <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>کالا</th><th>موجودی</th><th>حداقل</th></tr></thead>
        <tbody>
        <?php foreach ($low as $w): ?>
          <tr class="low-stock"><td><?php echo h($w['name']); ?></td>
              <td><?php echo xnum($w['quantity']); ?> <?php echo h(unit_label($w['unit'])); ?></td>
              <td><?php echo xnum($w['min_stock']); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-currency-dollar me-2"></i>آخرین خریدها</div>
  <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>شماره سفارش</th><th>تأمین‌کننده</th><th>مجموع (PKR)</th><th>تاریخ</th><th>وضعیت</th></tr></thead>
    <tbody>
    <?php foreach ($recentPur as $p): ?>
      <tr><td><a href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p['id']; ?>"><?php echo h($p['purchase_no']); ?></a></td>
          <td><?php echo h($p['supplier'] ?? '-'); ?></td>
          <td class="text-nowrap"><?php echo money0($p['total_cost']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? ''); ?></td>
          <td><?php echo badge($p['status']); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>