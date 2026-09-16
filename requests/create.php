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
    $item    = trim($old['item_name'] ?? '');
    $cat_id  = (int)($old['category_id'] ?? 0);
    $qty     = trim($old['quantity'] ?? '');
    $unit    = trim($old['unit'] ?? 'pcs');
    $urgency = ($old['urgency'] ?? 'normal') === 'urgent' ? 'urgent' : 'normal';
    $direct  = isset($old['direct_delivery']) ? 1 : 0;
    $reason  = trim($old['reason'] ?? '');
    $details = trim($old['details'] ?? '');
    $rdate   = trim($old['request_date'] ?? today());
    $ndate   = trim($old['needed_date'] ?? '');

    if (!$dept_id)       { $errors[] = 'لطفاً یک اداره را انتخاب کنید.'; }
    if ($item === '')    { $errors[] = 'نام کالا الزامی است.'; }
    if ($qty === '')     { $errors[] = 'مقدار / تعداد کالا را وارد کنید.'; }
    if ($rdate === '')   { $rdate = today(); }
    if ($ndate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ndate)) { $errors[] = 'تاریخ مورد نیاز نامعتبر است.'; }

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
                 urgency, direct_delivery, reason, details, status, requested_by, request_date, needed_date)
                VALUES ('" . esc($request_no) . "', $dept_id, " . ($cat_id ? $cat_id : 'NULL') . ",
                        '" . esc($item) . "', '" . esc($qty) . "', '" . esc($unit) . "', '$urgency', $direct,
                        '" . esc($reason) . "', $details_sql, 'pending', " . (int)$user['id'] . ", '$rdate', $ndate_sql)";
        if (exec_sql($sql)) {
            flash_set('success', 'درخواست ' . $request_no . ' با موفقیت ثبت شد.');
            redirect_to($base . '/requests/view.php?id=' . inserted_id());
        }
        $errors[] = 'امکان ذخیره وجود ندارد: ' . last_error();
    }
}

$user = current_user();
$depts = fetch_all('SELECT id, name FROM departments ORDER BY name');
$cats  = fetch_all('SELECT id, name FROM categories ORDER BY name');
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
              <label class="form-label required">دیپارتمنت & بخش</label>
              <select name="department_id" class="form-select" required>
                <option value="0">-- انتخاب دیپارتمنت --</option>
                <?php foreach ($depts as $d): ?>
                <option value="<?php echo $d['id']; ?>" <?php echo (int)($old['department_id'] ?? ($user['department_id'] ?? 0)) === (int)$d['id'] ? 'selected' : ''; ?>><?php echo h($d['name']); ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text text-muted small">دیپارتمنت و بخش مورد نیاز خود را آزادانه انتخاب کنید.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">دسته‌بندی</label>
              <select name="category_id" class="form-select">
                <option value="0">-- انتخاب دسته‌بندی --</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo (int)($old['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['name']); ?></option>
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
              <label class="form-label required">مقدار / تعداد</label>
              <input type="text" name="quantity" class="form-control" required
                     value="<?php echo h($old['quantity'] ?? ''); ?>"
                     placeholder="مثلاً ۱۰، ۵ کیلوگرام، ۳ متر...">
              <div class="form-text text-muted small">مقدار را بنویسید؛ واحد از فیلد واحد انتخاب می‌شود.</div>
            </div>
            <div class="col-3">
              <label class="form-label required">واحد</label>
              <select name="unit" class="form-select">
                <?php foreach (['pcs','bag','ton','kg','ream','roll','liter','set','pair','box','coil','meter'] as $u): ?>
                <option value="<?php echo $u; ?>" <?php echo ($old['unit'] ?? 'pcs') === $u ? 'selected' : ''; ?>><?php echo unit_label($u); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">جزئیات وسایل یا مواد مورد ضرورت</label>
              <textarea name="details" rows="3" class="form-control" placeholder="مثلاً کمپیوتر دل: رم ۳۲ گیگابایت، حافظه ۱ ترابایت، کور i9 نسل ۱۰، گرافیک ۸ گیگابایت و..."><?php echo h($old['details'] ?? ''); ?></textarea>
              <div class="form-text text-muted small">مشخصات دقیق وسایل/مواد را اینجا بنویسید تا خرید دقیق‌تر انجام شود.</div>
            </div>

            <div class="col-3">
              <label class="form-label required">تاریخ درخواست</label>
              <input type="date" name="request_date" class="form-control" value="<?php echo h($old['request_date'] ?? today()); ?>">
            </div>
            <div class="col-3">
              <label class="form-label">تاریخ مورد نیاز</label>
              <input type="date" name="needed_date" class="form-control" value="<?php echo h($old['needed_date'] ?? ''); ?>">
              <div class="form-text text-muted small">تاریخی که وسایل یا مواد باید تا آن‌موقع برسد.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">فوریت</label>
              <select name="urgency" class="form-select">
                <option value="normal" <?php echo ($old['urgency'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>عادی (قیمت + کمیته)</option>
                <option value="urgent" <?php echo ($old['urgency'] ?? '') === 'urgent' ? 'selected' : ''; ?>>فوری (تأیید فوری)</option>
              </select>
              <div class="form-text text-muted small">درخواست‌های فوری مراحل قیمت‌گیری و کمیته را رد می‌کنند.</div>
            </div>
            <div class="col-md-6">
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
          <li>درخواست‌های عادی به <strong>3 قیمت از تأمین‌کنندگان</strong> و تأیید کمیته نیاز دارند؛ درخواست‌های فوری مستقیماً به خرید می‌روند.</li>
          <li>محصوالت ورودی در <strong>گیت</strong> بررسی می‌شوند، سپس ذخیره یا مستقیم تحویل داده می‌شوند.</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>