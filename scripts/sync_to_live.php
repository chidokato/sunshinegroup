<?php
/**
 * =========================================================================
 * SUNSHINE GROUP - LIVE SYNC & AUTO-PUBLISHING ENGINE
 * =========================================================================
 * Tự động đồng bộ bài viết & trang từ Localhost lên Live Website
 * (https://sunshine-group.com.vn) qua WordPress REST API & Application Passwords.
 * =========================================================================
 */

require_once dirname(__DIR__) . '/wp-load.php';

class SunshineLiveSync {
    private $live_url;
    private $username;
    private $app_password;
    private $auth_header;

    public function __construct( $user = null, $pass = null, $live_url = 'https://sunshine-group.com.vn' ) {
        $this->live_url = rtrim( $live_url, '/' );

        $config_file = dirname(__DIR__) . '/config-live-sync.php';
        if ( file_exists( $config_file ) ) {
            $config = include $config_file;
            if ( is_array( $config ) ) {
                $this->username     = $config['username'] ?? $user;
                $this->app_password = $config['app_password'] ?? $pass;
                if ( ! empty( $config['live_url'] ) ) {
                    $this->live_url = rtrim( $config['live_url'], '/' );
                }
            }
        }

        if ( $user ) $this->username = $user;
        if ( $pass ) $this->app_password = $pass;

        if ( $this->username && $this->app_password ) {
            $clean_pass = str_replace( ' ', '', $this->app_password );
            $this->auth_header = 'Basic ' . base64_encode( $this->username . ':' . $clean_pass );
        }
    }

    public function request( $endpoint, $method = 'GET', $data = null, $headers = [] ) {
        $url = $this->live_url . '/wp-json' . $endpoint;

        $default_headers = [
            'User-Agent' => 'Sunshine-Localhost-Sync/1.0',
        ];

        if ( $this->auth_header ) {
            $default_headers['Authorization'] = $this->auth_header;
        }

        if ( $data && is_array( $data ) && $method !== 'GET' ) {
            $default_headers['Content-Type'] = 'application/json';
        }

        $all_headers = array_merge( $default_headers, $headers );

        $formatted_headers = [];
        foreach ( $all_headers as $k => $v ) {
            $formatted_headers[] = "$k: $v";
        }

        $opts = [
            'http' => [
                'method'  => $method,
                'header'  => implode( "\r\n", $formatted_headers ) . "\r\n",
                'timeout' => 45,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ]
        ];

        if ( $data && $method !== 'GET' ) {
            $opts['http']['content'] = is_string( $data ) ? $data : json_encode( $data );
        }

        $ctx = stream_context_create( $opts );
        $response = @file_get_contents( $url, false, $ctx );

        $status_code = 0;
        if ( isset( $http_response_header ) && is_array( $http_response_header ) ) {
            if ( preg_match( '/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $sm ) ) {
                $status_code = intval( $sm[1] );
            }
        }

        return [
            'code' => $status_code,
            'body' => json_decode( $response, true ),
            'raw'  => $response,
        ];
    }

    public function test_connection() {
        if ( empty( $this->auth_header ) ) {
            echo "[LỖI] Chưa cung cấp Username hoặc Application Password!\n";
            return false;
        }

        echo "Đang kiểm tra kết nối đến {$this->live_url} với tài khoản {$this->username}...\n";
        $res = $this->request( '/wp/v2/users/me' );

        if ( $res['code'] === 200 && ! empty( $res['body']['id'] ) ) {
            $user_name = $res['body']['name'] ?? $res['body']['slug'];
            echo "[THÀNH CÔNG] Xác thực API thành công!\n";
            echo " - Người dùng: {$user_name} (ID: {$res['body']['id']})\n";
            return true;
        } else {
            echo "[THẤT BẠI] Mã lỗi HTTP: {$res['code']}\n";
            if ( ! empty( $res['body']['message'] ) ) {
                echo " - Chi tiết: " . $res['body']['message'] . "\n";
            }
            return false;
        }
    }

    public function upload_media( $local_file_path, $alt_text = '' ) {
        if ( ! file_exists( $local_file_path ) ) {
            return false;
        }

        $file_name = basename( $local_file_path );
        $mime_type = mime_content_type( $local_file_path ) ?: 'image/jpeg';
        $file_data = file_get_contents( $local_file_path );

        $headers = [
            'Content-Disposition' => 'attachment; filename="' . $file_name . '"',
            'Content-Type'        => $mime_type,
        ];

        $res = $this->request( '/wp/v2/media', 'POST', $file_data, $headers );

        if ( in_array( $res['code'], [200, 201] ) && ! empty( $res['body']['id'] ) ) {
            $media_id = $res['body']['id'];
            if ( $alt_text ) {
                $this->request( "/wp/v2/media/{$media_id}", 'POST', [ 'alt_text' => $alt_text ] );
            }
            return $media_id;
        }

        return false;
    }

    public function sync_item( $local_post_id ) {
        $p = get_post( $local_post_id );
        if ( ! $p ) {
            echo "[LỖI] Không tìm thấy item ID {$local_post_id} trên localhost!\n";
            return false;
        }

        $type_endpoint = ($p->post_type === 'page') ? '/wp/v2/pages' : '/wp/v2/posts';
        $type_label = ($p->post_type === 'page') ? 'Trang' : 'Bài viết';

        echo ">>> Bắt đầu đồng bộ {$type_label}: \"{$p->post_title}\" (ID: {$local_post_id}, Slug: {$p->post_name})\n";

        // Tự động thay thế URL localhost thành Live domain
        $content = $p->post_content;
        $content = str_replace( 'http://localhost/www/Sunshinegroup', $this->live_url, $content );
        $content = str_replace( 'http:\/\/localhost\/www\/Sunshinegroup', str_replace( '/', '\/', $this->live_url ), $content );

        // Upload ảnh đại diện nếu có
        $featured_media_id = 0;
        $thumb_id = get_post_thumbnail_id( $local_post_id );
        if ( $thumb_id ) {
            $thumb_path = get_attached_file( $thumb_id );
            if ( $thumb_path && file_exists( $thumb_path ) ) {
                echo "   + Đang upload ảnh đại diện lên live server...\n";
                $uploaded_id = $this->upload_media( $thumb_path, $p->post_title );
                if ( $uploaded_id ) {
                    $featured_media_id = $uploaded_id;
                    echo "   + Ảnh đại diện đã upload thành công (Live Media ID: {$featured_media_id})\n";
                }
            }
        }

        $payload = [
            'title'   => $p->post_title,
            'content' => $content,
            'status'  => 'publish',
            'slug'    => $p->post_name,
        ];

        if ( $p->post_excerpt ) {
            $payload['excerpt'] = $p->post_excerpt;
        }

        if ( $featured_media_id ) {
            $payload['featured_media'] = $featured_media_id;
        }

        // Rank Math SEO Meta
        $seo_title = get_post_meta( $local_post_id, 'rank_math_title', true );
        $seo_desc  = get_post_meta( $local_post_id, 'rank_math_description', true );
        $seo_focus = get_post_meta( $local_post_id, 'rank_math_focus_keyword', true );

        $meta = [];
        if ( $seo_title ) $meta['rank_math_title'] = $seo_title;
        if ( $seo_desc )  $meta['rank_math_description'] = $seo_desc;
        if ( $seo_focus ) $meta['rank_math_focus_keyword'] = $seo_focus;
        if ( ! empty( $meta ) ) {
            $payload['meta'] = $meta;
        }

        // Kiểm tra xem đã tồn tại trên Live chưa (theo slug)
        $find_res = $this->request( "{$type_endpoint}?slug=" . urlencode( $p->post_name ) );
        $existing_id = 0;
        if ( $find_res['code'] === 200 && ! empty( $find_res['body'] ) && is_array( $find_res['body'] ) ) {
            $existing_id = $find_res['body'][0]['id'] ?? 0;
        }

        if ( $existing_id > 0 ) {
            echo "   + Đã tồn tại trên live (ID: {$existing_id}) -> CẬP NHẬT...\n";
            $res = $this->request( "{$type_endpoint}/{$existing_id}", 'POST', $payload );
            $action = 'CẬP NHẬT';
        } else {
            echo "   + Chưa có trên live -> ĐĂNG MỚI...\n";
            $res = $this->request( $type_endpoint, 'POST', $payload );
            $action = 'ĐĂNG MỚI';
        }

        if ( in_array( $res['code'], [200, 201] ) ) {
            $live_id = $res['body']['id'] ?? $existing_id;
            $live_link = $res['body']['link'] ?? ($this->live_url . '/' . $p->post_name . '/');
            echo "[THÀNH CÔNG] Đã {$action} thành công!\n";
            echo " - Live ID: {$live_id}\n";
            echo " - URL: {$live_link}\n";
            return true;
        } else {
            echo "[LỖI] Thất bại (Mã HTTP: {$res['code']})!\n";
            if ( ! empty( $res['body']['message'] ) ) {
                echo " - Chi tiết: " . $res['body']['message'] . "\n";
            }
            return false;
        }
    }

    public function sync_all_content() {
        // Lấy tất cả bài viết và các trang quan trọng
        $items = get_posts([
            'numberposts' => -1,
            'post_type'   => ['post', 'page'],
            'post_status' => 'publish',
            'orderby'     => 'date',
            'order'       => 'DESC'
        ]);

        echo "BẮT ĐẦU ĐỒNG BỘ TOÀN BỘ " . count($items) . " BÀI VIẾT & TRANG TỪ LOCALHOST LÊN LIVE...\n";
        echo str_repeat("=", 80) . "\n\n";

        $success = 0;
        $failed = 0;

        foreach ( $items as $idx => $item ) {
            $num = $idx + 1;
            echo "[{$num}/" . count($items) . "] ";
            if ( $this->sync_item( $item->ID ) ) {
                $success++;
            } else {
                $failed++;
            }
            echo "\n";
            // Nghỉ nhẹ 0.3s để tránh quá tải live server
            usleep( 300000 );
        }

        echo str_repeat("=", 80) . "\n";
        echo "[KẾT QUẢ ĐỒNG BỘ]:\n";
        echo " - Thành công: {$success}\n";
        echo " - Thất bại/Bỏ qua: {$failed}\n";
        echo " - Tổng cộng: " . count($items) . "\n";
    }
}

// Xử lý dòng lệnh
if ( php_sapi_name() === 'cli' && isset( $argv ) ) {
    $options = getopt( '', [ 'test', 'sync-all', 'post_id:' ] );
    $sync = new SunshineLiveSync();

    if ( isset( $options['test'] ) ) {
        $sync->test_connection();
        exit;
    }

    if ( ! empty( $options['post_id'] ) ) {
        $sync->sync_item( intval( $options['post_id'] ) );
        exit;
    }

    if ( isset( $options['sync-all'] ) ) {
        $sync->sync_all_content();
        exit;
    }
}
