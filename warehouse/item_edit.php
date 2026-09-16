<?php
/**
 * Add / edit a warehouse inventory item.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('warehouse_manager', 'admin');
$page_title = 'کالای انبار';
$base = BASE_URL;

$id  = (int)($_GET['id'] ?? 0);
$item = null;
if ($id > 0) {
    $item = fetch_one("SELECT * FROM warehouse_items WHERE id=$id");
    if (!$item) { flash_set('danger', 'کالا یافت نشد.'); redirect_to($base . '/warehouse/inventory.php'); }
}

$errors = [];
$old = $_POST;
if (is_post()) {
    $name   = trim($old['name'] ?? '');
    $cat_id = (int)($old['category_id'] ?? 0);
    $qty    = (float)($old['quantity'] ?? 0);
    $unit   = trim($old['unit'] ?? 'pcs');
    $loc    = trim($old['location'] ?? '');
    $min    = (float)($old['min_stock'] ?? 0);
    $notes  = trim($old['notes'] ?? '');

    if ($name === '') { $errors[] = 'نام کالا الزامی است.'; }
    if ($qty < 0)     { $errors[] = 'تعداد نمی‌تواند منفی باشد.'; }

    if (count($errors) === 0) {
        $catSql  = $cat_id ? (string)$cat_id : 'NULL';
        $locSql  = "NULL";   if ($loc  !== '') { $locSql  = "'" . esc($loc) . "'"; }
        $noteSql = "NULL";   if ($notes !== '') { $noteSql = "'" . esc($notes) . "'"; }
        if ($id > 0) {
            $ok = exec_sql("UPDATE warehouse_items SET name='" . esc($name) . "', category_id=$catSql,
                            quantity=$qty, unit='" . esc($unit) . "', location=$locSql,
                            min_stock=$min, notes=$noteSql WHERE id=$id");
        } else {
            $ok = exec_sql("INSERT INTO warehouse_items (name, category_id, quantity, unit, location, min_stock, notes)
                            VALUES ('" . esc($name) . "', $catSql, $qty, '" . esc($unit) . "', $locSql, $min, $noteSql)");
        }
        if ($ok) {
            flash_set('success', 'کالا ذخیره شد: ' . $name);
            redirect_to($base . '/warehouse/inventory.php');
        }
        $errors[] = 'ذخیره ناموفق: ' . last_error();
    }
}

$cats = fetch_all('SELECT id, name FROM categories ORDER BY name');
$v = $item ? array_merge($item, ['name' => $item['name']]) : $old;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header"><i class="bi bi-box-seam me-2"></i><?php echo $id > 0 ? 'ویرایش کالا' : 'افزودن کالای جدید'; ?></div>
  <div class="card-body">
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label required">نام کالا</label>
          <input type="text" name="name" class="form-control" required value="<?php echo h($id > 0 ? $item['name'] : ($old['name'] ?? '')); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">دسته‌بندی</label>
          <select name="category_id" class="form-select">
            <option value="0">-- بدون دسته --</option>
            <?php foreach ($cats as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo (int)($id > 0 ? $item['category_id'] : ($old['category_id'] ?? 0)) === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label required">تعداد</label>
          <input type="number" name="quantity" min="0" step="any" class="form-control" required value="<?php echo h($id > 0 ? $item['quantity'] : ($old['quantity'] ?? '0')); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label required">واحد</label>
          <select name="unit" class="form-select">
            <?php foreach (['pcs','bag','ton','kg','ream','roll','liter','set','pair','box','coil','meter'] as $u): ?>
            <option value="<?php echo $u; ?>" <?php echo ($id > 0 ? $item['unit'] : ($old['unit'] ?? 'pcs')) === $u ? 'selected' : ''; ?>><?php echo unit_label($u); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">حداقل موجودی (سطح سفارش مجدد)</label>
          <input type="number" name="min_stock" min="0" step="any" class="form-control" value="<?php echo h($id > 0 ? $item['min_stock'] : ($old['min_stock'] ?? '0')); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">موقعیت</label>
          <input type="text" name="location" class="form-control" value="<?php echo h($id > 0 ? ($item['location'] ?? '') : ($old['location'] ?? '')); ?>" placeholder="Shed A / Rack 1">
        </div>
        <div class="col-12">
          <label class="form-label">یادداشت‌ها</label>
          <textarea name="notes" rows="2" class="form-control"><?php echo h($id > 0 ? ($item['notes'] ?? '') : ($old['notes'] ?? '')); ?></textarea>
        </div>
      </div>
      <hr>
      <button class="btn btn-primary px-4" type="submit"><i class="bi bi-floppy me-2"></i>ذخیره کالا</button>
      <a class="btn btn-outline-secondary ms-2" href="<?php echo $base; ?>/warehouse/inventory.php">انصراف</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>