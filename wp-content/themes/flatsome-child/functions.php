<?php
/**
 * Flatsome Child Theme Functions
 * Thư mục này dùng để thêm các tính năng và code PHP tùy chỉnh an toàn mà không lo bị mất khi cập nhật Flatsome theme.
 */

// Kế thừa style từ parent theme
add_action( 'wp_enqueue_scripts', 'flatsome_child_enqueue_styles', 20 );
function flatsome_child_enqueue_styles() {
    wp_enqueue_style( 'flatsome-child-style', get_stylesheet_uri(), array( 'flatsome-main' ), filemtime( get_stylesheet_directory() . '/style.css' ) );
}

/**
 * Tối ưu SEO & Bảo mật cơ bản
 */
// Ẩn phiên bản WordPress để bảo mật
remove_action('wp_head', 'wp_generator');

// Bật hỗ trợ thẻ title tag động cho SEO
add_theme_support( 'title-tag' );

/**
 * Chặn chuyển hướng canonical ra domain online khi đang chạy trên localhost
 */
add_filter( 'redirect_canonical', function( $redirect_url, $requested_url ) {
    if ( strpos( $_SERVER['HTTP_HOST'] ?? '', 'localhost' ) !== false ) {
        if ( strpos( $redirect_url, 'sunshine-group.com.vn' ) !== false ) {
            return false;
        }
    }
    return $redirect_url;
}, 10, 2 );

/**
 * Tối ưu Admin Bar: Mở trang chủ ở Tab mới để không bị mất trang quản trị đang làm việc
 */
add_action( 'wp_before_admin_bar_render', function() {
    global $wp_admin_bar;
    if ( ! is_object( $wp_admin_bar ) ) {
        return;
    }

    $node = $wp_admin_bar->get_node( 'site-name' );
    if ( $node ) {
        $meta = (array) $node->meta;
        $meta['target'] = '_blank';
        $meta['title'] = 'Xem trang chủ website trong tab mới';
        $node->meta = $meta;
        $node->href = home_url( '/' );
        $wp_admin_bar->add_node( (array) $node );
    }

    $view_site = $wp_admin_bar->get_node( 'view-site' );
    if ( $view_site ) {
        $view_meta = (array) $view_site->meta;
        $view_meta['target'] = '_blank';
        $view_site->meta = $view_meta;
        $wp_admin_bar->add_node( (array) $view_site );
    }
} );

/**
 * Tối ưu Bất Động Sản: Ẩn hoàn toàn Ngày tháng & Tác giả (Evergreen Content)
 */
if ( ! function_exists( 'flatsome_posted_on' ) ) {
    function flatsome_posted_on() {
        // Không xuất ngày tháng và author để giữ bài viết luôn mới và chuyên nghiệp
        return;
    }
}

/**
 * Loại bỏ tiền tố "Lưu trữ thẻ:" / "Tag Archives:" / "Category Archives:"
 */
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
add_filter( 'get_the_archive_title', function( $title ) {
    if ( is_category() ) {
        $title = single_cat_title( '', false );
    } elseif ( is_tag() ) {
        $title = single_tag_title( '', false );
    } elseif ( is_author() ) {
        $title = get_the_author();
    } elseif ( is_tax() ) {
        $title = single_term_title( '', false );
    }
    return $title;
} );



/**
 * Tải Google Fonts chuẩn nhận diện thương hiệu BĐS Cao Cấp:
 * - Playfair Display: Tiêu đề lớn (H1, H2), Logo
 * - Plus Jakarta Sans: Menu, Nút bấm, Nội dung (Body), Giá tiền, Diện tích
 */
add_action( 'wp_enqueue_scripts', 'kbds_enqueue_luxury_fonts', 5 );
function kbds_enqueue_luxury_fonts() {
    wp_enqueue_style(
        'google-fonts-luxury',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600;1,700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600;1,700&family=Montserrat:wght@600;700;800&display=swap',
        array(),
        null
    );
}

// Bổ sung preconnect để tải font nhanh vượt trội
add_action( 'wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 1 );


/**
 * =========================================================================
 * BẬT MẬT KHẨU ỨNG DỤNG (APPLICATION PASSWORDS) KỂ CẢ TRÊN LOCALHOST
 * =========================================================================
 */
add_filter( 'wp_is_application_passwords_available', '__return_true' );
add_filter( 'wp_is_application_passwords_available_for_user', '__return_true' );
add_filter( 'wp_is_application_password_available', '__return_true' );
add_filter( 'wp_is_application_password_available_for_user', '__return_true' );

/**
 * =========================================================================
 * TỰ ĐỘNG CHUYỂN LINK LOCALHOST THÀNH DOMAIN CHÍNH KHI ĐƯA LÊN SERVER THẬT
 * =========================================================================
 */
add_filter( 'the_content', 'kbds_replace_localhost_urls_on_live', 99 );
add_filter( 'widget_text', 'kbds_replace_localhost_urls_on_live', 99 );
function kbds_replace_localhost_urls_on_live( $content ) {
    if ( ! empty( $content ) && strpos( home_url(), 'localhost' ) === false && strpos( home_url(), '127.0.0.1' ) === false ) {
        $content = str_replace( 'http://localhost/www/Sunshinegroup', home_url(), $content );
        $content = str_replace( 'http:\/\/localhost\/www\/Sunshinegroup', str_replace( '/', '\/', home_url() ), $content );
        $content = str_replace( 'http://localhost', home_url(), $content );
    }
    return $content;
}
