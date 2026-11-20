<?php
require_once __DIR__ . '/wp-load.php';

global $wpdb;
$tbl = $wpdb->prefix . 'rank_math_redirections';
$rows = $wpdb->get_results("SELECT * FROM $tbl LIMIT 20");
echo "Total rows: " . count($rows) . "\n";
print_r($rows);
