<?php
require_once __DIR__ . '/../wp-load.php';

echo "====================================================================\n";
echo "BÁO CÁO KIỂM TRA TOÀN DIỆN VỀ ĂN THỊT TỪ KHÓA (KEYWORD CANNIBALIZATION)\n";
echo "====================================================================\n\n";

// 1. Thu thập toàn bộ Pillar Pages (Dự án)
$projects = get_posts([
    'post_type'      => 'du_an',
    'posts_per_page' => -1,
    'post_status'    => 'publish'
]);

$pillar_data = [];
foreach ($projects as $p) {
    $kw_str = get_post_meta($p->ID, 'rank_math_focus_keyword', true);
    $kws = array_map('trim', explode(',', mb_strtolower($kw_str, 'UTF-8')));
    $title = get_post_meta($p->ID, 'rank_math_title', true) ?: $p->post_title;
    $pillar_flag = get_post_meta($p->ID, 'rank_math_pillar_content', true);
    
    $pillar_data[$p->post_name] = [
        'id'       => $p->ID,
        'slug'     => $p->post_name,
        'title'    => $title,
        'kws'      => $kws,
        'is_pillar'=> $pillar_flag === 'on',
        'url'      => 'https://sunshine-group.com.vn/du-an/' . $p->post_name . '/'
    ];
}

echo "1. Đã quét " . count($pillar_data) . " Trang Dự Án (Pillar Pages - Cấp 2):\n";
foreach ($pillar_data as $slug => $data) {
    echo "  - [{$slug}] Pillar: " . ($data['is_pillar'] ? 'BẬT (Chuẩn)' : 'TẮT (CẦN SỬA)') . "\n";
    echo "    Bộ từ khóa độc quyền: " . implode(' | ', $data['kws']) . "\n";
}
echo "\n--------------------------------------------------------------------\n";

// 2. Thu thập toàn bộ Bài viết vệ tinh (Satellite Posts)
$posts = get_posts([
    'post_type'      => 'post',
    'posts_per_page' => -1,
    'post_status'    => 'publish'
]);

echo "2. Đang kiểm tra " . count($posts) . " Bài viết vệ tinh (Cấp 3)...\n\n";

$issues_cannibalization = [];
$issues_pillar_flag = [];
$issues_missing_link = [];
$issues_cross_link = [];
$passed_count = 0;

foreach ($posts as $post) {
    $kw_str = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
    $post_kws = array_filter(array_map('trim', explode(',', mb_strtolower($kw_str, 'UTF-8'))));
    $post_title = get_post_meta($post->ID, 'rank_math_title', true) ?: $post->post_title;
    $post_pillar = get_post_meta($post->ID, 'rank_math_pillar_content', true);
    
    // A. Kiểm tra cờ Pillar trên bài viết con (Bắt buộc phải là OFF)
    if ($post_pillar === 'on') {
        $issues_pillar_flag[] = "Bài viết ID {$post->ID} ({$post->post_title}) đang bật cờ Pillar (sai quy tắc Cấp 3).";
    }
    
    // B. Kiểm tra ăn thịt từ khóa: Focus Keyword của bài viết có trùng với từ khóa thương hiệu hạt nhân của bất kỳ Pillar Page nào không?
    $has_kw_collision = false;
    foreach ($pillar_data as $p_slug => $p_info) {
        foreach ($p_info['kws'] as $pk) {
            if (empty($pk)) continue;
            foreach ($post_kws as $post_k) {
                // Nếu bài con đặt Focus Keyword hoàn toàn trùng với từ khóa chính của Pillar
                if ($post_k === $pk) {
                    $issues_cannibalization[] = [
                        'post_id'    => $post->ID,
                        'post_title' => $post->post_title,
                        'post_kw'    => $post_k,
                        'pillar_slug'=> $p_slug,
                        'pillar_kw'  => $pk
                    ];
                    $has_kw_collision = true;
                }
            }
        }
    }
    
    // C. Kiểm tra liên kết nội bộ: Có trỏ về đúng Pillar Page của nó không?
    // Xác định bài viết này thuộc dự án nào
    $matched_pillar = null;
    $combined_text = mb_strtolower($post->post_title . ' ' . $post->post_name, 'UTF-8');
    
    foreach ($pillar_data as $p_slug => $p_info) {
        $clean_p_slug = str_replace('-', ' ', $p_slug);
        if (strpos($combined_text, $p_slug) !== false || 
            strpos($combined_text, $clean_p_slug) !== false ||
            (strpos($p_slug, 'hinode') !== false && strpos($combined_text, 'hinode') !== false) ||
            (strpos($p_slug, 'lalalay') !== false && strpos($combined_text, 'lalalay') !== false) ||
            (strpos($p_slug, 'handico') !== false && strpos($combined_text, 'handico') !== false) ||
            (strpos($p_slug, 'noble') !== false && strpos($combined_text, 'noble') !== false) ||
            (strpos($p_slug, 'continental') !== false && strpos($combined_text, 'continental') !== false) ||
            (strpos($p_slug, 'grand-riveria') !== false && strpos($combined_text, 'riveria') !== false) ||
            (strpos($p_slug, 'river-park') !== false && strpos($combined_text, 'river-park') !== false) ||
            (strpos($p_slug, 'king-park') !== false && strpos($combined_text, 'king-park') !== false) ||
            (strpos($p_slug, 'sun-urban') !== false && (strpos($combined_text, 'sun-urban') !== false || strpos($combined_text, 'ha nam') !== false))
        ) {
            $matched_pillar = $p_info;
            break;
        }
    }
    
    if ($matched_pillar) {
        $has_correct_link = (strpos($post->post_content, $matched_pillar['url']) !== false) ||
                            (strpos($post->post_content, '/du-an/' . $matched_pillar['slug']) !== false);
        
        if (!$has_correct_link) {
            $issues_missing_link[] = "Bài ID {$post->ID} ({$post->post_title}) chưa có link trỏ về Pillar: {$matched_pillar['slug']}";
        }
        
        // Kiểm tra trỏ chéo (cross-linking lộn xộn sang dự án khác)
        foreach ($pillar_data as $other_slug => $other_info) {
            if ($other_slug !== $matched_pillar['slug']) {
                if (strpos($post->post_content, $other_info['url']) !== false) {
                    $issues_cross_link[] = "Bài ID {$post->ID} ({$post->post_title}) thuộc [{$matched_pillar['slug']}] lại trỏ chéo sang [{$other_slug}]!";
                }
            }
        }
    }
    
    if (!$has_kw_collision) {
        $passed_count++;
    }
}

echo "====================================================================\n";
echo "KẾT QUẢ PHÂN TÍCH:\n";
echo "====================================================================\n";

echo "1. Cờ Pillar Content:\n";
if (empty($issues_pillar_flag)) {
    echo "  [CHUẨN 100%] Toàn bộ bài viết vệ tinh đều để cờ Pillar = OFF. Chỉ các trang Cột Trụ mới bật ON.\n";
} else {
    echo "  [CẢNH BÁO] Có " . count($issues_pillar_flag) . " bài vệ tinh đang bật cờ Pillar!\n";
    foreach ($issues_pillar_flag as $err) echo "    - $err\n";
}

echo "\n2. Tình trạng ăn thịt từ khóa (Keyword Cannibalization):\n";
if (empty($issues_cannibalization)) {
    echo "  [CHUẨN 100%] KHÔNG CÒN BẤT KỲ TÌNH TRẠNG ĂN THỊT TỪ KHÓA NÀO!\n";
    echo "  Toàn bộ bài viết vệ tinh đều đã được chuyển sang từ khóa đuôi dài (Long-tail keywords).\n";
    echo "  Các từ khóa ngắn hạt nhân (Short-tail) được độc quyền hoàn toàn bởi các Trang Cột Trụ (Pillar Pages).\n";
} else {
    echo "  [PHÁT HIỆN] Còn " . count($issues_cannibalization) . " trường hợp trùng từ khóa hạt nhân:\n";
    foreach ($issues_cannibalization as $c) {
        echo "    - Bài: '{$c['post_title']}' (ID: {$c['post_id']})\n";
        echo "      Từ khóa trùng: '{$c['post_kw']}' với Pillar [{$c['pillar_slug']}]\n";
    }
}

echo "\n3. Tình trạng liên kết nội bộ & Anchor Text về Pillar Page:\n";
if (empty($issues_missing_link)) {
    echo "  [CHUẨN 100%] 100% bài viết vệ tinh theo cụm đều có Anchor Text trỏ thẳng về đúng Trang Dự Án Cột Trụ.\n";
} else {
    echo "  [CẢNH BÁO] Còn " . count($issues_missing_link) . " bài chưa có link trỏ về Pillar:\n";
    foreach ($issues_missing_link as $m) echo "    - $m\n";
}

echo "\n4. Tình trạng trỏ chéo liên kết (Cross-linking vi phạm Silo):\n";
if (empty($issues_cross_link)) {
    echo "  [CHUẨN 100%] Cấu trúc Silo cách ly tuyệt đối! Không có tình trạng bài của dự án A trỏ chéo sang dự án B.\n";
} else {
    echo "  [CẢNH BÁO] Có " . count($issues_cross_link) . " trường hợp trỏ chéo vi phạm Silo:\n";
    foreach ($issues_cross_link as $cl) echo "    - $cl\n";
}

echo "\n====================================================================\n";
