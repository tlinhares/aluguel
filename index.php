<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
