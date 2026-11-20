<?php
require_once __DIR__ . '/wp-load.php';

$html1 = do_shortcode('[blog_posts style="normal" columns="3" columns__md="1" cat="39,40" posts="6" image_height="56.25%"]');
file_put_contents('test_out1.html', $html1);

$html2 = do_shortcode('[blog_posts style="normal" columns="3" columns__md="1" cat="38" posts="3" image_height="56.25%"]');
file_put_contents('test_out2.html', $html2);

echo "Done rendering shortcodes.\n";
