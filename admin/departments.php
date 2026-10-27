<?php
/**
 * Department management (admin only).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$page_title = 'ادارات';
$base = BASE_URL;

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['name'] ?? '');
    $code   = strtoupper(trim($_POST['code'] ?? ''));
    $desc   = trim($_POST['description'] ?? '');
    $parent = (int)($_POST['parent_id'] ?? 0);
    $deptId = (int)($_POST['department_id'] ?? 0);

    if ($action === 'add' || $action === 'update') {
        if ($name === '' || $code === '') { $errors[] = 'نام و کد اداره الزامی است.'; }
        elseif ($deptId > 0 && $parent === $deptId) { $errors[] = 'یک اداره نمی‌تواند والد خودش باشد.'; }
        elseif (count($errors) === 0) {
            $parentSql = $parent > 0 ? (string)$parent : 'NULL';
            $descSql   = $desc === '' ? 'NULL' : "'" . esc($desc) . "'";
            if ($action === 'add') {
                $ok = exec_sql("INSERT INTO departments (name, code, description, parent_id)
                                VALUES ('" . esc($name) . "', '" . esc($code) . "', $descSql, $parentSql)");
            } else {
                $ok = exec_sql("UPDATE departments SET
                                name='" . esc($name) . "', code='" . esc($code) . "',
                                description=$descSql, parent_id=$parentSql WHERE id=$deptId");
            }
            if ($ok) {
                flash_set('success', 'اداره ذخیره شد: ' . $name);
                redirect_to($base . '/admin/departments.php');
            }
            $errors[] = last_error();
        }
    } elseif ($action === 'delete') {
        $delId = (int)($_POST['del_id'] ?? 0);
        if ($delId > 0) {
            $kids = fetch_one("SELECT COUNT(*) n FROM departments WHERE parent_id=$delId");
            $used = fetch_one("SELECT COUNT(*) n FROM users WHERE department_id=$delId");
            if ((int)$kids['n'] > 0) {
                flash_set('warning', 'حذف ممکن نیست: این اداره ' . (int)$kids['n'] . ' اداره زیر مجموعه دارد - اول آن‌ها را نقل یا حذف کنید.');
            } elseif ((int)$used['n'] > 0) {
                flash_set('warning', 'حذف ممکن نیست: اداره توسط ' . (int)$used['n'] . ' کاربر استفاده می‌شود.');
            } else {
                exec_sql("DELETE FROM departments WHERE id=$delId");
                flash_set($conn->errno === 0 ? 'success' : 'danger',
                          $conn->errno === 0 ? 'اداره حذف شد.' : $conn->error);
            }
        }
        redirect_to($base . '/admin/departments.php');
    }
}

$edit = null;
if (isset($_GET['id'])) {
    $eid = (int)$_GET['id'];
    $edit = fetch_one("SELECT * FROM departments WHERE id=$eid");
}
$branches = fetch_all('SELECT id, name FROM departments WHERE parent_id IS NULL ORDER BY name');
$rows = fetch_all("SELECT d.*, p.name branch FROM departments d
                   LEFT JOIN departments p ON p.id = d.parent_id
                   ORDER BY IF(d.parent_id IS NULL, d.id, d.parent_id), d.parent_id IS NOT NULL, d.name");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($edit): ?>
<div class="alert alert-info"><i class="bi bi-pencil-square me-2"></i>در حال ویرایش: <strong><?php echo h($edit['name']); ?></strong>
  <a class="float-end" href="<?php echo $base; ?>/admin/departments.php">انصراف</a></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><?php echo $edit ? 'به‌روزرسانی اداره' : 'افزودن اداره'; ?></div>
      <div class="card-body">
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'add'; ?>">
          <?php if ($edit): ?><input type="hidden" name="department_id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
          <div class="mb-2"><label class="form-label">بخش اصلی (والد)</label>
            <select name="parent_id" class="form-select">
              <option value="0">-- بدون والد (بخش اصلی) --</option>
              <?php foreach ($branches as $b): ?>
              <option value="<?php echo $b['id']; ?>" <?php echo (int)($edit ? $edit['parent_id'] : ($_POST['parent_id'] ?? 0)) === (int)$b['id'] ? 'selected' : ''; ?>><?php echo h($b['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text text-muted small">بخش اصلی را بدون والد ثبت کنید (مثلاً «امور دفتر»، «پیمان کاران»)؛ دیپارتمنت‌ها و تیم‌ها را زیر یک بخش ثبت کنید.</div>
          </div>
          <div class="mb-2"><label class="form-label required">نام اداره / تیم</label>
            <input type="text" name="name" class="form-control" required value="<?php echo h($edit ? $edit['name'] : ($_POST['name'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label required">کد اداره</label>
            <input type="text" name="code" class="form-control" required placeholder="e.g. SITE, HQ, CT1" value="<?php echo h($edit ? $edit['code'] : ($_POST['code'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label">توضیحات</label>
            <textarea name="description" rows="2" class="form-control"><?php echo h($edit ? ($edit['description'] ?? '') : ($_POST['description'] ?? '')); ?></textarea></div>
          <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-2"></i>ذخیره اداره</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">ادارات <span class="text-muted small">(<?php echo count($rows); ?>)</span></div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>کد</th><th>نام</th><th>بخش</th><th>توضیحات</th><th class="text-end">عملیات</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $d): ?>
            <tr>
              <td class="text-muted small"><?php $dcode = trim($d['code'] ?? ''); echo h($dcode !== '' ? $dcode : '—'); ?></td>
              <td><?php if ($d['parent_id']): ?><span class="text-muted">↳ </span><?php endif; ?><?php echo h($d['name']); ?></td>
              <td class="text-muted small"><?php echo h($d['branch'] ?? ''); ?></td>
              <td class="text-muted small"><?php echo h($d['description'] ?? '—'); ?></td>
              <td class="table-actions text-end">
                <a class="btn btn-sm btn-outline-primary" href="?id=<?php echo $d['id']; ?>"><i class="bi bi-pencil-square"></i></a>
                <form method="post" class="d-inline" onsubmit="return confirm('حذف اداره &quot;<?php echo h($d['name']); ?>&quot;؟');">
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="del_id" value="<?php echo $d['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($rows) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">هیچ اداره‌ای ثبت نشده است.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>