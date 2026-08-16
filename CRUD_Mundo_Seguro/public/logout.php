<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';

if (is_logged_in()) {
    audit('Logout realizado.');
}
logout_user();
redirect('index.php');
