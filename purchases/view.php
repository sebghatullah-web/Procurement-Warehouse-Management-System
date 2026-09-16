<?php
/**
 * Purchase detail + workflow actions.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'جزئیات خرید';
$base = BASE_URL;
$role = $user['role'];
$isPro = in_array($role, ['procurement_manager','admin'], true);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_set('danger', 'خرید نامعتبر است.'); redirect_to($base . '/purchases/list.php'); }

$p = fetch_one("SELECT p.*, r.request_no, r.item_name AS req_item, r.department_id,
                       r.quantity AS req_qty, r.unit AS req_unit, r.urgency, r.direct_delivery,
                       d.name AS dept, s.name AS supplier_name
                FROM purchases p
                JOIN procurement_requests r ON r.id=p.request_id
                JOIN departments d ON d.id=r.department_id
                LEFT JOIN suppliers s ON s.id=p.supplier_id
                WHERE p.id=$id");

if (!$p) { flash_set('danger', 'خرید یافت نشد.'); redirect_to($base . '/purchases/list.php'); }

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_quote' && $isPro) {
        $sid   = (int)($_POST['supplier_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $days  = (int)($_POST['delivery_days'] ?? 0);
        $note  = trim($_POST['quote_note'] ?? '');
        if ($sid <= 0 || $price <= 0) { $errors[] = 'تأمین‌کننده و قیمت معتبر الزامی است.'; }
        else {
            $ok = exec_sql("INSERT INTO quotations (request_id, supplier_id, price, delivery_days, notes)
                            VALUES (" . (int)$p['request_id'] . ", $sid, $price, " . ($days ?: 'NULL') . ", '" . esc($note) . "')");
            if ($ok) { flash_set('success', 'قیمت اضافه شد.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
            $errors[] = last_error();
        }
    } elseif ($action === 'submit_committee' && $isPro) {
        $ok = exec_sql("UPDATE purchases SET status='committee_pending', committee_note='" . esc(trim($_POST['committee_note'] ?? '')) . "' WHERE id=$id");
        if ($ok) { flash_set('success', 'برای کمیته ارسال شد.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
        $errors[] = last_error();
    } elseif ($action === 'committee_decision') {
        $member = trim($_POST['member_name'] ?? '');
        $mrole  = trim($_POST['member_role'] ?? 'finance');
        $dec    = trim($_POST['decision'] ?? 'approved');
        $comment= trim($_POST['comment'] ?? '');
        if ($member === '') { $errors[] = 'نام عضو الزامی است.'; }
        else {
            $ok = exec_sql("INSERT INTO committee_approvals (purchase_id, member_name, member_role, decision, comment)
                            VALUES ($id, '" . esc($member) . "', '" . esc($mrole) . "', '" . esc($dec) . "', '" . esc($comment) . "')");
            if ($ok) {
                $newStatus = ($dec === 'approved') ? 'approved' : 'rejected';
                exec_sql("UPDATE purchases SET status='" . esc($newStatus) . "' WHERE id=$id");
                flash_set('success', 'تصمیم ثبت شد: ' . ucfirst($dec) . '.');
                redirect_to($base . '/purchases/view.php?id=' . $id);
            }
            $errors[] = last_error();
        }
    } elseif ($action === 'resubmit' && $isPro) {
        $ok = exec_sql("UPDATE purchases SET status='quotation', committee_note=NULL WHERE id=$id");
        if ($ok) { flash_set('success', 'برای بازبینی دوباره باز شد.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
        $errors[] = last_error();
    } elseif ($action === 'finalize' && $isPro) {
        $quotePath = trim($_POST['quote_path'] ?? '');
        $supId     = (int)($_POST['supplier_id'] ?? 0);
        $unitPrice = (float)($_POST['unit_price'] ?? 0);
        $pDate     = trim($_POST['purchase_date'] ?? today());
        if ($unitPrice <= 0) { $errors[] = 'قیمت واحد باید بزرگتر از صفر باشد.'; }
        else {
            $supSql = ($supId > 0) ? ", supplier_id=$supId" : '';
            $total  = $unitPrice * (float)$p['quantity'];
            $ok = exec_sql("UPDATE purchases SET unit_price=$unitPrice, total_cost=$total,
                            purchase_date='" . esc($pDate) . "', status='ordered'$supSql WHERE id=$id");
            if ($ok) { flash_set('success', 'سفارش نهایی شد @ ' . money0($unitPrice) . ' PKR/واحد.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
            $errors[] = last_error();
        }
    }
}

$quotations = fetch_all("SELECT q.*, s.name AS supplier
                        FROM quotations q
                        JOIN suppliers s ON s.id=q.supplier_id
                        WHERE q.request_id=" . (int)$p['request_id']);

$committee = fetch_all("SELECT * FROM committee_approvals WHERE purchase_id=$id ORDER BY approved_at DESC");
$gate      = fetch_all("SELECT * FROM gate_checklists WHERE purchase_id=$id ORDER BY check_date DESC");
$consumptions = fetch_all("SELECT c.*, d.name AS dept FROM consumptions c
                           JOIN departments d ON d.id=c.department_id
                           WHERE c.request_id=" . (int)$p['request_id'] . " OR c.item_name LIKE '%" . esc($p['req_item']) . "%'
                           ORDER BY c.delivery_date DESC");
$suppliers = fetch_all("SELECT id, name FROM suppliers ORDER BY name");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-cart-check me-2"></i><?php echo h($p['purchase_no']); ?> - <?php echo h($p['req_item']); ?></span>
    <?php echo badge($p['status']); ?>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3"><strong>درخواست</strong><br><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo $p['request_id']; ?>"><?php echo h($p['request_no']); ?></a></div>
      <div class="col-md-3"><strong>اداره</strong><br><?php echo h($p['dept']); ?></div>
      <div class="col-md-2"><strong>تعداد</strong><br><?php echo xnum($p['quantity']); ?> <?php echo h($p['req_unit']); ?></div>
      <div class="col-md-2"><strong>فوریت</strong><br><?php echo badge($p['urgency']); ?></div>
      <div class="col-md-2"><strong>تحویل مستقیم</strong><br><?php echo $p['direct_delivery'] ? 'بله' : 'خیر'; ?></div>
      <?php if ($p['supplier_name']): ?>
      <div class="col-md-3"><strong>تأمین‌کننده</strong><br><?php echo h($p['supplier_name']); ?></div>
      <?php endif; ?>
      <?php if ($p['total_cost']): ?>
      <div class="col-md-3"><strong>مجموع هزینه</strong><br class="text-nowrap"><?php echo money0($p['total_cost']); ?> PKR</div>
      <?php endif; ?>
      <?php if ($p['purchase_date']): ?>
      <div class="col-md-3"><strong>تاریخ خرید</strong><br class="text-nowrap"><?php echo h($p['purchase_date']); ?></div>
      <?php endif; ?>
      <?php if ($p['payment_status']): ?>
      <div class="col-md-3"><strong>پرداخت</strong><br><?php echo badge($p['payment_status']); ?></div>
      <?php endif; ?>
    </div>
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger mt-2 py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>

<?php if ($isPro && in_array($p['status'], ['draft','quotation'], true)): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-tags me-2"></i>افزودن قیمت (برای خرید عادی حداقل 3 قیمت لازم است)</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="add_quote">
      <div class="col-md-4">
        <select name="supplier_id" class="form-select" required>
          <option value="">-- تأمین‌کننده --</option>
          <?php foreach ($suppliers as $s): ?>
          <option value="<?php echo $s['id']; ?>"><?php echo h($s['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><input type="number" name="price" class="form-control" placeholder="قیمت واحد (PKR)" required min="0.01" step="any"></div>
      <div class="col-md-2"><input type="number" name="delivery_days" class="form-control" placeholder="روز" min="0"></div>
      <div class="col-md-3"><input type="text" name="quote_note" class="form-control" placeholder="یادداشت (اختیاری)"></div>
      <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">افزودن</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (count($quotations) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-tags me-2"></i>قیمت‌ها (<?php echo count($quotations); ?>)</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>تأمین‌کننده</th><th>قیمت واحد (PKR)</th><th>تحویل (روز)</th><th>یادداشت‌ها</th></tr></thead>
      <tbody>
      <?php foreach ($quotations as $qt): ?>
        <tr><td><?php echo h($qt['supplier']); ?></td>
            <td class="text-nowrap"><?php echo money0($qt['price']); ?></td>
            <td><?php echo h($qt['delivery_days'] ?? '—'); ?></td>
            <td class="text-muted small"><?php echo h($qt['notes'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($isPro && in_array($p['status'], ['draft','quotation'], true) && count($quotations) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-people me-2"></i>ارسال به کمیته</div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="action" value="submit_committee">
      <div class="row g-2">
        <div class="col-md-8"><input type="text" name="committee_note" class="form-control" placeholder="یادداشت برای کمیته (اختیاری)"></div>
        <div class="col-md-4"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-send-check me-1"></i>ارسال برای تأیید</button></div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($p['status'] === 'committee_pending' && in_array($role, ['committee','procurement_manager','admin'], true)): ?>
<div class="card mb-3 border-primary">
  <div class="card-header bg-primary-subtle text-primary-emphasis"><i class="bi bi-people me-2"></i>تصمیم کمیته</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="committee_decision">
      <div class="col-md-3"><label class="form-label required">نام عضو</label>
        <input type="text" name="member_name" class="form-control" required placeholder="مثلاً فیصل - مالی"></div>
      <div class="col-md-3"><label class="form-label">نقش</label>
        <select name="member_role" class="form-select">
          <option value="finance">مالی</option><option value="management">مدیریت</option>
          <option value="procurement">تدارکات</option>
        </select></div>
      <div class="col-md-2"><label class="form-label required">تصمیم</label>
        <select name="decision" class="form-select">
          <option value="approved">تصویب</option><option value="rejected">رد</option>
        </select></div>
      <div class="col-md-4"><label class="form-label">نظر</label>
        <input type="text" name="comment" class="form-control" placeholder="اختیاری"></div>
      <div class="col-12"><button class="btn btn-success" type="submit"><i class="bi bi-check2 me-1"></i>ثبت تصمیم</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($p['status'] === 'rejected' && $isPro): ?>
<div class="card mb-3">
  <div class="card-body">
    <form method="post"><input type="hidden" name="action" value="resubmit">
      <button class="btn btn-outline-primary" type="submit"><i class="bi bi-arrow-repeat me-1"></i>بازگشایی و بازبینی</button></form>
  </div>
</div>
<?php endif; ?>

<?php if ($p['status'] === 'approved' && $isPro): ?>
<div class="card mb-3 border-success">
  <div class="card-header bg-success-subtle text-success-emphasis"><i class="bi bi-check2-circle me-2"></i>نهایی کردن سفارش</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="finalize">
      <div class="col-md-4">
        <label class="form-label">از قیمت</label>
        <select name="quote_path" class="form-select" id="quoteSelect">
          <option value="">-- انتخاب قیمت --</option>
          <?php foreach ($quotations as $qt): ?>
          <option value="<?php echo $qt['supplier_id']; ?>|<?php echo money0($qt['price']); ?>"><?php echo h($qt['supplier']); ?> @ <?php echo money0($qt['price']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">یا تأمین‌کننده</label>
        <select name="supplier_id" class="form-select" id="supplierSelect">
          <option value="0">-- تأمین‌کننده --</option>
          <?php foreach ($suppliers as $s): ?>
          <option value="<?php echo $s['id']; ?>"><?php echo h($s['name']); ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-2"><label class="form-label">قیمت واحد (PKR)</label>
        <input type="number" name="unit_price" class="form-control" min="0.01" step="any" required></div>
      <div class="col-md-2"><label class="form-label">تاریخ</label>
        <input type="date" name="purchase_date" class="form-control" required value="<?php echo today(); ?>"></div>
      <div class="col-md-1 pt-4"><button class="btn btn-success w-100" type="submit">سفارش</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (count($committee) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-people me-2"></i>تصویب‌های کمیته</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>عضو</th><th>نقش</th><th>تصمیم</th><th>نظر</th></tr></thead>
      <tbody>
      <?php foreach ($committee as $a): ?>
        <tr><td class="text-nowrap small text-muted"><?php echo h($a['approved_at']); ?></td>
            <td><?php echo h($a['member_name']); ?></td>
            <td class="text-muted small"><?php echo h($a['member_role'] ?? '—'); ?></td>
            <td><?php echo $a['decision'] === 'approved' ? '<span class="badge text-bg-success">تصویب شد</span>' : '<span class="badge text-bg-danger">رد شد</span>'; ?></td>
            <td class="text-muted small"><?php echo h($a['comment'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (count($gate) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-shield-check me-2"></i>چک‌لیست رسید گیت</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>دریافت‌کننده</th><th>تعداد دریافت شده</th><th>وضعیت</th><th>یادداشت</th></tr></thead>
      <tbody>
      <?php foreach ($gate as $g): ?>
        <tr><td class="text-nowrap small text-muted"><?php echo h($g['check_date']); ?></td>
            <td>#<?php echo (int)$g['received_by']; ?></td>
            <td><?php echo xnum($g['quantity_received']); ?></td>
            <td><?php echo $g['condition_ok'] ? '<span class="badge text-bg-success">سالم</span>' : '<span class="badge text-bg-danger">آسیب‌دیده / کم</span>'; ?></td>
            <td class="text-muted small"><?php echo h($g['remarks'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (count($consumptions) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-box-arrow-up me-2"></i>تحویل‌ها / مصرف</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>اداره</th><th>کالا</th><th>تعداد</th><th>منبع</th></tr></thead>
      <tbody>
      <?php foreach ($consumptions as $c): ?>
        <tr><td class="text-nowrap"><?php echo h($c['delivery_date']); ?></td>
            <td><?php echo h($c['dept']); ?></td><td><?php echo h($c['item_name']); ?></td>
            <td><?php echo xnum($c['quantity']); ?> <?php echo h(unit_label($c['unit'])); ?></td>
            <td><?php echo badge($c['source']); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<a class="btn btn-outline-secondary" href="javascript:history.back()"><i class="bi bi-arrow-right me-1"></i>بازگشت</a>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>