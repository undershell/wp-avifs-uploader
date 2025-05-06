# WP AVIFS Uploader

Enable AVIF Sequence (.avifs) file uploads in WordPress media library with role-based access control and custom file size limit.

## Description

AVIFS Uploader allows WordPress users to upload AVIF Sequence (.avifs) files to the media library with enhanced control over who can upload and how large the files can be. AVIF Sequence is an animated image format based on the AV1 image format, offering superior compression and quality compared to traditional animated formats like GIFs.

This plugin provides the following features:

- **MIME Type Registration:** Registers the necessary MIME types for .avifs files to be recognized by WordPress.
- **File Type Validation:** Ensures that only valid AVIF Sequence files are uploaded.
- **Role-Based Access Control:** Allows administrators to specify which user roles are permitted to upload .avifs files.
- **Custom File Size Limit:** Enables administrators to set a custom maximum file size for .avifs uploads, independent of the overall WordPress upload limit.
- **Server Configuration:** Automatically updates the `.htaccess` file in the uploads directory (if the server is Apache and `mod_mime` is enabled) to ensure proper handling and security of AVIFS files.
- **Admin Interface:** Provides a user-friendly settings page within the WordPress admin area to configure allowed roles and the custom file size limit.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Installation

**Using the WordPress Plugin Directory (if available):**

1.  Navigate to the "Plugins" section in your WordPress admin dashboard.
2.  Click on "Add New."
3.  Search for "AVIFS Uploader."
4.  Locate the plugin in the search results and click "Install Now."
5.  After installation, click "Activate."

**Manual Installation:**

1.  Download the plugin as a ZIP file.
2.  In your WordPress admin dashboard, go to "Plugins" -> "Add New."
3.  Click the "Upload Plugin" button.
4.  Select the downloaded ZIP file and click "Install Now."
5.  After installation, click "Activate."

**Alternative Manual Installation (via FTP):**

1.  Download and extract the plugin ZIP file.
2.  Using an FTP client, upload the extracted `avifs-uploader` folder to the `/wp-content/plugins/` directory of your WordPress installation.
3.  In your WordPress admin dashboard, go to "Plugins."
4.  Find "AVIFS Uploader" in the list of plugins and click "Activate."

## Usage

Once activated, the plugin works automatically. You can now upload .avifs files to your WordPress media library using the standard media uploader.

## Configuration

To configure the plugin:

1.  In your WordPress admin dashboard, go to "Settings" -> "AVIFS Uploader."
2.  You will see the "AVIFS Upload Settings" section.
3.  **Allowed User Roles:** Select the user roles that should be allowed to upload .avifs files. By default, only the "Administrator" role is allowed.
4.  **Maximum File Size (MB):** Set the maximum file size (in MB) for .avifs uploads. The default is 10MB.  This limit is capped by your WordPress maximum upload size.

## Security

The plugin automatically adds appropriate MIME type configurations to your uploads directory's .htaccess file to ensure proper handling of AVIFS files. It also sets the `X-Content-Type-Options: nosniff` header to prevent MIME type sniffing.

## Frequently Asked Questions

**What are AVIF Sequence files?**

AVIF Sequence (.avifs) files are animated images using the AV1 Image File Format. They offer better compression and quality compared to GIFs and can serve as an efficient alternative to short video clips.

**Why aren't my AVIFS files uploading?**

Check the following:
- **Allowed Role:** Ensure your user role is selected in the plugin settings as an allowed role for AVIFS uploads.
- **File Size:** Verify that your file size is within the limit set in the plugin settings and also within the overall WordPress upload limit.
- **File Validity:** Make sure you are uploading a valid AVIF Sequence file. The plugin validates files based on their file extension and header signature.

- **Server Configuration:** If you encounter issues, it might be related to your server configuration (e.g., if the `.htaccess` file in your uploads directory is not writable). Check your server error logs for any related messages.

### Will this plugin convert my existing animations to AVIFS?
No, this plugin only enables uploading AVIFS files. You will need separate tools to create or convert images to the AVIFS format.

## Uninstallation

When you uninstall the plugin, it will remove any configurations it added to your .htaccess file.

## License

This plugin is licensed under the GPL-2.0+ License. See the LICENSE file for details.
