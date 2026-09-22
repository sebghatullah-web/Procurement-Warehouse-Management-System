<?php
/**
 * Sign-in page (standalone layout - no sidebar).
 */
require_once __DIR__ . '/includes/auth.php';

if (current_user()) { redirect_to(BASE_URL . '/index.php'); }

$base = BASE_URL;
$err  = '';

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $u = login_user($username, $password);
    if ($u) {
        flash_set('success', 'Welcome back, ' . $u['name'] . '!');
        redirect_to($base . '/index.php');
    }
    $err = 'Invalid username or password (or the account is disabled).';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود &middot; <?php echo APP_SHORT; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
<style>
  body { min-height: 100vh; display:flex; align-items:center; justify-content:center;
         background: linear-gradient(135deg,#1c2f4f 0%, #2f4a75 55%, #16233a 100%);
         font-family: 'Vazirmatn', 'Segoe UI', Tahoma, Arial, sans-serif; }
  .login-card { width: 100%; max-width: 430px; border:0; border-radius: .9rem; box-shadow: 0 1rem 2rem rgba(0,0,0,.35); }
</style>
</head>
<body>
<div class="w-100 d-flex justify-content-center">
  <div class="card login-card text-bg-light">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <i class="bi bi-box-seam" style="font-size:2.6rem;color:#e9a13c;"></i>
        <h4 class="mb-1"><?php echo APP_NAME; ?></h4>
        <div class="text-muted small">شرکت ساختمانی خاور &middot; افغانستان</div>
      </div>
      <?php if ($err !== ''): ?>
      <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-2"></i><?php echo h($err); ?></div>
      <?php endif; ?>
      <form method="post" class="needs-validation" novalidate>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-person-circle me-1"></i>نام کاربری</label>
          <input type="text" name="username" class="form-control" required autofocus autocomplete="username">
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-key me-1"></i>رمز عبور</label>
          <input type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary w-100 fw-semibold py-2" type="submit"><i class="bi bi-box-arrow-in-right me-2"></i>ورود</button>
      </form>
    </div>
    <div class="card-footer bg-light small p-2">
      <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#demoCreds">
        <i class="bi bi-people me-1"></i> حساب‌های نمایشی
      </button>
      <div class="collapse mt-1" id="demoCreds">
        <table class="table table-sm mb-0 small">
          <thead><tr><th>نقش</th><th>نام کاربری</th><th>رمز عبور</th></tr></thead>
          <tbody>
            <tr><td>مدیر خرید</td><td>procurement</td><td>password</td></tr>
            <tr><td>مدیر انبار</td><td>warehouse</td><td>password</td></tr>
            <tr><td>امنیت گیت</td><td>gate</td><td>password</td></tr>
            <tr><td>کمیته</td><td>committee</td><td>password</td></tr>
            <tr><td>مدیر عمومی</td><td>gm</td><td>password</td></tr>
            <tr><td>کارمند</td><td>employee</td><td>password</td></tr>
            <tr><td>مدیر سیستم</td><td>admin</td><td>admin123</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>