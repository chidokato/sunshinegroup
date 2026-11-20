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
        add_filter( 'flatsome_ux_builder_post_types', [ __CLASS__, 'enable_ux_builder' ] );

        // Hỗ trợ Thẻ Tags (post_tag) cho post type du_an
        register_taxonomy_for_object_type( 'post_tag', 'du_an' );

        // Tự động liên kết 2 chiều giữa Dự Án BĐS và Bài Viết Tin Tức thông qua Thẻ Tag
        add_filter( 'the_content', [ __CLASS__, 'enhance_content_with_project_links' ] );

        // Nạp toàn bộ CSS giao diện Sunshine chuẩn (Header, Logo, Grid 4 cột, 1340px)
        add_action( 'wp_head', [ __CLASS__, 'output_sunshine_styles' ], 99 );

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
    public static function enable_ux_builder( $post_types ) {
        if ( is_array( $post_types ) && ! in_array( 'du_an', $post_types, true ) ) {
            $post_types[] = 'du_an';
        }
        return $post_types;
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
    public static function register_kbds_shortcodes() {
        add_shortcode('kbds_lead_form', [__CLASS__, 'render_kbds_lead_form']);
        add_shortcode('kbds_loan_calculator', [__CLASS__, 'render_kbds_loan_calculator']);
        add_shortcode('kbds_project_news', [__CLASS__, 'render_kbds_project_news']);
    }

    public static function render_kbds_lead_form($atts) {
        $default_title = is_singular() ? get_the_title() : 'Dự án Bất Động Sản';
        $default_url   = is_singular() ? get_permalink() : home_url('/');

        $atts = shortcode_atts([
            'title'        => '📥 Tải Trọn Bộ Bảng Giá, Mặt Bằng & Pháp Lý Mới Nhất',
            'project'      => $default_title,
            'source_url'   => $default_url,
            'source_title' => $default_title,
        ], $atts);

        $submitted = false;
        if (isset($_POST['kbds_lead_submit']) && isset($_POST['kbds_lead_nonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kbds_lead_nonce'])), 'kbds_submit_lead')) {
                $name    = sanitize_text_field(wp_unslash($_POST['kbds_name'] ?? ''));
                $phone   = sanitize_text_field(wp_unslash($_POST['kbds_phone'] ?? ''));
                $email   = sanitize_email(wp_unslash($_POST['kbds_email'] ?? ''));
                $project = sanitize_text_field(wp_unslash($_POST['kbds_project'] ?? ''));
                $demand  = sanitize_textarea_field(wp_unslash($_POST['kbds_demand'] ?? ''));

                if ($name && $phone) {
                    $submitted = true;
                }
            }
        }

        ob_start();
        ?>
        <div class="kbds-lead-box" style="background:linear-gradient(135deg,#0a192f 0%,#164675 100%);color:#fff;padding:32px;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,0.15);margin:20px 0;">
            <h3 style="margin-top:0;color:#FBBF24;font-size:21px;font-weight:800;"><?php echo esc_html($atts['title']); ?></h3>
            <p style="color:#e2e8f0;margin-bottom:20px;font-size:14.5px;">Chuyên viên tư vấn cao cấp sẽ gửi trực tiếp bộ tài liệu PDF nét cao & Bảng tính dòng tiền qua Zalo/Email trong 3 phút (Cam kết bảo mật 100%).</p>

            <?php if ($submitted) : ?>
                <div style="background:#1b8a4b;color:#fff;padding:16px 20px;border-radius:8px;font-weight:700;font-size:15px;">
                    ✔ Đăng ký thành công! Hệ thống đã ghi nhận yêu cầu và bộ phận kinh doanh sẽ liên hệ gửi tài liệu cho quý khách ngay lập tức.
                </div>
            <?php else : ?>
                <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1.2fr auto;gap:12px;align-items:center;">
                    <?php wp_nonce_field('kbds_submit_lead', 'kbds_lead_nonce'); ?>
                    <input type="hidden" name="kbds_project" value="<?php echo esc_attr($atts['project']); ?>" />
                    <div>
                        <input type="text" name="kbds_name" placeholder="Họ và tên *" required style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;color:#000;" />
                    </div>
                    <div>
                        <input type="tel" name="kbds_phone" placeholder="Số điện thoại / Zalo *" required pattern="[0-9]{9,11}" style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;color:#000;" />
                    </div>
                    <div>
                        <input type="email" name="kbds_email" placeholder="Email nhận file PDF (tùy chọn)" style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;color:#000;" />
                    </div>
                    <div>
                        <button type="submit" name="kbds_lead_submit" value="1" style="width:100%;padding:12px 24px;background:#F59E0B;color:#000;border:none;border-radius:8px;font-weight:800;font-size:14.5px;cursor:pointer;white-space:nowrap;transition:all 0.2s ease;">
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
        .container, .row, .kbds-container, .header-inner, .site-width {
            max-width: 1340px !important;
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
           9. THANH MENU PHỤ CỐ ĐỊNH CHUẨN DỰ ÁN (STICKY SUBNAV)
           ======================================================== */
        html {
            scroll-behavior: smooth !important;
        }

        .kbds-project-subnav {
            position: -webkit-sticky !important;
            position: sticky !important;
            top: 70px !important;
            z-index: 999 !important;
            background: #0b2246 !important;
            border-bottom: 2px solid #d4af37 !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25) !important;
            width: 100% !important;
            margin: 0 !important;
        }

        body.admin-bar .kbds-project-subnav {
            top: 102px !important;
        }

        .kbds-project-subnav .kbds-container {
            max-width: 1240px !important;
            margin: 0 auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 10px 20px !important;
            gap: 15px !important;
        }

        .kbds-subnav-brand {
            font-weight: 800 !important;
            font-size: 14.5px !important;
            color: #ffffff !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            text-transform: uppercase !important;
            white-space: nowrap !important;
            order: 1 !important;
            flex-shrink: 0 !important;
        }

        .kbds-subnav-dot {
            display: inline-block !important;
            width: 9px !important;
            height: 9px !important;
            background: #d4af37 !important;
            border-radius: 50% !important;
            box-shadow: 0 0 8px #d4af37 !important;
        }

        .kbds-subnav-links {
            display: flex !important;
            align-items: center !important;
            gap: 14px !important;
            white-space: nowrap !important;
            flex: 1 1 auto !important;
            justify-content: center !important;
            order: 2 !important;
            overflow: visible !important;
        }

        .kbds-subnav-links::-webkit-scrollbar {
            display: none !important;
        }

        .kbds-subnav-link {
            color: #e2e8f0 !important;
            text-decoration: none !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            padding: 8px 14px !important;
            border-radius: 6px !important;
            transition: all 0.2s ease !important;
            white-space: nowrap !important;
            border-bottom: 2px solid transparent !important;
        }

        .kbds-subnav-link:hover,
        .kbds-subnav-link:focus,
        .kbds-subnav-link.active {
            color: #d4af37 !important;
            background: rgba(212, 175, 55, 0.15) !important;
            border-bottom: 2px solid #d4af37 !important;
        }

        .kbds-subnav-cta {
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%) !important;
            color: #0b2246 !important;
            padding: 9px 20px !important;
            border-radius: 20px !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            text-decoration: none !important;
            text-transform: uppercase !important;
            white-space: nowrap !important;
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.35) !important;
            transition: all 0.2s ease !important;
            order: 3 !important;
            margin-left: auto !important;
            flex-shrink: 0 !important;
        }

        .kbds-subnav-cta:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.5) !important;
            color: #ffffff !important;
        }

        /* Offset cuộn chuẩn để không bị đè bởi sticky header/subnav */
        section[id], div[id], h2[id], h3[id],
        #tong-quan, #vi-tri, #quy-hoach, #san-pham, #tien-ich, #chinh-sach, #tien-do, #hoi-dap, #tin-tuc-du-an, #dang-ky, #nhan-bang-gia, #mat-bang, #can-ho-dual-key {
            scroll-margin-top: 140px !important;
        }

        @media (max-width: 820px) {
            .kbds-subnav-brand {
                display: none !important;
            }
            .kbds-project-subnav {
                top: 70px !important;
            }
            body.admin-bar .kbds-project-subnav {
                top: 116px !important;
            }
            .kbds-project-subnav .kbds-container {
                padding: 6px 10px !important;
                gap: 6px !important;
                justify-content: flex-start !important;
            }
            .kbds-subnav-links {
                justify-content: flex-start !important;
                gap: 6px !important;
                order: 1 !important;
                overflow-x: auto !important;
                scrollbar-width: none !important;
            }
            .kbds-subnav-link {
                font-size: 12px !important;
                padding: 6px 9px !important;
                background: rgba(255, 255, 255, 0.08) !important;
            }
            .kbds-subnav-cta {
                padding: 6px 12px !important;
                font-size: 11px !important;
                order: 2 !important;
                margin-left: auto !important;
            }
            section[id], div[id], h2[id], h3[id] {
                scroll-margin-top: 110px !important;
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
        </style>
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
                flush_rewrite_rules( true );
                return [ 'success' => true, 'rules_flushed' => true ];
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

        return [
            'success' => true,
            'path'    => $rel_path,
            'bytes'   => $written,
            'url'     => home_url( '/' . $rel_path ),
        ];
    }
}

Sunshine_Project_Manager::init();
