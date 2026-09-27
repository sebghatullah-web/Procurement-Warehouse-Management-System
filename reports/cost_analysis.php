<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('general_manager', 'procurement_manager', 'warehouse_manager', 'admin');
$page_title = 'تحلیل هزینه‌ها';
$base = BASE_URL;

$frm = trim($_GET['from_date'] ?? '');
$to  = trim($_GET['to_date'] ?? '');
$dp  = (int)($_GET['department_id'] ?? 0);
$cat = (int)($_GET['category_id'] ?? 0);

$where = [];
if ($frm !== '') { $where[] = "p.purchase_date >= '" . esc($frm) . "'"; }
if ($to  !== '') { $where[] = "p.purchase_date <= '" . esc($to) . "'"; }
if ($dp > 0)     { $where[] = 'r.department_id = ' . $dp; }
if ($cat > 0)    { $where[] = 'r.category_id = ' . $cat; }

$sql = "SELECT p.*, r.request_no, r.item_name, r.department_id, r.category_id, d.name dept, s.name supplier
        FROM purchases p
        JOIN procurement_requests r ON r.id=p.request_id
        JOIN departments d ON d.id=r.department_id
        LEFT JOIN suppliers s ON s.id=p.supplier_id";
if (count($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.purchase_date DESC';
$rows = fetch_all($sql);
$total = array_sum(array_column($rows, 'total_cost'));
$depts = fetch_all('SELECT id, name FROM departments ORDER BY name');
$cats  = fetch_all('SELECT id, name FROM categories ORDER BY name');

$byDept = [];
foreach ($rows as $r) {
    $k = $r['dept'];
    if (!isset($byDept[$k])) $byDept[$k] = 0;
    $byDept[$k] += (float)($r['total_cost'] ?? 0);
}
arsort($byDept);

$byCat = [];
foreach ($rows as $r) {
    $k = $r['category_id'] ? ($r['category_id'] . '|' . ($r['cat'] ?? '—')) : '—';
    if (!isset($byCat[$k])) $byCat[$k] = 0;
    $byCat[$k] += (float)($r['total_cost'] ?? 0);
}
arsort($byCat);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="filter-box rounded-2 p-3 mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label small text-muted">از تاریخ</label>
      <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo h($frm); ?>"></div>
    <div class="col-auto"><label class="form-label small text-muted">تا تاریخ</label>
      <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo h($to); ?>"></div>
    <div class="col-md-3"><label class="form-label small text-muted">اداره</label>
      <select name="department_id" class="form-select form-select-sm" data-autosubmit>
        <option value="0">همه</option>
        <?php foreach ($depts as $d): ?>
        <option value="<?php echo $d['id']; ?>" <?php echo $dp===(int)$d['id']?'selected':''; ?>><?php echo h($d['name']); ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-3"><label class="form-label small text-muted">دسته‌بندی</label>
      <select name="category_id" class="form-select form-select-sm" data-autosubmit>
        <option value="0">همه</option>
        <?php foreach ($cats as $c): ?>
        <option value="<?php echo $c['id']; ?>" <?php echo $cat===(int)$c['id']?'selected':''; ?>><?php echo h($c['name']); ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-auto pt-3"><button class="btn btn-primary btn-sm" type="submit">فیلتر</button></div>
    <div class="col-auto pt-3"><a class="btn btn-outline-secondary btn-sm" href="<?php echo $base; ?>/reports/cost_analysis.php">بازنشانی</a></div>
    <div class="col-auto pt-3"><strong>مجموع (؋/$): <?php echo money0($total); ?></strong></div>
  </form>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">به تفکیک اداره</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>اداره</th><th>هزینه (؋/$)</th></tr></thead>
          <tbody>
          <?php foreach ($byDept as $dept => $spend): ?>
            <tr><td><?php echo h($dept); ?></td><td class="text-nowrap"><?php echo money0($spend); ?></td></tr>
          <?php endforeach; ?>
          <?php if (count($byDept) === 0): ?>
            <tr><td colspan="2" class="text-center text-muted py-3">داده‌ای موجود نیست.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">به تفکیک دسته‌بندی</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>دسته‌بندی</th><th>هزینه (؋/$)</th></tr></thead>
          <tbody>
          <?php foreach ($byCat as $key => $spend): ?>
            <?php $parts = explode('|', $key, 2); $catName = $parts[1] ?? '—'; ?>
            <tr><td><?php echo h($catName); ?></td><td class="text-nowrap"><?php echo money0($spend); ?></td></tr>
          <?php endforeach; ?>
          <?php if (count($byCat) === 0): ?>
            <tr><td colspan="2" class="text-center text-muted py-3">داده‌ای موجود نیست.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-graph-up me-2"></i>جزئیات خرید</span>
    <button class="btn btn-sm btn-outline-secondary" data-print><i class="bi bi-printer me-1"></i>چاپ</button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>شماره سفارش</th><th>درخواست</th><th>اداره</th><th>دسته‌بندی</th><th>کالا</th><th>تأمین‌کننده</th><th>تعداد</th><th>قیمت واحد</th><th>مجموع</th><th>تاریخ</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $p): ?>
        <tr>
          <td><a href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p['id']; ?>"><?php echo h($p['purchase_no']); ?></a></td>
          <td><?php echo h($p['request_no']); ?></td>
          <td class="text-muted small"><?php echo h($p['dept']); ?></td>
          <td class="text-muted small"><?php echo h($p['cat'] ?? '—'); ?></td>
          <td><?php echo h($p['item_name']); ?></td>
          <td><?php echo h($p['supplier'] ?? '—'); ?></td>
          <td><?php echo xnum($p['quantity']); ?></td>
          <td class="text-nowrap"><?php echo money_cur($p['unit_price'], $p['currency']); ?></td>
          <td class="text-nowrap fw-semibold"><?php echo money_cur($p['total_cost'], $p['currency']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? '—'); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>