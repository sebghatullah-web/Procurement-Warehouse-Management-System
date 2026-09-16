<?php
/**
 * Sidebar navigation - role based.
 * Uses: $me (current user), $base (BASE_URL), $role (current user role).
 */
$role = $me['role'] ?? ($user['role'] ?? 'employee');
$isA  = ($role === 'admin');
$base = BASE_URL;

function sidebar_link($href, $icon, $label, $sep = false)
{
    if ($sep) { echo '<div class="nav-title">' . h($label === '' ? $icon : _ucwords($label)) . '</div>'; }
    echo '<a class="nav-link" href="' . h($href) . '"><i class="bi ' . h($icon) . '"></i><span>' . h($label) . '</span></a>';
}
?>
<nav class="nav sidebar-nav flex-column">
  <div class="nav-title">Main</div>
  <a class="nav-link" href="<?php echo $base; ?>/index.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>

  <?php if ($role === 'employee' || $isA || $role === 'procurement_manager'): ?>
  <a class="nav-link" href="<?php echo $base; ?>/requests/create.php"><i class="bi bi-plus-square"></i><span>درخواست جدید</span></a>
  <?php endif; ?>

  <?php if ($role === 'employee' || $isA): ?>
  <a class="nav-link" href="<?php echo $base; ?>/requests/list.php?mine=1"><i class="bi bi-clipboard-plus"></i><span>درخواست‌های من</span></a>
  <?php endif; ?>

  <?php if (in_array($role, ['procurement_manager','warehouse_manager','general_manager','gate_security','committee','admin'], true)): ?>
  <div class="nav-title">درخواست‌ها</div>
  <a class="nav-link" href="<?php echo $base; ?>/requests/list.php"><i class="bi bi-inboxes"></i><span>همه درخواست‌ها</span></a>
  <?php endif; ?>

  <?php if ($role === 'procurement_manager' || $isA): ?>
  <a class="nav-link" href="<?php echo $base; ?>/requests/list.php?status=pending"><i class="bi bi-check2-circle"></i><span>صف بررسی</span></a>
  <?php endif; ?>

  <?php if ($role === 'procurement_manager' || $role === 'committee' || $isA): ?>
  <div class="nav-title">خرید و تدارکات</div>
  <a class="nav-link" href="<?php echo $base; ?>/purchases/list.php"><i class="bi bi-cart-check"></i><span>خریدها</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/purchases/committee.php"><i class="bi bi-people"></i><span>تصویب کمیته</span></a>
  <?php endif; ?>

  <?php if ($role === 'procurement_manager' || $isA): ?>
  <a class="nav-link" href="<?php echo $base; ?>/suppliers/list.php"><i class="bi bi-truck"></i><span>تأمین‌کنندگان</span></a>
  <?php endif; ?>

  <?php if ($role === 'warehouse_manager' || $isA): ?>
  <div class="nav-title">انبار</div>
  <a class="nav-link" href="<?php echo $base; ?>/warehouse/inventory.php"><i class="bi bi-boxes"></i><span>موجودی انبار</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/warehouse/categories.php"><i class="bi bi-tags"></i><span>دسته‌بندی‌ها</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/requests/list.php?status=warehouse_check"><i class="bi bi-upc-scan"></i><span>بررسی انبار</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/consumptions/list.php"><i class="bi bi-box-arrow-up"></i><span>تحویل و مصرف</span></a>
  <?php endif; ?>

  <?php if ($role === 'gate_security' || $isA): ?>
  <div class="nav-title">امنیت و گیت</div>
  <a class="nav-link" href="<?php echo $base; ?>/gate/receipts.php"><i class="bi bi-shield-check"></i><span>رسید گیت</span></a>
  <?php endif; ?>

  <?php if (in_array($role, ['general_manager','procurement_manager','warehouse_manager','admin'], true)): ?>
  <div class="nav-title">گزارش‌ها</div>
  <a class="nav-link" href="<?php echo $base; ?>/reports/index.php"><i class="bi bi-bar-chart"></i><span>مرکز گزارشات</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/reports/purchases.php"><i class="bi bi-currency-dollar"></i><span>گزارش خرید</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/reports/consumption.php"><i class="bi bi-pie-chart"></i><span>گزارش مصرف</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/reports/inventory.php"><i class="bi bi-archive"></i><span>موجودی کنونی</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/reports/cost_analysis.php"><i class="bi bi-graph-up"></i><span>تحلیل هزینه‌ها</span></a>
  <?php endif; ?>

  <?php if ($isA): ?>
  <div class="nav-title">مدیریت سیستم</div>
  <a class="nav-link" href="<?php echo $base; ?>/admin/users.php"><i class="bi bi-people"></i><span>کاربران</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/admin/departments.php"><i class="bi bi-buildings"></i><span>ادارات</span></a>
  <?php endif; ?>
</nav>