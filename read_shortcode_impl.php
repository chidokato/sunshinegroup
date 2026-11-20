<?php
$f = 'C:/xampp/htdocs/www/kenhbds/wp-content/plugins/kenhbds-data-manager/includes/class-kbds-shortcodes.php';
$c = file_get_contents($f);

$pos1 = strpos($c, 'function shortcode_lead_form');
echo "=== LEAD FORM ===\n";
echo substr($c, $pos1, 1500) . "\n";

$pos2 = strpos($c, 'function shortcode_project_news');
echo "=== PROJECT NEWS ===\n";
echo substr($c, $pos2, 1800) . "\n";
