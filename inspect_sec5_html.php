<?php
require_once __DIR__ . '/wp-load.php';

$p = get_post(401);
$content = $p->post_content;

$pos = strpos($content, 'ham-do-xe');
if ($pos !== false) {
    echo "=== SECTION 5 HAM SIEU XE HTML ===\n";
    echo substr($content, $pos - 300, 1500) . "\n";
} else {
    echo "Not found with 'ham-do-xe', searching for 'siêu xe'...\n";
    $pos2 = strpos($content, 'siêu xe');
    echo substr($content, $pos2 - 300, 1500) . "\n";
}
