<?php

namespace ElevenMiles\Iris;



use ElevenMiles\Iris\WordPress\Admin\AdminPanel;
use ElevenMiles\Iris\Helper\IrisHelper;
use ElevenMiles\Iris\IrisConvert\IrisConvert;
use ElevenMiles\Iris\WordPress\API;
use ElevenMiles\Iris\WordPress\CLI\Register;
use ElevenMiles\Iris\WordPress\Config;
use ElevenMiles\Iris\WordPress\Scripts;

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(ABSPATH . 'libraries/action-scheduler/action-scheduler.php');

/**
 * Class Iris
 */

class Iris
{
    // move to .env
    const WP_API_NAMESPACE = 'wyke/v1';
    /**
     * Iris constructor 
     */
    public function __construct()
    {
        // 
        new AdminPanel();

        // WordPress config
        add_filter('wp_image_editors', [Config::class, 'defaultToGD']);
        add_filter('image_editor_output_format', [Config::class, 'setWebpAsDefaultMimeType']);
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

        // IRIS :)
        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'irisWebpConverter'], 10, 3);
        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'iris'], 10, 3);
        add_action('delete_attachment', [__CLASS__, 'delete_webp_conversions'], 10);
        add_action('iris_webp_upload_processor', [__CLASS__, 'webpConverter'], 10, 2);
        add_action('iris_webp_bulk_processor', [__CLASS__, 'webpBulkConverter'], 10, 2);
    }

    public static function webpUploadConverter($image_meta, $attachment_id)
    {
        self::iris($image_meta, $attachment_id);
    }

    public static function webpBulkConverter($image_meta, $attachment_id)
    {
        self::irisWebpConverter($image_meta, $attachment_id);
    }


    public static function irisWebpConverter($metadata, $attachment_id)
    {
        as_enqueue_async_action('iris_webp_bulk_processor', [$metadata, $attachment_id], 'iris_webp_processor_task');
        $irisWebpConverter = new IrisConvert($attachment_id, $metadata);
        $irisWebpConverter->checkFileExists($attachment_id);
        $irisWebpConverter->checkFileType($attachment_id);
        $irisWebpConverter->create_array_of_sizes_to_be_converted($metadata);
        $irisWebpConverter->convert_array_of_sizes();

        return $metadata;
    }

    // function delete_webp_conversions($attachment_id)
    // {

    //     $delete_webp_conversions = new IrisConvert($attachment_id);
    //     $delete_webp_conversions->create_array_of_sizes_to_be_deleted($attachment_id);
    //     $delete_webp_conversions->delete_array_of_sizes();
    // }


    public static function iris($image_meta, $attachment_id)
    {
        as_enqueue_async_action('iris_webp_processor', [$image_meta, $attachment_id], 'iris_webp_processor_task');
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
