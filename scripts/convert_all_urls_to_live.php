<?php
/**
 * Chuyển đổi toàn bộ link localhost sang sunshine-group.com.vn trong Database
 * Xử lý chuẩn serialization chống lỗi dữ liệu WordPress
 */
require_once __DIR__ . '/../wp-load.php';
global $wpdb;

$from = 'http://localhost/www/Sunshinegroup';
$to   = 'https://sunshine-group.com.vn';

echo "Bắt đầu thay thế URL trong Database:\n";
echo "Từ: {$from}\n";
echo "Sang: {$to}\n\n";

function safe_replace_recursive($from, $to, $data, $is_serialized = false) {
    if (is_string($data)) {
        $unserialized = @unserialize($data);
        if ($unserialized !== false || $data === 'b:0;') {
            $replaced = safe_replace_recursive($from, $to, $unserialized, true);
            return serialize($replaced);
        } else {
            return str_replace($from, $to, $data);
        }
    } elseif (is_array($data)) {
        $res = [];
        foreach ($data as $k => $v) {
            $res[$k] = safe_replace_recursive($from, $to, $v, false);
        }
        return $res;
    } elseif (is_object($data)) {
        $res = clone $data;
        foreach (get_object_vars($data) as $k => $v) {
            $res->$k = safe_replace_recursive($from, $to, $v, false);
        }
        return $res;
    }
    return $data;
}

// 1. Thay thế trong wp_posts
echo "1. Đang quét và cập nhật wp_posts...\n";
$posts = $wpdb->get_results("SELECT ID, post_content, post_excerpt, guid FROM {$wpdb->posts} WHERE post_content LIKE '%{$from}%' OR post_excerpt LIKE '%{$from}%' OR guid LIKE '%{$from}%'");
$count_posts = 0;
foreach ($posts as $p) {
    $new_content = str_replace($from, $to, $p->post_content);
    $new_excerpt = str_replace($from, $to, $p->post_excerpt);
    $new_guid    = str_replace($from, $to, $p->guid);
    
    $wpdb->update(
        $wpdb->posts,
        ['post_content' => $new_content, 'post_excerpt' => $new_excerpt, 'guid' => $new_guid],
        ['ID' => $p->ID]
    );
    $count_posts++;
}
echo "   -> Đã cập nhật {$count_posts} bài viết / trang / dự án.\n";

// 2. Thay thế trong wp_postmeta
echo "2. Đang quét và cập nhật wp_postmeta...\n";
$metas = $wpdb->get_results("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE '%{$from}%'");
$count_meta = 0;
foreach ($metas as $m) {
    $new_val = safe_replace_recursive($from, $to, $m->meta_value);
    $wpdb->update(
        $wpdb->postmeta,
        ['meta_value' => $new_val],
        ['meta_id' => $m->meta_id]
    );
    $count_meta++;
}
echo "   -> Đã cập nhật {$count_meta} trường meta dữ liệu.\n";

// 3. Thay thế trong wp_options (giữ nguyên siteurl/home nếu người dùng muốn tự đổi)
echo "3. Đang quét và cập nhật wp_options...\n";
$options = $wpdb->get_results("SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE '%{$from}%'");
$count_options = 0;
foreach ($options as $opt) {
    if (in_array($opt->option_name, ['siteurl', 'home'])) {
        // Tự động chuyển đổi luôn cho đồng bộ hoàn toàn
        $new_val = $to;
    } else {
        $new_val = safe_replace_recursive($from, $to, $opt->option_value);
    }
    
    $wpdb->update(
        $wpdb->options,
        ['option_value' => $new_val],
        ['option_id' => $opt->option_id]
    );
    $count_options++;
}
echo "   -> Đã cập nhật {$count_options} cài đặt options.\n";

// 4. Thay thế trong các bảng Rank Math nếu có
$tables = ['wp_rank_math_internal_links', 'wp_rank_math_redirections'];
foreach ($tables as $t) {
    $tbl_exists = $wpdb->get_var("SHOW TABLES LIKE '{$t}'");
    if ($tbl_exists) {
        $cols = $wpdb->get_col("SHOW COLUMNS FROM `{$t}`");
        foreach ($cols as $col) {
            $wpdb->query("UPDATE `{$t}` SET `{$col}` = REPLACE(`{$col}`, '{$from}', '{$to}') WHERE `{$col}` LIKE '%{$from}%'");
        }
        echo "   -> Đã cập nhật bảng {$t}.\n";
    }
}

echo "\n[HOÀN TẤT] Toàn bộ liên kết trong Database đã được chuyển đổi sang {$to}!\n";
