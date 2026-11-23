<?php
/**
 * TỐI ƯU HÓA TOÀN DIỆN PILLAR PAGES (DỰ ÁN BĐS - CẤP 2)
 */
require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/sync_to_live.php';

$sync = new SunshineLiveSync();

$pillar_configs = [
    'hinode-solera-pham-hung' => [
        'title'      => 'Dự Án Hinode Solera Phạm Hùng | Bảng Giá Gốc CĐT 2026',
        'focuskw'    => 'dự án Hinode Solera Phạm Hùng, chung cư Hinode Solera Phạm Hùng, Hinode Solera, chung cư Hinode Solera',
        'desc'       => 'Dự án Hinode Solera Phạm Hùng (Chung cư 28A Phạm Hùng). Cập nhật bảng giá gốc CĐT 2026, mặt bằng, tiến độ thi công & chính sách ưu đãi chiết khấu mới nhất.',
        'pillar'     => 'on'
    ],
    'lalalay-residences-bac-an-khanh' => [
        'title'      => 'Lalalay Residences Bắc An Khánh | Bảng Giá & Tiến Độ CĐT 2026',
        'focuskw'    => 'Lalalay Residences, Lalalay Bắc An Khánh, dự án Lalalay Residences, chung cư Lalalay Residences',
        'desc'       => 'Lalalay Residences Bắc An Khánh – Tổ hợp căn hộ cao cấp Splendora. Cập nhật bảng giá gốc CĐT 2026, mặt bằng thiết kế, tiện ích chuẩn quốc tế & giỏ hàng độc quyền.',
        'pillar'     => 'on'
    ],
    'handico-sunshine-new-ha-noi' => [
        'title'      => 'Dự Án Handico Sunshine New Hà Nội | Bảng Giá Gốc CĐT 2026',
        'focuskw'    => 'Handico Sunshine New Hà Nội, dự án Handico Sunshine New Hà Nội, Sunshine New Hà Nội, Sunshine Mê Linh',
        'desc'       => 'Dự án Handico Sunshine New Hà Nội (Bắc Thăng Long - Mê Linh). Nhận bảng giá gốc CĐT 2026, quy hoạch biệt thự, liền kề & shophouse đẳng cấp hàng đầu Thủ Đô.',
        'pillar'     => 'on'
    ],
    'noble-west-lake-ha-noi' => [
        'title'      => 'Noble West Lake Hà Nội Ciputra | Bảng Giá Ngoại Giao 2026',
        'focuskw'    => 'Noble West Lake Hà Nội, Sunshine Noble Westlake, dự án Noble West Lake, căn hộ Ciputra Tây Hồ',
        'desc'       => 'Noble West Lake Hà Nội Ciputra Tây Hồ. Thông tin quy hoạch, bảng giá ngoại giao căn hộ siêu sang 2026, mặt bằng & chính sách thanh toán trực tiếp Chủ đầu tư.',
        'pillar'     => 'on'
    ],
    'vinhomes-king-park-bac-ninh' => [
        'title'      => 'Dự Án Vinhomes King Park Bắc Ninh | Bảng Giá & Quy Hoạch 2026',
        'focuskw'    => 'Vinhomes King Park Bắc Ninh, dự án Vinhomes King Park, Vinhomes King Park, Vinhomes Kinh Bắc',
        'desc'       => 'Dự án Vinhomes King Park Bắc Ninh (Đại đô thị sinh thái sân Golf 268ha). Cập nhật bảng giá biệt thự, shophouse, tiến độ xây dựng & chính sách bán hàng 2026.',
        'pillar'     => 'on'
    ],
    'vinhomes-global-sportia' => [
        'title'      => 'Vinhomes Global Sportia Ngọc Hồi | Bảng Giá CĐT Mới Nhất 2026',
        'focuskw'    => 'Vinhomes Global Sportia, dự án Vinhomes Global Sportia, Vinhomes Global Sportia Ngọc Hồi',
        'desc'       => 'Vinhomes Global Sportia Ngọc Hồi Hà Nội – Siêu đô thị thể thao hàng đầu phía Nam. Cập nhật quy hoạch, tiến độ mở bán đợt 1 và bảng giá ưu đãi CĐT 2026.',
        'pillar'     => 'on'
    ],
    'astor-1-ciputra' => [
        'title'      => 'Chung Cư Astor 1 Ciputra Tây Hồ | Bảng Giá Căn Hộ Gốc 2026',
        'focuskw'    => 'Astor 1 Ciputra, chung cư Astor 1 Ciputra, dự án Astor 1, căn hộ IA25 Ciputra',
        'desc'       => 'Chung cư Astor 1 Ciputra Tây Hồ (Khu đô thị Nam Thăng Long). Bảng giá gốc CĐT 2026, mặt bằng thiết kế sang trọng & tiêu chuẩn bàn giao nội thất cao cấp.',
        'pillar'     => 'on'
    ],
    'the-president-park-ha-noi' => [
        'title'      => 'The President Park Mễ Trì Hà Nội | Bảng Giá CityLand 2026',
        'focuskw'    => 'the president park, the president park mễ trì, the president park hà nội, cityland mễ trì',
        'desc'       => 'The President Park Mễ Trì Hà Nội – Quần thể biệt thự, shophouse CityLand. Nhận trọn bộ bảng giá 2026, phân tích vị trí kim cương & tiềm năng đầu tư sinh lời.',
        'pillar'     => 'on'
    ],
    'sunshine-bay-retreat' => [
        'title'      => 'Sunshine Bay Retreat Vũng Tàu | Tổ Hợp Nghỉ Dưỡng Biển 2026',
        'focuskw'    => 'Sunshine Bay Retreat, dự án Sunshine Bay Retreat, Sunshine Bay Retreat Vũng Tàu',
        'desc'       => 'Sunshine Bay Retreat Vũng Tàu ôm trọn bãi biển Chí Linh. Cập nhật bảng giá condotel, biệt thự biển nghỉ dưỡng 2026 và chính sách cam kết chia sẻ lợi nhuận.',
        'pillar'     => 'on'
    ],
    'sunshine-continental' => [
        'title'      => 'Chung Cư Sunshine Continental Mỹ Đình | Bảng Giá Gốc CĐT 2026',
        'focuskw'    => 'Sunshine Continental, dự án Sunshine Continental, chung cư Sunshine Continental, căn hộ Mỹ Đình',
        'desc'       => 'Dự án Sunshine Continental Mỹ Đình – Căn hộ hạng sang mặt tiền giao lộ vàng. Bảng giá gốc CĐT 2026, tiện ích chuẩn 5 sao và chính sách vay ngân hàng 0% lãi suất.',
        'pillar'     => 'on'
    ],
    'the-grand-riveria' => [
        'title'      => 'The Grand Riveria Sunshine Group | Đô Thị Sinh Thái Ven Sông 2026',
        'focuskw'    => 'The Grand Riveria, dự án The Grand Riveria, đại đô thị The Grand Riveria',
        'desc'       => 'The Grand Riveria Sunshine Group – Đại đô thị sinh thái thượng lưu ven sông. Cập nhật quy hoạch 1/500, bảng giá biệt thự nghỉ dưỡng & tiến độ hạ tầng mới nhất 2026.',
        'pillar'     => 'on'
    ],
    'sunshine-river-park' => [
        'title'      => 'Chung Cư Sunshine River Park | Bảng Giá & Mặt Bằng CĐT 2026',
        'focuskw'    => 'Sunshine River Park, chung cư Sunshine River Park, dự án Sunshine River Park',
        'desc'       => 'Chung cư Sunshine River Park – Tổ hợp căn hộ sinh thái xanh thông minh. Bảng giá gốc 2026, thiết kế tối ưu ánh sáng tự nhiên và chính sách chiết khấu thanh toán sớm.',
        'pillar'     => 'on'
    ],
    'sun-urban-city-ha-nam' => [
        'title'      => 'Sun Urban City Hà Nam | Đô Thị Nghỉ Dưỡng Sun Group 2026',
        'focuskw'    => 'Sun Urban City Hà Nam, dự án Sun Urban City, Sun Urban City',
        'desc'       => 'Sun Urban City Hà Nam – Đại đô thị nghỉ dưỡng 420ha của Tập đoàn Sun Group. Khám phá bảng giá mở bán 2026, 1001 tiện ích cảnh quan & chính sách booking đợt 1.',
        'pillar'     => 'on'
    ]
];

echo "=== BẮT ĐẦU CẬP NHẬT PILLAR PAGES (LOCAL & LIVE) ===\n\n";

foreach ($pillar_configs as $slug => $cfg) {
    $post = get_page_by_path($slug, OBJECT, 'du_an');
    if (!$post) {
        echo "[-] Không tìm thấy dự án local: $slug\n";
        continue;
    }
    
    // 1. Cập nhật Local
    update_post_meta($post->ID, 'rank_math_title', $cfg['title']);
    update_post_meta($post->ID, 'rank_math_focus_keyword', $cfg['focuskw']);
    update_post_meta($post->ID, 'rank_math_description', $cfg['desc']);
    update_post_meta($post->ID, 'rank_math_pillar_content', $cfg['pillar']);
    update_post_meta($post->ID, 'rank_math_canonical_url', 'https://sunshine-group.com.vn/du-an/' . $slug . '/');
    
    echo "[LOCAL OK] $slug -> Đã bật Pillar Content, SEO Title & Focus Keywords.\n";
    
    // 2. Cập nhật Live qua REST API
    $res = $sync->request("/wp/v2/du_an?slug={$slug}");
    if (!empty($res['body']) && is_array($res['body'])) {
        $live_id = $res['body'][0]['id'];
        
        $update = $sync->request("/wp/v2/du_an/{$live_id}", 'POST', [
            'meta' => [
                'rank_math_title'          => $cfg['title'],
                'rank_math_focus_keyword'  => $cfg['focuskw'],
                'rank_math_description'    => $cfg['desc'],
                'rank_math_pillar_content' => $cfg['pillar'],
                'rank_math_canonical_url'  => 'https://sunshine-group.com.vn/du-an/' . $slug . '/'
            ]
        ]);
        
        if (isset($update['body']['id'])) {
            echo "  └─ [LIVE OK] Đã đồng bộ lên Live ID: {$live_id}\n";
        } else {
            echo "  └─ [LIVE ERR] " . json_encode($update['body']) . "\n";
        }
    } else {
        echo "  └─ [LIVE NOT FOUND] $slug\n";
    }
}

echo "\n=== HOÀN TẤT BƯỚC 1: TỐI ƯU PILLAR PAGES ===\n";
