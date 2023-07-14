<?php

namespace ElevenMiles\Iris;



use ElevenMiles\Iris\WordPress\Admin\AdminPanel;
use ElevenMiles\Iris\IrisConvert\IrisConvert;
use ElevenMiles\Iris\WordPress\API;
use ElevenMiles\Iris\WordPress\CLI\Register;
use ElevenMiles\Iris\WordPress\Config;
use ElevenMiles\Iris\WordPress\Scripts;

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

/**
 * Class Iris
 */

class Iris
{

    /**
     * Iris constructor.
     */

    public function __construct()
    {
        new AdminPanel();

        // Check for action scheduler and offload media plugin
        if (!is_plugin_active('action-scheduler/action-scheduler.php') && !is_plugin_active('amazon-s3-and-cloudfront-pro/amazon-s3-and-cloudfront.php')) {
            return;
        }

        // Switch default image editor to GD
        add_filter('wp_image_editors', [Config::class, 'defaultToGD']);
        // Set webp as default mime type
        add_filter('image_editor_output_format', [Config::class, 'setWebpAsDefaultMimeType']);
        // Set webp as default mime type
        apply_filters('image_editor_default_mime_type', [Config::class, 'imageEditorDefaultMimeType']);

        // Check if multi site
        if (is_admin() && is_multisite()) {
            add_filter('site_option_upload_filetypes', [Config::class, 'ensureMultisitesSupportWebp']);
        }

        // Scripts
        add_action('admin_enqueue_scripts', [Scripts::class, 'enqueue_admin_script']);

        // API
        add_action('rest_api_init', [API::class, 'registerAccessRoute']);

        // CLI
        add_action('cli_init', [Register::class, 'registerCLI']);

        add_action('plugins_loaded', [__CLASS__, 'check_for_offload_media']);

        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'convertImageOnUplodad'], 10, 2);
        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'webpConverter'], 10, 1);
        add_action('webp_bulk_process_schedule', [__CLASS__, 'webpBulkConverter'], 10, 1);
    }

    /**
     * @param $attachment_id
     * @return mixed
     */

    public static function webpBulkConverter($attachment_id)
    {
        self::webpConverter($attachment_id);
    }

    /**
     * @param $attachment_id
     * @return mixed
     */

    public static function webpConverter($attachment_id)
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        $irisWebpConverter = new IrisConvert($attachment_id, $metadata);

        if ($irisWebpConverter->checkFileExists($attachment_id) && $irisWebpConverter->checkFileType($attachment_id)) {
            $irisWebpConverter->createArrayOfSizesToBeConverted($metadata);
            $irisWebpConverter->convertArrayOfSizes();
        }

        return $metadata;
    }


    public static function convertImageOnUplodad($image_meta, $attachment_id)
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
