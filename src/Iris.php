<?php

namespace ElevenMiles\Iris;

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

/**
 * Class Iris
 * 
 * For debugging: Debug::debug($image_meta);
 * 
 */

class Iris
{

    /**
     * Iris constructor 
     */
    public function __construct()
    {
        add_action('plugins_loaded', [__CLASS__, 'check_for_offload_media']);
        // Check for offload media plugin
        if (!is_plugin_active('amazon-s3-and-cloudfront-pro/amazon-s3-and-cloudfront-pro.php')) {
            return;
        }
        // Check if multi site
        if (is_admin() && is_multisite()) {
            add_filter('site_option_upload_filetypes', [__CLASS__, 'ensure_multisites_support_webp']);
        }
        add_filter('wp_image_editors', [__CLASS__, 'default_to_GD']);
        add_filter('image_editor_output_format', [__CLASS__, 'set_webp_as_default_mime_type']);
        apply_filters('image_editor_default_mime_type', [__CLASS__, 'image_editor_default_mime_type']);
        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'original_webp_converter'], 10, 2);
    }

    // Ensure all network sites include WebP support.

    function ensure_multisites_support_webp($filetypes)
    {
        $filetypes = explode(' ', $filetypes);
        if (!in_array('webp', $filetypes, true)) {
            $filetypes[] = 'webp';
        }
        $filetypes   = implode(' ', $filetypes);

        return $filetypes;
    }

    /**
     * Change the default image editor class to WP_Image_Editor_GD.
     *
     * @param string[] 
     * Array of available image editor class names. 
     * Defaults are 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD'.
     */

    public static function default_to_GD()
    {
        return ['WP_Image_Editor_GD', 'WP_Image_Editor_Imagick'];
    }

    /**
     * Filters the image editor output format mapping to webp.
     *
     * @param string[] $formats 
     * 
     */

    public static function set_webp_as_default_mime_type($formats)
    {
        $formats['image/jpeg'] = 'image/webp';
        $formats['image/png'] = 'image/webp';
        return $formats;
    }

    /**
     * Sets default mine type to webp.
     *
     * @param string[] $formats 
     * 
     */

    public static function image_editor_default_mime_type($mime_type)
    {
        $mime_type = 'image/webp';

        return $mime_type;
    }

    /**
     * Converts original uploaded file to webp
     * 
     * @param string $attachment_id
     * @param array[] $image_meta
     * 
     */

    public static function original_webp_converter($image_meta, $attachment_id)
    {
        $file = wp_get_original_image_path($attachment_id);
        $image_mime = wp_getimagesize($file)['mime'];
        $editor = wp_get_image_editor($file);

        if (is_wp_error($editor)) {
            return $image_meta;
        }

        // Only convert jpg or png file types
        if ('image/jpeg' == $image_mime || 'image/png' == $image_mime) {

            $upload_dir = wp_upload_dir();
            $dirname = dirname($file) . '/';
            $ext = '.' . pathinfo($file, PATHINFO_EXTENSION);
            $wp_basename_file_ext = wp_basename($file, $ext);
            $webp_filename = $dirname . $wp_basename_file_ext . '.webp';

            $webp_file = str_replace(trailingslashit($upload_dir['basedir']), "", $dirname)  . $wp_basename_file_ext . '.webp';

            if (!is_wp_error($editor->save($webp_filename))) {

                if (!isset($image_meta['original_image'])) {
                    $image_meta['original_image'] = wp_basename($file);
                }

                $image_meta['file'] = $webp_file;

                wp_update_attachment_metadata($attachment_id, $image_meta);
                update_post_meta($attachment_id, '_wp_attached_file', $webp_file);
            } else {
                error_log(__('Unable to save the original in webp format ') . $file);
            }
        }

        return $image_meta;
    }
}
