<?php
require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/sync_to_live.php';

$sync = new SunshineLiveSync();
$images = json_decode(file_get_contents(__DIR__ . '/noble_images.json'), true);

echo "Bắt đầu upload " . count($images) . " ảnh Noble Palace Long Bien lên Live server...\n";

$uploaded_map = [];

foreach ($images as $img) {
    $local_path = $img['local_path'];
    $alt = str_replace(['-', '.jpg'], [' ', ''], $img['seo_name']);
    $alt = ucwords($alt);
    
    echo "  Upload {$img['seo_name']}... ";
    
    // Check if live has it
    $live_url = $img['live_url'];
    $headers = @get_headers($live_url);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "[ĐÃ TỒN TẠI TRÊN LIVE] $live_url\n";
        $uploaded_map[$img['rel_path']] = $live_url;
        continue;
    }
    
    $res = $sync->upload_media($local_path, $alt);
    if (!empty($res['source_url'])) {
        echo "[THÀNH CÔNG] => {$res['source_url']}\n";
        $uploaded_map[$img['rel_path']] = $res['source_url'];
    } elseif (!empty($res['body']['source_url'])) {
        echo "[THÀNH CÔNG BODY] => {$res['body']['source_url']}\n";
        $uploaded_map[$img['rel_path']] = $res['body']['source_url'];
    } else {
        echo "[LỖI UPLOAD] " . json_encode($res) . "\n";
        // Fallback live url
        $uploaded_map[$img['rel_path']] = $live_url;
    }
}

file_put_contents(__DIR__ . '/noble_uploaded_map.json', json_encode($uploaded_map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\nHoàn tất upload ảnh.\n";
