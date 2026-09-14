<?php
/**
 * User management (admin only).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$page_title = 'User Management';
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
            $errors[] = 'Name, email and role are required.';
        } elseif (empty($pw) && $action === 'add') {
            $errors[] = 'Password is required for new users.';
        }
        if (count($errors) === 0) {
            $pwSql = !empty($pw) ? "password = '" . password_hash($pw, PASSWORD_DEFAULT) . "', " : '';
            if ($action === 'add') {
                $ok = exec_sql("INSERT INTO users (name, email, password, role, department_id)
                                VALUES ('" . esc($name) . "','" . esc($email) . "','" . password_hash($pw, PASSWORD_DEFAULT) . "','" . esc($role) . "'," . ($dept ?: 'NULL') . ")");
            } else {
                $ok = exec_sql("UPDATE users SET name='" . esc($name) . "', email='" . esc($email) . "', role='" . esc($role) . "', department_id=" . ($dept ?: 'NULL') . " WHERE id=$userId");
            }
            if ($ok) { flash_set('success', 'User saved: ' . $name); redirect_to($base . '/admin/users.php'); }
            $errors[] = last_error();
        }
    } elseif ($action === 'delete') {
        $delId = (int)($_POST['del_id'] ?? 0);
        if ($delId > 0 && $delId !== (int)$user['id']) {
            exec_sql("DELETE FROM users WHERE id=$delId");
            flash_set('success', 'User removed.');
        } elseif ($delId === (int)$user['id']) {
            flash_set('warning', 'You cannot delete your own account.');
        }
        redirect_to($base . '/admin/users.php');
    } elseif ($action === 'toggle') {
        $tId = (int)($_POST['user_id'] ?? 0);
        $active = (int)($_POST['active'] ?? 1);
        $new = $active ? 0 : 1;
        exec_sql("UPDATE users SET is_active=$new WHERE id=$tId");
        flash_set('success', 'Account status updated.');
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
<div class="alert alert-info"><i class="bi bi-pencil-square me-2"></i>Editing: <strong><?php echo h($edit['name']); ?></strong>
  <a class="float-end" href="<?php echo $base; ?>/admin/users.php">Cancel</a></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><?php echo $edit ? 'Update User' : 'Add User'; ?></div>
      <div class="card-body">
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'add'; ?>">
          <?php if ($edit): ?><input type="hidden" name="user_id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
          <div class="mb-2"><label class="form-label required">Full Name</label>
            <input type="text" name="name" class="form-control" required value="<?php echo h($edit ? $edit['name'] : ($_POST['name'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label required">Email</label>
            <input type="email" name="email" class="form-control" required value="<?php echo h($edit ? $edit['email'] : ($_POST['email'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label required">Role</label>
            <select name="role" class="form-select" required>
              <option value="">-- role --</option>
              <?php foreach (roles() as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo ($edit ? $edit['role'] : ($_POST['role'] ?? '')) === $k ? 'selected' : ''; ?>><?php echo h($v); ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="mb-2"><label class="form-label">Department</label>
            <select name="department_id" class="form-select">
              <option value="0">-- None --</option>
              <?php foreach ($depts as $d): ?>
              <option value="<?php echo $d['id']; ?>" <?php echo ($edit ? (int)$edit['department_id'] : 0) === (int)$d['id'] ? 'selected' : ''; ?>><?php echo h($d['name']); ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="mb-2"><label class="form-label">Password</label>
            <input type="text" name="password" class="form-control" placeholder="<?php echo $edit ? 'Leave blank to keep current' : 'Set password'; ?>" <?php echo $edit ? '' : 'required'; ?>>
            <div class="form-text"><?php echo $edit ? 'Leave blank to keep current password.' : ''; ?></div></div>
          <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-2"></i>Save User</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">Users <span class="text-muted small">(<?php echo count($users); ?>)</span></div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Active</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo h($u['name']); ?></td>
              <td class="text-muted small"><?php echo h($u['email']); ?></td>
              <td class="text-muted small"><?php echo h($u['role']); ?></td>
              <td class="text-muted small"><?php echo h($u['dept'] ?? '—'); ?></td>
              <td class="text-nowrap">
                <span class="badge text-bg-<?php echo $u['is_active'] ? 'success' : 'secondary'; ?>">
                  <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></span>
                <form method="post" class="d-inline" onsubmit="return confirm('Toggle status for <?php echo h($u['name']); ?>?');">
                  <input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <input type="hidden" name="active" value="<?php echo $u['is_active'] ? 1 : 0; ?>">
                  <button class="btn btn-xs btn-link p-0 ms-1" type="submit">Switch</button>
                </form>
              </td>
              <td class="table-actions text-end">
                <a class="btn btn-sm btn-outline-primary" href="?id=<?php echo $u['id']; ?>"><i class="bi bi-pencil-square"></i></a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete user <?php echo h($u['name']); ?>?');">
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