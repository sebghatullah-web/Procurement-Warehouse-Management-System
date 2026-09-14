<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'admin');
$page_title = 'Suppliers';
$base = BASE_URL;

$id  = (int)($_GET['id'] ?? 0);
$edit = null;
if ($id > 0) { $edit = fetch_one("SELECT * FROM suppliers WHERE id=$id"); }

$errors = [];
$old = $_POST;
if (is_post()) {
    $action = $old['action'] ?? '';
    $name   = trim($old['name'] ?? '');
    $cp     = trim($old['contact_person'] ?? '');
    $phone  = trim($old['phone'] ?? '');
    $email  = trim($old['email'] ?? '');
    $addr   = trim($old['address'] ?? '');
    $notes  = trim($old['notes'] ?? '');

    if ($action === 'add' || $action === 'update') {
        if ($name === '') { $errors[] = 'Supplier name is required.'; }
        elseif (count($errors) === 0) {
            $fields = "name='" . esc($name) . "'";
            if ($cp)    $fields .= ", contact_person='" . esc($cp) . "'";
            if ($phone) $fields .= ", phone='" . esc($phone) . "'";
            if ($email) $fields .= ", email='" . esc($email) . "'";
            if ($addr)  $fields .= ", address='" . esc($addr) . "'";
            if ($notes) $fields .= ", notes='" . esc($notes) . "'";
            $ok = ($action === 'add')
                ? exec_sql("INSERT INTO suppliers ($fields)")
                : exec_sql("UPDATE suppliers SET $fields WHERE id=$id");
            if ($ok) {
                flash_set('success', 'Supplier saved: ' . $name);
                redirect_to($base . '/suppliers/list.php');
            }
            $errors[] = last_error();
        }
    } elseif ($action === 'delete') {
        $delId = (int)($old['del_id'] ?? 0);
        if ($delId > 0) {
            $used = fetch_one("SELECT COUNT(*) n FROM purchases WHERE supplier_id=$delId");
            if ((int)$used['n'] > 0) {
                flash_set('warning', 'This supplier appears in ' . (int)$used['n'] . ' purchase record(s).');
            } else {
                exec_sql("DELETE FROM suppliers WHERE id=$delId");
                flash_set($conn->errno === 0 ? 'success' : 'danger',
                          $conn->errno === 0 ? 'Supplier removed.' : $conn->error);
            }
        }
        redirect_to($base . '/suppliers/list.php');
    }
}

$rows = fetch_all("SELECT * FROM suppliers ORDER BY name");
require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($edit): ?>
<div class="alert alert-info"><i class="bi bi-pencil-square me-2"></i>Editing: <strong><?php echo h($edit['name']); ?></strong>
  <a class="float-end" href="<?php echo $base; ?>/suppliers/list.php">Cancel</a></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><?php echo $edit ? 'Update Supplier' : 'Add Supplier'; ?></div>
      <div class="card-body">
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'add'; ?>">
          <div class="mb-2"><label class="form-label required">Supplier Name</label>
            <input type="text" name="name" class="form-control" required value="<?php echo h($edit ? $edit['name'] : ($old['name'] ?? '')); ?>"></div>
          <div class="mb-2"><label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" class="form-control" value="<?php echo h($edit ? ($edit['contact_person'] ?? '') : ($old['contact_person'] ?? '')); ?>"></div>
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?php echo h($edit ? ($edit['phone'] ?? '') : ($old['phone'] ?? '')); ?>"></div>
            <div class="col-md-6"><label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo h($edit ? ($edit['email'] ?? '') : ($old['email'] ?? '')); ?>"></div>
          </div>
          <div class="mt-2"><label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" value="<?php echo h($edit ? ($edit['address'] ?? '') : ($old['address'] ?? '')); ?>"></div>
          <div class="mt-2"><label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control"><?php echo h($edit ? ($edit['notes'] ?? '') : ($old['notes'] ?? '')); ?></textarea></div>
          <button class="btn btn-primary mt-2" type="submit"><i class="bi bi-floppy me-2"></i>Save Supplier</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">Suppliers <span class="text-muted small">(<?php echo count($rows); ?>)</span></div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>City</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $s): ?>
            <tr>
              <td><?php echo h($s['name']); ?></td>
              <td class="text-muted small"><?php echo h($s['contact_person'] ?? '—'); ?></td>
              <td class="text-muted small"><?php echo h($s['phone'] ?? '—'); ?></td>
              <td class="text-muted small"><?php echo h($s['address'] ?? '—'); ?></td>
              <td class="table-actions text-end">
                <a class="btn btn-sm btn-outline-primary" href="?id=<?php echo $s['id']; ?>"><i class="bi bi-pencil-square"></i></a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete supplier &quot;<?php echo h($s['name']); ?>&quot;?');">
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="del_id" value="<?php echo $s['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($rows) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No suppliers yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>