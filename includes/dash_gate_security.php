<?php
/** Gate security dashboard panel. */
$toRecv = fetch_all("SELECT p.id, p.purchase_no, p.request_id, p.quantity, s.name supplier, p.purchase_date
  FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id
  WHERE p.status='ordered' ORDER BY p.purchase_date ASC");
$done   = fetch_all("SELECT g.*, p.purchase_no FROM gate_checklists g
  JOIN purchases p ON p.id=g.purchase_id ORDER BY g.check_date DESC LIMIT 6");
?>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-truck me-2"></i>خریدهای در انتظار چک‌لیست رسید گیت</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>شماره سفارش</th><th>درخواست #</th><th>تأمین‌کننده</th><th>تعداد</th><th>تاریخ سفارش</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($toRecv as $p): ?>
        <tr>
          <td><?php echo h($p['purchase_no']); ?></td>
          <td>#<?php echo (int)$p['request_id']; ?></td>
          <td><?php echo h($p['supplier'] ?? '-'); ?></td>
          <td><?php echo xnum($p['quantity']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? ''); ?></td>
          <td><a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/gate/receipts.php?purchase_id=<?php echo $p['id']; ?>">تکمیل چک‌لیست</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($toRecv) === 0): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">هیچ خریدی در انتظار در گیت نیست.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-shield-check me-2"></i>چک‌لیست‌های اخیر گیت</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>شماره سفارش</th><th>تعداد دریافت شده</th><th>وضعیت</th><th>یادداشت</th></tr></thead>
      <tbody>
      <?php foreach ($done as $g): ?>
        <tr>
          <td class="text-nowrap"><?php echo h($g['check_date']); ?></td>
          <td><?php echo h($g['purchase_no']); ?></td>
          <td><?php echo xnum($g['quantity_received']); ?></td>
          <td><?php echo $g['condition_ok'] ? '<span class="badge text-bg-success">سالم</span>' : '<span class="badge text-bg-danger">آسیب‌دیده</span>'; ?></td>
          <td class="text-muted small"><?php echo h($g['remarks'] ?? '-'); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>