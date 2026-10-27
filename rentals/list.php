<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('procurement_manager', 'warehouse_manager', 'finance', 'general_manager', 'admin');
$page_title = 'مدیریت کرایه تجهیزات';
$base = BASE_URL;
$role = $user['role'];

$rows = fetch_all("SELECT rc.*, pr.request_no, d.name department
                   FROM rental_contracts rc
                   JOIN procurement_requests pr ON pr.id=rc.request_id
                   JOIN departments d ON d.id=pr.department_id
                   ORDER BY FIELD(rc.status,'active','scheduled','returned'), rc.planned_end_date, rc.id DESC");
$queue = fetch_all("SELECT pr.id, pr.request_no, pr.item_name, pr.quantity, pr.needed_date,
                           pr.rental_duration, pr.rental_duration_unit, d.name department
                    FROM procurement_requests pr
                    JOIN departments d ON d.id=pr.department_id
                    LEFT JOIN rental_contracts rc ON rc.request_id=pr.id
                    WHERE pr.request_type='rental' AND pr.status='rental_required' AND rc.id IS NULL
                    ORDER BY pr.needed_date, pr.id");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if (count($queue)): ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-hourglass-split me-2"></i>درخواست‌های تأییدشده و آماده قرارداد</div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>شماره درخواست</th><th>وسیله</th><th>تعداد</th><th>اداره / پروژه</th><th>آغاز مورد نیاز</th><th></th></tr></thead>
      <tbody><?php foreach ($queue as $r): ?>
        <tr>
          <td><?php echo h($r['request_no']); ?></td>
          <td><?php echo h($r['item_name']); ?></td>
          <td><?php echo h($r['quantity']); ?></td>
          <td><?php echo h($r['department']); ?></td>
          <td><?php echo h($r['needed_date'] ?? '—'); ?></td>
          <td><a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/rentals/create.php?request_id=<?php echo (int)$r['id']; ?>">ثبت قرارداد</a></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><i class="bi bi-truck me-2"></i>قراردادهای کرایه <span class="text-muted small">(<?php echo count($rows); ?>)</span></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>شماره قرارداد</th><th>وسیله</th><th>تأمین‌کننده</th><th>اداره / پروژه</th><th>مدت</th><th>کرایه</th><th>وضعیت</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php $late = $r['status'] !== 'returned' && $r['planned_end_date'] < today(); ?>
        <tr class="<?php echo $late ? 'table-warning' : ''; ?>">
          <td><a href="<?php echo $base; ?>/rentals/view.php?id=<?php echo (int)$r['id']; ?>"><?php echo h($r['rental_no']); ?></a></td>
          <td><?php echo h($r['equipment_name']); ?><?php if ((float)$r['quantity'] > 1): ?> <span class="text-muted">× <?php echo xnum($r['quantity']); ?></span><?php endif; ?></td>
          <td><?php echo h($r['supplier_name']); ?></td>
          <td><?php echo h($r['department']); ?><?php if ($r['rental_location']): ?><div class="small text-muted"><?php echo h($r['rental_location']); ?></div><?php endif; ?></td>
          <td class="text-nowrap"><?php echo h($r['start_date']); ?> — <?php echo h($r['actual_return_date'] ?: $r['planned_end_date']); ?></td>
          <td class="text-nowrap"><?php echo money_cur($r['rate_amount'], $r['currency']); ?> / <?php echo h(['hour'=>'ساعت','day'=>'روز','week'=>'هفته','month'=>'ماه'][$r['rate_unit']]); ?></td>
          <td><?php echo $late ? '<span class="badge text-bg-warning">از موعد گذشته</span> ' : ''; ?><?php echo badge($r['status']); ?></td>
          <td><a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/rentals/view.php?id=<?php echo (int)$r['id']; ?>">جزئیات</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!count($rows)): ?><tr><td colspan="8" class="text-center text-muted py-4">هنوز قراردادی ثبت نشده است.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
