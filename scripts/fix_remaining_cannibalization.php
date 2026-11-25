<?php
require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/sync_to_live.php';

$sync = new SunshineLiveSync();

echo "=== TIẾN HÀNH KHẮC PHỤC TRIỆT ĐỂ 7 BÀI VIẾT CÒN TỒN ĐỌNG ===\n\n";

$fixes = [
    // 1. Bài 1092 - The President Park
    1092 => [
        'longtail_kw' => 'hầm đỗ xe the president park mễ trì',
        'seo_title'   => 'Hầm Đỗ Xe Siêu Xe The President Park Mễ Trì [2026]',
        'meta_desc'   => 'Khám phá hệ thống hầm đỗ xe ngầm thông minh cho siêu xe dưới công viên sinh thái The President Park Mễ Trì. Tiêu chuẩn quốc tế cao cấp nhất.',
        'pillar_url'  => 'https://sunshine-group.com.vn/du-an/the-president-park-ha-noi/',
        'brand_kw'    => 'The President Park Mễ Trì'
    ],
    // 2. Bài 1091 - The President Park
    1091 => [
        'longtail_kw' => 'vị trí the president park mễ trì',
        'seo_title'   => 'Đánh Giá Vị Trí The President Park Mễ Trì Mới Nhất 2026',
        'meta_desc'   => 'Đánh giá chi tiết vị trí The President Park Mễ Trì tọa độ vàng ngã tư Cương Kiên - Lương Thế Vinh kề cận Vinhomes Green Bay. Kết nối giao thông hoàn hảo.',
        'pillar_url'  => 'https://sunshine-group.com.vn/du-an/the-president-park-ha-noi/',
        'brand_kw'    => 'The President Park Mễ Trì'
    ],
    // 3. Bài 414 - The President Park
    414 => [
        'longtail_kw' => 'tổng quan the president park mễ trì',
        'seo_title'   => 'Tổng Quan Dự Án The President Park Mễ Trì [Chi Tiết 2026]',
        'meta_desc'   => 'Tổng quan The President Park Mễ Trì CityLand Hà Nội: quy hoạch biệt thự, shophouse thương mại, tiến độ xây dựng và chính sách thanh toán trực tiếp CĐT.',
        'pillar_url'  => 'https://sunshine-group.com.vn/du-an/the-president-park-ha-noi/',
        'brand_kw'    => 'The President Park Mễ Trì'
    ],
    // 4. Bài 799 - Handico Sunshine New Hà Nội
    799 => [
        'longtail_kw' => 'thông tin quy hoạch handico sunshine new hà nội',
        'seo_title'   => 'Thông Tin Quy Hoạch Handico Sunshine New Hà Nội [2026]',
        'meta_desc'   => 'Phân tích thông tin quy hoạch Handico Sunshine New Hà Nội (Bắc Thăng Long - Mê Linh). Tiềm năng phát triển đô thị vệ tinh và cơ hội đầu tư sinh lời 2026.',
        'pillar_url'  => 'https://sunshine-group.com.vn/du-an/handico-sunshine-new-ha-noi/',
        'brand_kw'    => 'Handico Sunshine New Hà Nội'
    ],
    // 5. Bài 376 - Sunshine Bay Retreat
    376 => [
        'longtail_kw' => 'mặt bằng dự án sunshine bay retreat',
        'seo_title'   => 'Mặt Bằng Dự Án Sunshine Bay Retreat Vũng Tàu [2026]',
        'meta_desc'   => 'Chi tiết mặt bằng dự án Sunshine Bay Retreat Vũng Tàu. Phân tích layout condotel, biệt thự nghỉ dưỡng hướng biển Chí Linh đẳng cấp thượng lưu.',
        'pillar_url'  => 'https://sunshine-group.com.vn/du-an/sunshine-bay-retreat/',
        'brand_kw'    => 'Sunshine Bay Retreat'
    ],
    // 6. Bài 344 - Sunshine Bay Retreat
    344 => [
        'longtail_kw' => 'tổng quan dự án sunshine bay retreat',
        'seo_title'   => 'Tổng Quan Dự Án Sunshine Bay Retreat Vũng Tàu [2026]',
        'meta_desc'   => 'Tổng quan dự án Sunshine Bay Retreat Vũng Tàu: tổ hợp nghỉ dưỡng 320m mặt biển Chí Linh, bảng giá dự kiến 2026 và chính sách chia sẻ lợi nhuận hấp dẫn.',
        'pillar_url'  => 'https://sunshine-group.com.vn/du-an/sunshine-bay-retreat/',
        'brand_kw'    => 'Sunshine Bay Retreat'
    ],
    // 7. Bài 390 - Sunshine Bay Retreat
    390 => [
        'longtail_kw' => 'tiện ích dự án sunshine bay retreat',
        'seo_title'   => 'Hệ Thống Tiện Ích Dự Án Sunshine Bay Retreat Vũng Tàu [2026]',
        'meta_desc'   => 'Khám phá chuỗi tiện ích dự án Sunshine Bay Retreat Vũng Tàu: hồ bơi vô cực, bến du thuyền, sky bar và trung tâm chăm sóc sức khỏe chuẩn 5 sao quốc tế.',
        'pillar_url'  => 'https://sunshine-group.com.vn/du-an/sunshine-bay-retreat/',
        'brand_kw'    => 'Sunshine Bay Retreat'
    ]
];

foreach ($fixes as $post_id => $info) {
    $p = get_post($post_id);
    if (!$p) continue;
    
    echo "Đang xử lý bài ID {$post_id}: \"{$p->post_title}\"\n";
    $content = $p->post_content;
    
    // Gắn hyperlink về Pillar nếu chưa có
    if (strpos($content, $info['pillar_url']) === false) {
        $link_html = '<a href="' . $info['pillar_url'] . '" title="' . esc_attr($info['brand_kw']) . '" style="color:#d4af37;font-weight:700;text-decoration:underline;">' . $info['brand_kw'] . '</a>';
        
        $pattern = '/(?!(?:[^<]+>|[^>]+<\/a>))\b(' . preg_quote($info['brand_kw'], '/') . ')\b/iu';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $link_html, $content, 1);
            echo "  -> Đã gắn link về Pillar vào từ khóa đầu bài.\n";
        } else {
            $intro = '<p style="font-size:15.5px;line-height:1.7;background:#f8fafc;padding:14px 18px;border-left:4px solid #d4af37;border-radius:4px;margin-bottom:20px;">'
                . 'Bài viết phân tích chuyên sâu cho ' . $link_html . ' – cổng thông tin quy hoạch, bảng giá gốc cập nhật từ Chủ đầu tư.</p>';
            $content = $intro . $content;
            echo "  -> Đã chèn box giới thiệu có anchor text trỏ về Pillar.\n";
        }
    }
    
    // Cập nhật Local
    wp_update_post([
        'ID'           => $post_id,
        'post_content' => $content
    ]);
    update_post_meta($post_id, 'rank_math_title', $info['seo_title']);
    update_post_meta($post_id, 'rank_math_focus_keyword', $info['longtail_kw']);
    update_post_meta($post_id, 'rank_math_description', $info['meta_desc']);
    update_post_meta($post_id, 'rank_math_pillar_content', 'off');
    
    echo "  [LOCAL XONG] Focus KW đổi thành: \"{$info['longtail_kw']}\"\n";
    
    // Đồng bộ lên Live
    $live_check = $sync->request("/wp/v2/posts?slug={$p->post_name}");
    if (!empty($live_check['body']) && is_array($live_check['body'])) {
        $live_id = $live_check['body'][0]['id'];
        $live_content = str_replace('http://localhost/www/Sunshinegroup/', 'https://sunshine-group.com.vn/', $content);
        
        $update_res = $sync->request("/wp/v2/posts/{$live_id}", 'POST', [
            'content' => $live_content,
            'meta'    => [
                'rank_math_title'          => $info['seo_title'],
                'rank_math_focus_keyword'  => $info['longtail_kw'],
                'rank_math_description'    => $info['meta_desc'],
                'rank_math_pillar_content' => 'off'
            ]
        ]);
        if (isset($update_res['body']['id'])) {
            echo "  └─ [LIVE XONG] Đồng bộ Live ID: {$live_id}\n";
        }
    }
    echo "\n";
}

// Bật cờ Pillar cho Handico Sunshine Metropolis City (nếu còn tồn tại)
$metropolis = get_page_by_path('handico-sunshine-metropolis-city', OBJECT, 'du_an');
if ($metropolis) {
    update_post_meta($metropolis->ID, 'rank_math_pillar_content', 'on');
    echo "[METROPOLIS] Đã bật cờ Pillar on cho Metropolis City.\n";
}

echo "=== ĐÃ HOÀN TẤT KHẮC PHỤC TRIỆT ĐỂ! ===\n";
