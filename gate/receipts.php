<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('gate_security', 'admin');
$page_title = 'رسیدهای گیت';
$base = BASE_URL;

$pid = (int)($_GET['purchase_id'] ?? 0);
$p  = null;
if ($pid > 0) {
    $p = fetch_one("SELECT p.*, r.request_no, r.item_name, r.department_id, d.name dept
                    FROM purchases p
                    JOIN procurement_requests r ON r.id=p.request_id
                    JOIN departments d ON d.id=r.department_id
                    WHERE p.id=$pid");
}

$errors = [];
if (is_post() && $p) {
    $qty  = (float)($_POST['quantity_received'] ?? 0);
    $cond = isset($_POST['condition_ok']) ? 1 : 0;
    $rem  = trim($_POST['remarks'] ?? '');
    if ($qty <= 0) { $errors[] = 'تعداد دریافت‌شده باید بزرگتر از صفر باشد.'; }
    if (count($errors) === 0) {
        $ok = exec_sql("INSERT INTO gate_checklists
                (purchase_id, received_by, quantity_received, condition_ok, remarks)
                VALUES ($pid, " . (int)$user['id'] . ", $qty, $cond, '" . esc($rem) . "')");
        if ($ok) {
            exec_sql("UPDATE purchases SET status='received' WHERE id=$pid");
            flash_set('success', 'چک‌لیست گیت برای ' . $p['purchase_no'] . ' ذخیره شد.');
            redirect_to($base . '/gate/receipts.php');
        }
        $errors[] = last_error();
    }
}

$checklists = fetch_all("SELECT g.*, p.purchase_no FROM gate_checklists g
                         JOIN purchases p ON p.id=g.purchase_id ORDER BY g.check_date DESC");
$pending = fetch_all("SELECT p.id, p.purchase_no, p.request_id, p.quantity, p.purchase_date, d.name dept, r.request_no
                      FROM purchases p
                      JOIN procurement_requests r ON r.id=p.request_id
                      JOIN departments d ON d.id=r.department_id
                      WHERE p.status='ordered' ORDER BY p.purchase_date ASC");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($p): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-shield-check me-2"></i>چک‌لیست گیت - <?php echo h($p['purchase_no']); ?></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3"><strong>درخواست</strong><br><?php echo h($p['request_no']); ?></div>
      <div class="col-md-3"><strong>کالا</strong><br><?php echo h($p['item_name']); ?></div>
      <div class="col-md-2"><strong>برای اداره</strong><br><?php echo h($p['dept']); ?></div>
      <div class="col-md-2"><strong>تعداد</strong><br><?php echo xnum($p['quantity']); ?></div>
      <div class="col-md-2"><strong>تاریخ سفارش</strong><br class="text-nowrap"><?php echo h($p['purchase_date'] ?? '—'); ?></div>
    </div>
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger mt-2 py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <form method="post" class="row g-2 mt-2">
      <div class="col-md-3"><label class="form-label required">تعداد دریافت‌شده</label>
        <input type="number" name="quantity_received" class="form-control" required min="0.01" step="any"></div>
      <div class="col-md-3"><label class="form-label">وضعیت سالم؟</label>
        <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="condition_ok" checked><label class="form-check-label">بله</label></div></div>
      <div class="col-md-4"><label class="form-label">یادداشت</label>
        <input type="text" name="remarks" class="form-control" placeholder="مثلاً جعبه‌ها پلمب هستند"></div>
      <div class="col-md-2 pt-4"><button class="btn btn-success w-100" type="submit">ذخیره</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-truck me-2"></i>در انتظار در گیت <span class="badge text-bg-warning ms-1"><?php echo count($pending); ?></span></div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>شماره سفارش</th><th>درخواست</th><th>اداره</th><th>تعداد</th><th>تاریخ سفارش</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pending as $p): ?>
        <tr>
          <td><?php echo h($p['purchase_no']); ?></td>
          <td><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $p['request_id']; ?>"><?php echo h($p['request_no']); ?></a></td>
          <td class="text-muted small"><?php echo h($p['dept']); ?></td>
          <td><?php echo xnum($p['quantity']); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? ''); ?></td>
          <td><a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/gate/receipts.php?purchase_id=<?php echo $p['id']; ?>">چک‌لیست</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($pending) === 0): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">هیچ خریدی در انتظار در گیت نیست.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-shield-check me-2"></i>چک‌لیست‌های تکمیل شده</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>شماره سفارش</th><th>تعداد دریافت شد</th><th>وضعیت</th><th>یادداشت</th></tr></thead>
      <tbody>
      <?php foreach ($checklists as $g): ?>
        <tr>
          <td class="text-nowrap small text-muted"><?php echo h($g['check_date']); ?></td>
          <td><a href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $g['purchase_id']; ?>"><?php echo h($g['purchase_no']); ?></a></td>
          <td><?php echo xnum($g['quantity_received']); ?></td>
          <td><?php echo $g['condition_ok'] ? '<span class="badge text-bg-success">سالم</span>' : '<span class="badge text-bg-danger">آسیب‌دیده</span>'; ?></td>
          <td class="text-muted small"><?php echo h($g['remarks'] ?? '—'); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>