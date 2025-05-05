<?php
/**
 * Plugin Name:  AVIFS Uploader
 * Plugin URI:   https://github.com/slendev/wp-avifs-uploader
 * Description:  Enable AVIF Sequence (.avifs) file uploads in WordPress media library.
 * Version:      1.0.0
 * Author:       Slendev
 * Author URI:   https://github.com/slendev
 * License:      GPL-2.0+
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  avifs-uploader
 * Domain Path:  /languages
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class to enable AVIFS uploads in WordPress
 *
 * @since 1.0.0
 */
class AVIFS_Uploader {

    /**
     * File extension for AVIF Sequence files
     */
    const EXT = 'avifs';

    /**
     * Primary MIME type returned by fileinfo for AVIF files
     */
    const MIME_PRIMARY = 'image/avif';

    /**
     * Alternative MIME type that should be used for AVIF Sequence files
     */
    const MIME_ALT = 'image/avif-sequence';

    /**
     * Default maximum file size in MB
     */
    const DEFAULT_MAX_MB = 5;

    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Register MIME types with WordPress
        add_filter( 'upload_mimes', array( $this, 'register_mime_types' ) );

        // Fix file type detection
        add_filter( 'wp_check_filetype_and_ext', array( $this, 'validate_file_type' ), 10, 5 );

        // Add file size limit
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'limit_file_size' ) );

        // Plugin lifecycle
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

        // Load translations
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    /**
     * Load plugin text domain for translations
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'avifs-uploader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Register AVIFS MIME types with WordPress
     *
     * @param array $mimes Existing MIME types
     * @return array Modified MIME types
     */
    public function register_mime_types( $mimes ) {
        // Register the primary MIME type
        $mimes[self::EXT] = self::MIME_PRIMARY;

        // Add alternative MIME type with a fake key so it's included in comparisons
        $mimes['_avifs_alt'] = self::MIME_ALT;

        return $mimes;
    }

    /**
     * Validate and correct file type detection for AVIFS files
     *
     * @param array  $data      File data
     * @param string $file      Path to the uploaded file
     * @param string $filename  Original filename
     * @param array  $mimes     Allowed MIME types
     * @param string $real_mime Real MIME type if already detected
     * @return array Corrected file data
     */
    public function validate_file_type( $data, $file, $filename, $mimes, $real_mime = null ) {
        // Check if this is an AVIFS file
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( $ext !== self::EXT ) {
            return $data; // Not our file type, let WordPress handle it
        }

        // If WordPress already correctly identified it, accept the result
        if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
            return $data;
        }

        $is_valid = false;

        // Method 1: Check with fileinfo if available
        if ( function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            $mime = finfo_file( $finfo, $file );
            finfo_close( $finfo );
            
            // Accept any AVIF mime type variant
            if ( strpos( $mime, 'image/avif' ) === 0 ) {
                $is_valid = true;
            }
        }

        // Method 2: Check for AVIF signature in file header
        if ( ! $is_valid && is_readable( $file ) ) {
            $header = file_get_contents( $file, false, null, 0, 256 );
            $is_valid = ( strpos( $header, 'ftypavif' ) !== false );
        }

        // If file appears to be a valid AVIF, accept it
        if ( $is_valid ) {
            return array(
                'ext'             => self::EXT,
                'type'            => self::MIME_PRIMARY,
                'proper_filename' => wp_unique_filename( dirname( $file ), $filename ),
            );
        }

        // If validation failed, return original data (which will prevent upload)
        return $data;
    }

    /**
     * Limit the file size for AVIFS uploads
     *
     * @param array $file File data
     * @return array Modified file data with error if size limit exceeded
     */
    public function limit_file_size( $file ) {
        if ( strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) === self::EXT ) {
            // Get custom size limit, defaulting to 5MB
            $custom_limit = apply_filters( 'avifs_max_upload_size', self::DEFAULT_MAX_MB * MB_IN_BYTES );
            
            // Don't exceed WordPress' global upload limit
            $max_size = min( $custom_limit, wp_max_upload_size() );
            
            if ( $file['size'] > $max_size ) {
                $file['error'] = sprintf(
                    /* translators: %s: maximum file size in MB */
                    __( 'AVIF Sequence files must be smaller than %s MB.', 'avifs-uploader' ),
                    floor( $max_size / MB_IN_BYTES )
                );
            }
        }
        return $file;
    }

    /**
     * Plugin activation tasks
     *
     * @return void
     */
    public function activate() {
        // Add server configuration for security
        $this->update_htaccess();
        
        // Flush rewrite rules to ensure everything works
        flush_rewrite_rules();
    }

    /**
     * Add security rules to .htaccess in uploads directory
     *
     * @return void
     */
    protected function update_htaccess() {
        $uploads_dir = wp_upload_dir()['basedir'];
        
        // Check if uploads directory is writable
        if ( ! is_writable( $uploads_dir ) ) {
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
     * Plugin uninstallation tasks
     *
     * @return void
     */
    public static function uninstall() {
        // Remove .htaccess rules
        $uploads_dir = wp_upload_dir()['basedir'];
        $htaccess_file = trailingslashit( $uploads_dir ) . '.htaccess';
        
        if ( file_exists( $htaccess_file ) && is_writable( $htaccess_file ) ) {
            $content = file_get_contents( $htaccess_file );
            
            // Find our rules block
            $pattern = '/\n?# BEGIN avifs-uploader.*?# END avifs-uploader\n?/s';
            $cleaned_content = preg_replace( $pattern, '', $content );
            
            if ( $cleaned_content !== $content ) {
                file_put_contents( $htaccess_file, $cleaned_content );
            }
        }
    }
}

// Initialize the plugin
new AVIFS_Uploader();