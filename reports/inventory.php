<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('general_manager', 'procurement_manager', 'warehouse_manager', 'admin');
$page_title = 'Current Inventory';
$base = BASE_URL;

$q = trim($_GET['q'] ?? '');
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
    <div class="col-md-4"><label class="form-label small text-muted">Search</label>
      <input type="text" name="q" class="form-control form-control-sm" value="<?php echo h($q); ?>" placeholder="Item / location"></div>
    <div class="col-md-3"><label class="form-label small text-muted">Category</label>
      <select name="category_id" class="form-select form-select-sm" data-autosubmit>
        <option value="0">All</option>
        <?php foreach ($cats as $c): ?>
        <option value="<?php echo $c['id']; ?>" <?php echo $cat===(int)$c['id']?'selected':''; ?>><?php echo h($c['name']); ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-2 pt-4">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="low" value="1" id="lowOnly" <?php echo $low?'checked':''; ?> onchange="this.form.submit()">
        <label class="form-check-label form-label" for="lowOnly">Low stock only</label></div>
    </div>
    <div class="col-auto pt-3"><button class="btn btn-primary btn-sm" type="submit">Filter</button></div>
    <div class="col-auto pt-3"><a class="btn btn-outline-secondary btn-sm" href="<?php echo $base; ?>/reports/inventory.php">Reset</a></div>
  </form>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-archive me-2"></i>Warehouse Inventory <span class="text-muted small">(<?php echo count($rows); ?> records)</span></span>
    <button class="btn btn-sm btn-outline-secondary" data-print><i class="bi bi-printer me-1"></i>Print</button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Item</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Min Stock</th><th>Location</th><th>Updated</th></tr></thead>
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
        </tr>
      <?php endforeach; ?>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No inventory records.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>