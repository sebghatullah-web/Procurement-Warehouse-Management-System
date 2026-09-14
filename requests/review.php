<?php
/**
 * Procurement review page: Approve (-> warehouse check) or Close (unnecessary).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'admin');
$page_title = 'Review Request';
$base = BASE_URL;

$id = (int)($_GET['id'] ?? 0);
$r = fetch_one("SELECT r.*, d.name dept, u.name requester
                FROM procurement_requests r
                JOIN departments d ON d.id=r.department_id
                JOIN users u ON u.id=r.requested_by
                WHERE r.id = $id");
if (!$r) {
    flash_set('danger', 'Request not found.');
    redirect_to($base . '/requests/list.php');
}
if ($r['status'] !== 'pending') {
    flash_set('warning', 'This request has already been reviewed (now: ' . req_status_label($r['status']) . ').');
    redirect_to($base . '/requests/view.php?id=' . $id);
}

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    $note   = trim($_POST['review_note'] ?? '');
    $valid  = ['approve', 'close'];
    if (!in_array($action, $valid, true)) { $errors[] = 'Invalid action.'; }
    if (count($errors) === 0) {
        $to = ($action === 'approve') ? 'warehouse_check' : 'closed';
        $ok = exec_sql("UPDATE procurement_requests SET status='$to',
                        reviewed_by=" . (int)$user['id'] . ",
                        review_note='" . esc($note) . "', reviewed_at=NOW() WHERE id=$id");
        if ($ok) {
            flash_set('success', 'Request ' . $r['request_no'] . ' ' .
                      (($action === 'approve') ? 'approved - sent to warehouse check' : 'closed (declared unnecessary)') . '.');
            redirect_to($base . '/requests/view.php?id=' . $id);
        }
        $errors[] = 'Update failed: ' . last_error();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-check2-circle me-2"></i>Reviewing <?php echo h($r['request_no']); ?></div>
  <div class="card-body">
    <div class="row mb-2">
      <div class="col-md-3"><strong>Department</strong><br><?php echo h($r['dept']); ?></div>
      <div class="col-md-3"><strong>Item</strong><br><?php echo h($r['item_name']); ?></div>
      <div class="col-md-2"><strong>Quantity</strong><br><?php echo xnum($r['quantity']); ?> <?php echo h($r['unit']); ?></div>
      <div class="col-md-2"><strong>Urgency</strong><br><?php echo badge($r['urgency']); ?></div>
      <div class="col-md-2"><strong>Requested by</strong><br><?php echo h($r['requester']); ?></div>
    </div>
    <div class="small text-muted mb-3">
      <strong>Reason:</strong> <?php echo h($r['reason'] ?? '—'); ?><br>
      <strong>Direct delivery:</strong> <?php echo $r['direct_delivery'] ? 'Yes' : 'No'; ?>
    </div>

    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="card border-success-subtle h-100">
          <div class="card-header text-success">Approve - Necessary</div>
          <div class="card-body small">
            Sends the request to the <strong>warehouse manager</strong> to check stock availability.
            <form method="post">
              <input type="hidden" name="action" value="approve">
              <div class="mb-2"><textarea name="review_note" rows="2" class="form-control" placeholder="Optional note to warehouse (recommendation)"></textarea></div>
              <button class="btn btn-success w-100" type="submit"><i class="bi bi-check2 me-1"></i>Approve &amp; Send to Warehouse</button>
            </form>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-danger-subtle h-100">
          <div class="card-header text-danger">Close - Unnecessary</div>
          <div class="card-body small">
            Declares the request unnecessary and ends the process. Closed requests are read-only.
            <form method="post">
              <input type="hidden" name="action" value="close">
              <div class="mb-2"><textarea name="review_note" rows="2" class="form-control" required placeholder="Reason for closing (must be filled)"></textarea></div>
              <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-x-circle me-1"></i>Close Request</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>