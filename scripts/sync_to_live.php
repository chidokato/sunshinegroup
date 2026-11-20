<?php
/**
 * =========================================================================
 * SUNSHINE GROUP - LIVE SYNC & AUTO-PUBLISHING ENGINE
 * =========================================================================
 * Script tự động đồng bộ bài viết & dự án từ Localhost lên Live Website
 * (https://sunshine-group.com.vn) qua WordPress REST API & Application Passwords.
 * 
 * Cách dùng:
 * 1. Cấu hình file config-live-sync.php (hoặc truyền qua tham số)
 * 2. Đăng 1 bài cụ thể: php scripts/sync_to_live.php --post_id=965
 * 3. Kiểm tra kết nối:  php scripts/sync_to_live.php --test
 * =========================================================================
 */

// Nạp môi trường WordPress Localhost
require_once dirname(__DIR__) . '/wp-load.php';

class SunshineLiveSync {
    private $live_url;
    private $username;
    private $app_password;
    private $auth_header;

    public function __construct( $user = null, $pass = null, $live_url = 'https://sunshine-group.com.vn' ) {
        $this->live_url = rtrim( $live_url, '/' );

        // 1. Kiểm tra file config nếu có
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
            // Chuẩn hóa mật khẩu ứng dụng (bỏ khoảng trắng nếu có)
            $clean_pass = str_replace( ' ', '', $this->app_password );
            $this->auth_header = 'Basic ' . base64_encode( $this->username . ':' . $clean_pass );
        }
    }

    /**
     * Gửi yêu cầu HTTP đến REST API Live
     */
    private function request( $endpoint, $method = 'GET', $data = null, $headers = [] ) {
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
                'timeout' => 30,
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

    /**
     * 1. Kiểm tra kết nối API Live
     */
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
            echo " - Quyền hạn: " . implode( ', ', $res['body']['roles'] ?? ['author'] ) . "\n";
            return true;
        } else {
            echo "[THẤT BẠI] Mã lỗi HTTP: {$res['code']}\n";
            if ( ! empty( $res['body']['message'] ) ) {
                echo " - Chi tiết: " . $res['body']['message'] . "\n";
            }
            return false;
        }
    }

    /**
     * 2. Upload ảnh media lên Live website
     */
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

    /**
     * 3. Đồng bộ / Đăng bài viết từ Localhost lên Live Website
     */
    public function sync_post( $local_post_id ) {
        $p = get_post( $local_post_id );
        if ( ! $p ) {
            echo "[LỖI] Không tìm thấy bài viết ID {$local_post_id} trên localhost!\n";
            return false;
        }

        echo ">>> Bắt đầu đồng bộ bài: \"{$p->post_title}\" (ID: {$local_post_id}, Slug: {$p->post_name})\n";

        // Tự động chuyển đổi toàn bộ URL localhost thành live URL
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

        // Chuẩn bị payload bài viết
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

        // Lấy meta Rank Math SEO
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

        // Kiểm tra xem bài viết đã tồn tại trên Live chưa (theo slug)
        $find_res = $this->request( '/wp/v2/posts?slug=' . urlencode( $p->post_name ) );
        $existing_post_id = 0;
        if ( $find_res['code'] === 200 && ! empty( $find_res['body'] ) && is_array( $find_res['body'] ) ) {
            $existing_post_id = $find_res['body'][0]['id'] ?? 0;
        }

        if ( $existing_post_id > 0 ) {
            echo "   + Phát hiện bài viết đã có trên live (ID: {$existing_post_id}) -> Đang CẬP NHẬT...\n";
            $res = $this->request( "/wp/v2/posts/{$existing_post_id}", 'POST', $payload );
            $action = 'CẬP NHẬT';
        } else {
            echo "   + Bài viết chưa có trên live -> Đang TẠO MỚI...\n";
            $res = $this->request( '/wp/v2/posts', 'POST', $payload );
            $action = 'ĐĂNG MỚI';
        }

        if ( in_array( $res['code'], [200, 201] ) ) {
            $live_id = $res['body']['id'] ?? $existing_post_id;
            $live_link = $res['body']['link'] ?? ($this->live_url . '/' . $p->post_name . '/');
            echo "[THÀNH CÔNG] Đã {$action} bài viết thành công lên Live!\n";
            echo " - Live Post ID: {$live_id}\n";
            echo " - Live URL: {$live_link}\n";
            return true;
        } else {
            echo "[LỖI] Không thể đăng bài lên Live (Mã: {$res['code']})!\n";
            if ( ! empty( $res['body']['message'] ) ) {
                echo " - Chi tiết: " . $res['body']['message'] . "\n";
            }
            return false;
        }
    }
}

// =========================================================================
// XỬ LÝ DÒNG LỆNH (CLI RUNNER)
// =========================================================================
if ( php_sapi_name() === 'cli' && isset( $argv ) ) {
    $options = getopt( '', [ 'test', 'post_id:', 'user:', 'pass:', 'live_url:' ] );

    $user     = $options['user'] ?? null;
    $pass     = $options['pass'] ?? null;
    $live_url = $options['live_url'] ?? 'https://sunshine-group.com.vn';

    $sync = new SunshineLiveSync( $user, $pass, $live_url );

    if ( isset( $options['test'] ) ) {
        $sync->test_connection();
        exit;
    }

    if ( ! empty( $options['post_id'] ) ) {
        $sync->sync_post( intval( $options['post_id'] ) );
        exit;
    }

    echo "Cách dùng:\n";
    echo "  php scripts/sync_to_live.php --test\n";
    echo "  php scripts/sync_to_live.php --post_id=<ID_BAI_VIET>\n";
    echo "  php scripts/sync_to_live.php --post_id=<ID> --user=<admin> --pass=\"<mat_khau_api>\"\n";
}
