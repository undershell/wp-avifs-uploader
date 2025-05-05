# WP AVIFS Uploader

A simple WordPress plugin that enables uploading AVIF Sequence (.avifs) files to the WordPress media library.

## Description

AVIFS Uploader allows WordPress users to upload AVIF Sequence (.avifs) files to the media library. AVIF Sequence is an animated image format based on the AV1 image format, offering superior compression and quality compared to traditional animated formats.

The plugin handles:
- MIME type registration for .avifs files
- Proper file type validation
- File size limits
- Server configuration for secure handling

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New
3. Click on "Upload Plugin" and select the downloaded zip file
4. Activate the plugin

Alternatively, you can manually install the plugin:

1. Download and unzip the plugin
2. Upload the `avifs-uploader` directory to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

Once activated, the plugin works automatically. You can now upload .avifs files to your WordPress media library using the standard media uploader.

## Configuration

By default, AVIFS uploads are limited to 5MB. You can modify this limit using the `avifs_max_upload_size` filter:

```php
// Example: Change the maximum upload size to 10MB
add_filter('avifs_max_upload_size', function() {
    return 10 * MB_IN_BYTES; // 10MB
});
```

## Security

The plugin automatically adds appropriate MIME type configurations to your uploads directory's .htaccess file to ensure proper handling of AVIFS files. It also sets the `X-Content-Type-Options: nosniff` header to prevent MIME type sniffing.

## Frequently Asked Questions

### What are AVIF Sequence files?
AVIF Sequence (.avifs) files are animated images using the AV1 Image File Format. They offer better compression and quality compared to GIFs and can be a good alternative to short video clips.

### Why aren't my AVIFS files uploading?
Check that your files are within the size limit (default 5MB). Also ensure your files are valid AVIF Sequence files. The plugin validates files based on their header signature.

### Will this plugin convert my existing animations to AVIFS?
No, this plugin only enables uploading AVIFS files. You will need separate tools to create or convert images to the AVIFS format.

## Uninstallation

When you uninstall the plugin, it will remove any configurations it added to your .htaccess file.

## License

This plugin is licensed under the GPL-2.0+ License. See the LICENSE file for details.

Would you like me to explain or break down any part of this README?