<?php

namespace ElevenMiles\Iris;

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

/**
 * Class Iris
 */

// Debug function to help test and return file meta
function debug($info)
{
    $message = null;

    if (is_string($info) || is_int($info) || is_float($info)) {
        $message = $info;
    } else {
        $message = var_export($info, true);
    }

    if ($fh = fopen(ABSPATH . '/gdwebpconvert.log', 'a')) {
        fputs($fh, date('Y-m-d H:i:s') . " $message\n");
        fclose($fh);
    }
}

class Iris
{

    /**
     * Iris constructor 
     */
    public function __construct()
    {
        add_action('plugins_loaded', [__CLASS__, 'check_for_offload_media']);
        // Check for offload media plugin
        if (is_plugin_active('amazon-s3-and-cloudfront-pro/amazon-s3-and-cloudfront-pro.php')) {
            // check if multi site
            if (is_admin() && is_multisite()) {
                add_filter('site_option_upload_filetypes', [__CLASS__, 'ensure_multisites_support_webp']);
            }
            add_filter('wp_image_editors', [__CLASS__, 'defaultToGD']);
            add_filter('image_editor_output_format', [__CLASS__, 'setWebpAsDefaultMimeType']);
            apply_filters('image_editor_default_mime_type', [__CLASS__, 'imageEditorDefaultMimeType']);

            add_filter('wp_generate_attachment_metadata', [__CLASS__, 'originalWebpConverter'], 10, 2);
        } else {
            return;
        }
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

    public static function defaultToGD()
    {
        return ['WP_Image_Editor_GD', 'WP_Image_Editor_Imagick'];
    }

    /**
     * Filters the image editor output format mapping to webp.
     *
     * @param string[] $formats 
     * 
     */

    public static function setWebpAsDefaultMimeType($formats)
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

    public static function imageEditorDefaultMimeType($mine_type)
    {
        $mine_type = 'image/webp';

        return $mine_type;
    }



    public static function originalWebpConverter($image_meta, $attachment_id)
    {

        $file = wp_get_original_image_path($attachment_id);
        $image_mime = wp_getimagesize($file)['mime'];

        // switch ($image_mime) {
        //     case 'image/jpg':
        //     case 'image/jpeg':

        $editor = wp_get_image_editor($file);

        if (is_wp_error($editor)) {
            return $image_meta;
        }

        $upload_dir = wp_upload_dir();
        $dirname = dirname($file) . '/';
        $ext = '.' . pathinfo($file, PATHINFO_EXTENSION);
        $wp_basename_file_ext = wp_basename($file, $ext);
        $webp_filename = $dirname . $wp_basename_file_ext . '.webp';

        $webp_file = str_replace(trailingslashit($upload_dir['basedir']), "", $dirname)  . $wp_basename_file_ext . '.webp';

        if (!is_wp_error($editor->save($webp_filename))) {

            if (!isset($image_meta['original_image'])) {
                // replace the original image path with the webp image path
                $image_meta['original_image'] = $webp_file;
            }

            $image_meta['file'] = $webp_file;
            wp_update_attachment_metadata($attachment_id, $image_meta);
            debug($image_meta);
            update_post_meta($attachment_id, '_wp_attached_file', $webp_file);
        } else {
            error_log(__('Unable to save the original in webp format ') . $file);
        }
        //     case 'image/png':

        //         $editor = wp_get_image_editor($file);

        //         if (is_wp_error($editor)) {
        //             return $image_meta;
        //         }

        //         $upload_dir = wp_upload_dir();
        //         $dirname = dirname($file) . '/';
        //         $ext = '.' . pathinfo($file, PATHINFO_EXTENSION);
        //         $wp_basename_file_ext = wp_basename($file, $ext);
        //         $wp_basename_file = wp_basename($file);
        //         $webp_filename = $dirname . $wp_basename_file_ext . '.webp';

        //         $webp_file = str_replace(trailingslashit($upload_dir['basedir']), "", $dirname)  . $wp_basename_file_ext . '.webp';

        //         if (!is_wp_error($editor->save($webp_filename))) {

        //             if (!isset($image_meta['original_image'])) {
        //                 // replace the original image path with the webp image path
        //                 $image_meta['original_image'] = $wp_basename_file;
        //             }

        //             $image_meta['file'] = $webp_file;
        //             wp_update_attachment_metadata($attachment_id, $image_meta);

        //             update_post_meta($attachment_id, '_wp_attached_file', $webp_file);
        //         } else {
        //             error_log(__('Unable to save the original in webp format ') . $file);
        //         }
        // }

        return $image_meta;
    }
}
