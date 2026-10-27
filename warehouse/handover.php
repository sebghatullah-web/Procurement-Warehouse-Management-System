<?php
/**
 * Handover slip (فورم تسلیمی) - printable + requester confirmation.
 *
 * Created by the warehouse manager after gate receipt (see disposition.php).
 * The receiving employee (درخواست‌کننده) opens this page and confirms that
 * they actually received the requested item(s) - receiver_confirmed=1.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'فورم تسلیمی';
$base = BASE_URL;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_set('danger', 'فورم تسلیمی معتبر نیست.'); redirect_to($base . '/index.php'); }

$c = fetch_one("SELECT c.*, r.request_no, r.request_date, r.requested_by,
                       r.employee_name AS req_employee, r.employee_position AS req_position,
                       d.name dept, p.purchase_no, p.total_cost, p.currency, p.supplier_id,
                       s.name supplier, w.name AS stock_name, w.location AS stock_location,
                       u.name AS delivered_by_name
                FROM consumptions c
                LEFT JOIN procurement_requests r ON r.id=c.request_id
                JOIN departments d ON d.id=c.department_id
                LEFT JOIN purchases p ON p.id=c.purchase_id
                LEFT JOIN suppliers s ON s.id=p.supplier_id
                LEFT JOIN warehouse_items w ON w.id=c.item_id
                LEFT JOIN users u ON u.id=c.delivered_by
                WHERE c.id=$id");
if (!$c) { flash_set('danger', 'فورم تسلیمی یافت نشد.'); redirect_to($base . '/index.php'); }

$isManager   = in_array($user['role'], ['warehouse_manager','admin','general_manager'], true);
$isRequester = $c['requested_by'] && (int)$c['requested_by'] === (int)$user['id'];
$canConfirm  = !$c['receiver_confirmed'] && ($isManager || $isRequester);
$isConfirmed = (int)$c['receiver_confirmed'] === 1;

$errors = [];
if (is_post() && (($_POST['action'] ?? '') === 'confirm') && $canConfirm) {
    if (!isset($_POST['agree'])) {
        $errors[] = 'برای تأیید تسلیمی باید باکس «تأیید می‌کنم» را انتخاب کنید.';
    } else {
        $ok = exec_sql("UPDATE consumptions
                        SET receiver_confirmed=1, confirmed_at=NOW(),
                            receiver_user_id = IFNULL(receiver_user_id, " . (int)$user['id'] . ")
                        WHERE id=$id");
        if ($ok) {
            flash_set('success', 'تسلیمی تأیید شد. متشکریم!');
            redirect_to($base . '/warehouse/handover.php?id=' . $id);
        }
        $errors[] = last_error();
    }
}
if (is_post() && (($_POST['action'] ?? '') === 'confirm') && !$canConfirm && !isset($_POST['agree'])) {
    $errors[] = 'تسلیمی قبلاً تأیید شده است یا شما صاحب درخواست نیستید.';
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
@media print {
  .app-topbar, .app-sidebar, .no-print { display: none !important; }
  .card { border: 1px solid #000 !important; box-shadow: none !important; }
  body { background: #fff !important; }
}
</style>

<div class="d-flex gap-2 mb-3 no-print">
  <button class="btn btn-outline-dark" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>چاپ</button>
  <?php if ($c['purchase_id']): ?>
  <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/purchases/view.php?id=<?php echo (int)$c['purchase_id']; ?>"><i class="bi bi-arrow-right me-1"></i>خرید مربوطه</a>
  <?php endif; ?>
  <a class="btn btn-outline-secondary" href="javascript:history.back()"><i class="bi bi-arrow-right me-1"></i>بازگشت</a>
</div>

<div class="card border-dark shadow-sm">
  <div class="card-header bg-dark text-white text-center py-3">
    <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>فورم تسلیمی (تحویل کالا)</h4>
    <div class="small">شرکت انکشافی خاور &middot; بخش تدارکات و گدام</div>
  </div>
  <div class="card-body">
<div class="row mb-3">
      <div class="col-md-4"><strong>شماره فورم:</strong><br>
        <span class="fs-5 fw-bold"><?php echo h($c['handover_no'] ?? 'HND-…'); ?></span></div>
      <div class="col-md-4"><strong>تاریخ تحویل:</strong><br><?php echo h($c['delivery_date']); ?></div>
      <div class="col-md-4"><strong>وضعیت تسلیمی:</strong><br>
        <?php if ($isConfirmed): ?>
          <span class="badge text-bg-success fs-6"><i class="bi bi-check2-circle me-1"></i>تأیید شده در <?php echo h($c['confirmed_at']); ?></span>
        <?php else: ?>
          <span class="badge text-bg-warning fs-6">در انتظار تأیید درخواست‌کننده</span>
        <?php endif; ?>
      </div>
    </div>

    <table class="table table-bordered align-middle mb-3">
      <tbody>
        <tr><th class="w-25">کالا</th><td><?php echo h($c['item_name']); ?>
            <?php if ($c['stock_name'] && $c['stock_name'] !== $c['item_name']): ?><span class="text-muted small">(نام در انبار: <?php echo h($c['stock_name']); ?>)</span><?php endif; ?></td>
            <th class="w-25">تعداد</th><td class="text-nowrap"><?php echo xnum($c['quantity']); ?> <?php echo h(unit_label($c['unit'])); ?></td></tr>
        <tr><th>اداره</th><td><?php echo h($c['dept']); ?></td>
            <th>نوع تحویل</th><td><?php echo badge($c['source']); ?></td></tr>
        <tr><th>شماره درخواست</th><td><?php echo h($c['request_no'] ?? '—'); ?></td>
            <th>تاریخ درخواست</th><td><?php echo h($c['request_date'] ?? '—'); ?></td></tr>
        <tr><th>شماره سفارش</th><td><?php echo h($c['purchase_no'] ?? '—'); ?></td>
            <th>تأمین‌کننده</th><td><?php echo h($c['supplier'] ?? '—'); ?></td></tr>
        <tr><th>موقعیت در انبار</th><td><?php echo h($c['stock_location'] ?? '—'); ?></td>
            <th>مجموع قیمت</th><td><?php echo $c['total_cost'] ? money_cur($c['total_cost'], $c['currency']) : '—'; ?></td></tr>
      </tbody>
    </table>
<div class="row mb-4">
      <div class="col-md-6 border rounded p-3">
        <strong>تحویل داده شد توسط (گدام):</strong><br>
        <?php echo h($c['delivered_by_name'] ?? '—'); ?><br>
        <span class="text-muted small">امضا</span>
        <div class="mt-3" style="border-bottom:1px solid #999"></div>
      </div>
      <div class="col-md-6 border rounded p-3">
        <strong>دریافت شد توسط (درخواست‌کننده):</strong><br>
        <?php echo h($c['receiver_name'] ?? '—'); ?>
        <?php if ($c['receiver_position']): ?><span class="text-muted small">(<?php echo h($c['receiver_position']); ?>)</span><?php endif; ?><br>
        <span class="text-muted small">امضا و تاریخ</span>
        <div class="mt-3" style="border-bottom:1px solid #999"></div>
        <?php if ($isConfirmed && $c['confirmed_at']): ?>
        <div class="small text-success mt-1"><i class="bi bi-check2-circle me-1"></i>تأیید الکترونیکی: <?php echo h($c['confirmed_at']); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($c['handover_note']): ?>
    <div class="alert alert-light border mb-3"><strong>یادداشت تحویل:</strong> <?php echo h($c['handover_note']); ?></div>
    <?php endif; ?>

    <?php if (count($errors) > 0): ?>
    <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
<?php if ($canConfirm): ?>
    <div class="card border-success mb-3 no-print">
      <div class="card-header bg-success-subtle text-success-emphasis"><i class="bi bi-pen me-2"></i>تأیید تسلیمی توسط درخواست‌کننده</div>
      <div class="card-body">
        <p class="small mb-2">من به حیث درخواست‌کننده تصدیق می‌کنم که اقلام مشخص‌شده در این فورم را <strong>دریافت کرده‌ام</strong>.</p>
        <form method="post">
          <input type="hidden" name="action" value="confirm">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="agree" id="agree" value="1">
            <label class="form-check-label" for="agree">تأیید می‌کنم که درخواست مورد ضرورت را گرفتم.</label>
          </div>
          <button class="btn btn-success" type="submit"><i class="bi bi-check2-circle me-1"></i>ثبت تأیید تسلیمی</button>
        </form>
      </div>
    </div>
    <?php elseif ($isConfirmed): ?>
    <div class="alert alert-success no-print"><i class="bi bi-check2-circle me-1"></i>این فورم توسط درخواست‌کننده تأیید شده است.</div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>