<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'جزئیات قرارداد کرایه';
$base = BASE_URL;
$role = $user['role'];
$id = (int)($_GET['id'] ?? 0);
$can_manage = in_array($role, ['procurement_manager','admin'], true);
$can_handover = in_array($role, ['warehouse_manager','procurement_manager','admin'], true);
$can_pay = in_array($role, ['finance','procurement_manager','admin'], true);

$rental = fetch_one("SELECT rc.*, pr.request_no, pr.id request_id, pr.requested_by,
                            d.name department, u.name creator
                     FROM rental_contracts rc
                     JOIN procurement_requests pr ON pr.id=rc.request_id
                     JOIN departments d ON d.id=pr.department_id
                     LEFT JOIN users u ON u.id=rc.created_by
                     WHERE rc.id=$id");
if (!$rental) {
    flash_set('danger', 'قرارداد کرایه یافت نشد.');
    redirect_to($base . '/rentals/list.php');
}
if (!in_array($role, ['admin','procurement_manager','warehouse_manager','finance','general_manager'], true)
    && (int)$rental['requested_by'] !== (int)$user['id']) {
    flash_set('danger', 'شما اجازه مشاهده این قرارداد را ندارید.');
    redirect_to($base . '/requests/list.php?mine=1');
}
$page_title = 'قرارداد ' . $rental['rental_no'];
$errors = [];
$is_date = function ($value) {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
};

if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'handover' && $can_handover && $rental['status'] === 'scheduled') {
        $date = trim($_POST['handover_date'] ?? today());
        $condition = trim($_POST['handover_condition'] ?? '');
        $meter = trim($_POST['meter_start'] ?? '');
        if (!$is_date($date) || $date < $rental['start_date']) { $errors[] = 'تاریخ تحویل نامعتبر است یا پیش از آغاز قرارداد می‌باشد.'; }
        if ($condition === '') { $errors[] = 'وضعیت وسیله هنگام تحویل را درج کنید.'; }
        if ($meter !== '' && (!is_numeric($meter) || (float)$meter < 0)) { $errors[] = 'عدد آغاز کیلومتر / ساعت کارکرد نامعتبر است.'; }
        if (!count($errors)) {
            $conn->begin_transaction();
            $ok1 = exec_sql("UPDATE rental_contracts SET status='active', handover_date='" . esc($date) . "',
                             handover_condition='" . esc($condition) . "',
                             meter_start=" . ($meter === '' ? 'NULL' : (float)$meter) . "
                             WHERE id=$id AND status='scheduled'") && affected_rows() === 1;
            $ok2 = $ok1 && exec_sql("UPDATE procurement_requests SET status='rental_active'
                                     WHERE id=" . (int)$rental['request_id'] . " AND status='rental_required'")
                                     && affected_rows() === 1;
            if ($ok1 && $ok2) {
                $conn->commit();
                flash_set('success', 'تحویل ثبت شد و کرایه فعال گردید.');
                redirect_to($base . '/rentals/view.php?id=' . $id);
            }
            $conn->rollback();
            $errors[] = $conn->errno ? 'ثبت تحویل ناموفق بود: ' . last_error() : 'وضعیت قرارداد تغییر کرده است؛ صفحه را تازه‌سازی کنید.';
        }
    } elseif ($action === 'return' && $can_handover && $rental['status'] === 'active') {
        $date = trim($_POST['actual_return_date'] ?? today());
        $condition = trim($_POST['return_condition'] ?? '');
        $meter = trim($_POST['meter_end'] ?? '');
        if (!$is_date($date) || $date < ($rental['handover_date'] ?? $rental['start_date'])) { $errors[] = 'تاریخ برگشت نامعتبر است.'; }
        if ($condition === '') { $errors[] = 'وضعیت وسیله هنگام برگشت را درج کنید.'; }
        if ($meter !== '' && (!is_numeric($meter) || (float)$meter < 0
            || ($rental['meter_start'] !== null && (float)$meter < (float)$rental['meter_start']))) {
            $errors[] = 'عدد نهایی کیلومتر / ساعت کارکرد نامعتبر است.';
        }
        if (!count($errors)) {
            $conn->begin_transaction();
            $ok1 = exec_sql("UPDATE rental_contracts SET status='returned', actual_return_date='" . esc($date) . "',
                             return_condition='" . esc($condition) . "',
                             meter_end=" . ($meter === '' ? 'NULL' : (float)$meter) . ",
                             returned_by=" . (int)$user['id'] . " WHERE id=$id AND status='active'") && affected_rows() === 1;
            $ok2 = $ok1 && exec_sql("UPDATE procurement_requests SET status='completed'
                                     WHERE id=" . (int)$rental['request_id'] . " AND status='rental_active'")
                                     && affected_rows() === 1;
            if ($ok1 && $ok2) {
                $conn->commit();
                flash_set('success', 'برگشت وسیله ثبت شد و روند کرایه پایان یافت.');
                redirect_to($base . '/rentals/view.php?id=' . $id);
            }
            $conn->rollback();
            $errors[] = $conn->errno ? 'ثبت برگشت ناموفق بود: ' . last_error() : 'وضعیت قرارداد تغییر کرده است؛ صفحه را تازه‌سازی کنید.';
        }
    } elseif ($action === 'extend' && $can_manage && $rental['status'] === 'active') {
        $new_end = trim($_POST['new_end_date'] ?? '');
        $reason = trim($_POST['extension_reason'] ?? '');
        if (!$is_date($new_end) || $new_end <= $rental['planned_end_date'] || $new_end < today()) {
            $errors[] = 'تاریخ جدید باید پس از تاریخ پایان فعلی و امروز باشد.';
        }
        if (!count($errors)) {
            $conn->begin_transaction();
            $ok1 = exec_sql("INSERT INTO rental_extensions (rental_id, previous_end_date, new_end_date, reason, changed_by)
                             VALUES ($id, '" . esc($rental['planned_end_date']) . "', '" . esc($new_end) . "',
                             " . ($reason !== '' ? "'" . esc($reason) . "'" : 'NULL') . ", " . (int)$user['id'] . ")");
            $ok2 = $ok1 && exec_sql("UPDATE rental_contracts SET planned_end_date='" . esc($new_end) . "'
                                     WHERE id=$id AND status='active'") && affected_rows() === 1;
            if ($ok1 && $ok2) {
                $conn->commit();
                flash_set('success', 'تمدید مدت کرایه ثبت شد.');
                redirect_to($base . '/rentals/view.php?id=' . $id);
            }
            $conn->rollback();
            $errors[] = $conn->errno ? 'ثبت تمدید ناموفق بود: ' . last_error() : 'وضعیت قرارداد تغییر کرده است؛ صفحه را تازه‌سازی کنید.';
        }
    } elseif ($action === 'payment' && $can_pay && in_array($rental['status'], ['scheduled','active'], true)) {
        $date = trim($_POST['payment_date'] ?? today());
        $amount = (float)($_POST['amount'] ?? 0);
        $currency = $_POST['currency'] ?? '';
        $method = $_POST['method'] ?? '';
        $reference = trim($_POST['reference_no'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if (!$is_date($date)) { $errors[] = 'تاریخ پرداخت نامعتبر است.'; }
        if ($amount <= 0) { $errors[] = 'مبلغ پرداخت باید بیشتر از صفر باشد.'; }
        if (!in_array($currency, ['AFN','USD'], true)) { $errors[] = 'واحد پول نامعتبر است.'; }
        if (!in_array($method, ['cash','bank_transfer','cheque','other'], true)) { $errors[] = 'روش پرداخت نامعتبر است.'; }
        if (!count($errors)) {
            $ok = exec_sql("INSERT INTO rental_payments
                            (rental_id, payment_date, amount, currency, method, reference_no, notes, recorded_by)
                            VALUES ($id, '" . esc($date) . "', $amount, '" . esc($currency) . "', '" . esc($method) . "',
                            " . ($reference !== '' ? "'" . esc($reference) . "'" : 'NULL') . ",
                            " . ($notes !== '' ? "'" . esc($notes) . "'" : 'NULL') . ", " . (int)$user['id'] . ")");
            if ($ok) {
                flash_set('success', 'پرداخت کرایه ثبت شد.');
                redirect_to($base . '/rentals/view.php?id=' . $id);
            }
            $errors[] = 'ثبت پرداخت ناموفق بود: ' . last_error();
        }
    } else {
        $errors[] = 'این عملیات برای نقش یا وضعیت فعلی مجاز نیست.';
    }
}

$payments = fetch_all("SELECT rp.*, u.name recorder FROM rental_payments rp
                       LEFT JOIN users u ON u.id=rp.recorded_by
                       WHERE rp.rental_id=$id ORDER BY rp.payment_date DESC, rp.id DESC");
$extensions = fetch_all("SELECT re.*, u.name editor FROM rental_extensions re
                         LEFT JOIN users u ON u.id=re.changed_by
                         WHERE re.rental_id=$id ORDER BY re.created_at DESC");
$paid = ['AFN'=>0.0,'USD'=>0.0];
foreach ($payments as $payment) { $paid[$payment['currency']] += (float)$payment['amount']; }
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center"><span><?php echo h($rental['rental_no']); ?> — <?php echo h($rental['equipment_name']); ?></span><?php echo badge($rental['status']); ?></div>
  <div class="card-body">
    <?php if (count($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
    <div class="row g-3">
      <div class="col-md-3"><div class="text-muted small">درخواست / اداره</div><a href="<?php echo $base; ?>/requests/view.php?id=<?php echo (int)$rental['request_id']; ?>"><?php echo h($rental['request_no']); ?></a><div><?php echo h($rental['department']); ?></div></div>
      <div class="col-md-3"><div class="text-muted small">مالک / تأمین‌کننده</div><strong><?php echo h($rental['supplier_name']); ?></strong><div><?php echo h($rental['supplier_phone'] ?? ''); ?></div></div>
      <div class="col-md-3"><div class="text-muted small">مدت قرارداد</div><?php echo h($rental['start_date']); ?> تا <?php echo h($rental['actual_return_date'] ?: $rental['planned_end_date']); ?><?php if ($rental['status'] === 'active' && $rental['planned_end_date'] < today()): ?><div class="text-danger fw-semibold">از موعد برگشت گذشته است</div><?php endif; ?></div>
      <div class="col-md-3"><div class="text-muted small">کرایه</div><strong><?php echo money_cur($rental['rate_amount'], $rental['currency']); ?></strong> / <?php echo h(['hour'=>'ساعت','day'=>'روز','week'=>'هفته','month'=>'ماه'][$rental['rate_unit']]); ?><div class="small">تضمین: <?php echo money_cur($rental['deposit_amount'], $rental['currency']); ?></div></div>
      <div class="col-md-3"><div class="text-muted small">تعداد / محل</div><?php echo xnum($rental['quantity']); ?> — <?php echo h($rental['rental_location'] ?? '—'); ?></div>
      <div class="col-md-3"><div class="text-muted small">اپراتور</div><?php echo $rental['operator_included'] ? 'شامل قرارداد' . ($rental['operator_name'] ? ': ' . h($rental['operator_name']) : '') : 'شامل نیست'; ?></div>
      <div class="col-md-3"><div class="text-muted small">مسئول سوخت / ترمیم</div><?php echo $rental['fuel_responsibility'] === 'company' ? 'شرکت' : 'مالک'; ?> / <?php echo $rental['maintenance_responsibility'] === 'company' ? 'شرکت' : 'مالک'; ?></div>
      <div class="col-md-3"><div class="text-muted small">کارکرد آغاز / پایان</div><?php echo h($rental['meter_start'] ?? '—'); ?> / <?php echo h($rental['meter_end'] ?? '—'); ?></div>
    </div>
    <?php if ($rental['equipment_details'] || $rental['notes']): ?><hr><div class="row"><div class="col-md-6"><strong>مشخصات وسیله:</strong><div><?php echo nl2br(h($rental['equipment_details'] ?? '—')); ?></div></div><div class="col-md-6"><strong>یادداشت قرارداد:</strong><div><?php echo nl2br(h($rental['notes'] ?? '—')); ?></div></div></div><?php endif; ?>
    <?php if ($rental['handover_date']): ?><hr><div><strong>تحویل:</strong> <?php echo h($rental['handover_date']); ?> — <?php echo nl2br(h($rental['handover_condition'])); ?></div><?php endif; ?>
    <?php if ($rental['actual_return_date']): ?><div class="mt-2"><strong>برگشت:</strong> <?php echo h($rental['actual_return_date']); ?> — <?php echo nl2br(h($rental['return_condition'])); ?></div><?php endif; ?>
  </div>
</div>

<?php if ($rental['status'] === 'scheduled' && $can_handover): ?>
<div class="card mb-3"><div class="card-header">ثبت تحویل ماشین و آغاز کرایه</div><div class="card-body">
  <form method="post" class="row g-2"><input type="hidden" name="action" value="handover">
    <div class="col-md-3"><label class="form-label required">تاریخ تحویل</label><input type="date" name="handover_date" class="form-control" required value="<?php echo today(); ?>"></div>
    <div class="col-md-3"><label class="form-label">کیلومتر / ساعت کارکرد آغاز</label><input type="number" min="0" step="0.01" name="meter_start" class="form-control"></div>
    <div class="col-md-6"><label class="form-label required">وضعیت وسیله هنگام تحویل</label><input name="handover_condition" class="form-control" required></div>
    <div class="col-12"><button class="btn btn-success" type="submit">ثبت تحویل و فعال‌سازی کرایه</button></div>
  </form>
</div></div>
<?php endif; ?>

<?php if ($rental['status'] === 'active' && $can_handover): ?>
<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header">ثبت برگشت ماشین</div><div class="card-body">
    <form method="post" class="row g-2"><input type="hidden" name="action" value="return">
      <div class="col-md-5"><label class="form-label required">تاریخ برگشت</label><input type="date" name="actual_return_date" class="form-control" required value="<?php echo today(); ?>"></div>
      <div class="col-md-7"><label class="form-label">کیلومتر / ساعت کارکرد پایان</label><input type="number" min="0" step="0.01" name="meter_end" class="form-control"></div>
      <div class="col-12"><label class="form-label required">وضعیت وسیله هنگام برگشت</label><textarea name="return_condition" rows="2" class="form-control" required></textarea></div>
      <div class="col-12"><button class="btn btn-success" type="submit">ثبت برگشت و ختم کرایه</button></div>
    </form>
  </div></div></div>
  <?php if ($can_manage): ?><div class="col-lg-6"><div class="card h-100"><div class="card-header">تمدید قرارداد</div><div class="card-body">
    <form method="post" class="row g-2"><input type="hidden" name="action" value="extend">
      <div class="col-md-5"><label class="form-label required">تاریخ پایان جدید</label><input type="date" name="new_end_date" min="<?php echo max(today(), date('Y-m-d', strtotime($rental['planned_end_date'] . ' +1 day'))); ?>" class="form-control" required></div>
      <div class="col-md-7"><label class="form-label">دلیل تمدید</label><input name="extension_reason" class="form-control"></div>
      <div class="col-12"><button class="btn btn-outline-primary" type="submit">ثبت تمدید</button></div>
    </form>
  </div></div></div><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($can_pay && in_array($rental['status'], ['scheduled','active'], true)): ?>
<div class="card mb-3"><div class="card-header">ثبت پرداخت کرایه</div><div class="card-body">
  <form method="post" class="row g-2"><input type="hidden" name="action" value="payment">
    <div class="col-md-2"><label class="form-label required">تاریخ</label><input type="date" name="payment_date" class="form-control" required value="<?php echo today(); ?>"></div>
    <div class="col-md-2"><label class="form-label required">مبلغ</label><input type="number" name="amount" min="0.01" step="0.01" class="form-control" required></div>
    <div class="col-md-2"><label class="form-label">ارز</label><select name="currency" class="form-select"><option value="<?php echo h($rental['currency']); ?>"><?php echo cur_label($rental['currency']); ?></option><option value="<?php echo $rental['currency'] === 'AFN' ? 'USD' : 'AFN'; ?>"><?php echo cur_label($rental['currency'] === 'AFN' ? 'USD' : 'AFN'); ?></option></select></div>
    <div class="col-md-2"><label class="form-label">روش</label><select name="method" class="form-select"><option value="bank_transfer">حواله بانکی</option><option value="cash">نقد</option><option value="cheque">چک</option><option value="other">دیگر</option></select></div>
    <div class="col-md-2"><label class="form-label">شماره سند</label><input name="reference_no" class="form-control"></div>
    <div class="col-md-2"><label class="form-label">یادداشت</label><input name="notes" class="form-control"></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">ثبت پرداخت</button></div>
  </form>
</div></div>
<?php endif; ?>

<div class="card mb-3"><div class="card-header">تاریخچه پرداخت‌ها <span class="small text-muted">— مجموع: <?php echo money_cur($paid['AFN'], 'AFN'); ?> / <?php echo money_cur($paid['USD'], 'USD'); ?></span></div>
  <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>تاریخ</th><th>مبلغ</th><th>روش</th><th>شماره سند</th><th>ثبت‌کننده</th><th>یادداشت</th></tr></thead><tbody>
  <?php foreach ($payments as $p): ?><tr><td><?php echo h($p['payment_date']); ?></td><td><?php echo money_cur($p['amount'], $p['currency']); ?></td><td><?php echo h(['cash'=>'نقد','bank_transfer'=>'حواله بانکی','cheque'=>'چک','other'=>'دیگر'][$p['method']]); ?></td><td><?php echo h($p['reference_no'] ?? '—'); ?></td><td><?php echo h($p['recorder'] ?? '—'); ?></td><td><?php echo h($p['notes'] ?? '—'); ?></td></tr><?php endforeach; ?>
  <?php if (!count($payments)): ?><tr><td colspan="6" class="text-center text-muted py-3">پرداختی ثبت نشده است.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php if (count($extensions)): ?><div class="card mb-3"><div class="card-header">تاریخچه تمدیدها</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>تاریخ ثبت</th><th>پایان قبلی</th><th>پایان جدید</th><th>دلیل</th><th>ثبت‌کننده</th></tr></thead><tbody>
<?php foreach ($extensions as $e): ?><tr><td><?php echo h($e['created_at']); ?></td><td><?php echo h($e['previous_end_date']); ?></td><td><?php echo h($e['new_end_date']); ?></td><td><?php echo h($e['reason'] ?? '—'); ?></td><td><?php echo h($e['editor'] ?? '—'); ?></td></tr><?php endforeach; ?>
</tbody></table></div></div><?php endif; ?>
<a class="btn btn-outline-secondary" href="<?php echo $base; ?>/rentals/list.php">بازگشت به فهرست کرایه‌ها</a>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
