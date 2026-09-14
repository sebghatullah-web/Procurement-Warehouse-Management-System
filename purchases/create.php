<?php
/**
 * Start the purchase process for a request marked "purchase_required".
 *  - Urgent : immediate supplier + price (skip quotations/committee).
 *  - Normal : open a draft purchase and collect >= 3 quotations.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'admin');
$page_title = 'Start Purchase';
$base = BASE_URL;

$rid = (int)($_GET['request_id'] ?? 0);
$r = fetch_one("SELECT r.*, d.name dept FROM procurement_requests r
                JOIN departments d ON d.id=r.department_id WHERE r.id=$rid");
if (!$r) {
    flash_set('danger', 'Request not found.');
    redirect_to($base . '/requests/list.php');
}
if ($r['status'] !== 'purchase_required') {
    flash_set('warning', 'This request is not waiting for a purchase (status: ' . req_status_label($r['status']) . ').');
    redirect_to($base . '/requests/view.php?id=' . $rid);
}

$errors = [];
$suppliers = fetch_all('SELECT id, name FROM suppliers ORDER BY name');

if (is_post()) {
    $mode = $_POST['mode'] ?? '';
    $qty  = (float)$r['quantity'];
    $prefix = 'PUR-' . date('Y') . '-';
    $last = fetch_one("SELECT purchase_no FROM purchases WHERE purchase_no LIKE '$prefix%' ORDER BY purchase_no DESC LIMIT 1");
    $n = $last ? (int)substr($last['purchase_no'], strlen($prefix)) + 1 : 1;
    $purchase_no = $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);

    if ($mode === 'urgent') {
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        $price  = (float)($_POST['unit_price'] ?? 0);
        $pdate  = trim($_POST['purchase_date'] ?? today());
        $pay    = ($_POST['payment_status'] ?? 'pending') === 'paid' ? 'paid' : 'pending';
        $note   = trim($_POST['announcement_note'] ?? '');
        $total  = $qty * $price;
        if (!$supplier_id)       { $errors[] = 'Select a supplier.'; }
        if ($price <= 0)         { $errors[] = 'Unit price must be positive.'; }
        if ($pdate === '')       { $pdate = today(); }
        if (count($errors) === 0) {
            $ok = exec_sql("INSERT INTO purchases
                    (purchase_no, request_id, supplier_id, quantity, unit_price, total_cost, purchase_date,
                     urgency, payment_status, status, approved_by, announcement_note)
                    VALUES ('" . esc($purchase_no) . "', $rid, $supplier_id, $qty, $price, $total, '$pdate',
                            'urgent', '$pay', 'ordered', " . (int)$user['id'] . ", '" . esc($note) . "')");
            if ($ok) {
                exec_sql("UPDATE procurement_requests SET status='purchased' WHERE id=$rid");
                flash_set('success', 'Urgent purchase ' . $purchase_no . ' approved and ordered.');
                redirect_to($base . '/purchases/view.php?id=' . inserted_id());
            }
            $errors[] = last_error();
        }
    } elseif ($mode === 'normal') {
        $note = trim($_POST['announcement_note'] ?? '');
        $ok = exec_sql("INSERT INTO purchases
                (purchase_no, request_id, quantity, purchase_date, urgency, status, announcement_note)
                VALUES ('" . esc($purchase_no) . "', $rid, $qty, '" . today() . "', 'normal', 'quotation', '" . esc($note) . "')");
        if ($ok) {
            exec_sql("UPDATE procurement_requests SET status='quotation_pending' WHERE id=$rid");
            flash_set('success', 'Purchase ' . $purchase_no . ' opened. Add at least 3 quotations, then submit to the committee.');
            redirect_to($base . '/purchases/view.php?id=' . inserted_id());
        }
        $errors[] = last_error();
    } else {
        $errors[] = 'Choose how to proceed (urgent or normal).';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-cart-plus me-2"></i>Purchase For <?php echo h($r['request_no']); ?></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3"><strong>Department</strong><br><?php echo h($r['dept']); ?></div>
      <div class="col-md-3"><strong>Item</strong><br><?php echo h($r['item_name']); ?></div>
      <div class="col-md-2"><strong>Quantity</strong><br><?php echo xnum($r['quantity']); ?> <?php echo h($r['unit']); ?></div>
      <div class="col-md-2"><strong>Request Urgency</strong><br><?php echo badge($r['urgency']); ?></div>
      <div class="col-md-2"><strong>Direct delivery</strong><br><?php echo $r['direct_delivery'] ? 'Yes' : 'No'; ?></div>
    </div>
    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger mt-2 py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card border-danger-subtle h-100">
      <div class="card-header bg-danger-subtle text-danger-emphasis">
        <i class="bi bi-lightning-charge me-1"></i>URGENT - Immediate Approval &amp; Purchase
        <?php if ($r['urgency'] === 'urgent'): ?><span class="badge text-bg-danger ms-1">recommended for this request</span><?php endif; ?>
      </div>
      <div class="card-body small">
        For urgent needs: pick the supplier now, set the price and the purchase is
        <strong>approved immediately</strong> (budget approval included). No quotations or committee step.
        <form method="post">
          <input type="hidden" name="mode" value="urgent">
          <div class="row g-2">
            <div class="col-12"><label class="form-label required">Supplier</label>
              <select name="supplier_id" class="form-select">
                <option value="0">-- select supplier --</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo h($s['name']); ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-md-6"><label class="form-label required">Unit Price (PKR)</label>
              <input type="number" name="unit_price" min="0.01" step="any" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label required">Purchase Date</label>
              <input type="date" name="purchase_date" class="form-control" required value="<?php echo today(); ?>"></div>
            <div class="col-md-6"><label class="form-label">Payment</label>
              <select name="payment_status" class="form-select">
                <option value="pending">Pending</option><option value="paid">Paid</option>
              </select></div>
            <div class="col-md-6"><label class="form-label">Delivery note</label>
              <input type="text" name="announcement_note" class="form-control" placeholder="Optional"></div>
          </div>
          <hr>
          <button class="btn btn-danger w-100" type="submit"><i class="bi bi-lightning-charge me-1"></i>Approve &amp; Place Order</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-primary-subtle h-100">
      <div class="card-header bg-primary-subtle text-primary-emphasis">
        <i class="bi bi-tags me-1"></i>NORMAL - Quotations &amp; Committee Approval
      </div>
      <div class="card-body small">
        <ul class="mb-2">
          <li>Get quotations from <strong>at least 3 suppliers</strong></li>
          <li>Submit to the committee (Finance + Management + Procurement)</li>
          <li>After approval, select the final supplier and place the order</li>
        </ul>
        <form method="post">
          <input type="hidden" name="mode" value="normal">
          <div class="mb-2"><label class="form-label">Public announcement (for bulk purchases, optional)</label>
            <input type="text" name="announcement_note" class="form-control" placeholder="e.g. Public tender published on site notice board"></div>
          <hr>
          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-tags me-1"></i>Open Purchase &amp; Collect Quotations</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>