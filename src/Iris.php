<?php

namespace ElevenMiles\Iris;



use ElevenMiles\Iris\WordPress\Admin\AdminPanel;
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

    /**
     * Iris constructor.
     */

    public function __construct()
    {
        new AdminPanel();
        add_action('plugins_loaded', [__CLASS__, 'checkActionSchedulerPlugin']);
        // Check for offload media plugin
        if (!is_plugin_active('action-scheduler/action-scheduler.php')) {
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
            $irisWebpConverter->create_array_of_sizes_to_be_converted($metadata);
            $irisWebpConverter->convert_array_of_sizes();
        }

        return $metadata;
    }
}
