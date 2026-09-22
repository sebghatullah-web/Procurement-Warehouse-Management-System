<?php
/**
 * Start the purchase process for a request marked "purchase_required".
 *  - Urgent : immediate supplier + price (skip quotations/committee).
 *  - Normal : open a draft purchase and collect >= 3 quotations.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'admin');
$page_title = 'شروع خرید';
$base = BASE_URL;

$rid = (int)($_GET['request_id'] ?? 0);
$r = fetch_one("SELECT r.*, d.name dept FROM procurement_requests r
                JOIN departments d ON d.id=r.department_id WHERE r.id=$rid");
if (!$r) {
    flash_set('danger', 'درخواست یافت نشد.');
    redirect_to($base . '/requests/list.php');
}
if ($r['status'] !== 'purchase_required') {
    flash_set('warning', 'این درخواست در انتظار خرید نیست (وضعیت: ' . req_status_label($r['status']) . ').');
    redirect_to($base . '/requests/view.php?id=' . $rid);
}

$errors = [];
$suppliers = fetch_all('SELECT id, name FROM suppliers ORDER BY name');

if (is_post()) {
    $mode = $_POST['mode'] ?? '';
    $qty  = qty_num($r['quantity']);
    $prefix = 'PUR-' . date('Y') . '-';
    $last = fetch_one("SELECT purchase_no FROM purchases WHERE purchase_no LIKE '$prefix%' ORDER BY purchase_no DESC LIMIT 1");
    $n = $last ? (int)substr($last['purchase_no'], strlen($prefix)) + 1 : 1;
    $purchase_no = $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);

    if ($mode === 'urgent') {
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        $price  = (float)($_POST['unit_price'] ?? 0);
        $cur    = trim($_POST['currency'] ?? 'AFN');
        if ($cur !== 'USD') { $cur = 'AFN'; }
        $pdate  = trim($_POST['purchase_date'] ?? today());
        $pay    = ($_POST['payment_status'] ?? 'pending') === 'paid' ? 'paid' : 'pending';
        $note   = trim($_POST['announcement_note'] ?? '');
        $total  = $qty * $price;
        if (!$supplier_id)       { $errors[] = 'یک تأمین‌کننده انتخاب کنید.'; }
        if ($price <= 0)         { $errors[] = 'قیمت واحد باید مثبت باشد.'; }
        if ($pdate === '')       { $pdate = today(); }
        if (count($errors) === 0) {
            $ok = exec_sql("INSERT INTO purchases
                    (purchase_no, request_id, supplier_id, quantity, unit_price, total_cost, currency, purchase_date,
                     urgency, payment_status, status, approved_by, announcement_note)
                    VALUES ('" . esc($purchase_no) . "', $rid, $supplier_id, $qty, $price, $total, '$cur', '$pdate',
                            'urgent', '$pay', 'ordered', " . (int)$user['id'] . ", '" . esc($note) . "')");
            if ($ok) {
                exec_sql("UPDATE procurement_requests SET status='purchased' WHERE id=$rid");
                flash_set('success', 'خرید فوری ' . $purchase_no . ' تأیید و سفارش داده شد.');
                redirect_to($base . '/purchases/view.php?id=' . inserted_id());
            }
            $errors[] = last_error();
        }
    } elseif ($mode === 'normal') {
        $note = trim($_POST['announcement_note'] ?? '');
        $ok = exec_sql("INSERT INTO purchases
                (purchase_no, request_id, quantity, purchase_date, urgency, status, announcement_note)
                VALUES ('" . esc($purchase_no) . "', $rid, $qty, '" . today() . "', 'normal', 'quotation', '" . esc($note) . "')");
        if ($ok) {
            exec_sql("UPDATE procurement_requests SET status='quotation_pending' WHERE id=$rid");
            flash_set('success', 'خرید ' . $purchase_no . ' باز شد. حداقل 3 قیمت اضافه کنید، سپس به کمیته ارسال کنید.');
            redirect_to($base . '/purchases/view.php?id=' . inserted_id());
        }
        $errors[] = last_error();
    } else {
        $errors[] = 'نحوه ادامه (فوری یا عادی) را انتخاب کنید.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-cart-plus me-2"></i>خرید برای <?php echo h($r['request_no']); ?></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3"><strong>اداره</strong><br><?php echo h($r['dept']); ?></div>
      <div class="col-md-3"><strong>کالا</strong><br><?php echo h($r['item_name']); ?></div>
      <div class="col-md-2"><strong>تعداد</strong><br><?php echo h($r['quantity']); ?> <?php echo h(unit_label($r['unit'])); ?></div>
      <div class="col-md-2"><strong>فوریت درخواست</strong><br><?php echo badge($r['urgency']); ?></div>
      <div class="col-md-2"><strong>تحویل مستقیم</strong><br><?php echo $r['direct_delivery'] ? 'بله' : 'خیر'; ?></div>
    </div>
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger mt-2 py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card border-danger-subtle h-100">
      <div class="card-header bg-danger-subtle text-danger-emphasis">
        <i class="bi bi-lightning-charge me-1"></i>فوری - تأیید فوری و خرید
        <?php if ($r['urgency'] === 'urgent'): ?><span class="badge text-bg-danger ms-1">توصیه شده برای این درخواست</span><?php endif; ?>
      </div>
      <div class="card-body small">
        برای نیازهای فوری: همین حالا تأمین‌کننده را انتخاب کنید، قیمت را تعیین کنید و خرید
        <strong>فوراً تأیید می‌شود</strong> (شامل تأیید بودجه). بدون قیمت‌گیری یا مرحله کمیته.
        <form method="post">
          <input type="hidden" name="mode" value="urgent">
          <div class="row g-2">
            <div class="col-12"><label class="form-label required">تأمین‌کننده</label>
              <select name="supplier_id" class="form-select">
                <option value="0">-- انتخاب تأمین‌کننده --</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo h($s['name']); ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-md-4"><label class="form-label required">قیمت واحد</label>
              <input type="number" name="unit_price" min="0.01" step="any" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label required">ارز</label>
              <select name="currency" class="form-select">
                <option value="AFN">افغانی (؋)</option>
                <option value="USD">دالر ($)</option>
              </select></div>
            <div class="col-md-6"><label class="form-label required">تاریخ خرید</label>
              <input type="date" name="purchase_date" class="form-control" required value="<?php echo today(); ?>"></div>
            <div class="col-md-6"><label class="form-label">پرداخت</label>
              <select name="payment_status" class="form-select">
                <option value="pending">در انتظار</option><option value="paid">پرداخت شده</option>
              </select></div>
            <div class="col-md-6"><label class="form-label">یادداشت تحویل</label>
              <input type="text" name="announcement_note" class="form-control" placeholder="اختیاری"></div>
          </div>
          <hr>
          <button class="btn btn-danger w-100" type="submit"><i class="bi bi-lightning-charge me-1"></i>تأیید و ثبت سفارش</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-primary-subtle h-100">
      <div class="card-header bg-primary-subtle text-primary-emphasis">
        <i class="bi bi-tags me-1"></i>عادی - قیمت‌گیری و تأیید کمیته
      </div>
      <div class="card-body small">
        <ul class="mb-2">
          <li>از <strong>حداقل 3 تأمین‌کننده</strong> قیمت بگیرید</li>
          <li>برای کمیته ارسال کنید (مالی + مدیریت + تدارکات)</li>
          <li>پس از تأیید، تأمین‌کننده نهایی را انتخاب و سفارش ثبت کنید</li>
        </ul>
        <form method="post">
          <input type="hidden" name="mode" value="normal">
          <div class="mb-2"><label class="form-label">اعلامیه عمومی (برای خریدهای عمده، اختیاری)</label>
            <input type="text" name="announcement_note" class="form-control" placeholder="مثلاً مناقصه عمومی اعلام شد"></div>
          <hr>
          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-tags me-1"></i>باز کردن خرید و جمع‌آوری قیمت‌ها</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>