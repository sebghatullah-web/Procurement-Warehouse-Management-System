</main>
  </div>
  <aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
      <i class="bi bi-box-seam fs-4"></i>
      <span class="ms-2 fw-semibold">Khawar <span class="text-warning">PWMS</span></span>
    </div>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="sidebar-foot small text-light-emphasis">&copy; <?php echo date('Y'); ?> شرکت انکشافی خاور</div>
  </aside>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.PWMS_BASE = <?php echo json_encode(BASE_URL); ?>;</script>
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>