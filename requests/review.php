<?php
/**
 * Committee coordinator assigns the request-specific fourth reviewer.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('committee', 'admin');
$page_title = 'ارجاع درخواست به کمیته';
$base = BASE_URL;

$id = (int)($_GET['id'] ?? 0);
$r = fetch_one("SELECT r.*, d.name dept, u.name requester
                FROM procurement_requests r
                JOIN departments d ON d.id=r.department_id
                JOIN users u ON u.id=r.requested_by
                WHERE r.id=$id");
if (!$r) {
    flash_set('danger', 'درخواست یافت نشد.');
    redirect_to($base . '/requests/committee_queue.php');
}
if ($r['status'] !== 'pending') {
    flash_set('warning', 'این درخواست در مرحله تعیین عضو کمیته نیست.');
    redirect_to($base . '/requests/view.php?id=' . $id);
}

$errors = [];
$selectedDepartment = (int)($_POST['department_id'] ?? 0);
$selectedUser = (int)($_POST['assigned_user_id'] ?? 0);
if (is_post()) {
    $department = fetch_one("SELECT id FROM departments WHERE id=$selectedDepartment");
    $assignee = fetch_one("SELECT id FROM users
                           WHERE id=$selectedUser AND department_id=$selectedDepartment AND status=1
                             AND role NOT IN ('procurement_manager','contracting_manager','finance')");
    if (!$department) { $errors[] = 'بخش چهارم کمیته را انتخاب کنید.'; }
    if (!$assignee) { $errors[] = 'یک کاربر فعال و غیر از مسئولان سه بخش ثابت را از بخش انتخاب‌شده تعیین کنید.'; }

    if (count($errors) === 0) {
        if (!$conn->begin_transaction()) {
            $errors[] = 'آغاز ثبت ارجاع ناموفق بود: ' . last_error();
        } else {
            $locked = fetch_one("SELECT status FROM procurement_requests WHERE id=$id FOR UPDATE");
            if (!$locked && $conn->errno) {
                $errors[] = 'خواندن وضعیت درخواست ناموفق بود: ' . last_error();
            } elseif (!$locked || $locked['status'] !== 'pending') {
                $errors[] = 'وضعیت درخواست تغییر کرده است؛ صفحه را تازه‌سازی کنید.';
            }
            $keys = ['procurement', 'contracting', 'finance'];
            foreach ($keys as $key) {
                if (count($errors) > 0) { break; }
                if (!exec_sql("INSERT INTO request_committee_approvals
                               (request_id, committee_key) VALUES ($id, '$key')")) {
                    $errors[] = 'ثبت اعضای کمیته ناموفق بود: ' . last_error();
                }
            }
            if (count($errors) === 0 &&
                !exec_sql("INSERT INTO request_committee_approvals
                           (request_id, committee_key, department_id, assigned_user_id)
                           VALUES ($id, 'department', $selectedDepartment, $selectedUser)")) {
                $errors[] = 'ثبت نماینده بخش چهارم ناموفق بود: ' . last_error();
            }
            if (count($errors) === 0 &&
                (!exec_sql("UPDATE procurement_requests SET status='committee_review'
                            WHERE id=$id AND status='pending'") || affected_rows() !== 1)) {
                $errors[] = 'انتقال درخواست به مرحله تأیید کمیته ناموفق بود.';
            }

            if (count($errors) === 0 && $conn->commit()) {
                flash_set('success', 'درخواست به چهار بخش کمیته ارجاع شد.');
                redirect_to($base . '/requests/view.php?id=' . $id);
            }
            if (count($errors) === 0) {
                $errors[] = 'ثبت نهایی ارجاع ناموفق بود: ' . last_error();
            }
            $conn->rollback();
        }
    }
}

$departments = fetch_all('SELECT id, name FROM departments ORDER BY name');
$users = fetch_all("SELECT id, name, department_id FROM users
                    WHERE status=1 AND department_id IS NOT NULL
                      AND role NOT IN ('procurement_manager','contracting_manager','finance')
                    ORDER BY name");
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-people me-2"></i>تعیین بخش چهارم کمیته - <?php echo h($r['request_no']); ?></div>
  <div class="card-body">
    <div class="row mb-3">
      <div class="col-md-3"><strong>اداره درخواست‌کننده</strong><br><?php echo h($r['dept']); ?></div>
      <div class="col-md-3"><strong>نوع درخواست</strong><br><?php echo ($r['request_type'] ?? 'purchase') === 'rental' ? 'کرایه' : 'خرید'; ?></div>
      <div class="col-md-3"><strong>کالا / وسیله</strong><br><?php echo h($r['item_name']); ?></div>
      <div class="col-md-3"><strong>درخواست‌کننده</strong><br><?php echo h($r['employee_name'] ?: $r['requester']); ?></div>
    </div>
    <p class="text-muted">پس از تعیین بخش و نماینده، درخواست برای تأیید جداگانه تدارکات، قراردادی، مالی و نماینده بخش انتخاب‌شده فرستاده می‌شود. فقط با تأیید هر چهار بخش درخواست به مرحله بعد می‌رود.</p>
    <?php if ($errors): ?>
      <div class="alert alert-danger py-2"><?php foreach ($errors as $error): ?><div><?php echo h($error); ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <form method="post" class="row g-3">
      <div class="col-md-6">
        <label class="form-label required" for="committeeDepartment">بخش چهارم</label>
        <select name="department_id" id="committeeDepartment" class="form-select" required>
          <option value="">-- انتخاب بخش --</option>
          <?php foreach ($departments as $department): ?>
          <option value="<?php echo (int)$department['id']; ?>" <?php echo $selectedDepartment === (int)$department['id'] ? 'selected' : ''; ?>><?php echo h($department['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label required" for="committeeAssignee">نماینده همان بخش</label>
        <select name="assigned_user_id" id="committeeAssignee" class="form-select" required>
          <option value="">-- انتخاب کاربر --</option>
          <?php foreach ($users as $candidate): ?>
          <option value="<?php echo (int)$candidate['id']; ?>" data-department="<?php echo (int)$candidate['department_id']; ?>" <?php echo $selectedUser === (int)$candidate['id'] ? 'selected' : ''; ?>><?php echo h($candidate['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>ارجاع برای تأیید چهار بخش</button>
        <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/requests/view.php?id=<?php echo $id; ?>">بازگشت</a>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var department = document.getElementById('committeeDepartment');
  var assignee = document.getElementById('committeeAssignee');
  function filterAssignees() {
    var selected = department.value;
    var currentIsAvailable = false;
    Array.prototype.forEach.call(assignee.options, function (option) {
      if (!option.value) { option.hidden = false; return; }
      option.hidden = !selected || option.dataset.department !== selected;
      if (!option.hidden && option.selected) currentIsAvailable = true;
    });
    if (!currentIsAvailable) assignee.value = '';
  }
  department.addEventListener('change', filterAssignees);
  filterAssignees();
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
