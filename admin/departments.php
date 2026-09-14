<?php
/**
 * Department management (admin only).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('admin');
$page_title = 'Departments';
$base = BASE_URL;

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['name'] ?? '');
    $code   = strtoupper(trim($_POST['code'] ?? ''));
    $desc   = trim($_POST['description'] ?? '');
    $deptId = (int)($_POST['department_id'] ?? 0);

    if ($action === 'add' || $action === 'update') {
        if ($name === '' || $code === '') { $errors[] = 'Department name and code are required.'; }
        elseif (count($errors) === 0) {
            $fields = "name='" . esc($name) . "', code='" . esc($code) . "'";
            if ($desc) $fields .= ", description='" . esc($desc) . "'";
            $ok = ($action === 'add')
                ? exec_sql("INSERT INTO departments ($fields)")
                : exec_sql("UPDATE departments SET $fields WHERE id=$deptId");
            if ($ok) {
                flash_set('success', 'Department saved: ' . $name);
                redirect_to($base . '/admin/departments.php');
            }
            $errors[] = last_error();
        }
    } elseif ($action === 'delete') {
        $delId = (int)($_POST['del_id'] ?? 0);
        if ($delId > 0) {
            $used = fetch_one("SELECT COUNT(*) n FROM users WHERE department_id=$delId");
            if ((int)$used['n'] > 0) {
                flash_set('warning', 'Cannot delete: department is used by ' . (int)$used['n'] . ' user(s).');
            } else {
                exec_sql("DELETE FROM departments WHERE id=$delId");
                flash_set('success', 'Department removed.');
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
$rows = fetch_all("SELECT * FROM departments ORDER BY name");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($edit): ?>
<div class="alert alert-info"><i class="bi bi-pencil-square me-2"></i>Editing: <strong><?php echo h($edit['name']); ?></strong>
  <a class="float-end" href="<?php echo $base; ?>/admin/departments.php">Cancel</a></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><?php echo $edit ? 'Update Department' : 'Add Department'; ?></div>
      <div class="card-body">
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'add'; ?>">
          <?php if ($edit): ?><input type="hidden" name="department_id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
          <div class="mb-2"><label class="form-label required">Department Name</label>
            <input type="text" name="name" class="form-control" required value="<?php echo h($edit ? $edit['name'] : ($_POST['name'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label required">Code</label>
            <input type="text" name="code" class="form-control" required placeholder="e.g. SITE, HQ" value="<?php echo h($edit ? $edit['code'] : ($_POST['code'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label">Description</label>
            <textarea name="description" rows="2" class="form-control"><?php echo h($edit ? ($edit['description'] ?? '') : ($_POST['description'] ?? '')); ?></textarea></div>
          <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-2"></i>Save Department</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">Departments <span class="text-muted small">(<?php echo count($rows); ?>)</span></div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Code</th><th>Name</th><th>Description</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $d): ?>
            <tr>
              <td class="text-muted small"><?php echo h($d['code']); ?></td>
              <td><?php echo h($d['name']); ?></td>
              <td class="text-muted small"><?php echo h($d['description'] ?? '—'); ?></td>
              <td class="table-actions text-end">
                <a class="btn btn-sm btn-outline-primary" href="?id=<?php echo $d['id']; ?>"><i class="bi bi-pencil-square"></i></a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete department &quot;<?php echo h($d['name']); ?>&quot;?');">
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="del_id" value="<?php echo $d['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($rows) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No departments.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>