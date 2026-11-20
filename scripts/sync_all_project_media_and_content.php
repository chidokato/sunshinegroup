<?php
/**
 * Tải toàn bộ 41 ảnh dự án lên live server và cập nhật link ảnh trong bài viết
 */
require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/sync_to_live.php';

$sync = new SunshineLiveSync();

echo "=== BẮT ĐẦU ĐỒNG BỘ MEDIA & NỘI DUNG DỰ ÁN LÊN LIVE ===\n";

$projects = get_posts([
    'post_type'      => 'du_an',
    'posts_per_page' => -1,
    'post_status'    => 'publish'
]);

echo "Tìm thấy " . count($projects) . " dự án trên localhost.\n\n";

// 1. Tập hợp danh sách các ảnh cần upload
$upload_map = []; // local_rel_path => live_url
$all_image_paths = [];

foreach ($projects as $p) {
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $p->post_content, $matches);
    foreach ($matches[1] as $url) {
        $clean_path = str_replace([
            'http://localhost/www/Sunshinegroup/',
            'https://sunshine-group.com.vn/',
            'http://sunshine-group.com.vn/'
        ], '', $url);
        
        $all_image_paths[$clean_path] = true;
    }
}

echo "Tổng cộng " . count($all_image_paths) . " ảnh duy nhất cần đồng bộ.\n";

// 2. Upload từng ảnh lên Live server
$count_uploaded = 0;
foreach (array_keys($all_image_paths) as $rel_path) {
    $local_full = ABSPATH . $rel_path;
    if (!file_exists($local_full)) {
        echo " [BỎ QUA] Không tìm thấy file cục bộ: {$rel_path}\n";
        continue;
    }

    $filename = basename($local_full);
    echo " -> Đang upload: {$filename}... ";

    // Kiểm tra xem trên live đã có file này hay chưa
    $check_url = 'https://sunshine-group.com.vn/' . $rel_path;
    $ctx = stream_context_create(['http' => ['method' => 'HEAD', 'timeout' => 5, 'ignore_errors' => true]]);
    $headers = @get_headers($check_url);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "[ĐÃ TỒN TẠI TRÊN LIVE] $check_url\n";
        $upload_map[$rel_path] = $check_url;
        continue;
    }

    // Upload lên Live qua REST API
    $file_data = file_get_contents($local_full);
    $mime_type = mime_content_type($local_full) ?: 'image/jpeg';
    $res = $sync->request('/wp/v2/media', 'POST', $file_data, [
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Content-Type'        => $mime_type,
    ]);

    if (isset($res['body']['source_url'])) {
        $live_source = $res['body']['source_url'];
        $upload_map[$rel_path] = $live_source;
        echo "[THÀNH CÔNG] => {$live_source}\n";
        $count_uploaded++;
    } elseif (isset($res['body']['id'])) {
        // Lấy source_url
        $mid = $res['body']['id'];
        $m_res = $sync->request("/wp/v2/media/{$mid}");
        $live_source = $m_res['body']['source_url'] ?? ('https://sunshine-group.com.vn/' . $rel_path);
        $upload_map[$rel_path] = $live_source;
        echo "[THÀNH CÔNG (ID $mid)] => {$live_source}\n";
        $count_uploaded++;
    } else {
        echo "[LỖI UPLOAD] " . ($res['body']['message'] ?? json_encode($res['body'])) . "\n";
        // Fallback live url
        $upload_map[$rel_path] = 'https://sunshine-group.com.vn/' . $rel_path;
    }
}

echo "\nĐã tải lên {$count_uploaded} ảnh mới.\n\n";

// 3. Cập nhật bài viết trên Live server
echo "3. Bắt đầu cập nhật nội dung các dự án trên Live...\n";
foreach ($projects as $p) {
    $content = $p->post_content;
    
    // Thay thế toàn bộ link ảnh localhost thành link Live
    $content = str_replace('http://localhost/www/Sunshinegroup/', 'https://sunshine-group.com.vn/', $content);
    
    // Thay thế chính xác từng ảnh theo map nếu có URL khác
    foreach ($upload_map as $rel_path => $live_url) {
        $local_candidate = 'https://sunshine-group.com.vn/' . $rel_path;
        if ($local_candidate !== $live_url) {
            $content = str_replace($local_candidate, $live_url, $content);
        }
    }

    // Tìm dự án trên live theo slug
    $res = $sync->request("/wp/v2/du_an?slug={$p->post_name}");
    if (!empty($res['body']) && is_array($res['body'])) {
        $live_id = $res['body'][0]['id'];
        
        $update_res = $sync->request("/wp/v2/du_an/{$live_id}", 'POST', [
            'content' => $content
        ]);
        
        if (isset($update_res['body']['id'])) {
            echo " [OK] Cập nhật dự án Live: {$p->post_title} (ID: {$live_id})\n";
        } else {
            echo " [LỖI UPDATE] {$p->post_title}: " . ($update_res['body']['message'] ?? 'Lỗi không xác định') . "\n";
        }
    } else {
        echo " [CHƯA CÓ TRÊN LIVE] {$p->post_title}\n";
    }
}

echo "\n=== HOÀN TẤT ĐỒNG BỘ MEDIA & DỰ ÁN ===\n";
