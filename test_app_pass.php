<?php
require_once __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$available = wp_is_application_password_available();
echo "Is Application Password available on localhost? " . ($available ? "YES! [SUCCESS]" : "NO") . "\n";

unlink(__FILE__);
