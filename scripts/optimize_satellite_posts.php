<?php
/**
 * TỐI ƯU TOÀN DIỆN BÀI VIẾT VỆ TINH (CLUSTER POSTS - CẤP 3)
 */
require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/sync_to_live.php';

$sync = new SunshineLiveSync();

$posts = get_posts([
    'post_type'      => 'post',
    'posts_per_page' => -1,
    'post_status'    => 'publish'
]);

echo "Tìm thấy " . count($posts) . " bài viết trên hệ thống.\n";

// Bảng mapping bài viết với Pillar Page tương ứng
function detect_parent_pillar($post) {
    $content = $post->post_title . ' ' . $post->post_name;
    
    if (preg_match('/hinode-solera|hinode/i', $content)) {
        return [
            'pillar_slug' => 'hinode-solera-pham-hung',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/hinode-solera-pham-hung/',
            'pillar_name' => 'Dự án Hinode Solera Phạm Hùng',
            'brand_kw'    => 'Hinode Solera Phạm Hùng',
            'type'        => 'cao_tang'
        ];
    }
    if (preg_match('/lalalay/i', $content)) {
        return [
            'pillar_slug' => 'lalalay-residences-bac-an-khanh',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/lalalay-residences-bac-an-khanh/',
            'pillar_name' => 'Lalalay Residences Bắc An Khánh',
            'brand_kw'    => 'Lalalay Residences',
            'type'        => 'cao_tang'
        ];
    }
    if (preg_match('/handico|metropolis|me-linh|dong-anh/i', $content)) {
        return [
            'pillar_slug' => 'handico-sunshine-new-ha-noi',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/handico-sunshine-new-ha-noi/',
            'pillar_name' => 'Handico Sunshine New Hà Nội',
            'brand_kw'    => 'Handico Sunshine New Hà Nội',
            'type'        => 'thap_tang'
        ];
    }
    if (preg_match('/continental/i', $content)) {
        return [
            'pillar_slug' => 'sunshine-continental',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/sunshine-continental/',
            'pillar_name' => 'Sunshine Continental',
            'brand_kw'    => 'Sunshine Continental',
            'type'        => 'cao_tang'
        ];
    }
    if (preg_match('/riveria/i', $content)) {
        return [
            'pillar_slug' => 'the-grand-riveria',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/the-grand-riveria/',
            'pillar_name' => 'The Grand Riveria',
            'brand_kw'    => 'The Grand Riveria',
            'type'        => 'thap_tang'
        ];
    }
    if (preg_match('/river-park|river_park/i', $content)) {
        return [
            'pillar_slug' => 'sunshine-river-park',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/sunshine-river-park/',
            'pillar_name' => 'Sunshine River Park',
            'brand_kw'    => 'Sunshine River Park',
            'type'        => 'cao_tang'
        ];
    }
    if (preg_match('/noble-west-lake|noble/i', $content)) {
        return [
            'pillar_slug' => 'noble-west-lake-ha-noi',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/noble-west-lake-ha-noi/',
            'pillar_name' => 'Noble West Lake Hà Nội',
            'brand_kw'    => 'Noble West Lake Hà Nội',
            'type'        => 'cao_tang'
        ];
    }
    if (preg_match('/astor/i', $content)) {
        return [
            'pillar_slug' => 'astor-1-ciputra',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/astor-1-ciputra/',
            'pillar_name' => 'Astor 1 Ciputra',
            'brand_kw'    => 'Astor 1 Ciputra',
            'type'        => 'cao_tang'
        ];
    }
    if (preg_match('/king-park/i', $content)) {
        return [
            'pillar_slug' => 'vinhomes-king-park-bac-ninh',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/vinhomes-king-park-bac-ninh/',
            'pillar_name' => 'Vinhomes King Park Bắc Ninh',
            'brand_kw'    => 'Vinhomes King Park',
            'type'        => 'thap_tang'
        ];
    }
    if (preg_match('/sportia/i', $content)) {
        return [
            'pillar_slug' => 'vinhomes-global-sportia',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/vinhomes-global-sportia/',
            'pillar_name' => 'Vinhomes Global Sportia',
            'brand_kw'    => 'Vinhomes Global Sportia',
            'type'        => 'cao_tang'
        ];
    }
    if (preg_match('/sun-urban|ha-nam/i', $content)) {
        return [
            'pillar_slug' => 'sun-urban-city-ha-nam',
            'pillar_url'  => 'https://sunshine-group.com.vn/du-an/sun-urban-city-ha-nam/',
            'pillar_name' => 'Sun Urban City Hà Nam',
            'brand_kw'    => 'Sun Urban City',
            'type'        => 'cao_tang'
        ];
    }
    
    return null;
}

$optimized_count = 0;

foreach ($posts as $p) {
    $parent = detect_parent_pillar($p);
    if (!$parent) {
        // Bài viết tin tức tổng quan / pháp lý chung
        echo "[BỎ QUA - TIN TỨC CHUNG] ID {$p->ID}: {$p->post_title}\n";
        continue;
    }
    
    $pillar_url  = $parent['pillar_url'];
    $brand_kw    = $parent['brand_kw'];
    $pillar_name = $parent['pillar_name'];
    $product_type = $parent['type'];
    
    echo "\n----------------------------------------------------\n";
    echo "TỐI ƯU BÀI: \"{$p->post_title}\" (ID {$p->ID})\n";
    echo "  -> Thuộc Cụm Topic Cluster: {$pillar_name} ({$pillar_url})\n";
    
    $content = $p->post_content;
    
    // 1. Kiểm tra và tiêm Anchor Text chuẩn trỏ về Pillar Page ở đoạn đầu nếu chưa có
    $has_link_to_pillar = strpos($content, $pillar_url) !== false;
    
    if (!$has_link_to_pillar) {
        echo "  [CẢNH BÁO] Chưa có internal link trỏ về Pillar Page! Đang tiêm anchor text...\n";
        
        // Tìm từ khóa thương hiệu ở đoạn đầu tiên hoặc tạo câu giới thiệu chuẩn SEO
        $link_html = '<a href="' . $pillar_url . '" title="' . esc_attr($pillar_name) . '" style="color:#d4af37;font-weight:700;text-decoration:underline;">' . $brand_kw . '</a>';
        
        // Thay thế lần xuất hiện đầu tiên của brand_kw (không nằm trong thẻ a)
        $pattern = '/(?!(?:[^<]+>|[^>]+<\/a>))\b(' . preg_quote($brand_kw, '/') . ')\b/iu';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $link_html, $content, 1);
            echo "  -> Đã gắn hyperlink trỏ về Pillar Page vào từ khóa \"{$brand_kw}\" xuất hiện đầu tiên!\n";
        } else {
            // Chèn câu dẫn đầu bài viết
            $intro_block = '<p style="font-size:15.5px;line-height:1.7;background:#f8fafc;padding:14px 18px;border-left:4px solid #d4af37;border-radius:4px;margin-bottom:20px;">'
                . 'Bài viết thuộc chuỗi phân tích chuyên sâu cho ' . $link_html . ' – cổng thông tin quy hoạch, bảng giá gốc và chính sách bán hàng cập nhật chính thức từ Chủ đầu tư.</p>';
            $content = $intro_block . $content;
            echo "  -> Đã chèn box giới thiệu có anchor text trỏ về Pillar Page đầu bài viết!\n";
        }
    } else {
        echo "  [OK] Đã có link trỏ về đúng Pillar Page.\n";
    }
    
    // 2. Chống ăn thịt từ khóa: Xác định Long-tail Focus Keyword phù hợp cho bài viết con
    $raw_title = $p->post_title;
    // Bỏ năm cũ nếu có
    $clean_title = preg_replace('/\b202[0-9]\b/', '', $raw_title);
    $clean_title = trim(preg_replace('/\s+/', ' ', $clean_title));
    
    // Tạo Long-tail Focus Keyword chính xác từ chủ đề bài viết
    $longtail_kw = mb_strtolower($clean_title, 'UTF-8');
    // Rút gọn bớt nếu quá dài
    $longtail_kw = trim(str_replace(['phân tích', 'đánh giá', 'chi tiết', 'chủ đầu tư sunshine group'], '', $longtail_kw));
    $longtail_kw = trim(preg_replace('/\s+/', ' ', $longtail_kw));
    
    // SEO Title tối ưu chuẩn 2026 (50-60 ký tự)
    $seo_title = $clean_title . ' Mới Nhất 2026 | Sunshine Group';
    if (mb_strlen($seo_title, 'UTF-8') > 70) {
        $seo_title = $clean_title . ' [2026]';
    }
    
    // Meta Description chuẩn 155 ký tự, chứa chính xác longtail_kw
    $meta_desc = "Thông tin chi tiết về {$longtail_kw} cập nhật mới nhất 2026. Phân tích quy hoạch, mặt bằng thiết kế, tiện ích & bảng giá trực tiếp từ Chủ đầu tư.";
    if (mb_strlen($meta_desc, 'UTF-8') > 160) {
        $meta_desc = mb_substr($meta_desc, 0, 157, 'UTF-8') . '...';
    }
    
    // 3. Đảm bảo form CTA cuối bài viết phù hợp dòng sản phẩm
    if (strpos($content, 'kbds_lead_form') === false && strpos($content, 'kbds_quick_lead_card') === false) {
        $form_shortcode = ($product_type === 'thap_tang') 
            ? "\n\n[kbds_lead_form_thap_tang title=\"ĐĂNG KÝ NHẬN BẢNG GIÁ &amp; MẶT BẰNG GỐC 2026\" subtitle=\"Nhận trọn bộ bảng giá phân lô, hợp đồng mua bán &amp; chính sách ưu đãi trực tiếp qua Zalo\" project=\"{$pillar_name}\"]\n"
            : "\n\n[kbds_lead_form_cao_tang title=\"ĐĂNG KÝ NHẬN BẢNG GIÁ GỐC CĂN HỘ 2026\" subtitle=\"Nhận giỏ hàng ngoại giao, bảng tính lãi vay ngân hàng &amp; xem nhà mẫu thực tế\" project=\"{$pillar_name}\"]\n";
        $content .= $form_shortcode;
        echo "  -> Đã bổ sung form đăng ký chuẩn dòng " . ($product_type === 'thap_tang' ? 'Thấp Tầng' : 'Cao Tầng') . " vào cuối bài!\n";
    }
    
    // 4. Lưu dữ liệu Local
    wp_update_post([
        'ID'           => $p->ID,
        'post_content' => $content
    ]);
    update_post_meta($p->ID, 'rank_math_title', $seo_title);
    update_post_meta($p->ID, 'rank_math_focus_keyword', $longtail_kw);
    update_post_meta($p->ID, 'rank_math_description', $meta_desc);
    update_post_meta($p->ID, 'rank_math_canonical_url', 'https://sunshine-group.com.vn/' . $p->post_name . '/');
    update_post_meta($p->ID, 'rank_math_pillar_content', 'off'); // Bài vệ tinh KHÔNG ĐƯỢC để pillar
    
    echo "  [LOCAL LƯU THÀNH CÔNG] Focus KW: \"{$longtail_kw}\" | Title: \"{$seo_title}\"\n";
    
    // 5. Đồng bộ lên Live qua REST API
    $live_check = $sync->request("/wp/v2/posts?slug={$p->post_name}");
    if (!empty($live_check['body']) && is_array($live_check['body'])) {
        $live_id = $live_check['body'][0]['id'];
        
        $live_content = str_replace('http://localhost/www/Sunshinegroup/', 'https://sunshine-group.com.vn/', $content);
        
        $update_live = $sync->request("/wp/v2/posts/{$live_id}", 'POST', [
            'content' => $live_content,
            'meta'    => [
                'rank_math_title'          => $seo_title,
                'rank_math_focus_keyword'  => $longtail_kw,
                'rank_math_description'    => $meta_desc,
                'rank_math_canonical_url'  => 'https://sunshine-group.com.vn/' . $p->post_name . '/',
                'rank_math_pillar_content' => 'off'
            ]
        ]);
        
        if (isset($update_live['body']['id'])) {
            echo "  └─ [LIVE ĐỒNG BỘ THÀNH CÔNG] Live ID: {$live_id}\n";
        } else {
            echo "  └─ [LIVE LỖI] " . json_encode($update_live['body']) . "\n";
        }
    } else {
        echo "  └─ [LIVE CHƯA CÓ] Cần tạo mới bài này trên live nếu là bài mới.\n";
    }
    
    $optimized_count++;
}

echo "\n====================================================\n";
echo "HOÀN TẤT TỐI ƯU HÓA {$optimized_count} BÀI VIẾT VỆ TINH CHUẨN TOPIC CLUSTER & SILO!\n";
echo "====================================================\n";
