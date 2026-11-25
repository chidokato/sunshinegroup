<?php
$urls = [
    'Pillar'    => 'https://sunshine-group.com.vn/du-an/noble-palace-long-bien/',
    'Vệ tinh 1' => 'https://sunshine-group.com.vn/danh-gia-vi-tri-du-an-noble-palace-long-bien/',
    'Vệ tinh 2' => 'https://sunshine-group.com.vn/kien-truc-tien-ich-dinh-thu-noble-palace-long-bien/'
];

foreach ($urls as $type => $url) {
    echo "=== KIỂM TRA {$type}: {$url} ===\n";
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $html = @file_get_contents($url, false, $ctx);
    if ($html) {
        echo "  - HTTP Status: 200 OK (Độ dài: " . strlen($html) . " bytes)\n";
        if (preg_match('/<title>(.*?)<\/title>/i', $html, $t)) {
            echo "  - Title: " . html_entity_decode($t[1]) . "\n";
        }
        if (preg_match('/<meta name="description" content="([^"]*)"/i', $html, $d)) {
            echo "  - Meta Desc: " . html_entity_decode($d[1]) . "\n";
        }
        // Kiểm tra Anchor Text trỏ về Pillar
        if (strpos($type, 'Vệ tinh') !== false) {
            if (preg_match('/<a[^>]+href="([^"]*noble-palace-long-bien[^"]*)"[^>]*>(.*?)<\/a>/i', $html, $link)) {
                echo "  - Anchor Text về Pillar: [{$link[2]}] -> {$link[1]}\n";
            } else {
                echo "  - Anchor Text về Pillar: CHƯA TÌM THẤY\n";
            }
        }
        if (strpos($html, 'kbds-project-subnav') !== false) {
            echo "  - Sticky Subnav: CÓ\n";
        }
        if (strpos($html, 'kbds-lead-card') !== false || strpos($html, 'kbds-lead-grid-form') !== false) {
            echo "  - Form Đăng ký Thấp Tầng: CÓ\n";
        }
    } else {
        echo "  - LỖI KẾT NỐI: $url\n";
    }
    echo "\n";
}
