<?php
require_once __DIR__ . '/lib/auth.php';
do_admin_logout();
header('Location: admin_login.php');
exit;
