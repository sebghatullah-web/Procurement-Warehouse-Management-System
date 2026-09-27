<?php
/**
 * Purchase detail + workflow actions.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'جزئیات خرید';
$base = BASE_URL;
$role  = $user['role'];
$isPro  = in_array($role, ['procurement_manager','admin'], true);
$isAuth = in_array($role, ['general_manager','admin'], true);
$isFin  = in_array($role, ['finance','admin'], true);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_set('danger', 'خرید نامعتبر است.'); redirect_to($base . '/purchases/list.php'); }

$p = fetch_one("SELECT p.*, r.request_no, r.item_name AS req_item, r.department_id,
                       r.quantity AS req_qty, r.unit AS req_unit, r.urgency, r.direct_delivery,
                       r.needed_date AS req_needed, r.details AS req_details,
                       r.employee_name AS req_employee, r.employee_position AS req_position,
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
        $cur   = trim($_POST['currency'] ?? 'AFN');
        if ($cur !== 'USD') { $cur = 'AFN'; }
        $days  = (int)($_POST['delivery_days'] ?? 0);
        $note  = trim($_POST['quote_note'] ?? '');
        if ($sid <= 0 || $price <= 0) { $errors[] = 'تأمین‌کننده و قیمت معتبر الزامی است.'; }
        else {
            $ok = exec_sql("INSERT INTO quotations (request_id, supplier_id, price, currency, delivery_days, notes)
                            VALUES (" . (int)$p['request_id'] . ", $sid, $price, '$cur', " . ($days ?: 'NULL') . ", '" . esc($note) . "')");
            if ($ok) { flash_set('success', 'قیمت اضافه شد.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
            $errors[] = last_error();
        }
    } elseif ($action === 'submit_committee' && $isPro) {
        $ok = exec_sql("UPDATE purchases SET status='committee_pending', committee_note='" . esc(trim($_POST['committee_note'] ?? '')) . "' WHERE id=$id");
        if ($ok) { flash_set('success', 'برای کمیته ارسال شد.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
        $errors[] = last_error();
    } elseif ($action === 'committee_decision' && in_array($role, ['committee','procurement_manager','general_manager','admin'], true)) {
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
                exec_sql("UPDATE purchases SET status='" . esc($newStatus) . "',
                          committee_approved=" . ($dec === 'approved' ? 1 : 0) . " WHERE id=$id");
                /* keep the requester's view in sync with the purchase stage */
                exec_sql("UPDATE procurement_requests SET status='"
                          . ($dec === 'approved' ? 'approved' : 'committee_pending') . "'
                          WHERE id=" . (int)$p['request_id']);
                flash_set('success', 'تصمیم ثبت شد: ' . ($dec === 'approved' ? 'تصویب' : 'رد') . '.');
                redirect_to($base . '/purchases/view.php?id=' . $id);
            }
            $errors[] = last_error();
        }
    } elseif ($action === 'select_supplier' && $isPro) {
        /* Step 7 - procurement fixes the winning company + price. */
        if ($p['status'] !== 'approved') {
            $errors[] = 'این خرید در مرحله انتخاب تأمین‌کننده نیست (وضعیت: ' . pur_status_label($p['status']) . ').';
        } else {
            $qid       = (int)($_POST['selected_quotation_id'] ?? 0);
            $supId     = (int)($_POST['supplier_id'] ?? 0);
            $unitPrice = (float)($_POST['unit_price'] ?? 0);
            $cur       = trim($_POST['currency'] ?? 'AFN');
            if ($cur !== 'USD') { $cur = 'AFN'; }
            if ($qid > 0) {
                $q = fetch_one("SELECT * FROM quotations
                                WHERE id=$qid AND request_id=" . (int)$p['request_id']);
                if ($q) {
                    $supId     = (int)$q['supplier_id'];
                    $unitPrice = (float)$q['price'];
                    $cur       = ($q['currency'] === 'USD') ? 'USD' : 'AFN';
                } else {
                    $errors[] = 'قیمت انتخاب‌شده معتبر نیست.';
                }
            }
            if ($supId <= 0)     { $errors[] = 'شرکت / تأمین‌کننده نهایی را انتخاب کنید.'; }
            if ($unitPrice <= 0) { $errors[] = 'قیمت واحد باید بزرگتر از صفر باشد.'; }
            if (count($errors) === 0) {
                $total = $unitPrice * (float)$p['quantity'];
                $qSql  = $qid > 0 ? (string)$qid : 'NULL';
                $ok = exec_sql("UPDATE purchases SET supplier_id=$supId, selected_quotation_id=$qSql,
                                unit_price=$unitPrice, total_cost=$total, currency='$cur',
                                status='supplier_selected' WHERE id=$id");
                if ($ok) {
                    exec_sql("UPDATE procurement_requests SET status='supplier_selected'
                              WHERE id=" . (int)$p['request_id']);
                    flash_set('success', 'شرکت و قیمت نهایی ثبت شد. حالا منتظر تصویب ریاست شرکت باشید.');
                    redirect_to($base . '/purchases/view.php?id=' . $id);
                }
                $errors[] = last_error();
            }
        }
    } elseif ($action === 'authority_decision' && $isAuth) {
        /* Step 8 - company president / executive director approval. */
        if ($p['status'] !== 'supplier_selected') {
            $errors[] = 'این خرید در انتظار تصویب ریاست نیست (وضعیت: ' . pur_status_label($p['status']) . ').';
        } else {
            $aname = trim($_POST['authority_name'] ?? '');
            $apos  = trim($_POST['authority_position'] ?? '');
            $dec   = (($_POST['decision'] ?? 'approved') === 'rejected') ? 'rejected' : 'approved';
            $note  = trim($_POST['authority_note'] ?? '');
            if ($aname === '') { $errors[] = 'نام مقام تصویب‌کننده الزامی است.'; }
            if (count($errors) === 0) {
                $st = ($dec === 'approved') ? 'authority_approved' : 'rejected';
                $ok = exec_sql("UPDATE purchases SET authority_name='" . esc($aname) . "',
                                authority_position='" . esc($apos) . "',
                                authority_approved_by=" . (int)$user['id'] . ",
                                authority_approved_at=NOW(), authority_note='" . esc($note) . "',
                                status='$st' WHERE id=$id");
                if ($ok) {
                    exec_sql("UPDATE procurement_requests SET status='"
                              . ($dec === 'approved' ? 'authority_approved' : 'approved') . "'
                              WHERE id=" . (int)$p['request_id']);
                    if ($dec === 'approved') {
                        flash_set('success', 'تصویب ریاست ثبت شد. حالا منتظر تأمین هزینه توسط بخش مالی باشید.');
                    } else {
                        flash_set('warning', 'ریاست شرکت این خرید را رد کرد.');
                    }
                    redirect_to($base . '/purchases/view.php?id=' . $id);
                }
                $errors[] = last_error();
            }
        }
    } elseif ($action === 'finance_release' && $isFin) {
        /* Step 9 - finance hands the money over and messages purchasing. */
        if ($p['status'] !== 'authority_approved') {
            $errors[] = 'این خرید در مرحله تأمین هزینه (مالی) نیست (وضعیت: ' . pur_status_label($p['status']) . ').';
        } else {
            $method = trim($_POST['finance_method'] ?? '');
            $ref    = trim($_POST['finance_ref_no'] ?? '');
            $amt    = (float)($_POST['finance_amount'] ?? 0);
            $cur    = trim($_POST['finance_currency'] ?? ($p['currency'] ?: 'AFN'));
            if ($cur !== 'USD') { $cur = 'AFN'; }
            $note   = trim($_POST['finance_note'] ?? '');
            if (!array_key_exists($method, _finance_methods())) { $errors[] = 'طریقه تسلیمی هزینه را انتخاب کنید.'; }
            if ($amt <= 0)  { $errors[] = 'مبلغ تسلیمی باید بزرگتر از صفر باشد.'; }
            if ($note === '') { $errors[] = 'پیام/یادداشت مالی برای بخش خریداری الزامی است.'; }
            if (count($errors) === 0) {
                $ok = exec_sql("UPDATE purchases SET finance_method='" . esc($method) . "',
                                finance_ref_no='" . esc($ref) . "',
                                finance_amount=$amt, finance_currency='$cur',
                                finance_note='" . esc($note) . "',
                                finance_released_by=" . (int)$user['id'] . ",
                                finance_released_at=NOW(), payment_status='paid',
                                status='financed' WHERE id=$id");
                if ($ok) {
                    exec_sql("UPDATE procurement_requests SET status='financed'
                              WHERE id=" . (int)$p['request_id']);
                    flash_set('success', 'تسلیمی هزینه (' . finance_method_label($method)
                              . ') به بخش خریداری ثبت شد. حالا ثبت سفارش ممکن است.');
                    redirect_to($base . '/purchases/view.php?id=' . $id);
                }
                $errors[] = last_error();
            }
        }
    } elseif ($action === 'register_order' && $isPro) {
        /* Step 10 - purchasing finally registers the order. */
        if ($p['status'] !== 'financed') {
            $errors[] = 'تا زمانی که بخش مالی هزینه را تسلیم نکند، ثبت سفارش ممکن نیست (وضعیت: '
                      . pur_status_label($p['status']) . ').';
        } else {
            $pdate = trim($_POST['purchase_date'] ?? today());
            if ($pdate === '') { $pdate = today(); }
            $note    = trim($_POST['announcement_note'] ?? '');
            $noteSql = ($note === '') ? 'announcement_note' : "'" . esc($note) . "'";
            $ok = exec_sql("UPDATE purchases SET purchase_date='" . esc($pdate) . "',
                            announcement_note=$noteSql,
                            order_registered_by=" . (int)$user['id'] . ",
                            order_registered_at=NOW(), status='ordered' WHERE id=$id");
            if ($ok) {
                exec_sql("UPDATE procurement_requests SET status='purchased'
                          WHERE id=" . (int)$p['request_id']);
                flash_set('success', 'سفارش ' . $p['purchase_no'] . ' ثبت شد و برای رسید گیت آماده است.');
                redirect_to($base . '/purchases/view.php?id=' . $id);
            }
            $errors[] = last_error();
        }
    } elseif ($action === 'resubmit' && $isPro) {
        $ok = exec_sql("UPDATE purchases SET status='quotation', committee_note=NULL WHERE id=$id");
        if ($ok) { flash_set('success', 'برای بازبینی دوباره باز شد.'); redirect_to($base . '/purchases/view.php?id=' . $id); }
        $errors[] = last_error();
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
      <?php if ($p['req_employee']): ?>
      <div class="col-md-3"><strong>کارمند (درخواست‌کننده)</strong><br><?php echo h($p['req_employee']); ?><?php if ($p['req_position'] && $p['req_position'] !== '—'): ?> <span class="text-muted small">(<?php echo h($p['req_position']); ?>)</span><?php endif; ?></div>
      <?php endif; ?>
      <div class="col-md-2"><strong>تعداد</strong><br><?php echo xnum($p['quantity']); ?> <?php echo h($p['req_unit']); ?></div>
      <div class="col-md-2"><strong>فوریت</strong><br><?php echo badge($p['urgency']); ?></div>
      <div class="col-md-2"><strong>تحویل مستقیم</strong><br><?php echo $p['direct_delivery'] ? 'بله' : 'خیر'; ?></div>
      <?php if ($p['req_needed']): ?>
      <div class="col-md-3"><strong>تاریخ مورد نیاز</strong><br class="text-nowrap"><?php echo h($p['req_needed']); ?></div>
      <?php endif; ?>
      <?php if ($p['supplier_name']): ?>
      <div class="col-md-3"><strong>تأمین‌کننده</strong><br><?php echo h($p['supplier_name']); ?></div>
      <?php endif; ?>
      <?php if ($p['total_cost']): ?>
      <div class="col-md-3"><strong>مجموع هزینه</strong><br class="text-nowrap"><?php echo money_cur($p['total_cost'], $p['currency']); ?></div>
      <?php endif; ?>
      <?php if ($p['purchase_date']): ?>
      <div class="col-md-3"><strong>تاریخ خرید</strong><br class="text-nowrap"><?php echo h($p['purchase_date']); ?></div>
      <?php endif; ?>
      <?php if ($p['payment_status']): ?>
      <div class="col-md-3"><strong>پرداخت</strong><br><?php echo badge($p['payment_status']); ?></div>
      <?php endif; ?>
    </div>
    <?php if ($p['req_details']): ?>
    <div class="mt-2"><strong class="text-muted small">جزئیات کالا</strong><br><?php echo h($p['req_details']); ?></div>
    <?php endif; ?>
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
      <div class="col-md-3"><input type="number" name="price" class="form-control" placeholder="قیمت واحد" required min="0.01" step="any"></div>
      <div class="col-md-2"><label class="form-label">ارز</label>
        <select name="currency" class="form-select form-select-sm">
          <option value="AFN">افغانی (؋)</option>
          <option value="USD">دالر ($)</option>
        </select></div>
      <div class="col-md-1"><label class="form-label">تحویل</label>
        <input type="number" name="delivery_days" class="form-control" placeholder="روز" min="0"></div>
      <div class="col-md-3"><input type="text" name="quote_note" class="form-control" placeholder="یادداشت (اختیاری)"></div>
      <div class="col-md-3"><button class="btn btn-primary w-100" type="submit">افزودن</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (count($quotations) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-tags me-2"></i>قیمت‌ها (<?php echo count($quotations); ?>)</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>تأمین‌کننده</th><th>قیمت واحد</th><th>ارز</th><th>تحویل (روز)</th><th>یادداشت‌ها</th></tr></thead>
      <tbody>
      <?php foreach ($quotations as $qt): ?>
        <tr><td><?php echo h($qt['supplier']); ?></td>
            <td class="text-nowrap"><?php echo money_cur($qt['price'], $qt['currency']); ?></td>
            <td><?php echo cur_label($qt['currency']); ?></td>
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

<?php if ($p['status'] === 'committee_pending' && in_array($role, ['committee','procurement_manager','general_manager','admin'], true)): ?>
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

<?php if (purchase_stage_index($p['status']) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-diagram-3 me-2"></i>مسیر پس از تصویب کمیته تا تحویل کالا</div>
  <div class="card-body">
    <?php
    $stage = purchase_stage_index($p['status']);
    $chain = [
        1 => 'تصویب کمیته',
        2 => 'انتخاب شرکت و قیمت نهایی',
        3 => 'تصویب ریاست شرکت / رئیس اجرائیه',
        4 => 'تسلیمی هزینه از مالی به خریداری',
        5 => 'ثبت سفارش توسط خریداری',
        6 => 'رسید و بازرسی گیت',
        7 => 'ذخیره در انبار / تحویل + فورم تسلیمی',
    ];
    ?>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($chain as $i => $lbl): ?>
      <span class="badge rounded-pill <?php echo $i <= $stage ? 'text-bg-success' : 'text-bg-light text-dark border'; ?>">
        <?php echo $i; ?>. <?php echo h($lbl); ?>
      </span>
      <?php endforeach; ?>
    </div>
    <div class="small text-muted mt-2">مرحله فعلی: <strong><?php echo h(pur_status_label($p['status'])); ?></strong></div>
  </div>
</div>
<?php endif; ?>

<?php if ($p['authority_approved_at'] || $p['finance_released_at']): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-patch-check me-2"></i>تصویب ریاست و تأمین هزینه (مالی)</div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <div class="text-muted small">تصویب ریاست شرکت / رئیس اجرائیه</div>
        <?php if ($p['authority_name']): ?>
          <div class="fw-semibold"><?php echo h($p['authority_name']); ?>
            <?php if ($p['authority_position']): ?><span class="text-muted small">(<?php echo h($p['authority_position']); ?>)</span><?php endif; ?>
          </div>
          <div class="small text-muted"><?php echo h($p['authority_approved_at']); ?></div>
          <?php if ($p['authority_note']): ?><div class="small"><?php echo h($p['authority_note']); ?></div><?php endif; ?>
        <?php else: ?><div class="text-muted">—</div><?php endif; ?>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">تسلیمی هزینه توسط بخش مالی</div>
        <?php if ($p['finance_released_at']): ?>
          <div class="fw-semibold"><?php echo h(finance_method_label($p['finance_method'])); ?><?php if ($p['finance_amount']): ?> - <?php echo money_cur($p['finance_amount'], $p['finance_currency']); ?><?php endif; ?></div>
          <?php if ($p['finance_ref_no']): ?><div class="small">شماره سند / چک: <?php echo h($p['finance_ref_no']); ?></div><?php endif; ?>
          <div class="small text-muted"><?php echo h($p['finance_released_at']); ?></div>
        <?php else: ?><div class="text-muted">—</div><?php endif; ?>
      </div>
    </div>
    <?php if ($p['finance_note']): ?>
    <div class="alert alert-info mt-2 mb-0 py-2">
      <i class="bi bi-envelope-paper me-1"></i><strong>پیام مالی به بخش خریداری:</strong> <?php echo h($p['finance_note']); ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php if ($p['status'] === 'approved' && $isPro): ?>
<div class="card mb-3 border-success">
  <div class="card-header bg-success-subtle text-success-emphasis"><i class="bi bi-building-check me-2"></i>انتخاب شرکت و قیمت نهایی (پس از تصویب کمیته)</div>
  <div class="card-body">
    <p class="small text-muted mb-2">کمیته این خرید را تصویب کرده است. حالا مشخص کنید کالا از <strong>کدام شرکت</strong> و با <strong>چه قیمتی</strong> خریداری می‌شود؛ سپس برای تصویب به ریاست شرکت / رئیس اجرائیه ارسال می‌گردد.</p>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="select_supplier">
      <div class="col-md-4"><label class="form-label">انتخاب از قیمت‌های ثبت‌شده</label>
        <select name="selected_quotation_id" id="winQuote" class="form-select">
          <option value="0">-- انتخاب دستی --</option>
          <?php foreach ($quotations as $qt): ?>
          <option value="<?php echo $qt['id']; ?>" data-sup="<?php echo (int)$qt['supplier_id']; ?>"
                  data-price="<?php echo (float)$qt['price']; ?>" data-cur="<?php echo h($qt['currency'] ?? 'AFN'); ?>">
            <?php echo h($qt['supplier']); ?> @ <?php echo money_cur($qt['price'], $qt['currency']); ?>
          </option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label required">شرکت / تأمین‌کننده</label>
        <select name="supplier_id" id="winSupplier" class="form-select">
          <option value="0">-- انتخاب --</option>
          <?php foreach ($suppliers as $s): ?>
          <option value="<?php echo $s['id']; ?>"><?php echo h($s['name']); ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-2"><label class="form-label required">قیمت واحد</label>
        <input type="number" name="unit_price" id="winPrice" class="form-control" min="0.01" step="any" required></div>
      <div class="col-md-2"><label class="form-label">ارز</label>
        <select name="currency" id="winCurrency" class="form-select">
          <option value="AFN">افغانی (؋)</option>
          <option value="USD">دالر ($)</option>
        </select></div>
      <div class="col-md-1 pt-4"><button class="btn btn-success w-100" type="submit">ثبت</button></div>
    </form>
    <div class="small text-muted mt-2">پس از ثبت، وضعیت خرید «در انتظار تصویب ریاست شرکت» می‌شود.</div>
    <script>
    (function () {
      var q = document.getElementById('winQuote');
      if (!q) { return; }
      q.addEventListener('change', function () {
        var o = this.options[this.selectedIndex];
        if (!o || !o.getAttribute('data-sup')) { return; }
        document.getElementById('winSupplier').value = o.getAttribute('data-sup');
        document.getElementById('winPrice').value    = o.getAttribute('data-price');
        document.getElementById('winCurrency').value = o.getAttribute('data-cur');
      });
    })();
    </script>
  </div>
</div>
<?php endif; ?>

<?php if ($p['status'] === 'supplier_selected' && $isAuth): ?>
<div class="card mb-3 border-primary">
  <div class="card-header bg-primary-subtle text-primary-emphasis"><i class="bi bi-person-badge me-2"></i>تصویب ریاست شرکت / رئیس اجرائیه</div>
  <div class="card-body">
    <p class="small mb-2">کالا: <strong><?php echo h($p['req_item']); ?></strong> &middot;
      شرکت: <strong><?php echo h($p['supplier_name'] ?? '—'); ?></strong> &middot;
      قیمت واحد: <strong><?php echo money_cur($p['unit_price'], $p['currency']); ?></strong> &middot;
      مجموع: <strong><?php echo money_cur($p['total_cost'], $p['currency']); ?></strong></p>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="authority_decision">
      <div class="col-md-3"><label class="form-label required">نام مقام تصویب‌کننده</label>
        <input type="text" name="authority_name" class="form-control" required placeholder="مثلاً رئیس اجرائیه"></div>
      <div class="col-md-3"><label class="form-label">موقعیت وظیفه‌ای</label>
        <input type="text" name="authority_position" class="form-control" placeholder="مثلاً رئیس اجرائیه شرکت"></div>
      <div class="col-md-2"><label class="form-label required">تصمیم</label>
        <select name="decision" class="form-select">
          <option value="approved">تصویب</option><option value="rejected">رد</option>
        </select></div>
      <div class="col-md-2"><label class="form-label">نظر</label>
        <input type="text" name="authority_note" class="form-control" placeholder="اختیاری"></div>
      <div class="col-md-2 pt-4"><button class="btn btn-primary w-100" type="submit">ثبت تصویب</button></div>
    </form>
    <div class="small text-muted mt-2">پس از تصویب، خرید به بخش <strong>مالی</strong> می‌رود تا هزینه را تسلیم کند.</div>
  </div>
</div>
<?php elseif ($p['status'] === 'supplier_selected'): ?>
<div class="alert alert-warning"><i class="bi bi-hourglass-split me-1"></i>این خرید در <strong>انتظار تصویب ریاست شرکت / رئیس اجرائیه</strong> است.</div>
<?php endif; ?>

<?php if ($p['status'] === 'authority_approved' && $isFin): ?>
<div class="card mb-3 border-info">
  <div class="card-header bg-info-subtle text-info-emphasis"><i class="bi bi-cash-coin me-2"></i>تسلیمی هزینه از مالی به بخش خریداری</div>
  <div class="card-body">
    <p class="small mb-2">ریاست شرکت این خرید را تصویب کرده است. طریقه تسلیمی هزینه را مشخص کنید و پیام خود را برای بخش خریداری ثبت نمایید.</p>
    <div class="alert alert-light border small mb-2">
      کالا: <strong><?php echo h($p['req_item']); ?></strong> &middot;
      شرکت: <strong><?php echo h($p['supplier_name'] ?? '—'); ?></strong> &middot;
      مبلغ مورد نیاز: <strong><?php echo money_cur($p['total_cost'], $p['currency']); ?></strong>
    </div>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="finance_release">
      <div class="col-md-3"><label class="form-label required">طریقه تسلیمی</label>
        <select name="finance_method" class="form-select" required>
          <option value="">-- انتخاب --</option>
          <?php foreach (_finance_methods() as $k => $v): ?>
          <option value="<?php echo $k; ?>"><?php echo h($v); ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label">شماره چک / سند</label>
        <input type="text" name="finance_ref_no" class="form-control" placeholder="مثلاً CHQ-45120"></div>
      <div class="col-md-2"><label class="form-label required">مبلغ تسلیمی</label>
        <input type="number" name="finance_amount" class="form-control" min="0.01" step="any" required
               value="<?php echo (float)$p['total_cost']; ?>"></div>
      <div class="col-md-2"><label class="form-label">ارز</label>
        <select name="finance_currency" class="form-select">
          <option value="AFN" <?php echo ($p['currency'] ?? 'AFN') === 'AFN' ? 'selected' : ''; ?>>افغانی (؋)</option>
          <option value="USD" <?php echo ($p['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>دالر ($)</option>
        </select></div>
      <div class="col-md-2 pt-4"><button class="btn btn-info w-100" type="submit">تسلیمی</button></div>
      <div class="col-12"><label class="form-label required">پیام مالی برای بخش خریداری</label>
        <textarea name="finance_note" rows="2" class="form-control" required
                  placeholder="مثلاً هزینه این درخواست به شکل چک بانکی تحویل خریداری گردید"></textarea></div>
    </form>
    <div class="small text-muted mt-2">تا زمانی که این مرحله ثبت نشود، بخش خریداری نمی‌تواند سفارش را ثبت کند.</div>
  </div>
</div>
<?php elseif ($p['status'] === 'authority_approved'): ?>
<div class="alert alert-warning"><i class="bi bi-hourglass-split me-1"></i>این خرید در <strong>انتظار تسلیمی هزینه از بخش مالی</strong> است.</div>
<?php endif; ?>

<?php if ($p['status'] === 'financed' && $isPro): ?>
<div class="card mb-3 border-info">
  <div class="card-header bg-info-subtle text-info-emphasis"><i class="bi bi-journal-check me-2"></i>ثبت سفارش نهایی</div>
  <div class="card-body">
    <?php if ($p['finance_note']): ?>
    <div class="alert alert-info py-2 small"><i class="bi bi-envelope-paper me-1"></i><strong>پیام مالی:</strong> <?php echo h($p['finance_note']); ?></div>
    <?php endif; ?>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="register_order">
      <div class="col-md-3"><label class="form-label required">تاریخ سفارش</label>
        <input type="date" name="purchase_date" class="form-control" required value="<?php echo today(); ?>"></div>
      <div class="col-md-6"><label class="form-label">یادداشت تحویل / اعلامیه</label>
        <input type="text" name="announcement_note" class="form-control" placeholder="اختیاری"></div>
      <div class="col-md-3 pt-4"><button class="btn btn-success w-100" type="submit"><i class="bi bi-cart-check me-1"></i>ثبت سفارش</button></div>
    </form>
  </div>
</div>
<?php elseif ($p['status'] === 'financed'): ?>
<div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i>هزینه توسط مالی تسلیم شده است؛ در انتظار <strong>ثبت سفارش</strong> توسط بخش خریداری.</div>
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

<?php if ($p['status'] === 'received' && in_array($role, ['warehouse_manager','admin'], true)): ?>
<div class="card mb-3 border-success">
  <div class="card-header bg-success-subtle text-success-emphasis"><i class="bi bi-boxes me-2"></i>ثبت گدام / تعیین تکلیف</div>
  <div class="card-body">
    <p class="small text-muted mb-2">کالاها در گیت دریافت شده‌اند. حالا مشخص کنید که چطور تسلیم می‌شوند: <strong>ذخیره در انبار</strong> یا <strong>تحویل مستقیم به درخواست‌کننده</strong> — و فورم تسلیمی (فورم تحویل) را ثبت نمایید.</p>
    <a class="btn btn-success" href="<?php echo $base; ?>/warehouse/disposition.php?purchase_id=<?php echo $id; ?>"><i class="bi bi-boxes me-1"></i>ثبت گدام / تحویل</a>
  </div>
</div>
<?php elseif ($p['status'] === 'received'): ?>
<div class="alert alert-success"><i class="bi bi-boxes me-1"></i>کالا در گیت دریافت شده است؛ در انتظار <strong>ثبت گدام / تحویل</strong> توسط مدیر انبار.</div>
<?php endif; ?>

<?php if (count($consumptions) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-box-arrow-up me-2"></i>تحویل‌ها / فورم تسلیمی</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>فورم تسلیمی</th><th>تاریخ</th><th>کالا</th><th>تعداد</th><th>نوع تحویل</th><th>تأیید دریافت</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($consumptions as $c): ?>
        <tr>
          <td class="text-nowrap fw-semibold"><?php echo h($c['handover_no'] ?? 'HND-…'); ?></td>
          <td class="text-nowrap"><?php echo h($c['delivery_date']); ?></td>
          <td><?php echo h($c['item_name']); ?><?php if ($c['dept']): ?> <span class="text-muted small">(<?php echo h($c['dept']); ?>)</span><?php endif; ?></td>
          <td><?php echo xnum($c['quantity']); ?> <?php echo h(unit_label($c['unit'])); ?></td>
          <td><?php echo badge($c['source']); ?></td>
          <td><?php echo $c['receiver_confirmed'] ? '<span class="badge text-bg-success">تأیید شده</span>' : '<span class="badge text-bg-warning">در انتظار تأیید</span>'; ?></td>
          <td><a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/warehouse/handover.php?id=<?php echo $c['id']; ?>">فورم / تأیید</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<a class="btn btn-outline-secondary" href="javascript:history.back()"><i class="bi bi-arrow-right me-1"></i>بازگشت</a>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>