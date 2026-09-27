<?php
/**
 * Warehouse disposition / registration after gate receipt.
 *
 * ONE single form with an explicit choice of what happens to the goods:
 *   A) ذخیره در انبار               (mode = warehouse) -> stock is increased
 *   B) تحویل مستقیم به درخواست‌کننده (mode = direct)    -> no stock entry
 *
 * In BOTH cases a handover slip (فورم تسلیمی) is created with its own number
 * and the receiving employee, and is confirmed/print-able at
 * warehouse/handover.php?id=<consumption id>.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('warehouse_manager', 'admin');
$page_title = 'ثبت گدام / تعیین تکلیف';
$base = BASE_URL;

$pid = (int)($_GET['purchase_id'] ?? 0);
$p = fetch_one("SELECT p.*, s.name supplier, r.request_no, r.item_name, r.department_id, r.unit,
                       r.quantity AS req_qty, r.employee_name, r.employee_position,
                       r.category_id AS req_category_id,
                       d.name dept, r.direct_delivery, r.requested_by
                FROM purchases p
                LEFT JOIN suppliers s ON s.id=p.supplier_id
                JOIN procurement_requests r ON r.id=p.request_id
                JOIN departments d ON d.id=r.department_id
                WHERE p.id=$pid");
if (!$p) {
    flash_set('danger', 'خرید یافت نشد.');
    redirect_to($base . '/purchases/list.php');
}
if ($p['status'] !== 'received') {
    if ($p['status'] === 'completed') {
        flash_set('info', 'این خرید قبلاً تکمیل شده است (ذخیره یا تحویل داده شده است).');
    } else {
        flash_set('warning', 'اقلام باید قبل از ثبت گدام در گیت دریافت شوند (وضعیت: ' . pur_status_label($p['status']) . ').');
    }
    redirect_to($base . '/purchases/view.php?id=' . $pid);
}

$cats = fetch_all("SELECT c.id, c.name, g.name grp FROM categories c
                   JOIN categories g ON g.id = c.parent_id
                   WHERE c.parent_id IS NOT NULL ORDER BY g.name, c.name");
$gate = fetch_one("SELECT * FROM gate_checklists WHERE purchase_id=$pid ORDER BY id DESC LIMIT 1");
$receivedQty = $gate ? (float)$gate['quantity_received'] : (float)$p['quantity'];
$already = fetch_all("SELECT h.* FROM consumptions h WHERE h.purchase_id=$pid ORDER BY h.id DESC");

$errors = [];
if (is_post()) {
    $mode    = ((trim($_POST['mode'] ?? 'warehouse')) === 'direct') ? 'direct' : 'warehouse';
    $qty     = (float)($_POST['quantity'] ?? 0);
    $unit    = trim($_POST['unit'] ?? ($p['unit'] ?: 'pcs'));
    $date    = trim($_POST['delivery_date'] ?? '');
    if ($date === '') { $date = today(); }
    $rcvName = trim($_POST['receiver_name'] ?? '');
    $rcvPos  = trim($_POST['receiver_position'] ?? '');
    $note    = trim($_POST['handover_note'] ?? '');
    $name    = trim($_POST['name'] ?? $p['item_name']);
    $catId   = (int)($_POST['category_id'] ?? 0);
    $loc     = trim($_POST['location'] ?? '');
    $min     = (float)($_POST['min_stock'] ?? 0);

    if ($qty <= 0)       { $errors[] = 'تعداد باید بزرگتر از صفر باشد.'; }
    if ($rcvName === '') { $errors[] = 'نام تحویل‌گیرنده (کارمند درخواست‌کننده) الزامی است.'; }
    if ($mode === 'warehouse' && $name === '') { $errors[] = 'نام کالا برای ثبت در انبار الزامی است.'; }

    if (count($errors) === 0) {
        $itemId = 0;
        if ($mode === 'warehouse') {
            /* A) store in warehouse - add / top-up the stock item */
            $catSql  = $catId ? (string)$catId : 'NULL';
            $locSql  = ($loc === '') ? 'NULL' : "'" . esc($loc) . "'";
            $existing = fetch_one("SELECT id FROM warehouse_items
                                   WHERE LOWER(name) = '" . esc(strtolower($name)) . "'"
                                   . ($catId ? " AND category_id = $catId" : ''));
            $ok = $existing
                ? exec_sql("UPDATE warehouse_items SET quantity = quantity + $qty, unit='" . esc($unit) . "',
                            location=$locSql, updated_at=NOW() WHERE id=" . (int)$existing['id'])
                : exec_sql("INSERT INTO warehouse_items (name, category_id, quantity, unit, location, min_stock, notes)
                            VALUES ('" . esc($name) . "', $catSql, $qty, '" . esc($unit) . "',
                                    $locSql, $min, 'خریداری شده " . esc($p['purchase_no']) . "')");
            if (!$ok) { $errors[] = last_error(); }
            else { $itemId = $existing ? (int)$existing['id'] : (int)inserted_id(); }
        }
        if (count($errors) === 0) {
            $hno      = next_handover_no();
            $itemSql  = $itemId > 0 ? (string)$itemId : 'NULL';
            $catSql   = $catId ? (string)$catId : 'NULL';
            $noteSql  = ($note === '') ? 'NULL' : "'" . esc($note) . "'";
            $finalN   = ($name === '' ? $p['item_name'] : $name);
            $ok = exec_sql("INSERT INTO consumptions
                (item_id, request_id, purchase_id, department_id, item_name, category_id,
                 quantity, unit, source, handover_no, receiver_name, receiver_position,
                 receiver_confirmed, delivery_date, delivered_by, notes)
                VALUES ($itemSql, " . (int)$p['request_id'] . ", $pid, " . (int)$p['department_id'] . ",
                        '" . esc($finalN) . "', $catSql, $qty, '" . esc($unit) . "', '$mode', '$hno',
                        '" . esc($rcvName) . "', '" . esc($rcvPos) . "', 0,
                        '" . esc($date) . "', " . (int)$user['id'] . ", $noteSql)");
            if ($ok) {
                $consId = (int)inserted_id();
                exec_sql("UPDATE purchases SET status='completed' WHERE id=$pid");
                exec_sql("UPDATE procurement_requests SET status='completed' WHERE id=" . (int)$p['request_id']);
                flash_set('success', 'فورم تسلیمی ' . $hno . ' ثبت شد.'
                          . ($mode === 'warehouse' ? ' کالا به ذخیره انبار افزوده شد.' : ' کالا به درخواست‌کننده تحویل شد.')
                          . ' حالا درخواست‌کننده می‌تواند آن را تأیید کند.');
                redirect_to($base . '/warehouse/handover.php?id=' . $consId);
            }
            $errors[] = last_error();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<?php if (count($already) > 0): ?>
<div class="alert alert-info shadow-sm">
  <i class="bi bi-info-circle me-2"></i>برای این خرید قبلاً تحویل ثبت شده است. مشاهده و چاپ:
  <?php foreach ($already as $a): ?>
    <a class="btn btn-sm btn-outline-primary ms-1" href="<?php echo $base; ?>/warehouse/handover.php?id=<?php echo $a['id']; ?>">فورم تسلیمی <?php echo h($a['handover_no']); ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-boxes me-2"></i><?php echo h($p['purchase_no']); ?> - <?php echo h($p['item_name']); ?></span>
    <?php echo badge($p['status']); ?>
  </div>
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-3"><strong>تأمین‌کننده</strong><br><?php echo h($p['supplier'] ?? '—'); ?></div>
      <div class="col-md-3"><strong>اداره درخواست‌کننده</strong><br><?php echo h($p['dept']); ?></div>
      <div class="col-md-3"><strong>مقدار درخواست</strong><br><?php echo xnum($p['req_qty']); ?> <?php echo h(unit_label($p['unit'])); ?></div>
      <div class="col-md-3"><strong>مقدار دریافت در گیت</strong><br class="text-nowrap"><?php echo xnum($receivedQty); ?> <?php echo h(unit_label($p['unit'])); ?></div>
      <div class="col-md-4"><strong>درخواست‌کننده</strong><br>
        <?php $en = trim($p['employee_name'] ?? ''); echo h($en !== '' ? $en : '—'); ?>
        <?php if (trim($p['employee_position'] ?? '') !== ''): ?><span class="text-muted small">(<?php echo h($p['employee_position']); ?>)</span><?php endif; ?>
      </div>
      <div class="col-md-4"><strong>تحویل مستقیم در درخواست؟</strong><br><?php echo $p['direct_delivery'] ? 'بله' : 'خیر'; ?></div>
      <?php if ($gate): ?>
      <div class="col-md-4"><strong>وضعیت گیت</strong><br><span class="badge rounded-pill <?php echo $gate['condition_ok'] ? 'text-bg-success' : 'text-bg-danger'; ?> ms-1"><?php echo $gate['condition_ok'] ? 'سالم' : 'آسیب‌دیده'; ?></span></div>
      <?php endif; ?>
    </div>
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger mt-3 py-2 mb-0"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>

<form method="post">
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-1-circle me-2"></i>نوع ثبت گدام را انتخاب کنید</div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <input type="radio" class="btn-check" name="mode" id="modeWarehouse" value="warehouse"
               <?php echo ((trim($_POST['mode'] ?? 'warehouse')) !== 'direct' && !$p['direct_delivery']) ? 'checked' : ''; ?>>
        <label class="btn btn-outline-success w-100 text-start p-3" for="modeWarehouse">
          <i class="bi bi-boxes me-1"></i><strong>ذخیره در انبار</strong>
          <div class="small">کالا به موجودی انبار (گدام) اضافه می‌شود و بعداً مصرف می‌گردد.</div>
        </label>
      </div>
      <div class="col-md-6">
        <input type="radio" class="btn-check" name="mode" id="modeDirect" value="direct"
               <?php echo ((trim($_POST['mode'] ?? '') === 'direct') || $p['direct_delivery']) ? 'checked' : ''; ?>>
        <label class="btn btn-outline-primary w-100 text-start p-3" for="modeDirect">
          <i class="bi bi-send-check me-1"></i><strong>تحویل مستقیم به درخواست‌کننده</strong>
          <div class="small">بدون ذخیره در انبار، مستقیماً به اداره درخواست‌کننده تحویل می‌شود.</div>
        </label>
      </div>
    </div>
  </div>
</div>
<div class="card mb-4" id="warehouseBox">
  <div class="card-header"><i class="bi bi-2-circle me-2"></i>مشخصات ثبت در انبار (در حالت «ذخیره در انبار»)</div>
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label required">نام کالا</label>
        <input type="text" name="name" class="form-control" value="<?php echo h(trim($_POST['name'] ?? $p['item_name'])); ?>"></div>
      <div class="col-md-6"><label class="form-label">دسته‌بندی</label>
        <select name="category_id" class="form-select">
          <option value="0">-- هیچ --</option>
          <?php $last = null;
                foreach ($cats as $c): ?>
          <?php if ($last !== $c['grp']): ?><?php if ($last !== null): ?></optgroup><?php endif; ?><optgroup label="<?php echo h($c['grp']); ?>"><?php $last = $c['grp']; ?><?php endif; ?>
          <option value="<?php echo $c['id']; ?>" <?php echo (int)$p['req_category_id'] === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['name']); ?></option>
          <?php endforeach; ?>
          <?php if ($last !== null): ?></optgroup><?php endif; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">موقعیت در انبار</label>
        <input type="text" name="location" class="form-control" placeholder="سوله / قفسه" value="<?php echo h($_POST['location'] ?? ''); ?>"></div>
      <div class="col-md-4"><label class="form-label">حداقل موجودی (Min Stock)</label>
        <input type="number" name="min_stock" min="0" step="any" class="form-control" value="<?php echo h($_POST['min_stock'] ?? '0'); ?>"></div>
      <div class="col-md-4"><label class="form-label">یادداشت انبار</label>
        <input type="text" class="form-control" value="خریداری شده <?php echo h($p['purchase_no']); ?>" disabled></div>
    </div>
  </div>
</div>

<div class="card mb-4 border-primary">
  <div class="card-header bg-primary-subtle text-primary-emphasis"><i class="bi bi-3-circle me-2"></i>فورم تسلیمی (به درخواست‌کننده)</div>
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-2"><label class="form-label required">تعداد</label>
        <input type="number" name="quantity" min="0.01" step="any" class="form-control"
               value="<?php echo h((float)($_POST['quantity'] ?? $receivedQty)); ?>" required></div>
      <div class="col-md-2"><label class="form-label">واحد</label>
        <select name="unit" class="form-select form-select-sm">
          <?php foreach (_units() as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($p['unit'] ?? 'pcs') === $k ? 'selected' : ''; ?>><?php echo h($v); ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label required">تاریخ تحویل</label>
        <input type="date" name="delivery_date" class="form-control" value="<?php echo h($_POST['delivery_date'] ?? today()); ?>" required></div>
      <div class="col-md-5"><label class="form-label">یادداشت تحویل</label>
        <input type="text" name="handover_note" class="form-control" placeholder="اختیاری" value="<?php echo h($_POST['handover_note'] ?? ''); ?>"></div>
      <div class="col-md-5"><label class="form-label required">تحویل‌گیرنده (کارمند درخواست‌کننده)</label>
        <input type="text" name="receiver_name" class="form-control" required
               value="<?php echo h(trim($_POST['receiver_name'] ?? $p['employee_name'])); ?>"></div>
      <div class="col-md-4"><label class="form-label">موقعیت وظیفه‌ای تحویل‌گیرنده</label>
        <input type="text" name="receiver_position" class="form-control"
               value="<?php echo h(trim($_POST['receiver_position'] ?? $p['employee_position'])); ?>"></div>
      <div class="col-md-3 pt-4"><button class="btn btn-success w-100" type="submit"><i class="bi bi-save me-1"></i>ثبت گدام / تحویل</button></div>
    </div>
    <div class="small text-muted mt-2">
      پس از ثبت، درخواست‌کننده باید در «فورم تسلیمی» (HND-…) دریافت کالا را <strong>تأیید</strong> کند.
    </div>
  </div>
</div>
</form>

<script>
(function () {
  var wh = document.getElementById('warehouseBox');
  var mw = document.getElementById('modeWarehouse');
  if (!wh || !mw) { return; }
  function sync() {
    wh.style.display = mw.checked ? '' : 'none';
  }
  mw.addEventListener('change', sync);
  document.getElementById('modeDirect').addEventListener('change', sync);
  sync();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>