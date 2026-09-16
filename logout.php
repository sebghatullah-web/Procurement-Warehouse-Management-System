<?php
/** Simple sign-out. */
require_once __DIR__ . '/includes/auth.php';
logout_user();
flash_set('info', 'از سیستم خارج شدید.');
redirect_to(BASE_URL . '/login.php');