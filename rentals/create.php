<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'admin');
$page_title = 'ثبت قرارداد کرایه';
$base = BASE_URL;
$request_id = (int)($_GET['request_id'] ?? 0);
$request = fetch_one("SELECT pr.*, d.name department
                      FROM procurement_requests pr
                      JOIN departments d ON d.id=pr.department_id
                      WHERE pr.id=$request_id");
if (!$request || $request['request_type'] !== 'rental' || $request['status'] !== 'rental_required') {
    flash_set('warning', 'درخواست کرایه تأییدشده برای ثبت قرارداد یافت نشد.');
    redirect_to($base . '/rentals/list.php');
}
$existing = fetch_one("SELECT id FROM rental_contracts WHERE request_id=$request_id");
if ($existing) {
    redirect_to($base . '/rentals/view.php?id=' . (int)$existing['id']);
}

$errors = [];
$old = $_POST;
if (is_post()) {
    $supplier = trim($old['supplier_name'] ?? '');
    $contact = trim($old['supplier_contact'] ?? '');
    $phone = trim($old['supplier_phone'] ?? '');
    $start = trim($old['start_date'] ?? '');
    $end = trim($old['planned_end_date'] ?? '');
    $rate = (float)($old['rate_amount'] ?? 0);
    $rate_unit = $old['rate_unit'] ?? '';
    $currency = $old['currency'] ?? 'AFN';
    $deposit = (float)($old['deposit_amount'] ?? 0);
    $operator = isset($old['operator_included']) ? 1 : 0;
    $operator_name = trim($old['operator_name'] ?? '');
    $fuel = $old['fuel_responsibility'] ?? 'company';
    $maintenance = $old['maintenance_responsibility'] ?? 'supplier';
    $notes = trim($old['notes'] ?? '');
    $request_quantity = qty_num($request['quantity']);
    $quantity = (float)($old['quantity'] ?? $request_quantity);
    $start_obj = DateTime::createFromFormat('!Y-m-d', $start);
    $end_obj = DateTime::createFromFormat('!Y-m-d', $end);
    $valid_date = function ($value, $date) {
        return $date && $date->format('Y-m-d') === $value;
    };

    if ($supplier === '') { $errors[] = 'نام مالک / تأمین‌کننده الزامی است.'; }
    if (!$valid_date($start, $start_obj) || !$valid_date($end, $end_obj)) { $errors[] = 'تاریخ آغاز یا پایان نامعتبر است.'; }
    elseif ($end < $start) { $errors[] = 'تاریخ پایان نمی‌تواند پیش از تاریخ آغاز باشد.'; }
    if ($rate <= 0) { $errors[] = 'مبلغ کرایه باید بیشتر از صفر باشد.'; }
    if ($deposit < 0) { $errors[] = 'مبلغ تضمین نمی‌تواند منفی باشد.'; }
    if ($quantity <= 0) { $errors[] = 'تعداد ماشین / وسیله باید بیشتر از صفر باشد.'; }
    if (!in_array($rate_unit, ['hour','day','week','month'], true)) { $errors[] = 'واحد کرایه نامعتبر است.'; }
    if (!in_array($currency, ['AFN','USD'], true)) { $errors[] = 'واحد پول نامعتبر است.'; }
    if (!in_array($fuel, ['company','supplier'], true) || !in_array($maintenance, ['company','supplier'], true)) {
        $errors[] = 'مسئولیت سوخت یا ترمیم نامعتبر است.';
    }

    if (!count($errors)) {
        $prefix = 'RNT-' . date('Y') . '-';
        $last = fetch_one("SELECT rental_no FROM rental_contracts WHERE rental_no LIKE '$prefix%' ORDER BY rental_no DESC LIMIT 1");
        $number = $last ? (int)substr($last['rental_no'], strlen($prefix)) + 1 : 1;
        $rental_no = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
        $sql = "INSERT INTO rental_contracts
                (rental_no, request_id, supplier_name, supplier_contact, supplier_phone,
                 equipment_name, equipment_details, quantity, rate_amount, rate_unit, currency,
                 deposit_amount, start_date, planned_end_date, rental_location, operator_included,
                 operator_name, fuel_responsibility, maintenance_responsibility, notes, created_by)
                VALUES ('" . esc($rental_no) . "', $request_id, '" . esc($supplier) . "',
                        " . ($contact !== '' ? "'" . esc($contact) . "'" : 'NULL') . ",
                        " . ($phone !== '' ? "'" . esc($phone) . "'" : 'NULL') . ",
                        '" . esc($request['item_name']) . "', " . ($request['details'] ? "'" . esc($request['details']) . "'" : 'NULL') . ",
                        $quantity, $rate, '" . esc($rate_unit) . "', '" . esc($currency) . "', $deposit,
                        '" . esc($start) . "', '" . esc($end) . "',
                        " . ($request['rental_location'] ? "'" . esc($request['rental_location']) . "'" : 'NULL') . ",
                        $operator, " . ($operator_name !== '' ? "'" . esc($operator_name) . "'" : 'NULL') . ",
                        '" . esc($fuel) . "', '" . esc($maintenance) . "',
                        " . ($notes !== '' ? "'" . esc($notes) . "'" : 'NULL') . ", " . (int)$user['id'] . ")";
        if (exec_sql($sql)) {
            flash_set('success', 'قرارداد کرایه ' . $rental_no . ' ثبت شد؛ پس از تحویل، مرحله کرایه را فعال کنید.');
            redirect_to($base . '/rentals/view.php?id=' . inserted_id());
        }
        $errors[] = 'ثبت قرارداد ناموفق بود: ' . last_error();
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-3">
  <div class="card-header">درخواست <?php echo h($request['request_no']); ?> — <?php echo h($request['item_name']); ?></div>
  <div class="card-body small">
    <strong>اداره:</strong> <?php echo h($request['department']); ?> |
    <strong>تعداد درخواستی:</strong> <?php echo h($request['quantity']); ?> |
    <strong>تاریخ آغاز درخواستی:</strong> <?php echo h($request['needed_date'] ?? '—'); ?>
    <?php if ($request['rental_duration']): ?> |
      <strong>مدت پیشنهادی:</strong> <?php echo xnum($request['rental_duration']); ?> <?php echo h(['hour'=>'ساعت','day'=>'روز','week'=>'هفته','month'=>'ماه'][$request['rental_duration_unit']]); ?>
    <?php endif; ?>
    <?php if ($request['operator_required']): ?><div class="mt-1 text-primary">راننده / اپراتور در درخواست لازم دانسته شده است.</div><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>جزئیات قرارداد کرایه</div>
  <div class="card-body">
    <?php if (count($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label required">مالک / تأمین‌کننده</label><input class="form-control" name="supplier_name" required value="<?php echo h($old['supplier_name'] ?? ''); ?>"></div>
      <div class="col-md-4"><label class="form-label">شخص تماس</label><input class="form-control" name="supplier_contact" value="<?php echo h($old['supplier_contact'] ?? ''); ?>"></div>
      <div class="col-md-4"><label class="form-label">شماره تماس</label><input class="form-control" name="supplier_phone" value="<?php echo h($old['supplier_phone'] ?? ''); ?>"></div>
      <div class="col-md-3"><label class="form-label required">تعداد ماشین / وسیله</label><input type="number" name="quantity" min="0.01" step="0.01" required class="form-control" value="<?php echo h($old['quantity'] ?? qty_num($request['quantity'])); ?>"></div>
      <div class="col-md-3"><label class="form-label required">کرایه به ازای هر واحد</label><input type="number" name="rate_amount" min="0.01" step="0.01" required class="form-control" value="<?php echo h($old['rate_amount'] ?? ''); ?>"></div>
      <div class="col-md-3"><label class="form-label required">واحد محاسبه</label><select name="rate_unit" class="form-select" required><?php foreach (['hour'=>'ساعت','day'=>'روز','week'=>'هفته','month'=>'ماه'] as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo ($old['rate_unit'] ?? $request['rental_duration_unit'] ?? 'day') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">ارز</label><select name="currency" class="form-select"><option value="AFN">افغانی</option><option value="USD" <?php echo ($old['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>دالر</option></select></div>
      <div class="col-md-3"><label class="form-label">پیش‌پرداخت / تضمین</label><input type="number" name="deposit_amount" min="0" step="0.01" class="form-control" value="<?php echo h($old['deposit_amount'] ?? '0'); ?>"></div>
      <div class="col-md-3"><label class="form-label required">تاریخ آغاز</label><input type="date" name="start_date" required class="form-control" value="<?php echo h($old['start_date'] ?? $request['needed_date'] ?? today()); ?>"></div>
      <div class="col-md-3"><label class="form-label required">تاریخ پایان پیش‌بینی‌شده</label><input type="date" name="planned_end_date" required class="form-control" value="<?php echo h($old['planned_end_date'] ?? ''); ?>"></div>
      <div class="col-md-3"><label class="form-label">مسئول سوخت</label><select name="fuel_responsibility" class="form-select"><option value="company">شرکت</option><option value="supplier" <?php echo ($old['fuel_responsibility'] ?? '') === 'supplier' ? 'selected' : ''; ?>>مالک / تأمین‌کننده</option></select></div>
      <div class="col-md-3"><label class="form-label">مسئول ترمیم و نگهداری</label><select name="maintenance_responsibility" class="form-select"><option value="supplier">مالک / تأمین‌کننده</option><option value="company" <?php echo ($old['maintenance_responsibility'] ?? '') === 'company' ? 'selected' : ''; ?>>شرکت</option></select></div>
      <div class="col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="operator_included" id="operator" <?php echo isset($old['operator_included']) || $request['operator_required'] ? 'checked' : ''; ?>><label class="form-check-label" for="operator">اپراتور شامل قرارداد است</label></div></div>
      <div class="col-md-3"><label class="form-label">نام اپراتور</label><input class="form-control" name="operator_name" value="<?php echo h($old['operator_name'] ?? ''); ?>"></div>
      <div class="col-12"><label class="form-label">یادداشت و شرایط قرارداد</label><textarea class="form-control" name="notes" rows="3"><?php echo h($old['notes'] ?? ''); ?></textarea></div>
      <div class="col-12"><button class="btn btn-primary" type="submit">ثبت قرارداد (در انتظار تحویل)</button> <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/rentals/list.php">انصراف</a></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
