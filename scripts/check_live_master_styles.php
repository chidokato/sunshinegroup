<?php
$html = file_get_contents('https://sunshine-group.com.vn/du-an/handico-sunshine-new-ha-noi/');
$pos = strpos($html, 'THANH HEADER CUỘN STICKY');
if ($pos !== false) {
    echo substr($html, $pos, 1000) . "\n";
}
