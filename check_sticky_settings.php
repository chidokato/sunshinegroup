<?php
require_once __DIR__ . '/wp-load.php';

echo "=== CHILD THEME MODS ===\n";
$child_mods = get_option('theme_mods_flatsome-child', []);
foreach ($child_mods as $k => $v) {
    if (stripos($k, 'sticky') !== false || stripos($k, 'header') !== false) {
        echo "$k => " . (is_array($v) ? json_encode($v) : $v) . "\n";
    }
}

echo "\n=== PARENT THEME MODS ===\n";
$parent_mods = get_option('theme_mods_flatsome', []);
foreach ($parent_mods as $k => $v) {
    if (stripos($k, 'sticky') !== false || stripos($k, 'header') !== false) {
        echo "$k => " . (is_array($v) ? json_encode($v) : $v) . "\n";
    }
}

// Check how flatsome generates header classes
$header_classes = flatsome_header_classes();
echo "\nFlatsome header classes function output: " . implode(' ', $header_classes) . "\n";

unlink(__FILE__);
