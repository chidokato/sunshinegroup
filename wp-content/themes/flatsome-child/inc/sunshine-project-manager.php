<?php
/**
 * Plugin Name: Quản lý Dự án BĐS Sunshine (Sunshine Project Manager)
 * Description: Hệ thống quản lý chuyên sâu Dự án BĐS (Custom Post Type du_an), tự động hiển thị ra Trang Chủ theo chuyên mục, hỗ trợ Flatsome UX Builder và tự động chuyển hướng 301 chuẩn SEO.
 * Version: 1.0.0
 * Author: Sunshine Group
 * Text Domain: sunshine-project-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sunshine_Project_Manager {

    public static function init() {
        add_action('init', [__CLASS__, 'register_kbds_shortcodes'], 6);
        add_action( 'init', [ __CLASS__, 'register_post_type' ], 5 );
        add_action( 'init', [ __CLASS__, 'register_taxonomies' ], 6 );
        add_action( 'init', [ __CLASS__, 'add_default_terms' ], 10 );
        
        // Bật Flatsome UX Builder cho post type du_an
        add_action( 'init', [ __CLASS__, 'enable_ux_builder' ], 20 );

        // Hỗ trợ Thẻ Tags (post_tag) cho post type du_an
        register_taxonomy_for_object_type( 'post_tag', 'du_an' );

        // Tự động liên kết 2 chiều giữa Dự Án BĐS và Bài Viết Tin Tức thông qua Thẻ Tag
        add_filter( 'the_content', [ __CLASS__, 'enhance_content_with_project_links' ] );

        // Nạp toàn bộ CSS giao diện Sunshine chuẩn (Header, Logo, Grid 4 cột, 1340px)
        add_action( 'wp_head', [ __CLASS__, 'output_sunshine_styles' ], 99 );

        // Chân trang Luxury & Bản quyền Luu Van Mui
        add_filter( 'theme_mod_footer_left_text', [ __CLASS__, 'filter_footer_copyright_text' ], 99 );
        add_filter( 'theme_mod_footer_1', '__return_false', 99 );
        add_filter( 'theme_mod_footer_2', '__return_false', 99 );
        add_action( 'flatsome_before_footer', [ __CLASS__, 'render_sunshine_luxury_footer' ] );
        add_action( 'wp_footer', [ __CLASS__, 'output_sunshine_subnav_scripts' ], 99 );

        // Shortcode tự động hiển thị dự án ra Trang Chủ
        add_shortcode( 'sunshine_du_an', [ __CLASS__, 'render_projects_shortcode' ] );
        add_shortcode( 'sunshine_projects', [ __CLASS__, 'render_projects_shortcode' ] );

        // API an toàn cho phép đồng bộ hình ảnh trực tiếp vào wp-content/uploads/
        add_action( 'rest_api_init', [ __CLASS__, 'register_asset_sync_route' ] );

        // Tự động chuyển hướng 301 từ URL cũ (page) sang URL mới /du-an/
        add_action( 'template_redirect', [ __CLASS__, 'handle_301_redirects' ], 1 );

        // Đồng bộ Meta SEO Rank Math trực tiếp cho các dự án Cốt trụ vào Database
        add_action( 'init', [ __CLASS__, 'sync_pillar_seo_meta_to_db' ], 25 );

        // Tối ưu hóa Title & Meta Description chuẩn CTR cao cho Dự Án BĐS
        add_filter( 'rank_math/frontend/title', [ __CLASS__, 'filter_project_seo_title' ], 99 );
        add_filter( 'rank_math/frontend/description', [ __CLASS__, 'filter_project_seo_description' ], 99 );
        add_filter( 'pre_get_document_title', [ __CLASS__, 'filter_project_seo_title' ], 99 );
        add_filter( 'wp_title', [ __CLASS__, 'filter_project_seo_title' ], 99 );
        add_filter( 'rank_math/opengraph/facebook/title', [ __CLASS__, 'filter_project_seo_title' ], 99 );
        add_filter( 'rank_math/opengraph/twitter/title', [ __CLASS__, 'filter_project_seo_title' ], 99 );

        // Quản lý cột hiển thị và bộ lọc trong Admin
        add_filter( 'manage_du_an_posts_columns', [ __CLASS__, 'admin_columns' ] );
        add_action( 'manage_du_an_posts_custom_column', [ __CLASS__, 'admin_column_content' ], 10, 2 );
        add_action( 'restrict_manage_posts', [ __CLASS__, 'admin_filter_dropdowns' ] );

        // Menu quản lý Leads khách hàng đăng ký
        add_action( 'admin_menu', [ __CLASS__, 'register_admin_menus' ] );

                // Cấu hình PHPMailer gửi email qua SMTP chuẩn
        add_action( 'phpmailer_init', [ __CLASS__, 'configure_phpmailer_smtp' ] );

        // ĐỒNG BỘ TOÀN DIỆN CÁC FORM CŨ (Contact Form 7) VÀO HỆ THỐNG CRM & GỬI MAIL/TELEGRAM
        add_action( 'wpcf7_before_send_mail', [ __CLASS__, 'sync_cf7_submission_to_crm' ], 10, 3 );
        add_action( 'wpcf7_submit', [ __CLASS__, 'sync_cf7_submission_to_crm' ], 10, 2 );
        add_filter( 'wpcf7_mail_components', [ __CLASS__, 'filter_cf7_mail_recipient' ], 10, 2 );

        // Chống chặn oan khách thật: Khách có số điện thoại Việt Nam hợp lệ sẽ không bị đánh dấu Spam
        add_filter( 'wpcf7_spam', function( $spam, $submission ) {
            if ( $submission ) {
                $posted = $submission->get_posted_data();
                $phone = $posted['your-phone'] ?? $posted['your-tel'] ?? $posted['phone'] ?? '';
                $clean = preg_replace( '/[^0-9]/', '', $phone );
                if ( strlen( $clean ) >= 9 && strlen( $clean ) <= 11 && substr( $clean, 0, 1 ) === '0' ) {
                    return false;
                }
            }
            return $spam;
        }, 99, 2 );

        // Meta Box thông tin nhanh cho dự án
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_du_an', [ __CLASS__, 'save_meta_boxes' ] );
        add_action( 'save_post_du_an', [ __CLASS__, 'clear_project_cache' ] );
        add_action( 'deleted_post', [ __CLASS__, 'clear_project_cache' ] );
        add_action( 'trashed_post', [ __CLASS__, 'clear_project_cache' ] );

        // Loại bỏ mã rác trong wp_head để tăng tốc website
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'wp_shortlink_wp_head' );

        // Việt hóa placeholder ô tìm kiếm
        add_filter( 'get_search_form', function( $form ) {
            return str_replace( 'Search&hellip;', 'Tìm kiếm dự án, bài viết...', $form );
        } );
    }

    /**
     * 1. Đăng ký Custom Post Type: Dự án BĐS (du_an)
     */
    public static function register_post_type() {
        $labels = [
            'name'               => 'Dự án BĐS',
            'singular_name'      => 'Dự án',
            'menu_name'          => 'Dự án BĐS',
            'all_items'          => 'Tất cả Dự án',
            'add_new'            => 'Thêm mới',
            'add_new_item'       => 'Thêm Dự án mới',
            'edit_item'          => 'Chỉnh sửa Dự án',
            'new_item'           => 'Dự án mới',
            'view_item'          => 'Xem Dự án',
            'search_items'       => 'Tìm kiếm Dự án',
            'not_found'          => 'Không tìm thấy dự án nào',
            'not_found_in_trash' => 'Thùng rác trống',
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'du-an', 'with_front' => false ],
            'capability_type'    => 'post',
            'has_archive'        => 'du-an',
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-building',
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ],
            'taxonomies'         => [ 'danh_muc_du_an', 'khu_vuc_du_an', 'loai_hinh_du_an', 'trang_thai_du_an', 'post_tag' ],
            'show_in_rest'       => true,
        ];

        register_post_type( 'du_an', $args );
    }

    /**
     * 2. Đăng ký Taxonomies (Chuyên mục, Khu vực, Loại hình, Trạng thái)
     */
    public static function register_taxonomies() {
        // Chuyên mục Dự án (Dự án Sunshine / Dự án Liên kết)
        register_taxonomy( 'danh_muc_du_an', [ 'du_an' ], [
            'hierarchical'      => true,
            'labels'            => [
                'name'          => 'Chuyên mục Dự án',
                'singular_name' => 'Chuyên mục',
                'search_items'  => 'Tìm Chuyên mục',
                'all_items'     => 'Tất cả Chuyên mục',
                'parent_item'   => 'Chuyên mục cha',
                'edit_item'     => 'Sửa Chuyên mục',
                'update_item'   => 'Cập nhật Chuyên mục',
                'add_new_item'  => 'Thêm Chuyên mục mới',
                'new_item_name' => 'Tên Chuyên mục mới',
                'menu_name'     => 'Chuyên mục Dự án',
            ],
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'chuyen-muc-du-an', 'with_front' => false ],
        ] );

        // Khu vực Dự án
        register_taxonomy( 'khu_vuc_du_an', [ 'du_an' ], [
            'hierarchical'      => true,
            'labels'            => [
                'name'          => 'Khu vực',
                'singular_name' => 'Khu vực',
                'menu_name'     => 'Khu vực',
            ],
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'khu-vuc-du-an', 'with_front' => false ],
        ] );

        // Loại hình Dự án
        register_taxonomy( 'loai_hinh_du_an', [ 'du_an' ], [
            'hierarchical'      => true,
            'labels'            => [
                'name'          => 'Loại hình BĐS',
                'singular_name' => 'Loại hình',
                'menu_name'     => 'Loại hình',
            ],
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'loai-hinh-du-an', 'with_front' => false ],
        ] );

        // Trạng thái Dự án
        register_taxonomy( 'trang_thai_du_an', [ 'du_an' ], [
            'hierarchical'      => true,
            'labels'            => [
                'name'          => 'Trạng thái',
                'singular_name' => 'Trạng thái',
                'menu_name'     => 'Trạng thái',
            ],
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
        ] );

        // Chủ đầu tư (Developer) - Chuẩn như kenhbatdongsan.vn
        register_taxonomy( 'chu_dau_tu', [ 'du_an' ], [
            'hierarchical'      => true,
            'labels'            => [
                'name'          => 'Chủ đầu tư',
                'singular_name' => 'Chủ đầu tư',
                'search_items'  => 'Tìm Chủ đầu tư',
                'all_items'     => 'Tất cả Chủ đầu tư',
                'parent_item'   => 'Chủ đầu tư cha',
                'edit_item'     => 'Sửa Chủ đầu tư',
                'update_item'   => 'Cập nhật Chủ đầu tư',
                'add_new_item'  => 'Thêm Chủ đầu tư mới',
                'new_item_name' => 'Tên Chủ đầu tư mới',
                'menu_name'     => 'Chủ đầu tư',
            ],
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'chu-dau-tu', 'with_front' => false ],
        ] );

        // Đề xuất liên kết - Chuẩn như kenhbatdongsan.vn
        register_taxonomy( 'de_xuat_lien_ket', [ 'du_an', 'post' ], [
            'hierarchical'      => false,
            'labels'            => [
                'name'          => 'Đề xuất Liên kết',
                'singular_name' => 'Đề xuất Liên kết',
                'search_items'  => 'Tìm Đề xuất',
                'all_items'     => 'Tất cả Đề xuất',
                'edit_item'     => 'Sửa Đề xuất',
                'update_item'   => 'Cập nhật Đề xuất',
                'add_new_item'  => 'Thêm Đề xuất mới',
                'new_item_name' => 'Tên Đề xuất mới',
                'menu_name'     => 'Đề xuất Liên kết',
            ],
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
        ] );
    }

    /**
     * Khởi tạo các Terms mặc định (Chỉ chạy 1 lần duy nhất, tránh truy vấn DB lặp lại)
     */
    public static function add_default_terms() {
        if ( get_option( 'sunshine_default_terms_initialized' ) ) {
            return;
        }

        if ( ! term_exists( 'Dự án Sunshine', 'danh_muc_du_an' ) ) {
            wp_insert_term( 'Dự án Sunshine', 'danh_muc_du_an', [
                'slug'        => 'du-an-sunshine',
                'description' => 'Dự án bất động sản do Tập đoàn Sunshine Group trực tiếp phát triển',
            ] );
        }

        if ( ! term_exists( 'Dự án Liên kết', 'danh_muc_du_an' ) ) {
            wp_insert_term( 'Dự án Liên kết', 'danh_muc_du_an', [
                'slug'        => 'du-an-lien-ket',
                'description' => 'Dự án bất động sản liên kết đầu tư & phân phối chiến lược',
            ] );
        }

        update_option( 'sunshine_default_terms_initialized', 1 );
    }

    /**
     * 3. Bật Flatsome UX Builder cho post type du_an
     */
    public static function enable_ux_builder() {
        if ( function_exists( 'add_ux_builder_post_type' ) ) {
            add_ux_builder_post_type( 'du_an' );
        }
        $GLOBALS['_ux_builder_post_types']['du_an'] = true;
    }

    /**
     * Tối ưu SEO Title kích thích CTR cao cho Dự Án BĐS
     */
    public static function filter_project_seo_title( $title ) {
        $post_id = get_queried_object_id() ?: get_the_ID();
        if ( $post_id && get_post_type( $post_id ) === 'du_an' ) {
            $custom_title = get_post_meta( $post_id, 'rank_math_title', true );
            if ( ! empty( $custom_title ) ) {
                return $custom_title;
            }
            $clean_title = get_the_title( $post_id );
            $clean_title = html_entity_decode( $clean_title, ENT_QUOTES, 'UTF-8' );
            $clean_title = preg_replace( '/\s*[-–|].*$/u', '', $clean_title );
            return trim( $clean_title ) . ' – Bảng Giá Gốc CĐT & CSBH [2026]';
        }
        return $title;
    }

    /**
     * Tối ưu Meta Description chuẩn SEO cho Dự Án BĐS
     */
    public static function filter_project_seo_description( $desc ) {
        $post_id = get_queried_object_id() ?: get_the_ID();
        if ( $post_id && get_post_type( $post_id ) === 'du_an' ) {
            $custom_desc = get_post_meta( $post_id, 'rank_math_description', true );
            if ( ! empty( $custom_desc ) ) {
                return $custom_desc;
            }
            $clean_title = get_the_title( $post_id );
            $clean_title = html_entity_decode( $clean_title, ENT_QUOTES, 'UTF-8' );
            $clean_title = preg_replace( '/\s*[-–|].*$/u', '', $clean_title );
            return 'Thông tin chính thức ' . trim( $clean_title ) . '. Cập nhật bảng giá gốc đợt 1, chính sách bán hàng 2026, mặt bằng thiết kế và hồ sơ pháp lý 1/500 trực tiếp từ Chủ đầu tư.';
        }
        return $desc;
    }

    /**
     * Đồng bộ Meta SEO Rank Math trực tiếp vào Database cho các dự án Cốt trụ
     */
    public static function sync_pillar_seo_meta_to_db() {
        // Đảm bảo email nhận thông báo mặc định và đồng bộ admin_email
        if ( ! get_option( 'sunshine_lead_notification_emails' ) ) {
            update_option( 'sunshine_lead_notification_emails', 'luumui1234@gmail.com' );
        }
        if ( get_option( 'new_admin_email' ) === 'luumui1234@gmail.com' ) {
            update_option( 'admin_email', 'luumui1234@gmail.com' );
            delete_option( 'new_admin_email' );
            delete_option( 'adminhash' );
        }

        // Đồng bộ người nhận email cho các Form Contact Form 7 cũ (Form 782, 11)
        foreach ( [ 782, 11 ] as $fid ) {
            $mail_meta = get_post_meta( $fid, '_mail', true );
            if ( is_array( $mail_meta ) ) {
                $mail_meta['recipient'] = 'luumui1234@gmail.com';
                update_post_meta( $fid, '_mail', $mail_meta );
            }
        }

        $key = 'sunshine_pillar_seo_synced_v2';
        if ( get_option( $key ) ) {
            return;
        }

        $pillar_seo_data = [
            965 => [
                'title'       => 'Dự Án Handico Sunshine New Hà Nội | Bảng Giá Gốc CĐT 2026',
                'description' => 'Thông tin chính thức dự án Handico Sunshine New Hà Nội (Bắc Thăng Long - Mê Linh). Nhận bảng giá gốc đợt 1, chính sách bán hàng 2026 & hồ sơ pháp lý 1/500 trực tiếp Chủ đầu tư.',
                'focuskw'     => 'Handico Sunshine New Hà Nội, dự án Handico Sunshine New Hà Nội, Sunshine New Hà Nội',
            ],
            1349 => [
                'title'       => 'Dự Án Noble Palace Long Biên | Bảng Giá Biệt Thự Gốc CĐT 2026',
                'description' => 'Dự án Noble Palace Long Biên – Đỉnh cao dinh thự hoàng gia Sunshine Group. Cập nhật bảng giá gốc 2026, mặt bằng phân lô & chính sách ưu đãi trực tiếp F1.',
                'focuskw'     => 'Noble Palace Long Biên, dự án Noble Palace Long Biên, biệt thự Noble Palace Long Biên',
            ],
            851 => [
                'title'       => 'Dự Án Lalalay Residences Bắc An Khánh | Bảng Giá Gốc CĐT 2026',
                'description' => 'Thông tin dự án Lalalay Residences Bắc An Khánh (Lại Yên). Bảng giá gốc đợt 1, mặt bằng căn hộ cao cấp và chính sách thanh toán ưu đãi trực tiếp CĐT.',
                'focuskw'     => 'Lalalay Residences, Lalalay Residences Bắc An Khánh, căn hộ Lalalay Residences',
            ],
        ];

        foreach ( $pillar_seo_data as $pid => $data ) {
            if ( get_post( $pid ) ) {
                update_post_meta( $pid, 'rank_math_title', $data['title'] );
                update_post_meta( $pid, 'rank_math_description', $data['description'] );
                update_post_meta( $pid, 'rank_math_focus_keyword', $data['focuskw'] );
            }
        }

        update_option( $key, time() );
    }

    /**
     * 4. Shortcode tự động hiển thị danh sách dự án ra Trang Chủ (Có bộ nhớ đệm Transient siêu tốc)
     * Cú pháp: [sunshine_du_an category="du-an-sunshine" columns="3" count="6"]
     */
    public static function render_projects_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'category' => 'du-an-sunshine',
            'columns'  => '4',
            'count'    => '8',
            'orderby'  => 'menu_order date',
            'order'    => 'ASC',
        ], $atts );

        $query_args = [
            'post_type'      => 'du_an',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['count'] ),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'no_found_rows'  => true,
        ];

        if ( ! empty( $atts['category'] ) ) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'danh_muc_du_an',
                    'field'    => 'slug',
                    'terms'    => $atts['category'],
                ]
            ];
        }

        $projects = new WP_Query( $query_args );

        if ( ! $projects->have_posts() ) {
            return '<p style="text-align: center; color: #888; font-style: italic;">Đang cập nhật danh sách dự án...</p>';
        }

        $cols = intval( $atts['columns'] );
        if ( $cols <= 0 ) $cols = 4;
        $grid_class = ( 3 === $cols ) ? 'kbds-grid-3' : 'kbds-grid-4';

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $grid_class ); ?> kbds-home-projects-grid">
            <?php while ( $projects->have_posts() ) : $projects->the_post(); 
                $post_id   = get_the_ID();
                $title     = get_the_title();
                $link      = get_permalink();
                $thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );
                
                // Lấy vị trí dự án
                $location = get_post_meta( $post_id, '_project_location', true ) ?: get_post_meta( $post_id, '_kbds_address', true );
                if ( empty( $location ) ) {
                    $terms = get_the_terms( $post_id, 'khu_vuc_du_an' );
                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                        $location = $terms[0]->name;
                    } else {
                        $location = 'Hà Nội';
                    }
                }

                // Lấy chủ đầu tư
                $cdts = wp_get_post_terms( $post_id, 'chu_dau_tu', array( 'fields' => 'names' ) );
                if ( ! empty( $cdts ) && ! is_wp_error( $cdts ) ) {
                    $cdt_label = $cdts[0];
                } else {
                    if ( stripos( $title, 'Sunshine' ) !== false || stripos( $title, 'Handico' ) !== false ) {
                        $cdt_label = 'Tập đoàn Sunshine';
                    } elseif ( stripos( $title, 'President Park' ) !== false || stripos( $title, 'CityLand' ) !== false ) {
                        $cdt_label = 'CityLand Group';
                    } elseif ( stripos( $title, 'Hinode' ) !== false ) {
                        $cdt_label = 'WTO (Vietracimex)';
                    } elseif ( stripos( $title, 'Lalalay' ) !== false ) {
                        $cdt_label = 'Ramond Holdings';
                    } elseif ( stripos( $title, 'Vinhomes' ) !== false ) {
                        $cdt_label = 'Vinhomes';
                    } elseif ( stripos( $title, 'Astor' ) !== false ) {
                        $cdt_label = 'Kita Group';
                    } else {
                        $cdt_label = 'Chủ đầu tư uy tín';
                    }
                }

                // Lấy giá tham khảo
                $price = get_post_meta( $post_id, '_kbds_price_from', true ) ?: get_post_meta( $post_id, '_project_price', true );
                if ( empty( $price ) ) {
                    if ( stripos( $title, 'Sunshine New Hà Nội' ) !== false || stripos( $title, 'Metropolis' ) !== false ) {
                        $price = 'Từ 45 Triệu/m²';
                    } elseif ( stripos( $title, 'Noble West Lake' ) !== false ) {
                        $price = 'Từ 150 Triệu/m²';
                    } elseif ( stripos( $title, 'Hinode' ) !== false ) {
                        $price = 'Đang cập nhật (Mở bán Đợt 1)';
                    } elseif ( stripos( $title, 'President Park' ) !== false ) {
                        $price = 'Từ 35 Tỷ/Căn';
                    } elseif ( stripos( $title, 'Lalalay' ) !== false ) {
                        $price = 'Đang cập nhật (Mở bán Đợt 1 Tòa LALA 1)';
                    } else {
                        $price = 'Đang cập nhật';
                    }
                }

                // Trạng thái dự án
                $status_terms = wp_get_post_terms( $post_id, 'trang_thai_du_an', array( 'fields' => 'names' ) );
                $status_label = ! empty( $status_terms ) && ! is_wp_error( $status_terms ) ? $status_terms[0] : 'Đang mở bán';
                $status_map = [
                    'dang-mo-ban' => 'Đang mở bán',
                    'sap-mo-ban'  => 'Sắp mở bán',
                    'da-ban-giao' => 'Đã bàn giao',
                    'hot'         => 'Đang mở bán',
                ];
                if ( isset( $status_map[$status_label] ) ) {
                    $status_label = $status_map[$status_label];
                }
            ?>
            <article class="kbds-card kbds-project-card">
                <div class="kbds-card-media">
                    <a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $title ); ?>">
                        <?php if ( $thumb_url ) : ?>
                            <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
                        <?php else : ?>
                            <div class="kbds-img-placeholder" style="width:100%;height:100%;background:linear-gradient(135deg,#0B2C4D 0%,#1E5F74 100%);"></div>
                        <?php endif; ?>
                    </a>
                    <span class="kbds-badge-status"><?php echo esc_html( $status_label ); ?></span>
                </div>

                <div class="kbds-card-body">
                    <div class="kbds-card-meta-top">
                        <span class="kbds-developer-tag">🏛️ <?php echo esc_html( $cdt_label ); ?></span>
                    </div>
                    <h3 class="kbds-card-title">
                        <a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $title ); ?></a>
                    </h3>
                    <p class="kbds-card-location">📍 <?php echo esc_html( $location ); ?></p>

                    <div class="kbds-card-footer">
                        <div class="kbds-price-block">
                            <span class="kbds-price-label">Giá tham khảo</span>
                            <strong class="kbds-price-value"><?php echo esc_html( $price ); ?></strong>
                        </div>
                        <a href="<?php echo esc_url( $link ); ?>" class="kbds-btn-card">Chi tiết →</a>
                    </div>
                </div>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php
        $rendered_html = ob_get_clean();
        return $rendered_html;
    }

    /**
     * 5. Chuyển hướng 301 tự động từ URL cũ sang /du-an/
     * Chỉ kiểm tra khi trang bị lỗi 404 để không làm chậm các trang bình thường
     */
    public static function handle_301_redirects() {
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        // Chỉ kiểm tra khi WordPress không tìm thấy trang (404)
        if ( ! is_404() ) {
            return;
        }

        // Lấy đường dẫn yêu cầu
        $request_uri = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
        
        // Loại bỏ base path nếu chạy trong subfolder (như /www/Sunshinegroup/)
        $site_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
        if ( ! empty( $site_path ) && strpos( $request_uri, $site_path ) === 0 ) {
            $request_uri = trim( substr( $request_uri, strlen( $site_path ) ), '/' );
        }

        // Nếu request không bắt đầu bằng 'du-an/' và chỉ có 1 segment slug
        if ( ! empty( $request_uri ) && strpos( $request_uri, 'du-an/' ) !== 0 && strpos( $request_uri, '/' ) === false ) {
            $slug = sanitize_title( $request_uri );

            // 1. Kiểm tra danh sách ánh xạ gộp bài viết con sang Dự án lớn
            $redirect_map = get_option( 'sunshine_project_redirect_map', [] );
            if ( isset( $redirect_map[ $slug ] ) ) {
                $target_path = $redirect_map[ $slug ];
                $target_url  = home_url( $target_path );
                wp_redirect( $target_url, 301 );
                exit;
            }
            
            // 2. Kiểm tra xem slug này có thuộc post type 'du_an' không
            $project = get_page_by_path( $slug, OBJECT, 'du_an' );
            if ( $project && $project->post_status === 'publish' ) {
                $target_url = get_permalink( $project->ID );
                wp_redirect( $target_url, 301 );
                exit;
            }
        }
    }

    /**
     * 6. Cột thông minh trong màn hình Admin Dự án BĐS
     */
    public static function admin_columns( $columns ) {
        $new_columns = [];
        foreach ( $columns as $key => $title ) {
            if ( $key === 'title' ) {
                $new_columns['thumb'] = 'Ảnh';
                $new_columns['title'] = $title;
                $new_columns['danh_muc'] = 'Chuyên mục';
                $new_columns['khu_vuc'] = 'Khu vực';
            } elseif ( $key !== 'date' ) {
                $new_columns[ $key ] = $title;
            }
        }
        $new_columns['date'] = 'Ngày đăng';
        return $new_columns;
    }

    public static function admin_column_content( $column, $post_id ) {
        if ( $column === 'thumb' ) {
            $thumb = get_the_post_thumbnail( $post_id, [ 50, 50 ], [ 'style' => 'border-radius: 4px; object-fit: cover;' ] );
            echo $thumb ? $thumb : '<span style="color:#bbb;">—</span>';
        } elseif ( $column === 'danh_muc' ) {
            $terms = get_the_term_list( $post_id, 'danh_muc_du_an', '', ', ' );
            echo $terms ? $terms : '<span style="color:#bbb;">—</span>';
        } elseif ( $column === 'khu_vuc' ) {
            $terms = get_the_term_list( $post_id, 'khu_vuc_du_an', '', ', ' );
            echo $terms ? $terms : '<span style="color:#bbb;">—</span>';
        }
    }

    public static function admin_filter_dropdowns( $post_type ) {
        if ( $post_type !== 'du_an' ) {
            return;
        }

        $taxonomies = [ 'danh_muc_du_an', 'khu_vuc_du_an' ];
        foreach ( $taxonomies as $tax ) {
            $selected = isset( $_GET[ $tax ] ) ? $_GET[ $tax ] : '';
            $info_tax = get_taxonomy( $tax );
            wp_dropdown_categories( [
                'show_option_all' => "Tất cả {$info_tax->labels->name}",
                'taxonomy'        => $tax,
                'name'            => $tax,
                'orderby'         => 'name',
                'selected'        => $selected,
                'show_count'      => true,
                'hide_empty'      => false,
                'value_field'     => 'slug',
            ] );
        }
    }

    /**
     * 7. Meta Box Vị trí / Địa chỉ hiển thị dưới thẻ dự án
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'sunshine_project_details',
            'Thông tin Hiển thị Dự án',
            [ __CLASS__, 'render_meta_box' ],
            'du_an',
            'side',
            'default'
        );
    }

    public static function render_meta_box( $post ) {
        wp_nonce_field( 'sunshine_save_project_meta', 'sunshine_project_meta_nonce' );
        $location = get_post_meta( $post->ID, '_project_location', true );
        ?>
        <p>
            <label for="project_location"><strong>Vị trí / Địa chỉ ngắn:</strong></label><br>
            <input type="text" id="project_location" name="project_location" value="<?php echo esc_attr( $location ); ?>" style="width: 100%; margin-top: 5px;" placeholder="Ví dụ: KĐT Ciputra, Tây Hồ - Hà Nội">
            <span style="font-size: 12px; color: #777;">(Dòng chữ hiển thị dưới tên dự án ở trang chủ)</span>
        </p>
        <?php
    }

    public static function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['sunshine_project_meta_nonce'] ) || ! wp_verify_nonce( $_POST['sunshine_project_meta_nonce'], 'sunshine_save_project_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( isset( $_POST['project_location'] ) ) {
            update_post_meta( $post_id, '_project_location', sanitize_text_field( $_POST['project_location'] ) );
        }
    }

    /**
     * 8. Tự động xóa bộ nhớ đệm Cache khi cập nhật/xóa/thêm mới dự án
     */
    public static function clear_project_cache() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sunshine_du_an_%' OR option_name LIKE '_transient_timeout_sunshine_du_an_%'" );

        // Xóa Super Cache nếu có
        if ( function_exists( 'wp_cache_clean_cache' ) ) {
            global $file_prefix;
            wp_cache_clean_cache( $file_prefix, true );
        }
    }

    /**
     * 9. Tự động liên kết 2 chiều giữa Dự Án BĐS và Bài Viết Tin Tức thông qua Thẻ Tag
     */
    public static function enhance_content_with_project_links( $content ) {
        static $is_running = false;
        if ( $is_running || is_admin() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }
        $is_running = true;

        // 1. Khi đang xem bài viết blog (post): Tự động hiển thị Banner dẫn về Trang Dự Án chính
        if ( is_singular( 'post' ) ) {
            $post_id = get_the_ID();
            $tags = wp_get_post_tags( $post_id );
            if ( ! empty( $tags ) ) {
                foreach ( $tags as $tag ) {
                    // Tìm dự án du_an có cùng slug hoặc tag
                    $project = get_page_by_path( $tag->slug, OBJECT, 'du_an' );
                    if ( ! $project ) {
                        // Thử tìm theo tiêu đề
                        $query = new WP_Query([
                            'post_type'      => 'du_an',
                            'post_status'    => 'publish',
                            'posts_per_page' => 1,
                            's'              => $tag->name,
                            'no_found_rows'  => true,
                        ]);
                        if ( $query->have_posts() ) {
                            $project = $query->posts[0];
                        }
                    }

                    if ( $project && $project->post_status === 'publish' && $project->ID !== $post_id ) {
                        $p_url = get_permalink( $project->ID );
                        $p_title = get_the_title( $project->ID );
                        $safe_title = esc_html( $p_title );
                        $safe_url   = esc_url( $p_url );
                        
                        $cta_box = '<div class="sunshine-project-cta-banner" style="background: linear-gradient(135deg, #0b2246 0%, #163664 100%); padding: 18px 24px; border-radius: 8px; margin: 30px 0; color: #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">'
                            . '<div style="flex: 1; min-width: 260px;">'
                            . '<div style="font-size: 12px; font-weight: 700; color: #d4af37; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">★ DỰ ÁN LIÊN QUAN</div>'
                            . '<div style="font-size: 15px; font-weight: 600; line-height: 1.4;">Xem thông tin tổng quan, bảng giá & chính sách mở bán mới nhất:</div>'
                            . '<div style="font-size: 17px; font-weight: 700; color: #fff; margin-top: 2px;">' . $safe_title . '</div>'
                            . '</div>'
                            . '<div>'
                            . '<a href="' . $safe_url . '" style="display: inline-block; background: #d4af37; color: #0b2246; font-weight: 700; padding: 12px 22px; border-radius: 4px; text-decoration: none; text-transform: uppercase; font-size: 13.5px; box-shadow: 0 3px 8px rgba(0,0,0,0.15); transition: all 0.3s ease;">Xem Chi Tiết Dự Án &rarr;</a>'
                            . '</div>'
                            . '</div>';

                        $content .= $cta_box;

                        // Tự động chèn form đăng ký tương ứng (cao tầng / thấp tầng) nếu bài viết chưa có form
                        if ( false === strpos( $content, 'kbds-lead-box' ) && false === strpos( $content, 'kbds_lead_form' ) ) {
                            $proj_type = self::get_form_product_type([], $project->ID);
                            $content .= self::render_kbds_lead_form([
                                'project' => $p_title,
                                'type'    => $proj_type,
                                'title'   => '📥 Đăng Ký Nhận Bảng Giá & Mặt Bằng ' . $p_title,
                            ]);
                        }
                        break;
                    }
                }
            }

            // Khối Bài Viết Cùng Chuyên Mục ở cuối bài viết tin tức
            $post_categories = wp_get_post_categories( $post_id );
            if ( ! empty( $post_categories ) ) {
                $related_blog = new WP_Query([
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'category__in'   => $post_categories,
                    'post__not_in'   => [ $post_id ],
                    'no_found_rows'  => true,
                ]);

                if ( $related_blog->have_posts() ) {
                    $rel_html = '<div class="sunshine-related-blog-section" style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #f1f5f9;">'
                        . '<h3 style="font-size: 20px; font-weight: 700; color: #0b2246; text-transform: uppercase; margin-bottom: 22px; border-left: 4px solid #d4af37; padding-left: 12px; letter-spacing: 0.5px;">Bài viết cùng chuyên mục</h3>'
                        . '[row style="small"]';

                    while ( $related_blog->have_posts() ) {
                        $related_blog->the_post();
                        $rb_id = get_the_ID();
                        $rb_thumb = get_post_thumbnail_id( $rb_id );
                        $rb_title = get_the_title();
                        $rb_link = get_permalink();
                        $rb_date = get_the_date('d/m/Y');

                        $rel_html .= sprintf(
                            '[col span="4" span__sm="12"][ux_image_box img="%d" link="%s"]<h5 style="margin-bottom: 5px; font-size: 14px; font-weight: 600; line-height: 1.4;"><a href="%s" style="color: #0b2246;">%s</a></h5><p style="font-size: 12px; color: #888; margin-bottom: 0;">📅 %s</p>[/ux_image_box][/col]',
                            $rb_thumb,
                            esc_url( $rb_link ),
                            esc_url( $rb_link ),
                            esc_html( $rb_title ),
                            esc_html( $rb_date )
                        );
                    }
                    wp_reset_postdata();

                    $rel_html .= '[/row]</div>';
                    $content .= do_shortcode( $rel_html );
                }
            }
        }

        // 2. Khi đang xem một trang Dự Án (du_an): Tự động hiển thị Tin tức & Bài viết liên quan về dự án đó
        if ( is_singular( 'du_an' ) ) {
            // Nếu trong nội dung đã có section tin tức dự án id="tin-tuc-du-an" thì không chèn lặp lại
            if ( false !== strpos( $content, 'id="tin-tuc-du-an"' ) ) {
                return $content;
            }

            $project_id = get_the_ID();
            $tags = wp_get_post_tags( $project_id, [ 'fields' => 'ids' ] );
            
            if ( empty( $tags ) ) {
                $post_slug = get_post_field( 'post_name', $project_id );
                $matching_term = get_term_by( 'slug', $post_slug, 'post_tag' );
                if ( $matching_term ) {
                    $tags = [ $matching_term->term_id ];
                }
            }

            if ( ! empty( $tags ) ) {
                $related_query = new WP_Query([
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 6,
                    'tag__in'        => $tags,
                    'post__not_in'   => [ $project_id ],
                    'no_found_rows'  => true,
                ]);

                if ( $related_query->have_posts() ) {
                    $p_title = get_the_title( $project_id );
                    $news_html = '<section class="kbds-project-news-section" id="tin-tuc-du-an" style="padding:60px 0;background:#F8FAFC;border-top:1px solid #E2E8F0;margin-top:60px;">'
                        . '<div class="kbds-container" style="max-width:1240px;margin:0 auto;padding:0 20px;">'
                        . '<div style="text-align:center;margin-bottom:35px;">'
                        . '<span style="display:inline-block;color:#d4af37;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">★ BÀI VIẾT & TIN TỨC LIÊN QUAN</span>'
                        . '<h2 style="color:#0B2C4D;font-size:26px;font-weight:800;text-transform:uppercase;margin:0 0 10px;">Tin Tức & Đánh Giá ' . esc_html( $p_title ) . '</h2>'
                        . '<div style="width:50px;height:3px;background:#d4af37;margin:0 auto 15px auto;"></div>'
                        . '<p style="color:#64748B;font-size:14.5px;max-width:680px;margin:0 auto;">Tổng hợp các bài viết phân tích vị trí, mặt bằng, tiện ích và tiến độ mới nhất về dự án.</p>'
                        . '</div>'
                        . '[row style="normal" col_bg="#ffffff" col_bg_radius="8"]';

                    while ( $related_query->have_posts() ) {
                        $related_query->the_post();
                        $r_id = get_the_ID();
                        $r_thumb = get_post_thumbnail_id( $r_id );
                        $r_title = get_the_title();
                        $r_link = get_permalink();
                        $curr_p = get_post( $r_id );
                        $raw_txt = ! empty( $curr_p->post_excerpt ) ? $curr_p->post_excerpt : wp_strip_all_tags( $curr_p->post_content );
                        $r_excerpt = wp_trim_words( $raw_txt, 14, '...' );

                        $news_html .= sprintf(
                            '[col span="4" span__md="6" span__sm="12"][ux_image_box img="%d" link="%s" depth="1" depth_hover="3" image_height="60%%" image_hover="zoom"]<div style="padding:15px 14px 18px 14px;text-align:left;"><h5 style="margin-bottom:8px;font-size:15px;font-weight:700;line-height:1.4;"><a href="%s" style="color:#0b2246;">%s</a></h5><p style="font-size:13px;color:#64748b;line-height:1.5;margin-bottom:12px;">%s</p><a href="%s" style="color:#d4af37;font-size:13px;font-weight:700;text-decoration:none;">Xem chi tiết &rarr;</a></div>[/ux_image_box][/col]',
                            $r_thumb,
                            esc_url( $r_link ),
                            esc_url( $r_link ),
                            esc_html( $r_title ),
                            esc_html( $r_excerpt ),
                            esc_url( $r_link )
                        );
                    }
                    wp_reset_postdata();

                    $news_html .= '[/row]</div></section>';
                    $content .= do_shortcode( $news_html );
                }
            }
        }

        $is_running = false;
        return $content;
    }

    // =========================================================================
    // KBDS COMPATIBILITY SHORTCODES: LEAD FORM & PROJECT NEWS SLIDER
    // =========================================================================
    // KBDS SHORTCODES & LANDING ELEMENTS
    // =========================================================================
    public static function register_kbds_shortcodes() {
        add_shortcode('kbds_lead_form', [__CLASS__, 'render_kbds_lead_form']);
        add_shortcode('kbds_lead_form_cao_tang', [__CLASS__, 'render_kbds_lead_form_cao_tang']);
        add_shortcode('kbds_lead_form_thap_tang', [__CLASS__, 'render_kbds_lead_form_thap_tang']);
        add_shortcode('kbds_quick_lead_card', [__CLASS__, 'render_kbds_quick_lead_card']);
        add_shortcode('kbds_quick_lead_card_cao_tang', [__CLASS__, 'render_kbds_quick_lead_card_cao_tang']);
        add_shortcode('kbds_quick_lead_card_thap_tang', [__CLASS__, 'render_kbds_quick_lead_card_thap_tang']);
        add_shortcode('kbds_loan_calculator', [__CLASS__, 'render_kbds_loan_calculator']);
        add_shortcode('kbds_project_news', [__CLASS__, 'render_kbds_project_news']);
        add_action('wp_head', [__CLASS__, 'output_project_json_ld_schema'], 25);
    }

    /**
     * Xác định dòng sản phẩm: cao_tang (chung cư/căn hộ) hoặc thap_tang (liền kề/biệt thự/shophouse)
     */
    public static function get_form_product_type($atts = [], $post_id = 0) {
        if (!empty($atts['type'])) {
            $t = strtolower(trim($atts['type']));
            if (in_array($t, ['thap_tang', 'thap-tang', 'thaptang', 'low-rise', 'lowrise', 'biet_thu', 'shophouse', 'lien_ke'])) {
                return 'thap_tang';
            }
            if (in_array($t, ['cao_tang', 'cao-tang', 'caotang', 'high-rise', 'highrise', 'chung_cu', 'can_ho'])) {
                return 'cao_tang';
            }
        }

        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $haystack = '';
        if (!empty($atts['project'])) {
            $haystack .= ' ' . $atts['project'];
        }
        if ($post_id) {
            $haystack .= ' ' . get_the_title($post_id) . ' ' . get_post_field('post_name', $post_id);
            $terms = wp_get_post_terms($post_id, ['loai_hinh_du_an', 'danh_muc_du_an', 'post_tag']);
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $haystack .= ' ' . $term->name . ' ' . $term->slug;
                }
            }
        }

        $haystack = mb_strtolower($haystack, 'UTF-8');
        $low_rise_keywords = ['liền kề', 'lien ke', 'biệt thự', 'biet thu', 'shophouse', 'thấp tầng', 'thap tang', 'dinh thự', 'dinh thu', 'the president park', 'president-park', 'the grand riveria', 'vinhomes king park', 'handico', 'handico-sunshine'];
        foreach ($low_rise_keywords as $kw) {
            if (mb_strpos($haystack, $kw) !== false) {
                return 'thap_tang';
            }
        }

        return 'cao_tang';
    }

    /**
     * Lấy danh mục nhu cầu chuẩn theo dòng sản phẩm
     */
    public static function get_demand_options($product_type) {
        if ($product_type === 'thap_tang') {
            return [
                'Liền kề'           => 'Liền kề',
                'Biệt thự'          => 'Biệt thự',
                'Shophouse'         => 'Shophouse',
                'Biệt thự Song lập' => 'Biệt thự Song lập',
                'Biệt thự Đơn lập'  => 'Biệt thự Đơn lập',
            ];
        }

        return [
            'Studio'          => 'Studio',
            'Căn hộ 1PN'      => 'Căn hộ 1PN',
            'Căn hộ 2PN'      => 'Căn hộ 2PN',
            'Căn hộ 3PN'      => 'Căn hộ 3PN',
            'Căn hộ Dual Key' => 'Căn hộ Dual Key',
            'Penthouse'       => 'Penthouse / Duplex',
        ];
    }

    /**
     * Cấu hình PHPMailer gửi email qua SMTP chuẩn (Gmail / Hostinger / Brevo)
     */
    public static function configure_phpmailer_smtp( $phpmailer ) {
        $smtp_enabled = (int) get_option( 'sunshine_smtp_enabled', 0 );
        if ( ! $smtp_enabled ) {
            return;
        }

        $host      = get_option( 'sunshine_smtp_host', 'smtp.gmail.com' );
        $port      = (int) get_option( 'sunshine_smtp_port', 465 );
        $enc       = get_option( 'sunshine_smtp_enc', 'ssl' );
        $user      = get_option( 'sunshine_smtp_user', '' );
        $pass      = get_option( 'sunshine_smtp_pass', '' );
        $from_name = get_option( 'sunshine_smtp_from_name', 'Sunshine Group BĐS' );

        if ( ! empty( $host ) && ! empty( $user ) && ! empty( $pass ) ) {
            $phpmailer->isSMTP();
            $phpmailer->Host       = $host;
            $phpmailer->SMTPAuth   = true;
            $phpmailer->Port       = $port;
            $phpmailer->Username   = $user;
            $phpmailer->Password   = $pass;
            $phpmailer->SMTPSecure = ( $enc === 'tls' ) ? 'tls' : 'ssl';
            $phpmailer->From       = $user;
            $phpmailer->FromName   = $from_name;
            $phpmailer->CharSet    = 'UTF-8';
            $phpmailer->Timeout    = 15;
        }
    }

    /**
     * Lưu trữ lead khách hàng & gửi email thông báo HTML chuyên nghiệp
     */
    public static function save_lead_submission( $data ) {
        $leads = get_option( 'sunshine_customer_leads', [] );
        if ( ! is_array( $leads ) ) {
            $leads = [];
        }
        if ( empty( $data['id'] ) ) {
            $data['id'] = 'lead_' . substr( md5( uniqid( mt_rand(), true ) ), 0, 8 );
        }
        if ( empty( $data['status'] ) ) {
            $data['status'] = 'new';
        }
        if ( ! isset( $data['note'] ) ) {
            $data['note'] = '';
        }
        array_unshift( $leads, $data );
        if ( count( $leads ) > 300 ) {
            $leads = array_slice( $leads, 0, 300 );
        }
        update_option( 'sunshine_customer_leads', $leads );

        // Lấy danh sách email nhận thông báo từ cấu hình
        $config_emails = get_option( 'sunshine_lead_notification_emails', '' );
        if ( empty( $config_emails ) ) {
            $config_emails = 'luumui1234@gmail.com';
        }
        $to_emails = array_unique( array_filter( array_map( 'trim', explode( ',', $config_emails ) ) ) );
        if ( empty( $to_emails ) ) {
            $to_emails = [ 'luumui1234@gmail.com' ];
        }

        $project_name = ! empty( $data['project'] ) ? $data['project'] : 'Dự án BĐS Sunshine';
        $demand_name  = ! empty( $data['demand'] ) ? $data['demand'] : 'Nhận bảng giá & tư vấn';
        $type_text    = ( ( $data['type'] ?? '' ) === 'thap_tang' ) ? 'Thấp tầng (Liền kề / Biệt thự / Shophouse)' : 'Cao tầng (Studio / Căn hộ 1-3PN)';
        $client_name  = ! empty( $data['name'] ) ? $data['name'] : 'Khách Hàng';
        $client_phone = ! empty( $data['phone'] ) ? $data['phone'] : '';
        $clean_phone  = preg_replace( '/[^0-9]/', '', $client_phone );
        $client_email = ! empty( $data['email'] ) ? $data['email'] : 'Không cung cấp';
        $source_url   = ! empty( $data['source_url'] ) ? $data['source_url'] : home_url();
        $submit_time  = ! empty( $data['time'] ) ? $data['time'] : current_time( 'mysql' );

        $subject = '🔔 [KHÁCH ĐĂNG KÝ MỚI] ' . $project_name . ' - ' . $client_name . ' (' . $client_phone . ')';

        // Template HTML Email Đẹp & Hiện Đại
        $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html( $subject ) . '</title>
</head>
<body style="margin:0;padding:24px;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:620px;margin:0 auto;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 10px 25px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
        <!-- Header -->
        <tr>
            <td style="background:linear-gradient(135deg,#0a192f 0%,#1e3a8a 100%);padding:28px 24px;text-align:center;">
                <div style="color:#d4af37;font-size:12px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;margin-bottom:6px;">HỆ THỐNG QUẢN TRỊ BẤT ĐỘNG SẢN SUNSHINE GROUP</div>
                <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:800;line-height:1.3;">🔔 THÔNG BÁO KHÁCH ĐĂNG KÝ MỚI</h1>
                <div style="display:inline-block;margin-top:10px;background:rgba(212,175,55,0.2);color:#fbbf24;border:1px solid rgba(212,175,55,0.4);padding:4px 14px;border-radius:20px;font-size:13px;font-weight:700;">' . esc_html( $project_name ) . '</div>
            </td>
        </tr>
        <!-- Content -->
        <tr>
            <td style="padding:28px 24px;">
                <p style="font-size:15px;color:#334155;line-height:1.6;margin-top:0;">Xin chào Quản trị viên, vừa có khách hàng để lại thông tin trên website <strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>:</p>
                
                <table width="100%" cellspacing="0" cellpadding="0" style="margin:20px 0;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                    <tr style="background:#f8fafc;">
                        <td style="padding:12px 16px;font-weight:bold;color:#475569;width:38%;border-bottom:1px solid #e2e8f0;">👤 Họ và tên:</td>
                        <td style="padding:12px 16px;color:#0f172a;font-weight:800;font-size:16px;border-bottom:1px solid #e2e8f0;">' . esc_html( $client_name ) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;font-weight:bold;color:#475569;border-bottom:1px solid #e2e8f0;">📞 Số điện thoại / Zalo:</td>
                        <td style="padding:12px 16px;color:#0284c7;font-weight:800;font-size:17px;border-bottom:1px solid #e2e8f0;">' . esc_html( $client_phone ) . '</td>
                    </tr>
                    <tr style="background:#f8fafc;">
                        <td style="padding:12px 16px;font-weight:bold;color:#475569;border-bottom:1px solid #e2e8f0;">🎯 Nhu cầu quan tâm:</td>
                        <td style="padding:12px 16px;color:#b45309;font-weight:700;border-bottom:1px solid #e2e8f0;">' . esc_html( $demand_name ) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;font-weight:bold;color:#475569;border-bottom:1px solid #e2e8f0;">🏢 Dự án quan tâm:</td>
                        <td style="padding:12px 16px;color:#0b2246;font-weight:700;border-bottom:1px solid #e2e8f0;">' . esc_html( $project_name ) . '</td>
                    </tr>
                    <tr style="background:#f8fafc;">
                        <td style="padding:12px 16px;font-weight:bold;color:#475569;border-bottom:1px solid #e2e8f0;">🏗️ Phân khúc:</td>
                        <td style="padding:12px 16px;color:#334155;border-bottom:1px solid #e2e8f0;">' . esc_html( $type_text ) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;font-weight:bold;color:#475569;border-bottom:1px solid #e2e8f0;">📧 Email khách:</td>
                        <td style="padding:12px 16px;color:#334155;border-bottom:1px solid #e2e8f0;">' . esc_html( $client_email ) . '</td>
                    </tr>
                    <tr style="background:#f8fafc;">
                        <td style="padding:12px 16px;font-weight:bold;color:#475569;border-bottom:1px solid #e2e8f0;">⏰ Thời gian đăng ký:</td>
                        <td style="padding:12px 16px;color:#64748b;font-size:13px;border-bottom:1px solid #e2e8f0;">' . esc_html( $submit_time ) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;font-weight:bold;color:#475569;">🌐 Nguồn đăng ký:</td>
                        <td style="padding:12px 16px;font-size:13px;"><a href="' . esc_url( $source_url ) . '" target="_blank" style="color:#0284c7;text-decoration:underline;">' . esc_html( $source_url ) . '</a></td>
                    </tr>
                </table>

                <!-- Action Buttons: Gọi ngay & Zalo -->
                <div style="text-align:center;margin:28px 0 10px;">
                    ' . ( ! empty( $clean_phone ) ? '<a href="tel:' . $clean_phone . '" style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;font-weight:800;font-size:14px;padding:12px 22px;border-radius:6px;margin:0 5px 8px;box-shadow:0 4px 10px rgba(22,163,74,0.3);">📞 BẤM ĐỂ GỌI NGAY</a>' : '' ) . '
                    ' . ( ! empty( $clean_phone ) ? '<a href="https://zalo.me/' . $clean_phone . '" target="_blank" style="display:inline-block;background:#0068ff;color:#ffffff;text-decoration:none;font-weight:800;font-size:14px;padding:12px 22px;border-radius:6px;margin:0 5px 8px;box-shadow:0 4px 10px rgba(0,104,255,0.3);">💬 CHAT ZALO KHÁCH</a>' : '' ) . '
                    <a href="' . admin_url( 'edit.php?post_type=du_an&page=sunshine-customer-leads' ) . '" style="display:inline-block;background:#0b2246;color:#ffffff;text-decoration:none;font-weight:700;font-size:13.5px;padding:12px 18px;border-radius:6px;margin:0 5px 8px;">📊 XEM TRÊN WEBSITE</a>
                </div>
            </td>
        </tr>
        <!-- Footer -->
        <tr>
            <td style="background:#f8fafc;padding:16px 24px;text-align:center;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;">
                Email thông báo tự động từ hệ thống website <a href="' . home_url() . '" style="color:#64748b;text-decoration:underline;">' . home_url() . '</a>. Dữ liệu đồng thời được lưu trữ an toàn trong mục <strong>Quản lý Khách Đăng Ký</strong>.
            </td>
        </tr>
    </table>
</body>
</html>';

        $from_name = get_option( 'sunshine_smtp_from_name', 'Sunshine Group BĐS' );
        $from_user = get_option( 'sunshine_smtp_user', '' );
        if ( empty( $from_user ) ) {
            $from_user = 'no-reply@' . ( $_SERVER['HTTP_HOST'] ?? 'sunshine-group.com.vn' );
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_user . '>'
        ];

        // Lắng nghe lỗi gửi mail nếu có
        $last_error = '';
        $error_capture = function( $error ) use ( &$last_error ) {
            $last_error = $error->get_error_message();
            update_option( 'sunshine_last_mail_error', [
                'time'    => current_time( 'mysql' ),
                'message' => $last_error,
            ] );
        };
        add_action( 'wp_mail_failed', $error_capture );

        $sent = @wp_mail( $to_emails, $subject, $body, $headers );

        remove_action( 'wp_mail_failed', $error_capture );

        update_option( 'sunshine_last_mail_status', [
            'time'  => current_time( 'mysql' ),
            'to'    => implode( ', ', $to_emails ),
            'sent'  => $sent ? true : false,
            'error' => $sent ? '' : $last_error,
        ] );

        // Gửi Telegram (nếu có cấu hình)
        self::send_telegram_lead_notification( $data );
    }

    /**
     * Gửi thông báo lead qua Telegram Bot
     */
    public static function send_telegram_lead_notification( $data ) {
        $token   = get_option( 'sunshine_telegram_bot_token', '' );
        $chat_id = get_option( 'sunshine_telegram_chat_id', '' );
        if ( empty( $token ) || empty( $chat_id ) ) {
            return;
        }

        $project_name = ! empty( $data['project'] ) ? $data['project'] : 'Dự án BĐS';
        $demand_name  = ! empty( $data['demand'] ) ? $data['demand'] : 'Chung';
        $client_name  = ! empty( $data['name'] ) ? $data['name'] : 'Khách Hàng';
        $client_phone = ! empty( $data['phone'] ) ? $data['phone'] : '';

        $text = "🔔 *CÓ KHÁCH ĐĂNG KÝ MỚI TỪ WEBSITE*\n"
              . "━━━━━━━━━━━━━━━━━━━\n"
              . "👤 *Họ tên:* " . $client_name . "\n"
              . "📞 *Số điện thoại:* `" . $client_phone . "`\n"
              . "🎯 *Nhu cầu:* " . $demand_name . "\n"
              . "🏢 *Dự án:* " . $project_name . "\n"
              . "📧 *Email:* " . ( ! empty( $data['email'] ) ? $data['email'] : 'Không có' ) . "\n"
              . "⏰ *Thời gian:* " . ( $data['time'] ?? current_time( 'mysql' ) ) . "\n"
              . "🌐 *Nguồn:* " . ( $data['source_url'] ?? '' );

        @wp_remote_post( "https://api.telegram.org/bot{$token}/sendMessage", [
            'body'    => [
                'chat_id'    => $chat_id,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ],
            'timeout' => 8,
        ] );
    }

    /**
     * Gửi thử email kiểm tra cấu hình SMTP
     */
    public static function send_test_email( $to_email ) {
        $subject = '✅ [Kiểm Tra Thành Công] Thử Nghiệm Gửi Mail Từ Website Sunshine Group';
        $body = '<div style="font-family:Arial,sans-serif;padding:24px;background:#f8fafc;border-radius:8px;max-width:550px;margin:0 auto;border:1px solid #e2e8f0;">'
              . '<h2 style="color:#16a34a;margin-top:0;">🎉 Cấu Hình Email & SMTP Thành Công!</h2>'
              . '<p style="color:#334155;font-size:15px;line-height:1.6;">Email kiểm tra này xác nhận rằng hệ thống gửi thư của website <strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong> đang hoạt động hoàn hảo.</p>'
              . '<p style="color:#334155;font-size:15px;line-height:1.6;">Từ bây giờ, bất cứ khi nào khách hàng điền form nhận báo giá trên website, thông báo sẽ đổ thẳng về hộp thư của bạn ngay lập tức.</p>'
              . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;">'
              . '<p style="color:#64748b;font-size:12.5px;margin:0;">Thời gian gửi: ' . current_time( 'mysql' ) . '</p>'
              . '</div>';

        $from_name = get_option( 'sunshine_smtp_from_name', 'Sunshine Group BĐS' );
        $from_user = get_option( 'sunshine_smtp_user', '' );
        if ( empty( $from_user ) ) {
            $from_user = 'no-reply@' . ( $_SERVER['HTTP_HOST'] ?? 'sunshine-group.com.vn' );
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_user . '>'
        ];

        $captured_error = '';
        $hook = function( $err ) use ( &$captured_error ) {
            $captured_error = $err->get_error_message();
        };
        add_action( 'wp_mail_failed', $hook );

        $sent = @wp_mail( $to_email, $subject, $body, $headers );

        remove_action( 'wp_mail_failed', $hook );

        return [
            'success' => $sent ? true : false,
            'error'   => $sent ? '' : $captured_error,
        ];
    }

    /**
     * =========================================================================
     * CHỨC NĂNG CRM QUẢN LÝ KHÁCH HÀNG & CÀI ĐẶT THÔNG BÁO (TELEGRAM & EMAIL)
     * =========================================================================
     */

    /**
     * Đăng ký Menu Admin: Khách hàng (CRM)
     */
    public static function register_admin_menus() {
        $leads = get_option('sunshine_customer_leads', []);
        if (!is_array($leads)) { $leads = []; }

        // Đếm số lượng khách mới (chưa gọi / new)
        $new_count = 0;
        foreach ($leads as $l) {
            $st = $l['status'] ?? 'new';
            if ($st === 'new') {
                $new_count++;
            }
        }

        $badge = '';
        if ($new_count > 0) {
            $badge = ' <span class="update-plugins count-' . $new_count . '" style="background:#ef4444;color:#ffffff;border-radius:10px;padding:2px 7px;font-size:11px;font-weight:800;margin-left:4px;display:inline-block;line-height:1.2;">' . $new_count . '</span>';
        }

        // 1. Menu Top-Level: Khách hàng (CRM)
        add_menu_page(
            'Quản Lý Khách Hàng (CRM)',
            'Khách hàng (CRM)' . $badge,
            'manage_options',
            'sunshine-crm',
            [ __CLASS__, 'render_crm_customers_page' ],
            'dashicons-groups',
            6
        );

        // Submenu 1: Khách hàng (CRM) (Tất cả khách hàng)
        add_submenu_page(
            'sunshine-crm',
            'Danh Sách Khách Hàng (CRM)',
            'Khách hàng (CRM)',
            'manage_options',
            'sunshine-crm',
            [ __CLASS__, 'render_crm_customers_page' ]
        );

        // Submenu 2: Thêm thông tin Khách hàng
        add_submenu_page(
            'sunshine-crm',
            'Thêm Thông Tin Khách Hàng',
            'Thêm thông tin Khách hàng',
            'manage_options',
            'sunshine-crm-add',
            [ __CLASS__, 'render_crm_add_page' ]
        );

        // Submenu 3: Cài đặt Telegram (và Email SMTP)
        add_submenu_page(
            'sunshine-crm',
            'Cài Đặt Telegram & Thông Báo Mail',
            'Cài đặt Telegram',
            'manage_options',
            'sunshine-crm-settings',
            [ __CLASS__, 'render_crm_settings_page' ]
        );

        // Giữ tương thích đường dẫn cũ trong Dự Án BĐS
        add_submenu_page(
            'edit.php?post_type=du_an',
            'Danh Sách Khách Hàng (CRM)',
            'Khách Đăng Ký' . $badge,
            'manage_options',
            'sunshine-customer-leads',
            [ __CLASS__, 'render_crm_customers_page' ]
        );
    }

    /**
     * Gửi tin nhắn kiểm tra tới Telegram Bot
     */
    /**
     * Đồng bộ tự động tất cả các form Contact Form 7 cũ vào Khách hàng (CRM) & Gửi Mail/Telegram
     */
    public static function sync_cf7_submission_to_crm( $contact_form, $param2 = null, $submission = null ) {
        static $handled_submissions = [];
        if ( ! $submission ) {
            if ( class_exists( 'WPCF7_Submission' ) ) {
                $submission = WPCF7_Submission::get_instance();
            }
        }
        if ( ! $submission ) {
            return;
        }

        // Tránh lưu 2 lần nếu cả 2 hook đều chạy
        $sub_hash = spl_object_hash( $submission );
        if ( isset( $handled_submissions[$sub_hash] ) ) {
            return;
        }
        $handled_submissions[$sub_hash] = true;
        if ( ! $submission ) {
            if ( class_exists( 'WPCF7_Submission' ) ) {
                $submission = WPCF7_Submission::get_instance();
            }
        }
        if ( ! $submission ) {
            return;
        }

        $posted_data = $submission->get_posted_data();
        if ( empty( $posted_data ) || ! is_array( $posted_data ) ) {
            return;
        }

        // 1. Trích xuất Họ và tên
        $name = '';
        foreach ( [ 'your-name', 'name', 'ho-ten', 'full-name', 'fullname', 'your_name' ] as $k ) {
            if ( ! empty( $posted_data[$k] ) ) {
                $name = sanitize_text_field( $posted_data[$k] );
                break;
            }
        }
        if ( empty( $name ) ) {
            $name = 'Khách Hàng (Form Cũ)';
        }

        // 2. Trích xuất Số điện thoại
        $phone = '';
        foreach ( [ 'your-phone', 'your-tel', 'phone', 'tel', 'so-dien-thoai', 'your_phone', 'mobile' ] as $k ) {
            if ( ! empty( $posted_data[$k] ) ) {
                $phone = sanitize_text_field( $posted_data[$k] );
                break;
            }
        }

        // 3. Trích xuất Email
        $email = '';
        foreach ( [ 'your-email', 'email', 'your_email' ] as $k ) {
            if ( ! empty( $posted_data[$k] ) ) {
                $email = sanitize_email( $posted_data[$k] );
                break;
            }
        }

        // 4. Trích xuất Nhu cầu / Dự án / Lời nhắn
        $message = '';
        foreach ( [ 'your-message', 'message', 'your-subject', 'subject', 'du-an', 'noi-dung' ] as $k ) {
            if ( ! empty( $posted_data[$k] ) ) {
                $message = sanitize_text_field( $posted_data[$k] );
                break;
            }
        }

        // 5. Xác định Dự án và Trang nguồn
        $source_url = '';
        if ( method_exists( $submission, 'get_meta' ) ) {
            $source_url = $submission->get_meta( 'url' );
        }
        if ( empty( $source_url ) ) {
            $source_url = is_singular() ? get_permalink() : home_url( $_SERVER['REQUEST_URI'] ?? '/' );
        }

        $project_name = '';
        if ( ! empty( $message ) && ( strpos( mb_strtolower( $message ), 'sunshine' ) !== false || strpos( mb_strtolower( $message ), 'handico' ) !== false || strpos( mb_strtolower( $message ), 'noble' ) !== false || strpos( mb_strtolower( $message ), 'hinode' ) !== false ) ) {
            $project_name = $message;
        } else {
            $container_id = method_exists( $submission, 'get_meta' ) ? (int) $submission->get_meta( 'container_post_id' ) : 0;
            $current_post_id = get_queried_object_id() ?: $container_id;
            if ( $current_post_id ) {
                $project_name = get_the_title( $current_post_id );
            } else {
                $project_name = ! empty( $message ) ? $message : 'Đăng ký Form ' . $contact_form->title();
            }
        }

        // 6. Lưu vào Khách hàng (CRM) và kích hoạt gửi Email HTML + Telegram tự động!
        self::save_lead_submission( [
            'id'         => 'cf7_' . substr( md5( uniqid( mt_rand(), true ) ), 0, 8 ),
            'form'       => 'Contact Form 7 (ID ' . $contact_form->id() . ' - ' . $contact_form->title() . ')',
            'name'       => $name,
            'phone'      => $phone,
            'email'      => $email,
            'project'    => $project_name,
            'type'       => 'cao_tang',
            'demand'     => ! empty( $message ) ? $message : 'Nhận bảng giá & tư vấn liên hệ',
            'status'     => 'new',
            'note'       => 'Đăng ký từ ' . $contact_form->title() . ( ! empty( $message ) ? ' | Nội dung: ' . $message : '' ),
            'source_url' => $source_url,
            'time'       => current_time( 'mysql' ),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        ] );
    }

    /**
     * Đồng bộ người nhận email của Contact Form 7 sang email quản trị mới
     */
    public static function filter_cf7_mail_recipient( $components, $form ) {
        $configured_emails = get_option( 'sunshine_lead_notification_emails', 'luumui1234@gmail.com' );
        if ( ! empty( $configured_emails ) ) {
            $components['recipient'] = $configured_emails;
        }
        return $components;
    }

    public static function send_test_telegram() {
        $token   = trim(get_option('sunshine_telegram_bot_token', ''));
        $chat_id = trim(get_option('sunshine_telegram_chat_id', ''));
        if (empty($token) || empty($chat_id)) {
            return ['success' => false, 'error' => 'Vui lòng nhập Bot Token và Chat ID trước khi gửi thử.'];
        }

        $text = "🎉 *KIỂM TRA KẾT NỐI TELEGRAM THÀNH CÔNG!*\n"
              . "━━━━━━━━━━━━━━━━━━━\n"
              . "Website *" . get_bloginfo('name') . "* đã kết nối thành công với Telegram của bạn.\n"
              . "Từ bây giờ, mỗi khi có khách hàng đăng ký trên website, thông báo sẽ bắn trực tiếp về Telegram ngay tức thì!\n"
              . "⏰ Thời gian: " . current_time('mysql');

        $response = @wp_remote_post("https://api.telegram.org/bot{$token}/sendMessage", [
            'body'    => [
                'chat_id'    => $chat_id,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($data['ok'])) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => $data['description'] ?? 'Lỗi không xác định từ máy chủ Telegram.'];
    }

    /**
     * TRANG 1: DANH SÁCH KHÁCH HÀNG (CRM)
     */
    public static function render_crm_customers_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang này.');
        }

        $notice = '';
        $notice_type = 'success';

        $leads = get_option('sunshine_customer_leads', []);
        if (!is_array($leads)) { $leads = []; }

        // Đảm bảo mỗi lead có ID và status chuẩn
        $needs_save = false;
        foreach ($leads as $k => $l) {
            if (empty($l['id'])) {
                $leads[$k]['id'] = 'lead_' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
                $needs_save = true;
            }
            if (empty($l['status'])) {
                $leads[$k]['status'] = 'new';
                $needs_save = true;
            }
        }
        if ($needs_save) {
            update_option('sunshine_customer_leads', $leads);
        }

        // Xử lý Cập nhật trạng thái / ghi chú nhanh
        if (isset($_POST['crm_update_lead']) && check_admin_referer('crm_update_lead_nonce')) {
            $lead_id = sanitize_text_field(wp_unslash($_POST['lead_id'] ?? ''));
            $new_st  = sanitize_text_field(wp_unslash($_POST['new_status'] ?? 'new'));
            $new_nt  = sanitize_textarea_field(wp_unslash($_POST['new_note'] ?? ''));

            foreach ($leads as $k => $l) {
                if (($l['id'] ?? '') === $lead_id) {
                    $leads[$k]['status'] = $new_st;
                    $leads[$k]['note']   = $new_nt;
                    break;
                }
            }
            update_option('sunshine_customer_leads', $leads);
            $notice = '✓ Đã cập nhật trạng thái chăm sóc khách hàng!';
        }

        // Xử lý Xóa 1 lead
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('sunshine_crm_delete_lead');
            $del_id = sanitize_text_field($_GET['id']);
            foreach ($leads as $k => $l) {
                if (($l['id'] ?? '') === $del_id) {
                    array_splice($leads, $k, 1);
                    break;
                }
            }
            update_option('sunshine_customer_leads', $leads);
            wp_safe_redirect(admin_url('admin.php?page=sunshine-crm&deleted=1'));
            exit;
        }

        // Xử lý Xóa tất cả
        if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
            check_admin_referer('sunshine_crm_clear_leads');
            update_option('sunshine_customer_leads', []);
            wp_safe_redirect(admin_url('admin.php?page=sunshine-crm&cleared=1'));
            exit;
        }

        // Xử lý xuất Excel CSV
        if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            check_admin_referer('sunshine_crm_export');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=crm-khach-hang-' . date('Y-m-d') . '.csv');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($out, ['STT', 'Thời gian', 'Họ và tên', 'Số điện thoại', 'Trạng thái', 'Nhu cầu', 'Dự án', 'Phân khúc', 'Email', 'Ghi chú CSKH', 'Nguồn']);
            $st_labels = [
                'new'         => 'Mới tiếp nhận',
                'contacting'  => 'Đang liên hệ / Tư vấn',
                'appointment' => 'Hẹn xem dự án',
                'closed'      => 'Đã cọc / Giao dịch',
                'unqualified' => 'Không nhu cầu / Sai số',
            ];
            $i = 1;
            foreach ($leads as $l) {
                fputcsv($out, [
                    $i++,
                    $l['time'] ?? '',
                    $l['name'] ?? '',
                    $l['phone'] ?? '',
                    $st_labels[$l['status'] ?? 'new'] ?? 'Mới tiếp nhận',
                    $l['demand'] ?? '',
                    $l['project'] ?? '',
                    (($l['type'] ?? '') === 'thap_tang') ? 'Thấp tầng' : 'Cao tầng',
                    $l['email'] ?? '',
                    $l['note'] ?? '',
                    $l['source_url'] ?? 'Website'
                ]);
            }
            fclose($out);
            exit;
        }

        // Thống kê theo trạng thái
        $total = count($leads);
        $count_new = 0;
        $count_contacting = 0;
        $count_appointment = 0;
        $count_closed = 0;
        foreach ($leads as $l) {
            $st = $l['status'] ?? 'new';
            if ($st === 'new') $count_new++;
            elseif ($st === 'contacting') $count_contacting++;
            elseif ($st === 'appointment') $count_appointment++;
            elseif ($st === 'closed') $count_closed++;
        }

        // Bộ lọc hiển thị
        $filter_status  = sanitize_text_field($_GET['filter_status'] ?? '');
        $filter_project = sanitize_text_field($_GET['filter_project'] ?? '');
        $search_kw      = sanitize_text_field($_GET['s'] ?? '');

        $display_leads = $leads;
        if (!empty($filter_status)) {
            $display_leads = array_filter($display_leads, function($l) use ($filter_status) {
                return ($l['status'] ?? 'new') === $filter_status;
            });
        }
        if (!empty($filter_project)) {
            $display_leads = array_filter($display_leads, function($l) use ($filter_project) {
                return strpos(mb_strtolower($l['project'] ?? ''), mb_strtolower($filter_project)) !== false;
            });
        }
        if (!empty($search_kw)) {
            $display_leads = array_filter($display_leads, function($l) use ($search_kw) {
                $kw = mb_strtolower($search_kw);
                return (strpos(mb_strtolower($l['name'] ?? ''), $kw) !== false) ||
                       (strpos(mb_strtolower($l['phone'] ?? ''), $kw) !== false) ||
                       (strpos(mb_strtolower($l['email'] ?? ''), $kw) !== false);
            });
        }

        // Lấy danh sách dự án duy nhất cho bộ lọc
        $unique_projects = array_unique(array_filter(array_column($leads, 'project')));
        ?>
        <div class="wrap" style="max-width:1380px;margin-top:18px;">
            <!-- Header CRM -->
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="dashicons dashicons-groups" style="font-size:36px;width:36px;height:36px;color:#2563eb;"></span>
                    <div>
                        <h1 style="font-size:24px;font-weight:800;color:#0b2246;margin:0;line-height:1.2;">
                            Khách Hàng (CRM Bất Động Sản)
                        </h1>
                        <span style="font-size:13px;color:#64748b;">Hệ thống quản lý, phân loại và chăm sóc khách hàng mua BĐS</span>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="<?php echo admin_url('admin.php?page=sunshine-crm-add'); ?>" class="button button-primary" style="background:#2563eb;border-color:#2563eb;font-weight:700;padding:5px 16px;font-size:13.5px;display:inline-flex;align-items:center;gap:6px;">
                        <span class="dashicons dashicons-plus-alt2" style="font-size:16px;line-height:28px;"></span> Thêm Khách Hàng
                    </a>
                    <?php if ($total > 0) : ?>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sunshine-crm&action=export_csv'), 'sunshine_crm_export'); ?>" class="button" style="font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                            📥 Xuất Excel
                        </a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sunshine-crm&action=clear_all'), 'sunshine_crm_clear_leads'); ?>" class="button" onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ danh sách khách hàng này?');" style="color:#dc2626;">
                            Xóa tất cả
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($notice)) : ?>
                <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible" style="padding:10px 14px;"><p style="margin:0;font-weight:600;"><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])) : ?>
                <div class="notice notice-success is-dismissible"><p>✓ Đã xóa khách hàng khỏi danh sách.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['cleared'])) : ?>
                <div class="notice notice-success is-dismissible"><p>✓ Đã làm trống toàn bộ dữ liệu CRM.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['added'])) : ?>
                <div class="notice notice-success is-dismissible"><p>🎉 Đã thêm mới khách hàng thành công vào hệ thống CRM!</p></div>
            <?php endif; ?>

            <!-- THẺ THỐNG KÊ CRM (CARDS) -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:16px;margin-bottom:22px;">
                <!-- Tổng khách -->
                <div style="background:#fff;border-left:5px solid #2563eb;padding:16px 20px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                    <span style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;">Tổng Khách Hàng</span>
                    <strong style="display:block;font-size:26px;color:#0b2246;margin-top:4px;"><?php echo $total; ?></strong>
                </div>
                <!-- Khách mới (Chưa gọi) -->
                <div style="background:#fff;border-left:5px solid #ef4444;padding:16px 20px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:12px;color:#ef4444;font-weight:700;text-transform:uppercase;">Mới (Chưa Liên Hệ)</span>
                        <span style="background:#fee2e2;color:#b91c1c;border-radius:12px;padding:2px 8px;font-size:11px;font-weight:800;">Cần gọi ngay</span>
                    </div>
                    <strong style="display:block;font-size:26px;color:#b91c1c;margin-top:4px;"><?php echo $count_new; ?></strong>
                </div>
                <!-- Đang chăm sóc -->
                <div style="background:#fff;border-left:5px solid #f59e0b;padding:16px 20px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                    <span style="font-size:12px;color:#b45309;font-weight:700;text-transform:uppercase;">Đang Tư Vấn</span>
                    <strong style="display:block;font-size:26px;color:#b45309;margin-top:4px;"><?php echo $count_contacting; ?></strong>
                </div>
                <!-- Hẹn xem nhà -->
                <div style="background:#fff;border-left:5px solid #8b5cf6;padding:16px 20px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                    <span style="font-size:12px;color:#6d28d9;font-weight:700;text-transform:uppercase;">Hẹn Xem Dự Án</span>
                    <strong style="display:block;font-size:26px;color:#6d28d9;margin-top:4px;"><?php echo $count_appointment; ?></strong>
                </div>
                <!-- Đã chốt cọc -->
                <div style="background:#fff;border-left:5px solid #10b981;padding:16px 20px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                    <span style="font-size:12px;color:#047857;font-weight:700;text-transform:uppercase;">Đã Chốt Cọc / VIP</span>
                    <strong style="display:block;font-size:26px;color:#047857;margin-top:4px;"><?php echo $count_closed; ?></strong>
                </div>
            </div>

            <!-- THANH TÌM KIẾM & BỘ LỌC -->
            <div style="background:#ffffff;border:1px solid #e2e8f0;padding:14px 18px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:18px;">
                <form method="get" action="" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <input type="hidden" name="page" value="sunshine-crm">
                    
                    <div style="flex:1;min-width:200px;">
                        <input type="text" name="s" value="<?php echo esc_attr($search_kw); ?>" placeholder="🔍 Tìm theo Tên, SĐT, Email..." style="width:100%;padding:6px 12px;border:1px solid #cbd5e1;border-radius:5px;font-size:13.5px;">
                    </div>

                    <div>
                        <select name="filter_status" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="new" <?php selected($filter_status, 'new'); ?>>🔴 Mới tiếp nhận (Chưa gọi)</option>
                            <option value="contacting" <?php selected($filter_status, 'contacting'); ?>>🟡 Đang liên hệ / Tư vấn</option>
                            <option value="appointment" <?php selected($filter_status, 'appointment'); ?>>🟣 Hẹn xem dự án</option>
                            <option value="closed" <?php selected($filter_status, 'closed'); ?>>🟢 Đã chốt cọc</option>
                            <option value="unqualified" <?php selected($filter_status, 'unqualified'); ?>>⚪ Không nhu cầu / Sai số</option>
                        </select>
                    </div>

                    <div>
                        <select name="filter_project" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;">
                            <option value="">-- Tất cả dự án --</option>
                            <?php foreach ($unique_projects as $up) : ?>
                                <option value="<?php echo esc_attr($up); ?>" <?php selected($filter_project, $up); ?>><?php echo esc_html($up); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="button" style="font-weight:700;">Lọc dữ liệu</button>
                    <?php if (!empty($search_kw) || !empty($filter_status) || !empty($filter_project)) : ?>
                        <a href="<?php echo admin_url('admin.php?page=sunshine-crm'); ?>" style="color:#64748b;text-decoration:none;font-size:13px;padding:4px 8px;">✕ Đặt lại</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- BẢNG DANH SÁCH KHÁCH HÀNG -->
            <?php if (empty($display_leads)) : ?>
                <div style="background:#fff;padding:60px 20px;text-align:center;border-radius:8px;border:1px dashed #cbd5e1;">
                    <div style="font-size:46px;margin-bottom:12px;">👥</div>
                    <h3 style="margin:0 0 8px;color:#0f172a;font-size:17px;">Không tìm thấy khách hàng phù hợp</h3>
                    <p style="color:#64748b;margin:0 0 16px;font-size:13.5px;">Chưa có khách hàng nào trong bộ lọc này hoặc chưa có lượt đăng ký mới.</p>
                    <a href="<?php echo admin_url('admin.php?page=sunshine-crm-add'); ?>" class="button button-primary" style="background:#2563eb;border-color:#2563eb;font-weight:700;">
                        + Thêm Khách Hàng Thủ Công
                    </a>
                </div>
            <?php else : ?>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                    <table class="wp-list-table widefat fixed striped posts" style="border:none;">
                        <thead>
                            <tr>
                                <th style="width:36px;text-align:center;">#</th>
                                <th style="width:125px;">Thời gian</th>
                                <th style="width:210px;">Khách hàng</th>
                                <th style="width:140px;">Trạng thái CSKH</th>
                                <th style="width:150px;">Nhu cầu quan tâm</th>
                                <th style="width:170px;">Dự án</th>
                                <th>Ghi chú tư vấn</th>
                                <th style="width:120px;">Nguồn</th>
                                <th style="width:80px;text-align:center;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            foreach ($display_leads as $lead) : 
                                $lid   = $lead['id'] ?? '';
                                $phone = preg_replace('/[^0-9]/', '', $lead['phone'] ?? '');
                                $st    = $lead['status'] ?? 'new';
                                $note  = $lead['note'] ?? '';

                                $st_badge = [
                                    'new'         => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'text' => '🔴 Mới tiếp nhận'],
                                    'contacting'  => ['bg' => '#fef3c7', 'color' => '#92400e', 'text' => '🟡 Đang liên hệ'],
                                    'appointment' => ['bg' => '#ede9fe', 'color' => '#6d28d9', 'text' => '🟣 Hẹn xem dự án'],
                                    'closed'      => ['bg' => '#d1fae5', 'color' => '#065f46', 'text' => '🟢 Đã chốt cọc'],
                                    'unqualified' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'text' => '⚪ Không nhu cầu'],
                                ][$st] ?? ['bg' => '#fee2e2', 'color' => '#b91c1c', 'text' => '🔴 Mới tiếp nhận'];
                            ?>
                                <tr>
                                    <td style="text-align:center;font-weight:700;color:#94a3b8;"><?php echo $i++; ?></td>
                                    <td style="font-size:12px;color:#64748b;">
                                        <?php echo esc_html($lead['time'] ?? ''); ?>
                                    </td>
                                    <td>
                                        <strong style="font-size:14.5px;color:#0b2246;display:block;margin-bottom:4px;"><?php echo esc_html($lead['name'] ?? 'Khách Hàng'); ?></strong>
                                        <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;margin-bottom:3px;">
                                            <a href="tel:<?php echo esc_attr($phone); ?>" style="font-weight:800;color:#0284c7;text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:3px;">
                                                📞 <?php echo esc_html($lead['phone'] ?? ''); ?>
                                            </a>
                                            <?php if ($phone) : ?>
                                                <a href="https://zalo.me/<?php echo esc_attr($phone); ?>" target="_blank" style="background:#0068ff;color:#fff;font-size:11px;font-weight:800;padding:2px 7px;border-radius:4px;text-decoration:none;">Zalo</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($lead['email'])) : ?>
                                            <div style="font-size:11.5px;color:#64748b;overflow:hidden;text-overflow:ellipsis;"><?php echo esc_html($lead['email']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="display:inline-block;background:<?php echo $st_badge['bg']; ?>;color:<?php echo $st_badge['color']; ?>;padding:3px 9px;border-radius:12px;font-size:12px;font-weight:800;">
                                            <?php echo $st_badge['text']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="display:inline-block;background:#f8fafc;color:#1e293b;border:1px solid #e2e8f0;font-size:12.5px;font-weight:700;padding:3px 8px;border-radius:4px;">
                                            <?php echo esc_html($lead['demand'] ?? 'Chung'); ?>
                                        </span>
                                        <span style="display:block;font-size:11px;color:#64748b;margin-top:3px;">
                                            <?php echo (($lead['type'] ?? '') === 'thap_tang') ? 'Thấp tầng' : 'Cao tầng'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color:#0f172a;font-size:13.5px;line-height:1.3;display:block;"><?php echo esc_html($lead['project'] ?? 'Dự án'); ?></strong>
                                    </td>
                                    <td>
                                        <!-- Form ghi chú & đổi trạng thái nhanh -->
                                        <form method="post" action="" style="display:flex;flex-direction:column;gap:5px;">
                                            <?php wp_nonce_field('crm_update_lead_nonce'); ?>
                                            <input type="hidden" name="crm_update_lead" value="1">
                                            <input type="hidden" name="lead_id" value="<?php echo esc_attr($lid); ?>">
                                            
                                            <div style="display:flex;gap:5px;">
                                                <select name="new_status" style="font-size:11.5px;padding:2px 6px;border-radius:4px;border:1px solid #cbd5e1;width:130px;">
                                                    <option value="new" <?php selected($st, 'new'); ?>>Mới</option>
                                                    <option value="contacting" <?php selected($st, 'contacting'); ?>>Đang liên hệ</option>
                                                    <option value="appointment" <?php selected($st, 'appointment'); ?>>Hẹn xem</option>
                                                    <option value="closed" <?php selected($st, 'closed'); ?>>Đã cọc</option>
                                                    <option value="unqualified" <?php selected($st, 'unqualified'); ?>>Không nhu cầu</option>
                                                </select>
                                                <button type="submit" class="button button-small" style="padding:0 8px;font-size:11px;font-weight:700;">Lưu</button>
                                            </div>
                                            <input type="text" name="new_note" value="<?php echo esc_attr($note); ?>" placeholder="Thêm ghi chú khách..." style="font-size:12px;padding:3px 6px;border:1px solid #e2e8f0;border-radius:3px;width:100%;">
                                        </form>
                                    </td>
                                    <td style="font-size:12px;color:#64748b;">
                                        <?php if (!empty($lead['source_url'])) : ?>
                                            <a href="<?php echo esc_url($lead['source_url']); ?>" target="_blank" style="color:#2563eb;text-decoration:none;font-weight:600;display:inline-block;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Xem trang ↗</a>
                                        <?php else : ?>
                                            <span>Thêm thủ công</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sunshine-crm&action=delete&id=' . $lid), 'sunshine_crm_delete_lead'); ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');" style="color:#ef4444;text-decoration:none;font-size:18px;font-weight:bold;display:inline-block;padding:2px 6px;" title="Xóa khách hàng">
                                            &times;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * TRANG 2: THÊM THÔNG TIN KHÁCH HÀNG (CRM ADD)
     */
    public static function render_crm_add_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang này.');
        }

        $error = '';

        // Xử lý Form thêm khách hàng
        if (isset($_POST['crm_add_customer']) && check_admin_referer('crm_add_customer_nonce')) {
            $name    = sanitize_text_field(wp_unslash($_POST['c_name'] ?? ''));
            $phone   = sanitize_text_field(wp_unslash($_POST['c_phone'] ?? ''));
            $email   = sanitize_email(wp_unslash($_POST['c_email'] ?? ''));
            $project = sanitize_text_field(wp_unslash($_POST['c_project'] ?? ''));
            $type    = sanitize_text_field(wp_unslash($_POST['c_type'] ?? 'cao_tang'));
            $demand  = sanitize_text_field(wp_unslash($_POST['c_demand'] ?? ''));
            $status  = sanitize_text_field(wp_unslash($_POST['c_status'] ?? 'new'));
            $note    = sanitize_textarea_field(wp_unslash($_POST['c_note'] ?? ''));
            $source  = sanitize_text_field(wp_unslash($_POST['c_source'] ?? 'Thêm thủ công'));

            if (empty($name) || empty($phone)) {
                $error = 'Vui lòng nhập đầy đủ Họ và tên cùng Số điện thoại của khách.';
            } else {
                $leads = get_option('sunshine_customer_leads', []);
                if (!is_array($leads)) { $leads = []; }

                $new_lead = [
                    'id'         => 'lead_' . substr(md5(uniqid(mt_rand(), true)), 0, 8),
                    'form'       => 'manual_crm',
                    'name'       => $name,
                    'phone'      => $phone,
                    'email'      => $email,
                    'project'    => $project ?: 'Khách tư vấn chung',
                    'type'       => $type,
                    'demand'     => $demand ?: 'Tư vấn dự án',
                    'status'     => $status,
                    'note'       => $note,
                    'source_url' => $source,
                    'time'       => current_time('mysql'),
                    'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                ];

                array_unshift($leads, $new_lead);
                update_option('sunshine_customer_leads', $leads);

                wp_safe_redirect(admin_url('admin.php?page=sunshine-crm&added=1'));
                exit;
            }
        }

        // Lấy danh sách dự án hiện có
        $projects = get_posts([
            'post_type'      => 'du_an',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        ?>
        <div class="wrap" style="max-width:900px;margin-top:18px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                <a href="<?php echo admin_url('admin.php?page=sunshine-crm'); ?>" style="color:#64748b;text-decoration:none;font-weight:700;">&larr; Quay lại danh sách CRM</a>
            </div>

            <h1 style="font-size:24px;font-weight:800;color:#0b2246;margin:0 0 16px;display:flex;align-items:center;gap:10px;">
                <span class="dashicons dashicons-businessman" style="font-size:30px;color:#2563eb;"></span>
                Thêm Thông Tin Khách Hàng Mới
            </h1>

            <?php if (!empty($error)) : ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:26px 30px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                <form method="post" action="">
                    <?php wp_nonce_field('crm_add_customer_nonce'); ?>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:18px;">
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                                👤 Họ và tên khách hàng <span style="color:#ef4444;">*</span>:
                            </label>
                            <input type="text" name="c_name" required placeholder="ví dụ: Anh Hoàng / Chị Mai" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                                📞 Số điện thoại / Zalo <span style="color:#ef4444;">*</span>:
                            </label>
                            <input type="tel" name="c_phone" required placeholder="ví dụ: 0912345678" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:18px;">
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                                📧 Email khách hàng (tùy chọn):
                            </label>
                            <input type="email" name="c_email" placeholder="khachhang@gmail.com" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                                🏢 Dự án khách quan tâm:
                            </label>
                            <select name="c_project" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                                <option value="">-- Chọn dự án --</option>
                                <?php foreach ($projects as $p) : ?>
                                    <option value="<?php echo esc_attr($p->post_title); ?>"><?php echo esc_html($p->post_title); ?></option>
                                <?php endforeach; ?>
                                <option value="Handico Sunshine New Hà Nội">Handico Sunshine New Hà Nội</option>
                                <option value="Noble Palace Long Biên">Noble Palace Long Biên</option>
                                <option value="Hinode Solera Phạm Hùng">Hinode Solera Phạm Hùng</option>
                                <option value="Khách tư vấn chung">Khách tư vấn chung</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:18px;">
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                                🏗️ Phân khúc sản phẩm:
                            </label>
                            <select name="c_type" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                                <option value="cao_tang">Cao tầng (Căn hộ / Chung cư / Duplex)</option>
                                <option value="thap_tang">Thấp tầng (Liền kề / Biệt thự / Shophouse)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                                🎯 Nhu cầu quan tâm chi tiết:
                            </label>
                            <input type="text" name="c_demand" placeholder="ví dụ: Căn 2PN view hồ / Biệt thự song lập" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:18px;">
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                                🚦 Trạng thái chăm sóc ban đầu:
                            </label>
                            <select name="c_status" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                                <option value="new">🔴 Mới tiếp nhận (Chưa gọi)</option>
                                <option value="contacting">🟡 Đang liên hệ / Đã tư vấn sơ bộ</option>
                                <option value="appointment">🟣 Hẹn xem nhà mẫu / Sa bàn</option>
                                <option value="closed">🟢 Đã cọc / Khách VIP</option>
                                <option value="unqualified">⚪ Không nhu cầu / Sai số</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                                🌐 Nguồn khách hàng:
                            </label>
                            <input type="text" name="c_source" value="Nhân viên thêm thủ công" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                        </div>
                    </div>

                    <div style="margin-bottom:24px;">
                        <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13.5px;">
                            📝 Ghi chú tư vấn (Yêu cầu tài chính, hướng nhà, thời gian gọi lại...):
                        </label>
                        <textarea name="c_note" rows="3" placeholder="Nhập các thông tin đặc biệt của khách hàng để tiện theo dõi..." style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;"></textarea>
                    </div>

                    <div style="display:flex;gap:12px;align-items:center;">
                        <button type="submit" name="crm_add_customer" value="1" class="button button-primary" style="background:#2563eb;border-color:#2563eb;font-weight:800;padding:7px 24px;font-size:14.5px;">
                            💾 Lưu Khách Hàng Vào CRM
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=sunshine-crm'); ?>" class="button" style="padding:7px 16px;">Hủy bỏ</a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * TRANG 3: CÀI ĐẶT TELEGRAM & THÔNG BÁO EMAIL (CRM SETTINGS)
     */
    public static function render_crm_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang này.');
        }

        $notice = '';
        $notice_type = 'success';

        // Xử lý Lưu Cài Đặt
        if (isset($_POST['crm_save_settings']) && check_admin_referer('crm_save_settings_nonce')) {
            $tele_token     = sanitize_text_field(wp_unslash($_POST['sunshine_telegram_bot_token'] ?? ''));
            $tele_chat      = sanitize_text_field(wp_unslash($_POST['sunshine_telegram_chat_id'] ?? ''));
            $emails         = sanitize_text_field(wp_unslash($_POST['sunshine_lead_notification_emails'] ?? ''));
            $smtp_enabled   = isset($_POST['sunshine_smtp_enabled']) ? 1 : 0;
            $smtp_host      = sanitize_text_field(wp_unslash($_POST['sunshine_smtp_host'] ?? 'smtp.gmail.com'));
            $smtp_port      = (int) ($_POST['sunshine_smtp_port'] ?? 465);
            $smtp_enc       = sanitize_text_field(wp_unslash($_POST['sunshine_smtp_enc'] ?? 'ssl'));
            $smtp_user      = sanitize_email(wp_unslash($_POST['sunshine_smtp_user'] ?? ''));
            $smtp_pass      = sanitize_text_field(wp_unslash($_POST['sunshine_smtp_pass'] ?? ''));
            $smtp_from_name = sanitize_text_field(wp_unslash($_POST['sunshine_smtp_from_name'] ?? 'Sunshine Group BĐS'));

            update_option('sunshine_telegram_bot_token', $tele_token);
            update_option('sunshine_telegram_chat_id', $tele_chat);
            update_option('sunshine_lead_notification_emails', $emails);
            update_option('sunshine_smtp_enabled', $smtp_enabled);
            update_option('sunshine_smtp_host', $smtp_host);
            update_option('sunshine_smtp_port', $smtp_port);
            update_option('sunshine_smtp_enc', $smtp_enc);
            update_option('sunshine_smtp_user', $smtp_user);
            if (!empty($smtp_pass)) {
                update_option('sunshine_smtp_pass', $smtp_pass);
            }
            update_option('sunshine_smtp_from_name', $smtp_from_name);

            // Đồng bộ admin_email
            if (!empty($emails)) {
                $first = trim(explode(',', $emails)[0]);
                if (is_email($first)) {
                    update_option('admin_email', $first);
                    delete_option('new_admin_email');
                    delete_option('adminhash');
                }
            }

            $notice = '✓ Đã lưu thành công các cài đặt thông báo Telegram và Email!';
        }

        // Xử lý Gửi Thử Telegram
        if (isset($_POST['crm_test_telegram']) && check_admin_referer('crm_test_tele_nonce')) {
            $tele_res = self::send_test_telegram();
            if ($tele_res['success']) {
                $notice = '🎉 Tuyệt vời! Tin nhắn kiểm tra đã được gửi tới Telegram của bạn thành công!';
                $notice_type = 'success';
            } else {
                $notice = '❌ Gửi Telegram thất bại: ' . esc_html($tele_res['error'] ?? 'Vui lòng kiểm tra lại Token hoặc Chat ID.');
                $notice_type = 'error';
            }
        }

        // Xử lý Gửi Thử Email
        if (isset($_POST['crm_test_email']) && check_admin_referer('crm_test_email_nonce')) {
            $test_to = sanitize_email(wp_unslash($_POST['test_email_to'] ?? ''));
            if (empty($test_to)) {
                $test_to = get_option('sunshine_lead_notification_emails', 'luumui1234@gmail.com');
                $test_to = trim(explode(',', $test_to)[0]);
            }
            $mail_res = self::send_test_email($test_to);
            if ($mail_res['success']) {
                $notice = '🎉 Tuyệt vời! Đã gửi thành công email kiểm tra tới <strong>' . esc_html($test_to) . '</strong>. Hãy kiểm tra Hộp thư đến (Inbox)!';
                $notice_type = 'success';
            } else {
                $notice = '❌ Gửi email thất bại: ' . esc_html($mail_res['error'] ?: 'Lỗi cấu hình SMTP hoặc máy chủ.') . '<br><em>Hãy kiểm tra lại Mật khẩu ứng dụng Gmail 16 ký tự.</em>';
                $notice_type = 'error';
            }
        }

        $tele_token     = get_option('sunshine_telegram_bot_token', '');
        $tele_chat      = get_option('sunshine_telegram_chat_id', '');
        $emails         = get_option('sunshine_lead_notification_emails', 'luumui1234@gmail.com');
        $smtp_enabled   = (int) get_option('sunshine_smtp_enabled', 0);
        $smtp_host      = get_option('sunshine_smtp_host', 'smtp.gmail.com');
        $smtp_port      = (int) get_option('sunshine_smtp_port', 465);
        $smtp_enc       = get_option('sunshine_smtp_enc', 'ssl');
        $smtp_user      = get_option('sunshine_smtp_user', 'luumui1234@gmail.com');
        $smtp_pass      = get_option('sunshine_smtp_pass', '');
        $smtp_from_name = get_option('sunshine_smtp_from_name', 'Sunshine Group BĐS');
        ?>
        <div class="wrap" style="max-width:1050px;margin-top:18px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:15px;">
                <a href="<?php echo admin_url('admin.php?page=sunshine-crm'); ?>" style="color:#64748b;text-decoration:none;font-weight:700;">&larr; Quay lại danh sách CRM</a>
            </div>

            <h1 style="font-size:24px;font-weight:800;color:#0b2246;margin:0 0 16px;display:flex;align-items:center;gap:10px;">
                <span class="dashicons dashicons-admin-settings" style="font-size:32px;color:#2563eb;"></span>
                Cài Đặt Thông Báo (Telegram & Email SMTP)
            </h1>

            <?php if (!empty($notice)) : ?>
                <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible" style="padding:10px 14px;"><p style="margin:0;font-weight:600;"><?php echo wp_kses_post($notice); ?></p></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('crm_save_settings_nonce'); ?>

                <!-- KHỐI 1: CÀI ĐẶT TELEGRAM BOT -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:24px;box-shadow:0 1px 4px rgba(0,0,0,0.05);margin-bottom:22px;">
                    <div style="display:flex;align-items:center;gap:10px;border-bottom:2px solid #f1f5f9;padding-bottom:12px;margin-bottom:18px;">
                        <span style="font-size:24px;">✈️</span>
                        <div>
                            <h2 style="font-size:17px;font-weight:800;color:#0b2246;margin:0;">Thông Báo Qua Telegram (Khuyên Dùng Cho Môi Giới BĐS)</h2>
                            <span style="font-size:12.5px;color:#64748b;">Mỗi khi khách đăng ký, tin nhắn báo chuông điện thoại lập tức trong 1 giây kèm số điện thoại để gọi ngay.</span>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px;">
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13px;">
                                🔑 Telegram Bot Token:
                            </label>
                            <input type="text" name="sunshine_telegram_bot_token" value="<?php echo esc_attr($tele_token); ?>" placeholder="ví dụ: 789123456:AAFlk_xyz..." style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13.5px;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13px;">
                                💬 Chat ID (Nhóm hoặc Cá nhân):
                            </label>
                            <input type="text" name="sunshine_telegram_chat_id" value="<?php echo esc_attr($tele_chat); ?>" placeholder="ví dụ: 123456789 hoặc -100123456789" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13.5px;">
                        </div>
                    </div>

                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;font-size:12.5px;color:#334155;line-height:1.6;">
                        📌 <strong>Hướng dẫn lấy Token & Chat ID Telegram (1 phút):</strong><br>
                        1. Mở app Telegram, tìm kiếm <strong>@BotFather</strong> -> bấm <code>/newbot</code> -> đặt tên bot để nhận <strong>Bot Token</strong>.<br>
                        2. Tìm bot <strong>@userinfobot</strong> hoặc <strong>@getmyid_bot</strong> trên Telegram -> bấm Start để lấy số <strong>Chat ID</strong> của bạn (hoặc thêm bot vào nhóm kinh doanh để lấy Chat ID nhóm).
                    </div>
                </div>

                <!-- KHỐI 2: CÀI ĐẶT EMAIL & GMAIL SMTP -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:24px;box-shadow:0 1px 4px rgba(0,0,0,0.05);margin-bottom:24px;">
                    <div style="display:flex;align-items:center;gap:10px;border-bottom:2px solid #f1f5f9;padding-bottom:12px;margin-bottom:18px;">
                        <span style="font-size:24px;">📩</span>
                        <div>
                            <h2 style="font-size:17px;font-weight:800;color:#0b2246;margin:0;">Thông Báo Qua Email & Cấu Hình Gmail SMTP</h2>
                            <span style="font-size:12.5px;color:#64748b;">Gửi email HTML chi tiết khi có khách hàng đăng ký nhận bảng giá & tài liệu.</span>
                        </div>
                    </div>

                    <div style="margin-bottom:18px;">
                        <label style="display:block;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:13px;">
                            Email nhận thông báo khách:
                        </label>
                        <input type="text" name="sunshine_lead_notification_emails" value="<?php echo esc_attr($emails); ?>" placeholder="ví dụ: luumui1234@gmail.com" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;">
                        <span style="font-size:12px;color:#64748b;margin-top:4px;display:block;">Có thể điền nhiều email phân cách nhau bằng dấu phẩy.</span>
                    </div>

                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:800;color:#0b2246;font-size:14px;cursor:pointer;margin-bottom:8px;">
                            <input type="checkbox" name="sunshine_smtp_enabled" value="1" <?php checked($smtp_enabled, 1); ?> id="crm_smtp_toggle" onchange="document.getElementById('crm_smtp_box').style.display = this.checked ? 'block' : 'none';">
                            Bật Gửi Qua Gmail SMTP (Đảm bảo 100% vào Hộp thư đến Inbox)
                        </label>

                        <div id="crm_smtp_box" style="display:<?php echo $smtp_enabled ? 'block' : 'none'; ?>;margin-top:14px;border-top:1px dashed #cbd5e1;padding-top:14px;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                                <div>
                                    <label style="display:block;font-size:12.5px;font-weight:700;margin-bottom:4px;">SMTP Host:</label>
                                    <input type="text" name="sunshine_smtp_host" value="<?php echo esc_attr($smtp_host); ?>" style="width:100%;padding:6px 10px;font-size:13px;border:1px solid #cbd5e1;border-radius:4px;">
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                    <div>
                                        <label style="display:block;font-size:12.5px;font-weight:700;margin-bottom:4px;">Port:</label>
                                        <input type="number" name="sunshine_smtp_port" value="<?php echo esc_attr($smtp_port); ?>" style="width:100%;padding:6px 10px;font-size:13px;border:1px solid #cbd5e1;border-radius:4px;">
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:12.5px;font-weight:700;margin-bottom:4px;">Mã hóa:</label>
                                        <select name="sunshine_smtp_enc" style="width:100%;padding:6px 10px;font-size:13px;border:1px solid #cbd5e1;border-radius:4px;">
                                            <option value="ssl" <?php selected($smtp_enc, 'ssl'); ?>>SSL (465)</option>
                                            <option value="tls" <?php selected($smtp_enc, 'tls'); ?>>TLS (587)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                                <div>
                                    <label style="display:block;font-size:12.5px;font-weight:700;margin-bottom:4px;">Tài khoản Gmail gửi:</label>
                                    <input type="email" name="sunshine_smtp_user" value="<?php echo esc_attr($smtp_user); ?>" placeholder="luumui1234@gmail.com" style="width:100%;padding:6px 10px;font-size:13px;border:1px solid #cbd5e1;border-radius:4px;">
                                </div>
                                <div>
                                    <label style="display:block;font-size:12.5px;font-weight:700;margin-bottom:4px;">Mật khẩu ứng dụng (App Password 16 ký tự):</label>
                                    <input type="password" name="sunshine_smtp_pass" value="<?php echo esc_attr($smtp_pass); ?>" placeholder="<?php echo !empty($smtp_pass) ? '••••••••••••••••' : '16 chữ cái từ Google Account'; ?>" style="width:100%;padding:6px 10px;font-size:13px;border:1px solid #cbd5e1;border-radius:4px;">
                                </div>
                            </div>

                            <div style="margin-bottom:6px;">
                                <label style="display:block;font-size:12.5px;font-weight:700;margin-bottom:4px;">Tên người gửi:</label>
                                <input type="text" name="sunshine_smtp_from_name" value="<?php echo esc_attr($smtp_from_name); ?>" style="width:100%;padding:6px 10px;font-size:13px;border:1px solid #cbd5e1;border-radius:4px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-bottom:24px;">
                    <button type="submit" name="crm_save_settings" value="1" class="button button-primary" style="background:#2563eb;border-color:#2563eb;font-weight:800;padding:8px 26px;font-size:14.5px;">
                        💾 Lưu Tất Cả Cài Đặt
                    </button>
                </div>
            </form>

            <!-- KHỐI THỬ NGHIỆM GỬI THỬ -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                <!-- Test Telegram -->
                <div>
                    <h3 style="font-size:14px;font-weight:800;color:#0b2246;margin:0 0 8px;">🧪 Thử Nghiệm Bắn Tin Telegram</h3>
                    <p style="font-size:12.5px;color:#64748b;margin:0 0 10px;">Bấm nút để gửi ngay 1 tin nhắn test tới Telegram của bạn.</p>
                    <form method="post" action="">
                        <?php wp_nonce_field('crm_test_tele_nonce'); ?>
                        <button type="submit" name="crm_test_telegram" value="1" class="button" style="font-weight:700;">
                            🚀 Gửi Thử Telegram Ngay
                        </button>
                    </form>
                </div>

                <!-- Test Email -->
                <div>
                    <h3 style="font-size:14px;font-weight:800;color:#0b2246;margin:0 0 8px;">🧪 Thử Nghiệm Gửi Thư Email</h3>
                    <p style="font-size:12.5px;color:#64748b;margin:0 0 10px;">Gửi email kiểm tra tới hòm thư quản trị viên.</p>
                    <form method="post" action="" style="display:flex;gap:8px;">
                        <?php wp_nonce_field('crm_test_email_nonce'); ?>
                        <input type="email" name="test_email_to" value="<?php echo esc_attr(trim(explode(',', $emails)[0])); ?>" style="flex:1;padding:5px 10px;font-size:13px;border:1px solid #cbd5e1;border-radius:4px;">
                        <button type="submit" name="crm_test_email" value="1" class="button" style="font-weight:700;">
                            Gửi Thử
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_kbds_lead_form_cao_tang($atts) {
        $atts = is_array($atts) ? $atts : [];
        $atts['type'] = 'cao_tang';
        return self::render_kbds_lead_form($atts);
    }

    public static function render_kbds_lead_form_thap_tang($atts) {
        $atts = is_array($atts) ? $atts : [];
        $atts['type'] = 'thap_tang';
        return self::render_kbds_lead_form($atts);
    }

    public static function render_kbds_quick_lead_card_cao_tang($atts) {
        $atts = is_array($atts) ? $atts : [];
        $atts['type'] = 'cao_tang';
        return self::render_kbds_quick_lead_card($atts);
    }

    public static function render_kbds_quick_lead_card_thap_tang($atts) {
        $atts = is_array($atts) ? $atts : [];
        $atts['type'] = 'thap_tang';
        return self::render_kbds_quick_lead_card($atts);
    }

    public static function render_kbds_quick_lead_card($atts) {
        $default_title = is_singular() ? get_the_title() : 'Dự án Bất Động Sản';
        $atts = shortcode_atts([
            'title'    => 'ĐĂNG KÝ NHẬN BỘ TÀI LIỆU GỐC',
            'subtitle' => 'Nhận ngay Bảng giá dự kiến, Mặt bằng chi tiết & Chính sách ưu đãi qua Zalo trong 3 phút:',
            'project'  => $default_title,
            'type'     => '',
            'demand'   => '',
        ], $atts);

        $product_type = self::get_form_product_type($atts);
        $demand_options = self::get_demand_options($product_type);
        $type_badge_label = ($product_type === 'thap_tang') ? 'SẢN PHẨM THẤP TẦNG' : 'CĂN HỘ CAO TẦNG';

        $submitted = false;
        $submitted_demand = '';
        if (isset($_POST['kbds_quick_submit']) && isset($_POST['kbds_lead_nonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kbds_lead_nonce'])), 'kbds_submit_lead')) {
                $name   = sanitize_text_field(wp_unslash($_POST['kbds_name'] ?? ''));
                $phone  = sanitize_text_field(wp_unslash($_POST['kbds_phone'] ?? ''));
                $demand = sanitize_text_field(wp_unslash($_POST['kbds_demand'] ?? ''));
                if ($name && $phone) {
                    $submitted = true;
                    $submitted_demand = $demand;
                    self::save_lead_submission([
                        'form'       => 'quick_lead_card',
                        'name'       => $name,
                        'phone'      => $phone,
                        'email'      => '',
                        'project'    => sanitize_text_field(wp_unslash($_POST['kbds_project'] ?? $atts['project'])),
                        'type'       => $product_type,
                        'demand'     => $demand,
                        'source_url' => is_singular() ? get_permalink() : home_url($_SERVER['REQUEST_URI'] ?? '/'),
                        'time'       => current_time('mysql'),
                        'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                    ]);
                }
            }
        }

        ob_start();
        ?>
        <div class="kbds-quick-lead-card" style="background:rgba(15,23,42,0.92);backdrop-filter:blur(14px);border:1.5px solid rgba(212,175,55,0.45);border-radius:16px;padding:26px 24px;box-shadow:0 20px 45px rgba(0,0,0,0.45);color:#ffffff;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
                <div style="display:inline-block;background:rgba(212,175,55,0.18);border:1px solid rgba(212,175,55,0.45);color:#fbbf24;font-size:11px;font-weight:800;padding:4px 10px;border-radius:6px;letter-spacing:0.8px;text-transform:uppercase;">
                    ⚡ PHÒNG KINH DOANH CHỦ ĐẦU TƯ
                </div>
                <div style="font-size:11px;font-weight:700;color:#38bdf8;background:rgba(56,189,248,0.12);padding:3px 8px;border-radius:4px;border:1px solid rgba(56,189,248,0.25);">
                    ✦ <?php echo esc_html($type_badge_label); ?>
                </div>
            </div>
            <h3 style="margin:0 0 8px;color:#ffffff;font-size:19px;font-weight:900;line-height:1.3;"><?php echo esc_html($atts['title']); ?></h3>
            <p style="margin:0 0 16px;color:#cbd5e1;font-size:13.5px;line-height:1.55;"><?php echo esc_html($atts['subtitle']); ?></p>
            <?php if ($submitted) : ?>
                <div style="background:#15803d;color:#fff;padding:16px;border-radius:10px;font-weight:700;font-size:14px;line-height:1.5;">
                    ✔ Đã tiếp nhận đăng ký<?php echo $submitted_demand ? ' cho <strong>' . esc_html($submitted_demand) . '</strong>' : ''; ?>! Chuyên viên sẽ gửi trọn bộ tài liệu <strong><?php echo esc_html($atts['project']); ?></strong> qua Zalo cho quý khách ngay lập tức.
                </div>
            <?php else : ?>
                <form method="post" style="display:flex;flex-direction:column;gap:11px;margin:0;">
                    <?php wp_nonce_field('kbds_submit_lead', 'kbds_lead_nonce'); ?>
                    <input type="hidden" name="kbds_project" value="<?php echo esc_attr($atts['project']); ?>" />
                    <input type="hidden" name="kbds_type" value="<?php echo esc_attr($product_type); ?>" />
                    
                    <input type="text" name="kbds_name" placeholder="Họ và tên quý khách *" required style="width:100%;height:48px;padding:0 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.25);background:rgba(255,255,255,0.98);color:#0f172a;font-size:14.5px;font-weight:500;margin:0;box-sizing:border-box;" />
                    
                    <input type="tel" name="kbds_phone" placeholder="Số điện thoại nhận Zalo *" required pattern="[0-9]{9,11}" style="width:100%;height:48px;padding:0 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.25);background:rgba(255,255,255,0.98);color:#0f172a;font-size:14.5px;font-weight:500;margin:0;box-sizing:border-box;" />
                    
                    <select name="kbds_demand" required style="width:100%;height:48px;min-height:48px;line-height:46px;padding:0 36px 0 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.25);background-color:#ffffff;color:#0f172a;font-size:14.5px;font-weight:600;margin:0;box-sizing:border-box;cursor:pointer;-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' width=\'18\' height=\'18\' fill=\'%23475569\'%3E%3Cpath d=\'M7 10l5 5 5-5z\'/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 12px center;">
                        <option value="" disabled <?php echo empty($atts['demand']) ? 'selected' : ''; ?>>-- Chọn nhu cầu quan tâm * --</option>
                        <?php foreach ($demand_options as $val => $label) : ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected($atts['demand'], $val); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" name="kbds_quick_submit" value="1" style="width:100%;padding:13px 18px;background:linear-gradient(135deg,#d4af37 0%,#b8860b 100%);color:#0b2246;border:none;border-radius:8px;font-weight:900;font-size:14.5px;cursor:pointer;text-transform:uppercase;letter-spacing:0.4px;margin-top:2px;box-shadow:0 4px 14px rgba(212,175,55,0.35);transition:all 0.25s ease;">
                        📥 TẢI TRỌN BỘ TÀI LIỆU GỐC →
                    </button>
                    <div style="text-align:center;font-size:12px;color:#94a3b8;margin-top:2px;">
                        🔒 Bảo mật thông tin 100% • Hỗ trợ trực tiếp 24/7
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function output_project_json_ld_schema() {
        if (!is_singular('du_an')) {
            return;
        }
        $pid = get_the_ID();
        $title = get_the_title($pid);
        $url = get_permalink($pid);
        $thumb = get_the_post_thumbnail_url($pid, 'full') ?: home_url('/wp-content/uploads/2026/11/tong-quan-hinode-Solera-pham-hung.jpg');
        $desc = get_post_meta($pid, 'rank_math_description', true) ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $pid)), 35);

        // Build the address from project data so new listings don't inherit a
        // hard-coded Hanoi address. Keep the legacy Hinode address until its
        // fields are migrated to the structured project meta below.
        $street_address = get_post_meta($pid, '_project_street_address', true);
        $address_locality = get_post_meta($pid, '_project_address_locality', true);
        $address_region = get_post_meta($pid, '_project_address_region', true);
        $address_country = get_post_meta($pid, '_project_address_country', true);

        if (964 === (int) $pid) {
            $street_address = $street_address ?: 'Số 28A Phạm Hùng, Phường Mỹ Đình 2, Quận Nam Từ Liêm';
            $address_locality = $address_locality ?: 'Hà Nội';
            $address_region = $address_region ?: 'Hà Nội';
        } elseif ( empty($street_address) && empty($address_locality) && empty($address_region) ) {
            $address_locality = get_post_meta($pid, '_project_location', true);
        }

        $address = ['@type' => 'PostalAddress'];
        if ($street_address) {
            $address['streetAddress'] = $street_address;
        }
        if ($address_locality) {
            $address['addressLocality'] = $address_locality;
        }
        if ($address_region) {
            $address['addressRegion'] = $address_region;
        }
        if ($address_country || count($address) > 1) {
            $address['addressCountry'] = $address_country ?: 'VN';
        }

        $project_entity = [
            '@type'         => ['ApartmentComplex', 'RealEstateListing'],
            '@id'           => $url . '#project',
            'name'          => $title,
            'alternateName' => ($pid == 964) ? [
                'Dự án Hinode Solera Phạm Hùng',
                'Chung cư Hinode Solera Phạm Hùng',
                'Hinode Solera',
                'Chung cư Hinode Solera',
                'Hinode Solera 28A Phạm Hùng',
            ] : [$title],
            'url'           => $url,
            'image'         => $thumb,
            'description'   => $desc,
        ];
        if (count($address) > 1) {
            $project_entity['address'] = $address;
        }

        $graph = [$project_entity];

        if ($pid == 964) {
            $graph[] = [
                '@type'      => 'FAQPage',
                '@id'        => $url . '#faq',
                'mainEntity' => [
                    [
                        '@type' => 'Question',
                        'name'  => 'Dự án Hinode Solera Phạm Hùng (Chung cư Hinode Solera) nằm ở đâu?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'Dự án Hinode Solera Phạm Hùng (Chung cư Hinode Solera) tọa lạc tại Số 28A Phạm Hùng, phường Mỹ Đình 2, quận Nam Từ Liêm, TP. Hà Nội — nằm giữa tòa nhà VNPT Net và SIMCO Building, đối diện Công viên hồ điều hòa Cầu Giấy 32 ha.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name'  => 'Quy mô chung cư Hinode Solera Phạm Hùng như thế nào?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'Chung cư Hinode Solera được quy hoạch trên quỹ đất 4.824 m² (mật độ xây dựng 40%) gồm 25 tầng nổi (đã cất nóc) và 03 tầng hầm rộng 12.258 m², mật độ 16 căn/sàn từ 1PN, 2PN, 3PN đến Dual Key (54,3 – 159,4 m²) sở hữu sổ hồng lâu dài.',
                        ],
                    ],
                ],
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];
        echo "\n<!-- Sunshine Project Pillar Entity Schema -->\n";
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    }

    public static function render_kbds_lead_form($atts) {
        $default_title = is_singular() ? get_the_title() : 'Dự án Bất Động Sản';
        $default_url   = is_singular() ? get_permalink() : home_url('/');

        $atts = shortcode_atts([
            'title'        => '📥 Tải Trọn Bộ Bảng Giá, Mặt Bằng & Pháp Lý Mới Nhất',
            'project'      => $default_title,
            'source_url'   => $default_url,
            'source_title' => $default_title,
            'type'         => '',
            'demand'       => '',
        ], $atts);

        $product_type = self::get_form_product_type($atts);
        $demand_options = self::get_demand_options($product_type);
        $type_badge_label = ($product_type === 'thap_tang') ? 'Dòng sản phẩm Thấp tầng: Liền kề • Biệt thự • Shophouse' : 'Dòng sản phẩm Cao tầng: Studio • Căn hộ 1PN • 2PN • 3PN';

        $submitted = false;
        $submitted_demand = '';
        if (isset($_POST['kbds_lead_submit']) && isset($_POST['kbds_lead_nonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kbds_lead_nonce'])), 'kbds_submit_lead')) {
                $name    = sanitize_text_field(wp_unslash($_POST['kbds_name'] ?? ''));
                $phone   = sanitize_text_field(wp_unslash($_POST['kbds_phone'] ?? ''));
                $email   = sanitize_email(wp_unslash($_POST['kbds_email'] ?? ''));
                $project = sanitize_text_field(wp_unslash($_POST['kbds_project'] ?? ''));
                $demand  = sanitize_text_field(wp_unslash($_POST['kbds_demand'] ?? ''));

                if ($name && $phone) {
                    $submitted = true;
                    $submitted_demand = $demand;
                    self::save_lead_submission([
                        'form'       => 'lead_form',
                        'name'       => $name,
                        'phone'      => $phone,
                        'email'      => $email,
                        'project'    => $project ?: $atts['project'],
                        'type'       => $product_type,
                        'demand'     => $demand,
                        'source_url' => $atts['source_url'],
                        'time'       => current_time('mysql'),
                        'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                    ]);
                }
            }
        }

        ob_start();
        ?>
        <div class="kbds-lead-box" style="background:linear-gradient(135deg,#0a192f 0%,#164675 100%);color:#fff;padding:32px;border-radius:14px;box-shadow:0 12px 35px rgba(0,0,0,0.2);margin:24px 0;border:1px solid rgba(212,175,55,0.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:8px;">
                <h3 style="margin:0;color:#FBBF24;font-size:21px;font-weight:800;"><?php echo esc_html($atts['title']); ?></h3>
                <span style="font-size:12px;font-weight:700;color:#38bdf8;background:rgba(56,189,248,0.12);border:1px solid rgba(56,189,248,0.3);padding:4px 10px;border-radius:20px;">
                    ✦ <?php echo esc_html($type_badge_label); ?>
                </span>
            </div>
            <p style="color:#e2e8f0;margin-bottom:20px;font-size:14.5px;line-height:1.6;">Chuyên viên tư vấn cao cấp sẽ gửi trực tiếp bộ tài liệu PDF nét cao & Bảng tính dòng tiền dự án <strong><?php echo esc_html($atts['project']); ?></strong> qua Zalo/Email trong 3 phút (Cam kết bảo mật 100%).</p>

            <?php if ($submitted) : ?>
                <div style="background:#1b8a4b;color:#fff;padding:16px 20px;border-radius:8px;font-weight:700;font-size:15px;line-height:1.5;">
                    ✔ Đăng ký thành công<?php echo $submitted_demand ? ' cho <strong>' . esc_html($submitted_demand) . '</strong>' : ''; ?>! Hệ thống đã ghi nhận yêu cầu và bộ phận kinh doanh sẽ liên hệ gửi trọn bộ tài liệu cho quý khách ngay lập tức.
                </div>
            <?php else : ?>
                <form method="post" class="kbds-lead-grid-form" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)) auto;gap:12px;align-items:center;">
                    <?php wp_nonce_field('kbds_submit_lead', 'kbds_lead_nonce'); ?>
                    <input type="hidden" name="kbds_project" value="<?php echo esc_attr($atts['project']); ?>" />
                    <input type="hidden" name="kbds_type" value="<?php echo esc_attr($product_type); ?>" />
                    
                    <div>
                        <input type="text" name="kbds_name" placeholder="Họ và tên *" required style="width:100%;height:48px;padding:0 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14.5px;background:#fff;color:#0f172a;font-weight:500;box-sizing:border-box;margin:0;" />
                    </div>
                    <div>
                        <input type="tel" name="kbds_phone" placeholder="Số điện thoại / Zalo *" required pattern="[0-9]{9,11}" style="width:100%;height:48px;padding:0 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14.5px;background:#fff;color:#0f172a;font-weight:500;box-sizing:border-box;margin:0;" />
                    </div>
                    <div>
                        <select name="kbds_demand" required style="width:100%;height:48px;min-height:48px;line-height:46px;padding:0 36px 0 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14.5px;background:#fff;color:#0f172a;font-weight:600;box-sizing:border-box;margin:0;cursor:pointer;-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' width=\'18\' height=\'18\' fill=\'%23475569\'%3E%3Cpath d=\'M7 10l5 5 5-5z\'/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 12px center;">
                            <option value="" disabled <?php echo empty($atts['demand']) ? 'selected' : ''; ?>>-- Chọn nhu cầu quan tâm * --</option>
                            <?php foreach ($demand_options as $val => $label) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($atts['demand'], $val); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <input type="email" name="kbds_email" placeholder="Email nhận file PDF (tùy chọn)" style="width:100%;height:48px;padding:0 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14.5px;background:#fff;color:#0f172a;box-sizing:border-box;margin:0;" />
                    </div>
                    <div class="kbds-form-submit-col">
                        <button type="submit" name="kbds_lead_submit" value="1" style="width:100%;height:48px;line-height:48px;padding:0 24px;background:#F59E0B;color:#000;border:none;border-radius:8px;font-weight:800;font-size:14.5px;cursor:pointer;white-space:nowrap;transition:all 0.2s ease;box-shadow:0 4px 12px rgba(245,158,11,0.35);display:inline-flex;align-items:center;justify-content:center;">
                            GỬI YÊU CẦU →
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_kbds_project_news($atts) {
        $atts = shortcode_atts([
            'project_id'   => get_the_ID(),
            'project_name' => get_the_title(),
            'limit'        => 8,
            'title'        => '',
        ], $atts);

        $pid = (int) $atts['project_id'];
        $p_title = $atts['project_name'];
        if (!$p_title && $pid) {
            $p_title = get_the_title($pid);
        }
        $p_slug = $pid ? get_post_field('post_name', $pid) : '';
        $limit = 8; // Cố định hiển thị 2 hàng x 4 cột = 8 bài viết
        $section_title = $atts['title'] ?: 'Tin Tức & Bài Viết Về Dự Án ' . $p_title;

        // Query posts
        $query_args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 8,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post__not_in'   => [799, 165, $pid] // Loại trừ bài viết gốc / trang mẹ
        ];

        // Lọc theo tag hoặc tìm kiếm theo tên dự án
        $tag = get_term_by('slug', $p_slug, 'post_tag');
        if (!$tag && $pid) {
            $tags = wp_get_post_tags($pid);
            if (!empty($tags)) {
                $tag = $tags[0];
            }
        }

        if ($tag) {
            $query_args['tag_id'] = $tag->term_id;
        } else {
            // Tách từ khóa tìm kiếm sạch từ tiêu đề dự án
            $clean_kw = preg_replace('/(Dự Án|Tập đoàn Sunshine|Bắc Thăng Long|-)/ui', ' ', $p_title);
            $clean_kw = trim(preg_replace('/\s+/', ' ', $clean_kw));
            if ($clean_kw) {
                $query_args['s'] = $clean_kw;
            } else {
                $query_args['s'] = 'Handico Sunshine';
            }
        }

        $news_query = new WP_Query($query_args);
        if (!$news_query->have_posts()) {
            return '';
        }

        ob_start();
        ?>
        <div class="kbds-project-news-wrapper" style="margin:24px 0 10px;">
            <div class="kbds-project-news-grid">
                <?php while ($news_query->have_posts()) : $news_query->the_post(); 
                    $thumb_id = get_post_thumbnail_id();
                    $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'medium_large') : '';
                    $cats = get_the_category();
                    $cat_name = !empty($cats) ? $cats[0]->name : 'Tin Tập Đoàn Sunshine';
                ?>
                <div class="kbds-news-card" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,0.05);display:flex;flex-direction:column;transition:transform 0.25s ease, box-shadow 0.25s ease;">
                    <a href="<?php the_permalink(); ?>" style="display:block;position:relative;padding-top:56.25%;overflow:hidden;background:#f1f5f9;">
                        <?php if ($thumb_url) : ?>
                            <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;transition:transform 0.3s ease;" loading="lazy" />
                        <?php else: ?>
                            <div style="position:absolute;top:0;left:0;width:100%;height:100%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;font-weight:600;">Sunshine Group</div>
                        <?php endif; ?>
                        <span style="position:absolute;top:10px;left:10px;background:#B45309;color:#fff;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:4px;text-transform:uppercase;">
                            <?php echo esc_html($cat_name); ?>
                        </span>
                    </a>
                    <div style="padding:14px 16px 16px;flex:1;display:flex;flex-direction:column;">
                        <h4 style="font-size:14px;font-weight:700;line-height:1.45;margin:0 0 8px;color:#0F172A;min-height:42px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <a href="<?php the_permalink(); ?>" style="color:#0F172A;text-decoration:none;">
                                <?php the_title(); ?>
                            </a>
                        </h4>
                        <p style="font-size:12.5px;color:#64748B;line-height:1.6;margin:0 0 12px;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <?php echo esc_html(wp_trim_words(get_the_excerpt(), 14)); ?>
                        </p>
                        <div style="border-top:1px dashed #E2E8F0;padding-top:8px;display:flex;justify-content:space-between;align-items:center;">
                            <a href="<?php the_permalink(); ?>" style="color:#D97706;font-weight:700;font-size:12.5px;text-decoration:none;">
                                Đọc chi tiết →
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_kbds_loan_calculator($atts = []) {
        $atts = shortcode_atts([
            'price' => '35',
            'ratio' => '70',
            'years' => '20',
            'rate'  => '7.5'
        ], $atts);

        ob_start();
        ?>
        <div class="kbds-loan-calc" style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:12px;padding:26px;margin:28px 0;box-shadow:0 4px 15px rgba(0,0,0,0.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <span style="font-size:24px;">🧮</span>
                <h3 style="margin:0;color:#0a192f;font-size:20px;font-weight:800;">Bảng Tính Lãi Vay Ngân Hàng Mua Dự Án (Tham Khảo)</h3>
            </div>
            <p style="color:#64748b;font-size:14px;margin-bottom:20px;">Dự toán số tiền thanh toán hàng tháng theo dư nợ giảm dần hoặc theo tiến độ ưu đãi từ ngân hàng tài trợ.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                <div>
                    <label style="font-weight:700;font-size:13.5px;color:#1e293b;display:block;margin-bottom:6px;">Giá trị BĐS (Tỷ VNĐ):</label>
                    <input type="number" id="kbds_calc_price" value="<?php echo esc_attr($atts['price']); ?>" step="0.5" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#fff;" oninput="kbdsCalculateLoan()" />
                </div>
                <div>
                    <label style="font-weight:700;font-size:13.5px;color:#1e293b;display:block;margin-bottom:6px;">Tỷ lệ vay ngân hàng (%):</label>
                    <input type="number" id="kbds_calc_ratio" value="<?php echo esc_attr($atts['ratio']); ?>" min="10" max="85" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#fff;" oninput="kbdsCalculateLoan()" />
                </div>
                <div>
                    <label style="font-weight:700;font-size:13.5px;color:#1e293b;display:block;margin-bottom:6px;">Thời hạn vay (Năm):</label>
                    <input type="number" id="kbds_calc_years" value="<?php echo esc_attr($atts['years']); ?>" min="1" max="35" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#fff;" oninput="kbdsCalculateLoan()" />
                </div>
                <div>
                    <label style="font-weight:700;font-size:13.5px;color:#1e293b;display:block;margin-bottom:6px;">Lãi suất sau ưu đãi (%/năm):</label>
                    <input type="number" id="kbds_calc_rate" value="<?php echo esc_attr($atts['rate']); ?>" step="0.1" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#fff;" oninput="kbdsCalculateLoan()" />
                </div>
            </div>
            <div id="kbds_calc_result" style="background:#fff;padding:20px;border-radius:10px;margin-top:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;border-left:4px solid #b45309;box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                <div>
                    <span style="color:#64748b;font-size:13px;font-weight:600;">Vốn tự có cần chuẩn bị:</span><br/>
                    <strong id="kbds_res_equity" style="font-size:20px;color:#0a192f;font-weight:800;">10.5 Tỷ</strong>
                </div>
                <div>
                    <span style="color:#64748b;font-size:13px;font-weight:600;">Số tiền vay ngân hàng:</span><br/>
                    <strong id="kbds_res_loan" style="font-size:20px;color:#1b8a4b;font-weight:800;">24.5 Tỷ</strong>
                </div>
                <div>
                    <span style="color:#64748b;font-size:13px;font-weight:600;">Gốc + Lãi tháng đầu cao nhất:</span><br/>
                    <strong id="kbds_res_monthly" style="font-size:20px;color:#b45309;font-weight:800;">255.2 Triệu/tháng</strong>
                </div>
            </div>
            <script>
            function kbdsCalculateLoan() {
                var price = parseFloat(document.getElementById('kbds_calc_price').value) || 0;
                var ratio = parseFloat(document.getElementById('kbds_calc_ratio').value) || 0;
                var years = parseFloat(document.getElementById('kbds_calc_years').value) || 1;
                var rate  = parseFloat(document.getElementById('kbds_calc_rate').value) || 0;

                var loanBillion = price * (ratio / 100);
                var equityBillion = price - loanBillion;
                var months = years * 12;
                var principalMillion = (loanBillion * 1000) / months;
                var firstInterestMillion = (loanBillion * 1000) * (rate / 100 / 12);
                var firstMonthTotal = principalMillion + firstInterestMillion;

                document.getElementById('kbds_res_equity').innerText = equityBillion.toFixed(2) + ' Tỷ';
                document.getElementById('kbds_res_loan').innerText = loanBillion.toFixed(2) + ' Tỷ';
                document.getElementById('kbds_res_monthly').innerText = firstMonthTotal.toFixed(1) + ' Triệu/tháng';
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', kbdsCalculateLoan);
            } else {
                kbdsCalculateLoan();
            }
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Nạp toàn bộ CSS Sunshine chuẩn (Logo Avatar, Sticky Header, Lưới Dự án 4 cột, 1340px)
     * Đảm bảo website luôn hiển thị đẹp 100% không phụ thuộc vào theme
     */
    public static function output_sunshine_styles() {
        ?>
        <style id="sunshine-master-styles">
        /* 1. ĐỘ RỘNG WEBSITE CHUẨN HIỆN ĐẠI 1340PX */
        .container, .row:not(.row-full-width), .kbds-container, .header-inner, .site-width {
            max-width: 1340px !important;
        }
        .row.row-full-width {
            max-width: 100% !important;
            width: 100% !important;
        }
        .kbds-2col-grid > p,
        .kbds-2col-grid > br,
        .kbds-hero-grid > p,
        .kbds-hero-grid > br {
            display: none !important;
        }
        
        /* 2. ẨN HOÀN TOÀN THANH TOP-BAR */
        #top-bar, .header-top, .top-bar-nav {
            display: none !important;
            height: 0 !important;
            min-height: 0 !important;
            visibility: hidden !important;
        }

        /* 3. LOGO AVATAR CHUYÊN VIÊN TRÒN VIỀN VÀNG */
        #logo {
            display: flex !important;
            align-items: center !important;
            height: 100% !important;
        }
        #logo a {
            display: flex !important;
            align-items: center !important;
        }
        #logo img {
            max-height: 54px !important;
            height: 54px !important;
            width: 54px !important;
            min-width: 54px !important;
            min-height: 54px !important;
            border-radius: 50% !important;
            border: 2px solid #D4AF37 !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25) !important;
            background: #FFFFFF !important;
            object-fit: cover !important;
            object-position: center 15% !important;
            margin: 0 !important;
            transition: transform 0.25s ease, box-shadow 0.25s ease !important;
        }
        #logo a img:not(:first-child) { display: none !important; }
        #logo a img:first-child { display: block !important; }
        .stuck #logo img {
            max-height: 48px !important;
            height: 48px !important;
            width: 48px !important;
            min-height: 48px !important;
            min-width: 48px !important;
        }

        /* 4. THANH HEADER CUỘN STICKY ĐỔI MÀU NAVY-GOLD */
        #header {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        body.single-du_an #header,
        body.single-du_an .header-wrapper {
            background-color: #0b2246 !important;
            background: #0b2246 !important;
        }
        .header-wrapper.stuck,
        .stuck.header-wrapper {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            z-index: 99999 !important;
            background: #0b2246 !important;
            background-color: #0b2246 !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
            border-bottom: 2px solid #D4AF37 !important;
            transition: all 0.3s ease !important;
        }
        .stuck .header-main {
            height: 70px !important;
            min-height: 70px !important;
        }
        body.admin-bar .header-wrapper.stuck,
        body.admin-bar .stuck.header-wrapper {
            top: 32px !important;
        }
        @media (max-width: 782px) {
            body.admin-bar .header-wrapper.stuck,
            body.admin-bar .stuck.header-wrapper { top: 46px !important; }
        }

        /* 5. MENU CHÍNH 1 HÀNG NGANG CHỮ VÀNG KIM */
        .header-nav-main {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            flex-wrap: nowrap !important;
            gap: 2px !important;
        }
        .header-nav-main > li > a {
            font-family: 'Plus Jakarta Sans', 'Montserrat', 'Roboto Condensed', sans-serif !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            letter-spacing: 0.15px !important;
            color: #fcfc73 !important;
            padding: 8px 5px !important;
            text-transform: uppercase !important;
            white-space: nowrap !important;
            transition: color 0.25s ease, text-shadow 0.25s ease !important;
        }
        .header-nav-main > li > a:hover {
            color: #FFFFFF !important;
            text-shadow: 0 0 10px rgba(252, 252, 115, 0.7) !important;
        }
        .header-nav-main > li.active > a,
        .header-nav-main > li.current-menu-item > a,
        .header-nav-main > li.current_page_item > a {
            color: #fcfc73 !important;
            border-bottom: 2px solid #D4AF37 !important;
        }
        .header-nav > li.html,
        .header-nav > li.header-divider,
        .header-search,
        .header-search-form,
        .search-popup {
            display: none !important;
        }

        /* 6. DROPDOWN MENU CON CHUẨN SUNSHINE NAVY */
        .nav-dropdown, .sub-menu {
            border-radius: 10px !important;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35) !important;
            border: 1px solid rgba(212, 175, 55, 0.3) !important;
            padding: 8px 0 !important;
            background: #0B2246 !important;
        }
        .nav-dropdown > li > a, .sub-menu > li > a {
            font-size: 13.5px !important;
            font-weight: 600 !important;
            color: #F8FAFC !important;
            padding: 10px 18px !important;
        }

        /* 8. ĐỒNG BỘ GIAO DIỆN FORM CŨ (CONTACT FORM 7) THEO PHONG CÁCH BĐS CAO CẤP */
        .wpcf7 {
            margin: 15px 0 !important;
        }
        .wpcf7-form {
            background: rgba(15, 23, 42, 0.94) !important;
            padding: 24px 22px !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(212, 175, 55, 0.35) !important;
            color: #ffffff !important;
        }
        .wpcf7-form p {
            margin-bottom: 12px !important;
        }
        .wpcf7-form input[type="text"],
        .wpcf7-form input[type="tel"],
        .wpcf7-form input[type="email"],
        .wpcf7-form textarea {
            width: 100% !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            font-size: 14px !important;
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.08) !important;
            transition: all 0.25s ease !important;
            box-sizing: border-box !important;
        }
        .wpcf7-form input[type="text"]::placeholder,
        .wpcf7-form input[type="tel"]::placeholder,
        .wpcf7-form input[type="email"]::placeholder,
        .wpcf7-form textarea::placeholder {
            color: #cbd5e1 !important;
        }
        .wpcf7-form input[type="text"]:focus,
        .wpcf7-form input[type="tel"]:focus,
        .wpcf7-form input[type="email"]:focus,
        .wpcf7-form textarea:focus {
            border-color: #d4af37 !important;
            background: rgba(255, 255, 255, 0.14) !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.3) !important;
        }
        .wpcf7-form input[type="submit"],
        .wpcf7-submit {
            width: 100% !important;
            height: 46px !important;
            line-height: 46px !important;
            padding: 0 20px !important;
            background: linear-gradient(135deg, #d4af37 0%, #b45309 100%) !important;
            color: #0b2246 !important;
            font-weight: 800 !important;
            font-size: 14.5px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            border: none !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            box-shadow: 0 4px 14px rgba(212, 175, 55, 0.4) !important;
            transition: all 0.25s ease !important;
            margin-top: 6px !important;
        }
        .wpcf7-form input[type="submit"]:hover,
        .wpcf7-submit:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.6) !important;
            background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%) !important;
        }
        .wpcf7-response-output {
            border-radius: 8px !important;
            padding: 12px 16px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            margin-top: 14px !important;
            border: none !important;
        }
        .wpcf7 form.sent .wpcf7-response-output {
            background: rgba(16, 185, 129, 0.2) !important;
            border: 1px solid #10b981 !important;
            color: #34d399 !important;
        }
        .wpcf7 form.failed .wpcf7-response-output,
        .wpcf7 form.invalid .wpcf7-response-output {
            background: rgba(239, 68, 68, 0.2) !important;
            border: 1px solid #ef4444 !important;
            color: #f87171 !important;
        }

                /* 7. LƯỚI CARD DỰ ÁN 4 CỘT CHUẨN KENHBATDONGSAN */
        .kbds-home-projects-grid,
        .kbds-grid-4 {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 24px !important;
            margin-top: 24px !important;
            margin-bottom: 40px !important;
            width: 100% !important;
        }
        .kbds-project-card,
        .kbds-card {
            background: #FFFFFF !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid #E2E8F0 !important;
            display: flex !important;
            flex-direction: column !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease !important;
        }
        .kbds-project-card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15) !important;
        }
        .kbds-card-media {
            position: relative !important;
            width: 100% !important;
            height: 220px !important;
            overflow: hidden !important;
            background: #0B2246 !important;
        }
        .kbds-card-media a {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
        }
        .kbds-card-media img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block !important;
            transition: transform 0.5s ease !important;
        }
        .kbds-project-card:hover .kbds-card-media img {
            transform: scale(1.05) !important;
        }
        .kbds-badge-status {
            position: absolute !important;
            top: 12px !important;
            left: 12px !important;
            background: #0B2246 !important;
            color: #fcfc73 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            padding: 4px 10px !important;
            border-radius: 4px !important;
            border: 1px solid #D4AF37 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            z-index: 2 !important;
        }
        .kbds-card-body {
            padding: 16px 18px !important;
            display: flex !important;
            flex-direction: column !important;
            flex-grow: 1 !important;
        }
        .kbds-developer-tag {
            font-size: 12px !important;
            font-weight: 600 !important;
            color: #64748B !important;
            margin-bottom: 6px !important;
            display: block !important;
        }
        .kbds-card-title {
            font-size: 15px !important;
            font-weight: 700 !important;
            line-height: 1.4 !important;
            margin: 0 0 8px 0 !important;
            color: #0B2246 !important;
        }
        .kbds-card-title a {
            color: #0B2246 !important;
            text-decoration: none !important;
        }
        .kbds-card-title a:hover {
            color: #C59B27 !important;
        }
        .kbds-card-location {
            font-size: 12.5px !important;
            color: #64748B !important;
            margin: 0 0 14px 0 !important;
            line-height: 1.4 !important;
        }
        .kbds-card-footer {
            margin-top: auto !important;
            padding-top: 12px !important;
            border-top: 1px solid #F1F5F9 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }
        .kbds-price-label {
            font-size: 11px !important;
            color: #94A3B8 !important;
            display: block !important;
        }
        .kbds-price-value {
            font-size: 14px !important;
            font-weight: 800 !important;
            color: #C59B27 !important;
        }
        .kbds-btn-card {
            padding: 6px 12px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            color: #0B2246 !important;
            background: #F8FAFC !important;
            border: 1px solid #CBD5E1 !important;
            border-radius: 6px !important;
            text-decoration: none !important;
        }
        .kbds-btn-card:hover {
            background: #0B2246 !important;
            color: #FFFFFF !important;
        }

        /* 8. RESPONSIVE CHO TABLET & MOBILE */
        @media (max-width: 1024px) {
            .kbds-home-projects-grid, .kbds-grid-4 {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 18px !important;
            }
        }
        @media (max-width: 600px) {
            .kbds-home-projects-grid, .kbds-grid-4 {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
        }

        /* ========================================================
           9. THANH MENU PHỤ CỐ ĐỊNH CHUẨN DỰ ÁN (STICKY SUBNAV) - LUXURY PILLS
           ======================================================== */
        html {
            scroll-behavior: smooth !important;
        }

        :root {
            --kbds-subnav-top: 70px;
        }
        body.admin-bar {
            --kbds-subnav-top: 102px;
        }
        @media (max-width: 782px) {
            body.admin-bar {
                --kbds-subnav-top: 116px;
            }
        }

        .kbds-project-subnav {
            position: -webkit-sticky !important;
            position: sticky !important;
            top: var(--kbds-subnav-top, 70px) !important;
            z-index: 9998 !important;
            background: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06) !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 8px 0 !important;
            transition: top 0.2s ease, background 0.2s ease !important;
        }

        .kbds-project-subnav .kbds-container,
        .kbds-project-subnav .kbds-subnav-container {
            max-width: 1300px !important;
            margin: 0 auto !important;
            padding: 0 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
            box-sizing: border-box !important;
        }

        /* Brand tên dự án bên trái trên Desktop */
        .kbds-subnav-brand {
            font-weight: 800 !important;
            font-size: 13px !important;
            color: #0b2246 !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            white-space: nowrap !important;
            order: 1 !important;
            flex-shrink: 0 !important;
        }

        .kbds-subnav-dot {
            display: inline-block !important;
            width: 8px !important;
            height: 8px !important;
            background: #d4af37 !important;
            border-radius: 50% !important;
            box-shadow: 0 0 8px rgba(212, 175, 55, 0.9) !important;
            flex-shrink: 0 !important;
        }

        /* Danh sách nút cuộn dạng Pills */
        .kbds-subnav-links {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            white-space: nowrap !important;
            flex: 1 1 auto !important;
            justify-content: flex-start !important;
            order: 2 !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            -webkit-overflow-scrolling: touch !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
            padding: 2px 2px !important;
        }

        .kbds-subnav-links::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        .kbds-subnav-link {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #334155 !important;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 20px !important;
            text-decoration: none !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            padding: 6px 14px !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            white-space: nowrap !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
            flex-shrink: 0 !important;
            line-height: 1.3 !important;
        }

        .kbds-subnav-link:hover,
        .kbds-subnav-link:focus {
            color: #0b2246 !important;
            border-color: #0b2246 !important;
            background: #f8fafc !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 3px 8px rgba(11, 34, 70, 0.12) !important;
        }

        .kbds-subnav-link.active {
            color: #ffffff !important;
            background: #0b2246 !important;
            border-color: #0b2246 !important;
            box-shadow: 0 3px 10px rgba(11, 34, 70, 0.25) !important;
            transform: translateY(-1px) !important;
        }

        /* Nút CTA Nhận Báo Giá Gốc viền vàng gradient */
        .kbds-subnav-cta {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%) !important;
            color: #0b2246 !important;
            border: 1px solid #c59b27 !important;
            padding: 7px 18px !important;
            border-radius: 20px !important;
            font-size: 12.5px !important;
            font-weight: 800 !important;
            text-decoration: none !important;
            text-transform: uppercase !important;
            letter-spacing: 0.3px !important;
            white-space: nowrap !important;
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.35) !important;
            transition: all 0.2s ease !important;
            order: 3 !important;
            margin-left: auto !important;
            flex-shrink: 0 !important;
            line-height: 1.3 !important;
        }

        .kbds-subnav-cta:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.5) !important;
            color: #ffffff !important;
        }

        /* Offset cuộn cho các section chuẩn */
        section[id], div[id], h2[id], h3[id],
        #tong-quan, #vi-tri, #quy-hoach, #san-pham, #tien-ich, #chinh-sach, #tien-do, #hoi-dap, #tin-tuc-du-an, #dang-ky, #nhan-bang-gia, #mat-bang, #can-ho-dual-key, #tieu-chuan-ban-giao, #tien-do-thuc-te {
            scroll-margin-top: 135px !important;
        }

        /* RESPONSIVE MOBILE & TABLET (< 850px) */
        @media (max-width: 850px) {
            .kbds-subnav-brand {
                display: none !important;
            }
            .kbds-project-subnav {
                padding: 6px 0 !important;
            }
            .kbds-project-subnav .kbds-container,
            .kbds-project-subnav .kbds-subnav-container {
                padding: 0 8px !important;
                gap: 6px !important;
                justify-content: flex-start !important;
            }
            .kbds-subnav-links {
                order: 1 !important;
                gap: 6px !important;
                padding: 2px 0 !important;
                flex: 1 1 auto !important;
            }
            .kbds-subnav-link {
                font-size: 12px !important;
                padding: 5px 11px !important;
                border-radius: 16px !important;
                line-height: 1.2 !important;
            }
            .kbds-subnav-cta {
                order: 2 !important;
                font-size: 11.5px !important;
                padding: 5px 12px !important;
                border-radius: 16px !important;
                line-height: 1.2 !important;
            }
            section[id], div[id], h2[id], h3[id] {
                scroll-margin-top: 125px !important;
            }
        }

        .kbds-project-subnav br,
        .kbds-subnav-links br,
        .kbds-subnav-brand br,
        .kbds-container br {
            display: none !important;
        }

        .kbds-project-subnav p {
            margin: 0 !important;
            padding: 0 !important;
            display: contents !important;
            order: 3 !important;
            margin-left: auto !important;
        }

        /* ========================================================
           10. BỐ CỤC TRANG DỰ ÁN CHI TIẾT & RESPONSIVE MOBILE
           ======================================================== */
        .kbds-container,
        .single-du_an .kbds-container {
            max-width: 1240px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding-left: 20px !important;
            padding-right: 20px !important;
            box-sizing: border-box !important;
            width: 100% !important;
        }

        .single-du_an table {
            width: 100% !important;
            max-width: 100% !important;
            border-collapse: collapse !important;
        }

        .single-du_an img,
        .single-du_an figure img {
            max-width: 100% !important;
            height: auto !important;
        }

        @media (max-width: 820px) {
            .kbds-container,
            .single-du_an .kbds-container {
                padding-left: 14px !important;
                padding-right: 14px !important;
            }
            .single-du_an [style*="grid-template-columns:1.15fr 0.85fr"],
            .single-du_an [style*="grid-template-columns: 1.15fr 0.85fr"],
            .single-du_an [style*="grid-template-columns:1.1fr 0.9fr"],
            .single-du_an [style*="grid-template-columns: 1.1fr 0.9fr"],
            .single-du_an [style*="grid-template-columns:1.2fr 0.8fr"],
            .single-du_an [style*="grid-template-columns: 1.2fr 0.8fr"],
            .single-du_an [style*="grid-template-columns:1fr 1fr"],
            .single-du_an [style*="grid-template-columns: 1fr 1fr"],
            .single-du_an [style*="grid-template-columns:1fr 1.2fr"],
            .single-du_an [style*="grid-template-columns: 1fr 1.2fr"],
            .single-du_an [style*="grid-template-columns:repeat(2,1fr)"],
            .single-du_an [style*="grid-template-columns: repeat(2, 1fr)"],
            .single-du_an .kbds-2col-grid,
            .single-du_an .kbds-hero-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 20px !important;
            }
        }

        /* ========================================================
           11. CHÂN TRANG LUXURY (SUNSHINE FOOTER) & MENU DƯỚI RÕ NÉT
           ======================================================== */
        #footer .footer-widgets.footer-1,
        #footer .footer-widgets.footer-2 {
            display: none !important;
        }

        .sunshine-luxury-footer {
            background: linear-gradient(180deg, #0a192f 0%, #071224 100%) !important;
            color: #e2e8f0 !important;
            border-top: 2px solid #d4af37 !important;
            padding: 58px 0 42px !important;
            font-size: 14.5px !important;
            line-height: 1.7 !important;
        }

        .sunshine-footer-container {
            max-width: 1340px !important;
            margin: 0 auto !important;
            padding: 0 20px !important;
        }

        .sunshine-footer-grid {
            display: grid !important;
            grid-template-columns: 1.35fr 1.05fr 0.95fr 1.15fr !important;
            gap: 36px !important;
        }

        .sunshine-footer-brand {
            display: flex !important;
            align-items: center !important;
            gap: 14px !important;
            margin-bottom: 16px !important;
        }

        .sunshine-footer-logo {
            width: 56px !important;
            height: 56px !important;
            border-radius: 50% !important;
            border: 2px solid #d4af37 !important;
            object-fit: cover !important;
            background: #ffffff !important;
            flex-shrink: 0 !important;
        }

        .sunshine-footer-brand-title {
            color: #ffffff !important;
            font-size: 18px !important;
            font-weight: 900 !important;
            letter-spacing: 0.4px !important;
            margin: 0 !important;
            line-height: 1.25 !important;
            text-transform: uppercase !important;
        }

        .sunshine-footer-brand-sub {
            color: #fbbf24 !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            letter-spacing: 0.8px !important;
            text-transform: uppercase !important;
            display: block !important;
            margin-top: 3px !important;
        }

        .sunshine-footer-heading {
            color: #fbbf24 !important;
            font-size: 15.5px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.7px !important;
            margin: 0 0 18px 0 !important;
            padding-bottom: 10px !important;
            border-bottom: 1px solid rgba(212, 175, 55, 0.28) !important;
            position: relative !important;
        }

        .sunshine-footer-heading::after {
            content: "" !important;
            position: absolute !important;
            left: 0 !important;
            bottom: -1px !important;
            width: 48px !important;
            height: 2px !important;
            background: #d4af37 !important;
        }

        .sunshine-footer-list {
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .sunshine-footer-list li {
            margin: 0 0 10px 0 !important;
            padding: 0 !important;
        }

        .sunshine-footer-list li a {
            color: #e2e8f0 !important;
            text-decoration: none !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .sunshine-footer-list li a::before {
            content: "›" !important;
            color: #d4af37 !important;
            font-weight: 900 !important;
            font-size: 16px !important;
            line-height: 1 !important;
        }

        .sunshine-footer-list li a:hover {
            color: #fbbf24 !important;
            transform: translateX(4px) !important;
        }

        .sunshine-footer-contact-item {
            display: flex !important;
            align-items: flex-start !important;
            gap: 10px !important;
            margin-bottom: 12px !important;
            color: #e2e8f0 !important;
            font-size: 14px !important;
        }

        .sunshine-footer-contact-item strong {
            color: #ffffff !important;
        }

        .sunshine-footer-contact-item a {
            color: #fbbf24 !important;
            font-weight: 700 !important;
            text-decoration: none !important;
        }

        /* Thanh chân trang dưới cùng (.absolute-footer) */
        #footer .absolute-footer {
            background-color: #050d1a !important;
            border-top: 1px solid rgba(212, 175, 55, 0.25) !important;
            padding: 18px 0 !important;
            color: #cbd5e1 !important;
        }

        #footer .absolute-footer .container {
            max-width: 1340px !important;
            padding: 0 20px !important;
        }

        #footer .absolute-footer ul.footer-nav {
            margin: 0 0 10px 0 !important;
            padding: 0 !important;
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px 22px !important;
            align-items: center !important;
        }

        #footer .absolute-footer ul.footer-nav li {
            margin: 0 !important;
            border: none !important;
        }

        #footer .absolute-footer ul.footer-nav li a {
            color: #ffffff !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px !important;
            text-transform: uppercase !important;
            opacity: 1 !important;
            text-decoration: none !important;
            transition: color 0.2s ease !important;
        }

        #footer .absolute-footer ul.footer-nav li a:hover,
        #footer .absolute-footer ul.footer-nav li.current-menu-item a {
            color: #fbbf24 !important;
        }

        #footer .absolute-footer .copyright-footer {
            color: #cbd5e1 !important;
            font-size: 13.5px !important;
            opacity: 1 !important;
        }

        #footer .absolute-footer .copyright-footer strong {
            color: #fbbf24 !important;
            font-weight: 800 !important;
        }

        @media (max-width: 960px) {
            .sunshine-footer-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 28px !important;
            }
        }

        @media (max-width: 600px) {
            .sunshine-footer-grid {
                grid-template-columns: 1fr !important;
                gap: 24px !important;
            }
            #footer .absolute-footer ul.footer-nav {
                justify-content: center !important;
                gap: 8px 14px !important;
            }
        }
        /* 15. RESPONSIVE LEAD FORMS (CAO TẦNG & THẤP TẦNG) */
        .kbds-lead-box,
        .kbds-quick-lead-card {
            box-sizing: border-box !important;
        }
        .kbds-lead-box *,
        .kbds-quick-lead-card * {
            box-sizing: border-box !important;
        }
        .kbds-lead-box select,
        .kbds-quick-lead-card select,
        .kbds-lead-grid-form select,
        select[name="kbds_demand"] {
            display: block !important;
            width: 100% !important;
            height: 48px !important;
            min-height: 48px !important;
            max-height: 48px !important;
            line-height: 46px !important;
            padding: 0 40px 0 14px !important;
            font-size: 14.5px !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='18' height='18' fill='%23475569'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 14px center !important;
            background-size: 18px 18px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            vertical-align: middle !important;
            margin: 0 !important;
            box-shadow: none !important;
            outline: none !important;
            transition: all 0.2s ease !important;
        }
        .kbds-lead-box select option,
        select[name="kbds_demand"] option {
            padding: 10px 14px !important;
            font-size: 14.5px !important;
            color: #0f172a !important;
            background: #ffffff !important;
        }
        .kbds-lead-box input[type="text"],
        .kbds-lead-box input[type="tel"],
        .kbds-lead-box input[type="email"],
        .kbds-quick-lead-card input[type="text"],
        .kbds-quick-lead-card input[type="tel"],
        .kbds-quick-lead-card input[type="email"],
        .kbds-lead-grid-form input {
            display: block !important;
            width: 100% !important;
            height: 48px !important;
            min-height: 48px !important;
            max-height: 48px !important;
            line-height: 46px !important;
            padding: 0 14px !important;
            font-size: 14.5px !important;
            font-weight: 500 !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            margin: 0 !important;
            box-shadow: none !important;
            outline: none !important;
            transition: all 0.2s ease !important;
        }
        .kbds-lead-box select:focus,
        .kbds-lead-box input:focus,
        .kbds-quick-lead-card select:focus,
        .kbds-quick-lead-card input:focus,
        .kbds-lead-grid-form select:focus,
        .kbds-lead-grid-form input:focus {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25) !important;
            background-color: #ffffff !important;
        }
        .kbds-lead-box button[type="submit"],
        .kbds-lead-grid-form button[type="submit"] {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 48px !important;
            min-height: 48px !important;
            line-height: 48px !important;
            padding: 0 28px !important;
            font-size: 14.5px !important;
            font-weight: 800 !important;
            border-radius: 8px !important;
            border: none !important;
            cursor: pointer !important;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35) !important;
            transition: all 0.2s ease !important;
        }
        .kbds-lead-box button[type="submit"]:hover,
        .kbds-lead-grid-form button[type="submit"]:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 18px rgba(245, 158, 11, 0.45) !important;
            background: #d97706 !important;
        }
        @media (max-width: 991px) {
            .kbds-lead-grid-form {
                grid-template-columns: 1fr 1fr !important;
                gap: 12px !important;
            }
            .kbds-lead-grid-form .kbds-form-submit-col {
                grid-column: span 2 !important;
            }
        }
        @media (max-width: 600px) {
            .kbds-lead-grid-form {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }
            .kbds-lead-grid-form .kbds-form-submit-col {
                grid-column: span 1 !important;
            }
        }
        </style>
        <?php
    }

    public static function filter_footer_copyright_text( $val ) {
        return 'Copyright [ux_current_year] &copy; <strong>Luu Van Mui</strong>';
    }

    public static function render_sunshine_luxury_footer() {
        $logo_url = home_url( '/wp-content/uploads/2026/11/avatar-cropped-white.png' );
        ?>
        <div class="sunshine-luxury-footer">
            <div class="sunshine-footer-container">
                <!-- TOP BAR: LOGO & 3 HỘP THÔNG TIN NHANH -->
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:20px;padding-bottom:32px;margin-bottom:36px;border-bottom:1px solid rgba(255,255,255,0.1)">
                    <div class="sunshine-footer-brand" style="margin-bottom:0 !important">
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="Tập đoàn Sunshine - BĐS Indochine" class="sunshine-footer-logo" width="58" height="58" />
                        <div>
                            <h3 class="sunshine-footer-brand-title">Tập Đoàn Sunshine • Indochine</h3>
                            <span class="sunshine-footer-brand-sub">Cổng Thông Tin Dự Án, Quy Hoạch &amp; Giỏ Hàng F1</span>
                        </div>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;gap:14px">
                        <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.12);border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:12px">
                            <span style="font-size:18px">📞</span>
                            <div>
                                <div style="font-size:11.5px;color:#94a3b8;line-height:1.2">Hotline 24/7</div>
                                <a href="tel:0942655111" style="color:#fbbf24;font-weight:800;font-size:15px;text-decoration:none">0942.655.111</a>
                            </div>
                        </div>
                        <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.12);border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:12px">
                            <span style="font-size:18px">🏢</span>
                            <div>
                                <div style="font-size:11.5px;color:#94a3b8;line-height:1.2">Hệ thống văn phòng</div>
                                <strong style="color:#fbbf24;font-weight:800;font-size:14.5px">Hà Nội &amp; TP.HCM</strong>
                            </div>
                        </div>
                        <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.12);border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:12px">
                            <span style="font-size:18px">✉️</span>
                            <div>
                                <div style="font-size:11.5px;color:#94a3b8;line-height:1.2">Chăm sóc khách hàng</div>
                                <a href="mailto:luumui91@gmail.com" style="color:#fbbf24;font-weight:800;font-size:14.5px;text-decoration:none">luumui91@gmail.com</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4 CỘT CHÍNH -->
                <div class="sunshine-footer-grid">
                    <!-- CỘT 1: CÔNG TY CỔ PHẦN BẤT ĐỘNG SẢN INDOCHINE -->
                    <div>
                        <h4 class="sunshine-footer-heading">Công Ty Cổ Phần Bất Động Sản Indochine</h4>
                        <div class="sunshine-footer-contact-item">
                            <span>📍</span>
                            <div><strong>Trụ sở TP.HCM:</strong> Số 45 Thạch Thị Thanh, Phường Tân Định, TP. Hồ Chí Minh, Việt Nam.</div>
                        </div>
                        <div class="sunshine-footer-contact-item">
                            <span>📍</span>
                            <div><strong>Trụ sở Hà Nội:</strong> Tầng 2 tòa nhà Capital Elite, Số 18 Phạm Hùng, Phường Mỹ Đình, Nam Từ Liêm, Hà Nội &amp; Tầng 1 CT4 Vimeco Nguyễn Chánh, Yên Hòa, Hà Nội.</div>
                        </div>
                        <div class="sunshine-footer-contact-item">
                            <span>📞</span>
                            <div><a href="tel:0942655111" style="color:#ffffff">0942.655.111</a> – <a href="mailto:luumui91@gmail.com" style="color:#ffffff">luumui91@gmail.com</a></div>
                        </div>
                    </div>

                    <!-- CỘT 2: HƯỚNG DẪN & DỊCH VỤ -->
                    <div>
                        <h4 class="sunshine-footer-heading">Hướng Dẫn &amp; Dịch Vụ</h4>
                        <ul class="sunshine-footer-list">
                            <li><a href="<?php echo esc_url( home_url( '/gioi-thieu/' ) ); ?>">Về chúng tôi</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Liên hệ &amp; Văn phòng</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/bao-gia-va-tu-van-du-an/' ) ); ?>">Báo giá và tư vấn dự án</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/cam-nang-phap-ly/' ) ); ?>">Cẩm nang đầu tư &amp; Pháp lý</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>">Sơ đồ website (Sitemap)</a></li>
                        </ul>
                    </div>

                    <!-- CỘT 3: QUY ĐỊNH & PHÁP LÝ -->
                    <div>
                        <h4 class="sunshine-footer-heading">Quy Định &amp; Pháp Lý</h4>
                        <ul class="sunshine-footer-list">
                            <li><a href="<?php echo esc_url( home_url( '/chinh-sach-bao-mat/' ) ); ?>">Chính sách bảo mật</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/dieu-khoan-su-dung/' ) ); ?>">Điều khoản sử dụng</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/tuyen-bo-mien-tru/' ) ); ?>">Tuyên bố miễn trừ</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/quy-che-hoat-dong-san/' ) ); ?>">Quy chế hoạt động sàn</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/che-tai-xu-ly-vi-pham/' ) ); ?>">Chế tài xử lý vi phạm</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/giai-quyet-khieu-nai/' ) ); ?>">Giải quyết khiếu nại</a></li>
                        </ul>
                    </div>

                    <!-- CỘT 4: QUỐC GIA & NGÔN NGỮ / DỰ ÁN TIÊU BIỂU -->
                    <div>
                        <h4 class="sunshine-footer-heading">Quốc Gia &amp; Ngôn Ngữ</h4>
                        <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.16);border-radius:10px;padding:10px 16px;color:#ffffff;font-weight:700;font-size:13.5px;margin-bottom:14px">
                            🌐 Việt Nam (Tiếng Việt)
                        </div>
                        <p style="color:#94a3b8;font-size:13.5px;line-height:1.65;margin:0 0 14px">
                            Cổng thông tin dự án, quy hoạch &amp; thẩm định bất động sản cao cấp hàng đầu Việt Nam.
                        </p>
                        <div style="font-size:12.5px;color:#cbd5e1;line-height:1.7">
                            <strong style="color:#fbbf24">Dự án tiêu biểu:</strong>
                            <a href="<?php echo esc_url( home_url( '/du-an/handico-sunshine-new-ha-noi/' ) ); ?>" style="color:#cbd5e1;text-decoration:underline">Handico Sunshine New Hà Nội</a> •
                            <a href="<?php echo esc_url( home_url( '/du-an/hinode-solera-pham-hung/' ) ); ?>" style="color:#cbd5e1;text-decoration:underline">Hinode Solera Phạm Hùng</a> •
                            <a href="<?php echo esc_url( home_url( '/du-an/noble-west-lake-ha-noi/' ) ); ?>" style="color:#cbd5e1;text-decoration:underline">Noble West Lake</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Endpoint hỗ trợ đồng bộ các tệp tin media chính xác theo đường dẫn gốc
     */
    public static function register_asset_sync_route() {
                register_rest_route( 'sunshine/v1', '/scan-forms', [
            'methods'             => 'GET',
            'callback'            => function() {
                global $wpdb;
                $cf7_forms = $wpdb->get_results("SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE post_type = 'wpcf7_contact_form'");
                $posts_with_forms = $wpdb->get_results("SELECT ID, post_type, post_title, post_name FROM {$wpdb->posts} WHERE (post_content LIKE '%[contact-form-7%' OR post_content LIKE '%[kbds_lead%' OR post_content LIKE '%<form%') AND post_status = 'publish'");
                $blocks = $wpdb->get_results("SELECT ID, post_title, post_name, post_content FROM {$wpdb->posts} WHERE post_type = 'blocks'");
                
                $block_forms = [];
                foreach ($blocks as $b) {
                    if (strpos($b->post_content, 'form') !== false || strpos($b->post_content, 'contact') !== false || strpos($b->post_content, 'dang-ky') !== false || strpos($b->post_content, 'kbds') !== false) {
                        $block_forms[] = [
                            'ID' => $b->ID,
                            'title' => $b->post_title,
                            'slug' => $b->post_name,
                            'content' => substr($b->post_content, 0, 300)
                        ];
                    }
                }

                return [
                    'cf7_forms' => $cf7_forms,
                    'posts_with_forms' => $posts_with_forms,
                    'block_forms' => $block_forms,
                    'theme_mods' => get_theme_mods()
                ];
            },
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ] );

        register_rest_route( 'sunshine/v1', '/upload-asset', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_asset_upload' ],
            'permission_callback' => function() {
                return current_user_can( 'upload_files' );
            },
        ] );

        register_rest_route( 'sunshine/v1', '/flush-permalinks', [
            'methods'             => 'POST',
            'callback'            => function() {
                set_theme_mod( 'footer_left_text', 'Copyright [ux_current_year] &copy; <strong>Luu Van Mui</strong>' );
                flush_rewrite_rules( true );
                if ( ! headers_sent() ) {
                    header( 'X-LiteSpeed-Purge: *' );
                }
                do_action( 'litespeed_purge_all' );
                wp_cache_flush();
                return [ 'success' => true, 'rules_flushed' => true, 'cache_purged' => true ];
            },
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ] );

        register_rest_route( 'sunshine/v1', '/sync-seo-meta', [
            'methods'             => 'POST',
            'callback'            => function( $request ) {
                $params = $request->get_json_params();
                $post_id = (int) ( $params['post_id'] ?? 0 );
                if ( ! $post_id && ! empty( $params['slug'] ) ) {
                    $p = get_page_by_path( sanitize_text_field( $params['slug'] ), OBJECT, [ 'post', 'page', 'du_an' ] );
                    if ( $p ) {
                        $post_id = $p->ID;
                    }
                }
                if ( ! $post_id ) {
                    return new WP_Error( 'not_found', 'Post not found', [ 'status' => 404 ] );
                }
                $meta = $params['meta'] ?? [];
                foreach ( $meta as $k => $v ) {
                    update_post_meta( $post_id, sanitize_key( $k ), $v );
                }
                return [ 'success' => true, 'post_id' => $post_id, 'updated_keys' => array_keys( $meta ) ];
            },
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ] );

        register_rest_route( 'sunshine/v1', '/fix-htaccess', [
            'methods'             => 'POST',
            'callback'            => function() {
                $htaccess_file = ABSPATH . '.htaccess';
                $exists = file_exists( $htaccess_file );
                $current_content = $exists ? file_get_contents( $htaccess_file ) : '';
                
                // Clean all old WordPress rewrite blocks
                $cleaned = preg_replace( '/# BEGIN WordPress.*?# END WordPress\s*/s', '', $current_content );
                
                $wp_rules = "# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\nRewriteBase /\nRewriteRule ^index\\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . /index.php [L]\n</IfModule>\n# END WordPress\n\n";
                
                $final_content = $wp_rules . trim( $cleaned ) . "\n";
                $saved = file_put_contents( $htaccess_file, $final_content );
                
                flush_rewrite_rules( true );
                
                return [
                    'success' => true,
                    'htaccess_exists' => $exists,
                    'cleaned_content' => $final_content,
                    'saved' => $saved,
                ];
            },
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    public static function handle_asset_upload( $request ) {
        $params = $request->get_json_params();
        $rel_path = sanitize_text_field( $params['path'] ?? '' );
        $b64_data = $params['data'] ?? '';

        if ( empty( $rel_path ) || empty( $b64_data ) ) {
            return new WP_Error( 'missing_params', 'Thiếu đường dẫn hoặc dữ liệu file', [ 'status' => 400 ] );
        }

        $rel_path = ltrim( str_replace( '\\', '/', $rel_path ), '/' );
        if ( strpos( $rel_path, 'wp-content/uploads/' ) !== 0 ) {
            return new WP_Error( 'invalid_path', 'Đường dẫn phải nằm trong wp-content/uploads/', [ 'status' => 403 ] );
        }

        $dest = ABSPATH . $rel_path;
        $dir = dirname( $dest );
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        $raw = base64_decode( $b64_data );
        if ( $raw === false ) {
            return new WP_Error( 'invalid_data', 'Dữ liệu base64 không hợp lệ', [ 'status' => 400 ] );
        }

        $written = file_put_contents( $dest, $raw );
        if ( $written === false ) {
            return new WP_Error( 'write_failed', 'Không thể ghi file lên ổ cứng', [ 'status' => 500 ] );
        }

        $att_id = 0;
        if ( ! empty( $params['register_media'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            global $wpdb;
            $rel_meta = substr( $rel_path, strlen( 'wp-content/uploads/' ) );
            $att_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
                $rel_meta
            ) );

            $filetype = wp_check_filetype( basename( $dest ), null );
            $attachment = [
                'guid'           => home_url( '/' . $rel_path ),
                'post_mime_type' => $filetype['type'] ?: 'image/jpeg',
                'post_title'     => sanitize_text_field( $params['title'] ?? pathinfo( $dest, PATHINFO_FILENAME ) ),
                'post_excerpt'   => sanitize_text_field( $params['caption'] ?? '' ),
                'post_content'   => sanitize_text_field( $params['caption'] ?? '' ),
                'post_status'    => 'inherit',
            ];

            if ( $att_id ) {
                $attachment['ID'] = $att_id;
                wp_update_post( $attachment );
            } else {
                $att_id = wp_insert_attachment( $attachment, $dest );
            }

            $attach_data = wp_generate_attachment_metadata( $att_id, $dest );
            wp_update_attachment_metadata( $att_id, $attach_data );
            if ( ! empty( $params['alt'] ) ) {
                update_post_meta( $att_id, '_wp_attachment_image_alt', sanitize_text_field( $params['alt'] ) );
            }
        }

        return [
            'success'       => true,
            'path'          => $rel_path,
            'bytes'         => $written,
            'url'           => home_url( '/' . $rel_path ),
            'attachment_id' => $att_id,
        ];
    }

    /**
     * Script điều hướng mượt mà, định vị pixel-perfect và Scrollspy cho menu phụ
     */
    public static function output_sunshine_subnav_scripts() {
        ?>
        <script id="kbds-subnav-js">
        (function() {
            function initKbdsSubnav() {
                var subnav = document.querySelector('.kbds-project-subnav');
                if (!subnav) return;

                var header = document.querySelector('.header-wrapper.stuck') || document.querySelector('.header-wrapper') || document.getElementById('header');
                var adminBar = document.getElementById('wpadminbar');

                function updateSubnavPosition() {
                    var adminH = (adminBar && window.getComputedStyle(adminBar).position === 'fixed') ? adminBar.offsetHeight : 0;
                    var headerH = 70;
                    if (header) {
                        var isFixed = window.getComputedStyle(header).position === 'fixed' || header.classList.contains('stuck');
                        if (isFixed) {
                            var rect = header.getBoundingClientRect();
                            headerH = Math.round(rect.height);
                        } else {
                            headerH = window.innerWidth <= 820 ? 70 : 70;
                        }
                    }
                    var totalTop = headerH + adminH;
                    subnav.style.top = totalTop + 'px';
                    document.documentElement.style.setProperty('--kbds-subnav-top', totalTop + 'px');
                }

                window.addEventListener('scroll', updateSubnavPosition, { passive: true });
                window.addEventListener('resize', updateSubnavPosition, { passive: true });
                updateSubnavPosition();

                // Smooth scrolling
                var links = subnav.querySelectorAll('.kbds-subnav-link');
                var linksContainer = subnav.querySelector('.kbds-subnav-links');

                links.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        var href = this.getAttribute('href');
                        if (href && href.charAt(0) === '#' && href.length > 1) {
                            var target = document.querySelector(href);
                            if (target) {
                                e.preventDefault();
                                var adminH = (adminBar && window.getComputedStyle(adminBar).position === 'fixed') ? adminBar.offsetHeight : 0;
                                var headerH = 70;
                                if (header) {
                                    headerH = Math.round(header.getBoundingClientRect().height);
                                }
                                var subnavH = subnav.offsetHeight || 44;
                                var offset = target.getBoundingClientRect().top + window.pageYOffset - (headerH + subnavH + adminH + 8);
                                window.scrollTo({ top: offset, behavior: 'smooth' });

                                links.forEach(function(l) { l.classList.remove('active'); });
                                link.classList.add('active');
                            }
                        }
                    });
                });

                // Scrollspy
                var sections = [];
                links.forEach(function(l) {
                    var href = l.getAttribute('href');
                    if (href && href.charAt(0) === '#' && href.length > 1) {
                        var sec = document.querySelector(href);
                        if (sec) sections.push({ link: l, el: sec });
                    }
                });

                if (sections.length > 0) {
                    function onScrollSpy() {
                        var scrollPos = window.pageYOffset + 180;
                        var current = null;
                        for (var i = 0; i < sections.length; i++) {
                            var top = sections[i].el.offsetTop;
                            if (scrollPos >= top) {
                                current = sections[i];
                            }
                        }
                        if (current) {
                            links.forEach(function(l) { l.classList.remove('active'); });
                            current.link.classList.add('active');
                            if (linksContainer && window.innerWidth <= 850) {
                                var linkLeft = current.link.offsetLeft;
                                var linkWidth = current.link.offsetWidth;
                                var cWidth = linksContainer.offsetWidth;
                                linksContainer.scrollTo({
                                    left: linkLeft - (cWidth / 2) + (linkWidth / 2),
                                    behavior: 'smooth'
                                });
                            }
                        }
                    }
                    window.addEventListener('scroll', onScrollSpy, { passive: true });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initKbdsSubnav);
            } else {
                initKbdsSubnav();
            }
        })();
        </script>
        <?php
    }
}

Sunshine_Project_Manager::init();
