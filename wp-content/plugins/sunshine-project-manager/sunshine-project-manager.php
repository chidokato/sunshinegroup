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

        // Quản lý cột hiển thị và bộ lọc trong Admin
        add_filter( 'manage_du_an_posts_columns', [ __CLASS__, 'admin_columns' ] );
        add_action( 'manage_du_an_posts_custom_column', [ __CLASS__, 'admin_column_content' ], 10, 2 );
        add_action( 'restrict_manage_posts', [ __CLASS__, 'admin_filter_dropdowns' ] );

        // Menu quản lý Leads khách hàng đăng ký
        add_action( 'admin_menu', [ __CLASS__, 'register_admin_menus' ] );

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
     * Lưu trữ lead khách hàng & gửi email thông báo
     */
    public static function save_lead_submission($data) {
        $leads = get_option('sunshine_customer_leads', []);
        if (!is_array($leads)) {
            $leads = [];
        }
        array_unshift($leads, $data);
        if (count($leads) > 200) {
            $leads = array_slice($leads, 0, 200);
        }
        update_option('sunshine_customer_leads', $leads);

        $admin_email = get_option('admin_email');
        $to_emails = array_unique(array_filter([$admin_email, 'luumui91@gmail.com']));
        $project_name = !empty($data['project']) ? $data['project'] : 'Dự án BĐS';
        $demand_name = !empty($data['demand']) ? $data['demand'] : 'Chung';
        $type_text = ($data['type'] === 'thap_tang') ? 'Thấp tầng (Liền kề / Biệt thự / Shophouse)' : 'Cao tầng (Studio / Căn hộ 1-3PN)';

        $subject = '🔔 [Khách Đăng Ký Mới] ' . $project_name . ' - ' . $demand_name;
        $body = "THÔNG TIN KHÁCH HÀNG ĐĂNG KÝ:\n"
              . "------------------------------------\n"
              . "• Họ và tên: " . ($data['name'] ?? '') . "\n"
              . "• Số điện thoại / Zalo: " . ($data['phone'] ?? '') . "\n"
              . "• Nhu cầu quan tâm: " . $demand_name . "\n"
              . "• Dòng sản phẩm: " . $type_text . "\n"
              . "• Dự án: " . $project_name . "\n"
              . "• Email: " . (!empty($data['email']) ? $data['email'] : 'Không cung cấp') . "\n"
              . "• Form: " . ($data['form'] ?? 'Website') . "\n"
              . "• Thời gian gửi: " . ($data['time'] ?? current_time('mysql')) . "\n"
              . "• Link đăng ký: " . ($data['source_url'] ?? '') . "\n"
              . "• IP Khách hàng: " . ($data['ip'] ?? '') . "\n";

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        @wp_mail($to_emails, $subject, $body, $headers);
    }

    public static function register_admin_menus() {
        $leads = get_option('sunshine_customer_leads', []);
        $unread_count = is_array($leads) ? count($leads) : 0;
        $menu_title = 'Khách Đăng Ký';
        if ($unread_count > 0) {
            $menu_title .= ' <span class="awaiting-mod count-' . $unread_count . '"><span class="pending-count" style="background:#d4af37;color:#0b2246;font-weight:bold;padding:1px 6px;border-radius:10px;">' . $unread_count . '</span></span>';
        }

        add_submenu_page(
            'edit.php?post_type=du_an',
            'Danh Sách Khách Hàng Đăng Ký (Leads)',
            $menu_title,
            'manage_options',
            'sunshine-customer-leads',
            [ __CLASS__, 'render_leads_admin_page' ]
        );
    }

    public static function render_leads_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Bạn không có quyền truy cập trang này.');
        }

        $leads = get_option('sunshine_customer_leads', []);
        if (!is_array($leads)) {
            $leads = [];
        }

        // Xử lý xuất Excel / CSV
        if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            check_admin_referer('sunshine_export_leads');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=danh-sach-khach-hang-' . date('Y-m-d') . '.csv');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM cho Excel
            fputcsv($output, ['STT', 'Thời gian', 'Họ và tên', 'Số điện thoại', 'Nhu cầu quan tâm', 'Dòng sản phẩm', 'Dự án', 'Email', 'Link nguồn', 'IP']);
            $i = 1;
            foreach ($leads as $l) {
                fputcsv($output, [
                    $i++,
                    $l['time'] ?? '',
                    $l['name'] ?? '',
                    $l['phone'] ?? '',
                    $l['demand'] ?? '',
                    (($l['type'] ?? '') === 'thap_tang') ? 'Thấp tầng' : 'Cao tầng',
                    $l['project'] ?? '',
                    $l['email'] ?? '',
                    $l['source_url'] ?? '',
                    $l['ip'] ?? ''
                ]);
            }
            fclose($output);
            exit;
        }

        // Xử lý xóa 1 lead
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['index'])) {
            check_admin_referer('sunshine_delete_lead');
            $idx = (int) $_GET['index'];
            if (isset($leads[$idx])) {
                array_splice($leads, $idx, 1);
                update_option('sunshine_customer_leads', $leads);
            }
            wp_safe_redirect(admin_url('edit.php?post_type=du_an&page=sunshine-customer-leads&deleted=1'));
            exit;
        }

        // Xử lý xóa tất cả
        if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
            check_admin_referer('sunshine_clear_leads');
            update_option('sunshine_customer_leads', []);
            wp_safe_redirect(admin_url('edit.php?post_type=du_an&page=sunshine-customer-leads&cleared=1'));
            exit;
        }

        $total = count($leads);
        $cao_tang_count = 0;
        $thap_tang_count = 0;
        foreach ($leads as $l) {
            if (($l['type'] ?? '') === 'thap_tang') {
                $thap_tang_count++;
            } else {
                $cao_tang_count++;
            }
        }
        $admin_email = get_option('admin_email');
        ?>
        <div class="wrap" style="max-width:1250px;margin-top:20px;">
            <h1 style="display:flex;align-items:center;gap:10px;font-size:24px;font-weight:700;color:#0b2246;">
                <span class="dashicons dashicons-id-alt" style="font-size:30px;width:30px;height:30px;color:#d4af37;"></span>
                Quản Lý Khách Hàng Đăng Ký (Leads Nhận Báo Giá)
            </h1>

            <div style="background:#fff;border-left:4px solid #d4af37;padding:16px 20px;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,0.05);margin:20px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;">
                <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
                    <div>
                        <span style="font-size:12px;color:#64748b;display:block;text-transform:uppercase;font-weight:700;">Tổng lượt đăng ký</span>
                        <strong style="font-size:22px;color:#0b2246;"><?php echo $total; ?> khách</strong>
                    </div>
                    <div style="border-left:1px solid #e2e8f0;padding-left:24px;">
                        <span style="font-size:12px;color:#64748b;display:block;text-transform:uppercase;font-weight:700;">Căn hộ Cao Tầng</span>
                        <strong style="font-size:20px;color:#0284c7;"><?php echo $cao_tang_count; ?></strong>
                    </div>
                    <div style="border-left:1px solid #e2e8f0;padding-left:24px;">
                        <span style="font-size:12px;color:#64748b;display:block;text-transform:uppercase;font-weight:700;">Sản phẩm Thấp Tầng</span>
                        <strong style="font-size:20px;color:#16a34a;"><?php echo $thap_tang_count; ?></strong>
                    </div>
                    <div style="border-left:1px solid #e2e8f0;padding-left:24px;">
                        <span style="font-size:12px;color:#64748b;display:block;text-transform:uppercase;font-weight:700;">Email nhận thông báo</span>
                        <code style="background:#f1f5f9;color:#0f172a;font-size:13px;padding:3px 8px;border-radius:4px;">luumui91@gmail.com, <?php echo esc_html($admin_email); ?></code>
                    </div>
                </div>

                <div style="display:flex;gap:10px;">
                    <?php if ($total > 0) : ?>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=du_an&page=sunshine-customer-leads&action=export_csv'), 'sunshine_export_leads'); ?>" class="button button-primary" style="background:#0b2246;border-color:#0b2246;font-weight:700;">
                            📥 Xuất File Excel (CSV)
                        </a>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=du_an&page=sunshine-customer-leads&action=clear_all'), 'sunshine_clear_leads'); ?>" class="button" onclick="return confirm('Bạn có chắc chắn muốn xóa tất cả danh sách khách hàng này không?');" style="color:#dc2626;">
                            Xóa tất cả
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_GET['deleted'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Đã xóa thành công thông tin khách hàng.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['cleared'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Đã làm trống danh sách đăng ký.</p></div>
            <?php endif; ?>

            <?php if (empty($leads)) : ?>
                <div style="background:#fff;padding:60px 20px;text-align:center;border-radius:8px;border:1px dashed #cbd5e1;">
                    <div style="font-size:48px;margin-bottom:10px;">📋</div>
                    <h3 style="margin:0 0 8px;color:#0f172a;">Chưa có lượt đăng ký mới</h3>
                    <p style="color:#64748b;margin:0;">Khi khách hàng để lại thông tin tại bất kỳ form đăng ký nào trên website, dữ liệu sẽ tự động xuất hiện tại đây và đồng thời gửi thông báo tới email của bạn.</p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped posts" style="border-radius:6px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);margin-top:16px;">
                    <thead>
                        <tr>
                            <th style="width:40px;text-align:center;">#</th>
                            <th style="width:140px;">Thời gian</th>
                            <th style="width:190px;">Khách hàng</th>
                            <th style="width:150px;">Nhu cầu quan tâm</th>
                            <th style="width:110px;">Phân khúc</th>
                            <th style="width:180px;">Dự án</th>
                            <th style="width:180px;">Email</th>
                            <th>Nguồn trang</th>
                            <th style="width:60px;text-align:center;">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $idx => $lead) : 
                            $phone = preg_replace('/[^0-9]/', '', $lead['phone'] ?? '');
                            $is_thap_tang = ($lead['type'] ?? '') === 'thap_tang';
                        ?>
                            <tr>
                                <td style="text-align:center;font-weight:700;color:#94a3b8;"><?php echo $idx + 1; ?></td>
                                <td style="font-size:12.5px;color:#64748b;">
                                    <?php echo esc_html($lead['time'] ?? ''); ?>
                                </td>
                                <td>
                                    <strong style="font-size:14px;color:#0b2246;display:block;margin-bottom:4px;"><?php echo esc_html($lead['name'] ?? 'Chưa rõ'); ?></strong>
                                    <div style="display:flex;gap:6px;align-items:center;">
                                        <a href="tel:<?php echo esc_attr($phone); ?>" style="font-weight:700;color:#0284c7;text-decoration:none;">
                                            📞 <?php echo esc_html($lead['phone'] ?? ''); ?>
                                        </a>
                                        <?php if ($phone) : ?>
                                            <a href="https://zalo.me/<?php echo esc_attr($phone); ?>" target="_blank" style="display:inline-block;background:#0068ff;color:#fff;font-size:11px;font-weight:700;padding:2px 6px;border-radius:3px;text-decoration:none;">Zalo</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="display:inline-block;background:#fef3c7;color:#92400e;font-size:12px;font-weight:700;padding:3px 8px;border-radius:4px;border:1px solid #fde68a;">
                                        <?php echo esc_html($lead['demand'] ?? 'Chung'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($is_thap_tang) : ?>
                                        <span style="display:inline-block;background:#dcfce7;color:#166534;font-size:11px;font-weight:700;padding:2px 7px;border-radius:4px;">Thấp tầng</span>
                                    <?php else : ?>
                                        <span style="display:inline-block;background:#e0f2fe;color:#075985;font-size:11px;font-weight:700;padding:2px 7px;border-radius:4px;">Cao tầng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color:#0f172a;font-size:13px;"><?php echo esc_html($lead['project'] ?? 'Dự án'); ?></strong>
                                </td>
                                <td style="font-size:13px;">
                                    <?php echo !empty($lead['email']) ? '<a href="mailto:' . esc_attr($lead['email']) . '">' . esc_html($lead['email']) . '</a>' : '<span style="color:#94a3b8;">—</span>'; ?>
                                </td>
                                <td style="font-size:12px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?php if (!empty($lead['source_url'])) : ?>
                                        <a href="<?php echo esc_url($lead['source_url']); ?>" target="_blank" title="<?php echo esc_attr($lead['source_url']); ?>"><?php echo esc_html($lead['source_url']); ?></a>
                                    <?php else : ?>
                                        <span style="color:#94a3b8;">Trang web</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=du_an&page=sunshine-customer-leads&action=delete&index=' . $idx), 'sunshine_delete_lead'); ?>" onclick="return confirm('Xóa khách hàng này?');" style="color:#ef4444;text-decoration:none;font-size:16px;" title="Xóa">
                                        &times;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
                    
                    <input type="text" name="kbds_name" placeholder="Họ và tên quý khách *" required style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.25);background:rgba(255,255,255,0.98);color:#0f172a;font-size:14px;font-weight:500;margin:0;box-sizing:border-box;" />
                    
                    <input type="tel" name="kbds_phone" placeholder="Số điện thoại nhận Zalo *" required pattern="[0-9]{9,11}" style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.25);background:rgba(255,255,255,0.98);color:#0f172a;font-size:14px;font-weight:500;margin:0;box-sizing:border-box;" />
                    
                    <select name="kbds_demand" required style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.25);background:rgba(255,255,255,0.98);color:#0f172a;font-size:14px;font-weight:600;margin:0;box-sizing:border-box;cursor:pointer;-webkit-appearance:menulist;">
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
                        <input type="text" name="kbds_name" placeholder="Họ và tên *" required style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;color:#0f172a;font-weight:500;box-sizing:border-box;margin:0;" />
                    </div>
                    <div>
                        <input type="tel" name="kbds_phone" placeholder="Số điện thoại / Zalo *" required pattern="[0-9]{9,11}" style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;color:#0f172a;font-weight:500;box-sizing:border-box;margin:0;" />
                    </div>
                    <div>
                        <select name="kbds_demand" required style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;color:#0f172a;font-weight:600;box-sizing:border-box;margin:0;cursor:pointer;-webkit-appearance:menulist;">
                            <option value="" disabled <?php echo empty($atts['demand']) ? 'selected' : ''; ?>>-- Chọn nhu cầu quan tâm * --</option>
                            <?php foreach ($demand_options as $val => $label) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($atts['demand'], $val); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <input type="email" name="kbds_email" placeholder="Email nhận file PDF (tùy chọn)" style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;color:#0f172a;box-sizing:border-box;margin:0;" />
                    </div>
                    <div class="kbds-form-submit-col">
                        <button type="submit" name="kbds_lead_submit" value="1" style="width:100%;padding:12px 24px;background:#F59E0B;color:#000;border:none;border-radius:8px;font-weight:800;font-size:14.5px;cursor:pointer;white-space:nowrap;transition:all 0.2s ease;box-shadow:0 4px 12px rgba(245,158,11,0.35);">
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
        .kbds-lead-grid-form select,
        .kbds-lead-grid-form input,
        .kbds-quick-lead-card select,
        .kbds-quick-lead-card input {
            transition: all 0.2s ease !important;
        }
        .kbds-lead-grid-form select:focus,
        .kbds-lead-grid-form input:focus,
        .kbds-quick-lead-card select:focus,
        .kbds-quick-lead-card input:focus {
            border-color: #d4af37 !important;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.25) !important;
            outline: none !important;
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
