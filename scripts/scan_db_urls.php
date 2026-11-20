<?php
require_once __DIR__ . '/../wp-load.php';
global $wpdb;

$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
echo "Scanning tables for 'localhost/www/Sunshinegroup'...\n";

foreach ($tables as $t) {
    // Get text columns
    $cols = $wpdb->get_results("SHOW COLUMNS FROM `{$t}`");
    $match_count = 0;
    foreach ($cols as $c) {
        $type = strtolower($c->Type);
        if (strpos($type, 'char') !== false || strpos($type, 'text') !== false || strpos($type, 'blob') !== false) {
            $col_name = $c->Field;
            $cnt = $wpdb->get_var("SELECT COUNT(*) FROM `{$t}` WHERE `{$col_name}` LIKE '%localhost/www/Sunshinegroup%'");
            $match_count += intval($cnt);
        }
    }
    if ($match_count > 0) {
        echo "Table: {$t} => {$match_count} rows matching\n";
    }
}
