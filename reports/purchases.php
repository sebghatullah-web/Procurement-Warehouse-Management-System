<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('general_manager', 'procurement_manager', 'warehouse_manager', 'admin');
$page_title = 'گزارش خرید';
$base = BASE_URL;

$frm = trim($_GET['from_date'] ?? '');
$to  = trim($_GET['to_date'] ?? '');
$st  = trim($_GET['status'] ?? '');
$dp  = (int)($_GET['department_id'] ?? 0);

$where = [];
if ($frm !== '') { $where[] = "p.purchase_date >= '" . esc($frm) . "'"; }
if ($to  !== '') { $where[] = "p.purchase_date <= '" . esc($to) . "'"; }
if ($st  !== '') { $where[] = "p.status = '" . esc($st) . "'"; }
if ($dp > 0)     { $where[] = 'r.department_id = ' . $dp; }

$sql = "SELECT p.*, r.request_no, r.item_name, r.department_id, d.name dept, s.name supplier
        FROM purchases p
        JOIN procurement_requests r ON r.id=p.request_id
        JOIN departments d ON d.id=r.department_id
        LEFT JOIN suppliers s ON s.id=p.supplier_id";
if (count($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.purchase_date DESC';
$rows = fetch_all($sql);
$total = array_sum(array_column($rows, 'total_cost'));
$depts = fetch_all('SELECT id, name FROM departments ORDER BY name');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="filter-box rounded-2 p-3 mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label small text-muted">از تاریخ</label>
      <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo h($frm); ?>"></div>
    <div class="col-auto"><label class="form-label small text-muted">تا تاریخ</label>
      <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo h($to); ?>"></div>
    <div class="col-md-3"><label class="form-label small text-muted">وضعیت</label>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">همه</option>
        <?php foreach (_pur_statuses() as $key => $label): ?>
        <option value="<?php echo $key; ?>" <?php echo $st===$key?'selected':''; ?>><?php echo h($label); ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-3"><label class="form-label small text-muted">اداره</label>
      <select name="department_id" class="form-select form-select-sm" data-autosubmit>
        <option value="0">همه</option>
        <?php foreach ($depts as $d): ?>
        <option value="<?php echo $d['id']; ?>" <?php echo $dp===(int)$d['id']?'selected':''; ?>><?php echo h($d['name']); ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-auto pt-3"><button class="btn btn-primary btn-sm" type="submit">فیلتر</button></div>
    <div class="col-auto pt-3"><a class="btn btn-outline-secondary btn-sm" href="<?php echo $base; ?>/reports/purchases.php">بازنشانی</a></div>
    <div class="col-auto pt-3"><strong>مجموع (AFN): <?php echo money0($total); ?></strong></div>
  </form>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-currency-dollar me-2"></i>خریدها</span>
    <button class="btn btn-sm btn-outline-secondary" data-print><i class="bi bi-printer me-1"></i>چاپ</button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>شماره سفارش</th><th>درخواست</th><th>کالا</th><th>اداره</th><th>تأمین‌کننده</th><th>تعداد</th><th>قیمت واحد</th><th>مجموع</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $p): ?>
        <tr>
          <td><a href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p['id']; ?>"><?php echo h($p['purchase_no']); ?></a></td>
          <td><?php echo h($p['request_no']); ?></td>
          <td><?php echo h($p['item_name']); ?></td>
          <td class="text-muted small"><?php echo h($p['dept']); ?></td>
          <td><?php echo h($p['supplier'] ?? '—'); ?></td>
          <td><?php echo xnum($p['quantity']); ?></td>
          <td class="text-nowrap"><?php echo money_cur($p['unit_price'], $p['currency'] ?? 'AFN'); ?></td>
          <td class="text-nowrap fw-semibold"><?php echo money_cur($p['total_cost'], $p['currency'] ?? 'AFN'); ?></td>
          <td><?php echo badge($p['status']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? '—'); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">برای فیلترهای انتخاب‌شده خریدی یافت نشد.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>