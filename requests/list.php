<?php
/**
 * Procurement request list with filters.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'درخواست‌های تدارکات';
$base = BASE_URL;
$role = $user['role'];

$mine = isset($_GET['mine']) && (int)$_GET['mine'] === 1;

/* access control: employees only see their own requests */
$isEmployee = ($role === 'employee');
if ($isEmployee) { $mine = true; }

$where = [];
$params = [];

if ($mine) {
    $where[] = 'r.requested_by = ' . (int)$user['id'];
}
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $where[] = "(r.item_name LIKE '%" . esc($q) . "%' OR r.request_no LIKE '%" . esc($q) . "%')";
}
$st = trim($_GET['status'] ?? '');
if ($st !== '') { $where[] = "r.status = '" . esc($st) . "'"; }
$dp = (int)($_GET['department_id'] ?? 0);
if ($dp > 0) { $where[] = 'r.department_id = ' . $dp; }
$frm = trim($_GET['from_date'] ?? '');
$to  = trim($_GET['to_date'] ?? '');
if ($frm !== '') { $where[] = "r.request_date >= '" . esc($frm) . "'"; }
if ($to  !== '') { $where[] = "r.request_date <= '" . esc($to) . "'"; }

$sql = "SELECT r.*, d.name dept, c.name cat, u.name requester
        FROM procurement_requests r
        JOIN departments d ON d.id = r.department_id
        LEFT JOIN categories c ON c.id = r.category_id
        LEFT JOIN users u ON u.id = r.requested_by";
if (count($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY r.id DESC';

$rows = fetch_all($sql);
$depts = fetch_all('SELECT id, name FROM departments ORDER BY name');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="filter-box rounded-2 p-3 mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small text-muted">جستجو</label>
      <input type="text" name="q" class="form-control form-control-sm" value="<?php echo h($q); ?>" placeholder="شماره درخواست / کالا">
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">وضعیت</label>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">همه</option>
        <?php foreach (_req_statuses() as $key => $label): ?>
        <option value="<?php echo $key; ?>" <?php echo $st === $key ? 'selected' : ''; ?>><?php echo h($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted">اداره</label>
      <select name="department_id" class="form-select form-select-sm" data-autosubmit>
        <option value="0">همه</option>
        <?php foreach ($depts as $d): ?>
        <option value="<?php echo $d['id']; ?>" <?php echo $dp === (int)$d['id'] ? 'selected' : ''; ?>><?php echo h($d['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto"><label class="form-label small text-muted">از تاریخ</label>
      <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo h($frm); ?>"></div>
    <div class="col-auto"><label class="form-label small text-muted">تا تاریخ</label>
      <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo h($to); ?>"></div>
    <div class="col-auto pt-3"><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>فیلتر</button></div>
    <div class="col-auto pt-3"><a class="btn btn-outline-secondary btn-sm" href="<?php echo $base; ?>/requests/list.php<?php echo $mine ? '?mine=1' : ''; ?>">بازنشانی</a></div>
    <div class="col-auto pt-3"><strong class="text-muted small"><?php echo count($rows); ?> رکورد</strong></div>
  </form>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-inboxes me-2"></i>درخواست‌ها</span>
    <?php if ($role === 'employee' || $role === 'procurement_manager' || $role === 'admin'): ?>
    <a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/requests/create.php"><i class="bi bi-plus-square me-1"></i>درخواست جدید</a>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="mainTable">
      <thead>
        <tr><th>شماره درخواست</th><th>تاریخ</th><th>تاریخ مورد نیاز</th><th>اداره</th><th>کالا</th><th>دسته‌بندی</th><th>مقدار</th><th>فوریت</th><th>وضعیت</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $r['id']; ?>"><?php echo h($r['request_no']); ?></a></td>
          <td class="text-muted small text-nowrap"><?php echo h($r['request_date']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($r['needed_date'] ?? '—'); ?></td>
          <td><?php echo h($r['dept']); ?></td>
          <td><?php echo h($r['item_name']); ?></td>
          <td class="text-muted small"><?php echo h($r['cat'] ?? '-'); ?></td>
          <td class="text-nowrap"><?php echo h($r['quantity']); ?> <?php echo h(unit_label($r['unit'])); ?></td>
          <td><?php echo badge($r['urgency']); ?></td>
          <td><?php echo badge($r['status']); ?></td>
          <td><a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/requests/view.php?id=<?php echo $r['id']; ?>">باز کردن</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">درخواستی یافت نشد.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>