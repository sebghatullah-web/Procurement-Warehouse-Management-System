<?php
/**
 * Warehouse inventory list + quick delete.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('warehouse_manager', 'admin');
$page_title = 'Warehouse Inventory';
$base = BASE_URL;

if (is_post() && (($_POST['action'] ?? '') === 'delete')) {
    $delId = (int)($_POST['id'] ?? 0);
    if ($delId > 0) {
        $name = fetch_one("SELECT name FROM warehouse_items WHERE id=$delId");
        if ($name) {
            exec_sql("DELETE FROM warehouse_items WHERE id=$delId");
            flash_set($conn->errno === 0 ? 'success' : 'danger',
                      $conn->errno === 0 ? "Item removed: " . $name['name'] : "Delete failed: " . $conn->error);
        }
    }
    redirect_to($base . '/warehouse/inventory.php');
}

$q  = trim($_GET['q'] ?? '');
$cat = (int)($_GET['category_id'] ?? 0);
$low = isset($_GET['low']) && (int)$_GET['low'] === 1;

$where = [];
if ($q !== '')   { $where[] = "(w.name LIKE '%" . esc($q) . "%' OR w.location LIKE '%" . esc($q) . "%')"; }
if ($cat > 0)    { $where[] = 'w.category_id = ' . $cat; }
if ($low)        { $where[] = 'w.quantity <= w.min_stock'; }

$sql = "SELECT w.*, c.name cat FROM warehouse_items w LEFT JOIN categories c ON c.id=w.category_id";
if (count($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY w.name';
$rows = fetch_all($sql);
$cats = fetch_all('SELECT id, name FROM categories ORDER BY name');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="filter-box rounded-2 p-3 mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small text-muted">Search</label>
      <input type="text" name="q" class="form-control form-control-sm" value="<?php echo h($q); ?>" placeholder="Item / location">
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted">Category</label>
      <select name="category_id" class="form-select form-select-sm" data-autosubmit>
        <option value="0">All</option>
        <?php foreach ($cats as $c): ?>
        <option value="<?php echo $c['id']; ?>" <?php echo $cat === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 pt-4">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="low" value="1" id="lowOnly" <?php echo $low ? 'checked' : ''; ?> onchange="this.form.submit()">
        <label class="form-check-label form-label" for="lowOnly">Low stock only</label>
      </div>
    </div>
    <div class="col-auto pt-3"><button class="btn btn-primary btn-sm" type="submit">Filter</button></div>
    <div class="col-auto pt-3"><a class="btn btn-success btn-sm" href="<?php echo $base; ?>/warehouse/item_edit.php"><i class="bi bi-plus-square me-1"></i>Add Item</a></div>
  </form>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-boxes me-2"></i>Stock Levels <span class="text-muted small">(<?php echo count($rows); ?> records)</span></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><th>Item</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Min Stock</th><th>Location</th><th>Updated</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $w): ?>
        <?php $isLow = (float)$w['quantity'] <= (float)$w['min_stock']; ?>
        <tr class="<?php echo $isLow ? 'low-stock' : ''; ?>">
          <td><?php echo h($w['name']); ?></td>
          <td class="text-muted small"><?php echo h($w['cat'] ?? '—'); ?></td>
          <td class="<?php echo $isLow ? 'text-danger fw-semibold' : 'fw-semibold'; ?>"><?php echo xnum($w['quantity']); ?></td>
          <td><?php echo h($w['unit']); ?></td>
          <td><?php echo xnum($w['min_stock']); ?></td>
          <td class="text-muted small"><?php echo h($w['location'] ?? '—'); ?></td>
          <td class="text-muted small text-nowrap"><?php echo h(substr($w['updated_at'] ?? '', 0, 10)); ?></td>
          <td class="table-actions">
            <a class="btn btn-sm btn-outline-primary" href="item_edit.php?id=<?php echo $w['id']; ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete &quot;<?php echo h($w['name']); ?>&quot; from inventory? Related usage history is kept.');">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $w['id']; ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete"><i class="bi bi-trash3"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No inventory records. <a href="item_edit.php">Add an item</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>