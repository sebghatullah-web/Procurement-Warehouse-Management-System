<?php
/**
 * Item categories CRUD (categories can be added / removed at runtime).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('warehouse_manager', 'admin');
$page_title = 'Item Categories';
$base = BASE_URL;

$id  = (int)($_GET['id'] ?? 0);
$edit = null;
if ($id > 0) { $edit = fetch_one("SELECT * FROM categories WHERE id=$id"); }

$errors = [];
$old = $_POST;
if (is_post()) {
    $action = $old['action'] ?? '';
    $name   = trim($old['name'] ?? '');
    $desc   = trim($old['description'] ?? '');
    if ($action === 'add' || $action === 'update') {
        if ($name === '') { $errors[] = 'Category name is required.'; }
        elseif (count($errors) === 0) {
            $descSql = $desc === '' ? 'NULL' : "'" . esc($desc) . "'";
            $ok = ($action === 'add')
                ? exec_sql("INSERT INTO categories (name, description) VALUES ('" . esc($name) . "', $descSql)")
                : exec_sql("UPDATE categories SET name='" . esc($name) . "', description=$descSql WHERE id=$id");
            if ($ok) {
                flash_set('success', 'Category saved: ' . $name);
                redirect_to($base . '/warehouse/categories.php');
            }
            $errors[] = last_error();
        }
    } elseif ($action === 'delete') {
        $delId = (int)($old['del_id'] ?? 0);
        if ($delId > 0) {
            $used = fetch_one("SELECT COUNT(*) n FROM warehouse_items WHERE category_id=$delId");
            if ((int)$used['n'] > 0) {
                flash_set('warning', $used['n'] . ' warehouse item(s) still use this category - reassign them first.');
            } else {
                exec_sql("DELETE FROM categories WHERE id=$delId");
                flash_set($conn->errno === 0 ? 'success' : 'danger',
                          $conn->errno === 0 ? 'Category removed.' : $conn->error);
            }
        }
        redirect_to($base . '/warehouse/categories.php');
    }
}

$cats = fetch_all("SELECT c.*, (SELECT COUNT(*) FROM warehouse_items w WHERE w.category_id=c.id) item_count
                   FROM categories c ORDER BY c.name");
require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($edit): ?>
<div class="alert alert-info"><i class="bi bi-pencil-square me-2"></i>Editing: <strong><?php echo h($edit['name']); ?></strong>
  <a class="float-end" href="<?php echo $base; ?>/warehouse/categories.php">Cancel edit</a></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><?php echo $edit ? 'Update Category' : 'Add Category'; ?></div>
      <div class="card-body">
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'add'; ?>">
          <div class="mb-2">
            <label class="form-label required">Category Name</label>
            <input type="text" name="name" class="form-control" required value="<?php echo h($edit ? $edit['name'] : ($old['name'] ?? '')); ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" value="<?php echo h($edit ? ($edit['description'] ?? '') : ($old['description'] ?? '')); ?>">
          </div>
          <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-2"></i>Save Category</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">Categories <span class="text-muted small">(<?php echo count($cats); ?>)</span></div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Name</th><th>Description</th><th>Items</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($cats as $c): ?>
            <tr>
              <td><?php echo h($c['name']); ?></td>
              <td class="text-muted small"><?php echo h($c['description'] ?? '—'); ?></td>
              <td><?php echo (int)$c['item_count']; ?></td>
              <td class="table-actions text-end">
                <a class="btn btn-sm btn-outline-primary" href="categories.php?id=<?php echo $c['id']; ?>"><i class="bi bi-pencil-square"></i></a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete category &quot;<?php echo h($c['name']); ?>&quot;?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="del_id" value="<?php echo $c['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($cats) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No categories yet - add one.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>