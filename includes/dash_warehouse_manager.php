<?php
/** Warehouse manager dashboard panel. */
$check  = fetch_all("SELECT r.id, r.request_no, d.name dept, r.item_name, r.quantity, r.unit FROM procurement_requests r
  JOIN departments d ON d.id=r.department_id WHERE r.status='warehouse_check' ORDER BY r.id DESC LIMIT 6");
$low    = fetch_all("SELECT w.name, w.quantity, w.unit, w.min_stock, w.location FROM warehouse_items w
  WHERE w.quantity <= w.min_stock ORDER BY (w.quantity - w.min_stock) ASC LIMIT 8");
$recent = fetch_all("SELECT c.delivery_date, d.name dept, c.item_name, c.quantity, c.unit, c.source
  FROM consumptions c JOIN departments d ON d.id=c.department_id ORDER BY c.id DESC LIMIT 6");
?>
<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">Requests Awaiting Warehouse Check</div>
      <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>No</th><th>Department</th><th>Item</th><th>Qty</th></tr></thead>
        <tbody>
        <?php foreach ($check as $r): ?>
          <tr><td><a href="<?php echo $base; ?>/warehouse/check.php?request_id=<?php echo $r['id']; ?>"><?php echo h($r['request_no']); ?></a></td>
              <td><?php echo h($r['dept']); ?></td><td><?php echo h($r['item_name']); ?></td>
              <td><?php echo xnum($r['quantity']); ?> <?php echo h($r['unit']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($check) === 0): ?><tr><td colspan="4" class="text-center text-muted py-3">Nothing to check.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alerts</div>
      <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Item</th><th>Stock</th><th>Min</th><th>Location</th></tr></thead>
        <tbody>
        <?php foreach ($low as $w): ?>
          <tr class="low-stock"><td><?php echo h($w['name']); ?></td>
              <td><?php echo xnum($w['quantity']); ?> <?php echo h($w['unit']); ?></td>
              <td><?php echo xnum($w['min_stock']); ?></td>
              <td><?php echo h($w['location'] ?? '-'); ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($low) === 0): ?><tr><td colspan="4" class="text-center text-muted py-3">All items above reorder level &#128640;</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-box-arrow-up me-2"></i>Recent Deliveries &amp; Usage</span>
    <a class="small" href="<?php echo $base; ?>/consumptions/list.php">All usage &rarr;</a>
  </div>
  <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>Date</th><th>Department</th><th>Item</th><th>Qty</th><th>Source</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $c): ?>
      <tr><td class="text-nowrap"><?php echo h($c['delivery_date']); ?></td>
          <td><?php echo h($c['dept']); ?></td><td><?php echo h($c['item_name']); ?></td>
          <td><?php echo xnum($c['quantity']); ?> <?php echo h($c['unit']); ?></td>
          <td><?php echo badge($c['source']); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>