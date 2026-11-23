<?php
require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/sync_to_live.php';

$sync = new SunshineLiveSync();

echo "=== BẮT ĐẦU ĐỒNG BỘ TOÀN BỘ ẢNH LÊN LIVE SERVER ===\n\n";

global $wpdb;
$posts = $wpdb->get_results("SELECT ID, post_title, post_type, post_content FROM {$wpdb->posts} WHERE post_status = 'publish'");

$all_images = [];
foreach ($posts as $p) {
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $p->post_content, $m);
    foreach ($m[1] as $url) {
        if (strpos($url, 'wp-content/uploads/') !== false) {
            $rel = substr($url, strpos($url, 'wp-content/uploads/'));
            // Remove any query params
            if (strpos($rel, '?') !== false) {
                $rel = substr($rel, 0, strpos($rel, '?'));
            }
            $all_images[$rel] = true;
        }
    }

    // Quét Flatsome shortcode IDs: id="123", bg="456", img="789"
    preg_match_all('/(?:id|bg|img)=["\'](\d+)["\']/i', $p->post_content, $m_ids);
    if (!empty($m_ids[1])) {
        foreach (array_unique($m_ids[1]) as $aid) {
            $file = get_attached_file($aid);
            if ($file && file_exists($file)) {
                $rel = str_replace(ABSPATH, '', $file);
                $rel = str_replace('\\', '/', $rel);
                $all_images[$rel] = true;
                $meta = wp_get_attachment_metadata($aid);
                if (!empty($meta['sizes'])) {
                    $base_dir = dirname($file);
                    foreach ($meta['sizes'] as $s_info) {
                        $sub_f = $base_dir . '/' . $s_info['file'];
                        if (file_exists($sub_f)) {
                            $s_rel = str_replace(ABSPATH, '', $sub_f);
                            $s_rel = str_replace('\\', '/', $s_rel);
                            $all_images[$s_rel] = true;
                        }
                    }
                }
            }
        }
    }

    $thumb_id = get_post_thumbnail_id($p->ID);
    if ($thumb_id) {
        foreach (['full', 'large', 'medium_large', 'medium', 'thumbnail'] as $size) {
            $img_src = wp_get_attachment_image_src($thumb_id, $size);
            if ($img_src && !empty($img_src[0]) && strpos($img_src[0], 'wp-content/uploads/') !== false) {
                $rel = substr($img_src[0], strpos($img_src[0], 'wp-content/uploads/'));
                if (strpos($rel, '?') !== false) {
                    $rel = substr($rel, 0, strpos($rel, '?'));
                }
                $all_images[$rel] = true;
            }
        }
    }
}

// Also scan all files in wp-content/uploads/2026/11/ to ensure all recent project assets & thumbnails are synced
$nov_files = glob(ABSPATH . 'wp-content/uploads/2026/11/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
if ($nov_files) {
    foreach ($nov_files as $f) {
        $rel = 'wp-content/uploads/2026/11/' . basename($f);
        $all_images[$rel] = true;
    }
}

echo "Tìm thấy tổng cộng " . count($all_images) . " ảnh được sử dụng trên website.\n\n";

$count_uploaded = 0;
$count_existed  = 0;
$count_missing  = 0;

foreach (array_keys($all_images) as $rel) {
    $local_full = ABSPATH . $rel;
    if (!file_exists($local_full)) {
        // Try without dimensions like -1024x576 if it's a thumbnail
        $orig = preg_replace('/-\d+x\d+(\.[a-z]{3,4})$/i', '$1', $rel);
        if (file_exists(ABSPATH . $orig)) {
            $local_full = ABSPATH . $orig;
            $rel = $orig;
        } else {
            $count_missing++;
            continue;
        }
    }

    // Check if file already exists on live
    $check_url = 'https://sunshine-group.com.vn/' . $rel;
    $headers = @get_headers($check_url);
    if ($headers && strpos($headers[0], '200') !== false) {
        $count_existed++;
        continue;
    }

    echo " -> Đang upload: {$rel}... ";
    $data = base64_encode(file_get_contents($local_full));
    $res = $sync->request('/sunshine/v1/upload-asset', 'POST', [
        'path' => $rel,
        'data' => $data
    ]);

    if (!empty($res['body']['success'])) {
        echo "[OK] ({$res['body']['bytes']} bytes)\n";
        $count_uploaded++;
    } else {
        echo "[LỖI] " . ($res['body']['message'] ?? json_encode($res['body'])) . "\n";
    }
}

echo "\n=== KẾT QUẢ ĐỒNG BỘ MEDIA ===\n";
echo " - Đã có sẵn trên Live: {$count_existed}\n";
echo " - Vừa tải lên thành công: {$count_uploaded}\n";
echo " - Không tìm thấy file cục bộ: {$count_missing}\n";
echo "==============================\n";
