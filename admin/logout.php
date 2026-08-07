<?php
define('ADMIN_CONTEXT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

$_SESSION = [];
session_destroy();
redirect(base_path('/admin/login.php'));
