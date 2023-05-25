<?php

namespace ElevenMiles\Iris\WordPress\CLI;

use ElevenMiles\Iris\Debug;
use ElevenMiles\Iris\Iris;
use ElevenMiles\Iris\WordPress\CLI\RunIris;
use Timber\PostQuery;
use WP_CLI;


class CLI
{
    private static $allowed_mime_type = ['image/jpeg', 'image/jpg', 'image/png'];

    public function __invoke($args, $assoc_args)
    {
        WP_CLI::log('Running Iris');

        $images = new PostQuery([
            'post_type' => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => self::$allowed_mime_type,
            'posts_per_page' => 5,
            'orderby' => 'post_date',
            'order' => 'desc',
        ]);
        $progress = WP_CLI\Utils\make_progress_bar('Running Iris', $images);
        // check main image and all sizes and their mime types
        foreach ($images as $image) {

            $metadata = wp_get_attachment_metadata($image->ID);
            $attachment_id = $image->ID;
            Iris::webpConverter($metadata, $attachment_id);
        }

        WP_CLI::log('Iris complete.');
    }

    public function irisConvert()
    {
        \WP_CLI::log('Iris is converting images...');

        $images = new PostQuery([
            // 'p' => 3143,
            'post_type' => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => self::$allowed_mime_type,
            'posts_per_page' => 10,
            'orderby' => 'post_date',
            'order' => 'desc',
        ]);

        // check main image and all sizes and their mime types
        foreach ($images as $image) {
            $metadata = wp_get_attachment_metadata($image->ID);
            $attachment_id = $image->ID;
            Iris::webpBulkConverter($metadata, $attachment_id);
        }

        WP_CLI::log('Iris complete.');
    }
}
