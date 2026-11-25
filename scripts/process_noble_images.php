<?php
require_once __DIR__ . '/../wp-load.php';

$upload_dir = wp_upload_dir()['basedir'] . '/2026/11';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

$uploaded_dir = 'C:/Users/Admin/.gemini/antigravity/brain/33bf4589-fdce-42c9-89cd-b5d358ac5d22/.user_uploaded';

$img_files = glob($uploaded_dir . '/*.{jpg,png,jpeg}', GLOB_BRACE);
// Filter only media_1795575139*
$target_files = [];
foreach ($img_files as $f) {
    if (strpos($f, '1795575139') !== false) {
        $target_files[] = $f;
    }
}

echo "Found " . count($target_files) . " uploaded files for Noble Palace Long Bien:\n";

$seo_names = [
    'phoi-canh-dinh-thu-noble-palace-long-bien.jpg',
    'be-boi-san-vuon-biet-thu-noble-palace-long-bien.jpg',
    'khong-gian-thu-gian-tai-gia-noble-palace-long-bien.jpg',
    'lounge-bar-thuong-luu-noble-palace-long-bien.jpg',
    'cong-vien-vom-kinh-chau-au-noble-palace-long-bien.jpg',
    'ho-boi-resort-trung-tam-noble-palace-long-bien.jpg',
    'khong-gian-song-vuong-gia-noble-palace-long-bien.jpg',
    'quang-truong-vuon-hoa-chau-au-noble-palace-long-bien.jpg',
    'dai-lo-thuong-mai-shophouse-noble-palace-long-bien.jpg',
    'canh-quan-xanh-kien-truc-co-dien-noble-palace-long-bien.jpg',
    'day-biet-thu-song-lap-noble-palace-long-bien.jpg',
    'thiet-ke-mat-tien-kien-truc-phap-noble-palace-long-bien.jpg',
    'biet-thu-don-lap-hoang-gia-noble-palace-long-bien.jpg',
    'chi-tiet-hoa-van-kien-truc-noble-palace-long-bien.jpg'
];

$processed_images = [];

foreach ($target_files as $idx => $src) {
    $seo_name = $seo_names[$idx] ?? ('noble-palace-long-bien-' . ($idx + 1) . '.jpg');
    $dest = $upload_dir . '/' . $seo_name;
    copy($src, $dest);
    
    $info = getimagesize($dest);
    $url = home_url('/wp-content/uploads/2026/11/' . $seo_name);
    
    $processed_images[] = [
        'local_path' => $dest,
        'rel_path'   => 'wp-content/uploads/2026/11/' . $seo_name,
        'url'        => $url,
        'live_url'   => 'https://sunshine-group.com.vn/wp-content/uploads/2026/11/' . $seo_name,
        'width'      => $info[0] ?? 0,
        'height'     => $info[1] ?? 0,
        'seo_name'   => $seo_name
    ];
    echo "  -> Copy: $seo_name ({$info[0]}x{$info[1]})\n";
}

file_put_contents(__DIR__ . '/noble_images.json', json_encode($processed_images, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\nSaved noble_images.json\n";
