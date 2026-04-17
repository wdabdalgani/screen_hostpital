<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (admin_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
} else {
    header('Location: ' . url('admin/login.php'));
}
exit;
