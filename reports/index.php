<?php
/**
 * Report center - overview page with links and summary.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('general_manager', 'procurement_manager', 'warehouse_manager', 'admin');
$page_title = 'مرکز گزارشات';
$base = BASE_URL;
$role = $user['role'];

$fm = date('Y-m-01');
$yr = date('Y-01-01');

$monthSpend = fetch_one("SELECT COALESCE(SUM(total_cost),0) m FROM purchases WHERE status IN ('completed','received','ordered') AND purchase_date >= '$fm'");
$yearSpend  = fetch_one("SELECT COALESCE(SUM(total_cost),0) y FROM purchases WHERE status IN ('completed','received','ordered') AND purchase_date >= '$yr'");
$monthIssued = fetch_one("SELECT COALESCE(SUM(quantity),0) q FROM consumptions WHERE delivery_date >= '$fm'");
$deptBreakdown = fetch_all("SELECT d.name dept, COUNT(*) c, COALESCE(SUM(co.quantity),0) qty
  FROM consumptions co JOIN departments d ON d.id=co.department_id
  GROUP BY co.department_id ORDER BY qty DESC LIMIT 8");
$catBreakdown  = fetch_all("SELECT c.name cat, COALESCE(SUM(co.quantity),0) qty
  FROM consumptions co LEFT JOIN categories c ON c.id=co.category_id
  GROUP BY co.category_id ORDER BY qty DESC LIMIT 8");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">هزینه این ماه</div>
    <div class="fs-3 fw-bold"><?php echo money0($monthSpend['m']); ?></div>
    <div class="small text-muted">افغانی (؋) / دالر ($)</div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">هزینه امسال</div>
    <div class="fs-3 fw-bold"><?php echo money0($yearSpend['y']); ?></div>
    <div class="small text-muted">افغانی (؋) / دالر ($)</div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">تحویل داده شده این ماه</div>
    <div class="fs-3 fw-bold"><?php echo xnum($monthIssued['q']); ?></div>
    <div class="small text-muted">واحد</div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">پر مصرف‌ترین اداره</div>
    <div class="fs-5 fw-bold"><?php echo count($deptBreakdown) > 0 ? h($deptBreakdown[0]['dept']) : '—'; ?></div>
    <div class="small text-muted"><?php echo count($deptBreakdown) > 0 ? xnum($deptBreakdown[0]['qty']) . ' واحد' : ''; ?></div></div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart me-2"></i>مصرف به تفکیک اداره (برتر)</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>اداره</th><th>دفعات</th><th>تعداد</th></tr></thead>
          <tbody>
          <?php foreach ($deptBreakdown as $d): ?>
            <tr><td><?php echo h($d['dept']); ?></td><td><?php echo $d['c']; ?></td><td class="text-nowrap"><?php echo xnum($d['qty']); ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart me-2"></i>مصرف به تفکیک دسته‌بندی (برتر)</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>دسته‌بندی</th><th>تعداد</th></tr></thead>
          <tbody>
          <?php foreach ($catBreakdown as $c): ?>
            <tr><td><?php echo h($c['cat'] ?? '—'); ?></td><td class="text-nowrap"><?php echo xnum($c['qty']); ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-layout-text-window me-2"></i>گزارشات</div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><a class="btn btn-outline-primary w-100 h-100 text-start" href="purchases.php">
        <i class="bi bi-currency-dollar me-2"></i>گزارش خرید</a></div>
      <div class="col-md-3"><a class="btn btn-outline-primary w-100 h-100 text-start" href="consumption.php">
        <i class="bi bi-pie-chart me-2"></i>گزارش مصرف</a></div>
      <div class="col-md-3"><a class="btn btn-outline-primary w-100 h-100 text-start" href="inventory.php">
        <i class="bi bi-archive me-2"></i>موجودی کنونی</a></div>
      <div class="col-md-3"><a class="btn btn-outline-primary w-100 h-100 text-start" href="cost_analysis.php">
        <i class="bi bi-graph-up me-2"></i>تحلیل هزینه‌ها</a></div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>