<?php
/** General manager dashboard panel - strategic overview. */
$recentReq = fetch_all("SELECT r.request_no, d.name dept, r.item_name, r.status, r.request_date
  FROM procurement_requests r JOIN departments d ON d.id=r.department_id ORDER BY r.id DESC LIMIT 8");
$recentPur = fetch_all("SELECT p.purchase_no, s.name supplier, p.total_cost, p.purchase_date, p.status
  FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id ORDER BY p.id DESC LIMIT 8");
$deptCons  = fetch_all("SELECT d.name dept, COUNT(*) times, COALESCE(SUM(c.quantity),0) qty
  FROM consumptions c JOIN departments d ON d.id=c.department_id
  GROUP BY c.department_id ORDER BY qty DESC LIMIT 6");
?>
<div class="row g-3 mb-3">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-inboxes me-2"></i>Latest Procurement Requests</div>
      <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Req No</th><th>Department</th><th>Item</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recentReq as $r): ?>
          <tr><td><?php echo h($r['request_no']); ?></td><td><?php echo h($r['dept']); ?></td>
              <td><?php echo h($r['item_name']); ?></td><td><?php echo badge($r['status']); ?></td>
              <td class="text-muted small text-nowrap"><?php echo h($r['request_date']); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Top Consuming Departments</div>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Department</th><th>Issues</th><th>Qty</th></tr></thead>
        <tbody>
        <?php foreach ($deptCons as $c): ?>
          <tr><td><?php echo h($c['dept']); ?></td><td><?php echo $c['times']; ?></td>
              <td class="text-nowrap"><?php echo xnum($c['qty']); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-currency-dollar me-2"></i>Latest Purchases (costs)</span>
    <a class="small" href="<?php echo $base; ?>/reports/index.php">Open Report Center &rarr;</a>
  </div>
  <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>PO No</th><th>Supplier</th><th>Total (PKR)</th><th>Date</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($recentPur as $p): ?>
      <tr><td><?php echo h($p['purchase_no']); ?></td><td><?php echo h($p['supplier'] ?? '-'); ?></td>
          <td class="text-nowrap"><?php echo money0($p['total_cost']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? ''); ?></td>
          <td><?php echo badge($p['status']); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>