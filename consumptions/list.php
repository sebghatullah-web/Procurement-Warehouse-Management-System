<?php
/**
 * Deliveries and usage log (consumptions).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'تحویل و مصرف';
$base = BASE_URL;
$role = $user['role'];

$where = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') { $where[] = "(c.item_name LIKE '%" . esc($q) . "%' OR d.name LIKE '%" . esc($q) . "%')"; }
$src = trim($_GET['source'] ?? '');
if ($src !== '') { $where[] = "c.source = '" . esc($src) . "'"; }
$dp = (int)($_GET['department_id'] ?? 0);
if ($dp > 0) { $where[] = 'c.department_id = ' . $dp; }
$frm = trim($_GET['from_date'] ?? '');
$to  = trim($_GET['to_date'] ?? '');
if ($frm !== '') { $where[] = "c.delivery_date >= '" . esc($frm) . "'"; }
if ($to  !== '') { $where[] = "c.delivery_date <= '" . esc($to) . "'"; }

$sql = "SELECT c.*, d.name dept FROM consumptions c
        JOIN departments d ON d.id=c.department_id";
if (count($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY c.id DESC';
$rows = fetch_all($sql);
$depts = fetch_all('SELECT id, name FROM departments ORDER BY name');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="filter-box rounded-2 p-3 mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label small text-muted">جستجو</label>
      <input type="text" name="q" class="form-control form-control-sm" value="<?php echo h($q); ?>" placeholder="کالا / اداره"></div>
    <div class="col-md-2"><label class="form-label small text-muted">منبع</label>
      <select name="source" class="form-select form-select-sm" data-autosubmit>
        <option value="">همه</option>
        <option value="warehouse" <?php echo $src==='warehouse'?'selected':''; ?>>انبار</option>
        <option value="direct" <?php echo $src==='direct'?'selected':''; ?>>تحویل مستقیم</option>
      </select></div>
    <div class="col-md-2"><label class="form-label small text-muted">اداره</label>
      <select name="department_id" class="form-select form-select-sm" data-autosubmit>
        <option value="0">همه</option>
        <?php foreach ($depts as $d): ?>
        <option value="<?php echo $d['id']; ?>" <?php echo $dp===(int)$d['id']?'selected':''; ?>><?php echo h($d['name']); ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-auto"><label class="form-label small text-muted">از تاریخ</label>
      <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo h($frm); ?>"></div>
    <div class="col-auto"><label class="form-label small text-muted">تا تاریخ</label>
      <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo h($to); ?>"></div>
    <div class="col-auto pt-3"><button class="btn btn-primary btn-sm" type="submit">فیلتر</button></div>
    <div class="col-auto pt-3"><a class="btn btn-outline-secondary btn-sm" href="<?php echo $base; ?>/consumptions/list.php">بازنشانی</a></div>
  </form>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-box-arrow-up me-2"></i>تحویل و مصرف <span class="text-muted small">(<?php echo count($rows); ?>)</span></span>
    <div>
      <button class="btn btn-sm btn-outline-secondary" data-print><i class="bi bi-printer me-1"></i>چاپ</button>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="mainTable">
      <thead>
        <tr><th>تاریخ</th><th>اداره</th><th>کالا</th><th>دسته‌بندی</th><th>تعداد</th><th>واحد</th><th>منبع</th><th>یادداشت</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $c): ?>
        <tr>
          <td class="text-nowrap"><?php echo h($c['delivery_date']); ?></td>
          <td><?php echo h($c['dept']); ?></td>
          <td><?php echo h($c['item_name']); ?></td>
          <td class="text-muted small"><?php echo h($c['category_id'] ?? '—'); ?></td>
          <td class="text-nowrap"><?php echo xnum($c['quantity']); ?></td>
          <td><?php echo h(unit_label($c['unit'])); ?></td>
          <td><?php echo badge($c['source']); ?></td>
          <td class="text-muted small"><?php echo h($c['notes'] ?? '—'); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">رکوردی یافت نشد.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>