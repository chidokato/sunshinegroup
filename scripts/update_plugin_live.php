<?php
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
$cookie_file = __DIR__ . '/live_cookies.txt';

// 1. Get nonce
$ch = curl_init('https://sunshine-group.com.vn/wp-admin/plugin-editor.php?file=sunshine-project-manager/sunshine-project-manager.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
$html = curl_exec($ch);

if (!preg_match('/name="nonce" value="([^"]+)"/', $html, $m)) {
    die("Nonce not found\n");
}
$nonce = $m[1];
echo "Nonce: {$nonce}\n";

// 2. Read local content
$local_file = ABSPATH . 'wp-content/plugins/sunshine-project-manager/sunshine-project-manager.php';
$content = file_get_contents($local_file);
echo "Local file size: " . strlen($content) . " bytes\n";

// 3. Post update
$ch2 = curl_init('https://sunshine-group.com.vn/wp-admin/plugin-editor.php');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query([
    'action'           => 'update',
    'file'             => 'sunshine-project-manager/sunshine-project-manager.php',
    'plugin'           => 'sunshine-project-manager/sunshine-project-manager.php',
    'nonce'            => $nonce,
    '_wp_http_referer' => '/wp-admin/plugin-editor.php?file=sunshine-project-manager%2Fsunshine-project-manager.php',
    'newcontent'       => $content,
    'submit'           => 'Cập nhật tập tin'
]));
curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookie_file);
$res2 = curl_exec($ch2);
$info = curl_getinfo($ch2);
echo "HTTP response: " . $info['http_code'] . "\n";
if (strpos($res2, 'Tập tin đã được chỉnh sửa thành công') !== false || strpos($res2, 'File edited successfully') !== false) {
    echo "SUCCESS: File edited successfully on live server!\n";
} else {
    echo "Check response: " . substr(strip_tags($res2), 0, 300) . "\n";
}
