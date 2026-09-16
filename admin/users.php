<?php
/**
 * User management (admin only).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$page_title = 'مدیریت کاربران';
$base = BASE_URL;

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $role   = trim($_POST['role'] ?? '');
    $dept   = (int)($_POST['department_id'] ?? 0);
    $pw     = trim($_POST['password'] ?? '');
    $userId = (int)($_POST['user_id'] ?? 0);

    if (in_array($action, ['add','update'])) {
        if ($name === '' || $email === '' || $role === '') {
            $errors[] = 'نام، ایمیل و نقش الزامی هستند.';
        } elseif (empty($pw) && $action === 'add') {
            $errors[] = 'رمز عبور برای کاربر جدید الزامی است.';
        }
        if (count($errors) === 0) {
            $pwSql = !empty($pw) ? "password = '" . password_hash($pw, PASSWORD_DEFAULT) . "', " : '';
            if ($action === 'add') {
                $ok = exec_sql("INSERT INTO users (name, email, password, role, department_id)
                                VALUES ('" . esc($name) . "','" . esc($email) . "','" . password_hash($pw, PASSWORD_DEFAULT) . "','" . esc($role) . "'," . ($dept ?: 'NULL') . ")");
            } else {
                $ok = exec_sql("UPDATE users SET name='" . esc($name) . "', email='" . esc($email) . "', role='" . esc($role) . "', department_id=" . ($dept ?: 'NULL') . " WHERE id=$userId");
            }
            if ($ok) { flash_set('success', 'کاربر ذخیره شد: ' . $name); redirect_to($base . '/admin/users.php'); }
            $errors[] = last_error();
        }
    } elseif ($action === 'delete') {
        $delId = (int)($_POST['del_id'] ?? 0);
        if ($delId > 0 && $delId !== (int)$user['id']) {
            exec_sql("DELETE FROM users WHERE id=$delId");
            flash_set('success', 'کاربر حذف شد.');
        } elseif ($delId === (int)$user['id']) {
            flash_set('warning', 'شما نمی‌توانید حساب کاربری خودتان را حذف کنید.');
        }
        redirect_to($base . '/admin/users.php');
    } elseif ($action === 'toggle') {
        $tId = (int)($_POST['user_id'] ?? 0);
        $active = (int)($_POST['active'] ?? 1);
        $new = $active ? 0 : 1;
        exec_sql("UPDATE users SET status=$new WHERE id=$tId");
        flash_set('success', 'وضعیت حساب به‌روزرسانی شد.');
        redirect_to($base . '/admin/users.php');
    }
}

$edit = null;
if (isset($_GET['id'])) {
    $eid = (int)$_GET['id'];
    $edit = fetch_one("SELECT * FROM users WHERE id=$eid");
}

$users = fetch_all("SELECT u.*, d.name dept FROM users u
                    LEFT JOIN departments d ON d.id=u.department_id
                    ORDER BY u.name");
$depts = fetch_all('SELECT id, name FROM departments ORDER BY name');

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($edit): ?>
<div class="alert alert-info"><i class="bi bi-pencil-square me-2"></i>در حال ویرایش: <strong><?php echo h($edit['name']); ?></strong>
  <a class="float-end" href="<?php echo $base; ?>/admin/users.php">انصراف</a></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><?php echo $edit ? 'به‌روزرسانی کاربر' : 'افزودن کاربر'; ?></div>
      <div class="card-body">
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'add'; ?>">
          <?php if ($edit): ?><input type="hidden" name="user_id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
          <div class="mb-2"><label class="form-label required">نام کامل</label>
            <input type="text" name="name" class="form-control" required value="<?php echo h($edit ? $edit['name'] : ($_POST['name'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label required">ایمیل</label>
            <input type="email" name="email" class="form-control" required value="<?php echo h($edit ? $edit['email'] : ($_POST['email'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label required">نقش</label>
            <select name="role" class="form-select" required>
              <option value="">-- انتخاب نقش --</option>
              <?php foreach (roles() as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo ($edit ? $edit['role'] : ($_POST['role'] ?? '')) === $k ? 'selected' : ''; ?>><?php echo h($v); ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="mb-2"><label class="form-label">اداره</label>
            <select name="department_id" class="form-select">
              <option value="0">-- هیچ --</option>
              <?php foreach ($depts as $d): ?>
              <option value="<?php echo $d['id']; ?>" <?php echo ($edit ? (int)$edit['department_id'] : 0) === (int)$d['id'] ? 'selected' : ''; ?>><?php echo h($d['name']); ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="mb-2"><label class="form-label">رمز عبور</label>
            <input type="text" name="password" class="form-control" placeholder="<?php echo $edit ? 'خالی بگذارید تا حفظ شود' : 'تعیین رمز عبور'; ?>" <?php echo $edit ? '' : 'required'; ?>>
            <div class="form-text"><?php echo $edit ? 'خالی بگذارید تا رمز عبور فعلی حفظ شود.' : ''; ?></div></div>
          <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-2"></i>ذخیره کاربر</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">کاربران <span class="text-muted small">(<?php echo count($users); ?>)</span></div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th><th>اداره</th><th>فعال</th><th class="text-end">عملیات</th></tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo h($u['name']); ?></td>
              <td class="text-muted small"><?php echo h($u['email']); ?></td>
              <td class="text-muted small"><?php echo role_label($u['role']); ?></td>
              <td class="text-muted small"><?php echo h($u['dept'] ?? '—'); ?></td>
              <td class="text-nowrap">
                <span class="badge text-bg-<?php echo $u['status'] ? 'success' : 'secondary'; ?>">
                  <?php echo $u['status'] ? 'فعال' : 'غیرفعال'; ?></span>
                <form method="post" class="d-inline" onsubmit="return confirm('تغییر وضعیت برای <?php echo h($u['name']); ?>؟');">
                  <input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <input type="hidden" name="active" value="<?php echo $u['status'] ? 1 : 0; ?>">
                  <button class="btn btn-xs btn-link p-0 ms-1" type="submit">تغییر</button>
                </form>
              </td>
              <td class="table-actions text-end">
                <a class="btn btn-sm btn-outline-primary" href="?id=<?php echo $u['id']; ?>"><i class="bi bi-pencil-square"></i></a>
                <form method="post" class="d-inline" onsubmit="return confirm('حذف کاربر <?php echo h($u['name']); ?>؟');">
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="del_id" value="<?php echo $u['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>