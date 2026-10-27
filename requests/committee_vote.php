<?php
/**
 * Record one committee department's decision on a request.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$base = BASE_URL;
$approvalId = (int)($_GET['approval_id'] ?? $_POST['approval_id'] ?? 0);
$approval = fetch_one("SELECT a.*, r.request_no, r.request_type, r.status request_status,
                              r.item_name, r.quantity, r.unit, r.reason, r.details, r.urgency,
                              r.needed_date, d.name request_department,
                              cd.name committee_department, assigned.name assigned_name
                       FROM request_committee_approvals a
                       JOIN procurement_requests r ON r.id=a.request_id
                       JOIN departments d ON d.id=r.department_id
                       LEFT JOIN departments cd ON cd.id=a.department_id
                       LEFT JOIN users assigned ON assigned.id=a.assigned_user_id
                       WHERE a.id=$approvalId");
if (!$approval) {
    flash_set('danger', 'تأیید کمیته یافت نشد.');
    redirect_to($base . '/requests/committee_queue.php');
}
if (!can_decide_request_committee($user, $approval)) {
    flash_set('danger', 'شما مسئول تأیید این بخش نیستید.');
    redirect_to($base . '/requests/view.php?id=' . (int)$approval['request_id']);
}
if ($approval['request_status'] !== 'committee_review' || $approval['decision'] !== 'pending') {
    flash_set('warning', 'این تأیید قبلاً ثبت شده یا درخواست دیگر در مرحله کمیته نیست.');
    redirect_to($base . '/requests/view.php?id=' . (int)$approval['request_id']);
}

$errors = [];
$note = trim($_POST['comment'] ?? '');
if (is_post()) {
    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['approve','reject'], true)) { $errors[] = 'عملیات نامعتبر است.'; }
    if ($action === 'reject' && $note === '') { $errors[] = 'برای رد درخواست، دلیل الزامی است.'; }

    if (!$errors) {
        if (!$conn->begin_transaction()) {
            $errors[] = 'آغاز ثبت تصمیم ناموفق بود: ' . last_error();
        } else {
            $requestId = (int)$approval['request_id'];
            $lockedRequest = fetch_one("SELECT status, request_type FROM procurement_requests WHERE id=$requestId FOR UPDATE");
            if (!$lockedRequest && $conn->errno) {
                $errors[] = 'خواندن وضعیت درخواست ناموفق بود: ' . last_error();
            }
            $lockedApproval = fetch_one("SELECT * FROM request_committee_approvals
                                         WHERE id=$approvalId AND request_id=$requestId FOR UPDATE");
            if (!$errors && !$lockedApproval && $conn->errno) {
                $errors[] = 'خواندن وضعیت تأیید ناموفق بود: ' . last_error();
            }
            if (!$errors && (!$lockedRequest || $lockedRequest['status'] !== 'committee_review' ||
                !$lockedApproval || $lockedApproval['decision'] !== 'pending' ||
                !can_decide_request_committee($user, $lockedApproval))) {
                $errors[] = 'وضعیت یا مسئولیت این تأیید تغییر کرده است؛ صفحه را تازه‌سازی کنید.';
            } elseif (!$errors) {
                $decision = $action === 'approve' ? 'approved' : 'rejected';
                $commentSql = $note === '' ? 'NULL' : "'" . esc($note) . "'";
                if (!exec_sql("UPDATE request_committee_approvals
                               SET decision='$decision', comment=$commentSql,
                                   decided_by=" . (int)$user['id'] . ", decided_at=NOW()
                               WHERE id=$approvalId AND decision='pending'") || affected_rows() !== 1) {
                    $errors[] = 'ثبت تصمیم بخش ناموفق بود: ' . last_error();
                }
            }

            if (!$errors && $action === 'reject') {
                if (!exec_sql("UPDATE procurement_requests
                               SET status='closed', reviewed_by=" . (int)$user['id'] . ",
                                   review_note='" . esc($note) . "', reviewed_at=NOW()
                               WHERE id=$requestId AND status='committee_review'") || affected_rows() !== 1) {
                    $errors[] = 'ثبت رد درخواست ناموفق بود: ' . last_error();
                }
            } elseif (!$errors) {
                $summary = fetch_one("SELECT COUNT(*) total,
                                             COALESCE(SUM(decision='approved'),0) approved,
                                             COALESCE(SUM(decision='rejected'),0) rejected
                                      FROM request_committee_approvals
                                      WHERE request_id=$requestId");
                if (!$summary && $conn->errno) {
                    $errors[] = 'خواندن وضعیت تأییدهای کمیته ناموفق بود: ' . last_error();
                } elseif ((int)$summary['rejected'] > 0) {
                    $errors[] = 'درخواست رد شده است و دیگر قابل انتقال نیست.';
                } elseif ((int)$summary['total'] === 4 && (int)$summary['approved'] === 4) {
                    $nextStatus = $lockedRequest['request_type'] === 'rental' ? 'rental_required' : 'warehouse_check';
                    if (!exec_sql("UPDATE procurement_requests
                                   SET status='$nextStatus', reviewed_by=" . (int)$user['id'] . ",
                                       review_note='تأیید همه چهار بخش کمیته', reviewed_at=NOW()
                                   WHERE id=$requestId AND status='committee_review'") || affected_rows() !== 1) {
                        $errors[] = 'انتقال درخواست پس از تکمیل تأییدها ناموفق بود: ' . last_error();
                    }
                }
            }

            if (!$errors && $conn->commit()) {
                if ($action === 'reject') {
                    flash_set('warning', 'درخواست به دلیل رد بخش شما بسته شد و به مرحله بعد نمی‌رود.');
                } elseif (isset($nextStatus)) {
                    flash_set('success', $lockedRequest['request_type'] === 'rental'
                        ? 'تأیید هر چهار بخش ثبت شد؛ درخواست به روند قبلی قرارداد کرایه رفت.'
                        : 'تأیید هر چهار بخش ثبت شد؛ درخواست برای بررسی موجودی به گدام فرستاده شد.');
                } else {
                    flash_set('success', 'تأیید بخش شما ثبت شد؛ درخواست تا تکمیل تأییدهای دیگر در کمیته می‌ماند.');
                }
                redirect_to($base . '/requests/view.php?id=' . (int)$approval['request_id']);
            }
            if (!$errors) { $errors[] = 'ثبت نهایی تصمیم ناموفق بود: ' . last_error(); }
            $conn->rollback();
        }
    }
}

$page_title = 'تصمیم بخش کمیته';
$committeeLabels = [
    'procurement' => 'تدارکات',
    'contracting' => 'قراردادی',
    'finance' => 'مالی',
    'department' => $approval['committee_department'] ?? 'بخش چهارم',
];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header"><i class="bi bi-check2-circle me-2"></i>تصمیم <?php echo h($committeeLabels[$approval['committee_key']]); ?> - <?php echo h($approval['request_no']); ?></div>
  <div class="card-body">
    <div class="row mb-3">
      <div class="col-md-3"><strong>اداره درخواست‌کننده</strong><br><?php echo h($approval['request_department']); ?></div>
      <div class="col-md-3"><strong>نوع</strong><br><?php echo $approval['request_type'] === 'rental' ? 'کرایه' : 'خرید'; ?></div>
      <div class="col-md-3"><strong>کالا / وسیله</strong><br><?php echo h($approval['item_name']); ?></div>
      <div class="col-md-3"><strong>مقدار</strong><br><?php echo h($approval['quantity']); ?> <?php echo h(unit_label($approval['unit'])); ?></div>
    </div>
    <div class="row mb-3">
      <div class="col-md-3"><strong>فوریت</strong><br><?php echo badge($approval['urgency']); ?></div>
      <div class="col-md-3"><strong>تاریخ مورد نیاز</strong><br><?php echo h($approval['needed_date'] ?? '—'); ?></div>
      <div class="col-md-6"><strong>دلیل درخواست</strong><br><?php echo h($approval['reason'] ?? '—'); ?></div>
    </div>
    <?php if ($approval['details']): ?>
    <div class="border rounded-2 bg-light-subtle p-2 mb-3"><strong>جزئیات:</strong><br><?php echo nl2br(h($approval['details'])); ?></div>
    <?php endif; ?>
    <?php if ($approval['committee_key'] === 'department'): ?>
    <p class="text-muted">نماینده تعیین‌شده: <?php echo h($approval['assigned_name'] ?? '—'); ?></p>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger py-2"><?php foreach ($errors as $error): ?><div><?php echo h($error); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="approval_id" value="<?php echo $approvalId; ?>">
      <div class="mb-3">
        <label class="form-label" for="committeeComment">یادداشت / دلیل</label>
        <textarea id="committeeComment" name="comment" rows="3" class="form-control" <?php echo is_post() && ($_POST['action'] ?? '') === 'reject' ? 'required' : ''; ?>><?php echo h($note); ?></textarea>
        <div class="form-text">در صورت رد، نوشتن دلیل الزامی است.</div>
      </div>
      <button class="btn btn-success" type="submit" name="action" value="approve"><i class="bi bi-check2 me-1"></i>تأیید این بخش</button>
      <button class="btn btn-outline-danger" type="submit" name="action" value="reject" onclick="return confirm('با رد این بخش، درخواست بسته و از ادامه روند جلوگیری می‌شود. ادامه می‌دهید؟');"><i class="bi bi-x-circle me-1"></i>رد درخواست</button>
      <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/requests/view.php?id=<?php echo (int)$approval['request_id']; ?>">بازگشت</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
