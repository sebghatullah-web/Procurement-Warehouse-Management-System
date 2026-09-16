<?php
/**
 * Shared page header / layout opener.
 * Expects $user (current user array) and $page_title set by the page.
 */
require_once __DIR__ . '/auth.php';
$me = $user ?? current_user();
$page_title = $page_title ?? 'Dashboard';
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($page_title); ?> &middot; <?php echo APP_SHORT; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
      <i class="bi bi-box-seam fs-4"></i>
      <span class="ms-2 fw-semibold">Khawar <span class="text-warning">PWMS</span></span>
    </div>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="sidebar-foot small text-light-emphasis">&copy; <?php echo date('Y'); ?> شرکت انکشافی خاور</div>
  </aside>
  <div class="app-main">
    <header class="app-topbar">
      <div class="d-flex align-items-center text-light gap-2">
        <button class="btn btn-sm btn-outline-light" type="button" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div class="topbar-title"><?php echo h($page_title); ?></div>
      </div>
      <div class="d-flex align-items-center text-light gap-2">
        <span class="badge text-bg-dark rounded-pill"><i class="bi bi-person-badge ms-1"></i><?php echo h(role_label($me['role'])); ?></span>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle ms-1"></i><?php echo h($me['name']); ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><span class="dropdown-item-text small"><i class="bi bi-envelope ms-2"></i><?php echo h($me['email'] ?? 'no email'); ?></span></li>
            <li><span class="dropdown-item-text small"><i class="bi bi-buildings ms-2"></i><?php echo h($me['department_name'] ?? 'No department'); ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo $base; ?>/index.php"><i class="bi bi-speedometer2 ms-2"></i>داشبورد</a></li>
            <li><a class="dropdown-item text-danger" href="<?php echo $base; ?>/logout.php"><i class="bi bi-power ms-2"></i>خروج</a></li>
          </ul>
        </div>
      </div>
    </header>
    <main class="app-content container-fluid px-3 px-xl-4 pt-3">