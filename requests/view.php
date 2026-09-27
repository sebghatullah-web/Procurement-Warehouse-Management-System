<?php
/**
 * Request detail page - full information + role-aware actions.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$page_title = 'جزئیات درخواست';
$base = BASE_URL;
$role = $user['role'];

$id  = (int)($_GET['id'] ?? 0);
$r = fetch_one("SELECT r.*, d.name dept, c.name cat, u.name requester, rv.name reviewer
                FROM procurement_requests r
                JOIN departments d ON d.id = r.department_id
                LEFT JOIN categories c ON c.id = r.category_id
                LEFT JOIN users u ON u.id = r.requested_by
                LEFT JOIN users rv ON rv.id = r.reviewed_by
                WHERE r.id = $id");
if (!$r) {
    flash_set('danger', 'درخواست یافت نشد.');
    redirect_to($base . '/requests/list.php');
}
/* employees may only open their own requests */
if ($role === 'employee' && (int)$r['requested_by'] !== (int)$user['id']) {
    flash_set('danger', 'شما فقط می‌توانید درخواست‌های خودتان را مشاهده کنید.');
    redirect_to($base . '/requests/list.php?mine=1');
}
$page_title = 'درخواست ' . $r['request_no'];

$purchases   = fetch_all("SELECT p.*, s.name supplier FROM purchases p
                          LEFT JOIN suppliers s ON s.id=p.supplier_id
                          WHERE p.request_id = $id ORDER BY p.id DESC");
$quotations  = fetch_all("SELECT q.*, s.name supplier FROM quotations q
                          JOIN suppliers s ON s.id=q.supplier_id
                          WHERE q.request_id = $id ORDER BY q.price ASC");
$consumptions = fetch_all("SELECT c.*, d.name dept FROM consumptions c
                           JOIN departments d ON d.id=c.department_id
                           WHERE c.request_id = $id ORDER BY c.id DESC");
$checklists  = fetch_all("SELECT g.*, p.purchase_no FROM gate_checklists g
                          JOIN purchases p ON p.id=g.purchase_id
                          WHERE p.request_id = $id ORDER BY g.check_date DESC");
$committee = fetch_all("SELECT ca.*, p.purchase_no FROM committee_approvals ca
                        JOIN purchases p ON p.id=ca.purchase_id
                        WHERE p.request_id = $id ORDER BY ca.approved_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clipboard-minus me-2"></i>درخواست <?php echo h($r['request_no']); ?></span>
        <?php echo badge($r['status']); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4"><div class="text-muted small">اداره</div><div class="fw-semibold"><?php echo h($r['dept']); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">دسته‌بندی</div><div class="fw-semibold"><?php echo h($r['cat'] ?? '—'); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">تاریخ درخواست</div><div class="fw-semibold"><?php echo h($r['request_date']); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">تاریخ مورد نیاز</div><div class="fw-semibold"><?php echo h($r['needed_date'] ?? '—'); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">کالا</div><div class="fw-semibold"><?php echo h($r['item_name']); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">تعداد</div><div class="fw-semibold"><?php echo h($r['quantity']); ?> <?php echo h(unit_label($r['unit'])); ?></div></div>
          <div class="col-md-4"><div class="text-muted small">فوریت</div><?php echo badge($r['urgency']); ?></div>
          <div class="col-md-4"><div class="text-muted small">کارمند / درخواست‌کننده</div><div class="fw-semibold"><?php echo h($r['employee_name'] ?? $r['requester']); ?><?php if ($r['employee_position']): ?> <span class="text-muted small">(<?php echo h($r['employee_position']); ?>)</span><?php endif; ?></div></div>
          <div class="col-md-4"><div class="text-muted small">تحویل مستقیم</div><div class="fw-semibold"><?php echo $r['direct_delivery'] ? 'بله' : 'خیر'; ?></div></div>
          <div class="col-md-4"><div class="text-muted small">بررسی‌کننده</div><div class="fw-semibold"><?php echo h($r['reviewer'] ?? '—'); ?></div></div>
        </div>
        <div class="mt-3">
          <div class="text-muted small">دلیل</div>
          <div><?php echo h($r['reason'] ?? '—'); ?></div>
        </div>
        <?php if (isset($r['details']) && $r['details'] !== ''): ?>
        <div class="mt-3">
          <div class="text-muted small">جزئیات کالا</div>
          <div><?php echo h($r['details']); ?></div>
        </div>
        <?php endif; ?>
        <?php if ($r['review_note']): ?>
        <div class="mt-3">
          <div class="text-muted small">یادداشت بررسی</div>
          <div class="alert alert-warning py-1 px-2 mb-1 small"><?php echo h($r['review_note']); ?>
            <span class="text-muted">(<?php echo h($r['reviewed_at'] ?? ''); ?>)</span></div>
        </div>
        <?php endif; ?>
        <?php if ($r['warehouse_note']): ?>
        <div class="mt-3">
          <div class="text-muted small">یادداشت انبار</div>
          <div class="alert alert-info py-1 px-2 mb-1 small"><?php echo h($r['warehouse_note']); ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">مرحله فعلی</div>
      <div class="card-body">
        <div class="fw-semibold mb-2"><?php echo badge($r['status']); ?></div>
        <ul class="small text-muted-2 mb-2 ps-3">
          <li>ثبت شده توسط <?php echo h($r['employee_name'] ?? $r['requester']); ?><?php if ($r['employee_position'] && $r['employee_position'] !== '—'): ?> (<?php echo h($r['employee_position']); ?>)<?php endif; ?></li>
          <?php if ($r['status'] !== 'pending' && $r['status'] !== 'closed'): ?>
          <li>بررسی و تأیید شده توسط تدارکات</li>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['purchase_required','quotation_pending','committee_pending','approved','purchased','received','completed'], true)): ?>
          <li>در انبار موجود نیست - فرآیند خرید شروع شد</li>
          <?php endif; ?>
          <?php if ($r['status'] === 'closed'): ?><li class="text-danger">بسته شده به‌عنوان غیرضروری</li><?php endif; ?>
          <?php if (in_array($r['status'], ['quotation_pending','committee_pending','approved','purchased','received','completed'], true)): ?>
          <li>قیمت‌ها جمع‌آوری شد؛ تأیید کمیته لازم است</li>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['purchased','received','completed'], true)): ?>
          <li>کالا از تأمین‌کننده نهایی سفارش داده شد</li>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['received','completed'], true)): ?>
          <li>در گیت دریافت و تأیید شد</li>
          <?php endif; ?>
          <?php if ($r['status'] === 'completed'): ?><li class="text-success"><strong>&#10003; تکمیل شد</strong></li><?php endif; ?>
        </ul>
<div class="border-top pt-2">
          <div class="text-muted small mb-1">عملیات</div>
          <?php $p0 = count($purchases) > 0 ? $purchases[0] : null; ?>
          <?php if ($r['status'] === 'pending' && ($role === 'procurement_manager' || $role === 'admin')): ?>
            <a class="btn btn-primary w-100" href="<?php echo $base; ?>/requests/review.php?id=<?php echo $r['id']; ?>"><i class="bi bi-check2-circle me-1"></i>بررسی و تصمیم</a>
          <?php elseif ($r['status'] === 'warehouse_check' && ($role === 'warehouse_manager' || $role === 'admin')): ?>
            <a class="btn btn-primary w-100" href="<?php echo $base; ?>/warehouse/check.php?request_id=<?php echo $r['id']; ?>"><i class="bi bi-upc-scan me-1"></i>بررسی انبار</a>
          <?php elseif ($r['status'] === 'purchase_required' && ($role === 'procurement_manager' || $role === 'admin')): ?>
            <a class="btn btn-primary w-100" href="<?php echo $base; ?>/purchases/create.php?request_id=<?php echo $r['id']; ?>"><i class="bi bi-cart-plus me-1"></i>شروع فرآیند خرید</a>
          <?php elseif ($p0 && in_array($r['status'], ['quotation_pending','committee_pending','approved','purchased','received','completed'], true)): ?>
            <a class="btn btn-outline-primary w-100" href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p0['id']; ?>"><i class="bi bi-cart-check me-1"></i>باز کردن خرید #<?php echo h($p0['purchase_no']); ?></a>
            <?php if ($r['status'] === 'purchased' && ($role === 'gate_security' || $role === 'admin')): ?>
              <a class="btn btn-outline-warning w-100 mt-2" href="<?php echo $base; ?>/gate/receipts.php?purchase_id=<?php echo $p0['id']; ?>"><i class="bi bi-shield-check me-1"></i>تکمیل چک‌لیست گیت</a>
            <?php endif; ?>
            <?php if ($r['status'] === 'received' && ($role === 'warehouse_manager' || $role === 'admin')): ?>
              <a class="btn btn-success w-100 mt-2" href="<?php echo $base; ?>/warehouse/disposition.php?purchase_id=<?php echo $p0['id']; ?>"><i class="bi bi-box-seam me-1"></i>تصمیم: ذخیره یا تحویل مستقیم</a>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-muted small">هیچ اقدام دیگری از شما لازم نیست.</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-cart-check me-2"></i>رکوردهای خرید</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>شماره سفارش</th><th>تأمین‌کننده</th><th>تعداد</th><th>قیمت واحد</th><th>مجموع</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
      <tbody>
      <?php foreach ($purchases as $p): ?>
        <tr><td><a href="<?php echo $base; ?>/purchases/view.php?id=<?php echo $p['id']; ?>"><?php echo h($p['purchase_no']); ?></a></td>
            <td><?php echo h($p['supplier'] ?? '—'); ?></td>
            <td><?php echo xnum($p['quantity']); ?></td>
            <td class="text-nowrap"><?php echo money_cur($p['unit_price'], $p['currency']); ?></td>
            <td class="text-nowrap fw-semibold"><?php echo money_cur($p['total_cost'], $p['currency']); ?></td>
            <td><?php echo badge($p['status']); ?></td>
            <td class="text-muted small text-nowrap"><?php echo h($p['purchase_date'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      <?php if (count($purchases) === 0): ?><tr><td colspan="7" class="text-center text-muted py-3">هنوز خریدی ثبت نشده است.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (count($quotations) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-tags me-2"></i>قیمت‌های تأمین‌کنندگان</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>تأمین‌کننده</th><th>قیمت واحد</th><th>ارز</th><th>زمان تحویل (روز)</th><th>یادداشت</th></tr></thead>
      <tbody>
      <?php foreach ($quotations as $qt): ?>
        <tr><td><?php echo h($qt['supplier']); ?></td>
            <td class="text-nowrap"><?php echo money_cur($qt['price'], $qt['currency']); ?></td>
            <td><?php echo cur_label($qt['currency']); ?></td>
            <td><?php echo h($qt['delivery_days'] ?? '—'); ?></td>
            <td class="text-muted small"><?php echo h($qt['notes'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (count($committee) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-people me-2"></i>تصویب‌های کمیته</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>عضو</th><th>نقش</th><th>تصمیم</th><th>نظر</th></tr></thead>
      <tbody>
      <?php foreach ($committee as $a): ?>
        <tr><td class="text-nowrap small text-muted"><?php echo h($a['approved_at']); ?></td>
            <td><?php echo h($a['member_name']); ?></td>
            <td class="text-muted small"><?php echo h($a['member_role'] ?? '—'); ?></td>
            <td><?php echo $a['decision'] === 'approved' ? '<span class="badge text-bg-success">تصویب شد</span>' : '<span class="badge text-bg-danger">رد شد</span>'; ?></td>
            <td class="text-muted small"><?php echo h($a['comment'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-box-arrow-up me-2"></i>تحویل‌ها / مصرف</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>اداره</th><th>کالا</th><th>تعداد</th><th>منبع</th></tr></thead>
      <tbody>
      <?php foreach ($consumptions as $c): ?>
        <tr><td class="text-nowrap"><?php echo h($c['delivery_date']); ?></td>
            <td><?php echo h($c['dept']); ?></td>
            <td><?php echo h($c['item_name']); ?></td>
            <td><?php echo xnum($c['quantity']); ?> <?php echo h(unit_label($c['unit'])); ?></td>
            <td><?php echo badge($c['source']); ?></td></tr>
      <?php endforeach; ?>
      <?php if (count($consumptions) === 0): ?><tr><td colspan="5" class="text-center text-muted py-3">هنوز تحویل داده نشده است.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (count($checklists) > 0): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-shield-check me-2"></i>چک‌های رسید گیت</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>تاریخ</th><th>سفارش</th><th>تعداد دریافت‌شده</th><th>وضعیت</th><th>یادداشت</th></tr></thead>
      <tbody>
      <?php foreach ($checklists as $g): ?>
        <tr><td class="text-nowrap small text-muted"><?php echo h($g['check_date']); ?></td>
            <td><?php echo h($g['purchase_no']); ?></td>
            <td><?php echo xnum($g['quantity_received']); ?></td>
            <td><?php echo $g['condition_ok'] ? '<span class="badge text-bg-success">سالم</span>' : '<span class="badge text-bg-danger">آسیب‌دیده / کم</span>'; ?></td>
            <td class="text-muted small"><?php echo h($g['remarks'] ?? '—'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<a class="btn btn-outline-secondary" href="javascript:history.back()"><i class="bi bi-arrow-right me-1"></i>بازگشت</a>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>