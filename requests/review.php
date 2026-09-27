<?php
/**
 * Procurement review page: Approve (-> warehouse check) or Close (unnecessary).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'admin');
$page_title = 'بررسی درخواست';
$base = BASE_URL;

$id = (int)($_GET['id'] ?? 0);
$r = fetch_one("SELECT r.*, d.name dept, u.name requester
                FROM procurement_requests r
                JOIN departments d ON d.id=r.department_id
                JOIN users u ON u.id=r.requested_by
                WHERE r.id = $id");
if (!$r) {
    flash_set('danger', 'درخواست یافت نشد.');
    redirect_to($base . '/requests/list.php');
}
if ($r['status'] !== 'pending') {
    flash_set('warning', 'این درخواست قبلاً بررسی شده است (حالا: ' . req_status_label($r['status']) . ').');
    redirect_to($base . '/requests/view.php?id=' . $id);
}

$errors = [];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    $note   = trim($_POST['review_note'] ?? '');
    $valid  = ['approve', 'close'];
    if (!in_array($action, $valid, true)) { $errors[] = 'عملیات نامعتبر.'; }
    if (count($errors) === 0) {
        $to = ($action === 'approve') ? 'warehouse_check' : 'closed';
        $ok = exec_sql("UPDATE procurement_requests SET status='$to',
                        reviewed_by=" . (int)$user['id'] . ",
                        review_note='" . esc($note) . "', reviewed_at=NOW() WHERE id=$id");
        if ($ok) {
            flash_set('success', 'درخواست ' . $r['request_no'] . ' ' .
                      (($action === 'approve') ? 'تأیید شد - برای بررسی انبار ارسال شد' : 'بسته شد (غیرضروری اعلام شد)') . '.');
            redirect_to($base . '/requests/view.php?id=' . $id);
        }
        $errors[] = 'به‌روزرسانی ناموفق: ' . last_error();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-check2-circle me-2"></i>بررسی <?php echo h($r['request_no']); ?></div>
  <div class="card-body">
    <div class="row mb-2">
      <div class="col-md-3"><strong>اداره</strong><br><?php echo h($r['dept']); ?></div>
      <div class="col-md-3"><strong>کالا</strong><br><?php echo h($r['item_name']); ?></div>
      <div class="col-md-2"><strong>تعداد</strong><br><?php echo h($r['quantity']); ?> <?php echo h(unit_label($r['unit'])); ?></div>
      <div class="col-md-2"><strong>فوریت</strong><br><?php echo badge($r['urgency']); ?></div>
      <div class="col-md-2"><strong>کارمند</strong><br><?php echo h($r['employee_name'] ?? $r['requester']); ?></div>
    </div>
    <div class="small text-muted mb-3">
      <strong>دلیل:</strong> <?php echo h($r['reason'] ?? '—'); ?><br>
      <strong>تحویل مستقیم:</strong> <?php echo $r['direct_delivery'] ? 'بله' : 'خیر'; ?>
      <?php if ($r['employee_position']): ?><br><strong>موقعیت وظیفه‌ای:</strong> <?php echo h($r['employee_position']); ?><?php endif; ?>
      <?php if ($r['needed_date']): ?><br><strong>تاریخ مورد نیاز:</strong> <?php echo h($r['needed_date']); ?><?php endif; ?>
      <?php if (isset($r['details']) && $r['details'] !== ''): ?>
        <br><strong>جزئیات کالا:</strong><br>
        <div class="border rounded-2 p-2 mt-1 bg-light-subtle"><?php echo h($r['details']); ?></div>
      <?php endif; ?>
    </div>

    <?php if (count($errors) > 0): ?>
      <div class="alert alert-danger py-2"><?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="card border-success-subtle h-100">
          <div class="card-header text-success">تأیید - ضروری است</div>
          <div class="card-body small">
            درخواست را برای بررسی موجودی به <strong>مدیر انبار</strong> ارسال می‌کند.
            <form method="post">
              <input type="hidden" name="action" value="approve">
              <div class="mb-2"><textarea name="review_note" rows="2" class="form-control" placeholder="یادداشت اختیاری برای انبار (پیشنهاد)"></textarea></div>
              <button class="btn btn-success w-100" type="submit"><i class="bi bi-check2 me-1"></i>تأیید و ارسال به انبار</button>
            </form>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-danger-subtle h-100">
          <div class="card-header text-danger">بستن - غیرضروری</div>
          <div class="card-body small">
            درخواست را غیرضروری اعلام و روند را خاتمه می‌دهد. درخواست‌های بسته فقط‌خواندنی هستند.
            <form method="post">
              <input type="hidden" name="action" value="close">
              <div class="mb-2"><textarea name="review_note" rows="2" class="form-control" required placeholder="دلیل بستن (باید پر شود)"></textarea></div>
              <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-x-circle me-1"></i>بستن درخواست</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>