<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
admin_logout();
header('Location: ' . url('admin/login.php'));
exit;
