<?php
/** Simple sign-out. */
require_once __DIR__ . '/includes/auth.php';
logout_user();
flash_set('info', 'You have been signed out.');
redirect_to(BASE_URL . '/login.php');