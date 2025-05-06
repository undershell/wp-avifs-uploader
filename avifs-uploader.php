<?php
/**
 * Plugin Name:  AVIFS Uploader
 * Plugin URI:   https://github.com/slendev/wp-avifs-uploader
 * Description:  Enable AVIF Sequence (.avifs) file uploads in WordPress media library.
 * Version:      1.1.0
 * Author:       Slendev
 * Author URI:   https://github.com/slendev/wp-avifs-uploader
 * License:      GPL-2.0+
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  avifs-uploader
 * Domain Path:  /languages
 */


// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class to enable AVIFS uploads in WordPress with role-based access control and custom file size limit.
 *
 * @since 1.0.0
 */
class AVIFS_Uploader {

    /**
     * File extension for AVIF Sequence files.
     *
     * @var string
     */
    const EXT = 'avifs';

    /**
     * Primary MIME type for AVIF files.
     *
     * @var string
     */
    const MIME_PRIMARY = 'image/avif';

    /**
     * Alternative MIME type for AVIF Sequence files.
     *
     * @var string
     */
    const MIME_ALT = 'image/avif-sequence';

    /**
     * Default maximum file size in MB.
     *
     * @var int
     */
    const DEFAULT_CUSTOM_MAX_MB = 5;

    /**
     * Capability required to upload AVIFS files.
     *
     * @var string
     */
    const AVIFS_UPLOAD_CAPABILITY = 'upload_avifs';

    /**
     * Option key to store allowed roles.
     *
     * @var string
     */
    const ALLOWED_ROLES_OPTION = 'avifs_uploader_allowed_roles';

    /**
     * Option key to store the custom maximum file size.
     *
     * @var string
     */
    const CUSTOM_MAX_SIZE_OPTION = 'avifs_uploader_custom_max_size';

    /**
     * Initialize the plugin.
     */
    public function __construct() {
        // Register MIME types with WordPress
        add_action( 'init', function() {
            // Check PHP and WP versions
            if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
                add_action( 'admin_notices', array( $this, 'php_version_error' ) );
                return;
            }
            if ( version_compare( get_bloginfo( 'version' ), '4.7', '<' ) ) {
                add_action( 'admin_notices', array( $this, 'wp_version_error' ) );
                return;
            }
        } );
        add_filter( 'upload_mimes', array( $this, 'register_mime_types' ) );

        // Fix file type detection
        add_filter( 'wp_check_filetype_and_ext', array( $this, 'validate_file_type' ), 10, 5 );

        // Add file size limit and capability check
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'limit_file_size_and_capability' ) );

        // Plugin lifecycle
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

        // Load translations
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Add admin menu and settings
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Add settings link to plugins list
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );

        // Add admin notices for errors
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
    }

    /**
     * Display PHP version error notice.
     */
    public function php_version_error() {
        $message = sprintf( __( 'AVIFS Uploader requires PHP version 5.6 or higher. Your current version is %s.', 'avifs-uploader' ), PHP_VERSION );
        echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
    }

    /**
     * Display WordPress version error notice.
     */
    public function wp_version_error() {
        $message = sprintf( __( 'AVIFS Uploader requires WordPress version 4.7 or higher. Your current version is %s.', 'avifs-uploader' ), get_bloginfo( 'version' ) );
        echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
    }

    /**
     * Add settings link to the plugins list page.
     *
     * @param array $links Array of plugin action links.
     * @return array Updated array of plugin action links.
     */
    public function add_settings_link( $links ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=avifs-uploader-settings' ) ) . '">' . __( 'Settings', 'avifs-uploader' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Load plugin text domain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'avifs-uploader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Add admin menu item for settings page.
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'AVIFS Uploader Settings', 'avifs-uploader' ),
            __( 'AVIFS Uploader', 'avifs-uploader' ),
            'manage_options',
            'avifs-uploader-settings',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting(
            'avifs_uploader_group',
            self::ALLOWED_ROLES_OPTION,
            array( $this, 'sanitize_allowed_roles' )
        );

        register_setting(
            'avifs_uploader_group',
            self::CUSTOM_MAX_SIZE_OPTION,
            array( 'sanitize_callback' => 'absint' )
        );

        add_settings_section(
            'avifs_uploader_section',
            __( 'AVIFS Upload Settings', 'avifs-uploader' ),
            array( $this, 'print_section_info' ),
            'avifs-uploader-settings'
        );

        add_settings_field(
            'allowed_roles',
            __( 'Allowed User Roles', 'avifs-uploader' ),
            array( $this, 'allowed_roles_field' ),
            'avifs-uploader-settings',
            'avifs_uploader_section'
        );

        add_settings_field(
            'custom_max_size',
            __( 'Maximum File Size (MB)', 'avifs-uploader' ),
            array( $this, 'custom_max_size_field' ),
            'avifs-uploader-settings',
            'avifs_uploader_section'
        );
    }

    /**
     * Sanitize allowed roles input.
     *
     * @param array $input The array of selected roles.
     * @return array Sanitized array of roles.
     */
    public function sanitize_allowed_roles( $input ) {
        $allowed_roles = array();
        $all_roles = wp_roles()->get_names();

        if ( is_array( $input ) ) {
            foreach ( $input as $role ) {
                if ( isset( $all_roles[ $role ] ) ) {
                    $allowed_roles[] = sanitize_key( $role );
                }
            }
        }
        return $allowed_roles;
    }

    /**
     * Print the section information.
     */
    public function print_section_info() {
        _e( 'Configure the settings for AVIFS Sequence file uploads.', 'avifs-uploader' );
    }

    /**
     * Display the allowed roles field.
     */
    public function allowed_roles_field() {
        $options = get_option( self::ALLOWED_ROLES_OPTION, array( 'administrator' ) );
        $all_roles = wp_roles()->get_names();

        foreach ( $all_roles as $role => $name ) {
            printf(
                '<label><input type="checkbox" name="%s[]" value="%s" %s> %s</label><br>',
                self::ALLOWED_ROLES_OPTION,
                esc_attr( $role ),
                in_array( $role, $options, true ) ? 'checked' : '',
                esc_html( translate_user_role( $name ) )
            );
        }
    }

    /**
     * Display the custom maximum file size field.
     */
    public function custom_max_size_field() {
        $custom_size = get_option( self::CUSTOM_MAX_SIZE_OPTION, self::DEFAULT_CUSTOM_MAX_MB );
        $wp_max_size = floor( wp_max_upload_size() / MB_IN_BYTES );
        printf(
            '<input type="number" name="%s" value="%s" min="1" max="%d"> %s <p class="description">%s</p>',
            self::CUSTOM_MAX_SIZE_OPTION,
            esc_attr( $custom_size ),
            $wp_max_size,
            __( 'MB', 'avifs-uploader' ),
            sprintf(
                /* translators: %1$d: default file size in MB, %2$d: WordPress max upload size in MB */
                __( 'Default: %1$d MB. WordPress maximum upload size: %2$d MB.', 'avifs-uploader' ),
                self::DEFAULT_CUSTOM_MAX_MB,
                $wp_max_size
            )
        );
    }

    /**
     * Render the settings page.
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'AVIFS Uploader Settings', 'avifs-uploader' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'avifs_uploader_group' );
                do_settings_sections( 'avifs-uploader-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display admin notices for errors.
     */
    public function display_admin_notices() {
        $uploads_dir = wp_upload_dir()['basedir'];
        if ( ! is_writable( $uploads_dir ) ) {
            ?>
            <div class="notice notice-error">
                <p><?php _e( 'The uploads directory is not writable. AVIFS Uploader cannot update .htaccess for security.', 'avifs-uploader' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Register AVIFS MIME types with WordPress based on allowed roles.
     *
     * @param array $mimes Existing MIME types.
     * @return array Modified MIME types.
     */
    public function register_mime_types( $mimes ) {
        $current_user = wp_get_current_user();
        $allowed_roles = get_option( self::ALLOWED_ROLES_OPTION, array( 'administrator' ) );

        foreach ( $current_user->roles as $role ) {
            if ( in_array( $role, $allowed_roles, true ) ) {
                $mimes[self::EXT] = self::MIME_PRIMARY;
                break;
            }
        }
        return $mimes;
    }

    /**
     * Validate and correct file type detection for AVIFS files based on allowed roles.
     *
     * @param array  $data      File data.
     * @param string $file      Path to the uploaded file.
     * @param string $filename  Original filename.
     * @param array  $mimes     Allowed MIME types.
     * @param string $real_mime Real MIME type if already detected.
     * @return array Corrected file data.
     */
    public function validate_file_type( $data, $file, $filename, $mimes, $real_mime = null ) {
        $current_user = wp_get_current_user();
        $allowed_roles = get_option( self::ALLOWED_ROLES_OPTION, array( 'administrator' ) );
        $has_permission = false;

        foreach ( $current_user->roles as $role ) {
            if ( in_array( $role, $allowed_roles, true ) ) {
                $has_permission = true;
                break;
            }
        }

        if ( ! $has_permission ) {
            return $data;
        }

        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( $ext !== self::EXT ) {
            return $data;
        }

        if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
            return $data;
        }

        $is_valid = false;

        if ( function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            $mime = finfo_file( $finfo, $file );
            finfo_close( $finfo );
            if ( in_array( $mime, array( self::MIME_PRIMARY, self::MIME_ALT ), true ) ) {
                $is_valid = true;
            }
        } 

        if ( ! $is_valid && is_readable( $file ) ) {
            $header = file_get_contents( $file, false, null, 0, 256 );
            $is_valid = ( strpos( $header, 'ftypavif' ) !== false );
        }

        if ( $is_valid ) {
            return array(
                'ext'             => self::EXT,
                'type'            => self::MIME_PRIMARY,
                'proper_filename' => wp_unique_filename( dirname( $file ), $filename ),
            ); 
        } else {
            $data['error'] = __( 'Invalid AVIFS file.', 'avifs-uploader' ); // Provide error message
        }

        return $data;
    }

    /**
     * Limit file size for AVIFS uploads and check user role.
     *
     * @param array $file File data.
     * @return array Modified file data with error if size limit exceeded or role not allowed.
     */
    public function limit_file_size_and_capability( $file ) {
        if ( strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) !== self::EXT ) {
            return $file;
        }

        $current_user = wp_get_current_user();
        $allowed_roles = get_option( self::ALLOWED_ROLES_OPTION, array( 'administrator' ) );
        $has_permission = false;

        foreach ( $current_user->roles as $role ) {
            if ( in_array( $role, $allowed_roles, true ) ) {
                $has_permission = true;
                break;
            }
        }

        if ( ! $has_permission ) {
            $file['error'] = __( 'Your user role is not allowed to upload AVIFS Sequence files.', 'avifs-uploader' );
            return $file;
        }

        $custom_limit_mb = get_option( self::CUSTOM_MAX_SIZE_OPTION, self::DEFAULT_CUSTOM_MAX_MB );
        $max_size = min( $custom_limit_mb * MB_IN_BYTES, wp_max_upload_size() );

        if ( $file['size'] > $max_size ) {
            $file['error'] = sprintf(
                /* translators: %s: maximum file size in MB */
                __( 'AVIF Sequence files must be smaller than %s MB.', 'avifs-uploader' ),
                floor( $max_size / MB_IN_BYTES )
            );
        } else {
           set_transient( 'avifs_upload_success', true, 60 ); // Set success transient.
        }

        return $file;
    }

    /**
     * Plugin activation tasks: Set default options and update .htaccess.
     */
    public function activate() {
        if ( ! get_option( self::ALLOWED_ROLES_OPTION ) ) {
            update_option( self::ALLOWED_ROLES_OPTION, array( 'administrator' ) );
        }

        if ( ! get_option( self::CUSTOM_MAX_SIZE_OPTION ) ) {
            update_option( self::CUSTOM_MAX_SIZE_OPTION, self::DEFAULT_CUSTOM_MAX_MB );
        }

        $this->update_htaccess();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation tasks.
     */
    public function deactivate() {
        // No specific deactivation tasks.
    }

    /**
     * Add security rules to .htaccess in uploads directory.
     */
    protected function update_htaccess() {
        // Check if server is Apache
        if ( ! function_exists( 'apache_get_modules' ) || ! in_array( 'mod_mime', apache_get_modules(), true ) ) {
            add_action( 'admin_notices', array( $this, 'htaccess_update_error' ) );
            return;
        }

        $uploads_dir = wp_upload_dir()['basedir'];
        if ( ! is_writable( $uploads_dir ) ) {
            error_log( 'AVIFS Uploader: Uploads directory is not writable for .htaccess update.' );
            return;
        }

        $htaccess_file = trailingslashit( $uploads_dir ) . '.htaccess';
        $rules = "\n# BEGIN avifs-uploader\n"
               . "<IfModule mod_mime.c>\n"
               . "  AddType " . self::MIME_PRIMARY . " ." . self::EXT . "\n"
               . "</IfModule>\n"
               . "<IfModule mod_headers.c>\n"
               . "  Header set X-Content-Type-Options \"nosniff\"\n"
               . "</IfModule>\n"
               . "# END avifs-uploader\n";

        if ( file_exists( $htaccess_file ) ) {
            $current_content = file_get_contents( $htaccess_file );
            if ( strpos( $current_content, '# BEGIN avifs-uploader' ) === false ) {
                file_put_contents( $htaccess_file, $current_content . $rules );
            }
        } else {
            file_put_contents( $htaccess_file, $rules );
        }
    }

    /**
     * Display .htaccess update error notice.
     */
    public function htaccess_update_error() {
        $message = __( 'AVIFS Uploader: Could not automatically update .htaccess. Please ensure your server is Apache with mod_mime enabled, and that the uploads directory is writable. You may need to manually add the following rules to your .htaccess file:', 'avifs-uploader' )
                 . '<pre>&lt;IfModule mod_mime.c&gt;
  AddType ' . self::MIME_PRIMARY . ' .' . self::EXT . '
&lt;/IfModule&gt;
&lt;IfModule mod_headers.c&gt;
  Header set X-Content-Type-Options "nosniff"
&lt;/IfModule&gt;</pre>';
        echo '<div class="notice notice-warning"><p>' . $message . '</p></div>';
    }

    /**
     * Plugin uninstallation tasks: Remove options and .htaccess rules.
     */
    public static function uninstall() {
        $uploads_dir = wp_upload_dir()['basedir'];
        $htaccess_file = trailingslashit( $uploads_dir ) . '.htaccess';

        if ( file_exists( $htaccess_file ) && is_writable( $htaccess_file ) ) {
            $content = file_get_contents( $htaccess_file );
            $pattern = '/\n?# BEGIN avifs-uploader.*?# END avifs-uploader\n?/s';
            $cleaned_content = preg_replace( $pattern, '', $content );
            if ( $cleaned_content !== $content ) {
                file_put_contents( $htaccess_file, $cleaned_content );
            }
        }

        delete_option( self::ALLOWED_ROLES_OPTION );
        delete_option( self::CUSTOM_MAX_SIZE_OPTION );
    }
}

// Initialize the plugin
new AVIFS_Uploader();