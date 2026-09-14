<?php
/**
 * Sidebar navigation - role based.
 * Uses: $me (current user), $base (BASE_URL), $r (role).
 */
$r   = $me['role'];
$isA = ($r === 'admin');
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

  <?php if ($r === 'employee' || $isA || $r === 'procurement_manager'): ?>
  <a class="nav-link" href="<?php echo $base; ?>/requests/create.php"><i class="bi bi-plus-square"></i><span>New Request</span></a>
  <?php endif; ?>

  <?php if ($r === 'employee' || $isA): ?>
  <a class="nav-link" href="<?php echo $base; ?>/requests/list.php?mine=1"><i class="bi bi-clipboard-plus"></i><span>My Requests</span></a>
  <?php endif; ?>

  <?php if (in_array($r, ['procurement_manager','warehouse_manager','general_manager','gate_security','committee','admin'], true)): ?>
  <div class="nav-title">Requests</div>
  <a class="nav-link" href="<?php echo $base; ?>/requests/list.php"><i class="bi bi-inboxes"></i><span>All Requests</span></a>
  <?php endif; ?>

  <?php if ($r === 'procurement_manager' || $isA): ?>
  <a class="nav-link" href="<?php echo $base; ?>/requests/list.php?status=pending"><i class="bi bi-check2-circle"></i><span>Review Queue</span></a>
  <?php endif; ?>

  <?php if ($r === 'procurement_manager' || $r === 'committee' || $isA): ?>
  <div class="nav-title">Procurement</div>
  <a class="nav-link" href="<?php echo $base; ?>/purchases/list.php"><i class="bi bi-cart-check"></i><span>Purchases</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/purchases/committee.php"><i class="bi bi-people"></i><span>Committee Approvals</span></a>
  <?php endif; ?>

  <?php if ($r === 'procurement_manager' || $isA): ?>
  <a class="nav-link" href="<?php echo $base; ?>/suppliers/list.php"><i class="bi bi-truck"></i><span>Suppliers</span></a>
  <?php endif; ?>

  <?php if ($r === 'warehouse_manager' || $isA): ?>
  <div class="nav-title">Warehouse</div>
  <a class="nav-link" href="<?php echo $base; ?>/warehouse/inventory.php"><i class="bi bi-boxes"></i><span>Inventory</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/warehouse/categories.php"><i class="bi bi-tags"></i><span>Categories</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/requests/list.php?status=warehouse_check"><i class="bi bi-upc-scan"></i><span>Warehouse Check</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/consumptions/list.php"><i class="bi bi-box-arrow-up"></i><span>Deliveries &amp; Usage</span></a>
  <?php endif; ?>

  <?php if ($r === 'gate_security' || $isA): ?>
  <div class="nav-title">Security</div>
  <a class="nav-link" href="<?php echo $base; ?>/gate/receipts.php"><i class="bi bi-shield-check"></i><span>Gate Receipts</span></a>
  <?php endif; ?>

  <?php if (in_array($r, ['general_manager','procurement_manager','warehouse_manager','admin'], true)): ?>
  <div class="nav-title">Reports</div>
  <a class="nav-link" href="<?php echo $base; ?>/reports/index.php"><i class="bi bi-bar-chart"></i><span>Report Center</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/reports/purchases.php"><i class="bi bi-currency-dollar"></i><span>Purchase Reports</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/reports/consumption.php"><i class="bi bi-pie-chart"></i><span>Consumption Reports</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/reports/inventory.php"><i class="bi bi-archive"></i><span>Current Inventory</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/reports/cost_analysis.php"><i class="bi bi-graph-up"></i><span>Cost Analysis</span></a>
  <?php endif; ?>

  <?php if ($isA): ?>
  <div class="nav-title">Administration</div>
  <a class="nav-link" href="<?php echo $base; ?>/admin/users.php"><i class="bi bi-people"></i><span>Users</span></a>
  <a class="nav-link" href="<?php echo $base; ?>/admin/departments.php"><i class="bi bi-buildings"></i><span>Departments</span></a>
  <?php endif; ?>
</nav>