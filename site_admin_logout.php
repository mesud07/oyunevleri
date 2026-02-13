<?php
require_once __DIR__ . '/includes/config.php';

unset($_SESSION['site_admin_giris'], $_SESSION['site_admin']);
header('Location: site_admin_login.php');
exit;
