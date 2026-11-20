<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('WP_CACHE', true);
define( 'WPCACHEHOME', 'C:/xampp/htdocs/www/Sunshinegroup/wp-content/plugins/wp-super-cache/' );
define( 'DB_NAME', 'sunshine' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ':a&&qLJTw+Ci{u ~)A00ElKMp=c3g~|-lDdwcq5AN5.$jdr7cIr]<&T$@?$cG%1`' );
define( 'SECURE_AUTH_KEY',  'qQAC4OJl~`Qf9PWYjo~y(32oL]7.ew4},B_&er26e8gDP)D6{p^P1-SKXb+2nnRq' );
define( 'LOGGED_IN_KEY',    '43}KW!t 3xM3eNS)?QG?Q,yrl@z*.UE*{Su>q6j,6 NeA&nS0j$9~hlaex]x)Uot' );
define( 'NONCE_KEY',        '=OkPu3|`Z)5[q<qJz(<XdKly6F+(5A`E#D/F!QVu.?wudg@LJKI|/*I~,i[84]5v' );
define( 'AUTH_SALT',        ' $5[t7Q6ehcgcAD.sq`@^~V:8I~P:V5Q!~R_mcb=-#_<R&hdNQI.@8I+`2@7f8S6' );
define( 'SECURE_AUTH_SALT', 'pq.-iuGf`/&cu2hA&MNur,CI3UV-Y&;tEM!A!{o@x]1o8O.YcBa4)DU{&Y (5UM}' );
define( 'LOGGED_IN_SALT',   '(ktPta9j/&/6MPNx?Qjx*!dXt} CtSi(x?j_g@uYM@:NQoQ@7YE}.+U+A:$biwy/' );
define( 'NONCE_SALT',       'uA^.>n*l_or3mY<fB=?if60mDzvE}}DH0Y=BjZc`3w`0R0`NZb&#ep]~e#9qTt@A' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */
/**
 * Tự động nhận diện môi trường: Localhost hay Production (Live Domain)
 * Khi git push lên server thật, WordPress tự động nhận domain chính, không bao giờ trỏ về localhost
 */
if ( defined( 'WP_CLI' ) && ! isset( $_SERVER['HTTP_HOST'] ) ) {
    define( 'WP_HOME', 'http://localhost/www/Sunshinegroup' );
    define( 'WP_SITEURL', 'http://localhost/www/Sunshinegroup' );
} elseif ( isset( $_SERVER['HTTP_HOST'] ) && ( strpos( $_SERVER['HTTP_HOST'], 'localhost' ) !== false || strpos( $_SERVER['HTTP_HOST'], '127.0.0.1' ) !== false ) ) {
    define( 'WP_HOME', 'http://localhost/www/Sunshinegroup' );
    define( 'WP_SITEURL', 'http://localhost/www/Sunshinegroup' );
} else {
    $protocol = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ? 'https://' : 'http://';
    $host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'sunshine-group.com.vn';
    define( 'WP_HOME', $protocol . $host );
    define( 'WP_SITEURL', $protocol . $host );
}

// Tối ưu hóa hiệu năng và kiểm soát dữ liệu
define( 'WP_POST_REVISIONS', 5 );        // Giới hạn tối đa 5 bản nháp revision/bài viết, chống phình database
define( 'AUTOSAVE_INTERVAL', 180 );      // Tự động lưu 180 giây thay vì 60 giây, giảm tải server
define( 'EMPTY_TRASH_DAYS', 14 );        // Tự động dọn thùng rác sau 14 ngày
define( 'WP_MEMORY_LIMIT', '256M' );     // Tăng bộ nhớ xử lý PHP cho WordPress




/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
