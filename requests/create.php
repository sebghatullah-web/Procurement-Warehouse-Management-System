<?php
/**
 * New procurement request.
 * Role: employee (own department), procurement_manager / admin (any department).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('employee', 'procurement_manager', 'admin');
$page_title = 'درخواست جدید تدارکات';
$base = BASE_URL;
$role = $user['role'];

$isManager = ($role === 'procurement_manager' || $role === 'admin');

$errors = [];
$old = $_POST;

if (is_post()) {
    $dept_id = (int)($old['department_id'] ?? 0);
    $emp_name = trim($old['employee_name'] ?? '');
    $emp_pos  = trim($old['employee_position'] ?? '');
    $item    = trim($old['item_name'] ?? '');
    $request_type = ($old['request_type'] ?? 'purchase') === 'rental' ? 'rental' : 'purchase';
    $cat_id  = (int)($old['category_id'] ?? 0);
    $qty     = trim($old['quantity'] ?? '');
    $unit    = trim($old['unit'] ?? 'pcs');
    $urgency = ($old['urgency'] ?? 'normal') === 'urgent' ? 'urgent' : 'normal';
    $direct  = isset($old['direct_delivery']) ? 1 : 0;
    $reason  = trim($old['reason'] ?? '');
    $details = trim($old['details'] ?? '');
    $rdate   = trim($old['request_date'] ?? today());
    $ndate   = trim($old['needed_date'] ?? '');
    $rental_duration = (float)($old['rental_duration'] ?? 0);
    $rental_duration_unit = $old['rental_duration_unit'] ?? 'day';
    $rental_location = trim($old['rental_location'] ?? '');
    $operator_required = isset($old['operator_required']) ? 1 : 0;

    if (!$dept_id)       { $errors[] = 'لطفاً یک دیپارتمنت را انتخاب کنید.'; }
    if ($item === '')    { $errors[] = 'نام کالا الزامی است.'; }
    if ($qty === '')     { $errors[] = 'مقدار / تعداد کالا را وارد کنید.'; }
    if ($rdate === '')   { $rdate = today(); }
    if ($ndate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ndate)) { $errors[] = 'تاریخ مورد نیاز نامعتبر است.'; }
    if ($request_type === 'rental') {
        $cat_id = 0;
        $unit = 'pcs';
        $direct = 0;
        if ($ndate === '') { $errors[] = 'تاریخ آغاز کرایه را مشخص کنید.'; }
        if ($rental_duration <= 0) { $errors[] = 'مدت کرایه باید بیشتر از صفر باشد.'; }
        if (!in_array($rental_duration_unit, ['hour','day','week','month'], true)) {
            $errors[] = 'واحد مدت کرایه نامعتبر است.';
        }
    } else {
        $rental_duration = 0;
        $rental_duration_unit = 'day';
        $rental_location = '';
        $operator_required = 0;
    }

    if (count($errors) === 0) {
        $prefix = 'REQ-' . date('Y') . '-';
        $last = fetch_one("SELECT request_no FROM procurement_requests
                           WHERE request_no LIKE '$prefix%' ORDER BY request_no DESC LIMIT 1");
        $n = $last ? (int)substr($last['request_no'], strlen($prefix)) + 1 : 1;
        $request_no = $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);

        $details_sql = $details === '' ? 'NULL' : "'" . esc($details) . "'";
        $ndate_sql   = $ndate   === '' ? 'NULL' : "'" . esc($ndate) . "'";

        $sql = "INSERT INTO procurement_requests
                (request_no, department_id, category_id, item_name, quantity, unit,
                 request_type, rental_duration, rental_duration_unit, rental_location, operator_required,
                 urgency, direct_delivery, reason, details, status, requested_by,
                 employee_name, employee_position, request_date, needed_date)
                VALUES ('" . esc($request_no) . "', $dept_id, " . ($cat_id ? $cat_id : 'NULL') . ",
                        '" . esc($item) . "', '" . esc($qty) . "', '" . esc($unit) . "', '$request_type',
                        " . ($request_type === 'rental' ? $rental_duration : 'NULL') . ",
                        " . ($request_type === 'rental' ? "'" . esc($rental_duration_unit) . "'" : 'NULL') . ",
                        " . ($request_type === 'rental' && $rental_location !== '' ? "'" . esc($rental_location) . "'" : 'NULL') . ",
                        $operator_required, '$urgency', $direct,
                        '" . esc($reason) . "', $details_sql, 'pending', " . (int)$user['id'] . ",
                        '" . esc($emp_name) . "', '" . esc($emp_pos) . "', '$rdate', $ndate_sql)";
        if (exec_sql($sql)) {
            flash_set('success', 'درخواست ' . $request_no . ' با موفقیت ثبت شد.');
            redirect_to($base . '/requests/view.php?id=' . inserted_id());
        }
        $errors[] = 'امکان ذخیره وجود ندارد: ' . last_error();
    }
}

$user = current_user();

/* دو-شاخه‌ی دیپارتمنت‌ها: بخش اصلی (parent) و دیپارتمنت‌های زیر آن (leaf) */
$branches = fetch_all('SELECT id, name FROM departments WHERE parent_id IS NULL ORDER BY name');
$deptLeafs = fetch_all('SELECT id, name, parent_id FROM departments WHERE parent_id IS NOT NULL ORDER BY name');
/* دسته‌بندی دو-سطحی: گروه اصلی و دسته‌های زیر آن */
$groups = fetch_all('SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name');
$catLeafs = fetch_all('SELECT id, name, parent_id FROM categories WHERE parent_id IS NOT NULL ORDER BY name');

/* انتخاب‌های قبلی / پیش‌فرض (کارمند و بخش و دسته‌بندی او) */
$selDept = (int)($old['department_id'] ?? ($user['department_id'] ?? 0));
$selBranch = 0;
foreach ($deptLeafs as $d) { if ((int)$d['id'] === $selDept) { $selBranch = (int)$d['parent_id']; } }
if ($selBranch === 0 && $selDept > 0) { $selBranch = $selDept; } /* خود دیپارتمنت یک بخش اصلی است */
$selCat = (int)($old['category_id'] ?? 0);
$selGroup = 0;
foreach ($catLeafs as $c) { if ((int)$c['id'] === $selCat) { $selGroup = (int)$c['parent_id']; } }
if ($selGroup === 0 && $selCat > 0) { $selGroup = $selCat; }
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-plus-square me-2"></i>جزئیات درخواست</div>
      <div class="card-body">
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-danger py-2">
            <ul class="mb-1"><?php foreach ($errors as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">اسم کارمند (درخواست‌کننده) <span class="text-muted small">— اختیاری</span></label>
              <input type="text" name="employee_name" class="form-control"
                     value="<?php echo h($old['employee_name'] ?? $user['name']); ?>"
                     placeholder="اسم کامل کارمند">
              <div class="form-text text-muted small">به‌جای نام کاربری، اسم واقعی کارمند درج می‌شود (اگر درج نکردید مشکلی نیست).</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">موقعیت وظیفه‌ای <span class="text-muted small">— اختیاری</span></label>
              <input type="text" name="employee_position" class="form-control"
                     value="<?php echo h($old['employee_position'] ?? ''); ?>"
                     placeholder="مثلاً انجینر ساختمانی، حسابدار، کارمند تدارکات...">
              <div class="form-text text-muted small">موقعیت و سمت وظیفه‌ای کارمند را درج کنید (اختیاری).</div>
            </div>
            <div class="col-md-3">
              <label class="form-label required">بخش</label>
              <select name="branch_id" id="deptBranch" class="form-select">
                <option value="0">-- انتخاب بخش --</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b['id']; ?>" <?php echo $selBranch === (int)$b['id'] ? 'selected' : ''; ?>><?php echo h($b['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label required">دیپارتمنت / جزئیات</label>
              <select name="department_id" id="deptSub" class="form-select" required>
                <option value="0">-- انتخاب دیپارتمنت --</option>
                <?php foreach ($deptLeafs as $d): ?>
                <option value="<?php echo $d['id']; ?>" data-parent="<?php echo $d['parent_id']; ?>" <?php echo $selDept === (int)$d['id'] ? 'selected' : ''; ?>><?php echo h($d['name']); ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text text-muted small">اول بخش و بعد دیپارتمنت مورد نیاز خود را انتخاب کنید.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label required">نوع درخواست</label>
              <select name="request_type" id="requestType" class="form-select">
                <option value="purchase" <?php echo ($old['request_type'] ?? 'purchase') === 'purchase' ? 'selected' : ''; ?>>خرید / تأمین</option>
                <option value="rental" <?php echo ($old['request_type'] ?? '') === 'rental' ? 'selected' : ''; ?>>کرایه / اجاره موقت</option>
              </select>
              <div class="form-text">برای وسیله‌ای که پس از استفاده به مالک برگردانده می‌شود، کرایه را انتخاب کنید.</div>
            </div>
            <div class="col-md-6 purchase-field">
              <label class="form-label">گروه اصلی کالا</label>
              <select name="group_id" id="catGroup" class="form-select">
                <option value="0">-- انتخاب گروه --</option>
                <?php foreach ($groups as $g): ?>
                <option value="<?php echo $g['id']; ?>" <?php echo $selGroup === (int)$g['id'] ? 'selected' : ''; ?>><?php echo h($g['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 purchase-field">
              <label class="form-label">دسته‌بندی کالا</label>
              <select name="category_id" id="catSub" class="form-select">
                <option value="0">-- انتخاب دسته‌بندی --</option>
                <?php foreach ($catLeafs as $c): ?>
                <option value="<?php echo $c['id']; ?>" data-parent="<?php echo $c['parent_id']; ?>" <?php echo $selCat === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label required">اسم وسایل یا مواد مورد ضرورت</label>
              <input type="text" name="item_name" class="form-control" required
                     value="<?php echo h($old['item_name'] ?? ''); ?>"
                     placeholder="مثلاً کیسه سیمان، صندلی اداری، کاغذ A4...">
            </div>
            <div class="col-3">
              <label class="form-label required" id="quantityLabel">مقدار / تعداد</label>
              <input type="text" name="quantity" class="form-control" required
                     value="<?php echo h($old['quantity'] ?? ''); ?>"
                     placeholder="مثلاً ۱۰، ۵ کیلوگرام، ۳ متر..." id="requestQuantity">
              <div class="form-text text-muted small" id="quantityHelp">مقدار را بنویسید؛ واحد از فیلد واحد انتخاب می‌شود.</div>
            </div>
            <div class="col-3 purchase-field">
              <label class="form-label required">واحد</label>
              <select name="unit" class="form-select">
                <?php foreach (['pcs','bag','ton','kg','ream','roll','liter','set','pair','box','coil','meter'] as $u): ?>
                <option value="<?php echo $u; ?>" <?php echo ($old['unit'] ?? 'pcs') === $u ? 'selected' : ''; ?>><?php echo unit_label($u); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 rental-field">
              <label class="form-label required">مدت مورد نیاز برای کرایه</label>
              <input type="number" name="rental_duration" min="0.01" step="0.01" class="form-control"
                     value="<?php echo h($old['rental_duration'] ?? ''); ?>">
            </div>
            <div class="col-md-4 rental-field">
              <label class="form-label required">واحد مدت</label>
              <select name="rental_duration_unit" class="form-select">
                <?php foreach (['hour'=>'ساعت','day'=>'روز','week'=>'هفته','month'=>'ماه'] as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php echo ($old['rental_duration_unit'] ?? 'day') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 rental-field">
              <label class="form-label">محل استفاده</label>
              <input type="text" name="rental_location" class="form-control" value="<?php echo h($old['rental_location'] ?? ''); ?>" placeholder="نام پروژه / ساحه">
            </div>
            <div class="col-12 rental-field">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="operator_required" id="operatorRequired" <?php echo isset($old['operator_required']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="operatorRequired">راننده / اپراتور همراه ماشین ضرورت است</label>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">جزئیات وسایل یا مواد مورد ضرورت</label>
              <textarea name="details" rows="3" class="form-control" placeholder="مثلاً کمپیوتر دل: رم ۳۲ گیگابایت، حافظه ۱ ترابایت، کور i9 نسل ۱۰، گرافیک ۸ گیگابایت و..."><?php echo h($old['details'] ?? ''); ?></textarea>
              <div class="form-text text-muted small">برای کرایه، مشخصات ماشین، ظرفیت و تجهیزات لازم را بنویسید.</div>
            </div>

            <div class="col-3">
              <label class="form-label required">تاریخ درخواست</label>
              <input type="date" name="request_date" class="form-control" value="<?php echo h($old['request_date'] ?? today()); ?>">
            </div>
            <div class="col-3">
              <label class="form-label" id="neededDateLabel">تاریخ مورد نیاز</label>
              <input type="date" name="needed_date" class="form-control" value="<?php echo h($old['needed_date'] ?? ''); ?>">
              <div class="form-text text-muted small" id="neededDateHelp">برای کرایه، تاریخ آغاز استفاده را وارد کنید.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">فوریت</label>
              <select name="urgency" class="form-select">
                <option value="normal" id="urgencyNormal" <?php echo ($old['urgency'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>عادی (قیمت + کمیته)</option>
                <option value="urgent" id="urgencyUrgent" <?php echo ($old['urgency'] ?? '') === 'urgent' ? 'selected' : ''; ?>>فوری (تأیید فوری)</option>
              </select>
              <div class="form-text text-muted small" id="urgencyHelp">درخواست‌های خرید فوری مراحل قیمت‌گیری و کمیته را رد می‌کنند.</div>
            </div>
            <div class="col-md-6 purchase-field">
              <div class="form-check form-switch mt-4">
                <input class="form-check-input" type="checkbox" id="direct" name="direct_delivery" <?php echo isset($old['direct_delivery']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="direct">تحویل مستقیم (در انبار نگهداری نشود)</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">دلیل / توضیحات</label>
              <textarea name="reason" rows="3" class="form-control" placeholder="چرا این کالا لازم است؟"><?php echo h($old['reason'] ?? ''); ?></textarea>
            </div>
            
          </div>
          <hr>
          <button class="btn btn-primary px-4" type="submit"><i class="bi bi-send-check me-2"></i>ثبت درخواست</button>
          <a class="btn btn-outline-secondary ms-2" href="<?php echo $base; ?>/index.php">انصراف</a>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">بعد از این چه اتفاقی می‌افتد؟</div>
      <div class="card-body text-muted small">
        <ol class="mb-0">
          <li>تدارکات درخواست شما را بررسی می‌کند.</li>
          <li>در صورت لزوم، انبار موجودی را بررسی می‌کند.</li>
          <li>موجود بود &rarr; وسایل یا مواد تحویل داده می‌شود؛ در غیر این صورت خرید شروع می‌شود.</li>
          <li>در خرید عادی حداقل <strong>3 قیمت از تأمین‌کنندگان</strong> و تأیید کمیته لازم است؛ خرید فوری مستقیماً به سفارش می‌رود.</li>
          <li>کرایه پس از منظوری وارد قرارداد جداگانه می‌شود، تحویل و برگشت آن ثبت می‌گردد و به موجودی انبار افزوده نمی‌شود.</li>
          <li>کالاهای خریداری‌شده در <strong>گیت</strong> بررسی می‌شوند، سپس ذخیره یا مستقیم تحویل داده می‌شوند.</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var type = document.getElementById('requestType');
  function syncRequestType() {
    var rental = type && type.value === 'rental';
    document.querySelectorAll('.rental-field').forEach(function (el) { el.hidden = !rental; });
    document.querySelectorAll('.purchase-field').forEach(function (el) { el.hidden = rental; });
    var duration = document.querySelector('[name="rental_duration"]');
    var date = document.querySelector('[name="needed_date"]');
    if (duration) duration.required = rental;
    if (date) date.required = rental;
    var label = document.getElementById('neededDateLabel');
    if (label) label.textContent = rental ? 'تاریخ آغاز کرایه' : 'تاریخ مورد نیاز';
    var quantity = document.getElementById('requestQuantity');
    var quantityHelp = document.getElementById('quantityHelp');
    var urgencyHelp = document.getElementById('urgencyHelp');
    var urgencyNormal = document.getElementById('urgencyNormal');
    var urgencyUrgent = document.getElementById('urgencyUrgent');
    if (quantity) quantity.placeholder = rental ? 'مثلاً ۲ دستگاه ماشین‌آلات' : 'مثلاً ۱۰، ۵ کیلوگرام، ۳ متر...';
    if (quantityHelp) quantityHelp.textContent = rental ? 'تعداد ماشین‌ها یا وسایل مورد نیاز را وارد کنید.' : 'مقدار را بنویسید؛ واحد از فیلد واحد انتخاب می‌شود.';
    if (urgencyNormal) urgencyNormal.textContent = rental ? 'عادی' : 'عادی (قیمت + کمیته)';
    if (urgencyUrgent) urgencyUrgent.textContent = rental ? 'فوری' : 'فوری (تأیید فوری)';
    if (urgencyHelp) urgencyHelp.textContent = rental
      ? 'فوریت مورد نیاز برای تهیه وسیله کرایه‌ای را انتخاب کنید.'
      : 'درخواست‌های خرید فوری مراحل قیمت‌گیری و کمیته را رد می‌کنند.';
  }
  if (type) {
    type.addEventListener('change', syncRequestType);
    syncRequestType();
  }
  /* منوهای وابسته: بخش ← دیپارتمنت، گروه ← دسته‌بندی */
  function bindSub(branchId, subId, firstIfMissing) {
    var b = document.getElementById(branchId), s = document.getElementById(subId);
    if (!b || !s) return;
    function sync() {
      var p = b.value, shown = 0, hasPick = false;
      for (var i = 0; i < s.options.length; i++) {
        var o = s.options[i];
        if (o.value === '0') continue;
        var show = (p === '0') || (String(o.dataset.parent) === String(p));
        o.hidden = !show;
        if (show) {
          shown++;
          if (o.value === s.value) hasPick = true;
        }
      }
      if (!hasPick && shown > 0 && firstIfMissing) s.value = '0';
    }
    b.addEventListener('change', sync);
    sync();
  }
  bindSub('deptBranch', 'deptSub', true);
  bindSub('catGroup', 'catSub', true);
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>