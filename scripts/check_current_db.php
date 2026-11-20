<?php
require_once __DIR__ . '/../wp-load.php';
global $wpdb;
$siteurl = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'siteurl'");
$home = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'home'");
echo "siteurl: $siteurl\n";
echo "home: $home\n";
